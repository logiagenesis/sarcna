# Google Analytics 4 setup

## Adding the measurement ID

1. In [Google Analytics](https://analytics.google.com), create a property for
   the site and a **Web** data stream for `https://sarcna.org.za`.
2. Copy the **Measurement ID** — it looks like `G-XXXXXXXXXX`.
3. Paste it into **Admin → Settings → Analytics → GA4 measurement ID**.

That is all. The tag loads asynchronously with IP anonymisation on, and only
loads at all when an ID is set.

> **Reusing the old property.** The previous site used `G-51C28VRYTN`. Only
> reuse it if the committee wants continuity of historical data. A new property
> gives a clean baseline for a new site — decide deliberately and record which
> you chose.

---

## Events the site fires

Beyond automatic page views:

| Event | Fires when |
|---|---|
| `select_promotion` | The header *Register* button is clicked |
| `add_to_cart` | Registration, a bed, a shuttle seat, merchandise or a donation is added |
| `begin_checkout` | The cart's *Checkout* button is used |
| `add_payment_info` | The checkout form is submitted |
| `payfast_redirect` | The customer is handed to PayFast |
| `purchase` | The confirmed thank-you page loads, with `transaction_id`, `value` and `currency` |
| `whatsapp_click` | The floating WhatsApp button is used |
| `generate_lead` | The contact or service form is submitted |

`purchase` fires only on a **paid** order, so revenue in GA4 matches revenue in
the admin.

---

## Suggested conversions

Mark these as key events in GA4 → **Admin → Events**:

- `purchase`
- `begin_checkout`
- `generate_lead`

---

## Cookie notice

A small notice appears on the first visit, dismissible and remembered in the
browser. Turn it off in **Admin → Settings → Analytics** if the committee's
legal advice differs. The privacy policy explains what is set and why.

---

## Checking it works

1. Open the site in a private window.
2. GA4 → **Reports → Realtime**. You should appear within a few seconds.
3. Add something to the cart and check `add_to_cart` appears in the realtime
   event list.

If nothing appears, confirm the measurement ID is saved, and check for an ad
blocker in your browser.
