<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\SeoService;

final class GalleryController extends Controller
{
    public function index(): string
    {
        $category = (string) $this->request->input('category', '');

        $sql    = 'SELECT * FROM gallery_images WHERE is_active = 1';
        $params = [];

        if ($category !== '') {
            $sql     .= ' AND category = ?';
            $params[] = $category;
        }

        SeoService::breadcrumbs(['Gallery' => '/gallery']);

        return $this->page('pages.gallery', [
            'title'       => 'Venue Gallery',
            'description' => 'Images of the venue and conference spaces for the SARCNA 2027 Convention at Boschendal in the Cape Winelands.',
        ], [
            'images'     => Database::select($sql . ' ORDER BY sort_order, id', $params),
            'categories' => array_column(Database::select('SELECT DISTINCT category FROM gallery_images WHERE is_active = 1 ORDER BY category'), 'category'),
            'active'     => $category,
        ]);
    }
}
