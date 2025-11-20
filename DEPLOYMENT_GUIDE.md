# IdeaNest - Production Deployment Guide
**Version:** 2.0  
**Date:** November 19, 2025  
**Status:** Ready for Production

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### 1. Server Requirements
- [ ] PHP 8.0 or higher
- [ ] MySQL 5.7 or higher / MariaDB 10.4+
- [ ] Apache/Nginx web server
- [ ] SSL certificate installed
- [ ] Cron job support
- [ ] Email server (SMTP) configured
- [ ] Minimum 2GB RAM
- [ ] Minimum 20GB disk space

### 2. PHP Extensions Required
```bash
php -m | grep -E 'mysqli|openssl|mbstring|json|curl|gd|zip'
```
Required extensions:
- mysqli
- openssl
- mbstring
- json
- curl
- gd
- zip
- fileinfo

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Backup Current System
```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf ideanest_backup_$(date +%Y%m%d).tar.gz /path/to/IdeaNest
```

### Step 2: Update Environment Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit .env file
nano .env
```

**Required .env settings:**
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_NAME=ictmu6ya_ideanest
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Security
ENCRYPTION_KEY=generate_random_32_char_key_here
SESSION_LIFETIME=1800

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=IdeaNest

# File Upload
MAX_UPLOAD_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx,zip
```

### Step 3: Run Database Migrations
```bash
# Navigate to project directory
cd /path/to/IdeaNest

# Run migrations
mysql -u username -p database_name < db/migrations/add_progress_tracking.sql

# Verify tables created
mysql -u username -p database_name -e "SHOW TABLES LIKE 'progress_%';"
```

### Step 4: Set File Permissions
```bash
# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make scripts executable
chmod +x cron/setup_cron.sh

# Set writable directories
chmod 775 logs/
chmod 775 user/uploads/
chmod 775 user/forms/uploads/

# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data /path/to/IdeaNest
```

### Step 5: Setup Cron Jobs
```bash
# Run cron setup script
cd cron
./setup_cron.sh

# Verify cron jobs installed
crontab -l | grep IdeaNest

# Test cron jobs manually
php /path/to/IdeaNest/mentor/session_reminder_system.php
php /path/to/IdeaNest/cron/cleanup_old_sessions.php
```

### Step 6: Configure Web Server

#### Apache (.htaccess already included)
```apache
# Ensure mod_rewrite is enabled
sudo a2enmod rewrite
sudo systemctl restart apache2

# Verify .htaccess is working
curl -I https://yourdomain.com
```

#### Nginx (create config)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    root /path/to/IdeaNest;
    index index.php index.html;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Step 7: SSL Certificate Setup
```bash
# Using Let's Encrypt (recommended)
sudo apt install certbot python3-certbot-apache

# For Apache
sudo certbot --apache -d yourdomain.com

# For Nginx
sudo certbot --nginx -d yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Step 8: Test Email Configuration
```bash
# Run email test script
php test_email.php

# Check logs
tail -f logs/error.log
```

### Step 9: Security Hardening
```bash
# Disable directory listing
echo "Options -Indexes" >> .htaccess

# Protect sensitive files
cat >> .htaccess << 'EOF'
<FilesMatch "^\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sql|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF

# Set secure PHP settings
sudo nano /etc/php/8.0/apache2/php.ini
```

**Recommended php.ini settings:**
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /path/to/IdeaNest/logs/php_errors.log
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### Step 10: Performance Optimization
```bash
# Enable PHP OPcache
sudo nano /etc/php/8.0/apache2/conf.d/10-opcache.ini
```

**OPcache settings:**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Step 11: Setup Monitoring
```bash
# Create monitoring script
cat > /usr/local/bin/ideanest_monitor.sh << 'EOF'
#!/bin/bash
LOG_FILE="/path/to/IdeaNest/logs/monitor.log"
ERROR_LOG="/path/to/IdeaNest/logs/error.log"

# Check if site is up
if ! curl -f -s https://yourdomain.com > /dev/null; then
    echo "[$(date)] Site is DOWN!" >> $LOG_FILE
    # Send alert email
    echo "IdeaNest is down!" | mail -s "ALERT: IdeaNest Down" admin@yourdomain.com
fi

# Check error log size
ERROR_SIZE=$(stat -f%z "$ERROR_LOG" 2>/dev/null || stat -c%s "$ERROR_LOG")
if [ $ERROR_SIZE -gt 10485760 ]; then
    echo "[$(date)] Error log is large: $ERROR_SIZE bytes" >> $LOG_FILE
fi

