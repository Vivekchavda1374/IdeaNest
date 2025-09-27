# IdeaNest Production Deployment Guide

## Production Domain: https://ictmu.in/hcd/IdeaNest/

### ✅ Pre-Deployment Checklist

#### 1. Environment Configuration
- [x] `.env` file created with production settings
- [x] Database credentials updated for production
- [x] Email SMTP configuration verified
- [x] Google OAuth settings configured for production domain
- [x] Security headers and CSP policies updated

#### 2. Database Setup
- [ ] Production database `ictmu6ya_ideanest` created
- [ ] Database schema imported from `db/ideanest.sql`
- [ ] Database user permissions configured
- [ ] Test database connection

#### 3. File Permissions
```bash
chmod 755 user/uploads/
chmod 755 user/forms/uploads/
chmod 755 logs/
chmod 644 .env
chmod +x cron/*.sh
chmod +x cron/*.php
```

#### 4. Security Configuration
- [x] `.htaccess` updated with production security settings
- [x] Error reporting disabled for production
- [x] Security headers configured
- [x] File upload validation enhanced
- [x] CSRF protection enabled

#### 5. Google OAuth Setup
**Required Actions:**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Navigate to APIs & Services > Credentials
4. Edit OAuth 2.0 Client ID
5. Add authorized JavaScript origins:
   - `https://ictmu.in`
   - `https://ictmu.in/hcd/IdeaNest`
6. Add authorized redirect URIs:
   - `https://ictmu.in/hcd/IdeaNest/Login/Login/google_callback.php`

#### 6. Cron Jobs Setup
```bash
cd /opt/lampp/htdocs/IdeaNest/cron
chmod +x setup_cron.sh
./setup_cron.sh
```

#### 7. Email System Configuration
- [ ] SMTP credentials verified in `.env`
- [ ] Test email sending functionality
- [ ] Verify email templates are working

#### 8. GitHub Integration
- [ ] GitHub API token configured (if using)
- [ ] Test GitHub profile sync functionality

### 🚀 Deployment Steps

#### Step 1: Environment Setup
```bash
# Navigate to project directory
cd /opt/lampp/htdocs/IdeaNest

# Set proper file permissions
chmod 755 user/uploads/ user/forms/uploads/ logs/
chmod 644 .env
chmod +x cron/*.sh cron/*.php

# Create logs directory
mkdir -p logs
chmod 755 logs
```

#### Step 2: Database Configuration
```sql
-- Create production database (if not exists)
CREATE DATABASE IF NOT EXISTS ictmu6ya_ideanest;

-- Import schema
mysql -u ictmu6ya_ideanest -p ictmu6ya_ideanest < db/ideanest.sql
```

#### Step 3: Test Core Functionality
1. **Login System**: Test regular login, Google OAuth
2. **File Uploads**: Test project submission with files
3. **Email System**: Test notification emails
4. **Admin Panel**: Verify admin access and functionality
5. **Mentor System**: Test mentor dashboard and features

#### Step 4: Performance Optimization
```bash
# Enable Apache modules (if not already enabled)
sudo a2enmod rewrite
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod headers

# Restart Apache
sudo systemctl restart apache2
```

### 🔧 Production Configuration Files Updated

#### Core Files:
- `.env` - Production environment variables
- `.htaccess` - Security headers and optimizations
- `config/security.php` - Enhanced security settings
- `config/email_config.php` - Email configuration
- `Login/Login/google_config.php` - Google OAuth for production
- `Login/Login/db.php` - Database connection with error handling

#### Cron Jobs:
- `cron/setup_cron.sh` - Production cron setup
- Weekly notifications: Every Sunday at 9:00 AM
- Mentor emails: Every 5 minutes
- Log cleanup: Daily at 2:00 AM

### 🔒 Security Features Enabled

1. **HTTPS Enforcement**: Redirect HTTP to HTTPS
2. **Security Headers**: XSS protection, content type options, frame options
3. **Content Security Policy**: Restrict resource loading
4. **Session Security**: Secure cookies, HTTP-only flags
5. **File Upload Security**: MIME type validation, size limits
6. **CSRF Protection**: Token-based validation
7. **Rate Limiting**: Login attempt protection
8. **Input Sanitization**: XSS prevention

### 📊 Monitoring & Logs

#### Log Files Location: `/opt/lampp/htdocs/IdeaNest/logs/`
- `error.log` - PHP errors and application errors
- `weekly_notifications.log` - Weekly email notifications
- `mentor_emails.log` - Mentor email system logs

#### Monitoring Commands:
```bash
# Check error logs
tail -f /opt/lampp/htdocs/IdeaNest/logs/error.log

# Check cron job status
crontab -l | grep IdeaNest

# Check file permissions
ls -la /opt/lampp/htdocs/IdeaNest/
```

### 🚨 Troubleshooting

#### Common Issues:

1. **403/500 Errors**
   - Check file permissions
   - Verify `.htaccess` configuration
   - Check Apache error logs

2. **Google OAuth Issues**
   - Verify client ID in Google Console
   - Check authorized domains and redirect URIs
   - Ensure HTTPS is working

3. **Email Not Working**
   - Verify SMTP credentials in `.env`
   - Check Gmail app password (not regular password)
   - Test with `cron/weekly_notifications.php`

4. **File Upload Issues**
   - Check upload directory permissions (755)
   - Verify PHP upload limits in `.htaccess`
   - Check available disk space

5. **Database Connection Issues**
   - Verify credentials in `.env`
   - Check database server status
   - Test connection with MySQL client

### 📞 Support

For production issues:
- **Email**: ideanest.ict@gmail.com
- **Check logs**: `/opt/lampp/htdocs/IdeaNest/logs/`
- **GitHub Issues**: Create issue for bug reports

### 🔄 Backup Strategy

#### Recommended Backup Schedule:
1. **Database**: Daily automated backup
2. **Files**: Weekly backup of uploads directory
3. **Configuration**: Version control for code changes

#### Backup Commands:
```bash
# Database backup
mysqldump -u ictmu6ya_ideanest -p ictmu6ya_ideanest > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz user/uploads/ user/forms/uploads/
```

---

**Production URL**: https://ictmu.in/hcd/IdeaNest/
**Last Updated**: $(date)
**Environment**: Production