# SEO checklist

What is already built in, and what a person has to do after launch.

---

## Built in

### Per-page metadata
- [x] Unique `<title>` on every page, with the site name appended.
- [x] Unique meta description on every page, editable for products, room types
      and policy pages in the admin.
- [x] `rel="canonical"` on every page, derived from the configured site address.
- [x] `robots` meta per page — `noindex` on cart, checkout, payment, account and
      admin pages.

### Social sharing
- [x] Open Graph title, description, URL, image, type, locale and site name.
- [x] Twitter/X `summary_large_image` card.
- [x] A 1200×630 share card generated for the brand, used as the default image.
- [x] Product and room pages use their own image when they have one.

### Structured data (JSON-LD)
- [x] `Event` on every page — dates, venue, address, organiser, offers.
- [x] `Organization` on every page.
- [x] `BreadcrumbList` on inner pages.
- [x] `Product` with price, currency and availability on product pages.
- [x] `FAQPage` on the home page and the FAQ page.

### Crawling
- [x] `sitemap.xml` generated from the database, with `lastmod`, `changefreq`
      and `priority`. New room types, products, routes and pages appear
      automatically.
- [x] `robots.txt` generated, allowing public pages and disallowing account,
      cart, checkout, payment, admin and installer paths.
- [x] Clean URLs with no query strings on public pages.
- [x] Trailing slashes stripped with a 301, so each page has one address.

### Technical
- [x] Server-rendered HTML — the content is in the source, not assembled by
      JavaScript.
- [x] One heading hierarchy per page, one `<h1>`.
- [x] Alt text on every image; the admin refuses a gallery image without it.
- [x] `<picture>` with WebP and a JPEG fallback.
- [x] Lazy loading below the fold, eager loading for the hero.
- [x] Self-hosted fonts with `font-display: swap` and preloading of the two
      critical faces.
- [x] Compression and long cache headers in `.htaccess`, with asset URLs
      versioned by file modification time.
- [x] HTTPS forced, HSTS sent when the connection is secure.
- [x] Mobile-first layout, no horizontal scrolling.
- [x] `lang="en-ZA"`, currency ZAR, South African address in structured data.
- [x] A real 404 page that offers useful destinations.

---

## To do at launch

- [ ] Set the GA4 measurement ID in **Admin → Settings → Analytics**.
- [ ] Set the Search Console verification code and verify the property.
- [ ] Submit `sitemap.xml` in Search Console.
- [ ] Confirm the site address in `.env` is the canonical one, `https` and
      without a trailing slash — canonicals and `og:url` are built from it.
- [ ] Decide between `sarcna.org.za` and `www.sarcna.org.za` and redirect one to
      the other. There is a commented-out rule in `public_html/.htaccess`.
- [ ] Replace placeholder imagery, so the share card and product images show the
      real thing.
- [ ] Review each page's meta description for products and room types.
- [ ] Run Lighthouse on mobile and desktop and record the scores.
- [ ] Test one share into WhatsApp and one into Facebook and check the preview.
- [ ] Validate one page each with Google's Rich Results Test:
      home (Event, FAQ), a product page, a room page.

## To check a fortnight after launch

- [ ] Search Console → **Pages**: indexed count rising.
- [ ] Search Console → **Enhancements**: Event, FAQ and Breadcrumb items valid.
- [ ] Search Console → **Core Web Vitals**: green.
- [ ] `site:sarcna.org.za` in Google: titles and descriptions look right and
      differ page to page.
- [ ] GA4 shows `purchase` events matching the admin's paid orders.

---

## Target keywords

Primary: *SARCNA 2027 convention*, *NA convention South Africa 2027*, *recovery
convention Cape Town 2027*, *Cape Winelands recovery convention*.

Long-tail already served by real pages: *NA convention accommodation Cape
Winelands*, *convention shuttle from Cape Town airport*, *book a single bed
recovery convention*, *SARCNA registration 2027*.

The accommodation, transport and FAQ pages answer real questions in full
sentences, which is what earns these.

---

## Performance notes

Lighthouse targets: **mobile 90+, desktop 95+**.

The build helps by keeping one CSS file (~44 KB uncompressed, far less over the
wire), ~14 KB of JavaScript, no framework, WebP images that are mostly 12–32 KB,
and server-rendered HTML that needs no hydration.

Two things to watch after real photography lands:
1. Hero images. Keep them under about 250 KB and keep the mobile crop.
2. Total page weight on the accommodation index, which shows several cards.

If a score drops, look at image sizes first. It is almost always images.
