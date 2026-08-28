<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\AccommodationService;
use App\Services\CartService;
use App\Services\SeoService;

final class CartController extends Controller
{
    public function index(): string
    {
        $totals = CartService::totals();

        SeoService::set(['robots' => 'noindex,follow']);

        return $this->page('pages.cart', [
            'title'       => 'Your Cart',
            'description' => 'Review your SARCNA 2027 Convention registration, accommodation, transport and merchandise before checkout.',
        ], [
            'totals'      => $totals,
            'holdExpires' => AccommodationService::earliestHoldExpiry(CartService::token()),
            'holdMinutes' => AccommodationService::holdMinutes(),
        ]);
    }

    public function update(): never
    {
        $quantities = $this->request->array('quantities');

        foreach ($quantities as $itemId => $quantity) {
            CartService::updateQuantity((int) $itemId, (int) $quantity);
        }

        $this->flashSuccess('Your cart has been updated.');
        $this->redirect(url('/cart'));
    }

    public function remove(): never
    {
        CartService::remove($this->request->int('item_id'));

        $this->flashSuccess('Item removed from your cart.');
        $this->redirect(url('/cart'));
    }

    public function clear(): never
    {
        CartService::clear();

        $this->flashSuccess('Your cart is empty again, and any held beds have been released.');
        $this->redirect(url('/cart'));
    }

    public function coupon(): never
    {
        $result = CartService::applyCoupon((string) $this->request->input('code', ''));

        if ($result['ok']) {
            $this->flashSuccess($result['message']);
        } else {
            $this->flashError($result['message']);
        }

        $this->redirect(url('/cart'));
    }

    public function removeCoupon(): never
    {
        CartService::removeCoupon();
        $this->flashSuccess('Coupon removed.');
        $this->redirect(url('/cart'));
    }

    /** Small JSON endpoint so the header badge stays right after a back-button. */
    public function status(): never
    {
        Response::json([
            'count'       => CartService::count(),
            'total'       => money(CartService::totals()['total_cents']),
            'holdExpires' => AccommodationService::earliestHoldExpiry(CartService::token()),
        ]);
    }
}
