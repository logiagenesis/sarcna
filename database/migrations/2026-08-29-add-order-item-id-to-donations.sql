-- 2026-08-29-add-order-item-id-to-donations.sql
--
-- An order can carry more than one donation. The donations page sells several
-- at once — a Seventh Tradition contribution, a sponsored newcomer
-- registration, a sponsored bed — and a delegate can put two of them in the
-- same cart and pay for them together.
--
-- Fulfilment recorded only the first. It asked "does this ORDER already have a
-- donation?" before writing each line, so the first insert made the answer yes
-- and every later donation in the same order was skipped. The money was taken
-- and counted as income, but the donations ledger, the donations screen, the
-- donations CSV and the public "raised so far" total never saw it.
--
-- The fix needs somewhere to record which line item a donation came from, so
-- the question becomes "does this LINE ITEM already have a donation?". The
-- unique index is what makes that safe under a repeated PayFast notification:
-- the same line item can never produce two donation rows.
--
-- Safe to run on a live site: it adds a nullable column and an index. Existing
-- rows keep NULL, which the unique index permits any number of.

ALTER TABLE `donations`
    ADD COLUMN `order_item_id` INT UNSIGNED NULL AFTER `order_id`,
    ADD UNIQUE KEY `uniq_donation_order_item` (`order_item_id`);
