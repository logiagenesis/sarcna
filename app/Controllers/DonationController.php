<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\SeoService;
use App\Services\SettingsService;

final class DonationController extends Controller
{
    public function index(): string
    {
        SeoService::breadcrumbs(['Donations' => '/donations']);

        return $this->page('pages.donations', [
            'title'       => 'Donations',
            'description' => 'Support the SARCNA 2027 Convention with a Seventh Tradition contribution, by sponsoring a newcomer registration, or by sponsoring a bed for someone who could not otherwise stay.',
            'image'       => '/assets/img/venue/fellowship-lawn.jpg',
        ], [
            'products' => ProductService::all(['type' => ['donation']]),
            'isOpen'   => SettingsService::bool('donations_enabled', true),
            'raised'   => (int) Database::scalar('SELECT COALESCE(SUM(amount_cents), 0) FROM donations WHERE status = "paid"'),
            'count'    => (int) Database::scalar('SELECT COUNT(*) FROM donations WHERE status = "paid"'),
        ]);
    }

    public function add(): never
    {
        if (!SettingsService::bool('donations_enabled', true)) {
            $this->flashError('Donations are closed at the moment.');
            $this->back(url('/donations'));
        }

        $product = ProductService::find((int) $this->request->int('product_id'));

        if ($product === null || $product['type'] !== 'donation' || (int) $product['is_active'] !== 1) {
            $this->flashError('Please choose a donation type.');
            $this->back(url('/donations'));
        }

        $amount = (int) $product['allows_custom_amount'] === 1
            ? rands($this->request->input('amount', 0))
            : (int) $product['price_cents'];

        $minimum = max(2000, (int) $product['min_amount_cents']);

        if ($amount < $minimum) {
            $this->flashError('Please enter an amount of at least ' . money($minimum) . '.');
            $this->back(url('/donations'));
        }

        CartService::add([
            'item_type'        => 'donation',
            'product_id'       => (int) $product['id'],
            'description'      => $product['name'],
            'unit_price_cents' => $amount,
            'quantity'         => 1,
            'meta'             => [
                'donation_type' => $product['name'],
                'is_anonymous'  => $this->request->bool('is_anonymous') ? 1 : 0,
                'message'       => (string) $this->request->input('message', ''),
                'image'         => $product['image'],
            ],
        ]);

        $this->flashSuccess('Thank you. Your donation has been added to the cart.');
        $this->redirect(url('/cart'));
    }
}