# Check disk space
DISK_USAGE=$(df -h /path/to/IdeaNest | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "[$(date)] Disk usage high: $DISK_USAGE%" >> $LOG_FILE
fi
EOF

chmod +x /usr/local/bin/ideanest_monitor.sh

# Add to crontab (run every 5 minutes)
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/ideanest_monitor.sh") | crontab -
```

---

## 🧪 POST-DEPLOYMENT TESTING

### 1. Functionality Tests
```bash
# Test URLs
curl -I https://yourdomain.com
curl -I https://yourdomain.com/user/index.php
curl -I https://yourdomain.com/Admin/admin.php
curl -I https://yourdomain.com/mentor/dashboard.php
curl -I https://yourdomain.com/Report/report_dashboard.php
```

### 2. Security Tests
- [ ] HTTPS working (no mixed content)
- [ ] CSRF tokens present on forms
- [ ] File upload validation working
- [ ] Session timeout working (30 min)
- [ ] Rate limiting active
- [ ] Error pages display correctly (404, 500)
- [ ] .env file not accessible via browser
- [ ] Directory listing disabled

### 3. Feature Tests
- [ ] User registration/login
- [ ] Project submission
- [ ] Admin approval workflow
- [ ] Mentor-student pairing
- [ ] Session scheduling
- [ ] Progress tracking
- [ ] Report generation
- [ ] Email notifications
- [ ] File downloads

### 4. Performance Tests
```bash
# Load testing with Apache Bench
ab -n 1000 -c 10 https://yourdomain.com/

# Check response times
curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com/
```

**curl-format.txt:**
```
time_namelookup:  %{time_namelookup}\n
time_connect:  %{time_connect}\n
time_appconnect:  %{time_appconnect}\n
time_pretransfer:  %{time_pretransfer}\n
time_redirect:  %{time_redirect}\n
time_starttransfer:  %{time_starttransfer}\n
time_total:  %{time_total}\n
```

---

## 📊 MONITORING & MAINTENANCE

### Daily Tasks (Automated via Cron)
- Session reminders sent
- Old sessions cleaned up
- Daily reports generated
- Database backup

### Weekly Tasks
- [ ] Review error logs
- [ ] Check disk space
- [ ] Monitor database size
- [ ] Review failed emails
- [ ] Check SSL certificate expiry

### Monthly Tasks
- [ ] Update dependencies
- [ ] Security audit
- [ ] Performance review
- [ ] Backup verification
- [ ] User feedback review

### Log Files to Monitor
```bash
# Application logs
tail -f logs/error.log
tail -f logs/cron.log

# Web server logs
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# PHP logs
tail -f /var/log/php8.0-fpm.log

# MySQL logs
tail -f /var/log/mysql/error.log
```

---

## 🔄 ROLLBACK PROCEDURE

If deployment fails:

```bash
# 1. Stop web server
sudo systemctl stop apache2

# 2. Restore database
mysql -u username -p database_name < backup_YYYYMMDD.sql

# 3. Restore files
cd /path/to
tar -xzf ideanest_backup_YYYYMMDD.tar.gz

# 4. Restart web server
sudo systemctl start apache2

# 5. Verify rollback
curl -I https://yourdomain.com
```

---

## 🆘 TROUBLESHOOTING

### Issue: White Screen / 500 Error
```bash
# Check PHP error log
tail -f logs/error.log

# Check web server error log
tail -f /var/log/apache2/error.log

# Enable debug mode temporarily
nano .env
# Set APP_DEBUG=true
```

### Issue: Database Connection Failed
```bash
# Test database connection
mysql -u username -p database_name -e "SELECT 1;"

# Check credentials in .env
cat .env | grep DB_

# Verify user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'username'@'localhost';"
```

### Issue: Emails Not Sending
```bash
# Test email configuration
php test_email.php

# Check SMTP settings
cat .env | grep SMTP_

# Check email logs
tail -f logs/error.log | grep -i email
```

### Issue: Cron Jobs Not Running
```bash
# Check cron is running
sudo systemctl status cron

# Verify cron jobs
crontab -l

# Check cron logs
tail -f /var/log/syslog | grep CRON

# Test manually
php /path/to/IdeaNest/mentor/session_reminder_system.php
```

### Issue: File Upload Fails
```bash
# Check directory permissions
ls -la user/uploads/

# Check PHP upload settings
php -i | grep upload

# Check disk space
df -h

# Test file validation
php -r "var_dump(mime_content_type('test.jpg'));"
```

---

## 📞 SUPPORT CONTACTS

### Technical Support
- **Email:** support@yourdomain.com
- **Phone:** +1-XXX-XXX-XXXX
- **Hours:** 24/7

### Emergency Contacts
- **System Admin:** admin@yourdomain.com
- **Database Admin:** dba@yourdomain.com
- **Security Team:** security@yourdomain.com

---

## 📚 ADDITIONAL RESOURCES

### Documentation
- [User Manual](Report/USER_MANUAL.md)
- [API Documentation](docs/API.md)
- [Database Schema](Report/DATABASE_SCHEMA.md)
- [Security Guide](SECURITY.md)

### External Resources
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Apache Documentation](https://httpd.apache.org/docs/)
- [Let's Encrypt](https://letsencrypt.org/docs/)

---

## ✅ DEPLOYMENT COMPLETION CHECKLIST

- [ ] Environment configured (.env)
- [ ] Database migrated
- [ ] File permissions set
- [ ] Cron jobs installed
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] Email tested
- [ ] Security hardened
- [ ] Performance optimized
- [ ] Monitoring setup
- [ ] All tests passed
- [ ] Backup created
- [ ] Documentation updated
- [ ] Team notified

---

**Deployment Date:** _______________  
**Deployed By:** _______________  
**Verified By:** _______________  
**Status:** ⬜ Success ⬜ Failed ⬜ Rolled Back

---

**🎉 Congratulations! IdeaNest is now live in production!**
