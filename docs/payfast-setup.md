# PayFast setup

Everything about taking money on this site. Read the first section even if you
are only skimming — it explains why a booking sometimes takes a minute to
confirm, and why that is deliberate.

---

## How a payment actually completes

There are two separate things that happen when someone pays:

1. **The customer comes back to the site.** They land on
   `/payment/success`. This tells us nothing reliable — anyone can type that
   address — so **the site never marks an order as paid because of it.**
2. **PayFast posts a notification to our server.** This is the ITN (Instant
   Transaction Notification). It goes server-to-server to
   `/payment/notify` and it is the *only* thing that can mark an order paid.

Before an order is fulfilled, that notification has to pass four checks:

| Check | What it stops |
|---|---|
| Signature matches, including the passphrase | A forged notification |
| Source IP belongs to PayFast | A notification from anywhere else |
| Amount matches the order to the cent | Someone paying R1 for an R950 registration |
| PayFast confirms the payload when we post it back | A replayed or edited notification |

Only then does the site convert bed holds into bookings, write passenger
records, reduce stock, record donations and send the confirmation emails.

The practical consequence: a customer may briefly see *"we are confirming your
payment"*. That is correct behaviour, not a bug.

---

## Configuration

These live in `.env`, never in the database and never in Git:

```
PAYFAST_MODE=sandbox            # sandbox | live
PAYFAST_MERCHANT_ID=
PAYFAST_MERCHANT_KEY=
PAYFAST_PASSPHRASE=
PAYFAST_RETURN_URL=https://sarcna.org.za/payment/success
PAYFAST_CANCEL_URL=https://sarcna.org.za/payment/cancelled
PAYFAST_NOTIFY_URL=https://sarcna.org.za/payment/notify
```

The installer writes all three URLs for you from the site address.

---

## In the PayFast dashboard

1. Sign in at [payfast.co.za](https://www.payfast.co.za).
2. **Settings → Integration**: note the merchant ID and merchant key.
3. **Settings → Security**: set a **passphrase**. Use the same value in `.env`.
   Without it, signature validation cannot work properly.
4. **Settings → Notify URL**: leave blank. This site sends the notify URL with
   every payment, which is more reliable than a global setting.

---

## Testing in sandbox

Sandbox credentials, which are public and safe to use:

```
PAYFAST_MODE=sandbox
PAYFAST_MERCHANT_ID=10000100
PAYFAST_MERCHANT_KEY=46f0cd694581a
PAYFAST_PASSPHRASE=jt7NOE43FZPn
```

Then:

1. Add a registration, a bed and a shuttle seat to the cart.
2. Check out and complete the sandbox payment.
3. Watch **Admin → Payments → Notification log**. You should see
   `itn_received` followed by `order_paid`.
4. Check that the order is **Paid**, the bed appears on the **Bed board**, the
   passenger appears on the **manifest**, and the confirmation email arrived.

> **The notify URL must be reachable from the public internet.** PayFast cannot
> post to `localhost`, to an IP-restricted staging site, or to a site behind
> HTTP authentication. Test on the real domain.

---

## Going live

1. Set `PAYFAST_MODE=live` in `.env`.
2. Replace the merchant ID, key and passphrase with the live values.
3. Confirm the three URLs use the live domain and `https://`.
4. Make one real payment of a small amount and refund it from the PayFast
   dashboard.
5. Confirm the payout bank account with the committee treasurer.

---

## Reading the notification log

**Admin → Payments → Notification log** records every notification, accepted or
rejected.

| Event | Meaning |
|---|---|
| `redirect` | The customer was sent to PayFast |
| `itn_received` | A notification arrived |
| `itn_bad_signature` | The signature did not match — ignored. Usually a passphrase mismatch |
| `itn_bad_source` | It did not come from a PayFast server — ignored |
| `itn_amount_mismatch` | The amount did not match the order — order marked failed |
| `itn_not_confirmed` | PayFast would not confirm the payload, or we could not reach PayFast |
| `itn_duplicate` | A repeat notification for an order already paid — safely ignored |
| `order_paid` | The order was fulfilled |

---

## Troubleshooting

**Orders stay "awaiting payment" even though the customer paid.**
Look for `itn_received` in the log.
*Nothing at all* → PayFast cannot reach the notify URL. Check that
`https://yourdomain/payment/notify` returns a short text message in a browser.
*`itn_bad_signature`* → the passphrase in `.env` does not match the one in the
PayFast dashboard.
*`itn_not_confirmed`* → the host is blocking outbound HTTPS. Ask the host to
allow outbound connections to `www.payfast.co.za`, then check
**Admin → Settings → Diagnostics → PayFast reachable**.

**`itn_bad_source` on a payment you know is genuine.**
Some hosts cannot resolve PayFast's DNS. As a temporary measure only, switch on
*Skip the PayFast source-IP check* in **Admin → Settings → Payments**. The
signature and amount checks stay on. Ask the host to fix DNS and switch it back.

**A customer was charged but has no booking.**
Open the order in the admin. If the log shows `accommodation_conflict`, the bed
was taken while the payment was in flight — allocate another bed manually from
the **Bed board**, or refund. The committee is emailed automatically when this
happens.

**Refunds.** Refund in the PayFast dashboard, then set the order to
**Refunded** in the admin. That releases the beds and seats back to inventory.
