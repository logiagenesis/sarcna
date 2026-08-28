# Backup and restore

The hosting account has roughly **25 GB**. This site uses well under 1 GB, but
backups pile up if nobody prunes them.

---

## What has to be backed up

| What | Where | How often |
|---|---|---|
| **Database** | MySQL | Daily once bookings open, weekly before that |
| **Uploads** | `public_html/uploads` | Weekly, and after any bulk upload |
| **`.env`** | Application root | Once, and after any change. **Keep it out of Git.** |
| **Code** | GitHub | On every change |

The database is the irreplaceable part: it holds every order, booking,
passenger and payment record.

---

## Backing up in cPanel

### Database

1. cPanel → **phpMyAdmin** → select the database.
2. **Export** → *Quick* → SQL → **Go**.
3. Save the `.sql` file somewhere off the server — the committee's Google Drive
   is fine, encrypted or access-controlled.

Or cPanel → **Backup** → **Download a MySQL Database Backup**.

### Files

cPanel → **Backup** → **Download a Home Directory Backup**. That includes
uploads and `.env`.

For a smaller regular backup, use File Manager to compress
`public_html/uploads` and download the archive.

### Automatic backups

If the host offers scheduled backups (JetBackup, R1Soft, "Backup Wizard"), turn
them on and set retention to about 30 days. Ask the host how far back their own
backups go, and write the answer down.

---

## Restoring

### Database

1. phpMyAdmin → select the database → **Import** → choose the `.sql` file → **Go**.
2. If it complains about existing tables, drop the tables first, or import into
   a fresh database and point `.env` at it.

### Files

Upload and extract the archive over the existing folders. Do not overwrite a
newer `.env` with an older one — check the database credentials first.

### After any restore

1. Load the site.
2. **Admin → Settings → Diagnostics** — everything should be green.
3. Check that recent orders are present and that the bed board looks right.

---

## Rebuilding from scratch

If the account is lost entirely:

1. Create a new hosting account and database.
2. Upload the repository from GitHub.
3. Restore the database from your latest `.sql` backup.
4. Recreate `.env` from `.env.example` with the new database details and the
   same PayFast and SMTP credentials.
5. Create `app/Config/installed.lock` — any file with any content works — so the
   installer does not run again over restored data.
6. Restore `public_html/uploads`.
7. Point DNS at the new account and run AutoSSL.

---

## Keeping inside 25 GB

- Uploaded images are converted to WebP on upload; the originals stay, so a
  large photo library is the main thing that grows.
- Logs rotate daily in `storage/logs`. Delete anything older than 90 days.
- `storage/email-queue` only fills up when SMTP is failing. If it is growing,
  fix the email settings.
- Do not keep more than a handful of full-account backups on the server itself.
  Download them and delete the copies on the host.

A quick monthly check: cPanel → **Disk Usage**. If the site is over 5 GB,
something unexpected is growing — usually uploads or old backups.
