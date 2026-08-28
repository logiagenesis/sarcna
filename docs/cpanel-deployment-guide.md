# cPanel deployment guide

How to put the SARCNA 2027 Convention website onto a normal cPanel shared
hosting account. No SSH, no command line, no Node.js.

Allow about 45 minutes for a first deployment.

---

## 1. What you need before you start

- A cPanel login for the hosting account.
- The domain pointing at that account (or a temporary subdomain to test on).
- A copy of this repository as a ZIP file. Build one with `php tools/package.php`,
  or download the repository ZIP from GitHub.
- PayFast merchant ID, merchant key and passphrase (sandbox first).
- A mailbox on the domain for sending email, e.g. `no-reply@sarcna.org.za`.

Check the account meets the requirements: **PHP 8.1 or newer**, with the
`pdo_mysql`, `mbstring`, `openssl`, `curl` and `gd` extensions. In cPanel this
is under **Software → Select PHP Version**.

---

## 2. Create the database

1. cPanel → **MySQL® Databases**.
2. Under *Create New Database*, enter `sarcna27` and press **Create Database**.
   cPanel prefixes it with your account name, giving something like
   `cpuser_sarcna27`. Write the full name down.
3. Under *MySQL Users → Add New User*, create a user (e.g. `sarcna`) with a
   long random password. Write the full username and password down.
4. Under *Add User To Database*, add the user to the database and grant
   **ALL PRIVILEGES**.

---

## 3. Upload the files

1. cPanel → **File Manager**.
2. Go to the account's home directory — the folder that *contains*
   `public_html`, not `public_html` itself.
3. **Upload** the ZIP, then **Extract** it there.
4. Move the extracted contents up one level if the ZIP created a wrapper
   folder, so the final layout is:

```
/home/cpuser/
    app/
    database/
    docs/
    storage/
    tools/
    public_html/          ← the domain's document root
    .env.example
    README.md
```

If the account already had files in `public_html`, back them up and remove
them first — `public_html` must end up containing this project's
`index.php`, `.htaccess` and `assets`.

> **Add-on domains and subdomains.** If the site is not on the primary domain,
> point the domain's document root at this project's `public_html` in
> cPanel → **Domains**, and keep `app`, `database` and `storage` in the folder
> above it.

### Permissions

In File Manager, select each folder, choose **Permissions**, and set:

| Path | Permissions |
|---|---|
| All folders | `755` |
| All files | `644` |
| `storage` and everything in it | `755` (writable) |
| `public_html/uploads` | `755` (writable) |

---

## 4. Run the installer

Visit `https://yourdomain/install`.

The page first shows a set of server checks. Anything red should be fixed
before continuing — usually a missing PHP extension or a folder that is not
writable.

Then fill in:

| Section | What to enter |
|---|---|
| **Database** | The full database name, user and password from step 2. Host is normally `localhost`. |
| **Website** | The full `https://` address, the public contact email, and the WhatsApp number in international format (e.g. `27821234567`). |
| **Administrator** | Your name, email and a strong password. This becomes the first super admin. |
| **PayFast** | Start in **sandbox**. Merchant ID, key and passphrase. |
| **Email** | Choose **SMTP** and use a cPanel mailbox — see `docs/smtp-setup.md`. |
| **Analytics** | GA4 measurement ID and the Search Console verification code, if you have them. Both can be added later. |

Leave **Load the demo content** ticked for the committee preview. It creates
the room types, 87 units, 262 beds, products, transport routes, programme, FAQs
and gallery.

Press **Install the website**. When it finishes it will have:

- created every table,
- seeded the settings, email templates and policy pages,
- loaded the demo content and generated the accommodation inventory,
- created your admin account,
- written `.env` in the application root,
- locked itself so it cannot be run again.

---

## 5. Straight after installing

1. Sign in at `https://yourdomain/login` and open
   **Admin → Settings → Diagnostics**. Everything should be green apart from
   the sandbox notice.
2. Send yourself a test email from that page.
3. Run one full sandbox checkout, including the PayFast notification — see
   `docs/payfast-setup.md` and `docs/testing-checklist.md`.
4. Check that `https://yourdomain/.env` returns **403 or 404**, never the file
   contents. If it shows the file, `.env` is in the wrong place: it belongs one
   level above `public_html`.

---

## 6. SSL

cPanel → **SSL/TLS Status** → **Run AutoSSL**. Let's Encrypt certificates are
issued and renewed automatically. The site forces HTTPS in `.htaccess` once a
certificate is present.

---

## 7. Going live

Work through `docs/testing-checklist.md` first. Then:

1. **Admin → Settings**: switch off *Show the committee preview banner*.
2. Replace placeholder imagery with licensed venue photography
   (`docs/image-source-log.md`).
3. Have the policy pages reviewed and update them in **Admin → Content → Pages**.
4. Confirm the dates, venue, prices and room inventory with the committee.
5. Edit `.env` and set `PAYFAST_MODE=live` with the live merchant credentials.
6. Delete the demo customer accounts in **Admin → Customers** (they are marked
   *Demo*).
7. Submit the sitemap in Search Console (`docs/search-console-setup.md`).
8. Take a full backup (`docs/backup-restore-guide.md`).

---

## 8. Updating the site later

The application has no build step, so an update is a file copy:

1. Take a backup first.
2. Upload the changed files over the old ones. **Do not overwrite `.env`,
   `public_html/uploads` or `app/Config/installed.lock`.**
3. If the release notes mention a schema change, follow
   `database/migrations.md`.
4. Reload the site and check **Admin → Settings → Diagnostics**.

---

## 9. Troubleshooting

| Symptom | Cause and fix |
|---|---|
| Blank white page | PHP error with display off. Open `storage/logs/php-*.log` or **Admin → Logs**. |
| "This application requires PHP 8.1" | Change the version in cPanel → Select PHP Version. |
| Redirected to `/install` on every page | `app/Config/installed.lock` is missing. Re-run the installer or restore the file. |
| "The installer has already been run and is locked" | Expected. Delete `app/Config/installed.lock` only if you really want to reinstall — it will not drop existing tables, but it will re-seed. |
| Styles missing, plain text page | `.htaccess` is not being read, or `mod_rewrite` is off. Ask the host to enable `AllowOverride All`. |
| 500 on every page | Usually a database credential problem. Check `.env` against cPanel → MySQL Databases. |
| Images upload but do not appear | `public_html/uploads` is not writable. Set it to `755`. |
| Orders stay "awaiting payment" | PayFast cannot reach the notify URL, or outbound HTTPS is blocked. See `docs/payfast-setup.md`. |
| Emails never arrive | Wrong SMTP details, or the mailbox needs SPF. See `docs/smtp-setup.md`. |
