<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * The booking chair's working set.
 *
 * The bed board answers "what is where". This answers the questions that
 * actually cost the booking chair their weekend: who has not been given a bed,
 * who asked to share with whom and did it happen, who needs a ground-floor
 * room, who arrives when, and what will run out first.
 *
 * Nothing here invents a number. Every count comes from the same tables the
 * booking engine writes to, so the console and the board can never disagree.
 */
final class RoomingService
{
    /* ------------------------------------------------------------ occupancy */

    /** Occupancy per room type per night — where the pressure is. */
    public static function occupancyByType(): array
    {
        $nights = AccommodationService::nights();
        $types  = AccommodationService::roomTypes(false);
        $rows   = [];

        foreach ($types as $type) {
            $beds = (int) Database::scalar(
                'SELECT COUNT(*) FROM beds b
                   JOIN room_units ru ON ru.id = b.room_unit_id
                  WHERE ru.room_type_id = ? AND b.is_active = 1 AND ru.is_active = 1',
                [(int) $type['id']]
            );

            $byNight = [];
            $sold    = 0;

            foreach ($nights as $night) {
                $booked = (int) Database::scalar(
                    'SELECT COUNT(*) FROM bookings WHERE room_type_id = ? AND active_night = ?',
                    [(int) $type['id'], $night]
                );

                $held = (int) Database::scalar(
                    'SELECT COUNT(*) FROM booking_holds WHERE room_type_id = ? AND night = ? AND expires_at > NOW()',
                    [(int) $type['id'], $night]
                );

                $sold += $booked;

                $byNight[$night] = [
                    'booked'    => $booked,
                    'held'      => $held,
                    'free'      => max(0, $beds - $booked - $held),
                    'percent'   => $beds > 0 ? (int) round(($booked / $beds) * 100) : 0,
                ];
            }

            $rows[] = [
                'room_type'  => $type,
                'beds'       => $beds,
                'bed_nights' => $beds * max(1, count($nights)),
                'sold'       => $sold,
                'percent'    => $beds > 0 && $nights !== []
                    ? (int) round(($sold / ($beds * count($nights))) * 100)
                    : 0,
                'by_night'   => $byNight,
            ];
        }

        return ['nights' => $nights, 'rows' => $rows];
    }

    /**
     * The night that sells out first, and how much room is left on it. This is
     * the single number the committee asks for at every meeting.
     */
    public static function tightestNight(): ?array
    {
        $summary = AccommodationService::occupancySummary();
        $tightest = null;

        foreach ($summary['by_night'] as $night => $row) {
            if ($tightest === null || $row['available'] < $tightest['available']) {
                $tightest = $row + ['night' => $night];
            }
        }

        return $tightest;
    }

    /* --------------------------------------------------------- problem list */

