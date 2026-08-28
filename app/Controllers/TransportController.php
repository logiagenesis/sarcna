<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\SeoService;
use App\Services\SettingsService;
use App\Services\TransportService;

final class TransportController extends Controller
{
    public function index(): string
    {
        $routes = TransportService::routes();

        foreach ($routes as &$route) {
            $route['slots'] = TransportService::slots((int) $route['id'], false);
        }
        unset($route);

        SeoService::breadcrumbs(['Transport' => '/transport']);

        return $this->page('pages.transport-index', [
            'title'       => 'Transport & Shuttle Booking',
            'description' => 'Book a shuttle seat to the SARCNA 2027 Convention from Cape Town International Airport, the city centre, Stellenbosch or around the Winelands.',
            'image'       => '/assets/img/transport/airport-shuttle.jpg',
        ], [
            'routes'  => $routes,
            'summary' => TransportService::summary(),
            'isOpen'  => SettingsService::bool('transport_enabled', true),
        ]);
    }

    public function show(string $slug): string
    {
        $route = TransportService::findRoute($slug);

        if ($route === null || (int) $route['is_active'] !== 1) {
            $this->abort(404);
        }

        $slots = TransportService::slots((int) $route['id'], false);

        SeoService::breadcrumbs(['Transport' => '/transport', $route['name'] => '/transport/' . $route['slug']]);

        return $this->page('pages.transport-show', [
            'title'       => $route['name'],
            'description' => excerpt($route['description'], 155) ?: 'Book a seat on the ' . $route['name'] . ' shuttle for the SARCNA 2027 Convention.',
            'image'       => '/assets/img/transport/winelands-road.jpg',
        ], [
            'route'  => $route,
            'slots'  => $slots,
            'others' => array_slice(array_filter(
                TransportService::routes(),
                static fn (array $item): bool => (int) $item['id'] !== (int) $route['id']
            ), 0, 3),
            'isOpen' => SettingsService::bool('transport_enabled', true),
        ]);
    }

    public function book(string $slug): never
    {
        if (!SettingsService::bool('transport_enabled', true)) {
            $this->flashError('Transport booking is closed at the moment.');
            $this->back(url('/transport'));
        }

        $route = TransportService::findRoute($slug);

        if ($route === null || (int) $route['is_active'] !== 1) {
            $this->abort(404);
        }

        $validator = Validator::make($this->request->all(), [
            'slot_id'        => 'required|integer',
            'passenger_name' => 'required|max:160',
            'phone'          => 'required|phone',
            'email'          => 'required|email|max:190',
            'luggage_count'  => 'integer|gte:0|lte:6',
            'seats'          => 'integer|gte:1|lte:6',
        ], [
            'passenger_name' => 'Passenger name',
            'slot_id'        => 'Departure time',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $slot = TransportService::findSlot($this->request->int('slot_id'));

        if ($slot === null || (int) $slot['route_id'] !== (int) $route['id'] || (int) $slot['is_active'] !== 1) {
            $this->flashError('That departure is no longer available.');
            $this->back(url('/transport/' . $route['slug']));
        }

        $seats = max(1, min(6, $this->request->int('seats', 1)));
        $left  = TransportService::seatsLeft((int) $slot['id']);

        if ($left < $seats) {
            $this->flashError($left === 0
                ? 'That departure is full. Please choose another time.'
                : sprintf('Only %d seat%s left on that departure.', $left, $left === 1 ? '' : 's'));
            $this->back(url('/transport/' . $route['slug']));
        }

        if ((int) $route['requires_flight_number'] === 1 && trim((string) $this->request->input('flight_number', '')) === '') {
            $this->flashError('Please give us your flight number so we can match you to the right shuttle.');
            $this->back(url('/transport/' . $route['slug']));
        }

        CartService::add([
            'item_type'         => 'transport',
            'transport_slot_id' => (int) $slot['id'],
            'description'       => sprintf('%s — %s', $route['name'], za_date((string) $slot['departs_at'], 'D j M, H:i')),
            'unit_price_cents'  => (int) $route['price_cents'],
            'quantity'          => $seats,
            'meta'              => [
                'route_id'            => (int) $route['id'],
                'route_name'          => $route['name'],
                'departs_at'          => $slot['departs_at'],
                'pickup_point'        => $slot['pickup_point'],
                'dropoff_point'       => $slot['dropoff_point'],
                'passenger_name'      => (string) $this->request->input('passenger_name'),
                'phone'               => (string) $this->request->input('phone'),
                'email'               => (string) $this->request->input('email'),
                'flight_number'       => (string) $this->request->input('flight_number', ''),
                'luggage_count'       => $this->request->int('luggage_count', 1),
                'accessibility_needs' => (string) $this->request->input('accessibility_needs', ''),
                'notes'               => (string) $this->request->input('notes', ''),
            ],
        ]);

        $this->flashSuccess(sprintf('%d seat%s on the %s added to your cart.', $seats, $seats === 1 ? '' : 's', $route['name']));
        $this->redirect(url('/cart'));
    }
}
