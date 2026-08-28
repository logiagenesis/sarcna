-- 2026-08-29-add-finance-tables.sql
--
-- Financial reporting needs three things the schema could not express:
--
--   1. Refunds as amounts, not just an order status. An order marked "refunded"
--      cannot record a partial refund, when it was paid out, or why.
--   2. Expenditure. A convention treasurer reports a surplus or a deficit, and
--      income alone cannot produce either.
--   3. A budget to measure actuals against.
--
-- Safe to run on a live site: it only adds tables.

CREATE TABLE IF NOT EXISTS `refunds` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`          VARCHAR(24)  NOT NULL,
  `order_id`           INT UNSIGNED NOT NULL,
  `payment_id`         INT UNSIGNED     NULL,
  `amount_cents`       INT UNSIGNED NOT NULL,
  `reason`             VARCHAR(255)     NULL,
  `category`           ENUM('registration','accommodation','transport','merchandise','donation','mixed','other') NOT NULL DEFAULT 'mixed',
  `method`             VARCHAR(40)  NOT NULL DEFAULT 'payfast',
  `provider_reference` VARCHAR(80)      NULL,
  `status`             ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `refunded_on`        DATE             NULL,
  `created_by`         INT UNSIGNED     NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_refund_reference` (`reference`),
  KEY `idx_refund_order` (`order_id`),
  KEY `idx_refund_date` (`refunded_on`, `status`),
  CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `slug`       VARCHAR(140) NOT NULL,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_expense_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`     VARCHAR(24)  NOT NULL,
  `category_id`   INT UNSIGNED     NULL,
  `supplier`      VARCHAR(180)     NULL,
  `description`   VARCHAR(255) NOT NULL,
  `amount_cents`  INT UNSIGNED NOT NULL,
  `vat_cents`     INT UNSIGNED NOT NULL DEFAULT 0,
  `incurred_on`   DATE         NOT NULL,
  `due_on`        DATE             NULL,
  `paid_on`       DATE             NULL,
  `status`        ENUM('planned','committed','invoiced','paid','cancelled') NOT NULL DEFAULT 'committed',
  `payment_method` VARCHAR(60)     NULL,
  `invoice_number` VARCHAR(80)     NULL,
  `notes`         TEXT             NULL,
  `created_by`    INT UNSIGNED     NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_expense_reference` (`reference`),
  KEY `idx_expense_category` (`category_id`),
  KEY `idx_expense_date` (`incurred_on`, `status`),
  KEY `idx_expense_status` (`status`),
  CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_lines` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind`           ENUM('income','expense') NOT NULL DEFAULT 'expense',
  `category`       VARCHAR(120) NOT NULL,
  `description`    VARCHAR(255)     NULL,
  `budgeted_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `notes`          TEXT             NULL,
  `sort_order`     SMALLINT     NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_budget_kind` (`kind`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank reconciliation: tick each PayFast payout off against the bank statement.
CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `statement_date` DATE         NOT NULL,
  `description`    VARCHAR(255)     NULL,
  `amount_cents`   INT          NOT NULL,
  `matched_payment_ids` TEXT        NULL,
  `is_reconciled`  TINYINT(1)   NOT NULL DEFAULT 0,
  `notes`          TEXT             NULL,
  `created_by`     INT UNSIGNED     NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recon_date` (`statement_date`, `is_reconciled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
