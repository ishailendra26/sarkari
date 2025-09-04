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
