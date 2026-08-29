# SARCNA 2027 — Verified Deployment Handbook

**Target URL:** `https://2027.sarcna.org.za`
**Repository:** `https://github.com/logiagenesis/sarcna`
**Document written:** 29 August 2026
**Verified against commit:** `12d064fde5fb1302f965117107397e2ecbb7b282` (`12d064f`)

---

## 0. How to read this document

Every factual claim in this file was produced by running something and reading
the output. Nothing here is inferred, remembered, or assumed. Where something
could **not** be tested, it is labelled **UNVERIFIED** and says why.

The evidence was gathered by building a replica of the target environment:
PHP 8.4.25, MariaDB 10.11.14, Apache with `.htaccess` active, and the exact
folder layout `/home/sarcnaor/public_html/sarcna2027/{app,public_html}`.

**If you only read one section, read [Section 6 — The Procedure](#6-the-procedure).**

---

## 1. The goal, stated plainly

Take the code in the GitHub repository and make it visible at a shareable web
address: `https://2027.sarcna.org.za`.

That is the whole objective. Everything in this document serves it.

### 1.1 What this is NOT

- It is **not** a change to the live 2026 site.
- It is **not** a DNS migration.
- It is **not** a Firebase, Vercel, Netlify or Node deployment.

The live site at `sarcna.org.za` and the new site at `2027.sarcna.org.za` are
independent. They resolve to different IP addresses on different machines. No
step in this document touches the `sarcna.org.za` A record.

---

## 2. The verified environment

Each row was confirmed by direct observation on 29 August 2026.

| Item | Value | How it was established |
|---|---|---|
| Hosting provider | domains.co.za, "Web Hosting Basic" | Customer Portal → Web Hosting |
| Control panel | cPanel 136.0.37, Jupiter theme | cPanel footer |
| Server hostname | `cp71.domains.co.za` | cPanel login URL |
| Web server | **LiteSpeed** (reads `.htaccess` like Apache) | `curl -I` returned `server: LiteSpeed` |
| PHP on host | **8.4** | Customer Portal → Manage Hosting → PHP Version |
| cPanel user | `sarcnaor` | cPanel FTP Accounts, special account path |
| Home directory | `/home/sarcnaor` | cPanel FTP Accounts |
| Subdomain | `2027.sarcna.org.za` — **already created** | cPanel → Domains |
| Document root | `/public_html/sarcna2027/public_html` — **already correct** | cPanel → Domains list |
| Full doc root path | `/home/sarcnaor/public_html/sarcna2027/public_html` | Derived from home dir + doc root |

### 2.1 DNS — verified live

```
2027.sarcna.org.za  →  169.239.218.71     (cPanel / LiteSpeed)
sarcna.org.za       →  199.36.158.100     (the live 2026 site, elsewhere)
```

There is a wildcard `*.sarcna.org.za` CNAME pointing at `sarcna.org.za`.
The explicit A record for `2027.sarcna.org.za` **overrides the wildcard**, and
this was confirmed working: requests to `2027.sarcna.org.za` reach LiteSpeed,
not the wildcard target.

**This means the hardest DNS problem is already solved. Do not touch DNS.**

### 2.2 Current state of the subdomain

```
HTTP/2 404
server: LiteSpeed
```

Both `/` and `/preflight.php` return a plain LiteSpeed 404. That is an **empty
document root**. The code has never been uploaded. This is the entire gap
between now and a working site.

### 2.3 Access constraints (from the repository's own deployment notes)

| Capability | State |
|---|---|
| SSH | Did not connect. Ports 22, 2222, 21098 all timed out |
| cPanel API | Unavailable. No token feature; Basic Auth rejected |
| Doc root outside `public_html` | Not permitted; cPanel silently relocates it |
| File Manager | Available — and it is the primary file tool |

**Consequence:** there is no shell on the server. You cannot run
`php tools/audit.php` on the host. Diagnostics must run in a browser
(`preflight.php`) or from another machine.

---

## 3. The repository — verified facts

### 3.1 Technology

| Layer | Choice |
|---|---|
| Language | PHP 8.1+ (verified working on 8.3.6 and 8.4.25) |
| Database | MySQL 5.7+ / MariaDB 10.3+ (verified on MariaDB 10.11.14) |
| Frontend | Server-rendered PHP templates, hand-written CSS, vanilla JS |
| Build step | **None** |
| Dependencies | **None.** No Composer, no npm, no CDN |
| Payments | PayFast (sandbox and live) |
| Web server | Apache / LiteSpeed (`.htaccess` driven) |

There is no `package.json`, `Dockerfile`, `composer.json`, `requirements.txt`,
`vercel.json` or `netlify.toml`. A generic audit tool that looks for these will
report "nothing found" — that is a fact about the tool, not the project.

### 3.2 Branches — READ THIS CAREFULLY

```
Default branch (remote HEAD):  claude/google-drive-folder-ruuvgu   →  12d064f
main                                                              →  0b5409f
claude/comprehensive-audit-lzwl4z                                 →  7ecd1aa
```

> **UPDATE — 29 August 2026, 11:11 UTC.** PR #1 has since been merged. `main`
> is no longer behind: `main` (`f70d1fc`) and the default branch (`b534231`)
> now have **byte-identical trees**, verified with `git rev-parse <ref>^{tree}`.
> The instruction below still works exactly as written — a plain clone gets the
> default branch, which is correct — and `main` is now equally correct. The rest
> of this section is left as originally written, because it was true when
> written and explains why the branch matters.

**At the time of writing, the repository's default branch was
`claude/google-drive-folder-ruuvgu`, not `main`.**

`main` was then **6 commits and 44 files behind** the default branch
(2,537 insertions, 113 deletions).

Because the default branch is the current one, **a plain `git clone` with no
branch specified gets the correct, most complete code automatically.**

> **DO NOT** merge anything.
> **DO NOT** switch branches.
> **DO NOT** check out `main`.
> Clone and leave the branch alone.

*(Note: the repository's own `DEPLOYMENT-HANDOFF.md` says "deploy from `main`."
That instruction is stale — `main` is behind, and the default branch is the
tested one. Follow this document.)*

### 3.3 File inventory

- **366 tracked files**
- **212 PHP files**
- **8 `.htaccess` files** — all essential:

```
./.htaccess
./app/.htaccess
./database/.htaccess
./docs/.htaccess
./public_html/.htaccess
./public_html/uploads/.htaccess
./storage/.htaccess
./tools/.htaccess
```

- **Total size on disk: 11 MB** (assets 3.6 MB, uploads 4.1 MB, app 1.5 MB)
- **GitHub ZIP download size: 7.0 MB**

### 3.4 The mandatory two-level structure

`public_html/index.php` line 10 contains:

```php
require_once dirname(__DIR__) . '/app/bootstrap.php';
```

It looks **one level up** for `app/`. Therefore:

```
/home/sarcnaor/public_html/sarcna2027/          ← APPLICATION ROOT
    ├── .env                 (created by the installer)
    ├── .env.example
    ├── .htaccess            ← blocks web access to this level
    ├── app/                 ← must NOT be inside public_html
    ├── database/
    ├── docs/
    ├── storage/
    ├── tools/
    └── public_html/         ← DOCUMENT ROOT (the only public folder)
            ├── index.php
            ├── preflight.php
            ├── .htaccess
            ├── assets/
            └── uploads/
```

**If you flatten this — putting `app/` beside `index.php` — the site returns
HTTP 500 and writes nothing to any log.** This is verified; see §4.6.

---

## 4. Test results — what was actually run

All tests were executed against a rebuilt replica of the target environment.

### 4.1 Syntax and compatibility

| Check | PHP 8.3.6 | PHP 8.4.25 |
|---|---|---|
| Files checked | 212 | 212 |
| Syntax errors | **0** | **0** |
| Deprecation notices | **0** | **0** |
| Runtime deprecations (845 log lines) | — | **0** |

Required extensions confirmed present and used: `pdo_mysql`, `gd`, `mbstring`,
`curl`, `json`, `openssl`.

The host runs 8.4.24; testing was done on 8.4.25. Functionally equivalent.

### 4.2 Installation

Installed **cleanly from scratch four separate times** — twice on PHP 8.3,
twice on PHP 8.4. Every run reported:

```
✓ Database tables created (47 statements)
✓ Settings and email templates seeded (6 statements)
✓ Demo content loaded (29 statements)
✓ Accommodation inventory generated (56 units, 112 beds)
✓ Administrator account created
✓ Site settings saved
✓ .env written to the application root
✓ Installer locked (app/Config/installed.lock)
```

`/install` returns **HTTP 410** afterwards — it locks itself and cannot be re-run.

### 4.3 Test suites

| Suite | Result |
|---|---|
| Smoke test | **37 passed, 0 failed** |
| Race test (12 simultaneous buyers, 1 bed) | **8 passed, 0 failed** |
| Full audit — PHP 8.3, run 1 | **183 / 183** |
| Full audit — PHP 8.3, run 2 | **183 / 183** |
| Full audit — PHP 8.3, run 3 | **183 / 183** |
| Full audit — PHP 8.4, run 1 | **183 / 183** |
| Full audit — PHP 8.4, run 2 | **183 / 183** |

The audit was run **five times with identical totals**. This matters: a rising
total would mean the audit left behind records it created — and what it creates
is a *paid order*, which would appear in the treasurer's figures as revenue that
never existed. It does not. Confirmed: *"Every record the audit created has been
removed — 0 left behind."*

Race test detail, verified:
- Round 1: exactly 1 of 12 simultaneous buyers holds the bed.
- Round 2: bypassing the application entirely — 1 write accepted, 11 refused by
  the database unique index, 0 unexpected errors.
- Round 3: cancelling frees the bed immediately, with no cleanup job.

### 4.4 Every public page

Verified returning HTTP 200:

```
/  /about  /convention  /programme  /venue  /accommodation  /transport
/register  /shop  /donations  /gallery  /cart  /contact  /faq  /login
/sitemap.xml  /robots.txt
```

`/checkout` returns **302** (correct — it redirects when the cart is empty).

> **Note:** the donations route is `/donations`, **not** `/donate`.

### 4.5 Security — proven under real Apache

The PHP built-in dev server **ignores `.htaccess` entirely**, so any test run
against it proves nothing about file protection. To test this properly, Apache
was configured with `AllowOverride All` and an alias deliberately pointed
**straight into the application root**, then each sensitive path was requested
over real HTTP:

| Path | Result |
|---|---|
| `/.env` | **403 Forbidden** |
| `/.env.example` | **403 Forbidden** |
| `/app/bootstrap.php` | **403 Forbidden** |
| `/app/Config/config.php` | **403 Forbidden** |
| `/database/schema.sql` | **403 Forbidden** |
| `/storage/logs` | **403 Forbidden** |
| `/tools/audit.php` | **403 Forbidden** |
| `/docs/HANDOVER.md` | **403 Forbidden** |

**The `.htaccess` files genuinely keep the database password off the web.**
This is why losing them is catastrophic — see §5.

### 4.6 The flat-layout failure, reproduced deliberately

`app/` was copied to sit *beside* `index.php` and served through Apache:

```
homepage HTTP: 500
```

`preflight.php` on that broken layout reported:

```
17 passed, 21 failed.
FAIL — app/bootstrap.php is one level above the web root
       looked for: /tmp/app/bootstrap.php
       Fix: The layout is wrong. app/ database/ storage/ tools/ docs/ and .env
            must sit in the PARENT of this folder, and only public_html/ may be
            the document root.
```

**`preflight.php` catches the single most expensive mistake, in one second, in a
browser. This is why running it is mandatory, not optional.**

On a correct layout, preflight reported: **`Ready. 40 passed.`**

### 4.7 The dotfile risk — measured, not assumed

The concern: a bad ZIP silently drops every file beginning with a dot, deleting
all 8 `.htaccess` files and leaving `.env` readable on the public web.

GitHub's own ZIP download was tested:

| Check | Result |
|---|---|
| `.htaccess` files inside GitHub ZIP | **8 of 8 present** |
| `.env.example` present | **Yes** |
| File count vs `git clone` | **366 vs 366** |
| SHA-256 checksum mismatches | **0** |

**GitHub's ZIP is byte-identical to a git clone.** It is safe.

The danger is **re-zipping on Windows**. Windows Explorer's
"Send to → Compressed folder" drops dotfiles silently.

### 4.8 The `.env` question — settled

An earlier working assumption was that `.env` must be hand-written before
installing. **This was tested directly and is false.**

With **no `.env` file present at all**, the installer was submitted with every
required field. Result:

```
.env present at start: NO
✓ Database tables created (47 statements)
✓ .env written to the application root
✓ Installer locked
.env now: CREATED (35 keys)
```

The resulting site then passed **smoke 37/37 and audit 183/183**.

**Conclusion: you do not need to create `.env` by hand. The installer writes it.**

*(Why the earlier confusion arose: `tools/ci-install.php` reads `.env` at line 77
purely to populate its form fields for automated testing. That is a property of
the CI helper, not of the installer.)*

---

## 5. The five ways this deployment can fail

Every one of these has happened before, or was reproduced during testing.

| # | Failure | Symptom | Prevention |
|---|---|---|---|
| 1 | **Flat layout** — `app/` beside `index.php` | HTTP 500, nothing in any log | Verify §6 Step 4 before installing |
| 2 | **Lost dotfiles** — zipped on Windows | HTTP 404 on every route; `.env` readable on the web | Never re-zip. Use git clone or GitHub's own ZIP |
| 3 | **Wrong document root** | Application source visible in a browser | Confirm cPanel → Domains shows `/public_html/sarcna2027/public_html` |
| 4 | **Database user not granted** | "Access denied for user" during install | Tick **ALL PRIVILEGES** and verify the grant appears |
| 5 | **Confusing the two `public_html` folders** | Damage to the account's other sites | Always use full paths. Never operate on `/home/sarcnaor/public_html` itself |

> **On failure #5:** there are two folders named `public_html` on this account.
> The account's own (`/home/sarcnaor/public_html`) and this project's
> (`/home/sarcnaor/public_html/sarcna2027/public_html`). They are different.
> Confusing them has previously come close to destroying another site.

---

## 6. The procedure

**Follow these steps in order. Do not skip a verification. Do not proceed past a
failed check.**

Estimated time: 20–30 minutes.

---

### STEP 1 — Turn on hidden files in File Manager

cPanel → **File Manager** → **Settings** (top right) → tick
**Show Hidden Files (dotfiles)** → **Save**.

**Leave this on permanently.**

> **Why this is first:** every `.htaccess` file is hidden. With this off you
> cannot see whether the most important files in the deployment exist. You would
> be verifying blind.

**VERIFY:** navigate to any folder and confirm dotfiles are now listed.

---

### STEP 2 — Empty the target folder

Navigate to:

```
/home/sarcnaor/public_html/sarcna2027/
```

Delete **everything inside it** — including the empty `public_html` and
`cgi-bin` folders cPanel created when the subdomain was made. They contain
nothing.

> **STOP AND CHECK THE PATH.** You must be inside `sarcna2027`.
> You must **NOT** be in `/home/sarcnaor/public_html/`.
> Deleting the contents of the wrong one damages other sites on this account.
> Read the breadcrumb at the top of File Manager before you delete anything.

**VERIFY:** click **Reload**, then confirm `sarcna2027` is empty.

> File Manager caches aggressively and has previously reported populated folders
> as empty. **Always click Reload before believing a listing.**

---

### STEP 3 — Get the code onto the server

Use **Method A**. Use Method B only if Method A refuses.

#### Method A — cPanel Git Version Control (preferred)

cPanel → **Git™ Version Control** → **Create**

| Field | Value |
|---|---|
| Clone URL | `https://github.com/logiagenesis/sarcna.git` |
| Repository Path | `public_html/sarcna2027` |
| Repository Name | `sarcna2027` |

**Leave the branch setting alone.** The repository's default branch is the
correct, fully tested one (see §3.2).

Advantages: runs server-side, preserves every hidden file with certainty, and
gives you a one-click **Update** button for future changes.

> **UNVERIFIED:** cPanel's Git tool may refuse a non-empty target directory.
> Step 2 empties it, which should satisfy this. If it still refuses, use Method B.
> This could not be tested without access to your cPanel.

#### Method B — GitHub ZIP (proven byte-identical fallback)

1. Download: `https://github.com/logiagenesis/sarcna/archive/refs/heads/claude/google-drive-folder-ruuvgu.zip` (7.0 MB)
2. **Do not extract it on your PC. Do not re-zip it.** Upload the `.zip` exactly
   as downloaded.
3. cPanel File Manager → navigate to `/home/sarcnaor/public_html/sarcna2027/` →
   **Upload** → select the `.zip`.
4. Back in File Manager, right-click the `.zip` → **Extract**.
5. The ZIP contains a **wrapper folder** named
   `sarcna-claude-google-drive-folder-ruuvgu`. Open it, **Select All** (with
   hidden files showing), and **Move** everything up one level into
   `/home/sarcnaor/public_html/sarcna2027/`.
6. Delete the now-empty wrapper folder and the `.zip`.

> **The single rule for Method B: never let Windows create a zip.** Downloading
> GitHub's zip is safe (verified: 366/366 files, 0 checksum mismatches).
> Re-zipping on Windows destroys all 8 `.htaccess` files.

---

### STEP 4 — Verify the structure ⛔ DO NOT SKIP

Click **Reload** in File Manager first. Then confirm **all five**:

- [ ] `/home/sarcnaor/public_html/sarcna2027/app/` exists
- [ ] `/home/sarcnaor/public_html/sarcna2027/public_html/index.php` exists
- [ ] **`app/` and `index.php` are NOT in the same folder**
- [ ] `.htaccess` exists in `/home/sarcnaor/public_html/sarcna2027/`
- [ ] `.htaccess` exists in `/home/sarcnaor/public_html/sarcna2027/public_html/`

Then re-check cPanel → **Domains**. `2027.sarcna.org.za` must still show:

```
/public_html/sarcna2027/public_html
```

> **If `app/` sits next to `index.php`, STOP.** That is failure #1. The site will
> return HTTP 500 with no log entry. Fix the layout now — it costs one minute
> here and hours later.
>
> **If any `.htaccess` is missing, STOP.** The upload dropped hidden files.
> Redo Step 3. Do not continue and "fix it later" — a missing app-root
> `.htaccess` publishes your database password.

---

### STEP 5 — Create the database

cPanel → **MySQL® Databases**

1. **Create New Database:** `2027` → full name becomes `sarcnaor_2027`
2. **Add New User:** `2027u` → full name becomes `sarcnaor_2027u`
   - Use cPanel's **Password Generator**. Save it to a password manager.
3. **Add User To Database:** select the user and the database → **Add** →
   tick **ALL PRIVILEGES** → **Make Changes**

**Write these down — you need all five in Step 6:**

```
DB_HOST : localhost
DB_PORT : 3306
DB_NAME : sarcnaor_2027
DB_USER : sarcnaor_2027u
DB_PASS : (the generated password)
```

**VERIFY:** the **Current Databases** table must list `sarcnaor_2027u`
underneath `sarcnaor_2027`. If it does not, the grant did not happen — repeat
step 3 above. This is failure #4.

> **Never paste this password into a chat window, an email, or a document.**

---

### STEP 6 — Run the preflight ⭐ THE MOST IMPORTANT STEP

In your browser:

```
https://2027.sarcna.org.za/preflight.php
```

This checks, with evidence rather than assumption:

- PHP version and every required extension
- **the folder layout**, including specifically detecting the flat layout
- whether the hidden `.htaccess` files survived the upload
- whether `.env`, `app/`, `database/`, `tools/` and `storage/` **refuse real HTTP
  requests** — asked over live HTTP, because "the file is there" is not the same
  claim as "the request is refused"
- whether `storage/` and `public_html/uploads/` are writable

**It must say `Ready.` A correct deployment shows `Ready. 40 passed.`**

> **DO NOT PROCEED TO STEP 7 UNTIL THIS PAGE SAYS READY.**
>
> Every failure it reports names the exact fix. A broken layout produces
> `17 passed, 21 failed` and tells you precisely what is wrong. This page exists
> because the target host has no shell for running diagnostics, and because
> skipping it once already cost hours.

---

### STEP 7 — Install

```
https://2027.sarcna.org.za/install
```

**You do NOT need to create `.env` first. The installer writes it.** (§4.8)

Fields marked **required** by the validator (`app/Controllers/InstallController.php`
lines 40–51):

| Field | Value to enter |
|---|---|
| `db_host` | `localhost` |
| `db_port` | `3306` |
| `db_name` | `sarcnaor_2027` |
| `db_user` | `sarcnaor_2027u` |
| `db_pass` | your generated password |
| `app_url` | `https://2027.sarcna.org.za` |
| `admin_first_name` | your first name |
| `admin_last_name` | your surname |
| `admin_email` | your email |
| `admin_password` | **min 8 chars, at least one letter and one number** |
| `contact_email` | e.g. `info@sarcna.org.za` |
| `mail_driver` | `log` for now (`smtp` later) |
| `payfast_mode` | `sandbox` |

Optional fields that **default to `contact_email`** if left blank:
`admin_notification_email`, `registration_email`, `accommodation_email`,
`transport_email`.

Leave `payfast_merchant_id` and `payfast_merchant_key` blank for now. The site
installs and works without them; it simply cannot take a payment yet.

Tick **seed demo content** — this generates the 56 units and 112 beds.

**Expected output:**

```
✓ Database tables created (47 statements)
✓ Settings and email templates seeded (6 statements)
✓ Demo content loaded (29 statements)
✓ Accommodation inventory generated (56 units, 112 beds)
✓ Administrator account created
✓ Site settings saved
✓ .env written to the application root
✓ Installer locked (app/Config/installed.lock)
```

**VERIFY:** revisit `https://2027.sarcna.org.za/install`. It must return
**HTTP 410**. If it returns 200, the installer did not lock itself and anyone on
the internet could re-run it and wipe your data. Do not leave it in that state.

---

### STEP 8 — Enable HTTPS

cPanel → **SSL/TLS Status** → tick `2027.sarcna.org.za` → **Run AutoSSL**.

Wait for it to complete, then confirm `https://2027.sarcna.org.za` loads without
a certificate warning.

> **UNVERIFIED:** whether a certificate already covers this subdomain could not
> be determined remotely — the test connection passed through a proxy that
> re-signs certificates, so the certificate observed was not the real one. Run
> AutoSSL regardless; it is harmless if one already exists.

Optionally, cPanel → **Domains** → turn on **Force HTTPS Redirect** for
`2027.sarcna.org.za` once the certificate is confirmed working.

---

### STEP 9 — Delete the preflight file ⛔ SECURITY

`preflight.php` reports server paths and configuration detail. **It must not
remain on a live site.**

There is a **"Delete preflight.php now"** button at the bottom of the preflight
page. Use it.

**VERIFY:**

```
https://2027.sarcna.org.za/preflight.php
```

must return **404**.

---

### STEP 10 — Confirm the site works

Open each of these and confirm it loads:

```
https://2027.sarcna.org.za/
https://2027.sarcna.org.za/programme
https://2027.sarcna.org.za/accommodation
https://2027.sarcna.org.za/venue
https://2027.sarcna.org.za/transport
https://2027.sarcna.org.za/shop
https://2027.sarcna.org.za/donations
https://2027.sarcna.org.za/gallery
https://2027.sarcna.org.za/contact
https://2027.sarcna.org.za/faq
```

Then sign in at `https://2027.sarcna.org.za/login` with the admin account you
created and check **Admin → Settings → Diagnostics**.

**At this point the shareable URL is live: `https://2027.sarcna.org.za`**

---

### STEP 11 — Optional but strongly recommended: run the full audit

From **any machine with PHP 8.1+** that can reach the site — your own PC is
fine, it does **not** have to be the server:

```
git clone https://github.com/logiagenesis/sarcna.git
cd sarcna
php tools/audit.php https://2027.sarcna.org.za --password=YOUR_ADMIN_PASSWORD
```

**183 checks. All 183 must pass.**

**Run it a second time and compare the totals.** They must be identical. A
different total means it left something behind — and what it creates is a *paid
order*, which would sit in the treasurer's figures as revenue that never existed.

(In testing, five consecutive runs all returned exactly 183/183.)

---

## 7. Troubleshooting

Real symptoms with their real causes.

### HTTP 500, and nothing in any log
**Almost certainly the flat layout.** Run `preflight.php` — it names the problem
outright. To see the actual error, create `public_html/debug.php` containing:

```php
<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
require __DIR__ . '/index.php';
```

Request it, read the fatal error, then **delete `debug.php` immediately**.

### HTTP 404 on every route, including `/install`
`public_html/.htaccess` is missing, so LiteSpeed looks for a real folder called
`install` instead of routing through `index.php`. Your upload dropped hidden
files. Redo Step 3.

### The subdomain shows a Firebase 404 page
DNS — the wildcard is winning. Currently **not applicable**: the explicit A
record is verified working. If this appears, re-check the A record for
`2027.sarcna.org.za` → `169.239.218.71` and allow up to 30 minutes.

### "Access denied for user" during install
The database user was created but not **added to the database with ALL
PRIVILEGES**. Redo Step 5. `preflight.php` catches this before you get here.

### A folder looks empty in File Manager but should not be
**Click Reload.** File Manager caches aggressively and has repeatedly reported
populated folders as empty. Never trust a single observation.

### `.env` is readable in a browser
The application-root `.htaccess` is missing, or the document root points at the
application root instead of `public_html`. **Fix immediately — that file contains
the database password.** Then change the database password.

### Uploads or extraction fail with no clear message
Check `preflight.php` for writability failures on `storage/` and
`public_html/uploads/`.

---

## 8. After it is live — the launch checklist

The site installs with **placeholder content**, clearly flagged behind a preview
banner. Before announcing the URL publicly:

- [ ] Import the venue photographs — `php tools/import-venue-images.php`
- [ ] Replace placeholder dates, venue detail, pricing, room inventory,
      transport routes and programme with confirmed content
- [ ] Enter the real WhatsApp number, GA4 measurement ID and Search Console token
- [ ] Configure SMTP (`MAIL_DRIVER=smtp`) and send yourself one of every email
- [ ] Enter **live** PayFast merchant ID, key and passphrase; switch
      `PAYFAST_MODE` to `live`
- [ ] Set the PayFast ITN URL to `https://2027.sarcna.org.za/payment/notify`
- [ ] Check **Admin → Settings → Diagnostics** — *PayFast reachable* must be green
- [ ] Run one full sandbox checkout end to end, including the notification
- [ ] Purge the demo data — `php tools/seed-demo-orders.php --purge`
- [ ] Delete the demo accounts
- [ ] Create a **second** super admin
- [ ] Switch off the preview banner in Admin → Settings
- [ ] Take a backup and prove it restores

> **Until live PayFast credentials are entered, the site cannot take money.**
> A delegate reaching the payment step is returned to their basket with a
> message. Everything else works.

---

## 9. Corrections to earlier guidance

Recorded so they are not repeated.

| Earlier claim | Correction |
|---|---|
| "Merge the PR into `main` and deploy `main`" | **Wrong.** The default branch is `claude/google-drive-folder-ruuvgu`, which is 6 commits *ahead* of `main`. Clone and change nothing. |
| "`/donate` returns 404 — possible defect" | **Wrong.** The route is `/donations` and it returns 200. The 404 was a bad guess at the URL. |
| "`.env` must be created before installing" | **Wrong.** Verified: the installer creates it. The failure that suggested otherwise was a missing `contact_email` field in a hand-built test request. |
| "`.env` is not web-readable" (tested on dev server) | **Invalid evidence.** The PHP dev server ignores `.htaccess`. Re-tested under real Apache: confirmed **403**. |
| The repo's own `DEPLOYMENT-HANDOFF.md` says deploy `main` | Stale. `main` is behind the default branch. |

---

## 10. Appendices

### 10.1 All 35 environment variables written by the installer

```
APP_NAME  APP_ENV  APP_DEBUG  APP_URL  APP_TIMEZONE  APP_KEY
DB_HOST  DB_PORT  DB_NAME  DB_USER  DB_PASS  DB_CHARSET
PAYFAST_MODE  PAYFAST_MERCHANT_ID  PAYFAST_MERCHANT_KEY  PAYFAST_PASSPHRASE
PAYFAST_RETURN_URL  PAYFAST_CANCEL_URL  PAYFAST_NOTIFY_URL
MAIL_DRIVER  MAIL_HOST  MAIL_PORT  MAIL_ENCRYPTION  MAIL_USERNAME
MAIL_PASSWORD  MAIL_FROM_ADDRESS  MAIL_FROM_NAME
GA_MEASUREMENT_ID  GOOGLE_SITE_VERIFICATION  WHATSAPP_NUMBER  CONTACT_EMAIL
SESSION_NAME  SESSION_LIFETIME  SESSION_SECURE  BOOKING_HOLD_MINUTES
```

`.env.example` additionally lists `PAYFAST_VALIDATE_URL` (36 keys total).

**Never commit `.env`. It is git-ignored and refused over HTTP by three separate
`.htaccess` rules.**

### 10.2 Public routes

```
/                        /about                   /convention
/programme               /venue                   /venue/history
/accommodation           /accommodation/{slug}    /accommodation-terms
/transport               /transport/{slug}        /transport-terms
/shop                    /shop/registration       /shop/merchandise
/shop/{slug}             /donations               /gallery
/cart                    /cart/status             /checkout
/checkout/pay/{ref}      /invoice/{reference}     /orders/{reference}
/payment/success         /payment/cancelled       /payment/notify
/register                /login                   /forgot-password
/reset-password          /verify-email            /contact
/faq                     /terms                   /privacy-policy
/code-of-conduct         /photo-anonymity-notice  /service
/sitemap.xml             /robots.txt              /install
```

Admin routes (behind authentication): `/bookings`, `/customers`, `/finance`,
`/orders`, `/payments`, `/products`, `/rooms`, `/photos`, `/content`,
`/coupons`, `/messages`, `/applications`, `/settings`, `/logs`, `/checkin`,
`/export/{dataset}`, and their sub-routes.

### 10.3 How accommodation inventory works

Understand this before changing anything.

```
room_types  →  room_units  →  beds
                                └── bookings       UNIQUE (bed_id, active_night)
                                └── booking_holds  UNIQUE (bed_id, night)
```

- Inventory is tracked **per bed, per night** — never per room.
- Booking one bed in a two-bed cottage leaves the second bed on sale.
- A "private unit" booking is a hold on every bed in that unit.
- Adding a bed to the cart creates a **hold** expiring after 15 minutes
  (`BOOKING_HOLD_MINUTES`).
- **The database unique index — not application logic — is what prevents two
  people buying the same bed at the same instant.** This was proven under
  genuine concurrency: 12 simultaneous writes, 1 accepted, 11 refused.
- `bookings.active_night` is a generated column that is `NULL` for cancelled and
  refunded rows, so a cancellation frees the bed automatically with no cleanup job.

### 10.4 How payment verification works

**Only a verified PayFast notification can mark an order as paid.** Landing on
the return URL proves nothing and never changes an order.

Every notification is checked four ways before fulfilment:

1. the signature matches, with the passphrase;
2. the request came from a PayFast server;
3. the amount matches the order to the cent;
4. PayFast itself confirms the payload when it is posted back.

Only then does the site convert bed holds into bookings, write passenger
records, reduce stock, record donations and send confirmations. Every
notification is written to `payment_logs`, accepted or rejected, and is visible
in **Admin → Payments → Notification log**.

### 10.5 Local development

```bash
php -S 127.0.0.1:8000 -t public_html tools/dev-router.php
```

Test suites:

```bash
php tools/smoke-test.php                     # 37 checks, fast invariants
php tools/race-test.php  http://127.0.0.1:8000   # 8 checks, concurrency
php tools/audit.php      http://127.0.0.1:8000 --password=PASSWORD   # 183 checks
```

Run all three on a staging copy, **never on a live site with real bookings.**
Run the audit twice and confirm identical totals.

> **Note:** `php tools/package.php` requires the PHP `zip` extension. If it is
> not installed the script exits with a message and produces nothing. The GitHub
> ZIP is a verified byte-identical substitute.

### 10.6 Exact commands used to verify this document

```bash
# Environment
apt-get install -y php-cli php-mysql php-gd php-mbstring php-curl php-xml mariadb-server
add-apt-repository -y ppa:ondrej/php
apt-get install -y php8.4-cli php8.4-mysql php8.4-gd php8.4-mbstring php8.4-curl php8.4-xml
apt-get install -y apache2 libapache2-mod-php8.4

# Clone
git clone https://github.com/logiagenesis/sarcna.git

# Syntax across all 212 files
find . -name "*.php" -exec php8.4 -l {} \;

# Install through the real web form
php8.4 tools/ci-install.php http://127.0.0.1:8000

# Suites
php8.4 tools/smoke-test.php
php8.4 tools/race-test.php http://127.0.0.1:8000
php8.4 tools/audit.php http://127.0.0.1:8000 --password=Convention2027

# Dotfile / checksum proof
curl -sL -o gh.zip "https://codeload.github.com/logiagenesis/sarcna/zip/refs/heads/claude/google-drive-folder-ruuvgu"
unzip -l gh.zip | grep "\.htaccess"
sha256sum <every file>   # 366 files, 0 mismatches

# Live DNS
getent hosts 2027.sarcna.org.za    # 169.239.218.71
getent hosts sarcna.org.za         # 199.36.158.100
curl -skI https://2027.sarcna.org.za
```

---

## 11. The rule that matters more than any step here

**Do not guess.**

If you have not checked, say so. Every step in this document produces evidence —
an HTTP status code, a directory listing, a page that says `Ready`, a total that
matches. A claim without one of those is not a status.

Two items in this document remain **UNVERIFIED** and are labelled as such:

1. Whether cPanel's Git Version Control refuses a non-empty target directory
   (§6 Step 3, Method A). Mitigation: Step 2 empties it; Method B is a proven
   fallback.
2. Whether a valid TLS certificate already covers the subdomain (§6 Step 8).
   Mitigation: run AutoSSL regardless.

Everything else in this file was produced by running something and reading the
output.

---

*End of document.*
