# Deployment Handoff — SARCNA 2027

Every field below is filled in from the repository itself. Nothing here is
"unknown" and nothing here is inferred: each answer names the file or the
command that establishes it.

Written 29 August 2026. Corrected after PR #1 was merged; current as of `f70d1fc`.

> **For the step-by-step deployment procedure, read
> [`DEPLOYMENT-HANDBOOK.md`](DEPLOYMENT-HANDBOOK.md).** It was produced by
> rebuilding a replica of the target host (PHP 8.4.25, MariaDB 10.11.14, real
> Apache with `.htaccess` active) and testing against it, including things this
> environment could not test — notably that the `.htaccess` files genuinely
> return 403 under a real web server, which the PHP dev server cannot prove.
> This file is the reference sheet; that one is the procedure.

---

## The one thing to understand first

**This is not a Node, Python, Docker, or framework project.** There is no
`package.json`, no `package-lock.json`, no `pnpm-lock.yaml`, no `yarn.lock`,
no `Dockerfile`, no `docker-compose.yml`, no `requirements.txt`, no
`pyproject.toml`, no `composer.json`, no `tsconfig.json`, no `vercel.json`,
no `netlify.toml`. Verify that in one command:

```powershell
cd $HOME\sarcna-handoff\sarcna
git ls-files | Select-String -Pattern "package\.json|Dockerfile|composer\.json|requirements\.txt"
```

That returns nothing. It is a plain PHP 8.1+ application that runs on ordinary
cPanel shared hosting: you upload the files, create a MySQL database, and open
`/install` in a browser. There is nothing to compile and nothing to install.

**A generic audit script that looks for `npm ci`, `pip install` or
`docker build` will report "no package.json found" and conclude nothing.** That
is not a finding about the project's health — it is a finding about the script.
Use `tools/run-audit.ps1` in this repository instead; it audits what is
actually here.

---

## The fields

| Field | Value | How it is established |
|---|---|---|
| **Source-of-truth branch** | Either — they are identical | PR #1 was merged on 29 August 2026. `main` and `claude/google-drive-folder-ruuvgu` now have **byte-identical trees**. The repository's *default* branch is still `claude/google-drive-folder-ruuvgu`, so a plain `git clone` gets the right code either way. See *Branches* |
| **Source commit** | `f70d1fc` on `main`, `b534231` on the default branch | Identical trees; see *Branches* |
| **Deployment target** | cPanel shared hosting, `cp71.domains.co.za`, LiteSpeed, PHP 8.4.24 | `docs/cpanel-deployment-guide.md` §"The verified environment" |
| **Expected production URL** | `https://2027.sarcna.org.za` | `docs/cpanel-deployment-guide.md` Step 2. **Not yet live** — `sarcna.org.za` still points at Firebase (the 2026 site) and must be left alone |
| **Install command** | *None.* Open `https://<domain>/install` in a browser and complete the form | `app/Controllers/InstallController.php`; `docs/cpanel-deployment-guide.md` Step 8 |
| **Build command** | *None.* There is no build step | No build tooling exists — see the check above |
| **Start command** | *None.* Apache/LiteSpeed serves `public_html/index.php` | `public_html/index.php`, `public_html/.htaccess` |
| **Local dev server** | `php -S 127.0.0.1:8000 -t public_html tools/dev-router.php` | `README.md` §Local development |
| **Output directory** | *None.* The document root is `public_html/` | `docs/cpanel-deployment-guide.md` Step 2 |
| **Runtime — PHP** | 8.1 minimum; 8.2 in CI; 8.4.24 on the target host | `README.md`; `.github/workflows/tests.yml`; deployment guide |
| **Runtime — database** | MySQL 5.7+ / MariaDB 10.3+ | `README.md`; CI uses MariaDB 11 |
| **Runtime — Node** | Not required, and not used in production | No `package.json` |
| **PHP extensions** | `pdo_mysql`, `gd`, `mbstring`, `curl`, `json` | `.github/workflows/tests.yml`; checked at install time by `public_html/preflight.php` |
| **Dependencies** | None. No Composer, no npm, no CDN | `README.md` §What this is built on |
| **Drive folder purpose** | Committee inputs: the build brief, the deployment post-mortem, and the venue photographs | Read directly — contents listed below |
| **Screenshots included** | No | — |

---

## Required environment variable NAMES

All 36, from `.env.example` — counted, not estimated:
`grep -cE '^[A-Z_]+=' .env.example` returns 36, with no duplicates. (An earlier
version of this file said 37. It was miscounted.) **Names only — never commit
values.** `.env` is
git-ignored and is refused over HTTP by three separate `.htaccess` rules.

```
APP_NAME  APP_ENV  APP_DEBUG  APP_URL  APP_KEY  APP_TIMEZONE
DB_HOST  DB_PORT  DB_NAME  DB_USER  DB_PASS  DB_CHARSET
MAIL_DRIVER  MAIL_HOST  MAIL_PORT  MAIL_USERNAME  MAIL_PASSWORD
MAIL_ENCRYPTION  MAIL_FROM_ADDRESS  MAIL_FROM_NAME
PAYFAST_MODE  PAYFAST_MERCHANT_ID  PAYFAST_MERCHANT_KEY  PAYFAST_PASSPHRASE
PAYFAST_RETURN_URL  PAYFAST_CANCEL_URL  PAYFAST_NOTIFY_URL  PAYFAST_VALIDATE_URL
SESSION_NAME  SESSION_LIFETIME  SESSION_SECURE
BOOKING_HOLD_MINUTES  CONTACT_EMAIL  WHATSAPP_NUMBER
GA_MEASUREMENT_ID  GOOGLE_SITE_VERIFICATION
```

