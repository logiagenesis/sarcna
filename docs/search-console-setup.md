# Google Search Console setup

## Verifying the site

1. Go to [Search Console](https://search.google.com/search-console) and add a
   property. Choose **URL prefix** and enter `https://sarcna.org.za`.
2. Choose the **HTML tag** method. Google shows a tag like:

```html
<meta name="google-site-verification" content="AbC123..." />
```

3. Copy only the `content` value.
4. Paste it into
   **Admin → Settings → Analytics → Search Console verification code**.
5. Return to Search Console and press **Verify**.

The tag is then rendered in the `<head>` of every page.

---

## Submitting the sitemap

Search Console → **Sitemaps** → enter `sitemap.xml` → **Submit**.

The sitemap at `https://sarcna.org.za/sitemap.xml` is generated from the
database on every request, so new room types, products, routes and pages appear
automatically. It includes a `lastmod` per URL.

`robots.txt` is generated too. It allows everything public and disallows
`/admin`, `/account`, `/cart`, `/checkout`, `/payment/`, `/install`, `/login`,
`/reset-password` and `/verify-email`.

---

## What to check in the first fortnight

| Where | What you are looking for |
|---|---|
| **Pages** | Indexed count climbing. Anything under *Not indexed* worth investigating. |
| **Enhancements → Events** | The Event structured data on every page. |
| **Enhancements → FAQ** | The FAQ structured data on the home and FAQ pages. |
| **Enhancements → Breadcrumbs** | Breadcrumbs on inner pages. |
| **Core Web Vitals** | Should be green — the site is server-rendered with local assets. |
| **Mobile usability** | Should be clean; the layout is mobile-first. |

---

## Migrating from the old site

The previous site was a single-page React app: every page shared one title and
one description, and `og:url` pointed at `sarcna.org` — a domain the committee
does not own. Google will have indexed very little of it usefully.

When the new site replaces it:

1. Keep the same domain if possible. No redirects are then needed.
2. If any old URLs change, add 301 redirects in `public_html/.htaccess`.
3. Use **Removals** in Search Console only for pages that must disappear
   quickly; ordinary pages will re-crawl on their own.
4. Ask Search Console to re-crawl the home page once the new site is live.

The old `og:url` error is worth fixing before launch precisely because every
WhatsApp and Facebook share currently points at the wrong domain.
