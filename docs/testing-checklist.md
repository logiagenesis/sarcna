# Testing checklist

Work through this before the site goes public, and again after any significant
change. Test on a real phone as well as a desktop browser.

## Start with the automated check

```bash
php tools/smoke-test.php
```

It proves the invariants that matter most — that booking one bed leaves its
sibling on sale, that a hold cannot be taken twice, that the database refuses a
double booking, that cancelling frees a bed, and that a forged signature or an
under-payment is rejected. It creates its own test data and cleans up after
itself. **Run it on a staging copy, never on a live site with real bookings.**

If it fails, stop and fix that before working through anything below.

---

## 1. Installation

- [ ] `/install` shows the server checks and all of them pass.
- [ ] Installing with wrong database details shows a clear error, not a crash.
- [ ] A successful install creates the tables, seeds the demo data, generates
      the units and beds, creates the admin and writes `.env`.
- [ ] Visiting `/install` again is refused.
- [ ] `https://yourdomain/.env` returns 403 or 404, never file contents.
- [ ] `https://yourdomain/app/Config/config.php` is not reachable.
- [ ] `https://yourdomain/storage/logs/` is not reachable.

## 2. Public pages

Every one of these loads, looks right on mobile and desktop, and has a title
that describes it:

- [ ] Home · About · Convention · Programme · Venue · Venue history · Gallery · FAQ
- [ ] Accommodation index and each room type
- [ ] Shop, Registration, Merchandise, and a product page
- [ ] Transport index and a route page
- [ ] Donations · Service · Contact
- [ ] Privacy policy, terms, refund policy, code of conduct, and the other
      policy pages
- [ ] A made-up URL shows the styled 404 page

### Card grids

Check these on the convention, venue, shop, registration and home pages, at a
desktop width, at about a tablet width, and on a phone:

- [ ] Every card in a section is the same height as every other card in it,
      including the cards on the last row.
- [ ] The rule above the price, the price itself and the button or "View" link
      sit on one line across the whole section, not just across one row.
- [ ] Card body copy starts at the same height whether the heading above it
      ran to one line or two.
- [ ] The night chips on the room cards sit at the same height as each other.
- [ ] `--3` really is three across and `--4` really is four, on a wide screen.
- [ ] With JavaScript turned off, the cards are still all the same height and
      the prices still line up. Only the body copy may sit a line lower under
      a long heading.

## 3. Accommodation — the important one

- [ ] The room list shows free beds per night, and the numbers look right.
- [ ] Booking **one bed** for one night adds one line to the cart.
- [ ] **The other bed in that unit is still on sale.** Check the bed board.
- [ ] Booking a **whole unit** takes every bed in it for those nights.
- [ ] Two browsers cannot hold the same bed: the second gets a clear message.
- [ ] The hold timer appears in the cart and counts down.
- [ ] Letting a hold expire removes the line and frees the bed.
- [ ] Removing an accommodation line from the cart frees the bed immediately.
- [ ] A room type with no free beds shows "fully booked" and cannot be booked.
- [ ] Roommate requests and accessibility notes survive to the booking record.

## 4. Shop

- [ ] Adding registration, a day pass and merchandise all work.
- [ ] Merchandise requires a size and colour, and refuses without one.
- [ ] Sold-out variants cannot be selected.
- [ ] Quantity limits are enforced.
- [ ] Donations accept a custom amount and refuse an amount below the minimum.
- [ ] A coupon applies, shows the discount, and can be removed.
- [ ] An invalid or expired coupon is refused with a clear message.

## 5. Transport

- [ ] Routes and departures list with the correct seats remaining.
- [ ] Booking a seat captures the passenger, phone, email and flight number.
- [ ] Airport routes require a flight number.
- [ ] A full departure cannot be booked.
- [ ] Booking more seats than remain is refused with a clear message.

## 6. Cart and checkout

- [ ] The cart mixes registration, beds, shuttle seats, merchandise and a
      donation, and the total is right.
- [ ] Quantities update; beds cannot be multiplied.
- [ ] Checkout requires signing in (unless guest checkout is enabled).
- [ ] Attendee, guest and passenger detail fields appear for the right lines.
- [ ] The terms checkbox is required.
- [ ] Submitting creates a **pending** order and hands off to PayFast.
- [ ] Shuttle seats are reserved at that point, so the departure cannot be
      oversold while the customer is paying.

