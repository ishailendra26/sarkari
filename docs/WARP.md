# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## 🏛️ Project Overview

SarkariJobs Portal is a PHP-based government jobs portal featuring job listings, exam results, admit cards, and syllabus information. The application uses a simple MVC-like architecture with Tailwind CSS for styling.

## 🛠️ Development Commands

### Database Setup
```bash
# First-time setup (creates database and imports data)
php setup.php
# Access via browser: http://localhost/sarkari/setup.php

# Manual database import
mysql -u root -p sarkari < sarkari.sql
mysql -u root -p sarkari < migrations/001_init.sql
```

### Local Development
```bash
# Start XAMPP services (Windows)
# Or ensure Apache and MySQL are running

# Access the application
# Frontend: http://localhost/sarkari/
# Admin Panel: http://localhost/sarkari/admin/
# Default admin credentials: admin/admin123
```

### Testing & Debugging
```bash
# Test URL rewriting
php test-rewrite.php

# Test specific job debugging
php test-job-debug.php

# Debug database connections
php debug-job.php
```

## 🏗️ Architecture Overview

### Directory Structure
- **Root**: Main application files (`index.php`, `setup.php`, config files)
- **`src/`**: Core application layer
  - `config.php`: Application configuration constants
  - `db.php`: Database connection singleton class
  - `helpers.php`: Utility functions for common operations
  - `models/`: Data models (Job, Result, AdmitCard, Syllabus, Category)
- **`admin/`**: Administrative interface with authentication
- **`includes/`**: Shared templates (`header.php`, `footer.php`)
- **`migrations/`**: Database schema and initial data
- **`uploads/`**: User-uploaded files (created dynamically)

### Data Models Architecture
All models follow a consistent pattern:
- **Database Layer**: Singleton PDO connection with prepared statements
- **CRUD Operations**: `getAll()`, `getBySlug()`, `create()`, `update()`, `delete()`
- **Search Methods**: Category-aware search functionality
- **Pagination**: Built-in offset/limit support

Key Model Methods:
```php
// Job model example
$job->getAll($limit, $offset, $category)  // Paginated listings
$job->getBySlug($slug)                    // Single item by slug
$job->search($query, $limit, $offset)     // Global search
$job->searchByCategory($query, $categoryId) // Category-specific search
```

### URL Structure (Post-Rewrite Changes)
The application has moved from SEO-friendly URLs back to raw PHP parameters:
- Jobs: `job.php?slug=job-slug`
- Results: `result.php?slug=result-slug`
- Admit Cards: `admit-detail.php?slug=admit-slug`
- Categories: `jobs.php?category=category-slug`
- Search: `search.php?q=query&type=type`

### Security Implementation
- **Input Sanitization**: `htmlspecialchars()` on all user inputs
- **Database Security**: PDO prepared statements prevent SQL injection
- **Session Management**: Admin authentication with timeout
- **File Upload**: Type and size validation with secure naming
- **Admin Access**: Session-based authentication with `requireLogin()`

## 🔧 Configuration Management

### Environment Settings (`src/config.php`)
```php
// Database
DB_HOST, DB_NAME, DB_USER, DB_PASS

// Site
SITE_NAME, SITE_URL, SITE_DESCRIPTION

// File Upload
UPLOAD_PATH, MAX_FILE_SIZE (5MB default)

// Pagination
POSTS_PER_PAGE (10), JOBS_PER_PAGE (15)
```

### Development vs Production
- **Development**: `error_reporting(E_ALL)`, `display_errors = 1`
- **Production**: `error_reporting(0)`, `display_errors = 0`

## 📊 Database Schema

### Core Tables
- **`jobs`**: Job postings with category relationships, attachments as JSON
- **`results`**: Exam results with downloadable files
- **`admit_cards`**: Hall tickets with exam instructions
- **`syllabi`**: Syllabus with JSON-structured sections
- **`categories`**: Job categories (Central, State, Railway, etc.)
- **`admin_users`**: Admin authentication
- **`settings`**: Key-value site configuration
- **`tags`**: Content tagging system

### Key Relationships
- All content types (jobs, results, admit cards, syllabi) → categories (many-to-one)
- JSON fields: `jobs.attachments`, `syllabi.sections`
- Slug-based content access for SEO

## 🎨 Frontend Architecture

### Technology Stack
- **Framework**: Tailwind CSS via CDN
- **Icons**: FontAwesome
- **JavaScript**: Vanilla JS in `assets/js/main.js`
- **Responsive**: Mobile-first design

### Key UI Components
- **Search Interface**: Category-aware search with dropdown filtering
- **Card Layouts**: Consistent content presentation across all types
- **Admin Panel**: Full CRUD interfaces with form validation
- **Pagination**: Helper functions for efficient browsing

## 🚀 Common Development Tasks

### Adding New Content Types
1. Create model in `src/models/` following existing patterns
2. Add database table with category relationship
3. Create admin CRUD pages in `admin/`
4. Add frontend display pages
5. Update navigation and search functionality

### Modifying Search Functionality
- Main search logic in `search.php`
- Category-specific methods in respective models
- JavaScript handling in `assets/js/main.js`
- Search enhancement documented in `SEARCH_ENHANCEMENT_README.md`

### File Upload Handling
- Use `uploadFile()` helper function in `src/helpers.php`
- Default restrictions: 5MB, jpg/jpeg/png/pdf
- Files stored in `uploads/` with unique names
- Database stores filename, display uses `UPLOAD_URL`

### Admin Authentication
- Session-based with `startSession()`, `isLoggedIn()`, `requireLogin()`
- Default credentials: admin/admin123 (change after setup)
- Password hashing with `password_hash()` and `password_verify()`

## 🔍 Troubleshooting

### Common Issues
- **500 Error**: Check database connection in `src/config.php`
- **File Upload Issues**: Verify `uploads/` directory permissions (755)
- **Search Problems**: Check category relationships in database
- **Admin Access**: Ensure session configuration is correct

### Debug Mode
Enable in `src/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Database Connection Issues
- Verify XAMPP/MySQL service is running
- Check credentials in `src/config.php`
- Ensure database exists and is populated
- Run `setup.php` to reinitialize if needed

## 📝 Content Management

### Admin Panel Features
- **Dashboard**: Statistics, recent content, quick actions
- **Jobs**: Full CRUD with category assignment and file attachments
- **Results**: Exam result management with downloadable files
- **Admit Cards**: Hall ticket management with instructions
- **Syllabi**: JSON-structured syllabus sections
- **Categories**: Hierarchical organization system
- **Settings**: Site configuration and SEO metadata

### Content Types Structure
- All content uses slug-based URLs for SEO
- Category relationships for organization
- Status field for publish/draft control
- Timestamps for created/updated tracking
