<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

/**
 * Bed-level accommodation inventory.
 *
 * The rule that drives this whole class: booking one bed in a two-bed room must
 * leave the second bed on sale. Nothing is tracked at "room" level — a private
 * unit buyout is simply a hold on every bed in that unit for those nights.
 *
 * Two tables guarantee correctness under concurrency:
 *   booking_holds  UNIQUE (bed_id, night)  — temporary, expires
 *   bookings       UNIQUE (bed_id, active_night) — permanent, NULL when cancelled
 */
final class AccommodationService
{
    /** Nights the committee is selling, oldest first. */
    public static function nights(): array
    {
        $configured = SettingsService::get('accommodation_nights');

        if (is_string($configured) && trim($configured) !== '') {
            $nights = array_values(array_filter(array_map('trim', explode(',', $configured))));

            if ($nights !== []) {
                return $nights;
            }
        }

        return (array) Config::get('event.nights', []);
    }

    public static function nightLabels(): array
    {
        $labels = [];

        foreach (self::nights() as $night) {
            $labels[$night] = za_date($night, 'D j M');
        }

        return $labels;
    }

    /* -------------------------------------------------------- room types */

    public static function roomTypes(bool $activeOnly = true): array
    {
        $sql = 'SELECT rt.*,
                       (SELECT COUNT(*) FROM room_units ru WHERE ru.room_type_id = rt.id AND ru.is_active = 1) AS unit_count,
                       (SELECT COUNT(*) FROM beds b
                          JOIN room_units ru2 ON ru2.id = b.room_unit_id
                         WHERE ru2.room_type_id = rt.id AND ru2.is_active = 1 AND b.is_active = 1) AS bed_count
                  FROM room_types rt';

        if ($activeOnly) {
            $sql .= ' WHERE rt.is_active = 1';
        }

        return Database::select($sql . ' ORDER BY rt.sort_order, rt.id');
    }

    public static function findRoomType(int|string $identifier): ?array
    {
        $column = is_int($identifier) || ctype_digit((string) $identifier) ? 'id' : 'slug';

        return Database::first("SELECT * FROM room_types WHERE {$column} = ? LIMIT 1", [$identifier]);
    }

    public static function images(int $roomTypeId): array
    {
        return Database::select(
            'SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY sort_order, id',
            [$roomTypeId]
        );
    }

    /* ------------------------------------------------------- availability */

    /**
     * Free beds per night for a room type.
     *
     * @return array<string, int> night => beds available
     */
    public static function availability(int $roomTypeId, ?string $cartToken = null): array
    {
        self::purgeExpiredHolds();

        $availability = [];

        foreach (self::nights() as $night) {
            $availability[$night] = count(self::freeBedIds($roomTypeId, $night, $cartToken));
        }

        return $availability;
    }

    /** Bed ids that are neither booked nor held for that night. */
    public static function freeBedIds(int $roomTypeId, string $night, ?string $cartToken = null): array
    {
        $params = [
            'room_type' => $roomTypeId,
            'night'     => $night,
            'night2'    => $night,
        ];

        $holdClause = 'SELECT bed_id FROM booking_holds WHERE night = :night2 AND expires_at > NOW()';

        // A cart's own holds still count as available to that cart.
        if ($cartToken !== null) {
            $holdClause          .= ' AND cart_token <> :cart_token';
            $params['cart_token'] = $cartToken;
        }

        $sql = "SELECT b.id
                  FROM beds b
                  JOIN room_units ru ON ru.id = b.room_unit_id
                 WHERE ru.room_type_id = :room_type
                   AND ru.is_active = 1
                   AND b.is_active = 1
                   AND b.id NOT IN (
                        SELECT bed_id FROM bookings
                         WHERE active_night = :night
                   )
                   AND b.id NOT IN ({$holdClause})
              ORDER BY ru.sort_order, ru.id, b.sort_order, b.id";

        return array_map('intval', array_column(Database::select($sql, $params), 'id'));
    }