## 7. Payment — do this in sandbox first

- [ ] A completed sandbox payment marks the order **paid**.
- [ ] The confirmation email arrives with the check-in code, the bed allocation
      and the shuttle details.
- [ ] The bed appears on the bed board against the right unit and night.
- [ ] The passenger appears on the manifest.
- [ ] Merchandise stock has come down.
- [ ] The donation is recorded.
- [ ] The cart is empty afterwards.
- [ ] **Visiting `/payment/success` without paying does not mark anything paid.**
- [ ] Cancelling at PayFast returns to the cancelled page and releases the seats.
- [ ] `Admin → Payments → Notification log` shows `itn_received` then
      `order_paid`.
- [ ] A second notification for the same order is ignored (`itn_duplicate`).

## 8. Accounts

- [ ] Registering creates the account and sends the verification email.
- [ ] The verification link works and cannot be reused.
- [ ] Signing in with a wrong password fails, and repeated attempts are throttled.
- [ ] Password reset emails, works once, and the link then expires.
- [ ] The dashboard shows orders, bed bookings and shuttle bookings.
- [ ] The invoice page prints cleanly.
- [ ] One customer cannot open another customer's order.

## 9. Forms

- [ ] The contact form validates, saves, emails the committee and thanks the
      sender.
- [ ] The service form requires at least one service area and the consent box.
- [ ] Both forms are rate-limited on repeated submission.
- [ ] The honeypot silently absorbs a bot submission.

## 10. Admin

- [ ] A non-admin visiting `/admin` gets a 403, not the dashboard.
- [ ] Each role sees only its own sections. Test with a Check-in Volunteer.
- [ ] Orders can be searched and filtered, and a status change fulfils or
      releases correctly.
- [ ] Room types can be created and units and beds generated.
- [ ] A unit with confirmed bookings cannot be taken out of service.
- [ ] Nightly rates save and appear on the public page.
- [ ] The bed board matches reality.
- [ ] Products, variants and stock adjustments save.
- [ ] Transport routes, departures and manifests work; boarding check-in toggles.
- [ ] Content, gallery, programme, FAQs and events all save and appear publicly.
- [ ] Image upload works and produces a WebP twin.
- [ ] Every CSV export downloads and opens in a spreadsheet.
- [ ] The check-in desk finds an order by code, by surname and by email.
- [ ] Settings save; the diagnostics page is green; the test email arrives.
- [ ] Admin actions appear in the audit log.

## 11. Security

- [ ] Submitting a form with a stale session shows the expired-session page,
      not an error.
- [ ] `'; DROP TABLE users; --` in a search box does nothing but search for it.
- [ ] `<script>alert(1)</script>` in a name field is displayed as text.
- [ ] Uploading a `.php` file renamed to `.jpg` is refused.
- [ ] A file in `public_html/uploads` is served as a download, never executed.
- [ ] The session cookie is HttpOnly, SameSite and Secure over HTTPS.
- [ ] No token or password appears in `localStorage`.
- [ ] Six failed sign-ins lock the account for a period.

## 12. Performance and accessibility

- [ ] Lighthouse mobile 90+ and desktop 95+ on the home page.
- [ ] No horizontal scrolling at 320 px wide.
- [ ] Every page is usable by keyboard alone; focus is always visible.
- [ ] The skip link works.
- [ ] Zooming to 200% does not break the layout.
- [ ] Forms announce their errors next to the field.
- [ ] Images have meaningful alt text.
- [ ] Colour contrast passes on body text and buttons.

## 13. Email

- [ ] All customer emails arrive and render in Gmail and Outlook.
- [ ] All committee notifications arrive.
- [ ] Links in emails point at the live domain over HTTPS.
- [ ] Nothing lands in spam — check SPF and DKIM if it does.

## 14. Before the switch

- [ ] Preview banner off.
- [ ] Demo customer accounts deleted.
- [ ] Placeholder imagery replaced.
- [ ] Policy pages reviewed.
- [ ] Dates, venue, rates and inventory confirmed.
- [ ] PayFast live, with a real payment made and refunded.
- [ ] Backups taken and a restore tested.
- [ ] Sitemap submitted.
