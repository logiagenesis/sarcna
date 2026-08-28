# Changing the database

There is no migration framework here on purpose: a framework you have to
install is a framework the next person has to learn, and this site has to be
maintainable from a cPanel File Manager.

Instead: **`schema.sql` is the truth**, and changes are applied as small,
dated SQL files that you run once.

---

## The rules

1. `database/schema.sql` always describes the current schema. Update it in the
   same commit as any change.
2. Every change also gets a file in `database/migrations/`, named
   `YYYY-MM-DD-short-description.sql`.
3. Migrations are **additive wherever possible** — add a column, add a table,
   add an index. Dropping a column on a live site loses data.
4. Every migration starts with a comment saying what it does and why.
5. **Take a backup before running one.** Every time.

---

## Applying a migration on cPanel

1. cPanel → **Backup** → download a MySQL backup.
2. cPanel → **phpMyAdmin** → select the database → **SQL**.
3. Paste the migration file's contents and press **Go**.
4. Load the site and check **Admin → Settings → Diagnostics**.

---

## Writing one

```sql
-- 2027-01-15-add-dietary-requirements-to-bookings.sql
--
-- The kitchen needs dietary requirements per guest, not per order, because a
-- cottage can hold four people with four different needs.

ALTER TABLE `bookings`
  ADD COLUMN `dietary_notes` VARCHAR(255) NULL AFTER `accessibility_needs`;
```

Then update `schema.sql` so a fresh install gets the same result.

---

## Things worth knowing about this schema

**Money is integer cents.** Never `FLOAT`, never `DECIMAL` in application code.
`money()` formats for display and `rands()` parses admin input.

**`bookings.active_night` is generated.**

```sql
`active_night` DATE GENERATED ALWAYS AS (
  CASE WHEN `status` IN ('confirmed','checked_in') THEN `night` ELSE NULL END
) STORED
```

with `UNIQUE KEY (bed_id, active_night)`. Because MySQL ignores `NULL` in unique
indexes, this enforces "one live booking per bed per night" while letting a
cancelled booking sit in the table as history. **Do not remove this index.** It
is the only thing standing between the site and double-booked beds.

**`booking_holds` has `UNIQUE (bed_id, night)`.** Holds are deleted when they
expire, so a plain unique key is enough. `AccommodationService::holdBed()`
catches the duplicate-key error and turns it into a friendly message.

**Foreign keys use `ON DELETE RESTRICT` where history matters** — you cannot
delete a bed that has bookings — and `CASCADE` where it does not, such as
product images.

**Indexes that exist for a reason:** `bookings(night, status)` and
`booking_holds(expires_at)` drive availability lookups; `orders(status,
created_at)` drives the admin lists; `products(type, is_active)` drives the
shop.

---

## Adding a room type or a night

Neither needs a migration.

- **Room types, units and beds**: Admin → Room types & beds. The generator
  creates units and their beds without touching existing inventory.
- **Bookable nights**: Admin → Settings → Shop → *Bookable nights*, a
  comma-separated list of `YYYY-MM-DD`. Add the night, then set its rates on
  each room type.

---

## Moving to another host

1. Export the database with phpMyAdmin.
2. Copy the files, including `public_html/uploads` and `.env`.
3. Import the database on the new host.
4. Update `DB_*` and `APP_URL` in `.env`.
5. Make sure `app/Config/installed.lock` exists so the installer does not run.
6. Update the PayFast return, cancel and notify URLs to the new domain, in
   `.env`.
7. Point DNS, run AutoSSL, and work through `docs/testing-checklist.md`.
