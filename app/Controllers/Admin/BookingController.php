<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccommodationService;

final class BookingController extends AdminController
{
    public function index(): string
    {
        $night  = (string) $this->request->input('night', '');
        $room   = $this->request->int('room_type_id', 0);
        $search = trim((string) $this->request->input('q', ''));

        $where  = ['bk.status <> "cancelled"'];
        $params = [];

        if ($night !== '') {
            $where[]         = 'bk.night = :night';
            $params['night'] = $night;
        }

        if ($room > 0) {
            $where[]        = 'bk.room_type_id = :room';
            $params['room'] = $room;
        }

        if ($search !== '') {
            $where[]          = '(bk.guest_name LIKE :search OR bk.reference LIKE :search OR bk.guest_email LIKE :search OR o.reference LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM bookings bk LEFT JOIN orders o ON o.id = bk.order_id WHERE {$clause}",
            "SELECT bk.*, rt.name AS room_type_name, ru.name AS unit_name, b.label AS bed_label, o.reference AS order_reference, o.email AS order_email
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN room_units ru ON ru.id = bk.room_unit_id
               JOIN beds b ON b.id = bk.bed_id
          LEFT JOIN orders o ON o.id = bk.order_id
              WHERE {$clause}
           ORDER BY bk.night, rt.sort_order, ru.name, b.label",
            $params,
            60
        );

        return $this->render('admin.bookings', 'Accommodation bookings', [
            'result'    => $result,
            'night'     => $night,
            'room'      => $room,
            'search'    => $search,
            'nights'    => AccommodationService::nights(),
            'roomTypes' => AccommodationService::roomTypes(false),
        ]);
    }

    /** A grid of every unit against every night — the view the committee lives in. */
    public function board(): string
    {
        AccommodationService::purgeExpiredHolds();

        $nights = AccommodationService::nights();
        $units  = Database::select(
            'SELECT ru.*, rt.name AS room_type_name, rt.id AS room_type_id, rt.sort_order AS type_order
               FROM room_units ru JOIN room_types rt ON rt.id = ru.room_type_id
           ORDER BY rt.sort_order, ru.sort_order, ru.id'
        );

        $beds = Database::select('SELECT * FROM beds ORDER BY room_unit_id, sort_order');

        $bedsByUnit = [];
        foreach ($beds as $bed) {
            $bedsByUnit[(int) $bed['room_unit_id']][] = $bed;
        }

        $bookings = Database::select(
            'SELECT bk.bed_id, bk.night, bk.guest_name, bk.reference, bk.status, o.reference AS order_reference
               FROM bookings bk LEFT JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")'
        );

        $booked = [];
        foreach ($bookings as $booking) {
            $booked[(int) $booking['bed_id']][$booking['night']] = $booking;
        }

        $held = [];
        foreach (Database::select('SELECT bed_id, night FROM booking_holds WHERE expires_at > NOW()') as $hold) {
            $held[(int) $hold['bed_id']][$hold['night']] = true;
        }

        return $this->render('admin.booking-board', 'Bed board', [
            'nights'     => $nights,
            'units'      => $units,
            'bedsByUnit' => $bedsByUnit,
            'booked'     => $booked,
            'held'       => $held,
            'occupancy'  => AccommodationService::occupancySummary(),
        ]);
    }

    public function holds(): string
    {
        AccommodationService::purgeExpiredHolds();

        return $this->render('admin.booking-holds', 'Live bed holds', [
            'holds' => Database::select(
                'SELECT h.*, rt.name AS room_type_name, ru.name AS unit_name, b.label AS bed_label, u.email AS user_email
                   FROM booking_holds h
                   JOIN beds b ON b.id = h.bed_id
                   JOIN room_units ru ON ru.id = h.room_unit_id
                   JOIN room_types rt ON rt.id = h.room_type_id
              LEFT JOIN users u ON u.id = h.user_id
                  WHERE h.expires_at > NOW()
               ORDER BY h.expires_at'
            ),
            'holdMinutes' => AccommodationService::holdMinutes(),
        ]);
    }

    public function updateStatus(string $id): never
    {
        $booking = Database::first('SELECT * FROM bookings WHERE id = ?', [(int) $id]);
        $status  = (string) $this->request->input('status', '');

        if ($booking === null) {
            $this->abort(404);
        }

        if (!in_array($status, ['confirmed', 'checked_in', 'cancelled', 'refunded'], true)) {
            $this->flashError('That is not a valid booking status.');
            $this->back();
        }

        Database::update('bookings', [
            'status'        => $status,
            'checked_in_at' => $status === 'checked_in' ? date('Y-m-d H:i:s') : null,
        ], 'id = :id', ['id' => (int) $booking['id']]);

        $this->audit('changed a bed booking to ' . $status, 'booking', (int) $booking['id']);
        $this->flashSuccess('Booking ' . $booking['reference'] . ' is now ' . str_replace('_', ' ', $status) . '.');
        $this->back();
    }
}
