# Image source log

Every image the site ships with, where it came from, and what has to happen
before launch. Keep this file up to date — it is the committee's record that it
has the right to use every image on the site.

---

## Status: all shipped imagery is placeholder

**None of the images in this build are photographs of Boschendal or of any real
venue.** They are original illustrations generated for this project by
`tools/generate-images.php` using PHP's GD library — layered mountain ridges, a
sunrise, vineyard rows in perspective, cypress treelines and cottage
silhouettes, in the convention's own palette.

They are used because:

- the committee does not yet hold licensed venue photography;
- nothing on this site may be hot-linked from another website;
- a stylised illustration cannot be mistaken for a photograph of the venue,
  which a stock photo of *some other* farm could be.

Venue-facing illustrations carry a small **ILLUSTRATION · PLACEHOLDER** mark in
the corner, and the site shows a preview banner explaining this. Both come off
once real photography arrives.

**Licence:** original work created for this project. No third-party rights.
Free to keep, edit or discard.

---

## Replacing the imagery

For each image below:

1. Obtain the photograph, with written permission from the venue or the
   photographer.
2. Resize it to roughly the dimensions given, and export a JPEG at quality 80–85.
3. Upload it through **Admin → Gallery**, or replace the file at the same path
   by FTP. Uploads through the admin generate a WebP copy automatically; if you
   replace a file by FTP, replace the `.webp` twin as well.
4. Update the row in this table with the source and the permission reference.
5. Remove the placeholder mark by regenerating (`php tools/generate-images.php`)
   only if you still need placeholders elsewhere.

---

## Backgrounds and heroes

| File | Size | Used on | Source | Replace with |
|---|---|---|---|---|
| `img/backgrounds/hero-winelands.jpg` | 1920×1080 | Home hero | Original illustration | A wide landscape of the estate at sunrise |
| `img/backgrounds/hero-mobile.jpg` | 900×1300 | Home hero, portrait | Original illustration | The same scene, portrait crop |
| `img/backgrounds/cta-sunset.jpg` | 1920×760 | Call-to-action band, registration hero | Original illustration | The estate at golden hour |
| `img/backgrounds/section-mist.jpg` | 1600×600 | Policy and programme page heroes | Original illustration | A soft, low-contrast landscape |
| `img/backgrounds/placeholder.jpg` | 800×600 | Fallback when a record has no image | Original illustration | Keep as a fallback |

## Venue

| File | Size | Used on | Source | Replace with |
|---|---|---|---|---|
| `img/venue/boschendal-overview.jpg` | 1600×1000 | Venue hero, home venue block | Original illustration | The estate seen from the approach |
| `img/venue/retreat-cottages.jpg` | 1200×800 | Venue, room type | Original illustration | The cottage cluster |
| `img/venue/conference-barn.jpg` | 1200×800 | Venue | Original illustration | The conference barn, exterior |
| `img/venue/gardens.jpg` | 1200×800 | Venue, gallery | Original illustration | The gardens |
| `img/venue/farm-kitchen.jpg` | 1200×800 | Venue, contact, FAQ | Original illustration | The dining terrace |
| `img/venue/mountain-walk.jpg` | 1200×800 | Venue | Original illustration | The walking route |
| `img/venue/fireside.jpg` | 1200×800 | Venue, Friday programme | Original illustration | The fire pit at dusk |
| `img/venue/arrival-drive.jpg` | 1200×800 | Venue history | Original illustration | The oak-lined drive |

## Conference spaces

| File | Size | Used on | Source | Replace with |
|---|---|---|---|---|
| `img/conference/main-hall.jpg` | 1200×800 | Convention hero | Original illustration | The barn set up for a meeting |
| `img/conference/workshop-room.jpg` | 1200×800 | Venue, events | Original illustration | A workshop room |
| `img/conference/fellowship-lawn.jpg` | 1200×800 | About, donations, service | Original illustration | The lawn during a break |

## Room types

