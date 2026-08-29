# SARCNA 2027 Convention

The website for the SARCNA 2027 Convention — **Rooted in Recovery. Rising Together.**
27–29 August 2027, Boschendal Retreat Cottages & Conference Venue, Cape Winelands.

Registration, bed-level accommodation booking, shuttle seats, merchandise and
donations, in one cart, with one PayFast payment — plus an admin dashboard the
committee can run without a developer.

---

## What this is built on

Deliberately plain, because it has to run on ordinary cPanel shared hosting for
years after whoever built it has moved on.

| Layer | Choice |
|---|---|
| Language | PHP 8.1+ (8.2 recommended), no Composer, no build step |
| Database | MySQL 5.7+ / MariaDB 10.3+ via PDO with prepared statements |
| Frontend | Server-rendered PHP templates, hand-written CSS, ~14 KB of vanilla JS |
| Fonts | Self-hosted Lora and Work Sans (SIL Open Font License) |
| Images | Local files only, JPEG + WebP, nothing hot-linked |
| Payments | PayFast (sandbox and live) |
| Email | cPanel mailbox SMTP, with `mail()` and a file queue as fallbacks |
| Hosting | Apache + cPanel. **No Node.js, Firebase, Vercel, MongoDB or Cloudinary.** |

There is nothing to compile and nothing to install. Upload the files, create a
database, and visit `/install`.

---

## Repository layout

```
/public_html          The only folder the web server exposes
  index.php           Front controller — every request enters here
  .htaccess           Rewrites, HTTPS, compression, caching, security headers
  /assets             css, js, fonts, brand, img (all local)
  /uploads            Admin image uploads (inert: PHP execution is denied)

/app                  Application code — must not be reachable over HTTP
  bootstrap.php       Boots config, autoloader, helpers
  /Core               Router, view engine, PDO wrapper, session, CSRF,
                      validator, SMTP mailer, logger, kernel
  /Controllers        Public controllers, plus /Admin for the dashboard
  /Services           Cart, accommodation, transport, orders, PayFast, mail,
                      settings, auth, audit, CSV, rate limiting
  /Middleware         auth, guest, admin (with capabilities), throttle, verified
  /Views              layouts, partials, pages, admin, emails
  /Config             config.php, routes.php, installed.lock (after install)
  /Helpers            functions.php — the helpers every view uses

/database             schema.sql, seed.sql, demo-data.sql, migrations.md
/storage              logs, cache, backups, email-queue (writable, not public)
/docs                 Handover, deployment, PayFast, SEO, testing, source logs
/tools                generate-images.php, dev-router.php, package.php
```

If the hosting account cannot place `/app` outside the document root, each
private folder carries a deny-all `.htaccess` as a second line of defence.

---

## Installing

1. In cPanel, create a MySQL database and a user, and give the user all
   privileges on that database.
2. Upload the whole repository so that the domain's document root points at
   `/public_html`.
3. Visit `https://yourdomain/install`.
4. Fill in the database, site, administrator, PayFast, email and analytics
   details.
5. The installer creates every table, seeds the demo content, generates the
   accommodation inventory, creates your admin account, writes `.env` and then
   locks itself.

Full step-by-step instructions, including the cPanel screens:
[`docs/cpanel-deployment-guide.md`](docs/cpanel-deployment-guide.md).

---

## Local development

```bash
php -S 127.0.0.1:8000 -t public_html tools/dev-router.php
```

Regenerate the placeholder imagery (JPEG + WebP, all local):

```bash
php tools/generate-images.php
```

Build a cPanel upload package:

```bash
php tools/package.php
```

Prove the critical paths still work after a change — bed inventory, holds,
fulfilment and PayFast verification:

```bash
php tools/smoke-test.php
```

It creates its own test data and cleans up after itself. Run it on a staging
copy, not on a live site with real bookings.

---

## How accommodation works

This is the part worth understanding before changing anything.

Inventory is tracked **per bed, per night** — never per room:

```
room_types  →  room_units  →  beds
                                └── bookings  UNIQUE (bed_id, active_night)
                                └── booking_holds  UNIQUE (bed_id, night)
```

* Booking one bed in a two-bed cottage leaves the second bed on sale.
* A "private unit" booking is simply a hold on every bed in that unit.
* Adding a bed to the cart creates a **hold** that expires after 15 minutes.
  The unique index — not application logic — is what stops two people buying
  the same bed at the same instant.
* `bookings.active_night` is a generated column that is `NULL` for cancelled and
  refunded rows, so a cancellation frees the bed automatically.

---

## How payment works

**Only a verified PayFast notification can mark an order as paid.** Landing on
the return URL proves nothing and never changes an order.

Every notification is checked four ways before fulfilment:

1. the signature matches, with the passphrase;
2. the request came from a PayFast server;
3. the amount matches the order to the cent;
4. PayFast itself confirms the payload when we post it back.

Only then does the site convert bed holds into bookings, write passenger
records, reduce stock, record donations and send the confirmations. Every
notification is written to `payment_logs`, accepted or rejected, and is visible
in **Admin → Payments → Notification log**.

---

## Security

Prepared statements everywhere · CSRF tokens on every state-changing form ·
server-side validation on every input · output escaping by default ·
HttpOnly + SameSite session cookies (never localStorage) · modern password
hashing · role-based admin capabilities · admin audit log · rate limiting on
login, password reset and public forms · honeypots on public forms · uploads
validated by their own bytes and served inert · no credentials in the
repository · error pages that leak nothing.

---

## Documentation

| Document | What it covers |
|---|---|
| **[`HANDOVER.md`](HANDOVER.md)** | **The handover document. Start here.** What you have, proof it works, how it works, the launch checklist |
| [`docs/cpanel-deployment-guide.md`](docs/cpanel-deployment-guide.md) | Uploading, installing, going live |
| [`docs/payfast-setup.md`](docs/payfast-setup.md) | Sandbox to live, the ITN URL, troubleshooting |
| [`docs/smtp-setup.md`](docs/smtp-setup.md) | cPanel mailbox email, SPF and DKIM |
| [`docs/analytics-setup.md`](docs/analytics-setup.md) | GA4 and the events the site fires |
| [`docs/search-console-setup.md`](docs/search-console-setup.md) | Verification and the sitemap |
| [`docs/admin-user-guide.md`](docs/admin-user-guide.md) | Written for committee members, not developers |
| [`docs/backup-restore-guide.md`](docs/backup-restore-guide.md) | Backups, restores, the 25 GB budget |
| [`docs/seo-checklist.md`](docs/seo-checklist.md) | What is done and what to check after launch |
| [`docs/testing-checklist.md`](docs/testing-checklist.md) | The pre-launch test run |
| [`docs/functionality-parity-checklist.md`](docs/functionality-parity-checklist.md) | Old site feature → new site feature |
| [`docs/current-site-functionality-map.md`](docs/current-site-functionality-map.md) | What the old stack was and what replaces it |
| [`docs/image-source-log.md`](docs/image-source-log.md) | Every image, where it came from, what must replace it |
| [`docs/venue-source-log.md`](docs/venue-source-log.md) | Venue claims that must be verified before launch |
| [`database/migrations.md`](database/migrations.md) | How to change the schema safely |

---

## Status

This is a **complete, working mockup for committee review**. The dates, venue,
pricing, room inventory, transport routes, programme and imagery are placeholder
content, clearly flagged in the admin and behind a preview banner on the site.
See **[HANDOVER.md](HANDOVER.md)** — the single handover document — for what has to be confirmed before it goes public.
