# Deploying to cPanel — SARCNA 2027

**Read this whole file before touching the server. It takes four minutes. The
first deployment attempt failed for several hours because this file was not
opened.**

---

## ⛔ The one fact that breaks everything

This application needs a **two-level folder structure**. The private folders
must be the **parent** of the web root, never its siblings:

```
sarcna2027/                 ← the application root (NOT the document root)
├── app/                    ← private
├── database/               ← private
├── docs/                   ← private
├── storage/                ← private
├── tools/                  ← private
├── .env                    ← private. Database password.
├── .htaccess               ← protects everything above. HIDDEN FILE.
└── public_html/            ← THIS is the document root. Only this is public.
    ├── index.php
    ├── .htaccess           ← HIDDEN FILE
    ├── preflight.php
    ├── assets/
    └── uploads/
```

`public_html/index.php` requires `../app/bootstrap.php`.

**If you flatten this — putting `app/` next to `index.php` — the site returns
HTTP 500 and writes nothing to any log.** That is precisely what happened on
the first attempt, and it cost hours. `preflight.php` (Step 7) detects it in
one second.

---

## The verified environment

Confirmed by direct observation on 29 August 2026, not assumed.

| Item | Value |
|---|---|
| Host | domains.co.za, Web Hosting Basic |
| Control panel | cPanel 136, Jupiter theme |
| Server | `cp71.domains.co.za` |
| Web server | **LiteSpeed** (reads `.htaccess`, like Apache) |
| PHP | **8.4.24** — the application requires 8.1+ ✓ |
| cPanel user | `sarcnaor`, home `/home/sarcnaor` |
| Disk quota | 25 GB |
| **SSH** | **Did not connect.** Ports 22, 2222 and 21098 all timed out from where this was tested. cPanel *does* show an SSH Access page with Manage SSH Keys, so the feature exists on the account — it may be firewalled, or need enabling with the host. Treat shell access as unavailable until somebody has actually opened a session. |
| **cPanel API** | **Unavailable.** No token feature; Basic Auth rejected. |
| **Document root outside `public_html`** | **Not permitted on this account.** cPanel silently relocates it. |

### What those constraints mean in practice

1. **You cannot run `php tools/audit.php` on this server.** There is no shell.
   Use `preflight.php` in the browser (Step 7), then run the full audit from
   any other machine that can reach the site (Step 11).
2. **The application root has to live inside `public_html`.** That is why the
   repository ships an `.htaccess` in its root and in every private folder.
   **Do not delete them — they are the only thing keeping `.env` off the web.**
3. **File Manager is your only file tool**, and its listing caches
   aggressively. **Click Reload before believing any directory is empty.**

### Do not disturb

- `sarcna.org.za` points to **Firebase** (`199.36.158.100`) — that is the live
  2026 site. Leave its A record alone.
- The account's own `public_html` holds a **separate WordPress install** plus
  `sarcnadev`, `sarcnadev26` and `test.sarcna.org.za`.
- **Two folders will be called `public_html`.** The account's, and this
  project's. Always use full paths. Confusing them nearly destroyed the live
  WordPress site.

---

## Step 0 — Before you start

- [ ] **Check disk usage is under quota.** cPanel home page, right-hand column.
      An over-quota account fails uploads *silently*.
- [ ] **Back up** the existing site and databases (cPanel → Backup).
- [ ] Confirm PHP is 8.1+ (cPanel → MultiPHP Manager).
- [ ] Note the server IP (cPanel → right sidebar → Shared IP Address).
- [ ] **Never paste a password into a chat window.** Use cPanel's generator and
      a password manager.

---

## Step 1 — Get the code up, with hidden files intact

**Windows Explorer's "Send to → Compressed folder" silently drops every file
whose name starts with a dot.** That deletes all eight `.htaccess` files and
`.env.example`. It caused the first 404 of the failed attempt. Use one of these
instead:

**Best — cPanel → Git™ Version Control**
Clone `https://github.com/logiagenesis/sarcna.git`. Preserves everything, and
updating later is one click.

