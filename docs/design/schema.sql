-- Gobiz: طرح اولیه دیتابیس MySQL 8 (utf8mb4)
SET NAMES utf8mb4;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(15) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);

CREATE TABLE provinces (id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60) NOT NULL);
CREATE TABLE cities (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, province_id SMALLINT UNSIGNED NOT NULL, name VARCHAR(80) NOT NULL,
  FOREIGN KEY (province_id) REFERENCES provinces(id));

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  business_type ENUM('manufacturer','trader','agent','service') NOT NULL,
  national_id VARCHAR(20) NULL,
  registration_no VARCHAR(30) NULL,
  established_year SMALLINT NULL,
  employees_range VARCHAR(20) NULL,
  city_id INT UNSIGNED NULL,
  address VARCHAR(300) NULL,
  phone VARCHAR(30) NULL, website VARCHAR(200) NULL,
  logo VARCHAR(255) NULL, description TEXT NULL,
  verified_level TINYINT NOT NULL DEFAULT 0,  -- 0 تأیید نشده، 1 تأیید مدارک، 2 بازدید میدانی
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (city_id) REFERENCES cities(id)
);

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- فیلدهای فنی هر دسته (مثلاً توان، ظرفیت، ولتاژ، تناژ)
CREATE TABLE attributes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL, unit VARCHAR(20) NULL,
  type ENUM('text','number','select','bool') NOT NULL DEFAULT 'text',
  options JSON NULL, is_filterable TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE listings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  city_id INT UNSIGNED NULL,
  type ENUM('machine','factory','part','service') NOT NULL,
  title VARCHAR(200) NOT NULL, slug VARCHAR(230) NOT NULL UNIQUE,
  description MEDIUMTEXT NULL,
  condition_state ENUM('new','used','refurbished') NULL,
  brand VARCHAR(100) NULL, model VARCHAR(100) NULL,
  manufacture_year SMALLINT NULL, origin_country VARCHAR(60) NULL,
  deal_type ENUM('sale','rent','lease') NOT NULL DEFAULT 'sale',
  price BIGINT UNSIGNED NULL, currency CHAR(3) NOT NULL DEFAULT 'IRT',
  price_negotiable TINYINT(1) NOT NULL DEFAULT 0,
  min_order_qty INT NULL, stock_qty INT NULL,
  warranty_months SMALLINT NULL, has_installation TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','pending','published','sold','expired','rejected') NOT NULL DEFAULT 'pending',
  is_featured TINYINT(1) NOT NULL DEFAULT 0, featured_until DATETIME NULL,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATETIME NULL, expires_at DATETIME NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (city_id) REFERENCES cities(id),
  INDEX idx_filter (status, category_id, city_id, price),
  FULLTEXT KEY ft_search (title, description, brand, model)
);

CREATE TABLE listing_attribute_values (
  listing_id BIGINT UNSIGNED NOT NULL, attribute_id INT UNSIGNED NOT NULL,
  value VARCHAR(255) NOT NULL,
  PRIMARY KEY (listing_id, attribute_id),
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (attribute_id) REFERENCES attributes(id)
);

-- ویژه برای کارخانه/سوله
CREATE TABLE factory_details (
  listing_id BIGINT UNSIGNED PRIMARY KEY,
  land_area INT NULL, building_area INT NULL,
  zone_type ENUM('industrial_town','free_zone','outside_town') NULL,
  has_license TINYINT(1) DEFAULT 0, license_type VARCHAR(100) NULL,
  power_kw INT NULL, water TINYINT(1) DEFAULT 0, gas TINYINT(1) DEFAULT 0,
  workers_count INT NULL, deed_type VARCHAR(60) NULL,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE listing_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, listing_id BIGINT UNSIGNED NOT NULL,
  kind ENUM('image','video','catalog_pdf') NOT NULL, path VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE rfqs (  -- درخواست قیمت
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL, title VARCHAR(200) NOT NULL, details TEXT NOT NULL,
  quantity INT NULL, budget BIGINT UNSIGNED NULL, city_id INT UNSIGNED NULL,
  status ENUM('open','closed') DEFAULT 'open', created_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE inquiries (  -- پیام/استعلام به آگهی
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, listing_id BIGINT UNSIGNED NULL, rfq_id BIGINT UNSIGNED NULL,
  from_user_id BIGINT UNSIGNED NOT NULL, to_company_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL, offered_price BIGINT UNSIGNED NULL,
  status ENUM('new','read','answered') DEFAULT 'new', created_at TIMESTAMP NULL,
  FOREIGN KEY (from_user_id) REFERENCES users(id), FOREIGN KEY (to_company_id) REFERENCES companies(id)
);

CREATE TABLE favorites (user_id BIGINT UNSIGNED NOT NULL, listing_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, listing_id));
CREATE TABLE reviews (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL, body TEXT NULL, approved TINYINT(1) DEFAULT 0, created_at TIMESTAMP NULL);
CREATE TABLE reports (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, listing_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NULL, reason VARCHAR(255), created_at TIMESTAMP NULL);

CREATE TABLE plans (id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80), price BIGINT UNSIGNED, max_listings INT, featured_slots INT, duration_days INT);
CREATE TABLE subscriptions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, plan_id SMALLINT UNSIGNED NOT NULL, starts_at DATETIME, ends_at DATETIME);
CREATE TABLE payments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, amount BIGINT UNSIGNED, gateway VARCHAR(40), ref_id VARCHAR(80), status ENUM('pending','paid','failed'), created_at TIMESTAMP NULL);
