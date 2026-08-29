# SARCNA 2027 Convention — working notes

Project memory. Durable rules and hard-won lessons, not a change log.

**The convention:** 27–29 August 2027, Boschendal Retreat Cottages &
Conference Venue. Theme *"Rooted in Recovery. Rising Together."* This is a
Narcotics Anonymous convention: **anonymity is a design constraint, not a
nicety.** Anywhere a delegate's name or email could leak to someone who is not
them, that is a real harm, not a theoretical one.

---

## Hard constraints — do not violate these

**Stack.** cPanel-first PHP 8.1+ (8.4 on the host) and MySQL/MariaDB via PDO.
Server-rendered templates, hand-written CSS, vanilla JS.

**No dependencies. None.** No Composer, no npm, no CDN, no build step, no
Node.js in production. Explicitly forbidden by the brief: Firebase, Vercel,
MongoDB, Cloudinary, serverless. **Any added server dependency is a
regression**, however convenient. There is no `package.json`, `composer.json`
or `Dockerfile` — a generic audit tool that looks for them reports "nothing
found", which is a fact about the tool, not the project.

**The two-level folder layout is mandatory.** `public_html/index.php` does
`require_once dirname(__DIR__) . '/app/bootstrap.php'` — it looks *one level
up*. Flatten it (put `app/` beside `index.php`) and the site returns **HTTP 500
and writes nothing to any log**. Verified.

```
sarcna2027/          ← application root, .htaccess blocks web access
  app/  database/  docs/  storage/  tools/  .env
  public_html/       ← document root, the only public folder
```

**No credentials in the repository, ever.** Not in code, not in docs, not in
commit messages. Also: never accept a password pasted into chat — say so and
ask for it to be rotated if one appears.

---

## Architecture invariants

**Bed-level inventory.** `room_types → room_units → beds`. Booking one bed in
a two-bed room must leave the sibling on sale. `bookings.active_night` is a
STORED generated column with `UNIQUE (bed_id, active_night)`; because MySQL
unique indexes ignore NULL, cancellation frees the bed automatically and
double-booking is impossible at the database level, not just in application
code. `booking_holds` has `UNIQUE (bed_id, night)` and a 15-minute expiry.

**Money is always integer cents.** Never floats.

**PayFast ITN is the only path to `paid`.** Landing on the return URL proves
nothing and must never mark an order paid. Five checks run in
`PayFastService::handleNotification()`:

1. signature 2. order exists 3. amount 4. source address 5. PayFast confirms

Cheap checks run first so a forgery costs nothing — **but any check whose
failure *changes* an order waits until 4 and 5 have passed.** The amount check
used to call `markFailed()` (releasing shuttle seats, emailing the delegate)
before proving the notification came from PayFast; since the signature is
`md5(fields + passphrase)` and PayFast permits a blank passphrase, that let
anyone holding a reference destroy a stranger's booking. Do not reorder this
back.

**`OrderService::belongsToCurrentVisitor()` is the single ownership rule** for
every route that takes an order reference from the address bar. A reference is
**not a secret** — it travels in confirmation email, browser history and
PayFast's own screens. Proof of ownership is being signed in as the owning
account, or holding the session whose cart became the order. Nothing else.

**Settings that gate a page must also gate the action.** `allow_guest_checkout`
once bound `GET /checkout` but not `POST /checkout`, so orders landed with
`user_id NULL` — invisible to the delegate, orphans on the booking chair's
list.

**The admin gate is two-factor:** the `is_admin` flag *and* a role. Exports are
gated twice over — the `exports` permission, then each dataset's own capability
in `ExportController::DATASET_CAPABILITIES`. An accommodation admin holding
`exports` still cannot pull the finance pack.

**`purge_users()` refuses to delete admin accounts** unless passed
`$includeAdmins = true`. That guard protects real committee members; keep it.

---

## Who this is for

Two people are named in the brief as having had trouble before:

- **The finance chair** — "financial reporting is of utmost importance." The
  finance screens must agree with the orders table *to the cent*. Fictional
  revenue from leftover test data is the specific failure that has happened.
- **The booking chair** — "has had issues, and we want to go out of our way to
  accommodate him." Orphan orders with no account behind them are exactly his
  problem.