**Or — the project's own packager**
On any machine with PHP: `php tools/package.php`. It writes a zip to `dist/`
containing exactly what belongs on a server and nothing that does not — no
`.git`, no `.env`, no demo uploads — **with all hidden files included**. Upload
that zip and extract it in File Manager.

**Or — 7-Zip**, with hidden files explicitly included. **Never Windows
Explorer.**

### Verify before continuing

In File Manager: **Settings → Show Hidden Files (dotfiles) → Save.** Leave it
on permanently. Then confirm these exist:

- [ ] `.env.example` in the application root
- [ ] `.htaccess` in the application root
- [ ] `public_html/.htaccess`

**If any are missing, the upload was incomplete. Redo it — do not carry on.**

---

## Step 2 — Create the subdomain with the right document root

cPanel → **Domains → Create A New Domain**

- Domain: `2027.sarcna.org.za`
- **Untick "Share document root"**
- Document root: `public_html/sarcna2027/public_html`

Giving the full target for clarity:

```
/home/sarcnaor/public_html/sarcna2027/public_html
```

**Verify:** the Domains list must show that exact path. cPanel sometimes
relocates it silently. **If it is wrong, fix it now.** A wrong document root
costs one minute to correct here and several hours to correct later.

---

## Step 3 — Put the files in the right place

Everything from the repository goes into
`/home/sarcnaor/public_html/sarcna2027/`, keeping the two-level structure
shown at the top of this file.

**Verify — this is the check that matters most:**

- [ ] `/home/sarcnaor/public_html/sarcna2027/app/` exists
- [ ] `/home/sarcnaor/public_html/sarcna2027/public_html/index.php` exists
- [ ] **`app/` and `index.php` are NOT in the same folder**

---

## Step 4 — Database

cPanel → **MySQL® Databases**

1. Create database: `sarcnaor_2027`
2. Create user: `sarcnaor_2027u` — use cPanel's **password generator**, and
   save it to a password manager
3. **Add user to database → tick ALL PRIVILEGES → Make Changes**

**Verify:** the "Current Databases" table lists the user under the database.
If it does not, the grant did not happen — do it again.

---

## Step 5 — `.env`

Copy `.env.example` to `.env` in the **application root** (not in
`public_html`), then fill in the values.

**Only edit keys that already exist in `.env.example`. Never invent key
names** — inventing them wasted a full diagnostic cycle on the first attempt.

At minimum: `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
The installer writes the rest.

---

## Step 6 — DNS

There is a wildcard record `*.sarcna.org.za` pointing at Firebase, so a new
subdomain resolves to Firebase unless an explicit record overrides it.

At domains.co.za, add:

| Host | TTL | Type | Value |
|---|---|---|---|
| `2027.sarcna.org.za` | 300 | A | *(your cPanel server IP)* |

**Leave the `sarcna.org.za` A record alone.** That is the live 2026 site.

**Verify** (PowerShell on your own PC):

```powershell
curl.exe -k -I https://2027.sarcna.org.za
```

The `Server:` header must say **LiteSpeed**. If it says anything else, or you
get a Firebase 404, DNS has not propagated yet or the record is wrong.

---

## Step 7 — Run the preflight ⭐

**In your browser:** `https://2027.sarcna.org.za/preflight.php`

This is the step that would have prevented the first failure. It checks, with
evidence rather than assumption:

- PHP version and every required extension
- **the folder layout** — including specifically detecting the flat layout
  that returns HTTP 500
- whether the hidden `.htaccess` files survived the upload
- whether `.env` exists and has the required keys filled in
- whether **the database actually connects** with those credentials
- whether `.env`, `app/`, `database/`, `tools/` and `storage/` **refuse HTTP
  requests** — asked over real HTTP against your live site, because "the
  `.htaccess` is there" is not the same claim as "the request is refused"
- whether `storage/` and `uploads/` are writable

Every failure tells you exactly what to do. **Do not proceed to Step 8 until
this page says "Ready."**

