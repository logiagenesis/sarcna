<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Services\AccommodationService;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\MailService;
use App\Services\OrderService;
use App\Services\PayFastService;
use App\Services\SeoService;
use App\Services\SettingsService;

final class CheckoutController extends Controller
{
    public function index(): string
    {
        $totals = CartService::totals();

        if ($totals['items'] === []) {
            $this->flashError('Your cart is empty.');
            $this->redirect(url('/shop'));
        }

        if (!AuthService::check() && !SettingsService::bool('allow_guest_checkout', false)) {
            \App\Core\Session::put('intended_url', '/checkout');
            \App\Core\Session::flash('info', 'Please sign in or create an account so we can keep your bookings together.');
            $this->redirect(url('/login'));
        }

        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.checkout', [
            'title'       => 'Checkout',
            'description' => 'Complete your SARCNA 2027 Convention booking.',
        ], [
            'totals'      => $totals,
            'user'        => AuthService::user(),
            'holdExpires' => AccommodationService::earliestHoldExpiry(CartService::token()),
            'termsNote'   => (string) SettingsService::get('order_terms_note', ''),
        ]);
    }

    public function place(): never
    {
        $totals = CartService::totals();

        if ($totals['items'] === []) {
            $this->flashError('Your cart is empty.');
            $this->redirect(url('/shop'));
        }

        $validator = Validator::make($this->request->all(), [
            'first_name' => 'required|max:80',
            'last_name'  => 'required|max:80',
            'email'      => 'required|email|max:190',
            'phone'      => 'required|phone',
            'terms'      => 'required|accepted',
        ], [
            'terms' => 'the terms, refund and privacy policies',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        // Per-line attendee / guest / passenger details captured on the page.
        $itemDetails = [];

        foreach ($this->request->array('items') as $itemId => $fields) {
            if (!is_array($fields)) {
                continue;
            }

            $itemDetails[(int) $itemId] = array_map(
                static fn ($value): string => is_string($value) ? trim($value) : '',
                array_intersect_key($fields, array_flip([
                    'attendee_name', 'attendee_email', 'dietary_notes',
                    'guest_name', 'guest_email', 'guest_phone', 'roommate_request',
                    'accessibility_needs', 'notes',
                    'passenger_name', 'phone', 'email', 'flight_number',
                ]))
            );
        }

        $customer = [
            'first_name' => (string) $this->request->input('first_name'),
            'last_name'  => (string) $this->request->input('last_name'),
            'email'      => (string) $this->request->input('email'),
            'phone'      => (string) $this->request->input('phone'),
            'note'       => (string) $this->request->input('customer_note', ''),
        ];

        $order = OrderService::createFromCart($customer, $totals, $itemDetails);

        // Transport seats are counted now so a shuttle cannot be oversold while
        // the customer is on the PayFast page.
        $problems = OrderService::reserveTransportSeats($order);

        if ($problems !== []) {
            OrderService::markCancelled($order, 'Seats were no longer available at checkout.');

            $this->flashError('These shuttle departures filled up while you were checking out: ' . implode('; ', $problems) . '. Please choose another departure.');
            $this->redirect(url('/cart'));
        }

        MailService::orderCreated($order);

        $this->redirect(url('/checkout/pay/' . $order['reference']));
    }

    /** The PayFast handoff page. It posts itself, with a manual button as a fallback. */
    public function pay(string $reference): string
    {
        $order = OrderService::findByReference($reference);

        if ($order === null) {
            $this->abort(404);
        }

        if (AuthService::check() && $order['user_id'] !== null && (int) $order['user_id'] !== AuthService::id()) {
            $this->abort(403, 'That order belongs to a different account.');
        }

        if ($order['status'] === 'paid') {
            $this->redirect(url('/payment/success?reference=' . $order['reference']));
        }

        if (!PayFastService::isConfigured()) {
            $this->flashError('Online payment is not configured yet. Please contact the committee to complete your booking.');
            $this->redirect(url('/cart'));
        }

        PayFastService::recordRedirect($order);

        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.checkout-pay', [
            'title'       => 'Redirecting to PayFast',
            'description' => 'Completing your SARCNA 2027 Convention payment.',
        ], [
            'order'      => $order,
            'items'      => OrderService::items((int) $order['id']),
            'fields'     => PayFastService::fieldsForOrder($order),
            'processUrl' => PayFastService::processUrl(),
            'sandbox'    => PayFastService::isSandbox(),
        ]);
    }
}
