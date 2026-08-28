<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\SeoService;
use App\Services\SettingsService;

final class ShopController extends Controller
{
    public function index(): string
    {
        SeoService::breadcrumbs(['Shop' => '/shop']);

        return $this->page('pages.shop-index', [
            'title'       => 'Convention Shop',
            'description' => 'Registration, day passes, merchandise, transport and donations for the SARCNA 2027 Convention — all in one cart, paid once through PayFast.',
        ], [
            'registration' => ProductService::all(['type' => ['registration', 'day_pass']]),
            'merch'        => ProductService::all(['type' => ['merchandise']]),
            'donations'    => ProductService::all(['type' => ['donation']]),
            'categories'   => ProductService::categories(),
        ]);
    }

    public function registration(): string
    {
        SeoService::breadcrumbs(['Shop' => '/shop', 'Registration' => '/shop/registration']);

        return $this->page('pages.shop-registration', [
            'title'       => 'Convention Registration',
            'description' => 'Register for the SARCNA 2027 Convention: full weekend early bird and standard registration, plus Friday, Saturday and Sunday day passes.',
            'image'       => '/assets/img/backgrounds/cta-sunset.jpg',
        ], [
            'weekend' => ProductService::all(['type' => ['registration']]),
            'days'    => ProductService::all(['type' => ['day_pass']]),
            'isOpen'  => SettingsService::bool('registration_open', true),
        ]);
    }

    public function merchandise(): string
    {
        SeoService::breadcrumbs(['Shop' => '/shop', 'Merchandise' => '/shop/merchandise']);

        return $this->page('pages.shop-merchandise', [
            'title'       => 'Convention Merchandise',
            'description' => 'Official SARCNA 2027 Convention merchandise — t-shirts, hoodies, caps, totes, mugs, stickers and lanyards, collected at the registration desk.',
            'image'       => '/assets/img/merch/t-shirt.jpg',
        ], [
            'products'   => ProductService::all(['type' => ['merchandise']]),
            'pickupNote' => (string) SettingsService::get('merch_pickup_note', ''),
        ]);
    }

    public function show(string $slug): string
    {
        $product = ProductService::find($slug);

        if ($product === null || (int) $product['is_active'] !== 1) {
            $this->abort(404);
        }

        $images = ProductService::images((int) $product['id']);

        SeoService::breadcrumbs(['Shop' => '/shop', $product['name'] => '/shop/' . $product['slug']]);
        SeoService::addSchema(SeoService::productSchema($product, $product['image']));

        return $this->page('pages.shop-show', [
            'title'       => $product['meta_title'] ?: $product['name'],
            'description' => $product['meta_description'] ?: excerpt($product['short_description'] ?: $product['description'], 155),
            'image'       => $product['image'],
            'og_type'     => 'product',
        ], [
            'product'  => $product,
            'variants' => ProductService::variants((int) $product['id']),
            'images'   => $images,
            'related'  => array_slice(array_filter(
                ProductService::all(['type' => [$product['type']]]),
                static fn (array $item): bool => (int) $item['id'] !== (int) $product['id']
            ), 0, 3),
        ]);
    }

    public function add(string $slug): never
    {
        $product = ProductService::find($slug);

        if ($product === null || (int) $product['is_active'] !== 1) {
            $this->abort(404);
        }

        if (!SettingsService::bool('shop_enabled', true)) {
            $this->flashError('The shop is closed at the moment.');
            $this->back(url('/shop'));
        }

        $quantity  = max(1, min((int) $product['max_per_order'], $this->request->int('quantity', 1)));
        $variantId = $this->request->int('variant_id', 0) ?: null;
        $variant   = null;

        if ($variantId !== null) {
            foreach (ProductService::variants((int) $product['id']) as $candidate) {
                if ((int) $candidate['id'] === $variantId) {
                    $variant = $candidate;
                    break;
                }
            }

            if ($variant === null) {
                $this->flashError('Please choose an available size and colour.');
                $this->back(url('/shop/' . $product['slug']));
            }
        } elseif (ProductService::variants((int) $product['id']) !== []) {
            $this->flashError('Please choose a size and colour.');
            $this->back(url('/shop/' . $product['slug']));
        }

        if (!ProductService::inStock($product, $variant)) {
            $this->flashError('That option has just sold out.');
            $this->back(url('/shop/' . $product['slug']));
        }

        $available = ProductService::stockFor($product, $variant);

        if ($available < $quantity) {
            $this->flashError(sprintf('Only %d left of that option.', $available));
            $this->back(url('/shop/' . $product['slug']));
        }

        $price = ProductService::priceFor($product, $variant);

        // Donations and other open-amount products take the amount typed in.
        if ((int) $product['allows_custom_amount'] === 1) {
            $price = rands($this->request->input('amount', 0));

            if ($price < (int) $product['min_amount_cents']) {
                $this->flashError('Please enter an amount of at least ' . money((int) $product['min_amount_cents']) . '.');
                $this->back(url('/shop/' . $product['slug']));
            }

            $quantity = 1;
        }

        $description = $product['name'];

        if ($variant !== null) {
            $descriptor = trim(implode(' / ', array_filter([$variant['size'], $variant['colour']])));

            if ($descriptor !== '') {
                $description .= ' (' . $descriptor . ')';
            }
        }

        $itemType = match ($product['type']) {
            'registration', 'day_pass' => 'registration',
            'donation'                 => 'donation',
            'transport'                => 'transport',
            default                    => 'merchandise',
        };

        $meta = [
            'product_type' => $product['type'],
            'sku'          => $variant['sku'] ?? $product['sku'],
            'image'        => $product['image'],
        ];

        if ($itemType === 'registration') {
            $meta['attendee_name']  = (string) $this->request->input('attendee_name', '');
            $meta['attendee_email'] = (string) $this->request->input('attendee_email', '');
            $meta['dietary_notes']  = (string) $this->request->input('dietary_notes', '');
        }

        if ($itemType === 'donation') {
            $meta['donation_type'] = $product['name'];
            $meta['is_anonymous']  = $this->request->bool('is_anonymous') ? 1 : 0;
            $meta['message']       = (string) $this->request->input('message', '');
        }

        CartService::add([
            'item_type'        => $itemType,
            'product_id'       => (int) $product['id'],
            'variant_id'       => $variant === null ? null : (int) $variant['id'],
            'description'      => $description,
            'unit_price_cents' => $price,
            'quantity'         => $quantity,
            'meta'             => $meta,
        ]);

        $this->flashSuccess($description . ' added to your cart.');
        $this->redirect(url('/cart'));
    }
}