| File | Size | Room type | Source | Replace with |
|---|---|---|---|---|
| `img/rooms/retreat-twin-cottage.jpg` | 1200×800 | Retreat Twin Cottage | Original illustration | The interior, both beds visible |
| `img/rooms/garden-quad-cottage.jpg` | 1200×800 | Garden Quad Cottage | Original illustration | The interior, four beds |
| `img/rooms/mountain-view-farmhouse.jpg` | 1200×800 | Mountain View Shared Farmhouse | Original illustration | The farmhouse and its view |
| `img/rooms/accessible-twin-cottage.jpg` | 1200×800 | Accessible Twin Cottage | Original illustration | **Show the step-free access and the bathroom** — guests booking this need to see it |
| `img/rooms/overflow-partner-lodge.jpg` | 1200×800 | Overflow Partner Lodge | Original illustration | The partner property, with its permission |

## Transport

| File | Size | Used on | Source | Replace with |
|---|---|---|---|---|
| `img/transport/airport-shuttle.jpg` | 1200×800 | Transport route pages | Original illustration | The actual shuttle vehicle |
| `img/transport/winelands-road.jpg` | 1200×800 | Transport index | Original illustration | The route into the valley |

## Merchandise

| File | Size | Product | Source | Replace with |
|---|---|---|---|---|
| `img/merch/t-shirt.jpg` | 900×900 | T-shirt | Original illustration | A photograph or supplier mock-up of the real garment |
| `img/merch/hoodie.jpg` | 900×900 | Hoodie | Original illustration | As above |
| `img/merch/cap.jpg` | 900×900 | Cap | Original illustration | As above |
| `img/merch/tote-bag.jpg` | 900×900 | Tote bag | Original illustration | As above |
| `img/merch/mug.jpg` | 900×900 | Mug | Original illustration | As above |
| `img/merch/stickers.jpg` | 900×900 | Sticker pack | Original illustration | As above |
| `img/merch/lanyard.jpg` | 900×900 | Lanyard | Original illustration | As above |

## Brand assets

These are **final**, not placeholders. They are original work created for this
convention.

| File | What it is |
|---|---|
| `brand/logo.svg` | Horizontal lockup for light backgrounds |
| `brand/logo-light.svg` | Horizontal lockup for dark backgrounds |
| `brand/logo-dark.svg` | Same as `logo.svg`, kept for naming clarity |
| `brand/badge.svg` | The circular badge on its own |
| `brand/favicon.svg` | Browser tab icon |
| `brand/favicon.ico` | 48×48 fallback for older browsers |
| `brand/apple-touch-icon.png` | 180×180 home-screen icon |
| `brand/social-share-card.jpg` | 1200×630 card for WhatsApp, Facebook and X |

The badge combines a Cape mountain silhouette, a sunrise, vineyard rows forming
a path, and a circle of fellowship. It deliberately does **not** use the NA
symbol or any protected mark.

---

## Fonts

| Font | Licence | Where it came from |
|---|---|---|
| Lora (Regular, Bold) | SIL Open Font License 1.1 | Self-hosted in `assets/fonts`, licence text alongside |
| Work Sans (Regular, Bold) | SIL Open Font License 1.1 | Self-hosted in `assets/fonts`, licence text alongside |

Both are OFL, which permits embedding and web use. They are self-hosted so the
site makes no third-party font requests — better for privacy, and one less
dependency.

The original brief suggested Fraunces, Cormorant Garamond or Playfair Display
for headings and Inter, Manrope or Montserrat for body. Lora and Work Sans are
the closest equivalents that could be self-hosted without a third-party
request. To switch, drop the new font files into `assets/fonts` and change the
four `@font-face` blocks and the two font variables at the top of
`assets/css/app.css`.

---

## Rules for any image added later

1. **Never hot-link.** Download it, optimise it, serve it from this server.
2. **Always record the source** in the gallery's *Source & permission note*
   field and in this file.
3. **Always write alt text.** The admin will not accept a gallery image
   without it.
4. **Respect anonymity.** No photograph of an identifiable attendee without
   their consent — see the Photo & Anonymity Notice.
5. **Optimise.** 1200px wide is enough for a card; 1920px for a hero. Keep JPEGs
   under about 200 KB.
