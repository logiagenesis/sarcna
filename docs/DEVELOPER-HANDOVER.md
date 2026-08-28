# Developer handover — SARCNA 2027 Convention site

This is the technical companion to this repository, written for the developer
who picks it up next year. It assumes you know PHP but have never seen this
codebase. The committee-facing summary is `docs/handover.md`; the
step-by-step deployment recipe is `docs/cpanel-deployment-guide.md`. This
document explains **how the thing actually works and why it is built the way
it is**, so you can change it without breaking the two promises the site
makes: nobody is ever double-booked, and nothing is ever marked paid unless
PayFast said so.

---

## 1. Orientation

**What this is.** The registration, accommodation, transport, shop and
donation site for the SARCNA 2027 Convention (27–29 August 2027, Boschendal,
Cape Winelands), with a full admin back-office for the organising committee.

**The stack, deliberately boring:** PHP 8.1+ with a small hand-written
framework (no Composer, no packages), MySQL/MariaDB via PDO prepared
statements, plain CSS and a little vanilla JavaScript. No build step, no
Node, no CDN at runtime. The whole site uploads to ordinary cPanel shared
hosting and runs. That constraint is from the brief and it is the most
important thing to preserve: **any change that adds a server dependency
beyond PHP + MySQL is a regression**, however nice the library.

**Run it locally:**

```bash
cp .env.example .env            # fill in DB credentials; sandbox PayFast keys are fine
php -S 127.0.0.1:8000 -t public_html tools/dev-router.php
# visit /install once to create the schema and seed content
php tools/smoke-test.php        # 38 checks; all must pass before you touch anything
php tools/seed-demo-orders.php  # optional: realistic orders so admin screens have data
```

`.env` is git-ignored and must stay that way. There are **no credentials
anywhere in this repository**; everything secret comes from `.env`, and
runtime-editable settings live in the `settings` table (Admin → Settings).

---

## 2. How a request flows

```
public_html/index.php
  → app/bootstrap.php        loads .env, config, helpers, autoloader (PSR-4, App\ → app/)
  → App\Core\Kernel::handle
      → Router::match        regex routes from app/Config/routes.php
      → middleware           'guest', 'auth', 'admin:<capability>', 'throttle:n,seconds'
      → Controller method    app/Controllers/…  (Admin\ subfolder for the back-office)
      → View::render         app/Views/…  plain PHP templates with layout/section helpers
```

Things worth knowing before you edit:

