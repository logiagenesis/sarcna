# Functionality parity checklist

Every feature the old site had, and where it lives now. Tick the right-hand
column during acceptance testing.

Legend: **✔** built and tested · **●** built, needs committee content · **—** not
applicable

---

## Accounts

| Old feature | New location | Status | Tested |
|---|---|---|---|
| Register | `/register` | ✔ | ☐ |
| Log in | `/login` | ✔ | ☐ |
| Log out | Header and account nav | ✔ | ☐ |
| Email verification | `/verify-email` | ✔ | ☐ |
| Resend verification | Account dashboard | ✔ | ☐ |
| Forgot password | `/forgot-password` | ✔ | ☐ |
| Reset password | `/reset-password` | ✔ | ☐ |
| View profile | `/account/profile` | ✔ | ☐ |
| Edit profile | `/account/profile` | ✔ | ☐ |
| Change password | `/account/profile` | ✔ | ☐ |

## Shop and orders

| Old feature | New location | Status | Tested |
|---|---|---|---|
| Product catalogue | `/shop` | ✔ | ☐ |
| Product detail | `/shop/{slug}` | ✔ | ☐ |
| Product variants | Size and colour on merchandise | ✔ | ☐ |
| Stock control | Admin → Products & stock | ✔ | ☐ |
| Cart | `/cart` | ✔ | ☐ |
| Checkout | `/checkout` | ✔ | ☐ |
| Order history | `/account/orders` | ✔ | ☐ |
| Order detail | `/account/orders/{ref}` | ✔ | ☐ |
| Invoice | `/account/invoice/{ref}` | ✔ | ☐ |
| Coupons | Cart, Admin → Coupons | ✔ | ☐ |

## Payments

| Old feature | New location | Status | Tested |
|---|---|---|---|
| PayFast checkout | `/checkout/pay/{ref}` | ✔ | ☐ |
| PayFast ITN | `/payment/notify` | ✔ | ☐ |
| Payment verification | Four checks before fulfilment | ✔ | ☐ |
| Reconciliation | Admin → Payments | ✔ | ☐ |
| Failed and cancelled payments | `/payment/cancelled`, admin | ✔ | ☐ |
| Payment logs | Admin → Payments → Notification log | ✔ | ☐ |
| Donation payments | Same cart and checkout | ✔ | ☐ |

## Content

| Old feature | New location | Status | Tested |
|---|---|---|---|
| Home page banners | Admin → Content → Banners | ● | ☐ |
| Upcoming events | Admin → Content → Events | ● | ☐ |
| Homepage CMS content | Admin → Content | ● | ☐ |
| Cloud-hosted images | Local uploads and gallery | ✔ | ☐ |
| Admin / CMS | `/admin` | ✔ | ☐ |

## Forms

| Old feature | New location | Status | Tested |
|---|---|---|---|
| Service applications | `/service` | ✔ | ☐ |
| Admin review of applications | Admin → Service applications | ✔ | ☐ |
| Donations | `/donations` | ✔ | ☐ |
| Contact form | `/contact` | ✔ | ☐ |

## Analytics and SEO

| Old feature | New location | Status | Tested |
|---|---|---|---|
| Google Analytics | Admin → Settings → Analytics | ● | ☐ |
| Search Console | Admin → Settings → Analytics | ● | ☐ |
| Sitemap | `/sitemap.xml`, generated | ✔ | ☐ |
| robots.txt | `/robots.txt`, generated | ✔ | ☐ |
| Per-page metadata | Every page | ✔ | ☐ |

---

## New in this build

| Feature | Location | Tested |
|---|---|---|
| Bed-level accommodation booking | `/accommodation` | ☐ |
| One bed in a shared room leaves the others on sale | `/accommodation/{slug}` | ☐ |
| Private unit buyout | `/accommodation/{slug}` | ☐ |
| 15-minute bed holds | Cart | ☐ |
| Per-night rates and availability | Admin → Room types | ☐ |
| Roommate requests | Booking form and checkout | ☐ |
| Accessibility needs | Booking form and checkout | ☐ |
| Transport routes and departures | `/transport` | ☐ |
| Per-departure seat inventory | `/transport/{slug}` | ☐ |
| Passenger manifests | Admin → Transport | ☐ |
| Boarding check-in | Admin → Transport → Manifest | ☐ |
| Mixed cart | `/cart` | ☐ |
| Bed board | Admin → Bed board | ☐ |
| Live holds view | Admin → Bookings → Live holds | ☐ |
| Check-in desk | Admin → Check-in desk | ☐ |
| Check-in codes | Confirmation email and order | ☐ |
| Admin roles | Admin → Customers | ☐ |
| Admin audit log | Admin → Logs | ☐ |
| CSV exports (12) | Throughout the admin | ☐ |
| Rooming list for the venue | Admin → Bookings | ☐ |
| Programme management | Admin → Content → Programme | ☐ |
| FAQ management with structured data | Admin → Content → FAQs | ☐ |
| Policy pages editable in the admin | Admin → Content → Pages | ☐ |
| Gallery with alt text and source notes | Admin → Gallery | ☐ |
| WhatsApp floating button | Every public page | ☐ |
| Diagnostics | Admin → Settings → Diagnostics | ☐ |
| One-run installer | `/install` | ☐ |