    /**
     * Bookings that need a human before anyone arrives. Each one carries a
     * plain-English reason, because "exception" on its own helps nobody.
     */
    public static function problems(): array
    {
        $problems = [];

        // 1. Paid for a bed and never got one. This is the worst thing that can
        //    happen, so it is checked first and reported loudest.
        $unallocated = Database::select(
            'SELECT o.id, o.reference, o.email, o.first_name, o.last_name, o.paid_at,
                    oi.description, oi.night, oi.room_type_id, rt.name AS room_type_name
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
          LEFT JOIN room_types rt ON rt.id = oi.room_type_id
              WHERE oi.item_type = "accommodation"
                AND o.status = "paid"
                AND NOT EXISTS (
                      SELECT 1 FROM bookings bk
                       WHERE bk.order_id = o.id
                         AND bk.night = oi.night
                         AND bk.status IN ("confirmed","checked_in")
                    )
           ORDER BY o.paid_at'
        );

        foreach ($unallocated as $row) {
            $problems[] = [
                'severity' => 'critical',
                'kind'     => 'No bed allocated',
                'reason'   => 'This order is paid for a bed on ' . za_date((string) $row['night'], 'D j M')
                    . ' but no bed was ever allocated. Allocate one now.',
                'who'      => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: (string) $row['email'],
                'order_id' => (int) $row['id'],
                'order_reference' => (string) $row['reference'],
                'night'    => (string) $row['night'],
                'action'   => 'order',
            ];
        }

        // 2. A bed with nobody's name on it. Reception cannot hand over a key.
        $nameless = Database::select(
            'SELECT bk.*, o.reference AS order_reference, o.email AS order_email,
                    ru.name AS unit_name, b.label AS bed_label
               FROM bookings bk
          LEFT JOIN orders o ON o.id = bk.order_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
              WHERE bk.status IN ("confirmed","checked_in")
                AND (bk.guest_name IS NULL OR TRIM(bk.guest_name) = "")
           ORDER BY bk.night, ru.name'
        );

        foreach ($nameless as $row) {
            $problems[] = [
                'severity' => 'warning',
                'kind'     => 'No guest name',
                'reason'   => 'A bed is held in ' . $row['unit_name'] . ' on ' . za_date((string) $row['night'], 'D j M')
                    . ' with no name on it. Reception cannot check anyone in without one.',
                'who'      => (string) ($row['order_email'] ?: 'unknown'),
                'booking_id' => (int) $row['id'],
                'order_id' => $row['order_id'] === null ? null : (int) $row['order_id'],
                'order_reference' => (string) ($row['order_reference'] ?? ''),
                'night'    => (string) $row['night'],
                'action'   => 'booking',
            ];
        }

        // 3. An accessibility need in a room type that is not the accessible one.
        $accessible = Database::select(
            'SELECT bk.*, rt.name AS room_type_name, rt.slug AS room_type_slug,
                    ru.name AS unit_name, b.label AS bed_label, o.reference AS order_reference
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")
                AND bk.accessibility_needs IS NOT NULL AND TRIM(bk.accessibility_needs) <> ""
                AND rt.slug NOT LIKE "%accessible%"
           ORDER BY bk.night'
        );

        foreach ($accessible as $row) {
            $problems[] = [
                'severity' => 'warning',
                'kind'     => 'Accessibility need in a standard room',
                'reason'   => trim((string) $row['guest_name']) . ' asked for "' . excerpt((string) $row['accessibility_needs'], 70)
                    . '" but is in ' . $row['room_type_name'] . '. Move them to an accessible room if one is free.',
                'who'      => (string) $row['guest_name'],
                'booking_id' => (int) $row['id'],
                'order_id' => $row['order_id'] === null ? null : (int) $row['order_id'],
                'order_reference' => (string) ($row['order_reference'] ?? ''),
                'night'    => (string) $row['night'],
                'action'   => 'move',
            ];
        }

        return $problems;
    }

    /* ------------------------------------------------------ roommate pairing */

    /**
     * Everyone who asked to share with somebody, and whether it actually
     * happened. A request is "matched" when the named person is in the same
     * room unit on the same night.
     */
    public static function roommateRequests(): array
    {
        $requests = Database::select(
            'SELECT bk.id, bk.reference, bk.night, bk.guest_name, bk.guest_email, bk.roommate_request,
                    bk.room_unit_id, ru.name AS unit_name, rt.name AS room_type_name,
                    b.label AS bed_label, o.reference AS order_reference, o.id AS order_id
               FROM bookings bk
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN beds b ON b.id = bk.bed_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")
                AND bk.roommate_request IS NOT NULL AND TRIM(bk.roommate_request) <> ""
           ORDER BY bk.night, bk.guest_name'
        );

        foreach ($requests as &$request) {
            $wanted = trim((string) $request['roommate_request']);

            // Who else is in this unit on this night?
            $others = Database::select(
                'SELECT bk.guest_name, bk.guest_email, b.label AS bed_label
                   FROM bookings bk
                   JOIN beds b ON b.id = bk.bed_id
                  WHERE bk.room_unit_id = ? AND bk.night = ? AND bk.id <> ?
                    AND bk.status IN ("confirmed","checked_in")',
                [(int) $request['room_unit_id'], $request['night'], (int) $request['id']]
            );

            $request['sharing_with'] = $others;
            $request['matched']      = false;

            foreach ($others as $other) {
                $name = strtolower(trim((string) $other['guest_name']));

                if ($name !== '' && str_contains(strtolower($wanted), $name)) {
                    $request['matched'] = true;
                    break;
                }

                // Also try the other way round: "Thabo" matching "Thabo Mokoena".
                if ($name !== '' && str_contains($name, strtolower($wanted))) {
                    $request['matched'] = true;
                    break;
                }
            }

            // Is the person they asked for even booked at all?
            $request['requested_is_booked'] = (int) Database::scalar(
                'SELECT COUNT(*) FROM bookings
                  WHERE status IN ("confirmed","checked_in") AND night = ?
                    AND (guest_name LIKE ? OR guest_email LIKE ?)',
                [$request['night'], '%' . $wanted . '%', '%' . $wanted . '%']
            ) > 0;
        }
        unset($request);

        return $requests;
    }

