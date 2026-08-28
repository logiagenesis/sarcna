# Email (SMTP) setup

The site sends account, order, booking and admin notification emails. It has no
external email dependency: it talks SMTP directly, so a cPanel mailbox is all
you need.

---

## Create the mailbox

1. cPanel → **Email Accounts** → **Create**.
2. Address: `no-reply@sarcna.org.za`. Use a long random password.
3. Open **Connect Devices** on that mailbox and note the *Secure SSL/TLS
   Settings*. They are usually:

```
Outgoing server : mail.sarcna.org.za
Port            : 465 (SSL) or 587 (STARTTLS)
Username        : no-reply@sarcna.org.za   (the full address)
Password        : the mailbox password
```

---

## Configure the site

Either run the installer, or edit `.env`:

```
MAIL_DRIVER=smtp
MAIL_HOST=mail.sarcna.org.za
MAIL_PORT=587
MAIL_ENCRYPTION=tls          # tls for 587, ssl for 465
MAIL_USERNAME=no-reply@sarcna.org.za
MAIL_PASSWORD=your-mailbox-password
MAIL_FROM_ADDRESS=no-reply@sarcna.org.za
MAIL_FROM_NAME="SARCNA 2027 Convention"
```

Then **Admin → Settings → Diagnostics → Send a test email**.

### The other two drivers

| Driver | When to use it |
|---|---|
| `smtp` | Normal. Best deliverability. |
| `mail` | If SMTP is blocked by the host. Uses PHP `mail()`. More likely to land in spam. |
| `log` | Local testing only. Writes `.eml` files to `storage/email-queue` and sends nothing. |

If SMTP fails at send time the message is written to `storage/email-queue`
rather than lost, and the failure is logged.

---

## Deliverability

Convention emails carry booking details people need, so they must not go to
spam.

1. **SPF.** cPanel → **Email Deliverability** → **Repair**. This adds the SPF
   record for the server.
2. **DKIM.** The same screen offers DKIM. Turn it on.
3. **From address.** Always send from an address on your own domain. Never send
   as `@gmail.com`.
4. **Reply-to.** Contact form notifications set reply-to to the enquirer, so
   the committee can reply directly.
5. **Test it.** Send a test to a Gmail address and to an Outlook address, and
   check both inboxes and spam folders.

---

## Which emails the site sends

| To the customer | When |
|---|---|
| Confirm your email address | On registration |
| Welcome | On registration |
| Order received | When an order is created, before payment |
| Payment received | When PayFast confirms — includes bed, shuttle and check-in code |
| Payment failed | When a payment fails |
| Password reset | On request |
| Service application received | On submitting the service form |
| Contact form received | On submitting the contact form |
| Donation received | When a donation is paid |

| To the committee | When |
|---|---|
| New paid order | Every paid order |
| Failed payment | Every failed payment |
| Low stock | When an item drops to its threshold |
| Bed allocation failed | When a paid order could not be allocated a bed |
| New service application | Every application |
| Website enquiry | Every contact form submission |

The recipient is the **Order notification email** in
**Admin → Settings → Contact details**.

Subject lines are editable in **Admin → Settings → Email templates**. The
message bodies are branded templates in `app/Views/emails/`.
