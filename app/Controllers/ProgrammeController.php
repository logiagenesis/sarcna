<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\SeoService;

final class ProgrammeController extends Controller
{
    public function index(): string
    {
        $items = Database::select('SELECT * FROM programme_items WHERE is_active = 1 ORDER BY day_date, start_time, sort_order');

        $days = [];
        foreach ($items as $item) {
            $days[$item['day_date']][] = $item;
        }

        SeoService::breadcrumbs(['Programme' => '/programme']);

        return $this->page('pages.programme', [
            'title'       => 'Weekend Programme',
            'description' => 'The full hour-by-hour programme for the SARCNA 2027 Convention, Friday 27 to Sunday 29 August 2027 at Boschendal in the Cape Winelands.',
        ], ['days' => $days]);
    }
}
