<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;
use App\Services\TransportService;

final class TransportController extends AdminController
{
    public function index(): string
    {
        $routes = TransportService::routes(false);

        foreach ($routes as &$route) {
            $route['slots'] = Database::select(
                'SELECT s.*, (SELECT COUNT(*) FROM transport_bookings tb WHERE tb.slot_id = s.id AND tb.status <> "cancelled") AS passengers
                   FROM transport_slots s WHERE s.route_id = ? ORDER BY s.departs_at',
                [(int) $route['id']]
            );
        }
        unset($route);

        return $this->render('admin.transport', 'Transport', [
            'routes'  => $routes,
            'summary' => TransportService::summary(),
        ]);
    }

    public function saveRoute(): never
    {
        $validator = Validator::make($this->request->all(), [
            'name'      => 'required|max:160',
            'price'     => 'required|numeric|gte:0',
            'direction' => 'required|in:to_venue,from_venue,return,onsite',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'name'                   => (string) $this->request->input('name'),
            'slug'                   => slugify((string) ($this->request->input('slug') ?: $this->request->input('name'))),
            'description'            => (string) $this->request->input('description', ''),
            'direction'              => (string) $this->request->input('direction'),
            'price_cents'            => rands($this->request->input('price', 0)),
            'requires_flight_number' => $this->request->bool('requires_flight_number') ? 1 : 0,
            'sort_order'             => $this->request->int('sort_order', 0),
            'is_active'              => $this->request->bool('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            Database::update('transport_routes', $data, 'id = :id', ['id' => $id]);
            $this->audit('updated a transport route', 'transport_route', $id);
            $this->flashSuccess('Route saved.');
        } else {
            $existing = Database::scalar('SELECT id FROM transport_routes WHERE slug = ?', [$data['slug']]);

            if ($existing !== null) {
                $data['slug'] .= '-' . bin2hex(random_bytes(2));
            }

            $id = Database::insert('transport_routes', $data);
            $this->audit('created a transport route', 'transport_route', $id);
            $this->flashSuccess('Route created. Now add its departure times.');
        }

        $this->back(url('/admin/transport'));
    }

    public function deleteRoute(string $id): never
    {
        $passengers = (int) Database::scalar('SELECT COUNT(*) FROM transport_bookings WHERE route_id = ? AND status <> "cancelled"', [(int) $id]);

        if ($passengers > 0) {
            $this->flashError('That route has ' . $passengers . ' booked passenger(s). Deactivate it instead of deleting it.');
            $this->back();
        }

        Database::delete('transport_routes', 'id = ?', [(int) $id]);

        $this->audit('deleted a transport route', 'transport_route', (int) $id);
        $this->flashSuccess('Route deleted.');
        $this->back(url('/admin/transport'));
    }

    public function saveSlot(): never
    {
        $validator = Validator::make($this->request->all(), [
            'route_id'      => 'required|integer|exists:transport_routes,id',
            'departs_at'    => 'required|date',
            'pickup_point'  => 'required|max:180',
            'dropoff_point' => 'required|max:180',
            'capacity'      => 'required|integer|gte:1|lte:200',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'route_id'      => $this->request->int('route_id'),
            'departs_at'    => date('Y-m-d H:i:s', (int) strtotime((string) $this->request->input('departs_at'))),
            'pickup_point'  => (string) $this->request->input('pickup_point'),
            'dropoff_point' => (string) $this->request->input('dropoff_point'),
            'capacity'      => $this->request->int('capacity', 22),
            'notes'         => (string) $this->request->input('notes', ''),
            'is_active'     => $this->request->bool('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            $slot = Database::first('SELECT * FROM transport_slots WHERE id = ?', [$id]);

            if ($slot !== null && $data['capacity'] < (int) $slot['seats_taken']) {
                $this->flashError('Capacity cannot be lower than the ' . (int) $slot['seats_taken'] . ' seat(s) already sold.');
                $this->back();
            }

            Database::update('transport_slots', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Departure saved.');
        } else {
            $id = Database::insert('transport_slots', $data);
            $this->flashSuccess('Departure added.');
        }

        $this->audit('saved a transport departure', 'transport_slot', $id);
        $this->back(url('/admin/transport'));
    }

    public function deleteSlot(string $id): never
    {
        $passengers = (int) Database::scalar('SELECT COUNT(*) FROM transport_bookings WHERE slot_id = ? AND status <> "cancelled"', [(int) $id]);

        if ($passengers > 0) {
            $this->flashError('That departure has passengers booked. Deactivate it instead.');
            $this->back();
        }

        Database::delete('transport_slots', 'id = ?', [(int) $id]);

        $this->audit('deleted a transport departure', 'transport_slot', (int) $id);
        $this->flashSuccess('Departure deleted.');
        $this->back(url('/admin/transport'));
    }

    public function manifest(string $slotId): string
    {
        $slot = TransportService::findSlot((int) $slotId);

        if ($slot === null) {
            $this->abort(404);
        }

        return $this->render('admin.transport-manifest', 'Passenger manifest', [
            'slot'       => $slot,
            'passengers' => TransportService::manifest((int) $slot['id']),
        ]);
    }

    public function checkIn(string $id): never
    {
        $booking = Database::first('SELECT * FROM transport_bookings WHERE id = ?', [(int) $id]);

        if ($booking === null) {
            $this->abort(404);
        }

        $checkedIn = $booking['checked_in_at'] === null;

        Database::update('transport_bookings', [
            'status'        => $checkedIn ? 'checked_in' : 'confirmed',
            'checked_in_at' => $checkedIn ? date('Y-m-d H:i:s') : null,
        ], 'id = :id', ['id' => (int) $booking['id']]);

        $this->audit($checkedIn ? 'checked in a passenger' : 'undid a passenger check-in', 'transport_booking', (int) $booking['id']);
        $this->back();
    }
}
