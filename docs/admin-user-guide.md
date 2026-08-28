# Admin guide

Written for committee members, not developers. You do not need to know anything
technical to use any of this.

Sign in at **https://sarcna.org.za/login** with the email and password you were
given, then open **Admin** in the top right.

---

## The dashboard

The first screen answers "how are we doing?".

- **Revenue** and **Registrations** — money taken and passes sold.
- **Bed-nights booked** — how full the accommodation is. "Held" means beds
  sitting in someone's cart right now; they free themselves after 15 minutes if
  the person does not pay.
- **Shuttle seats** — seats sold across every departure.
- **Pending payment** — people who started a checkout but have not paid.
- **Needs your attention** — new applications, unread messages, and any order
  the system could not fulfil on its own.

Anything red at the top of the page needs a person. The most common one is an
order that was paid but could not be given a bed, which happens if the bed was
taken while the payment was going through. Open the order and allocate another
bed, or arrange a refund.

---

## Who can see what

Every admin has one or more roles:

| Role | Can reach |
|---|---|
| **Super Admin** | Everything, including settings and other people's roles |
| **Finance Admin** | Orders, payments, donations, coupons, customers, exports |
| **Accommodation Admin** | Room types, beds, bookings, the bed board, exports |
| **Transport Admin** | Routes, departures, manifests, check-in, exports |
| **Merch Admin** | Products, stock, orders, exports |
| **Content Editor** | Banners, pages, programme, FAQs, events, gallery |
| **Check-in Volunteer** | The check-in desk only |

A super admin sets these in **Customers & admins → open a person → Admin roles**.
Give volunteers the narrowest role that lets them do their job — the check-in
desk role, for instance, cannot see anyone's payment details.

---

## Accommodation

### How the beds work

Beds are sold **one at a time**. A two-bed cottage is two separate things on
sale. If one person books one bed, the other bed stays available for someone
else. Someone who wants the whole cottage chooses "whole unit", which takes all
its beds.

### Room types & beds

Each room type has units (the actual cottages) and each unit has beds.

- **Adding capacity**: open the room type, then under *Units and beds* enter how
  many to add and press **Generate**. Existing units are untouched, so this is
  safe to do mid-sale.
- **Taking a unit out of service**: press *Take out* next to it. If it has
  confirmed bookings the system will refuse — move those guests first.
- **Nightly rates**: set a different price per night, and untick *On sale* for a
  night you are not selling. The Thursday early-arrival night is priced lower in
  the demo data.

### The bed board

**Accommodation → Bed board** shows every bed against every night in one grid:
green free, amber held in a cart, dark green booked, striped out of service.
This is the screen to have open in the week before the convention.

### Rooming list

**Bookings → Rooming list CSV** produces one row per unit per night with who is
in which bed. That is the file the venue wants.

---

## Transport

**Routes & departures** holds each route and its departure times. Each departure
has a seat capacity, and the site will not sell more seats than that.

- **Manifest** next to a departure lists the passengers, with phone numbers,
  flight numbers and luggage counts. Print it for the driver.
- On the day, press **Aboard** as each passenger boards.
- To add a vehicle, add another departure at the same time with its own capacity.

---

## Orders

**Orders** lists everything sold. Open one to see the items, the accommodation
and transport it produced, the payment history and the full event log.

- **Resend confirmation** re-sends the customer's email.
- **Change status** is for payments taken outside the website. Setting an order
  to **Paid** here does everything a real payment does: allocates beds, writes
  passengers, reduces stock and emails the customer. Use it deliberately.
- **Refunded** releases the beds and seats back to inventory. Do the actual
  refund in PayFast first.
- **Internal note** is only ever visible to admins.

---

## Products and stock

**Products & stock** covers registration, day passes, merchandise and donation
options.

- **Variants** are sizes and colours, each with its own stock count and optional
  price difference.
- **Adjust stock** records why stock changed, so you can see later where it went.
- A product that has been sold cannot be deleted — it is hidden instead, so old
  orders still make sense.
- Low stock is flagged on the dashboard and emailed to the committee.

---

## Content

**Content** has five tabs:

- **Banners** — the home page hero and the call-to-action band.
- **Pages & policies** — the privacy policy, terms, refund policy, code of
  conduct and the rest. These are draft copy: have them reviewed before launch.
- **Programme** — the weekend timetable. Tick ★ to highlight an item on the
  home page.
- **FAQs** — grouped by category. These also feed Google's FAQ results.
- **Events** — fundraisers and other events shown on the home page.

**Gallery** is for venue photography. Every image needs alt text, and there is a
source note field — use it to record who supplied each image and under what
permission.

---

## Service applications

Every application from the website lands here. Filter by service area, set a
status, add internal notes, and email the applicant directly from their page.
Export the whole list to CSV for the service co-ordinator.

---

## The check-in desk

**Check-in desk** is built for the registration table.

1. Type the check-in code from the customer's confirmation email — or their
   surname or email if they do not have it.
2. The screen shows what they bought, which bed they are in and which shuttles
   they are on.
3. Press **Check in**. Hand over the badge and room key.

Only paid orders can be checked in. A check-in can be undone if you make a
mistake.

---

## Settings

**Settings** holds everything the committee can change: the site name, contact
addresses, the WhatsApp number and message, analytics IDs, whether the shop,
accommodation, transport and donations are open, and how long a bed is held.

Passwords and payment keys are **not** here. They live in a server file called
`.env` that only someone with cPanel access can change — deliberately.

**Settings → Diagnostics** is the health check. Run it before launch and any
time something feels wrong. It also sends a test email.

---

## Exports

Everything can leave as a CSV, from the button on each screen or from
`/admin/export/...`:

orders · order items · attendees · bookings · **rooming list** · transport
passengers · donations · service applications · contact messages · customers ·
stock · payments

They open in Excel and Google Sheets, and are UTF-8 so South African names come
out correctly.

---

## Before the site goes public

- [ ] Switch off *Show the committee preview banner* in Settings.
- [ ] Replace the placeholder imagery with real venue photography.
- [ ] Have the policy pages reviewed and updated.
- [ ] Confirm dates, venue, prices and room inventory.
- [ ] Switch PayFast to live (a developer or cPanel user does this).
- [ ] Delete the two demo customer accounts, marked *Demo*.
- [ ] Run a real payment and refund it.
