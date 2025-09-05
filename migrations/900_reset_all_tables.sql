-- Reset all tables for the Sarkari project (MySQL/MariaDB)
-- Usage:
-- 1) Review the DB name below matches your config (src/config.php -> DB_NAME)
-- 2) Run this script via phpMyAdmin, Adminer, or mysql CLI
--    Example (CLI): mysql -u root -p < migrations/900_reset_all_tables.sql

USE sarkari;

SET FOREIGN_KEY_CHECKS = 0;

-- Junction/child tables first
TRUNCATE TABLE post_tags;

-- Flexible content tables
TRUNCATE TABLE content_sections;
TRUNCATE TABLE content_vacancies;
TRUNCATE TABLE content_age_limits;
TRUNCATE TABLE content_fees;
TRUNCATE TABLE content_faqs;
TRUNCATE TABLE content_links;
TRUNCATE TABLE content_events;

-- Content tables
TRUNCATE TABLE posts;
TRUNCATE TABLE tags;
TRUNCATE TABLE jobs;
TRUNCATE TABLE results;
TRUNCATE TABLE admit_cards;
TRUNCATE TABLE syllabi;

-- Reference tables
TRUNCATE TABLE categories;
TRUNCATE TABLE authors;

-- Other tables
TRUNCATE TABLE admin_users;
TRUNCATE TABLE settings;
TRUNCATE TABLE page_views;
TRUNCATE TABLE ads;

SET FOREIGN_KEY_CHECKS = 1;

-- Note: TRUNCATE resets AUTO_INCREMENT counters automatically.
