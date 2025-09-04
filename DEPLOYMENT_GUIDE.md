# SarkariJobs Portal - Deployment Guide

## 🚀 Quick Start (Local Development)

### Using XAMPP (Recommended for Windows)

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache + MySQL services

2. **Setup Project**
   ```bash
   # Navigate to XAMPP htdocs
   cd C:\xampp\htdocs\
   
   # Your project is already here as 'sarkari'
   ```

3. **Database Setup**
   - Open http://localhost/phpmyadmin
   - Create database `sarkari_clone`
   - Import `sarkari.sql` file
   - Import `migrations/001_init.sql` for sample data

4. **Run Setup Script**
   - Visit: http://localhost/sarkari/setup.php
   - Follow the setup instructions

5. **Access Your Site**
   - Frontend: http://localhost/sarkari/public/
   - Admin Panel: http://localhost/sarkari/admin/
   - Default Admin: username `admin`, password `admin123`

## 🌐 Production Deployment

### Shared Hosting (Most Common)

1. **Upload Files**
   - Upload all files to your hosting account
   - Set document root to `/public` folder

2. **Database Setup**
   - Create MySQL database via cPanel
   - Import SQL files through phpMyAdmin
   - Update `src/config.php` with production credentials

3. **Configuration**
   ```php
   // src/config.php - Production settings
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('SITE_URL', 'https://yourdomain.com');
   
   // Disable error reporting in production
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

4. **Security Setup**
   - Change default admin password
   - Set proper file permissions (755 for directories, 644 for files)
   - Enable HTTPS in .htaccess
   - Update security headers

### VPS/Dedicated Server

1. **Server Requirements**
   - PHP 7.4+ with extensions: pdo, pdo_mysql, mbstring, json
   - MySQL 5.7+ or MariaDB 10.3+
   - Apache with mod_rewrite enabled

2. **Installation**
   ```bash
   # Clone repository
   git clone <your-repo> /var/www/sarkari
   
   # Set permissions
   sudo chown -R www-data:www-data /var/www/sarkari
   sudo chmod -R 755 /var/www/sarkari
   sudo chmod -R 777 /var/www/sarkari/public/uploads
   
   # Configure Apache virtual host
   sudo nano /etc/apache2/sites-available/sarkari.conf
   ```

3. **Apache Virtual Host**
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/sarkari/public
       
       <Directory /var/www/sarkari/public>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/sarkari_error.log
       CustomLog ${APACHE_LOG_DIR}/sarkari_access.log combined
   </VirtualHost>
   ```

## 🔧 Configuration Options

### Environment-Specific Settings

**Development**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('SITE_URL', 'http://localhost/sarkari');
```

**Production**
```php
error_reporting(0);
ini_set('display_errors', 0);
define('SITE_URL', 'https://yourdomain.com');
```

### Performance Optimization

1. **Enable Gzip Compression** (in .htaccess)
2. **Set Cache Headers** for static files
3. **Optimize Images** - Use WebP format when possible
4. **Database Indexing** - Add indexes for frequently queried columns
5. **CDN Integration** - Use CloudFlare or similar

### Security Hardening

1. **File Permissions**
   ```bash
   find /var/www/sarkari -type d -exec chmod 755 {} \;
   find /var/www/sarkari -type f -exec chmod 644 {} \;
   chmod 777 /var/www/sarkari/public/uploads
   ```

2. **Hide Sensitive Files**
   ```apache
   # Add to .htaccess
   <Files "*.sql">
       Deny from all
   </Files>
   <Files "config.php">
       Deny from all
   </Files>
   ```

3. **Database Security**
   - Use strong passwords
   - Limit database user privileges
   - Enable SSL connections
   - Regular backups

## 📱 Mobile Optimization

The site is already mobile-optimized with:
- **Responsive Design** - Tailwind CSS breakpoints
- **Touch-Friendly** - Large tap targets
- **Fast Loading** - Optimized assets
- **Progressive Web App** ready structure

## 🔍 SEO Setup

1. **Google Search Console**
   - Verify domain ownership
   - Submit sitemap: yourdomain.com/sitemap.xml.php

2. **Google Analytics**
   - Add tracking code in `src/config.php`
   - Set up conversion goals

3. **Meta Tags**
   - Already implemented dynamic meta tags
   - Customize in individual page files

## 🔄 Maintenance

### Regular Tasks
- **Database Backup** - Set up automated daily backups
- **Security Updates** - Keep PHP/MySQL updated
- **Content Review** - Moderate user-generated content
- **Performance Monitoring** - Monitor site speed and uptime

### Backup Strategy
```bash
# Database backup script
mysqldump -u username -p sarkari_clone > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /var/www/sarkari
```

## 🆘 Troubleshooting

### Common Issues

**Database Connection Error**
- Check credentials in `src/config.php`
- Verify MySQL service is running
- Check firewall settings

**Permission Denied**
- Set correct file permissions
- Check Apache user ownership
- Verify uploads directory is writable

**404 Errors**
- Enable mod_rewrite in Apache
- Check .htaccess file exists
- Verify document root points to `/public`

**Slow Performance**
- Enable PHP OPcache
- Optimize database queries
- Use CDN for static assets
- Enable Gzip compression

### Debug Mode
Enable debug mode by setting in `src/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

For deployment issues:
1. Check this guide first
2. Review error logs
3. Test on local environment
4. Contact support with specific error messages

---

**Happy Deploying! 🚀**
