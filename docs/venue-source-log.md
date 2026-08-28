# Venue source log

Every factual claim the site makes about the venue, the valley and the
logistics, where it came from, and whether the committee still has to verify it.

**Nothing marked "Not verified" should stay there when the site goes public.**
Where a claim turns out to be wrong, edit the page in
**Admin → Content → Pages**, or the relevant template, and update the row here.

---

## Where this information came from

The venue content was rebuilt from **published descriptions of the Boschendal
Retreat and the Boschendal estate**, gathered from the venue's own public
material and from established South African travel and venue directories.

It has **not** been checked against a signed venue contract, and the site was
built without access to one. Everything below is therefore "sourced but not
contractually confirmed" unless the committee has confirmed it.

> `boschendal.com` could not be reached from the build environment — the network
> egress proxy blocks it. Facts were gathered from search results and secondary
> sources; photographs could not be downloaded at all. See
> `docs/image-source-log.md`.

---

## Sourced from published venue material

These are consistent across the venue's own descriptions and independent
directories. Confirm them against the contract, but they are not invented.

| Claim | Where it appears |
|---|---|
| The venue is the **Boschendal Retreat**, a self-contained conference and cottage venue in a secluded corner of the Boschendal farm | Venue, Venue history, About, FAQ |
| **Eighteen self-catering cottages** | Venue, Accommodation, FAQ, About |
| **Two bedrooms per cottage**, each with **its own private exterior entrance** and **its own en-suite bathroom** with a shower over the bath | Accommodation, Venue, FAQ |
| **Sleeps 72 guests sharing** — which is 18 cottages × 2 bedrooms × 2 beds | Accommodation, Venue, FAQ |
| Cottages have a **living room with kitchen** and an **indoor fireplace** | Venue, FAQ, room descriptions |
| **Private patio** and **braai facilities** | Room descriptions |
| **WiFi and satellite TV** | Room descriptions, FAQ |
| An **auditorium** for the main sessions | Venue, Programme |
| A **private screening room** | Venue, Programme, FAQ |
| **Break-off boardrooms** for workshops | Venue, Programme |
| **Lounge and dining area** | Venue, Programme |
| A **communal boma / bonfire area** | Venue, Programme, FAQ |
| A **natural swimming pool** | Venue, Programme, FAQ |
| **Mountainside walking trails** | Venue, Programme, FAQ |
| **Scenic horse rides** on the farm | Venue, FAQ |
| **Fynbos gardens** | Venue, Programme |
| The estate is about **1 800 hectares** | Venue, Venue history, About |
| *Bos-en-dal* means **wood and valley** | Venue history |
| **About an hour's drive from Cape Town** | Venue, Venue history, FAQ |
| Between **Franschhoek and Stellenbosch**, in the **Dwars River valley** | Venue, Venue history |
| Between the **Simonsberg** and the **Groot Drakenstein** mountains | Venue, Venue history, FAQ |
| Vineyards run about **6 km along the mountain slopes** | Venue history |
| Title deeds dated **1685**; one of South Africa's oldest wine farms | Venue history, FAQ, About |
| First owner **Jean le Long**, a French Huguenot; land granted by the Dutch East India Company in **1688**; title deed written **1713** | Venue history |
| **Abraham de Villiers** acquired it in **1715**, sold to his brother **Jacques** in **1717** | Venue history |
| The **De Villiers family farmed it until 1879** and built the **Manor House** | Venue history |

Other spaces on the wider estate, mentioned in venue material but **not used by
our convention**: the Rhone Homestead (up to 50), the Olive Press barn (up to
350), the Werf picnic area and Rhone Rose Garden (250 each), and the Manor House
Lawn (250). If the committee ends up hiring one of these for the Saturday night
celebration, add it to the venue page and to this log.

---

## Still to verify with the venue or the committee

| Claim on the site | Where | How to verify |
|---|---|---|
| Convention runs **27–29 August 2027** | Everywhere | Committee minutes |
| **Thursday 26 August** is available for early arrival | Accommodation | Venue contract |
| **Rates** — R1 450 per bed per night at the Retreat, R2 600 for a private room, R950 at the partner guest house | Accommodation | Venue contract. These are placeholders |
| **32 standard + 4 accessible bedrooms** — i.e. which cottages are the step-free ones | Accommodation | Venue contract. The split is an assumption |
| Accessible rooms have **step-free access, a widened doorway, a roll-in shower and grab rails**, and are **closest to the auditorium** | Venue, Accommodation, FAQ | **Get this in writing.** Guests booking those rooms are relying on it |
| **Check-in from 16:00, checkout by 10:00** | Venue, Accommodation | Venue contract |
| **Linen, towels and heating included** | Accommodation | Venue contract |
| **Parking is free and at the cottages** | Venue | Venue contract |
| The **auditorium holds the whole convention** | Venue | Venue capacity documents — depends on final numbers |
| **Accessible parking bays next to the auditorium** | Venue | Site visit |
| The Retreat is **signposted from the main farm entrance** and away from the public tasting areas | Venue, FAQ | Site visit |
| **Partner guest house in Franschhoek**, 20 twin rooms, breakfast included, free shuttle | Accommodation | **Not contracted.** Entirely a placeholder |
| **August nights around 5–6 °C** | Venue history | South African Weather Service climate averages |
| **Shuttle routes, times, pick-up points, capacities and prices** | Transport | Transport supplier quotes. All placeholder |
| **Programme**, including which sessions run in which space | Programme | Committee |

---

## The wine estate question

The site states plainly that Boschendal is a historic wine farm, and equally
plainly that **no alcohol is served at any convention event and no tasting is
offered as a convention activity** — and that the Retreat is a self-contained
venue away from the estate's public tasting areas. That wording appears on the
About page, the Venue page, the Venue history page and the FAQ.

Two things for the committee before launch:

1. **Confirm the physical separation.** The Retreat is described as being in a
   secluded corner of the farm with its own facilities. Confirm on a site visit
   that attendees never need to pass through the tasting areas, and that someone
   can describe the layout to anyone who asks.
2. **Confirm the wording.** Some fellowships would rather a venue's wine history
   were not mentioned at all; others would rather be told. The current wording
   chooses honesty. Change it if the committee decides otherwise.

---

## The valley's history

The venue history page acknowledges that the farms of this valley were built on
the labour of enslaved people brought to the Cape, and that the descendants of
those communities still live there. This is historically accurate and, in a
South African recovery context, worth saying rather than skipping past.

Have someone on the committee read that section before launch and confirm they
are comfortable with both its accuracy and its tone.

---

## Before launch

- [ ] Check every "sourced" row above against the signed contract.
- [ ] Replace every placeholder rate with the contracted figure.
- [ ] Confirm the accessible-room specification **in writing**.
- [ ] Confirm the early-arrival night.
- [ ] Contract the overflow accommodation, or remove that room type.
- [ ] Confirm shuttle routes, times and vehicle capacities with the supplier.
- [ ] Walk the Venue and Venue history pages with someone who has been there.
- [ ] Obtain the venue media pack and written permission to use it, then run
      `php tools/import-venue-images.php` and log it in
      `docs/image-source-log.md`.
