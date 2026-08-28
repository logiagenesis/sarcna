<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\ProductService;
use App\Services\SeoService;
use App\Services\TransportService;

final class HomeController extends Controller
{
    public function index(): string
    {
        $hero = Database::first(
            'SELECT * FROM banners
              WHERE position = "home_hero" AND is_active = 1
                AND (starts_at IS NULL OR starts_at <= NOW())
                AND (ends_at IS NULL OR ends_at >= NOW())
           ORDER BY sort_order, id LIMIT 1'
        );

        $faqs = Database::select('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id LIMIT 6');

        SeoService::addSchema(SeoService::faqSchema($faqs));

        return $this->page('pages.home', [
            'title'       => 'SARCNA 2027 Convention',
            'description' => 'Rooted in Recovery. Rising Together. Join us from 27 to 29 August 2027 at Boschendal Retreat Cottages & Conference Venue in the Cape Winelands — registration, accommodation, transport and merchandise, all in one place.',
            'canonical'   => '/',
        ], [
            'hero'         => $hero,
            'cta'          => Database::first('SELECT * FROM banners WHERE position = "home_cta" AND is_active = 1 ORDER BY sort_order, id LIMIT 1'),
            'roomTypes'    => array_slice(AccommodationService::roomTypes(), 0, 3),
            'occupancy'    => AccommodationService::occupancySummary(),
            'nightLabels'  => AccommodationService::nightLabels(),
            'registration' => ProductService::all(['type' => ['registration', 'day_pass'], 'limit' => 3]),
            'merch'        => ProductService::all(['type' => ['merchandise'], 'limit' => 4]),
            'routes'       => array_slice(TransportService::routes(), 0, 3),
            'programme'    => Database::select('SELECT * FROM programme_items WHERE is_active = 1 AND is_highlight = 1 ORDER BY day_date, start_time LIMIT 5'),
            'gallery'      => Database::select('SELECT * FROM gallery_images WHERE is_active = 1 AND category = "venue" ORDER BY sort_order LIMIT 6'),
            'faqs'         => $faqs,
            'events'       => Database::select('SELECT * FROM events WHERE is_active = 1 AND starts_at >= NOW() ORDER BY starts_at LIMIT 3'),
        ]);
    }
}
