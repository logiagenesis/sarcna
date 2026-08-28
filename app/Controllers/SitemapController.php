<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;

/**
 * Sitemap and robots.txt are generated from the live database, so a new room
 * type or product is discoverable without anyone editing a static file.
 */
final class SitemapController extends Controller
{
    public function index(): never
    {
        $urls = [];

        $static = [
            '/'                        => ['1.0', 'weekly'],
            '/about'                   => ['0.7', 'monthly'],
            '/convention'              => ['0.9', 'weekly'],
            '/programme'               => ['0.8', 'weekly'],
            '/venue'                   => ['0.8', 'monthly'],
            '/venue/history'           => ['0.5', 'yearly'],
            '/accommodation'           => ['0.9', 'daily'],
            '/shop'                    => ['0.9', 'daily'],
            '/shop/registration'       => ['1.0', 'daily'],
            '/shop/merchandise'        => ['0.8', 'weekly'],
            '/transport'               => ['0.8', 'weekly'],
            '/donations'               => ['0.6', 'monthly'],
            '/service'                 => ['0.6', 'monthly'],
            '/gallery'                 => ['0.5', 'monthly'],
            '/faq'                     => ['0.7', 'monthly'],
            '/contact'                 => ['0.6', 'yearly'],
            '/register'                => ['0.4', 'yearly'],
            '/login'                   => ['0.3', 'yearly'],
        ];

        foreach ($static as $path => [$priority, $frequency]) {
            $urls[] = ['loc' => url($path), 'priority' => $priority, 'changefreq' => $frequency, 'lastmod' => date('Y-m-d')];
        }

        foreach (Database::select('SELECT slug, updated_at FROM room_types WHERE is_active = 1') as $room) {
            $urls[] = ['loc' => url('/accommodation/' . $room['slug']), 'priority' => '0.8', 'changefreq' => 'daily', 'lastmod' => za_date($room['updated_at'], 'Y-m-d')];
        }

        foreach (Database::select('SELECT slug, updated_at FROM products WHERE is_active = 1') as $product) {
            $urls[] = ['loc' => url('/shop/' . $product['slug']), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => za_date($product['updated_at'], 'Y-m-d')];
        }

        foreach (Database::select('SELECT slug FROM transport_routes WHERE is_active = 1') as $route) {
            $urls[] = ['loc' => url('/transport/' . $route['slug']), 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')];
        }

        foreach (Database::select('SELECT slug, updated_at FROM pages WHERE is_published = 1') as $page) {
            $urls[] = ['loc' => url('/' . $page['slug']), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => za_date($page['updated_at'], 'Y-m-d')];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= sprintf(
                "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>\n",
                htmlspecialchars($entry['loc'], ENT_XML1),
                $entry['lastmod'] ?: date('Y-m-d'),
                $entry['changefreq'],
                $entry['priority']
            );
        }

        $xml .= '</urlset>';

        Response::text($xml, 200, 'application/xml; charset=utf-8');
    }

    public function robots(): never
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Nothing below is useful to a search engine.',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /payment/',
            'Disallow: /install',
            'Disallow: /login',
            'Disallow: /reset-password',
            'Disallow: /verify-email',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        Response::text(implode("\n", $lines) . "\n");
    }
}