    /* ------------------------------------------------------ special requests */

    /** Everything a guest asked for that a human has to act on. */
    public static function specialRequests(): array
    {
        return Database::select(
            'SELECT bk.id, bk.reference, bk.night, bk.guest_name, bk.guest_email, bk.guest_phone,
                    bk.accessibility_needs, bk.notes, bk.roommate_request,
                    ru.name AS unit_name, b.label AS bed_label, rt.name AS room_type_name,
                    o.id AS order_id, o.reference AS order_reference, o.customer_note
               FROM bookings bk
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
               JOIN room_types rt ON rt.id = bk.room_type_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")
                AND (
                      (bk.accessibility_needs IS NOT NULL AND TRIM(bk.accessibility_needs) <> "")
                   OR (bk.notes IS NOT NULL AND TRIM(bk.notes) <> "")
                   OR (o.customer_note IS NOT NULL AND TRIM(o.customer_note) <> "")
                )
           ORDER BY bk.night, ru.name, b.label'
        );
    }

    /* --------------------------------------------------- arrivals/departures */

    /**
     * Who arrives and who leaves on each night. A guest "arrives" on the first
     * night they hold a bed and "departs" the morning after their last.
     */
    public static function arrivalsAndDepartures(): array
    {
        $stays = Database::select(
            'SELECT bk.guest_name, bk.guest_email, bk.guest_phone, bk.order_id,
                    MIN(bk.night) AS first_night, MAX(bk.night) AS last_night,
                    COUNT(*) AS nights,
                    GROUP_CONCAT(DISTINCT ru.name ORDER BY ru.name SEPARATOR ", ") AS units,
                    o.reference AS order_reference, o.checkin_code
               FROM bookings bk
               JOIN room_units ru ON ru.id = bk.room_unit_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")
           GROUP BY bk.order_id, bk.guest_name, bk.guest_email, bk.guest_phone, o.reference, o.checkin_code
           ORDER BY first_night, bk.guest_name'
        );

        $nights = AccommodationService::nights();
        $byDay  = [];

        foreach ($nights as $night) {
            $byDay[$night] = ['arriving' => [], 'departing' => [], 'staying' => 0, 'is_checkout_only' => false];
        }

        // Nobody sleeps on the check-out morning, but reception still has to
        // say goodbye to everybody, so it gets a row of its own.
        if ($nights !== []) {
            $checkout = date('Y-m-d', strtotime(end($nights) . ' +1 day'));

            $byDay[$checkout] = ['arriving' => [], 'departing' => [], 'staying' => 0, 'is_checkout_only' => true];
        }

        foreach ($stays as $stay) {
            $first     = (string) $stay['first_night'];
            $last      = (string) $stay['last_night'];
            $departure = date('Y-m-d', strtotime($last . ' +1 day'));

            if (isset($byDay[$first])) {
                $byDay[$first]['arriving'][] = $stay;
            }

            if (isset($byDay[$departure])) {
                $byDay[$departure]['departing'][] = $stay;
            }

            foreach ($byDay as $night => $day) {
                if (!$day['is_checkout_only'] && $night >= $first && $night <= $last) {
                    $byDay[$night]['staying']++;
                }
            }
        }

        return ['by_day' => $byDay, 'stays' => $stays];
    }

    /* -------------------------------------------------------------- run sheet */

