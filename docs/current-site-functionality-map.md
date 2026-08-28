# The old site, and what replaces it

A map of what `sarcna.org.za` was running before this build, drawn from the
technical audit of 28 August 2026, and what each part becomes here.

**Nothing from the old site's code, layout, styling, imagery, copy or database
was reused.** The old site was examined only to understand what functionality
had to exist, and to collect the public contact addresses.

---

## What the old site was

Not one system — five vendors, five logins, five bills.

| Layer | Old | New |
|---|---|---|
| Frontend | React + Vite single-page app on **Firebase Hosting**, project `sarcna-staging` | Server-rendered PHP on the cPanel account |
| Backend | **NestJS** API on **Vercel** (`sarcna-backend.vercel.app`) | The same PHP application — no separate API |
| Database | **MongoDB Atlas**, in a database literally named `test` | MySQL on the same cPanel account |
| Images | **Cloudinary** (`dmemecjil`), plus some hot-linked from Unsplash | Local files under `public_html/assets` and `public_html/uploads` |
| Payments | PayFast, live mode | PayFast, with a documented sandbox-to-live path |
| Email | Configured, provider never identified | cPanel mailbox SMTP, configured in `.env` |
| Domain, DNS, mail | domains.co.za | Unchanged |
| Analytics | GA4 `G-51C28VRYTN` via Firebase | GA4, ID set in the admin |

Firebase was used **only** for hosting and analytics. There was no Firestore, no
Firebase Auth and no Realtime Database.

---

## Problems the audit found, and what this build does about them

| Problem | What this build does |
|---|---|
| **No source code could be located.** The frontend was minified and the API base URL compiled into the bundle. | Everything is plain, readable PHP in one repository. There is no build step, so what is on the server is what is in Git. |
| **Production data lived in a MongoDB database called `test`** — the Mongoose default, meaning the connection string was written without a database name. | One MySQL database, named deliberately at install time, with a documented schema. |
| **The live site ran out of a Firebase project called `sarcna-staging`.** Staging and production were the same thing. | One cPanel account. To stage, copy the folder to a subdomain and give it its own database. |
| **The API address was baked into the compiled frontend**, so moving the backend meant editing source, rebuilding and redeploying. | No separate API. Moving hosts is a file copy plus a database import. |
| **The API reported itself `degraded`** — heap at 93% — under serverless cold starts. | No serverless layer. A PHP request starts, does its work and exits. |
| **No server-side rendering.** Every page shared one title and one description, so Google and WhatsApp previews were identical everywhere. | Every page renders on the server with its own title, description, canonical, Open Graph tags and structured data. |
| **`og:url` pointed at `https://sarcna.org` — the wrong domain**, which the committee does not own. | Canonical and `og:url` are derived from the configured site address. |
| **The session token was kept in `localStorage`**, readable by any script on the page. | Server-side sessions with HttpOnly, SameSite cookies. No token ever reaches JavaScript. |
| **The admin interface was not in the public bundle** and nobody could say where it was. | The admin is at `/admin`, in this repository, with roles and an audit log. |
| **Some imagery was hot-linked from Unsplash's CDN** rather than owned. | Every image is local. `docs/image-source-log.md` records the provenance of each one. |

---

## Feature-by-feature

| Old API route | Old behaviour | New equivalent |
|---|---|---|
| `/api/auth/register` | Create account | `POST /register` |
| `/api/auth/login` | Bearer token in `localStorage` | `POST /login`, server-side session |
| `/api/auth/verify-email` | Email verification | `GET /verify-email?token=` |
| `/api/auth/forgot-password` | Reset request | `POST /forgot-password` |
| `/api/auth/reset-password` | Reset | `POST /reset-password` |
| `/api/auth/resend-verification` | Resend | `POST /verify-email/resend` |
| `/api/users/me` | Current user | `/account` |
| `/api/products` | Product catalogue | `/shop`, admin **Products & stock** |
| `/api/orders` | Orders | `/account/orders`, admin **Orders** |
| `/api/banners` | Home page banners | Admin **Content → Banners** |
| `/api/upcoming-events` | Events | Admin **Content → Events** |
| `/api/service-applications` | Service applications | `/service`, admin **Service applications** |
| `/api/donations` | Donations | `/donations`, admin **Donations** |
| `/api/payments/payfast/checkout` | Start a payment | `/checkout` → `/checkout/pay/{ref}` |
| `/api/payments/payfast/reconcile` | Reconcile | Handled by the ITN endpoint plus admin **Payments** |
| `/api/payments/payfast/donate` | Donation payment | Donations go through the same cart and checkout |
| `/api/health` | Health check | Admin **Settings → Diagnostics** |

---

## What is new, that the old site did not have

- Bed-level accommodation booking with holds, private-unit buyouts, roommate
  requests and accessibility notes.
- Transport booking with per-departure seat inventory, passenger manifests and
  boarding check-in.
- One cart mixing registration, beds, shuttle seats, merchandise and donations.
- A bed board and a check-in desk built for the weekend itself.
- Role-based admin access with an audit log.
- Twelve CSV exports, including the venue rooming list.
- Per-page SEO, structured data and a database-driven sitemap.
- Coupons, product variants and stock control.
- A one-run installer, so the site can be rebuilt on a new host in under an hour.

---

## Public email addresses

The brief asks that the public email addresses on the old site be reused. The
installer asks for a general contact address, an order-notification address and
optional registration, accommodation and transport addresses, all editable later
in **Admin → Settings → Contact details**.

> **Committee action.** Read the addresses off the current live site and enter
> them at install time. If they cannot be recovered, the installer's placeholder
> stands in — this is flagged in `docs/handover.md` as an open item.

---

## What must still be recovered from the old vendors

Even though nothing is reused, the committee should still take control of the
old accounts before shutting them down:

1. **MongoDB Atlas** — export the existing data. There may be real registrations
   or donations in it.
2. **Vercel** — the environment variable list, in case it holds a credential
   nobody else has.
3. **Firebase** — take ownership of `sarcna-staging`, then decommission it once
   DNS has moved.
4. **Cloudinary** — download the uploaded images; some may be usable venue
   photography.
5. **PayFast** — confirm who the payouts go to.
6. **The old admin interface** — whatever it was, get its URL and credentials,
   then retire it.
7. **Written confirmation of who pays for each account**, and on which card.
