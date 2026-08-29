# Changelog

All notable changes to the SARCNA 2027 Convention website.
Versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed

**Card grids line up**
- `.grid--3` and `.grid--4` now lay out three and four across. They used
  `auto-fit` with a minimum track width, which on a 1180px container gave four
  and five across, so the class names did not describe what you got and the
  item counts chosen to suit them came out ragged — the venue's nine spaces as
  4 + 4 + 1, the seven merchandise cards as 5 + 2. They now fill 3 + 3 + 3 and
  4 + 3.
- Every card in a grid takes the height of the tallest card in that grid, on
  every row rather than row by row, so the rule, the price and the button land
  on one line right across a section.
- The availability chips are anchored to the bottom of the card with the foot,
  instead of floating wherever the copy above them happened to end.
- Headings, badge rows and chip rows are given a shared height per row, so the
  body copy in a card starts level with the copy beside it whether the heading
  above it ran to one line or two. This is the one part done in JavaScript;
  without it the layout is still correct, just a line out under a long heading.

## [1.0.0] — Committee preview build

The first complete build: a new website, database, installer, shop, bed-level
accommodation booking, transport booking, PayFast integration, admin CMS, SEO
setup and documentation. Nothing is carried over from the previous site.

### Added

**Foundation**
- Front controller, regex router, plain-PHP view engine with layouts and
  sections, PDO wrapper, session handling, CSRF protection, validator,
  dependency-free SMTP mailer, daily-rotating logger, middleware pipeline.
- PSR-4 autoloader — no Composer, nothing to install on the host.
- One-run web installer at `/install` that creates the schema, seeds content,
  generates the accommodation inventory, creates the first admin, writes `.env`
  and locks itself.

**Brand and design**
- Original logo package: badge, horizontal lockup, light and dark variants,
  favicon (SVG and ICO), Apple touch icon, social share card.
- Cape Winelands palette, self-hosted Lora and Work Sans, a full custom CSS
  design system with no framework dependency.
- Original illustrated imagery for the hero, venue, rooms, conference spaces,
  transport and merchandise, generated locally as JPEG + WebP.

**Public site**
- Home, About, Convention, Programme, Venue, Venue history, Gallery, FAQ,
  Accommodation (index and room detail), Shop (index, registration,
  merchandise, product detail), Transport (index and route detail), Donations,
  Service applications, Contact, Cart, Checkout, PayFast handoff, payment
  success and cancelled, and eight legal/policy pages.
- Accounts: register, sign in, email verification, password reset, dashboard,
  order history, accommodation and transport bookings, profile, printable
  invoice.

**Accommodation**
- Bed-level inventory: booking one bed in a shared room leaves the others on
  sale; private-unit buyout reserves every bed in that unit.
- 15-minute holds enforced by a unique database index, per-night pricing and
  availability, roommate requests, accessibility notes.

**Transport**
- Routes, departures and per-departure seat capacity; passenger details
  including flight number and luggage; passenger manifests.

**Shop and payments**
- Products with variants, stock control, sale pricing, coupons, custom-amount
  donations, and one cart mixing every item type.
- PayFast with signature generation, ITN handling behind four independent
  checks, payment records and a full notification log.

**Admin**
- Seven roles with capability-based access, dashboard, orders, payments,
  customers, products and stock, coupons, room types with unit and bed
  generation, a bed board, live holds, transport, content (banners, pages,
  programme, FAQs, events), gallery, service applications, donations, messages,
  a check-in desk, settings, email subjects, diagnostics, logs and twelve CSV
  exports.

**SEO and analytics**
- Unique titles, descriptions and canonicals per page, Open Graph and Twitter
  cards, Event / Organization / Breadcrumb / Product / FAQ structured data,
  database-driven sitemap and robots.txt, GA4 with ecommerce events, Search
  Console verification support.

**Documentation**
- Handover, deployment, PayFast, SMTP, analytics, Search Console, admin guide,
  backup and restore, SEO checklist, testing checklist, parity checklist, old
  site map, image and venue source logs, and migration notes.

### Notes
- All content is placeholder pending committee confirmation, flagged in the
  admin and behind a dismissible preview banner.
- Imagery is original illustration, not photography of the venue. Replace it
  with licensed venue photography before launch — see
  `docs/image-source-log.md`.
