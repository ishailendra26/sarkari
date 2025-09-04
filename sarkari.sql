CREATE DATABASE IF NOT EXISTS sarkari CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sarkari;

-- admin users
CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(150),
  role ENUM('admin','editor') DEFAULT 'editor',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- authors (content attribution)
CREATE TABLE authors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  bio TEXT NULL,
  avatar_url VARCHAR(500) NULL,
  verified TINYINT(1) DEFAULT 0,
  social JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- categories (job, result, admit, syllabus, general)
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE
);

-- tags for SEO and filtering
CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE
);

-- generic posts/articles (used for news, blog)
CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT,
  content LONGTEXT,
  category_id INT,
  status ENUM('draft','published') DEFAULT 'published',
  meta_title VARCHAR(255),
  meta_description VARCHAR(255),
  thumbnail_url VARCHAR(500) NULL,
  author_id INT NULL,
  extras JSON NULL,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- junction post_tags
CREATE TABLE post_tags (
  post_id INT,
  tag_id INT,
  PRIMARY KEY (post_id, tag_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Jobs (more structured)
CREATE TABLE jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  organization VARCHAR(150),
  location VARCHAR(150),
  apply_link VARCHAR(500),
  last_date DATE,
  vacancy_count INT DEFAULT 0,
  educational_qualification TEXT,
  age_limit VARCHAR(100),
  category_id INT,
  content LONGTEXT,
  attachments JSON DEFAULT NULL,
  thumbnail_url VARCHAR(500) NULL,
  author_id INT NULL,
  extras JSON NULL,
  status ENUM('draft','published') DEFAULT 'published',
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- Results (final schema)
CREATE TABLE results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  organization VARCHAR(150) NULL,
  description LONGTEXT NULL,
  result_date DATE NULL,
  download_url VARCHAR(500) NULL,
  thumbnail_url VARCHAR(500) NULL,
  author_id INT NULL,
  status ENUM('draft','published') DEFAULT 'published',
  extras JSON NULL,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- Admit cards (final schema)
CREATE TABLE admit_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  organization VARCHAR(150) NULL,
  description LONGTEXT NULL,
  exam_date DATE NULL,
  download_url VARCHAR(500) NULL,
  instructions LONGTEXT NULL,
  required_documents LONGTEXT NULL,
  thumbnail_url VARCHAR(500) NULL,
  author_id INT NULL,
  status ENUM('draft','published') DEFAULT 'published',
  extras JSON NULL,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- Syllabus (structured, final schema)
CREATE TABLE syllabi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  organization VARCHAR(150) NULL,
  description LONGTEXT NULL,
  exam_date DATE NULL,
  download_url VARCHAR(500) NULL,
  sections LONGTEXT,
  thumbnail_url VARCHAR(500) NULL,
  author_id INT NULL,
  status ENUM('draft','published') DEFAULT 'published',
  extras JSON NULL,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- basic site settings for admin
CREATE TABLE settings (
  k VARCHAR(100) PRIMARY KEY,
  v TEXT
);

-- simple analytics/log (optional)
CREATE TABLE page_views (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255),
  ip VARBINARY(16),
  user_agent VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ads management table
CREATE TABLE IF NOT EXISTS ads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  placement VARCHAR(50) NOT NULL, -- e.g., header_banner, footer_banner, sidebar_top, in_content
  page_scope VARCHAR(50) NOT NULL DEFAULT 'all', -- all, home, jobs, results, admit, syllabus, posts, post_detail, job_detail, result_detail, admit_detail, syllabus_detail, category
  slug_scope VARCHAR(255) NULL, -- optional slug for detail pages/category
  code MEDIUMTEXT NOT NULL, -- raw HTML/JS ad code
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  priority INT NOT NULL DEFAULT 0, -- higher shows first
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_placement (placement),
  INDEX idx_scope (page_scope, slug_scope),
  INDEX idx_status (status),
  INDEX idx_schedule (start_at, end_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Flexible content: events, links, fees, age limits, vacancies, sections, faqs
-- (merged from migrations/006_flexible_content.sql)

CREATE TABLE IF NOT EXISTS content_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  event_type ENUM('application_start','application_end','fee_payment_last','exam','admit_card','result','other') NOT NULL,
  event_label VARCHAR(255) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  notes VARCHAR(500) NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id),
  KEY idx_entity_type_order (entity_type, entity_id, event_type, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  label VARCHAR(255) NOT NULL,
  url VARCHAR(1024) NOT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_faqs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  question VARCHAR(500) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  category VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NULL,
  text VARCHAR(255) NULL,
  mode_notes VARCHAR(255) NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_age_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  min_age INT NULL,
  max_age INT NULL,
  cutoff_date DATE NULL,
  relaxation_text VARCHAR(500) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_vacancies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  post_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NULL,
  total_posts INT NULL,
  eligibility_text TEXT NULL,
  pay_scale VARCHAR(255) NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(32) NOT NULL,
  entity_id INT NOT NULL,
  section_type ENUM('how_to_apply','mode_of_exam','application_note','age_limit_note','vacancy_note','other') NOT NULL,
  title VARCHAR(255) NULL,
  content TEXT NOT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, section_type, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
