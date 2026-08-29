# Security audit — 29 August 2026

A full adversarial pass over the site: not "does it work", which
`tools/audit.php` already answers, but "can it be made to misbehave".

Everything below was established by running against a real site over HTTP and
reading the database afterwards. Nothing was concluded from reading the source
alone — source is what you check when you already believe the answer.

Every finding is now covered by `tools/security-test.php`, which runs in CI. To
prove the tests were worth having, each fix was reverted one at a time and the
test confirmed red with the exact evidence before being restored.

---

## Findings

### 1. A stranger could open the payment page for someone else's order — HIGH

**Fixed.**

`/checkout/pay/{reference}` answered HTTP 200 to an anonymous visitor holding
any order reference. Two harms, both confirmed:

- **The buyer's identity leaked.** The page embeds the PayFast fields
  `name_first`, `name_last` and `email_address`. A reference is not a secret —
  it travels in the confirmation email, in browser history and through PayFast's
  own screens — so anyone who saw one could read who booked and their email
  address. At an NA convention, anonymity is not a nicety.
- **An unauthenticated write.** `PayFastService::recordRedirect()` inserted a
  `payments` row and a log event *before* any ownership check, so a stranger
  could pollute the payment log the treasurer reads.

The guard that was there only ran for signed-in visitors:

```php
if (AuthService::check() && $order['user_id'] !== null && ...)
```

An anonymous request failed the first condition and sailed through.

**The fix.** `OrderService::belongsToCurrentVisitor()` is now the single rule,
used by both `CheckoutController::pay` and `PaymentController::cancelled`.
Two things count as proof, and neither can be read off a reference: being signed
in as the account that owns the order, or holding the session whose cart became
it. A guest sent back from PayFast still has that session cookie, so the honest
payment path is unaffected — verified.

### 2. A forged notification could destroy a stranger's booking — HIGH

**Fixed.**

The PayFast notification handler ran five checks cheapest-first. Check 3, the
amount check, called `OrderService::markFailed()` — which releases the shuttle
seats and emails the delegate to say their payment failed — *before* check 4
(did this come from a PayFast server) and check 5 (does PayFast confirm it).

So the only thing standing between a stranger and a cancelled booking was the
signature. That signature is `md5(fields + passphrase)`, and PayFast permits a
blank passphrase. With none set, every input is public, the signature is
forgeable by anyone who has seen a reference, and one forged notification with a
wrong amount would fail a paying delegate's order, hand back their seats and
email them to say so.

**The fix.** The amount is still checked where it is cheap, but only *reported*
there. The destructive consequence now waits until checks 4 and 5 have passed,
so a forgery can be rejected without also being able to destroy a booking.
Genuine PayFast under-payments still fail the order exactly as before — both
halves are tested.

Two supporting changes, since the finding turns on the passphrase:

- **Admin → Settings → Diagnostics** now has a *PayFast passphrase set* row.
- The installer's completion page warns when merchant credentials are present
  but the passphrase is blank.

### 3. The guest-checkout setting bound the page, not the action — MEDIUM

**Fixed.**

With `allow_guest_checkout` off, `GET /checkout` correctly redirected to
`/login` — but `POST /checkout` created the order anyway. The setting only hid
the form.

Orders placed that way landed with `user_id NULL`: invisible to the delegate
under `/account/orders`, and an orphan on the booking chair's list with no
account behind it to chase. Reproduced deterministically, not intermittently.

**The fix.** `CheckoutController::place()` now enforces the same rule the page
does.

---

## What was tested and found sound

These are not assumptions. Each was probed and passed.

**Money.** Posting `price_cents`, `price`, `unit_price_cents`, `total_cents` and
`amount` at the add-to-cart route changes nothing — the catalogue price is used.
A negative quantity cannot make a line worth negative money. A discount larger
than the cart floors the total at zero rather than going negative.

**Coupons.** A coupon past its usage limit, expired, not yet started, or
deactivated is refused in all four cases. Checked against the database, not the
page: a cart page that simply never prints the code would make a broken refusal
look like a good one.

**Payments.** A correctly signed notification for R1.00 never marks a larger
order paid. Landing on `/payment/success` never marks an order paid. Replaying
the same notification byte for byte does not double-count the payment or
duplicate bookings or donations. The recorded payment equals the order total to
the cent.

**Ledger invariants, across the whole database.** No shuttle departure oversold.
No negative stock. No bed double-booked on a night. No refund exceeding what its
order was paid. Every paid order has a completed payment behind it. No order
total disagreeing with its line items.

**Role boundaries.** All 49 admin routes walked as all 7 roles — 343
combinations, every one correct. Nothing reached a route it had no permission
for, which is the direction that matters. The admin gate is two-factor: the
`is_admin` flag *and* a role. Exports are gated twice over, by the `exports`
permission and again by each dataset's own capability, so an accommodation admin
holding `exports` still cannot pull the finance pack.

**Injection.** No SQL is built from user input anywhere in `app/`,
`public_html/` or `tools/` — every query is a prepared statement, and the one
raw query in the installer is `$pdo->quote()`d. The unescaped `<?=` sites in the
views are all literals, integers, or ternaries over booleans; the only two HTML
passthroughs (`page-hero`'s `actions`) are hardcoded markup.

**Uploads.** The photograph manager never trusts the client filename — it
generates its own — validates by content via `getimagesize()` rather than
extension, re-encodes through GD (which kills polyglot files and strips EXIF,
so a committee member's phone photograph does not publish its GPS coordinates),
and derives the extension from the detected type. `public_html/uploads/.htaccess`
denies PHP and removes the handlers besides.

**Sessions.** Cookies are `HttpOnly` and `SameSite=Lax`, with `Secure` under
HTTPS. The session id is regenerated on login, so session fixation does not
apply. Responses carry `X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy` and `Permissions-Policy`.

---

## Noted, not defects

**Only the Super Admin can reach service applications and contact messages.**
The router asks for the `applications` and `messages` permissions, and no role
except `super_admin` holds either. Not a security hole — the opposite — but it
means those two jobs cannot be delegated. Worth a committee decision rather than
a code change: if the secretary should handle service applications, they need a
role that includes it.

**There is no Content-Security-Policy header.** With no XSS sink found, this is
defence in depth rather than a gap being exploited. Adding one would mean
auditing the inline styles the views use, which is a larger change than this
audit's scope.

**The venue photographs still need written permission from Boschendal.** Not a
security matter, but it remains outstanding and is recorded in every source
note.

---

## Running it

```bash
php tools/security-test.php http://127.0.0.1:8000
```

42 checks. It refuses to run against anything but a local address, because it
forges payments and places orders. It registers nine accounts, places real
orders and removes everything it made — and the last check counts users,
orders, roles and coupons to prove it, not just the convenient one.

It runs in CI on every push, alongside the smoke test (46), the race test (8)
and the audit (200). All 296 pass, and the database is byte-identical before and
after.
