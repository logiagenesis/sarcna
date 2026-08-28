<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\SeoService;

final class VenueController extends Controller
{
    public function index(): string
    {
        SeoService::breadcrumbs(['The Venue' => '/venue']);

        return $this->page('pages.venue', [
            'title'       => 'The Venue — Boschendal Retreat Cottages & Conference Venue',
            'description' => 'Boschendal Retreat Cottages & Conference Venue in the Cape Winelands: cottages, conference spaces, gardens, walks, arrival and parking, accessibility and transport for the SARCNA 2027 Convention.',
            'image'       => '/assets/img/venue/boschendal-overview.jpg',
        ], [
            'gallery'   => Database::select('SELECT * FROM gallery_images WHERE is_active = 1 AND category IN ("venue","conference") ORDER BY sort_order LIMIT 12'),
            'roomTypes' => AccommodationService::roomTypes(),
        ]);
    }

    public function history(): string
    {
        SeoService::breadcrumbs(['The Venue' => '/venue', 'Venue history' => '/venue/history']);

        return $this->page('pages.venue-history', [
            'title'       => 'Venue History & the Cape Winelands',
            'description' => 'The story of the Boschendal estate and the Cape Winelands valley that surrounds it — the setting for the SARCNA 2027 Convention.',
            'image'       => '/assets/img/venue/arrival-drive.jpg',
        ], []);
    }
}
