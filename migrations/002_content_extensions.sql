-- Content extensions: authors, thumbnails, flexible sections

-- Authors table
CREATE TABLE IF NOT EXISTS authors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  bio TEXT NULL,
  avatar_url VARCHAR(500) NULL,
  verified TINYINT(1) DEFAULT 0,
  social JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add author and media/extra fields to content tables
ALTER TABLE jobs 
  ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER attachments,
  ADD COLUMN author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN extras JSON NULL AFTER author_id,
  ADD CONSTRAINT fk_jobs_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;

ALTER TABLE results 
  ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER content,
  ADD COLUMN author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN extras JSON NULL AFTER author_id,
  ADD CONSTRAINT fk_results_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;

ALTER TABLE admit_cards 
  ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER instructions,
  ADD COLUMN author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN extras JSON NULL AFTER author_id,
  ADD CONSTRAINT fk_admit_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;

ALTER TABLE syllabi 
  ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER sections,
  ADD COLUMN author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN extras JSON NULL AFTER author_id,
  ADD CONSTRAINT fk_syllabi_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;

ALTER TABLE posts 
  ADD COLUMN thumbnail_url VARCHAR(500) NULL AFTER content,
  ADD COLUMN author_id INT NULL AFTER thumbnail_url,
  ADD COLUMN extras JSON NULL AFTER author_id,
  ADD CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL;
