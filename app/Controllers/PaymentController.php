<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\AccommodationService;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PayFastService;
use App\Services\SeoService;
use App\Services\TransportService;

final class PaymentController extends Controller
{
    /**
     * Where PayFast sends the customer back to. This page NEVER marks an order
     * as paid — it only reports what the ITN has already told us.
     */
    public function success(): string
    {
        $reference = (string) $this->request->input('reference', $this->request->input('m_payment_id', ''));
        $order     = $reference === '' ? null : OrderService::findByReference($reference);

        if ($order === null) {
            // PayFast does not always echo the reference on the return URL.
            $token = CartService::token();
            $order = \App\Core\Database::first(
                'SELECT * FROM orders WHERE cart_token = ? ORDER BY created_at DESC LIMIT 1',
                [$token]
            );
        }

        if ($order !== null && $order['status'] === 'paid') {
            CartService::reset();
        }

        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.payment-success', [
            'title'       => 'Thank you',
            'description' => 'Your SARCNA 2027 Convention booking.',
        ], [
            'order'             => $order,
            'items'             => $order === null ? [] : OrderService::items((int) $order['id']),
            'bookings'          => $order === null ? [] : AccommodationService::bookingsForOrder((int) $order['id']),
            'transportBookings' => $order === null ? [] : TransportService::bookingsForOrder((int) $order['id']),
        ]);
    }

    public function cancelled(): string
    {
        $reference = (string) $this->request->input('reference', '');
        $order     = $reference === '' ? null : OrderService::findByReference($reference);

        // Only the buyer may cancel, and only their own order. An order
        // reference is not proof of ownership: it travels in confirmation
        // email, in the address bar and through PayFast, so without this check
        // any GET request carrying a reference — an <img> tag on another site
        // would do — cancelled a stranger's order and handed back the beds and
        // shuttle seats they were in the middle of paying for.
        if ($order !== null && $order['status'] === 'pending_payment' && OrderService::belongsToCurrentVisitor($order)) {
            OrderService::markCancelled($order);
        }

        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.payment-cancelled', [
            'title'       => 'Payment cancelled',
            'description' => 'Your SARCNA 2027 Convention payment was cancelled.',
        ], ['order' => $order]);
    }

    /**
     * PayFast Instant Transaction Notification. This is the ONLY place an order
     * can become paid. Always answers 200 so PayFast stops retrying.
     */
    public function notify(): never
    {
        $data = $_POST;

        if ($data === []) {
            parse_str($this->request->rawBody(), $data);
        }

        try {
            PayFastService::handleNotification($data, $this->request->ip());
        } catch (\Throwable $e) {
            PayFastService::logEvent(null, 'itn_exception', $e->getMessage(), $data);
        }

        Response::text('', 200);
    }

    /** A GET on the notify URL just confirms the endpoint is reachable. */
    public function notifyProbe(): never
    {
        Response::text("SARCNA 2027 PayFast notification endpoint is reachable.\nPayFast must POST to this URL.\n");
    }
}
