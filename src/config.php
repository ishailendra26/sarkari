<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u163236363_ez');
define('DB_USER', 'u163236363_ez');
define('DB_PASS', 'Shailu26ap@');

// Site Configuration
define('SITE_NAME', 'Examsz - Your Exam Success Partner | Jobs, Results, Admit Cards & Syllabus | Mock Tests');
define('SITE_URL', 'https://examsz.in');
define('SITE_DESCRIPTION', 'Latest Government Jobs, Results, Admit Cards & Syllabus | Mock Tests');

// Community / Social Channels
// Update these to your real channels
define('TELEGRAM_URL', 'https://t.me/examszin');
define('WHATSAPP_URL', 'https://whatsapp.com/channel/0029VbB1u0GDeONDSSn6jC1a');

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Pagination
define('POSTS_PER_PAGE', 10);
define('JOBS_PER_PAGE', 15);

// Admin Configuration
define('ADMIN_EMAIL', 'admin@examsz.in');
define('SESSION_TIMEOUT', 3600); // 1 hour

// SEO Configuration
define('DEFAULT_META_TITLE', 'Examsz - Your Exam Success Partner | Jobs, Results, Admit Cards & Syllabus | Mock Tests');
define('DEFAULT_META_DESCRIPTION', 'Find latest government jobs, results, admit cards and syllabus. Stay updated with Sarkari job notifications.');

// Error Reporting (set to 0 in production)
error_reporting(0);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>