- **Routes are matched in registration order.** Literal paths must be
  registered before parameterised ones (`/bookings/operations` before
  `/bookings/{id}` — there's a comment at the spot in `routes.php`).
- **Escaping**: `e()` everywhere in templates. Grep for `?= e(` before
  copying a pattern; unescaped output is only ever used for HTML the admin
  wrote through the CMS, and it is marked as such.
- **Money is always integer cents.** `money(130500)` renders "R1 305.00"
  (non-breaking space separator); `rands('1305.00')` parses back to cents.
  Never use floats for money anywhere.
- **Flash + redirect helpers**: controllers use `flashSuccess()` /
  `flashError()` / `back()` / `redirect()`. (`success()` is *not* a base
  helper — that name belongs to `PaymentController::success()`, the thank-you
  page. This bit us once already.)

---

## 3. The bed-level booking engine (the heart of the site)

The brief's central requirement: booking one bed in a two-bed room must
leave the second bed on sale. The design makes double-booking **impossible
at the database level**, not merely unlikely at the application level.

```
room_types  (Retreat Cottage Twin Room, Accessible Room, Partner Guest House)
  └─ room_units  (Cottage 07 · Room A …)
       └─ beds   (Bed 1, Bed 2)
```

`bookings` carries a **stored generated column**:

```sql
active_night DATE GENERATED ALWAYS AS
  (CASE WHEN status IN ('confirmed','checked_in') THEN night ELSE NULL END) STORED,
UNIQUE KEY uq_bed_active_night (bed_id, active_night)
```

MySQL ignores NULLs in unique indexes, so:

- Two **live** bookings for the same bed on the same night are refused by
  the database itself, whatever the application does.
- **Cancelling or refunding** a booking sets its status, `active_night`
  becomes NULL, and the bed is back on sale automatically — no cleanup job.

Carts don't own beds; **holds** do: `booking_holds` has its own
`UNIQUE (bed_id, night)` and a 15-minute expiry
(`AccommodationService::holdBed/purgeExpiredHolds`). Checkout turns holds
into bookings inside the payment fulfilment (`confirmHolds`); if a hold
expired during a slow payment, `allocateBedsWithoutHolds` tries to re-secure
the same beds and flags the order for a human if any bed is genuinely gone —
that flag surfaces as a **critical problem on the Rooming operations screen**
(see §7).

If you remember one thing: **never write to `bookings` without letting the
unique index be the final authority**. Catch PDO error `23000` and tell the
user the bed was taken; see `RoomingService::moveBooking()` for the pattern.

---

## 4. The money path

```
cart_items → CheckoutController::place → orders(status=pending_payment) + order_items
          → signed redirect to PayFast (CheckoutController::pay)
          → PayFast POSTs the ITN to /payment/notify
          → PayFastService::handleNotification   ← THE ONLY PATH TO status=paid
          → OrderService::markPaid                fulfilment (idempotent)
```

**`handleNotification` runs five checks, cheapest first**: signature →
order exists → amount matches → source IP (fails open with a logged
warning, because PayFast publishes hostnames, not IPs, and a DNS wobble
must not lose a real payment — the other four checks still stand) → a
server-to-server confirmation POST back to PayFast. Landing on
`/payment/success` marks **nothing** paid; the page just shows whatever
status the order genuinely has. This is tested: the smoke test forges a
signature and under-pays, and both are rejected without touching the order.

**Fulfilment (`markPaid`) is idempotent** — PayFast legitimately re-sends
ITNs. A second notification for a paid order is logged and ignored.
Fulfilment confirms beds, writes transport bookings, reduces stock (variant
and product level, with an `inventory_movements` audit row), records
donations, counts coupon usage and queues the emails.

**Refunds never move money.** The admin records a refund the committee has
already processed in the PayFast dashboard or by EFT
(`Admin\FinanceController::recordRefund`). The service refuses to
over-refund, and only a **full** refund with the "release inventory" box
ticked frees the beds and seats — a partial refund leaves the booking
standing. Everything lands in the `refunds` ledger with who recorded it.

**Testing payments without PayFast:** set `PAYFAST_VALIDATE_URL` in `.env`
to a local stub that answers `VALID` (a one-line PHP file served with
`php -S`), and the setting `payfast_skip_ip_check` for local addresses.
The whole genuine pipeline then runs end-to-end on any machine. Both
overrides log loudly and neither may ever be set in production.

**Going live** is `PAYFAST_MODE=live` plus the live merchant credentials in
`.env` — see `docs/payfast-setup.md`. Nothing in the code changes.

---

## 5. Database

44 tables; `database/schema.sql` is the canonical DDL, and the one-run
`/install` executes it (the SQL splitter in `InstallController` is quote-
and comment-aware — don't replace it with `explode(';')`). Migrations for
already-installed sites live in `database/migrations/` and are plain SQL,
applied by hand or via cPanel's phpMyAdmin; the only one so far is the
finance tables. `database/seed.sql` is required content (pages, email
templates, settings); `database/demo-data.sql` is polished demo content.

Families, by prefix: catalogue (`products`, `product_variants`,
`product_images`, `product_categories`, `coupons`, `inventory_movements`);
accommodation (`room_types`, `room_units`, `beds`, `bed_rates`,
`booking_holds`, `bookings`); transport (`transport_routes`,
`transport_slots`, `transport_bookings`); orders & money (`carts`,
`cart_items`, `orders`, `order_items`, `payments`, `payment_logs`,
`donations`, plus the finance suite: `refunds`, `expenses`,
`expense_categories`, `budget_lines`, `bank_reconciliations`); people
(`users`, `user_roles`, `password_resets`, `email_verifications`); content
(`pages`, `banners`, `faqs`, `gallery_images`, `programme_items`, `events`,
`contact_messages`, `service_applications`, `email_templates`); plumbing
(`settings`, `rate_limits`, `admin_audit_logs`).

Rows seeded as demonstration data are flagged `is_mock = 1` wherever a
table supports it, so demo content can be cleared without touching real
records — `php tools/seed-demo-orders.php --purge` removes every demo
order and puts stock and shuttle seats back exactly.

---

## 6. Admin, roles and the finance suite

Roles are capability sets in `AuthService::CAPABILITIES`: `super_admin`,
`finance_admin`, `accommodation_admin`, `transport_admin`, `merch_admin`,
`content_editor`, `checkin_volunteer`. Routes gate with
`['admin:<capability>']`; the sidebar in `app/Views/layouts/admin.php`
hides what the role can't reach, but the middleware is the enforcement.

**The finance suite** (`/admin/finance/*`, capability `finance`) is six
screens on one shared period filter, all fed by `FinanceService`. The rules
its numbers follow are the point:

- Income counts **only orders PayFast confirmed as paid**. Pending orders
  are shown separately as pipeline, never mixed into revenue.
- A gateway fee PayFast hasn't reported yet is **estimated and labelled as
  an estimate** — a treasurer is never shown a guess dressed as a fact.
- Expense actuals count **paid + committed** (a signed quote is money
  owed); *cash* surplus counts only what has actually left the bank.
- The reconciliation screen lists every confirmed payment to tick off
  against the bank statement, and a separate exceptions panel for the
  handful that need a human. A fully refunded order is *not* an exception.

CSV exports live in `Admin\ExportController` — 16 datasets including
`finance-pack`, the whole financial position on one sheet for a committee
meeting. The CSV builder defuses spreadsheet formula injection but leaves
plain negative numbers numeric (there's a smoke-test check pinning that).

**The rooming console** (`/admin/bookings/operations`, plus the printable
run sheet and the move-a-guest screen) is the booking chair's side, fed by
`RoomingService`. Its problem list is deliberate plain English, worst
first: *paid with no bed allocated* is critical; missing guest names and
accessibility needs sitting in non-accessible rooms are warnings. Roommate
requests are checked against reality (the named person actually in the same
unit on the same night). Moving a guest changes only the bed — reference
and price survive, every move is audit-logged, and the unique index
protects the move against races.

---

## 7. Tools

| Tool | What it does |
|---|---|
| `tools/dev-router.php` | Router for `php -S` local serving |
| `tools/smoke-test.php` | 38 checks of the invariants that matter: bed rules, hold behaviour, payment verification, finance arithmetic, CSV safety. Run it before and after every change. |
| `tools/seed-demo-orders.php [n] [--purge]` | Places realistic orders through the site's own cart/holds/fulfilment path; `--purge` removes them and restores stock exactly |
| `tools/import-venue-images.php [--list\|--dry-run]` | Downloads Boschendal's own photography (25-image curated manifest) into `assets/img/venue/real/` and registers gallery/room images. Run from a machine with ordinary internet access. Confirm usage with the venue in writing first. |
| `tools/generate-images.php` | Regenerates the GD-drawn placeholder illustrations (JPEG + WebP) |
| `tools/package.php` | Builds the upload zip for cPanel |

---

## 8. Front-end notes

- Two stylesheets: `assets/css/app.css` (public) and `admin.css` (admin),
  hand-written, custom properties for the 13-colour palette, no framework.
- Fonts are self-hosted (Lora + Work Sans, OFL) in `assets/fonts/`.
- Wide tables must live inside `.table-wrap` or `.ledger-scroll` so pages
  never scroll sideways on a phone; grid children have `min-width: 0` for
  the same reason. If you add a table and the page scrolls horizontally at
  375 px, you forgot the wrapper.
- The venue-page films use a click-to-load facade: **nothing loads from
  YouTube until the visitor presses play**, then a `youtube-nocookie.com`
  iframe swaps in. Keep that property; it is a privacy promise on the page.
- The flight/car-hire buttons (`partials/travel-buttons.php`) appear on the
  checkout and the payment confirmation **only** — that placement was an
  explicit committee decision. Don't spread them.
- The only external runtime requests the public site can cause: WhatsApp
  link, Google Maps link, GA4 (if configured), and YouTube after an
  explicit play. Everything else is self-hosted.

---

## 9. What was verified, and how to re-verify

All of this was tested against a real MariaDB and a real browser before
handover; each is repeatable:

1. **Installer** from an empty database via `/install` (one run).
2. **The bed invariant**, at the database level: hold → sibling stays on
   sale → confirm → double-book refused by the unique index → cancel frees
   the bed (`tools/smoke-test.php`).
3. **A full order through the browser UI** (Playwright): account
   registration → registration ticket → bed with guest details → shuttle
   (the airport route correctly refuses to book without a flight number) →
   t-shirt variant → R150 donation → checkout → PayFast handoff page with
   signed fields.
4. **The ITN pipeline** end-to-end against a local `VALID` stub: order
   paid, bed confirmed, stock reduced with movement rows, donation
   recorded, fee captured. Forged signatures and under-payments rejected;
   `/payment/success` proven not to mark anything paid.
5. **Refund path**: partial refund recorded (bookings untouched),
   over-refund refused with an accurate message, full refund with release
   frees exactly the right beds while unrelated bookings on the same beds
   for other nights stay live.
6. **A 180-page signed-in crawl** of the public site, account area and
   admin: zero broken links, zero PHP errors, no horizontal overflow at
   375 px on any admin or finance screen.

---

## 10. Honest limitations, and the to-do list for next year

**Facts still to confirm with the venue** (tracked in
`docs/venue-source-log.md`): 2027 rates (current `bed_rates` are
placeholders), the accessible-room split, check-in/out times, the partner
guest house arrangement, shuttle timings. The venue *structure* (18 Retreat
cottages × 2 en-suite rooms × 2 beds = 72 beds, plus partner overflow) is
from Boschendal's published material.

**Before launch, in order:**

1. Run `php tools/import-venue-images.php` from any normal machine, and get
   the venue's written OK on photo usage.
2. Replace placeholder rates/dates once the venue contract is signed.
3. Set the real WhatsApp number, GA4 id and Search Console token in
   Admin → Settings.
4. `PAYFAST_MODE=live` + live credentials; make one real R5 payment and
   refund it (`docs/payfast-setup.md`).
5. SMTP credentials (`docs/smtp-setup.md`); send yourself every email from
   the test screen.
6. Clear demo data: `php tools/seed-demo-orders.php --purge`, and review
   anything flagged `is_mock` in the admin.
7. Work through `docs/testing-checklist.md` on the production host itself.

**Known trade-offs, on purpose:**

- No queue worker: emails send inline via SMTP and fall back to a disk
  queue (`storage/email-queue/`) with a cron-friendly flush — cPanel-safe.
- PHP sessions on disk: fine for this scale; don't add Redis, the host
  doesn't have it.
- The finance suite records; it does not bank. Money moves only in PayFast
  or the bank, and the ledger here mirrors it. Resist any request to "just
  add a pay-out button".
- One deliberate fail-open (the ITN source-IP check, logged) so a DNS
  wobble can't lose a real payment; signature + amount + confirmation stay
  mandatory.

---

## 11. Documentation index

| Doc | For |
|---|---|
| `docs/handover.md` | The committee: what was delivered, decisions and why |
| `docs/cpanel-deployment-guide.md` | Putting it on the server, step by step |
| `docs/payfast-setup.md`, `smtp-setup.md`, `analytics-setup.md`, `search-console-setup.md` | Third-party service setup |
| `docs/admin-user-guide.md` | The committee members using the admin |
| `docs/backup-restore-guide.md` | Backups, and proving they restore |
| `docs/testing-checklist.md` | Manual pre-launch checks |
| `docs/venue-source-log.md` | Which venue facts are sourced vs. assumed, image manifest and film credits |
| `docs/seo-checklist.md` | Search behaviour |

If something here contradicts the code, the code and its tests win — then
please fix this document.
