<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\SeoService;

final class PageController extends Controller
{
    public function about(): string
    {
        SeoService::breadcrumbs(['About' => '/about']);

        return $this->page('pages.about', [
            'title'       => 'About SARCNA 2027',
            'description' => 'Who we are, what the convention is for, and what the theme "Rooted in Recovery. Rising Together." means for the 2027 weekend in the Cape Winelands.',
        ], [
            'faqs' => Database::select('SELECT * FROM faqs WHERE is_active = 1 AND category = "Registration" ORDER BY sort_order LIMIT 4'),
        ]);
    }

    public function convention(): string
    {
        SeoService::breadcrumbs(['The Convention' => '/convention']);

        return $this->page('pages.convention', [
            'title'       => 'Convention Overview',
            'description' => 'Everything the SARCNA 2027 Convention weekend includes: meetings, workshops, speakers, service, accommodation, transport and the Saturday night celebration.',
        ], [
            'programme' => Database::select('SELECT * FROM programme_items WHERE is_active = 1 ORDER BY day_date, start_time'),
            'products'  => Database::select('SELECT * FROM products WHERE is_active = 1 AND type IN ("registration","day_pass") ORDER BY sort_order'),
        ]);
    }

    public function faq(): string
    {
        $faqs = Database::select('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id');

        $grouped = [];
        foreach ($faqs as $faq) {
            $grouped[$faq['category']][] = $faq;
        }

        SeoService::breadcrumbs(['FAQ' => '/faq']);
        SeoService::addSchema(SeoService::faqSchema($faqs));

        return $this->page('pages.faq', [
            'title'       => 'Frequently Asked Questions',
            'description' => 'Answers about registration, booking a single bed, transport, payments, the venue and anonymity at the SARCNA 2027 Convention.',
        ], ['grouped' => $grouped]);
    }

    /** Every legal / policy page is served from the pages table so admins can edit it. */
    public function legal(): string
    {
        $slug = trim($this->request->path(), '/');
        $page = Database::first('SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1', [$slug]);

        if ($page === null) {
            $this->abort(404);
        }

        SeoService::breadcrumbs([$page['title'] => '/' . $slug]);

        return $this->page('pages.legal', [
            'title'       => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?: excerpt($page['body_html'], 155),
        ], [
            'page'  => $page,
            'legal' => Database::select('SELECT slug, title FROM pages WHERE is_legal = 1 AND is_published = 1 ORDER BY title'),
        ]);
    }
}