    /** Room units where every bed is free for all of the requested nights. */
    public static function freeUnits(int $roomTypeId, array $nights, ?string $cartToken = null): array
    {
        if ($nights === []) {
            return [];
        }

        $units = Database::select(
            'SELECT ru.id, ru.name, ru.code,
                    (SELECT COUNT(*) FROM beds b WHERE b.room_unit_id = ru.id AND b.is_active = 1) AS bed_count
               FROM room_units ru
              WHERE ru.room_type_id = ? AND ru.is_active = 1
           ORDER BY ru.sort_order, ru.id',
            [$roomTypeId]
        );

        $freeByNight = [];
        foreach ($nights as $night) {
            $freeByNight[$night] = array_flip(self::freeBedIds($roomTypeId, $night, $cartToken));
        }

        $available = [];

        foreach ($units as $unit) {
            $bedIds = array_map(
                'intval',
                array_column(Database::select('SELECT id FROM beds WHERE room_unit_id = ? AND is_active = 1 ORDER BY sort_order, id', [(int) $unit['id']]), 'id')
            );

            if ($bedIds === []) {
                continue;
            }

            $allFree = true;

            foreach ($nights as $night) {
                foreach ($bedIds as $bedId) {
                    if (!isset($freeByNight[$night][$bedId])) {
                        $allFree = false;
                        break 2;
                    }
                }
            }

            if ($allFree) {
                $unit['bed_ids'] = $bedIds;
                $available[]     = $unit;
            }
        }

        return $available;
    }

