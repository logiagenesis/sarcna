<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;
use App\Services\AccommodationService;
use App\Services\RoomingService;

/**
 * The booking chair's console.
 *
 * Everything on these screens is about the weekend itself rather than the
 * money: who has a bed, who has not, who asked to share with whom, who needs a
 * ground-floor room, and what reception hands out at the door.
 */
final class RoomingController extends AdminController
{
    public function operations(): string
    {
        AccommodationService::purgeExpiredHolds();

        return $this->render('admin.rooming-operations', 'Rooming operations', [
            'occupancy'     => AccommodationService::occupancySummary(),
            'byType'        => RoomingService::occupancyByType(),
            'tightest'      => RoomingService::tightestNight(),
            'problems'      => RoomingService::problems(),
            'roommates'     => RoomingService::roommateRequests(),
            'requests'      => RoomingService::specialRequests(),
            'movement'      => RoomingService::arrivalsAndDepartures(),
            'holds'         => (int) Database::scalar('SELECT COUNT(*) FROM booking_holds WHERE expires_at > NOW()'),
        ]);
    }

    /** The printable door list reception works off. */
    public function runSheet(): string
    {
        $night  = (string) $this->request->input('night', '');
        $nights = AccommodationService::nights();

        if ($night !== '' && !in_array($night, $nights, true)) {
            $night = '';
        }

        return $this->render('admin.rooming-run-sheet', 'Run sheet', [
            'night'  => $night,
            'nights' => $nights,
            'sheet'  => RoomingService::runSheet($night === '' ? null : $night),
        ]);
    }

    /** The move-a-guest screen: one booking, every bed it could go to. */
    public function move(string $id): string
    {
        $booking = $this->findBooking((int) $id);

        return $this->render('admin.rooming-move', 'Move a guest', [
            'booking'   => $booking,
            'beds'      => RoomingService::movableBeds($booking),
            'sharing'   => Database::select(
                'SELECT bk.guest_name, bk.guest_email, b.label AS bed_label
                   FROM bookings bk JOIN beds b ON b.id = bk.bed_id
                  WHERE bk.room_unit_id = ? AND bk.night = ? AND bk.id <> ?
                    AND bk.status IN ("confirmed","checked_in")',
                [(int) $booking['room_unit_id'], $booking['night'], (int) $booking['id']]
            ),
        ]);
    }

    public function applyMove(string $id): never
    {
        $booking = $this->findBooking((int) $id);
        $target  = $this->request->int('bed_id', 0);

        if ($target <= 0) {
            $this->flashError('Choose a bed to move this guest to.');
            $this->back(url('/admin/bookings/' . $booking['id'] . '/move'));
        }

        $error = RoomingService::moveBooking($booking, $target);

        if ($error !== null) {
            $this->flashError($error);
            $this->back(url('/admin/bookings/' . $booking['id'] . '/move'));
        }

        $moved = $this->findBooking((int) $booking['id']);

        $this->audit(
            sprintf('moved %s from %s to %s', $booking['guest_name'] ?: 'a guest', $booking['unit_name'], $moved['unit_name']),
            'booking',
            (int) $booking['id'],
            ['from_bed' => (int) $booking['bed_id'], 'to_bed' => $target, 'night' => $booking['night']]
        );

        $this->flashSuccess(sprintf(
            '%s moved to %s · %s for %s.',
            $booking['guest_name'] ?: 'Guest',
            $moved['unit_name'],
            $moved['bed_label'],
            za_date((string) $booking['night'], 'D j M')
        ));

        $this->redirect(url('/admin/bookings/operations'));
    }

    /** Fill in or correct the guest details on a booking. */
    public function saveGuest(string $id): never
    {
        $booking = $this->findBooking((int) $id);

        $validator = Validator::make($this->request->all(), [
            'guest_name' => 'required|max:120',
        ], ['guest_name' => 'Guest name']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        Database::update('bookings', [
            'guest_name'          => (string) $this->request->input('guest_name'),
            'guest_email'         => (string) $this->request->input('guest_email', ''),
            'guest_phone'         => (string) $this->request->input('guest_phone', ''),
            'roommate_request'    => (string) $this->request->input('roommate_request', ''),
            'accessibility_needs' => (string) $this->request->input('accessibility_needs', ''),
            'notes'               => (string) $this->request->input('notes', ''),
        ], 'id = :id', ['id' => (int) $booking['id']]);

        $this->audit('updated the guest details on a booking', 'booking', (int) $booking['id']);
        $this->flashSuccess('Guest details saved.');
        $this->back(url('/admin/bookings/operations'));
    }

    private function findBooking(int $id): array
    {
        $booking = Database::first(
            'SELECT bk.*, rt.name AS room_type_name, ru.name AS unit_name, b.label AS bed_label,
                    o.reference AS order_reference, o.email AS order_email
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.id = ?',
            [$id]
        );

        if ($booking === null) {
            $this->abort(404);
        }

        return $booking;
    }
}
