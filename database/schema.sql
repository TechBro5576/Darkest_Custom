 -- ============================================================
--  Darkest Custom — Database Schema
--  MySQL 5.7+ / MariaDB 10.3+
--  Run once: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS darkest_custom
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE darkest_custom;

-- ------------------------------------------------------------
--  admins — who can log into /admin
-- ------------------------------------------------------------
CREATE TABLE admins (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(150) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  name            VARCHAR(100) NOT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at   TIMESTAMP NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  categories — the 11 product categories from the flyer
-- ------------------------------------------------------------
CREATE TABLE categories (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  slug            VARCHAR(100) NOT NULL UNIQUE,
  icon            VARCHAR(100),          -- filename in /assets/img/icons
  sort_order      INT DEFAULT 0,
  is_active       TINYINT(1) DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  products — sellable items
-- ------------------------------------------------------------
CREATE TABLE products (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id     INT UNSIGNED NOT NULL,
  name            VARCHAR(200) NOT NULL,
  slug            VARCHAR(200) NOT NULL UNIQUE,
  description     TEXT,
  base_price      DECIMAL(10,2) NOT NULL,
  image           VARCHAR(255),          -- main product photo
  mockup_image    VARCHAR(255),          -- blank template for customizer
  variants        JSON,                  -- {"sizes":["S","M","L"],"colors":["White","Navy"]}
  is_active       TINYINT(1) DEFAULT 1,
  sort_order      INT DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  INDEX idx_active_cat (is_active, category_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  orders — one row per customer order
-- ------------------------------------------------------------
CREATE TABLE orders (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_ref           VARCHAR(30) NOT NULL UNIQUE,   -- DC-2026-0001

  -- Customer info (denormalized — guest checkout, no customers table)
  customer_name       VARCHAR(100) NOT NULL,
  customer_phone      VARCHAR(20)  NOT NULL,
  customer_email      VARCHAR(150),
  delivery_address    TEXT         NOT NULL,
  delivery_state      VARCHAR(50)  NOT NULL,

  -- Money (all in NGN)
  subtotal            DECIMAL(10,2) NOT NULL,
  delivery_fee        DECIMAL(10,2) NOT NULL DEFAULT 0,
  total               DECIMAL(10,2) NOT NULL,

  -- Payment
  payment_status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  payment_ref         VARCHAR(100) NULL,            -- Paystack reference
  payment_method      VARCHAR(30)  NULL,            -- card, transfer, ussd
  paid_at             TIMESTAMP    NULL,

  -- Fulfillment
  fulfillment_status  ENUM('new','in_production','ready','delivered','cancelled')
                        NOT NULL DEFAULT 'new',
  customer_notes      TEXT,
  admin_notes         TEXT,

  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_payment     (payment_status),
  INDEX idx_fulfillment (fulfillment_status),
  INDEX idx_created     (created_at),
  INDEX idx_ref         (order_ref)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  order_items — one row per product in an order
-- ------------------------------------------------------------
CREATE TABLE order_items (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id            INT UNSIGNED NOT NULL,
  product_id          INT UNSIGNED NOT NULL,

  -- Snapshot at time of order (so history doesn't break if product changes)
  product_name        VARCHAR(200) NOT NULL,
  unit_price          DECIMAL(10,2) NOT NULL,
  quantity            INT UNSIGNED NOT NULL DEFAULT 1,

  -- Customer's choice
  selected_size       VARCHAR(50),
  selected_color      VARCHAR(50),

  -- The customization
  design_file         VARCHAR(255),    -- /uploads/designs/uuid.png (raw upload)
  preview_image       VARCHAR(255),    -- /uploads/previews/uuid.png (flattened on mockup)
  customization_notes TEXT,

  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  settings — key/value config editable from /admin
-- ------------------------------------------------------------
CREATE TABLE settings (
  `key`        VARCHAR(100) PRIMARY KEY,
  `value`      TEXT,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--  Seed data
-- ============================================================

-- Default admin: email=admin@darkestcustom.com  password=ChangeMe123!
-- IMPORTANT: change password immediately after first login.
INSERT INTO admins (email, password_hash, name) VALUES
('admin@darkestcustom.com',
 '$2y$10$e0NRzP6m8vKzZ9Y1qXlQ8uJ6nZ0KzYc3sHk8wqXvJvV5rN0mQz8yG',  -- bcrypt of "ChangeMe123!"
 'Darkest Admin');

-- The 11 categories from your flyer
INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Clothing',      'clothing',      'tshirt.svg',   1),
('Caps',          'caps',          'cap.svg',      2),
('Shoes',         'shoes',         'shoe.svg',     3),
('Mugs',          'mugs',          'mug.svg',      4),
('Phone Cases',   'phone-cases',   'phone.svg',    5),
('Bags',          'bags',          'bag.svg',      6),
('Keyholders',    'keyholders',    'key.svg',      7),
('Gift Items',    'gift-items',    'gift.svg',     8),
('Frames',        'frames',        'frame.svg',    9),
('Tags & Labels', 'tags-labels',   'tag.svg',      10),
('Gifts & More',  'gifts-more',    'box.svg',      11);

-- Default settings
INSERT INTO settings (`key`, `value`) VALUES
('site_name',              'Darkest Custom'),
('site_tagline',           'Your Ideas. Our Creativity. Your Style. Your Way.'),
('whatsapp_number',        '2347035770759'),
('contact_email',          'hello@darkestcustom.com'),
('business_address',       'Asaba, Delta State, Nigeria'),
('currency_symbol',        '₦'),
('delivery_fee_default',   '2500'),
('delivery_fee_lagos',     '3500'),
('paystack_public_key',    ''),
('paystack_secret_key',    ''),
('order_prefix',           'DC'),
('orders_email_notify',    'admin@darkestcustom.com');