    /** Totals for the homepage teaser and admin dashboard. */
    public static function occupancySummary(): array
    {
        self::purgeExpiredHolds();

        $totalBeds = (int) Database::scalar(
            'SELECT COUNT(*) FROM beds b JOIN room_units ru ON ru.id = b.room_unit_id
              WHERE b.is_active = 1 AND ru.is_active = 1'
        );

        $nights   = self::nights();
        $byNight  = [];
        $booked   = 0;
        $held     = 0;

        foreach ($nights as $night) {
            $nightBooked = (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE active_night = ?', [$night]);
            $nightHeld   = (int) Database::scalar('SELECT COUNT(*) FROM booking_holds WHERE night = ? AND expires_at > NOW()', [$night]);

            $booked += $nightBooked;
            $held   += $nightHeld;

            $byNight[$night] = [
                'label'     => za_date($night, 'D j M'),
                'total'     => $totalBeds,
                'booked'    => $nightBooked,
                'held'      => $nightHeld,
                'available' => max(0, $totalBeds - $nightBooked - $nightHeld),
                'percent'   => $totalBeds > 0 ? (int) round(($nightBooked / $totalBeds) * 100) : 0,
            ];
        }

        return [
            'total_beds'      => $totalBeds,
            'total_bed_nights' => $totalBeds * max(1, count($nights)),
            'booked'          => $booked,
            'held'            => $held,
            'available'       => max(0, ($totalBeds * count($nights)) - $booked - $held),
            'by_night'        => $byNight,
        ];
    }

    /* -------------------------------------------------------------- rates */

    /** @return array{bed:int, unit:?int, label:?string, available:bool} */
    public static function rateFor(array $roomType, string $night): array
    {
        $rate = Database::first(
            'SELECT * FROM bed_rates WHERE room_type_id = ? AND night = ? LIMIT 1',
            [(int) $roomType['id'], $night]
        );

        if ($rate === null) {
            return [
                'bed'       => (int) $roomType['bed_rate_cents'],
                'unit'      => $roomType['private_unit_rate_cents'] === null ? null : (int) $roomType['private_unit_rate_cents'],
                'label'     => null,
                'available' => true,
            ];
        }

        return [
            'bed'       => (int) $rate['bed_rate_cents'],
            'unit'      => $rate['private_unit_rate_cents'] === null ? null : (int) $rate['private_unit_rate_cents'],
            'label'     => $rate['label'],
            'available' => (bool) $rate['is_available'],
        ];
    }

    public static function ratesFor(array $roomType): array
    {
        $rates = [];

        foreach (self::nights() as $night) {
            $rates[$night] = self::rateFor($roomType, $night);
        }

        return $rates;
    }

    /* -------------------------------------------------------------- holds */

    public static function holdMinutes(): int
    {
        $configured = SettingsService::int('booking_hold_minutes', 0);

        return $configured > 0 ? $configured : (int) Config::get('booking.hold_minutes', 15);
    }

    public static function purgeExpiredHolds(): int
    {
        try {
            return Database::delete('booking_holds', 'expires_at <= NOW()');
        } catch (\Throwable $e) {
            Logger::warning('Could not purge expired holds: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Place a hold on one bed for one night.
     *
     * @throws \RuntimeException when the bed was taken between the availability
     *                           check and the write — the unique index is the
     *                           authority, not the earlier SELECT.
     */
    public static function holdBed(string $cartToken, int $bedId, string $night, int $priceCents, bool $isPrivateUnit = false, ?int $userId = null): int
    {
        $bed = Database::first(
            'SELECT b.id, b.room_unit_id, ru.room_type_id
               FROM beds b JOIN room_units ru ON ru.id = b.room_unit_id
              WHERE b.id = ? AND b.is_active = 1 AND ru.is_active = 1 LIMIT 1',
            [$bedId]
        );

        if ($bed === null) {
            throw new \RuntimeException('That bed is no longer available.');
        }

        self::purgeExpiredHolds();

        if ((int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE bed_id = ? AND active_night = ?', [$bedId, $night]) > 0) {
            throw new \RuntimeException('That bed has just been booked by someone else.');
        }

        $expiresAt = date('Y-m-d H:i:s', time() + (self::holdMinutes() * 60));

        try {
            return Database::insert('booking_holds', [
                'cart_token'      => $cartToken,
                'user_id'         => $userId,
                'bed_id'          => $bedId,
                'room_unit_id'    => (int) $bed['room_unit_id'],
                'room_type_id'    => (int) $bed['room_type_id'],
                'night'           => $night,
                'is_private_unit' => $isPrivateUnit ? 1 : 0,
                'price_cents'     => $priceCents,
                'expires_at'      => $expiresAt,
            ]);
        } catch (\PDOException $e) {
            // 23000 = duplicate key: another cart holds this bed for this night.
            if ($e->getCode() === '23000') {
                $existing = Database::first('SELECT cart_token FROM booking_holds WHERE bed_id = ? AND night = ? LIMIT 1', [$bedId, $night]);

                if ($existing !== null && $existing['cart_token'] === $cartToken) {
                    Database::update(
                        'booking_holds',
                        ['expires_at' => $expiresAt, 'price_cents' => $priceCents, 'is_private_unit' => $isPrivateUnit ? 1 : 0],
                        'bed_id = :bed AND night = :night',
                        ['bed' => $bedId, 'night' => $night]
                    );

                    return (int) Database::scalar('SELECT id FROM booking_holds WHERE bed_id = ? AND night = ?', [$bedId, $night]);
                }

                throw new \RuntimeException('Someone else is checking out with that bed right now. Please choose another.');
            }

            throw $e;
        }
    }

    /** Extend every hold in a cart — called whenever the cart is touched. */
    public static function refreshHolds(string $cartToken): void
    {
        Database::update(
            'booking_holds',
            ['expires_at' => date('Y-m-d H:i:s', time() + (self::holdMinutes() * 60))],
            'cart_token = :token AND expires_at > NOW()',
            ['token' => $cartToken]
        );
    }

    public static function releaseHold(string $cartToken, int $bedId, string $night): void
    {
        Database::delete('booking_holds', 'cart_token = ? AND bed_id = ? AND night = ?', [$cartToken, $bedId, $night]);
    }

    public static function releaseCartHolds(string $cartToken): int
    {
        return Database::delete('booking_holds', 'cart_token = ?', [$cartToken]);
    }

    public static function holdsFor(string $cartToken): array
    {
        return Database::select(
            'SELECT h.*, rt.name AS room_type_name, ru.name AS unit_name, b.label AS bed_label
               FROM booking_holds h
               JOIN beds b ON b.id = h.bed_id
               JOIN room_units ru ON ru.id = h.room_unit_id
               JOIN room_types rt ON rt.id = h.room_type_id
              WHERE h.cart_token = ? AND h.expires_at > NOW()
           ORDER BY h.night, ru.name, b.label',
            [$cartToken]
        );
    }

    public static function earliestHoldExpiry(string $cartToken): ?string
    {
        $value = Database::scalar(
            'SELECT MIN(expires_at) FROM booking_holds WHERE cart_token = ? AND expires_at > NOW()',
            [$cartToken]
        );

        return $value === null ? null : (string) $value;
    }

    /* ----------------------------------------------------------- bookings */

    /**
     * Turn this cart's holds into confirmed bookings. Only ever called from
     * OrderService after PayFast has verified the payment.
     */
    public static function confirmHolds(string $cartToken, array $order, array $guestDetailsByKey = []): array
    {
        $holds   = self::holdsFor($cartToken);
        $created = [];

        foreach ($holds as $hold) {
            $key     = $hold['room_type_id'] . ':' . $hold['night'];
            $details = $guestDetailsByKey[$key] ?? $guestDetailsByKey['default'] ?? [];

            try {
                $bookingId = Database::insert('bookings', [
                    'reference'            => reference_code('BED'),
                    'order_id'             => (int) $order['id'],
                    'user_id'              => $order['user_id'] === null ? null : (int) $order['user_id'],
                    'bed_id'               => (int) $hold['bed_id'],
                    'room_unit_id'         => (int) $hold['room_unit_id'],
                    'room_type_id'         => (int) $hold['room_type_id'],
                    'night'                => $hold['night'],
                    'is_private_unit'      => (int) $hold['is_private_unit'],
                    'guest_name'           => $details['guest_name'] ?? trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
                    'guest_email'          => $details['guest_email'] ?? $order['email'],
                    'guest_phone'          => $details['guest_phone'] ?? $order['phone'],
                    'roommate_request'     => $details['roommate_request'] ?? null,
                    'accessibility_needs'  => $details['accessibility_needs'] ?? null,
                    'notes'                => $details['notes'] ?? null,
                    'price_cents'          => (int) $hold['price_cents'],
                    'status'               => 'confirmed',
                ]);

                $created[] = $bookingId;
            } catch (\PDOException $e) {
                Logger::error('Could not confirm a held bed', [
                    'order'  => $order['reference'] ?? null,
                    'bed_id' => $hold['bed_id'],
                    'night'  => $hold['night'],
                    'error'  => $e->getMessage(),
                ]);
                // Keep going: the rest of the order must still be confirmed, and
                // the admin dashboard flags the order for manual attention.
            }
        }

        self::releaseCartHolds($cartToken);

        return $created;
    }

    public static function cancelBookingsForOrder(int $orderId, string $status = 'cancelled'): int
    {
        return Database::update('bookings', ['status' => $status], 'order_id = :order', ['order' => $orderId]);
    }

    public static function bookingsForUser(int $userId): array
    {
        return Database::select(
            'SELECT bk.*, rt.name AS room_type_name, rt.slug AS room_type_slug,
                    ru.name AS unit_name, b.label AS bed_label, o.reference AS order_reference
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.user_id = ? AND bk.status <> "cancelled"
           ORDER BY bk.night, rt.name',
            [$userId]
        );
    }

    public static function bookingsForOrder(int $orderId): array
    {
        return Database::select(
            'SELECT bk.*, rt.name AS room_type_name, ru.name AS unit_name, b.label AS bed_label
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
              WHERE bk.order_id = ?
           ORDER BY bk.night, ru.name, b.label',
            [$orderId]
        );
    }
}