Only six must be set by hand before `/install`: `APP_URL`, `DB_HOST`,
`DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. The installer writes the rest.

**If `PAYFAST_MERCHANT_ID` and `PAYFAST_MERCHANT_KEY` are left blank the site
installs and works, but cannot take a payment** — a delegate reaching the
payment step is returned to their basket with a message. The installer's
completion page says so explicitly.

---

## Branches

**PR #1 was merged into `main` on 29 August 2026 at 11:11 UTC.**

| Branch | Head | State |
|---|---|---|
| `main` | `f70d1fc` | **Current.** Contains everything |
| `claude/google-drive-folder-ruuvgu` | `b534231` | The repository's default branch. **Byte-identical tree to `main`** |
| `claude/comprehensive-audit-lzwl4z` | `7ecd1aa` | A third branch, not merged. Review before deploying anything from it |

Verified identical, not assumed:

```bash
git rev-parse origin/main^{tree}
git rev-parse origin/claude/google-drive-folder-ruuvgu^{tree}
# same hash
```

**Clone and change nothing:**

```bash
git clone https://github.com/logiagenesis/sarcna.git
```

The default branch is still `claude/google-drive-folder-ruuvgu`, so that is
what a plain clone gets — and since its tree is identical to `main`, that is
correct. If you would rather deploy `main`, `git checkout main` is equally
correct. There is no longer a wrong answer between those two.

### Two corrections, recorded rather than quietly fixed

**First:** an earlier version of this file said *"deploy from `main`"* while
`main` was seven commits behind, and I repeated that advice several times. It
was wrong at the time — deploying `main` then would have shipped a site with no
venue photographs, the broken `picture()` path bug, no CI, and the audit
clean-up defect that put fictional revenue in the finance screens.

**Second:** the fix for that then said *"`main` is behind — deploy the default
branch."* The merge has now made that stale too. Both statements were true when
written; neither is true now. This is what a branch fact looks like: it has a
date on it. The check above is the durable version — compare the trees.

---

## Deployability

**Determined: yes, with the two prerequisites below.** This is not an opinion;
it is what a clean machine does with this code.

GitHub Actions (`.github/workflows/tests.yml`) runs on every push. On an
Ubuntu runner that has never seen this project it:

1. checks every PHP file parses;
2. starts an empty MariaDB 11 database;
3. **installs the site through the real `/install` web form** — the same form a
   human uses, not a test-only shortcut (`tools/ci-install.php`);
4. confirms `/install` then returns HTTP 410, so it cannot be re-run;
5. runs the smoke test, the race test, and the full audit **twice**, comparing
   totals.

Latest run on `341eaba`: **all steps green.** That is a clean install and a
full pass on a machine with no prior state, which is the evidence a
deployability verdict needs.

**Two prerequisites before it can take real money and real bookings:**

| Prerequisite | State |
|---|---|
| Live PayFast merchant ID, key and passphrase | **Outstanding.** Without them checkout cannot complete. Nothing else is blocked |
| DNS for `2027.sarcna.org.za` | **Already done.** `DEPLOYMENT-HANDBOOK.md` §2.1 records the explicit A record resolving to `169.239.218.71` and reaching LiteSpeed, overriding the wildcard. Not verified from here — this environment cannot resolve external DNS — but verified there |
| The code being on the server | **Outstanding.** The document root is empty; `/` returns a plain LiteSpeed 404. This is now the only thing between the repository and a live site |

Neither outstanding item is a code defect.

---

## The one deployment mistake that has already been made once

The application needs a **two-level folder structure**: `app/`, `database/`,
`storage/`, `tools/`, `docs/` and `.env` must be the **parent** of the web
root, not its siblings.

Flatten it — put `app/` next to `index.php` — and the site returns **HTTP 500
and writes nothing to any log**. That is exactly what happened on the first
deployment attempt and it cost hours.

`public_html/preflight.php` detects it in one second, in a browser, before you
install. It is Step 7 of the deployment guide and it exists precisely because
the target host has no shell for running diagnostics.

---

## Auditing this project on Windows

```powershell
cd $HOME\sarcna-handoff\sarcna
powershell -ExecutionPolicy Bypass -File .\tools\run-audit.ps1
```

It records PHP and MySQL versions, the git state, a SHA-256 manifest of every
tracked file, and the presence or absence of every build-tool file a generic
auditor looks for — then, if a database is reachable, installs and runs the
whole suite. Everything goes to a timestamped log next to the repository.

It needs PHP 8.1+ on PATH. It does **not** need Node, Docker or Composer.

---

## What the Drive folder contains

Read directly on 29 August 2026, 07:32 UTC (09:32 SAST):

| Folder | Contents |
|---|---|
| `Instructions 2027/` | `GPT5.5Pro.txt` — the original build brief |
| `Update 2027/` | `Update.txt`, `Rebuild.txt`, `SARCNA-2027-DEPLOYMENT-POSTMORTEM.md` |
| `MD Build 2026/` | `SARCNA-site-technical-audit-2026-08-28.md` — audit of the old site |
| `Images/` | `boschendal-images.zip`, 48 images, and five scrape-metadata files |

**Drive-versus-repository alignment:** the Drive holds *inputs*, not a mirror of
the repository, so a file-by-file diff between them is not a meaningful check.
The one alignment that matters is the photographs, and it is verifiable: eight
of the 48 supplied images are in the repository at
`public_html/uploads/photos/`. The other 40 are logos, icons, duplicates, or
images excluded on the record — every exclusion is listed with its reason in
`tools/import-drive-images.php`.
