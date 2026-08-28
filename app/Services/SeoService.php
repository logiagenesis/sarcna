<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Per-page SEO metadata and JSON-LD. Every page sets its own title, description
 * and canonical — the old single-page app could not, which is why its Google
 * and WhatsApp previews were identical everywhere.
 */
final class SeoService
{
    private static array $meta = [];
    private static array $schemas = [];
    private static array $breadcrumbs = [];

    public static function set(array $meta): void
    {
        self::$meta = array_merge(self::$meta, $meta);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$meta[$key] ?? $default;
    }

    public static function title(): string
    {
        $title    = (string) (self::$meta['title'] ?? Config::get('event.title'));
        $siteName = (string) (SettingsService::get('site_name', Config::get('app.name')));

        return $title === $siteName ? $title : $title . ' | ' . $siteName;
    }

    public static function description(): string
    {
        return (string) (self::$meta['description'] ?? SettingsService::get(
            'default_meta_description',
            'SARCNA 2027 Convention — Rooted in Recovery. Rising Together. 27–29 August 2027 at Boschendal Retreat Cottages & Conference Venue in the Cape Winelands.'
        ));
    }

    public static function canonical(): string
    {
        if (isset(self::$meta['canonical'])) {
            return url((string) self::$meta['canonical']);
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        return url(rtrim($path, '/') ?: '/');
    }

    public static function image(): string
    {
        $image = self::$meta['image'] ?? null;

        return $image !== null ? uploaded($image) : asset('brand/social-share-card.jpg');
    }

    public static function robots(): string
    {
        return (string) (self::$meta['robots'] ?? 'index,follow,max-image-preview:large');
    }

    public static function type(): string
    {
        return (string) (self::$meta['og_type'] ?? 'website');
    }

    public static function breadcrumbs(array $crumbs): void
    {
        self::$breadcrumbs = $crumbs;
    }

    public static function crumbs(): array
    {
        return self::$breadcrumbs;
    }

    public static function addSchema(array $schema): void
    {
        self::$schemas[] = $schema;
    }

    /** All JSON-LD blocks for the current page, including the always-on Event graph. */
    public static function schemaBlocks(): array
    {
        $blocks = [self::eventSchema(), self::organisationSchema()];

        if (self::$breadcrumbs !== []) {
            $blocks[] = self::breadcrumbSchema();
        }

        return array_merge($blocks, self::$schemas);
    }

    public static function eventSchema(): array
    {
        $event = Config::get('event');

        return [
            '@context'            => 'https://schema.org',
            '@type'               => 'Event',
            'name'                => $event['title'],
            'description'         => self::description(),
            'startDate'           => date('c', (int) strtotime((string) $event['starts_at'])),
            'endDate'             => date('c', (int) strtotime((string) $event['ends_at'])),
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'image'               => [self::image()],
            'url'                 => url('/'),
            'location'            => [
                '@type'   => 'Place',
                'name'    => $event['venue_name'],
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => 'Franschhoek',
                    'addressRegion'   => 'Western Cape',
                    'addressCountry'  => 'ZA',
                ],
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name'  => 'SARCNA 2027 Convention Committee',
                'url'   => url('/'),
            ],
            'offers' => [
                '@type'         => 'Offer',
                'url'           => url('/shop/registration'),
                'priceCurrency' => 'ZAR',
                'availability'  => 'https://schema.org/InStock',
                'validFrom'     => date('c'),
            ],
        ];
    }

    private static function organisationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => (string) SettingsService::get('site_name', Config::get('app.name')),
            'url'      => url('/'),
            'logo'     => asset('brand/logo.svg'),
            'email'    => (string) SettingsService::get('contact_email', Config::get('contact.email')),
            'areaServed' => 'ZA',
        ];
    }

    private static function breadcrumbSchema(): array
    {
        $items = [];
        $position = 1;

        foreach (self::$breadcrumbs as $label => $path) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $label,
                'item'     => url((string) $path),
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    public static function productSchema(array $product, ?string $image = null): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product['name'],
            'description' => excerpt($product['short_description'] ?? $product['description'] ?? '', 200),
            'image'       => [$image !== null ? uploaded($image) : self::image()],
            'sku'         => $product['sku'] ?? ('SARCNA-' . $product['id']),
            'brand'       => ['@type' => 'Brand', 'name' => 'SARCNA 2027'],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => url('/shop/' . $product['slug']),
                'priceCurrency' => 'ZAR',
                'price'         => number_format(((int) ($product['sale_price_cents'] ?: $product['price_cents'])) / 100, 2, '.', ''),
                'availability'  => ((int) $product['track_stock'] === 0 || (int) $product['stock'] > 0)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    }

    public static function faqSchema(array $faqs): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type'          => 'Question',
                'name'           => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags((string) $faq['answer']),
                ],
            ], $faqs),
        ];
    }

    public static function reset(): void
    {
        self::$meta        = [];
        self::$schemas     = [];
        self::$breadcrumbs = [];
    }
}