---

## Step 8 — Install

Open `https://2027.sarcna.org.za/install` and complete the form.

Expected:

```
✓ Database tables created (47 statements)
✓ Settings and email templates seeded
✓ Demo content loaded (22 statements)
✓ Accommodation inventory generated (56 units, 112 beds)
✓ Administrator account created
✓ Site settings saved
✓ .env written to the application root
✓ Installer locked (app/Config/installed.lock)
```

**Verify:** revisit `/install`. It must return **HTTP 410**. If it does not,
the installer did not lock itself and anyone could re-run it.

---

## Step 9 — SSL

cPanel → **SSL/TLS Status** → tick `2027.sarcna.org.za` → **Run AutoSSL**.

**Verify:** `curl.exe -I https://2027.sarcna.org.za` succeeds **without** `-k`.

---

## Step 10 — Delete the preflight

`preflight.php` reports server paths, so it must not stay on a live site.
There is a **Delete preflight.php now** button at the bottom of the page. Use
it, then confirm:

```powershell
curl.exe -I https://2027.sarcna.org.za/preflight.php
```

Must return **404**.

---

## Step 11 — Run the full audit

From **any machine with PHP** that can reach the site — your own PC is fine,
it does not have to be the server:

```powershell
php tools/audit.php https://2027.sarcna.org.za --password=YOUR_ADMIN_PASSWORD
```

**183 checks. All 183 must pass.** It exits non-zero on failure, so it can gate
a go-live decision. It creates test records and removes them again.

**Run it a second time.** The total must be identical. A different total means
it left something behind — and what it creates is a *paid order*, which would
sit in the treasurer's figures as revenue that never existed.

---

## Step 12 — Before you announce the URL

Work through the launch checklist in **`HANDOVER.md` §8**. The essentials:

- [ ] Import the venue photographs — `php tools/import-venue-images.php`
- [ ] Enter the real WhatsApp number, GA4 ID and Search Console token
- [ ] Configure SMTP, and send yourself one of every email
- [ ] Check **Admin → Settings → Diagnostics** — *PayFast reachable* must be green
- [ ] Purge the demo data — `php tools/seed-demo-orders.php --purge`
- [ ] Delete the two demo accounts
- [ ] Create a **second** super admin
- [ ] Take a backup and prove it restores

---

## Troubleshooting

These are the real symptoms from the failed attempt, with their real causes.

### HTTP 500, and nothing in any log

**Almost certainly the flat layout.** Run `preflight.php` — it names the
problem outright. To see the actual error, create `public_html/debug.php`
containing:

```php
<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
require __DIR__ . '/index.php';
```

Then request it, read the fatal, **and delete `debug.php` immediately.** Do not
leave it on a live server.

### HTTP 404 on `/install` when the file structure looks fine

`public_html/.htaccess` is missing, so LiteSpeed is looking for a real folder
called `install` instead of routing through `index.php`. Your upload dropped
hidden files. Re-upload properly (Step 1).

### A directory looks empty in File Manager but should not be

**Click Reload.** File Manager caches aggressively and repeatedly reported
populated folders as empty during the failed attempt. Never trust a single
observation.

### The subdomain shows the Firebase 404 page

DNS. The wildcard record is winning. Add the explicit A record (Step 6) and
allow up to 30 minutes.

### "Access denied for user" during install

The database user was created but not **added to the database with ALL
PRIVILEGES**. Redo Step 4. `preflight.php` catches this before you get here.

### Uploads or extraction fail with no clear message

Check the disk quota. An over-quota account fails silently.

### `.env` is readable in a browser

The application-root `.htaccess` is missing or the document root is pointing at
the application root instead of `public_html`. **Fix immediately** — that file
contains the database password. `preflight.php` tests this over real HTTP.

---

## The rule that matters more than any step here

**Do not guess.** If you have not checked, say so. Every command in this file
produces evidence — an HTTP status, a directory listing, a page that says
Ready. A claim without one of those is not a status.
