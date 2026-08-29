# Deployment Handoff — SARCNA 2027

Every field below is filled in from the repository itself. Nothing here is
"unknown" and nothing here is inferred: each answer names the file or the
command that establishes it.

Written 29 August 2026 against commit `341eaba`.

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
| **Source-of-truth branch** | `main` | The branch the repository is meant to deploy from. `claude/google-drive-folder-ruuvgu` is a working branch with an open pull request into `main` — see *Branches* below |
| **Source commit** | `341eaba9407f2a8e0e8e271df16e0361e329d40c` | Head of `claude/google-drive-folder-ruuvgu` |
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

All 37, from `.env.example`. **Names only — never commit values.** `.env` is
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

| Branch | Head | State |
|---|---|---|
| `main` | `0b5409f` | The intended deployment source |
| `claude/google-drive-folder-ruuvgu` | `341eaba` | Working branch. Open pull request **#1** into `main`, CI green, mergeable clean |
| `claude/comprehensive-audit-lzwl4z` | — | A second working branch. Not created by the work in this handoff; review before deploying anything from it |

**Do not deploy from a `claude/*` branch.** Merge PR #1 into `main` and deploy
`main`. The audit handoff that prompted this document correctly warned against
assuming the checked-out branch was the deployment source — it is not.

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

| Prerequisite | Why |
|---|---|
| Live PayFast merchant ID, key and passphrase | Without them checkout cannot complete. Nothing else is blocked |
| DNS: an explicit A record for `2027.sarcna.org.za` | A wildcard `*.sarcna.org.za` points at Firebase, so a new subdomain resolves there instead |

Neither is a code defect. Both are committee inputs, listed in `HANDOVER.md` §7.

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