    /** The door list: every unit, who is in it, night by night. */
    public static function runSheet(?string $night = null): array
    {
        $nights = $night !== null && $night !== '' ? [$night] : AccommodationService::nights();
        $sheet  = [];

        foreach ($nights as $currentNight) {
            $rows = Database::select(
                'SELECT ru.id AS unit_id, ru.name AS unit_name, rt.name AS room_type_name,
                        b.label AS bed_label, b.id AS bed_id,
                        bk.id AS booking_id, bk.guest_name, bk.guest_phone, bk.guest_email,
                        bk.accessibility_needs, bk.notes, bk.status,
                        o.reference AS order_reference, o.checkin_code
                   FROM room_units ru
                   JOIN room_types rt ON rt.id = ru.room_type_id
                   JOIN beds b ON b.room_unit_id = ru.id
              LEFT JOIN bookings bk ON bk.bed_id = b.id AND bk.active_night = :night
              LEFT JOIN orders o ON o.id = bk.order_id
                  WHERE ru.is_active = 1 AND b.is_active = 1
               ORDER BY rt.sort_order, ru.sort_order, ru.name, b.sort_order, b.label',
                ['night' => $currentNight]
            );

            $units = [];

            foreach ($rows as $row) {
                $units[(int) $row['unit_id']]['name']      = $row['unit_name'];
                $units[(int) $row['unit_id']]['room_type'] = $row['room_type_name'];
                $units[(int) $row['unit_id']]['beds'][]    = $row;
            }

            $sheet[$currentNight] = $units;
        }

        return $sheet;
    }

    /* ------------------------------------------------------------ moving beds */

    /**
     * Beds a booking could be moved to on its night: free, active, and not the
     * one it already has. The move itself still goes through the unique index,
     * so even a stale list cannot cause a double booking.
     */
    public static function movableBeds(array $booking): array
    {
        return Database::select(
            'SELECT b.id, b.label, ru.name AS unit_name, rt.name AS room_type_name, rt.id AS room_type_id
               FROM beds b
               JOIN room_units ru ON ru.id = b.room_unit_id
               JOIN room_types rt ON rt.id = ru.room_type_id
              WHERE b.is_active = 1 AND ru.is_active = 1
                AND b.id <> :current
                AND NOT EXISTS (SELECT 1 FROM bookings bk WHERE bk.bed_id = b.id AND bk.active_night = :night)
                AND NOT EXISTS (SELECT 1 FROM booking_holds h WHERE h.bed_id = b.id AND h.night = :night2 AND h.expires_at > NOW())
           ORDER BY rt.sort_order, ru.sort_order, ru.name, b.sort_order, b.label',
            [
                'current' => (int) $booking['bed_id'],
                'night'   => $booking['night'],
                'night2'  => $booking['night'],
            ]
        );
    }

    /**
     * Move a booking to a different bed. Returns an error string, or null on
     * success. The database is the final authority: if the target bed was taken
     * between rendering the form and pressing the button, the unique index
     * refuses the write and the guest keeps the bed they had.
     */
    public static function moveBooking(array $booking, int $targetBedId): ?string
    {
        $target = Database::first(
            'SELECT b.*, ru.id AS unit_id, ru.name AS unit_name, ru.room_type_id, ru.is_active AS unit_active
               FROM beds b JOIN room_units ru ON ru.id = b.room_unit_id
              WHERE b.id = ?',
            [$targetBedId]
        );

        if ($target === null) {
            return 'That bed does not exist.';
        }

        if ((int) $target['is_active'] !== 1 || (int) $target['unit_active'] !== 1) {
            return 'That bed is out of service.';
        }

        $held = Database::first(
            'SELECT * FROM booking_holds WHERE bed_id = ? AND night = ? AND expires_at > NOW()',
            [$targetBedId, $booking['night']]
        );

        if ($held !== null) {
            return 'Somebody has that bed in their cart right now. Try again in a few minutes, or pick another bed.';
        }

        try {
            Database::update('bookings', [
                'bed_id'       => $targetBedId,
                'room_unit_id' => (int) $target['unit_id'],
                'room_type_id' => (int) $target['room_type_id'],
            ], 'id = :id', ['id' => (int) $booking['id']]);
        } catch (\PDOException $e) {
            // 23000 is the unique index on (bed_id, active_night) doing its job.
            if ($e->getCode() === '23000') {
                return 'That bed was taken while you were looking at this page. Nothing has changed — pick another one.';
            }

            throw $e;
        }

        return null;
    }
}