---

## Testing

```bash
php -S 127.0.0.1:8000 -t public_html tools/dev-router.php   # dev server

php tools/smoke-test.php                            # fast invariants
php tools/race-test.php     http://127.0.0.1:8000   # concurrency
php tools/security-test.php http://127.0.0.1:8000   # adversarial
php tools/audit.php         http://127.0.0.1:8000 --password=PASSWORD
```

**Read the total the run prints. Do not trust a number written in a
document — including this one.** All four run in CI on every push
(`.github/workflows/tests.yml`), which installs through the real `/install`
form via `tools/ci-install.php` rather than reimplementing it.

`security-test.php` refuses to run against anything but a local address,
because it forges payments and places orders.

**A run must leave the database byte-identical.** Check orders, users,
payments and total revenue before and after. Drift is the bug.

---

## Lessons that cost real time

**Hardcoded counts go stale.** Check counts have been wrong in five places at
once (two documents said 122, three said 169; the truth was 167, then 183, then
200). Never write a count you are not prepared to re-verify; prefer deriving it.

**Branch names in documents go stale.** The deployment guidance was wrong, then
right-then-stale, then stale again — on the same sentence, in one day. It now
gives commands instead of an answer:

```bash
curl -s https://api.github.com/repos/logiagenesis/sarcna | grep '"default_branch"'
git rev-parse HEAD^{tree}; git rev-parse origin/main^{tree}
```

A branch name is a fact with a date on it. A tree hash comparison is not.

**A test that only ever passes proves nothing.** After fixing something, put
the bug back, confirm the test goes red *with the right evidence*, then restore.
Every security fix in this repo was verified that way.

**Cleanup checks that count only the convenient table lie.** The audit once
reported "0 left behind" while 230 orphaned records — including 12 paid orders
counting as revenue — sat in the database; the finance-agreement check stayed
green because both sides were wrong equally. Then the *security test I wrote to
catch that* did the same thing: it left seven admin accounts behind while its
own "removed everything" check, which counted only orders, passed. Count every
kind of record you create.

**Tools must take the base path or URL as an argument.** `race-test.php` had
`require '/home/user/sarcna/app/bootstrap.php'` hardcoded and worked only on
one machine. CI caught it within a minute of CI existing.

**The PHP dev server ignores `.htaccess` entirely.** It cannot prove a deny
rule works. Only a real Apache/LiteSpeed can.

**Look at the rendered page, not just the status code.** A `picture()` path bug
(stored paths without a leading slash routed to `asset()` → `/assets/photos/` →
404) made every photograph render as broken alt text while the audit passed,
because the audit only checked one page. A screenshot found it.

**Check before reacting.** I once misread a diff's direction and thought a PR
had deleted 846 lines of tests. It had added them.

---

## Environment

**Egress is proxied and blocks the general web** — boschendal.com, google.com,
image hosts, the target cPanel host. Only allowlisted MCP servers work. Oversized
MCP tool results are written to disk under `~/.claude/projects/.../tool-results/`,
which is how 31 images were fetched without flooding context.

**Target host:** domains.co.za cPanel, LiteSpeed, PHP 8.4. **No SSH** (ports 22,
2222, 21098 all time out), **no cPanel API**, document root cannot sit outside
`public_html`. File Manager is the primary upload tool. Consequence: you cannot
run the test suite on the host; diagnose through `preflight.php` in a browser.

Chromium for Playwright is at `/opt/pw-browsers/`; never run
`playwright install`.

---

## Outstanding

- **The venue photographs need written permission from Boschendal.** The
  committee's responsibility; recorded in every source note. Eight photographs
  are live, screened by eye for alcohol and identifiable children — judgment the
  filenames gave no hint of. `tools/import-drive-images.php` records every
  rejection with its reason in its `EXCLUDED` constant.
- **Only the Super Admin can reach service applications and contact messages.**
  No other role holds those permissions. Not a hole — the opposite — but those
  two jobs cannot be delegated without a committee decision on the role map.
- **No Content-Security-Policy header.** Defence in depth; no XSS sink was
  found. Adding one means auditing the inline styles in the views.
