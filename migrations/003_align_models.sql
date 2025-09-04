-- Align DB schema with models and admin forms for Result, Admit Card, and Syllabus

-- RESULTS: add missing fields and rename legacy columns
ALTER TABLE results 
  ADD COLUMN IF NOT EXISTS organization VARCHAR(150) NULL AFTER slug,
  ADD COLUMN IF NOT EXISTS description LONGTEXT NULL AFTER organization,
  ADD COLUMN IF NOT EXISTS result_date DATE NULL AFTER description,
  ADD COLUMN IF NOT EXISTS download_url VARCHAR(500) NULL AFTER result_date,
  ADD COLUMN IF NOT EXISTS status ENUM('draft','published') DEFAULT 'published' AFTER download_url;

-- Rename legacy columns if they exist
ALTER TABLE results 
  CHANGE COLUMN exam_date result_date DATE NULL,
  CHANGE COLUMN download_link download_url VARCHAR(500),
  CHANGE COLUMN content description LONGTEXT;

-- Ensure optional columns added by prior migration exist (idempotent if 002 already ran)
ALTER TABLE results 
  ADD COLUMN IF NOT EXISTS thumbnail_url VARCHAR(500) NULL AFTER description,
  ADD COLUMN IF NOT EXISTS author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN IF NOT EXISTS extras JSON NULL AFTER author_id;

-- Recreate FK if needed (will error if exists; wrap in try/catch when running manually if required)
-- ALTER TABLE results ADD CONSTRAINT fk_results_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;


-- ADMIT CARDS: add missing fields and rename legacy columns
ALTER TABLE admit_cards 
  ADD COLUMN IF NOT EXISTS organization VARCHAR(150) NULL AFTER slug,
  ADD COLUMN IF NOT EXISTS description LONGTEXT NULL AFTER organization,
  ADD COLUMN IF NOT EXISTS download_url VARCHAR(500) NULL AFTER exam_date,
  ADD COLUMN IF NOT EXISTS required_documents LONGTEXT NULL AFTER instructions,
  ADD COLUMN IF NOT EXISTS status ENUM('draft','published') DEFAULT 'published' AFTER download_url;

-- Rename legacy columns if they exist
ALTER TABLE admit_cards 
  CHANGE COLUMN download_link download_url VARCHAR(500);

-- Ensure optional columns exist
ALTER TABLE admit_cards 
  ADD COLUMN IF NOT EXISTS thumbnail_url VARCHAR(500) NULL AFTER instructions,
  ADD COLUMN IF NOT EXISTS author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN IF NOT EXISTS extras JSON NULL AFTER author_id;

-- ALTER TABLE admit_cards ADD CONSTRAINT fk_admit_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;


-- SYLLABI: add missing fields and columns expected by model/forms
ALTER TABLE syllabi 
  ADD COLUMN IF NOT EXISTS organization VARCHAR(150) NULL AFTER slug,
  ADD COLUMN IF NOT EXISTS description LONGTEXT NULL AFTER organization,
  ADD COLUMN IF NOT EXISTS exam_date DATE NULL AFTER description,
  ADD COLUMN IF NOT EXISTS download_url VARCHAR(500) NULL AFTER exam_date,
  ADD COLUMN IF NOT EXISTS status ENUM('draft','published') DEFAULT 'published' AFTER download_url;

-- Ensure optional columns exist
ALTER TABLE syllabi 
  ADD COLUMN IF NOT EXISTS thumbnail_url VARCHAR(500) NULL AFTER sections,
  ADD COLUMN IF NOT EXISTS author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN IF NOT EXISTS extras JSON NULL AFTER author_id;

-- ALTER TABLE syllabi ADD CONSTRAINT fk_syllabi_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;


-- NOTE:
-- If your MySQL/MariaDB version does not support IF NOT EXISTS or CHANGE of non-existent columns, 
-- run each section manually and ignore errors about existing columns.
