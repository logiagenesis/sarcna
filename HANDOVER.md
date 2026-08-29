# SARCNA 2027 Convention — Handover

**The single handover document for this project.** Everything a committee
member or a developer needs is here. The other files in `docs/` are
step-by-step recipes for individual jobs (deployment, PayFast, SMTP); this
document is the one to read first and the one to hand over.

| | |
|---|---|
| **Project** | SARCNA 2027 Convention website |
| **Event** | 27–29 August 2027, Boschendal Retreat Cottages & Conference Venue, Cape Winelands |
| **Theme** | Rooted in Recovery. Rising Together. |
| **Repository** | `github.com/logiagenesis/sarcna` — branch `main` |
| **Stack** | PHP 8.1+ and MySQL/MariaDB on ordinary cPanel shared hosting |
| **Runtime dependencies** | None. No Composer, no npm, no CDN, no build step |
| **Status** | Feature-complete and audited. Not yet deployed to a live host. |

---

## Contents

1. [What you have](#1-what-you-have)
2. [Proof it works](#2-proof-it-works)
3. [How to run it in ten minutes](#3-how-to-run-it-in-ten-minutes)
4. [How the system works](#4-how-the-system-works)
5. [The two rules that must never break](#5-the-two-rules-that-must-never-break)
6. [The admin, role by role](#6-the-admin-role-by-role)
7. [Everything the committee must supply](#7-everything-the-committee-must-supply)
8. [Launch checklist](#8-launch-checklist)
9. [Decisions taken, and why](#9-decisions-taken-and-why)
10. [Honest limitations](#10-honest-limitations)
11. [For the next developer](#11-for-the-next-developer)
12. [Reference: files, tools and docs](#12-reference-files-tools-and-docs)

---

## 1. What you have

A complete convention website and back-office. Delegates register, book a bed,
book a shuttle seat, buy merchandise and donate — all in one cart, paid once
through PayFast. The committee runs the whole event from an admin dashboard
without needing a developer.

| | |
|---|---|
| Public pages | 36 |
| Admin screens | 37, across 7 roles |
| Routes | 164 |
| Database tables | 44 |
| Services (the business logic) | 15 |
| CSV exports | 16 |
| Transactional email templates | 14 |
| PHP files / lines | 203 / ~24 200 |
| Documentation files | 15 |

**What a delegate can do:** browse the convention, programme, venue and
gallery; watch three official venue films; register (full weekend or day
pass); book a specific bed on specific nights, with roommate and accessibility
requests; book shuttle seats on a specific departure; buy merchandise with
size and colour variants; donate; apply to serve; pay once by card, Instant
EFT, SnapScan or Mobicred; then see everything in their own account with an
invoice.

**What the committee can do:** see the money (six finance screens), run the
rooming (occupancy, problem list, roommate matching, run sheets, move a guest
between beds), manage the shop and stock, manage transport and manifests,
check delegates in at the door, edit all site content, handle service
applications and contact messages, and export 16 different CSVs.

---

## 2. Proof it works

Everything below is repeatable. Nothing here is an assurance — each line is a
command you can run.

### The one command that checks everything

```bash
php tools/audit.php
```

**169 checks. All 169 currently pass.** It exits non-zero if anything fails,
so it can gate a deployment. It covers:

| Section | What it proves |
|---|---|
| 1. Public pages | Every public page, plus every active room type, transport route and product detail page, returns HTTP 200 |
| 2. Customer journey | Creates a real account, adds a registration, **holds a real bed**, books a shuttle seat, reaches checkout, places an order and gets a signed PayFast handoff — through the real forms, not fixtures |
| 3. Payment rules | Landing on the success URL does **not** mark an order paid; a forged signature is rejected; a correctly signed notification does mark it paid and allocates the bed and the shuttle passenger |
| 4. Bed rules | Runs the 38-check smoke test: the bed invariant, holds, the database refusing a double-booking, cancellation freeing a bed, finance arithmetic, CSV safety |
| 5. Admin | Signs in as admin, loads all 31 admin screens, downloads all 16 CSV exports |
| 6. Security | Signs out and proves the admin is blocked; CSRF-less POSTs refused; `.env`, application code, SQL and `.git` all unreachable over the web |
| 7. Writes | Twenty-one committee actions performed over HTTP and then **verified in the database**: saving a setting, recording an expense (and its effect on the finance total), adding and deleting a budget line, creating a coupon, creating and editing a product, adding and deleting a programme item and an FAQ, saving an order note, moving a guest to another bed, and checking a delegate in and out |
| 8. Cart | Line totals match the catalogue price, a 10% coupon takes off exactly 10%, removing it restores the total, clearing empties the cart |
| 9. Public forms | The contact form and a service application actually reach the committee |
| 10. Data integrity | Nine SQL invariants: no bed double-booked, no hold on a booked bed, every booking on a real bed in the right unit, every paid order has a payment, order totals match their line items, no refund exceeds what was paid, no shuttle oversold, no negative stock, and the finance screens agree with the orders table to the cent |
| 11. Email | All 14 templates installed, the queue is writable, and paying an order queued its confirmations |
| 12. Clean-up | Every record the audit created is removed, and it proves it |

It starts and tears down its own PayFast stub, clears this machine's
rate-limit counters (the audit is a legitimate high-volume client; the limiter
itself is untouched), and restores `.env` and the security settings
byte-for-byte when it finishes — including if interrupted. Running it twice in
a row gives the same result, and it leaves no test records behind.

The transcript of the last full run is committed at
[`docs/audit-result.txt`](docs/audit-result.txt) so you can read the result
without running anything.

### The other two commands

```bash
php tools/smoke-test.php        # 38 invariant checks, all passing
php tools/seed-demo-orders.php  # realistic demo data; --purge removes it exactly
```

### Verified from a completely empty database

The database was dropped, recreated empty, and the installer run through the
web form exactly as it will be on cPanel. Result:

```
✓ Database tables created (47 statements)
✓ Settings and email templates seeded
✓ Demo content loaded (22 statements)
✓ Accommodation inventory generated (56 units, 112 beds)
✓ Administrator account created
✓ Site settings saved
✓ .env written to the application root
✓ Installer locked (app/Config/installed.lock)
```

44 tables, 3 room types, 56 units, 112 beds, 15 products, 7 routes, 16
departures, 14 email templates, 40 settings, 1 admin, **0 orders**. Visiting
`/install` again returns HTTP 410. The full 122-check audit then passed
against that fresh install.

---

## 3. How to run it in ten minutes

You need PHP 8.1+ and MySQL/MariaDB. Nothing else.

```bash
git clone https://github.com/logiagenesis/sarcna.git
cd sarcna
cp .env.example .env          # fill in DB credentials only; the rest is asked for on screen
php -S 127.0.0.1:8000 -t public_html tools/dev-router.php
```

Open `http://127.0.0.1:8000/install`, fill in the form, submit. The site is
live. Sign in at `/login` with the administrator you just created.

Optional, so the admin screens have something to show:

```bash
php tools/seed-demo-orders.php 110
```

To put it on a real cPanel host, follow `docs/cpanel-deployment-guide.md`.

---

## 4. How the system works

### The request path

```
public_html/index.php
  → app/bootstrap.php      .env, config, helpers, PSR-4 autoloader (App\ → app/)
  → App\Core\Kernel        routing, middleware, dispatch
      → Router             regex routes from app/Config/routes.php, matched in order
      → Middleware         guest | auth | admin:<capability> | throttle:n,seconds
      → Controller         app/Controllers (Admin\ for the back-office) — thin
      → Service            app/Services — all the business rules live here
      → View               app/Views — plain PHP templates, layouts and sections
```

### The data model, by family

| Family | Tables |
|---|---|
| Catalogue | `products`, `product_variants`, `product_images`, `product_categories`, `coupons`, `inventory_movements` |
| Accommodation | `room_types`, `room_units`, `beds`, `bed_rates`, `booking_holds`, `bookings` |
| Transport | `transport_routes`, `transport_slots`, `transport_bookings` |
| Orders & money | `carts`, `cart_items`, `orders`, `order_items`, `payments`, `payment_logs`, `donations` |
| Finance | `refunds`, `expenses`, `expense_categories`, `budget_lines`, `bank_reconciliations` |
| People | `users`, `user_roles`, `password_resets`, `email_verifications` |
| Content | `pages`, `banners`, `faqs`, `gallery_images`, `programme_items`, `events`, `contact_messages`, `service_applications`, `email_templates` |
| Plumbing | `settings`, `rate_limits`, `admin_audit_logs` |

`database/schema.sql` is the canonical DDL. `database/migrations/` holds
plain SQL for sites already installed. `database/seed.sql` is required
content; `database/demo-data.sql` is demonstration content.

Demonstration rows are flagged `is_mock = 1` wherever the table supports it,
so demo content can be cleared without touching real records.

### Conventions you must follow

- **Money is always integer cents.** `money(130500)` → "R1 305.00";
  `rands('1305.00')` → `130500`. Never floats.
- **Every dynamic value in a view goes through `e()`.**
- **Never query without a prepared statement.** `Database` makes that the easy
  path.
- **Literal routes before parameterised ones** (`/bookings/operations` before
  `/bookings/{id}`).
- Controllers use `flashSuccess()` / `flashError()` / `back()` / `redirect()`.
  There is deliberately no base `success()` helper — that name belongs to
  `PaymentController::success()`, the thank-you page.

---

## 5. The two rules that must never break

### Rule 1 — Nobody is ever double-booked

The brief's central requirement is that booking one bed in a two-bed room
leaves the second bed on sale. This is enforced **by the database**, not by
application code:

```
room_types → room_units → beds
```

`bookings` carries a stored generated column and a unique index:

```sql
active_night DATE GENERATED ALWAYS AS
  (CASE WHEN status IN ('confirmed','checked_in') THEN night ELSE NULL END) STORED,
UNIQUE KEY uq_bed_active_night (bed_id, active_night)
```

MySQL ignores NULLs in unique indexes, so:

- Two live bookings of the same bed on the same night are **impossible** —
  the database refuses the write, whatever the PHP does.
- Cancelling or refunding sets the status, `active_night` becomes NULL, and
  the bed returns to sale automatically. No cleanup job.

Carts do not own beds — **holds** do. `booking_holds` has its own
`UNIQUE (bed_id, night)` and a 15-minute expiry. Checkout converts holds into
bookings during payment fulfilment.

**If you change anything here:** never write to `bookings` without letting the
unique index be the final authority. Catch PDO error `23000` and tell the user
the bed was taken. `RoomingService::moveBooking()` is the pattern to copy.

### Rule 2 — Nothing is paid unless PayFast says so

```
cart → orders (pending_payment) → signed redirect to PayFast
     → PayFast POSTs a notification to /payment/notify
     → PayFastService::handleNotification()   ← the only path to status = paid
     → OrderService::markPaid()               ← fulfilment, idempotent
```

`handleNotification()` runs five checks, cheapest first:

1. **Signature** — the payload is signed with the merchant passphrase.
2. **Order exists** — the reference matches a real pending order.
3. **Amount matches** — to the cent.
4. **Source IP** — resolved from PayFast's published hostnames. *This one
   fails open with a logged warning*, deliberately: PayFast publishes
   hostnames rather than fixed IPs, and a DNS wobble must not lose a real
   payment. The other four checks still stand.
5. **PayFast confirms** — a server-to-server POST back to PayFast.

**Landing on `/payment/success` marks nothing paid.** The page shows whatever
status the order genuinely has. This is tested three ways in the audit.

Fulfilment is **idempotent** — PayFast legitimately re-sends notifications. A
second notification for a paid order is logged and ignored. Fulfilment
confirms beds, writes shuttle passengers, reduces stock (with an
`inventory_movements` audit row), records donations, counts coupon usage and
queues the emails.

**Refunds never move money.** The admin records a refund the committee has
already processed in PayFast or by EFT. The system refuses to over-refund, and
only a *full* refund with "release inventory" ticked frees the beds and seats.

---

## 6. The admin, role by role

Seven roles, defined as capability sets in `AuthService::CAPABILITIES`. Routes
are gated by middleware; the sidebar only hides what a role cannot reach.

| Role | Sees |
|---|---|
| `super_admin` | Everything |
| `finance_admin` | The six finance screens, orders, payments, donations, all exports |
| `accommodation_admin` | Rooming operations, bookings, bed board, run sheet, holds, room types, check-in |
| `transport_admin` | Routes, departures, manifests |
| `merch_admin` | Products, variants, stock, coupons |
| `content_editor` | Banners, pages, programme, FAQs, gallery |
| `checkin_volunteer` | The check-in desk only |

### The finance chair's section (`/admin/finance`)

Six screens on one shared period filter — today through financial year, or any
custom range.

| Screen | What it gives |
|---|---|
| **Overview** | Money received, fees and refunds, net income, expenditure, surplus, cash on hand, pipeline, exceptions, stock on hand |
| **Income** | By category, product, room type, route and day |
| **Expenses** | A real ledger — supplier, invoice, due date, status — plus a bills-coming-up diary |
| **Budget vs actual** | Line by line, both sides, with variance and progress |
| **Bank reconciliation** | Every confirmed payment to tick off, paginated, with an exceptions panel |
| **Refunds** | The refund ledger, with the policy written beside it |

**The rules its numbers follow — this is the important part:**

- Income counts **only orders PayFast has confirmed as paid**. Orders awaiting
  payment appear separately as *pipeline* and are never mixed into revenue.
- A gateway fee PayFast has not yet reported is **estimated and labelled as an
  estimate**. A treasurer is never shown a guess dressed as a fact.
- Expense actuals count **paid plus committed** — a signed quote is money owed.
  *Cash* surplus counts only what has actually left the bank.
- A refund can never exceed what the order was paid.

`finance-pack.csv` puts the entire financial position on one sheet for a
committee meeting.

### The booking chair's section (`/admin/bookings/operations`)

| Screen | What it gives |
|---|---|
| **Rooming operations** | The problem list, occupancy pressure, roommate matching, special requests, arrivals and departures |
| **Bed board** | Every bed against every night |
| **Run sheet** | The printable door list — unit, bed, guest, check-in code, phone, access needs |
| **Move a guest** | Move one booking to another bed, safely |
| **Live holds** | What is in someone's cart right now |

The problem list is deliberately plain English, worst first:

- **Critical** — an order is *paid* for a bed but no bed was allocated. This
  is the one thing that must never reach the venue unresolved.
- **Warning** — a bed with no guest name (reception cannot hand over a key);
  an accessibility need sitting in a non-accessible room.

Roommate requests are checked against reality: a request counts as *matched*
only when the named person is actually in the same unit on the same night. The
screen also says when the person asked for has no booking at all.

Moving a guest changes only the bed — reference and price survive, every move
is written to the audit log, and the unique index protects the move against a
race.

---

## 7. Everything the committee must supply

Nothing on this list can be invented by a developer.

| # | What | Why it matters |
|---|---|---|
| 1 | **Signed venue contract** | Rates, check-in times, the accessible-room split and the partner guest house are placeholders |
| 2 | **Confirmed rates for 2027** | `bed_rates` currently holds placeholder pricing |
| 3 | **Written permission to use Boschendal's photographs** | The importer is ready; the permission is the committee's to get |
| 4 | **Written accessibility details from the venue** | Guests booking accessible units rely on this |
| 5 | **Transport supplier quotes** | Routes, times, pick-up points, capacities and prices are demo data |
| 6 | **Live PayFast credentials** and the payout account | The site cannot take real money without them |
| 7 | **A mailbox with SPF and DKIM** | Otherwise confirmations go to spam |
| 8 | **GA4 measurement ID** | New, or a decision to reuse the old one |
| 9 | **Search Console verification code** | For indexing |
| 10 | **WhatsApp number** | The floating button hides itself without one |
| 11 | **Legal review of the policy pages** | They are competent drafts, not legal advice |
| 12 | **Merchandise range, sizes, colours, prices** | Currently demo data |
| 13 | **Programme confirmation** | Currently a plausible demo weekend |

### From the outgoing vendors

The audit of 28 August 2026 found the old site spread across five accounts
with no source code recovered. Nothing from it is reused, but take control of
these before shutting anything down:

- [ ] MongoDB Atlas — export the data; it may hold real registrations
- [ ] Vercel — the environment variable list
- [ ] Firebase project `sarcna-staging` — owner rights, then decommission after DNS moves
- [ ] Cloudinary account `dmemecjil` — download the images
- [ ] PayFast — confirm the payout account
- [ ] The old admin URL and credentials
- [ ] Written confirmation of who pays for each account, and on what card

### Access to hand over

| What | Should be held by |
|---|---|
| cPanel login | Two committee members |
| GitHub repository | The committee's own account or organisation |
| Site super admin | At least two people |
| PayFast dashboard | Treasurer and one other |
| Domain and DNS | Committee |
| `.env` contents | The committee's password manager |

**Create a second super admin before the first one goes on holiday.** The site
refuses to remove the last super admin, but it cannot create one for you.

---

## 8. Launch checklist

Work top to bottom. Each step is verifiable.

- [ ] **1. Deploy** — follow `docs/cpanel-deployment-guide.md`, run `/install`
- [ ] **2. Import the venue photographs** — `php tools/import-venue-images.php`
      from any machine with ordinary internet access (25 curated Boschendal
      photographs; the build sandbox could not reach image hosts). Get the
      venue's written permission first.
- [ ] **3. Replace placeholder rates and dates** once the venue contract is signed
- [ ] **4. Enter the real settings** — WhatsApp number, GA4 ID, Search Console
      token, committee email addresses (Admin → Settings)
- [ ] **5. Go live on PayFast** — `PAYFAST_MODE=live` plus live credentials in
      `.env`. Make one real R5 payment and refund it. See `docs/payfast-setup.md`
- [ ] **6. Configure SMTP** — `docs/smtp-setup.md`. Send yourself every email
      from the test screen
- [ ] **7. Check diagnostics** — Admin → Settings → Diagnostics. **PayFast
      reachable must be green**, or notifications will be rejected as
      unconfirmed and orders will sit unpaid
- [ ] **8. Clear the demo data** — `php tools/seed-demo-orders.php --purge`,
      then delete the two demo accounts (`demo.customer@sarcna.org.za`,
      `demo.volunteer@sarcna.org.za`) and review anything flagged **Demo** in
      the admin
- [ ] **9. Legal review** of the policy pages
- [ ] **10. Run the audit on the production host** — `php tools/audit.php`.
      All 122 checks must pass
- [ ] **11. Work through `docs/testing-checklist.md`** by hand on the live site
- [ ] **12. Create the second super admin**
- [ ] **13. Take a backup and prove it restores** — `docs/backup-restore-guide.md`

---

## 9. Decisions taken, and why

Where the brief left a choice, or where a literal reading would have produced
a worse result. Change any of them freely.

**1. Self-hosted Lora and Work Sans.** The brief asked for a warm editorial
serif with a clean sans and preferred self-hosted fonts. These are the closest
pair under the SIL Open Font License, so the site makes **no third-party font
requests at all**. Swapping them later is four `@font-face` blocks and two
variables in `app.css`.

**2. Original illustration as the placeholder, not stock photography.** Rather
than pass off a stock photo of some other farm as Boschendal, the site ships
original Cape Winelands illustrations generated by `tools/generate-images.php`
— clearly illustrative, unmistakably not a photograph, in the convention's
palette. `tools/import-venue-images.php` replaces them with Boschendal's own
photography in one command once permission is in hand.

**3. A PayFast notification, and nothing else, marks an order paid.** It would
be simpler to mark it paid when the customer returns to the site. It would
also mean anyone could type a URL and get a free registration.

**4. Bed availability is enforced by the database, not by application code.**
See [Rule 1](#rule-1--nobody-is-ever-double-booked).

**5. Shuttle seats are counted at checkout; beds are held at add-to-cart.**
Beds are individually identified, so they can be held. Seats are a count, so
they are reserved when the order is created and released if payment fails.

**6. Products that have been sold are deactivated, never deleted.** Deleting
them would break order history. The admin explains this when it happens.

**7. Secrets live in `.env`; settings live in the database.** The committee can
change the WhatsApp number, the analytics ID, shop opening and closing and
every piece of copy without touching the server. Nobody can change the PayFast
keys or the SMTP password from a browser.

**8. The finance suite records; it does not bank.** Money moves only in
PayFast or the bank; the ledger here mirrors it. Resist any request to "just
add a pay-out button".

**9. The venue films load nothing until you press play.** A locally hosted
poster is shown; only a deliberate click loads the privacy-enhanced
`youtube-nocookie.com` player. That is a promise made on the page.

**10. The flight and car-hire buttons appear on the checkout and confirmation
only.** An explicit committee decision. Do not spread them.

---

## 10. Honest limitations

Stated plainly, so nobody discovers them at an awkward moment.

1. **The PayFast notification requires outbound HTTPS from the server.** If the
   host blocks it, notifications are rejected as unconfirmed and orders sit
   unpaid. Check Diagnostics before launch. This is deliberate — the site fails
   closed rather than accepting an unverified payment.

2. **Venue photography is not yet imported.** The 25-image manifest is ready
   and one command imports it. Until then the site shows its own illustrations.

3. **Merchandise delivery is not implemented.** Everything is collected at the
   registration desk. The database and admin carry the flag; there is no
   shipping capture or courier pricing. Adding it is contained work.

4. **Refunds are recorded, not issued.** Refund in PayFast, then record it in
   the admin. Automating refunds would mean holding PayFast credentials with
   money-moving rights.

5. **Roommate matching is a check, not an algorithm.** The site records who
   wants to share with whom and tells you whether it actually happened. A human
   decides.

6. **One currency, one language.** ZAR and English only.

7. **Bed holds are released by traffic, not by a scheduler.** Expired holds are
   purged whenever anyone views the cart, the accommodation pages or the admin.
   A hold may sit a few minutes past expiry on a quiet site, but it can never be
   double-sold, because the check happens at write time. If the host offers
   cron, calling `/admin` on a schedule tidies it eagerly.

8. **No queue worker.** Emails send inline via SMTP and fall back to a disk
   queue at `storage/email-queue/` with a cron-friendly flush. This is a cPanel
   constraint, not an oversight.

9. **PHP sessions on disk.** Fine at this scale. Do not add Redis; the host
   does not have it.

10. **One deliberate fail-open** — the notification source-IP check, logged
    every time. Signature, amount and PayFast confirmation remain mandatory.

---

## 11. For the next developer

The stack was chosen so that whoever comes next can read it. No build step, no
framework to learn, no dependency to update. Everything is where its name says
it is.

**Start here:** `app/Services/AccommodationService.php`. It is the heart of the
thing. Then `PayFastService`, then `OrderService::markPaid()`.

**Before you commit:**

```bash
find app public_html tools -name '*.php' -exec php -l {} \; | grep -v 'No syntax errors'
php tools/smoke-test.php
php tools/audit.php
```

Silence from the first, 38/38 from the second, 122/122 from the third.

**The one constraint to preserve:** this must keep running on ordinary cPanel
shared hosting with nothing but PHP and MySQL. Any change that adds a server
dependency is a regression, however nice the library.

**Front-end notes:**

- Two hand-written stylesheets — `assets/css/app.css` (public) and `admin.css`.
  Custom properties for the 13-colour palette. No framework.
- Wide tables must sit inside `.table-wrap` or `.ledger-scroll`, and grid
  children carry `min-width: 0`. If a page scrolls sideways at 375 px, you
  forgot the wrapper.
- The only external requests the public site can cause: the WhatsApp link, a
  Google Maps link, GA4 if configured, and YouTube *after* an explicit play.

---

## 12. Reference: files, tools and docs

### Tools

| Command | What it does |
|---|---|
| `php tools/audit.php` | **The whole checklist.** 122 checks, exits non-zero on failure |
| `php tools/smoke-test.php` | 38 invariant checks: beds, holds, payments, finance, CSV safety |
| `php tools/seed-demo-orders.php [n]` | Realistic demo orders through the real cart and fulfilment |
| `php tools/seed-demo-orders.php --purge` | Removes every demo order, restoring stock and seats exactly |
| `php tools/import-venue-images.php` | Imports 25 curated Boschendal photographs (`--list`, `--dry-run`) |
| `php tools/generate-images.php` | Regenerates the placeholder illustrations |
| `php tools/package.php` | Builds the upload zip for cPanel |
| `php -S 127.0.0.1:8000 -t public_html tools/dev-router.php` | Local development server |

### Documentation

| Doc | For |
|---|---|
| **`HANDOVER.md`** | **This document. Start here.** |
| `docs/cpanel-deployment-guide.md` | Putting it on the server, step by step |
| `docs/payfast-setup.md` | Sandbox to live, and why an order might not be paid |
| `docs/smtp-setup.md` | Email, SPF and DKIM |
| `docs/analytics-setup.md` | GA4 |
| `docs/search-console-setup.md` | Search Console verification |
| `docs/seo-checklist.md` | Search behaviour |
| `docs/admin-user-guide.md` | For committee members using the admin |
| `docs/backup-restore-guide.md` | Backups, and proving they restore |
| `docs/testing-checklist.md` | Manual pre-launch checks on the live host |
| `docs/venue-source-log.md` | Which venue facts are sourced vs. assumed; the photo manifest and film credits |
| `docs/image-source-log.md` | Every image file and what should replace it |
| `docs/current-site-functionality-map.md` | What the old site did |
| `docs/functionality-parity-checklist.md` | Old site vs. new, feature by feature |

### Demo accounts — delete before launch

| Email | Password |
|---|---|
| `demo.customer@sarcna.org.za` | `Convention2027` |
| `demo.volunteer@sarcna.org.za` | `Convention2027` |

The administrator account is the one created during installation.

---

*If anything in this document contradicts the code, the code and its tests
win — then please fix this document.*
