-- ============================================================================
--  SARCNA 2027 Convention — database schema
--  MySQL 5.7+ / MariaDB 10.3+   utf8mb4   InnoDB
--
--  All money is stored as INTEGER CENTS (ZAR). Never store money as float.
--  Run by the installer at /install, or import manually via phpMyAdmin.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------- accounts --

CREATE TABLE IF NOT EXISTS `users` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`        VARCHAR(80)  NOT NULL,
  `last_name`         VARCHAR(80)  NOT NULL,
  `email`             VARCHAR(190) NOT NULL,
  `phone`             VARCHAR(30)      NULL,
  `password_hash`     VARCHAR(255) NOT NULL,
  `home_group`        VARCHAR(120)     NULL,
  `region`            VARCHAR(120)     NULL,
  `dietary_notes`     VARCHAR(255)     NULL,
  `accessibility_notes` VARCHAR(255)   NULL,
  `is_admin`          TINYINT(1)   NOT NULL DEFAULT 0,
  `status`            ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME         NULL,
  `last_login_at`     DATETIME         NULL,
  `marketing_opt_in`  TINYINT(1)   NOT NULL DEFAULT 0,
  `is_mock`           TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_admin` (`is_admin`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin capability grants. A user may hold several roles.
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `role`       ENUM('super_admin','finance_admin','accommodation_admin','transport_admin','merch_admin','content_editor','checkin_volunteer') NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_role` (`user_id`, `role`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64)     NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME         NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reset_token` (`token_hash`),
  KEY `idx_reset_user` (`user_id`),
  CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64)     NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME         NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_verify_token` (`token_hash`),
  KEY `idx_verify_user` (`user_id`),
  CONSTRAINT `fk_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login / form throttling.
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bucket`     CHAR(64)     NOT NULL,
  `attempts`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bucket` (`bucket`),
  KEY `idx_rate_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------- shop --

CREATE TABLE IF NOT EXISTS `product_categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` TEXT             NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`       INT UNSIGNED     NULL,
  `type`              ENUM('registration','day_pass','merchandise','transport','donation','other') NOT NULL DEFAULT 'merchandise',
  `name`              VARCHAR(180) NOT NULL,
  `slug`              VARCHAR(200) NOT NULL,
  `sku`               VARCHAR(60)      NULL,
  `short_description` VARCHAR(255)     NULL,
  `description`       MEDIUMTEXT       NULL,
  `price_cents`       INT UNSIGNED NOT NULL DEFAULT 0,
  `sale_price_cents`  INT UNSIGNED     NULL,
  `sale_ends_at`      DATETIME         NULL,
  `allows_custom_amount` TINYINT(1) NOT NULL DEFAULT 0,
  `min_amount_cents`  INT UNSIGNED NOT NULL DEFAULT 0,
  `track_stock`       TINYINT(1)   NOT NULL DEFAULT 1,
  `stock`             INT          NOT NULL DEFAULT 0,
  `low_stock_threshold` INT        NOT NULL DEFAULT 5,
  `max_per_order`     SMALLINT     NOT NULL DEFAULT 10,
  `requires_attendee` TINYINT(1)   NOT NULL DEFAULT 0,
  `pickup_only`       TINYINT(1)   NOT NULL DEFAULT 1,
  `delivery_enabled`  TINYINT(1)   NOT NULL DEFAULT 0,
  `image`             VARCHAR(255)     NULL,
  `meta_title`        VARCHAR(180)     NULL,
  `meta_description`  VARCHAR(255)     NULL,
  `is_featured`       TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`           TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`        SMALLINT     NOT NULL DEFAULT 0,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_slug` (`slug`),
  KEY `idx_products_type` (`type`, `is_active`),
  KEY `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variants` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`        INT UNSIGNED NOT NULL,
  `size`              VARCHAR(40)      NULL,
  `colour`            VARCHAR(40)      NULL,
  `sku`               VARCHAR(60)      NULL,
  `price_delta_cents` INT          NOT NULL DEFAULT 0,
  `stock`             INT          NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`        SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_variant_product` (`product_id`, `is_active`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL,
  `alt_text`   VARCHAR(200)     NULL,
  `source_note` VARCHAR(255)    NULL,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_images_product` (`product_id`),
  CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED     NULL,
  `change`     INT          NOT NULL,
  `reason`     VARCHAR(120) NOT NULL,
  `order_id`   INT UNSIGNED     NULL,
  `user_id`    INT UNSIGNED     NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_movement_product` (`product_id`),
  KEY `idx_movement_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `code`              VARCHAR(40)  NOT NULL,
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description`       VARCHAR(190)     NULL,
  `discount_type`     ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value`    INT UNSIGNED NOT NULL DEFAULT 0,
  `min_subtotal_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `applies_to`        ENUM('all','registration','accommodation','merchandise','transport') NOT NULL DEFAULT 'all',
  `max_uses`          INT UNSIGNED     NULL,
  `used_count`        INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at`         DATETIME         NULL,
  `ends_at`           DATETIME         NULL,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ accommodation --

CREATE TABLE IF NOT EXISTS `room_types` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                  VARCHAR(140) NOT NULL,
  `slug`                  VARCHAR(160) NOT NULL,
  `summary`               VARCHAR(255)     NULL,
  `description`           MEDIUMTEXT       NULL,
  `beds_per_unit`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `bed_rate_cents`        INT UNSIGNED NOT NULL DEFAULT 0,
  `private_unit_rate_cents` INT UNSIGNED   NULL,
  `allows_private_buyout` TINYINT(1)   NOT NULL DEFAULT 1,
  `is_accessible`         TINYINT(1)   NOT NULL DEFAULT 0,
  `is_offsite`            TINYINT(1)   NOT NULL DEFAULT 0,
  `amenities`             TEXT             NULL,
  `hero_image`            VARCHAR(255)     NULL,
  `meta_title`            VARCHAR(180)     NULL,
  `meta_description`      VARCHAR(255)     NULL,
  `sort_order`            SMALLINT     NOT NULL DEFAULT 0,
  `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`               TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_type_slug` (`slug`),
  KEY `idx_room_type_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_type_images` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_type_id` INT UNSIGNED NOT NULL,
  `file_path`    VARCHAR(255) NOT NULL,
  `alt_text`     VARCHAR(200)     NULL,
  `source_note`  VARCHAR(255)     NULL,
  `sort_order`   SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_room_images` (`room_type_id`),
  CONSTRAINT `fk_room_images_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_units` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_type_id` INT UNSIGNED NOT NULL,
  `name`         VARCHAR(140) NOT NULL,
  `code`         VARCHAR(40)      NULL,
  `notes`        VARCHAR(255)     NULL,
  `sort_order`   SMALLINT     NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_units_type` (`room_type_id`, `is_active`),
  CONSTRAINT `fk_units_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory is tracked at BED level: booking one bed in a two-bed room must
-- leave the second bed on sale.
CREATE TABLE IF NOT EXISTS `beds` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_unit_id` INT UNSIGNED NOT NULL,
  `label`        VARCHAR(40)  NOT NULL,
  `sort_order`   SMALLINT     NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_beds_unit` (`room_unit_id`, `is_active`),
  CONSTRAINT `fk_beds_unit` FOREIGN KEY (`room_unit_id`) REFERENCES `room_units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-night pricing and availability overrides.
CREATE TABLE IF NOT EXISTS `bed_rates` (
  `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_type_id`            INT UNSIGNED NOT NULL,
  `night`                   DATE         NOT NULL,
  `bed_rate_cents`          INT UNSIGNED NOT NULL,
  `private_unit_rate_cents` INT UNSIGNED     NULL,
  `is_available`            TINYINT(1)   NOT NULL DEFAULT 1,
  `label`                   VARCHAR(80)      NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rate_night` (`room_type_id`, `night`),
  KEY `idx_rate_night` (`night`, `is_available`),
  CONSTRAINT `fk_rates_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A hold reserves a bed for one night while the cart is being paid for.
-- The unique key is what actually prevents two people buying the same bed.
CREATE TABLE IF NOT EXISTS `booking_holds` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_token`   CHAR(64)     NOT NULL,
  `user_id`      INT UNSIGNED     NULL,
  `bed_id`       INT UNSIGNED NOT NULL,
  `room_unit_id` INT UNSIGNED NOT NULL,
  `room_type_id` INT UNSIGNED NOT NULL,
  `night`        DATE         NOT NULL,
  `is_private_unit` TINYINT(1) NOT NULL DEFAULT 0,
  `price_cents`  INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at`   DATETIME     NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hold_bed_night` (`bed_id`, `night`),
  KEY `idx_hold_cart` (`cart_token`),
  KEY `idx_hold_expiry` (`expires_at`),
  CONSTRAINT `fk_holds_bed` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Confirmed bed-nights. `active_night` is NULL for cancelled rows, so the
-- unique index only constrains live bookings and a cancellation frees the bed.
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`        VARCHAR(24)  NOT NULL,
  `order_id`         INT UNSIGNED     NULL,
  `order_item_id`    INT UNSIGNED     NULL,
  `user_id`          INT UNSIGNED     NULL,
  `bed_id`           INT UNSIGNED NOT NULL,
  `room_unit_id`     INT UNSIGNED NOT NULL,
  `room_type_id`     INT UNSIGNED NOT NULL,
  `night`            DATE         NOT NULL,
  `is_private_unit`  TINYINT(1)   NOT NULL DEFAULT 0,
  `guest_name`       VARCHAR(160)     NULL,
  `guest_email`      VARCHAR(190)     NULL,
  `guest_phone`      VARCHAR(30)      NULL,
  `roommate_request` VARCHAR(190)     NULL,
  `accessibility_needs` VARCHAR(255)  NULL,
  `notes`            VARCHAR(500)     NULL,
  `price_cents`      INT UNSIGNED NOT NULL DEFAULT 0,
  `status`           ENUM('confirmed','checked_in','cancelled','refunded') NOT NULL DEFAULT 'confirmed',
  `checked_in_at`    DATETIME         NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_night`     DATE GENERATED ALWAYS AS (CASE WHEN `status` IN ('confirmed','checked_in') THEN `night` ELSE NULL END) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_booking_bed_night` (`bed_id`, `active_night`),
  KEY `idx_booking_order` (`order_id`),
  KEY `idx_booking_user` (`user_id`),
  KEY `idx_booking_night` (`night`, `status`),
  KEY `idx_booking_reference` (`reference`),
  CONSTRAINT `fk_bookings_bed` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------- transport --

CREATE TABLE IF NOT EXISTS `transport_routes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(160) NOT NULL,
  `slug`        VARCHAR(180) NOT NULL,
  `description` TEXT             NULL,
  `direction`   ENUM('to_venue','from_venue','return','onsite') NOT NULL DEFAULT 'to_venue',
  `price_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `requires_flight_number` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`     TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_route_slug` (`slug`),
  KEY `idx_routes_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transport_slots` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id`      INT UNSIGNED NOT NULL,
  `departs_at`    DATETIME     NOT NULL,
  `pickup_point`  VARCHAR(180) NOT NULL,
  `dropoff_point` VARCHAR(180) NOT NULL,
  `capacity`      SMALLINT UNSIGNED NOT NULL DEFAULT 22,
  `seats_taken`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `notes`         VARCHAR(255)     NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_slots_route` (`route_id`, `is_active`, `departs_at`),
  CONSTRAINT `fk_slots_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transport_bookings` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`      VARCHAR(24)  NOT NULL,
  `order_id`       INT UNSIGNED     NULL,
  `order_item_id`  INT UNSIGNED     NULL,
  `user_id`        INT UNSIGNED     NULL,
  `slot_id`        INT UNSIGNED NOT NULL,
  `route_id`       INT UNSIGNED NOT NULL,
  `passenger_name` VARCHAR(160) NOT NULL,
  `phone`          VARCHAR(30)  NOT NULL,
  `email`          VARCHAR(190) NOT NULL,
  `flight_number`  VARCHAR(40)      NULL,
  `luggage_count`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `accessibility_needs` VARCHAR(255) NULL,
  `notes`          VARCHAR(500)     NULL,
  `price_cents`    INT UNSIGNED NOT NULL DEFAULT 0,
  `status`         ENUM('confirmed','checked_in','cancelled','refunded') NOT NULL DEFAULT 'confirmed',
  `checked_in_at`  DATETIME         NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tbooking_slot` (`slot_id`, `status`),
  KEY `idx_tbooking_order` (`order_id`),
  KEY `idx_tbooking_user` (`user_id`),
  CONSTRAINT `fk_tbookings_slot` FOREIGN KEY (`slot_id`) REFERENCES `transport_slots` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------- carts and orders --

CREATE TABLE IF NOT EXISTS `carts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token`      CHAR(64)     NOT NULL,
  `user_id`    INT UNSIGNED     NULL,
  `coupon_id`  INT UNSIGNED     NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cart_token` (`token`),
  KEY `idx_cart_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id`          INT UNSIGNED NOT NULL,
  `item_type`        ENUM('registration','accommodation','merchandise','transport','donation') NOT NULL,
  `product_id`       INT UNSIGNED     NULL,
  `variant_id`       INT UNSIGNED     NULL,
  `bed_id`           INT UNSIGNED     NULL,
  `room_type_id`     INT UNSIGNED     NULL,
  `night`            DATE             NULL,
  `transport_slot_id` INT UNSIGNED    NULL,
  `description`      VARCHAR(255) NOT NULL,
  `unit_price_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `quantity`         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `meta`             TEXT             NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_items_cart` (`cart_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`        VARCHAR(24)  NOT NULL,
  `cart_token`       CHAR(64)         NULL,
  `user_id`          INT UNSIGNED     NULL,
  `email`            VARCHAR(190) NOT NULL,
  `first_name`       VARCHAR(80)      NULL,
  `last_name`        VARCHAR(80)      NULL,
  `phone`            VARCHAR(30)      NULL,
  `status`           ENUM('pending_payment','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending_payment',
  `subtotal_cents`   INT UNSIGNED NOT NULL DEFAULT 0,
  `discount_cents`   INT UNSIGNED NOT NULL DEFAULT 0,
  `total_cents`      INT UNSIGNED NOT NULL DEFAULT 0,
  `currency`         CHAR(3)      NOT NULL DEFAULT 'ZAR',
  `coupon_id`        INT UNSIGNED     NULL,
  `coupon_code`      VARCHAR(40)      NULL,
  `payment_method`   VARCHAR(40)  NOT NULL DEFAULT 'payfast',
  `customer_note`    VARCHAR(500)     NULL,
  `admin_note`       VARCHAR(500)     NULL,
  `terms_accepted_at` DATETIME        NULL,
  `paid_at`          DATETIME         NULL,
  `cancelled_at`     DATETIME         NULL,
  `checkin_code`     VARCHAR(24)      NULL,
  `checked_in_at`    DATETIME         NULL,
  `ip`               VARCHAR(45)      NULL,
  `is_mock`          TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_reference` (`reference`),
  KEY `idx_orders_status` (`status`, `created_at`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`          INT UNSIGNED NOT NULL,
  `item_type`         ENUM('registration','accommodation','merchandise','transport','donation') NOT NULL,
  `product_id`        INT UNSIGNED     NULL,
  `variant_id`        INT UNSIGNED     NULL,
  `bed_id`            INT UNSIGNED     NULL,
  `room_type_id`      INT UNSIGNED     NULL,
  `night`             DATE             NULL,
  `transport_slot_id` INT UNSIGNED     NULL,
  `description`       VARCHAR(255) NOT NULL,
  `unit_price_cents`  INT UNSIGNED NOT NULL DEFAULT 0,
  `quantity`          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `total_cents`       INT UNSIGNED NOT NULL DEFAULT 0,
  `meta`              TEXT             NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_type` (`item_type`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`           INT UNSIGNED     NULL,
  `donation_id`        INT UNSIGNED     NULL,
  `provider`           VARCHAR(40)  NOT NULL DEFAULT 'payfast',
  `provider_reference` VARCHAR(80)      NULL,
  `amount_cents`       INT UNSIGNED NOT NULL DEFAULT 0,
  `fee_cents`          INT UNSIGNED NOT NULL DEFAULT 0,
  `status`             ENUM('initiated','complete','failed','cancelled','refunded') NOT NULL DEFAULT 'initiated',
  `signature_valid`    TINYINT(1)   NOT NULL DEFAULT 0,
  `source_ip`          VARCHAR(45)      NULL,
  `payload`            MEDIUMTEXT       NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_provider_ref` (`provider_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED     NULL,
  `payment_id` INT UNSIGNED     NULL,
  `event`      VARCHAR(60)  NOT NULL,
  `message`    VARCHAR(500)     NULL,
  `payload`    MEDIUMTEXT       NULL,
  `source_ip`  VARCHAR(45)      NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_paylog_order` (`order_id`),
  KEY `idx_paylog_event` (`event`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `donations` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`     VARCHAR(24)  NOT NULL,
  `order_id`      INT UNSIGNED     NULL,
  `order_item_id` INT UNSIGNED     NULL,
  `user_id`       INT UNSIGNED     NULL,
  `donation_type` VARCHAR(80)  NOT NULL DEFAULT '7th Tradition Donation',
  `name`          VARCHAR(160)     NULL,
  `email`         VARCHAR(190)     NULL,
  `amount_cents`  INT UNSIGNED NOT NULL,
  `is_anonymous`  TINYINT(1)   NOT NULL DEFAULT 0,
  `message`       VARCHAR(500)     NULL,
  `status`        ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_donation_reference` (`reference`),
  -- One donation row per donation line item. This is what makes fulfilment
  -- idempotent: a repeated PayFast notification cannot record the same
  -- donation twice, and an order carrying two donations records both.
  UNIQUE KEY `uniq_donation_order_item` (`order_item_id`),
  KEY `idx_donations_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------- applications & messages --

CREATE TABLE IF NOT EXISTS `service_applications` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`     VARCHAR(24)  NOT NULL,
  `user_id`       INT UNSIGNED     NULL,
  `name`          VARCHAR(160) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `phone`         VARCHAR(30)  NOT NULL,
  `region`        VARCHAR(120)     NULL,
  `home_group`    VARCHAR(120)     NULL,
  `clean_time`    VARCHAR(60)      NULL,
  `service_areas` VARCHAR(500)     NULL,
  `availability`  VARCHAR(255)     NULL,
  `skills`        VARCHAR(500)     NULL,
  `notes`         TEXT             NULL,
  `status`        ENUM('new','reviewing','accepted','waitlisted','declined') NOT NULL DEFAULT 'new',
  `admin_notes`   TEXT             NULL,
  `consent_at`    DATETIME         NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_applications_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(160) NOT NULL,
  `email`       VARCHAR(190) NOT NULL,
  `phone`       VARCHAR(30)      NULL,
  `subject`     VARCHAR(190) NOT NULL,
  `message`     TEXT         NOT NULL,
  `status`      ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `admin_notes` TEXT             NULL,
  `source_ip`   VARCHAR(45)      NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- content --

CREATE TABLE IF NOT EXISTS `banners` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `position`        VARCHAR(60)  NOT NULL DEFAULT 'home_hero',
  `title`           VARCHAR(190) NOT NULL,
  `subtitle`        VARCHAR(255)     NULL,
  `body`            TEXT             NULL,
  `image`           VARCHAR(255)     NULL,
  `image_alt`       VARCHAR(200)     NULL,
  `cta_label`       VARCHAR(60)      NULL,
  `cta_url`         VARCHAR(255)     NULL,
  `secondary_label` VARCHAR(60)      NULL,
  `secondary_url`   VARCHAR(255)     NULL,
  `starts_at`       DATETIME         NULL,
  `ends_at`         DATETIME         NULL,
  `sort_order`      SMALLINT     NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`         TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_banner_position` (`position`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pages` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(160) NOT NULL,
  `title`            VARCHAR(190) NOT NULL,
  `subtitle`         VARCHAR(255)     NULL,
  `body_html`        MEDIUMTEXT       NULL,
  `hero_image`       VARCHAR(255)     NULL,
  `meta_title`       VARCHAR(190)     NULL,
  `meta_description` VARCHAR(255)     NULL,
  `is_published`     TINYINT(1)   NOT NULL DEFAULT 1,
  `is_legal`         TINYINT(1)   NOT NULL DEFAULT 0,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(190)     NULL,
  `alt_text`    VARCHAR(200) NOT NULL,
  `file_path`   VARCHAR(255) NOT NULL,
  `category`    VARCHAR(60)  NOT NULL DEFAULT 'venue',
  `source_note` VARCHAR(255)     NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`     TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_category` (`category`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(190) NOT NULL,
  `slug`        VARCHAR(200) NOT NULL,
  `description` TEXT             NULL,
  `starts_at`   DATETIME     NOT NULL,
  `ends_at`     DATETIME         NULL,
  `location`    VARCHAR(190)     NULL,
  `image`       VARCHAR(255)     NULL,
  `link_url`    VARCHAR(255)     NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `is_mock`     TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_slug` (`slug`),
  KEY `idx_events_start` (`starts_at`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programme_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `day_date`    DATE         NOT NULL,
  `start_time`  TIME         NOT NULL,
  `end_time`    TIME             NULL,
  `title`       VARCHAR(190) NOT NULL,
  `description` VARCHAR(500)     NULL,
  `location`    VARCHAR(120)     NULL,
  `track`       VARCHAR(60)      NULL,
  `is_highlight` TINYINT(1)  NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_programme_day` (`day_date`, `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category`   VARCHAR(60)  NOT NULL DEFAULT 'General',
  `question`   VARCHAR(255) NOT NULL,
  `answer`     TEXT         NOT NULL,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_faq_category` (`category`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------ settings and audit --

CREATE TABLE IF NOT EXISTS `settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name`  VARCHAR(60)  NOT NULL DEFAULT 'general',
  `key_name`    VARCHAR(100) NOT NULL,
  `value`       TEXT             NULL,
  `type`        ENUM('text','textarea','boolean','number','email','url','select','secret') NOT NULL DEFAULT 'text',
  `label`       VARCHAR(160) NOT NULL,
  `description` VARCHAR(255)     NULL,
  `options`     VARCHAR(500)     NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_setting_key` (`key_name`),
  KEY `idx_setting_group` (`group_name`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name`    VARCHAR(80)  NOT NULL,
  `name`        VARCHAR(160) NOT NULL,
  `subject`     VARCHAR(200) NOT NULL,
  `body_html`   MEDIUMTEXT   NOT NULL,
  `variables`   VARCHAR(500)     NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_template_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED     NULL,
  `user_email` VARCHAR(190)     NULL,
  `action`     VARCHAR(80)  NOT NULL,
  `entity`     VARCHAR(80)      NULL,
  `entity_id`  INT UNSIGNED     NULL,
  `changes`    MEDIUMTEXT       NULL,
  `source_ip`  VARCHAR(45)      NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`, `created_at`),
  KEY `idx_audit_entity` (`entity`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------- finance --

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

SET FOREIGN_KEY_CHECKS = 1;
