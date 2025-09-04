-- Migration: 008_search_indexes.sql
-- Purpose: Improve search performance by adding FULLTEXT and supportive indexes
-- Safe to run multiple times; uses INFORMATION_SCHEMA checks to avoid duplicate index errors.

-- Jobs: FULLTEXT on (title, organization, location); non-fulltext on category_id
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'ft_jobs_main'
    ),
    'SELECT 1',
    'ALTER TABLE `jobs` ADD FULLTEXT `ft_jobs_main` (`title`, `organization`, `location`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_category_id'
    ),
    'SELECT 1',
    'ALTER TABLE `jobs` ADD INDEX `idx_jobs_category_id` (`category_id`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Results: FULLTEXT on (title, description)
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND INDEX_NAME = 'ft_results_main'
    ),
    'SELECT 1',
    'ALTER TABLE `results` ADD FULLTEXT `ft_results_main` (`title`, `description`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Admit Cards: FULLTEXT on (title, instructions, download_url)
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admit_cards' AND INDEX_NAME = 'ft_admit_main'
    ),
    'SELECT 1',
    'ALTER TABLE `admit_cards` ADD FULLTEXT `ft_admit_main` (`title`, `instructions`, `download_url`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Syllabi: FULLTEXT on (title, sections)
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'syllabi' AND INDEX_NAME = 'ft_syllabus_main'
    ),
    'SELECT 1',
    'ALTER TABLE `syllabi` ADD FULLTEXT `ft_syllabus_main` (`title`, `sections`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Posts: FULLTEXT on (title, excerpt, content)
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'ft_posts_main'
    ),
    'SELECT 1',
    'ALTER TABLE `posts` ADD FULLTEXT `ft_posts_main` (`title`, `excerpt`, `content`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Optional: common date ordering
SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_published_at'
    ),
    'SELECT 1',
    'ALTER TABLE `jobs` ADD INDEX `idx_jobs_published_at` (`published_at`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND INDEX_NAME = 'idx_results_published_at'
    ),
    'SELECT 1',
    'ALTER TABLE `results` ADD INDEX `idx_results_published_at` (`published_at`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admit_cards' AND INDEX_NAME = 'idx_admit_published_at'
    ),
    'SELECT 1',
    'ALTER TABLE `admit_cards` ADD INDEX `idx_admit_published_at` (`published_at`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'syllabi' AND INDEX_NAME = 'idx_syllabus_published_at'
    ),
    'SELECT 1',
    'ALTER TABLE `syllabi` ADD INDEX `idx_syllabus_published_at` (`published_at`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @stmt := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_posts_published_at'
    ),
    'SELECT 1',
    'ALTER TABLE `posts` ADD INDEX `idx_posts_published_at` (`published_at`);'
  )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
