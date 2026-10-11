-- ============================================================
--  Darkest Custom — Database Schema
--  Import into your chosen database (no CREATE DB / USE inside).
--  Idempotent: safe to re-run. WARNING: drops existing tables.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
--  Drop in reverse dependency order
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
--  admins
-- ------------------------------------------------------------
CREATE TABLE admins (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(150) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  name            VARCHAR(100) NOT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at   TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  categories
-- ------------------------------------------------------------
CREATE TABLE categories (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  slug            VARCHAR(100) NOT NULL UNIQUE,
  icon            VARCHAR(100),
  sort_order      INT DEFAULT 0,
  is_active       TINYINT(1) DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  products
-- ------------------------------------------------------------
CREATE TABLE products (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id     INT UNSIGNED NOT NULL,
  name            VARCHAR(200) NOT NULL,
  slug            VARCHAR(200) NOT NULL UNIQUE,
  description     TEXT,
  base_price      DECIMAL(10,2) NOT NULL,
  image           VARCHAR(255),
  mockup_image    VARCHAR(255),
  variants        JSON,
  is_active       TINYINT(1) DEFAULT 1,
  sort_order      INT DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  INDEX idx_active_cat (is_active, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  orders
-- ------------------------------------------------------------
CREATE TABLE orders (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_ref           VARCHAR(30) NOT NULL UNIQUE,

  customer_name       VARCHAR(100) NOT NULL,
  customer_phone      VARCHAR(20)  NOT NULL,
  customer_email      VARCHAR(150),
  delivery_address    TEXT         NOT NULL,
  delivery_state      VARCHAR(50)  NOT NULL,

  subtotal            DECIMAL(10,2) NOT NULL,
  delivery_fee        DECIMAL(10,2) NOT NULL DEFAULT 0,
  total               DECIMAL(10,2) NOT NULL,

  payment_status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  payment_ref         VARCHAR(100) NULL,
  payment_method      VARCHAR(30)  NULL,
  paid_at             TIMESTAMP    NULL,

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  order_items
-- ------------------------------------------------------------
CREATE TABLE order_items (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id            INT UNSIGNED NOT NULL,
  product_id          INT UNSIGNED NOT NULL,

  product_name        VARCHAR(200) NOT NULL,
  unit_price          DECIMAL(10,2) NOT NULL,
  quantity            INT UNSIGNED NOT NULL DEFAULT 1,

  selected_size       VARCHAR(50),
  selected_color      VARCHAR(50),

  design_file         VARCHAR(255),
  preview_image       VARCHAR(255),
  customization_notes TEXT,

  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  settings
-- ------------------------------------------------------------
CREATE TABLE settings (
  `key`        VARCHAR(100) PRIMARY KEY,
  `value`      TEXT,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Seed data
-- ============================================================

-- Placeholder admin. Password hash below is NOT valid — replace it
-- before login, OR use admin/setup.php (browser-based, safer).
-- Generate a real hash with:
--   php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
INSERT INTO admins (email, password_hash, name) VALUES
('admin@darkestcustom.com',
 '$2y$10$REPLACE_THIS_WITH_A_REAL_BCRYPT_HASH_BEFORE_LOGIN_ATTEMPT',
 'Darkest Admin');

-- Categories
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

-- Settings — currency symbol as raw UTF-8 bytes (0xE282A6 = ₦)
INSERT INTO settings (`key`, `value`) VALUES
('site_name',              'Darkest Custom'),
('site_tagline',           'Your Ideas. Our Creativity. Your Style. Your Way.'),
('whatsapp_number',        '2347035770759'),
('contact_email',          'hello@darkestcustom.com'),
('business_address',       'Asaba, Delta State, Nigeria'),
('currency_symbol',        0xE282A6),
('delivery_fee_default',   '2500'),
('delivery_fee_lagos',     '3500'),
('paystack_public_key',    ''),
('paystack_secret_key',    ''),
('order_prefix',           'DC'),
('orders_email_notify',    'admin@darkestcustom.com');