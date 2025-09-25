# IdeaNest Production Setup Guide

## 🚀 Production Deployment

### 1. Environment Configuration

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Update .env with production values:**
   ```bash
   # Database Configuration
   DB_HOST=your_production_host
   DB_USERNAME=your_production_username
   DB_PASSWORD=your_secure_password
   DB_NAME=your_production_database
   
   # Email Configuration
   SMTP_HOST=smtp.gmail.com
   SMTP_USERNAME=your_production_email@gmail.com
   SMTP_PASSWORD=your_app_specific_password
   
   # Security Settings
   APP_ENV=production
   SSL_VERIFY_PEER=true
   SSL_VERIFY_PEER_NAME=true
   SSL_ALLOW_SELF_SIGNED=false
   ```

### 2. PHPMailer Setup

1. **Install PHPMailer via Composer:**
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Verify autoload path:**
   ```bash
   # Ensure vendor/autoload.php exists
   ls -la vendor/autoload.php
   ```

### 3. Email Configuration

1. **Gmail App Password Setup:**
   - Enable 2-Factor Authentication on Gmail
   - Generate App-Specific Password
   - Use App Password in SMTP_PASSWORD

2. **Alternative SMTP Providers:**
   ```bash
   # SendGrid
   SMTP_HOST=smtp.sendgrid.net
   SMTP_PORT=587
   
   # Mailgun
   SMTP_HOST=smtp.mailgun.org
   SMTP_PORT=587
   
   # AWS SES
   SMTP_HOST=email-smtp.us-east-1.amazonaws.com
   SMTP_PORT=587
   ```

### 4. Database Configuration

1. **Update admin_settings table:**
   ```sql
   INSERT INTO admin_settings (setting_key, setting_value) VALUES
   ('smtp_host', 'smtp.gmail.com'),
   ('smtp_port', '587'),
   ('smtp_username', 'your_email@gmail.com'),
   ('smtp_password', 'your_app_password'),
   ('smtp_secure', 'tls'),
   ('from_email', 'your_email@gmail.com')
   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
   ```

### 5. File Permissions

```bash
# Set proper permissions
chmod 755 user/uploads/
chmod 755 user/forms/uploads/
chmod 755 logs/
chmod 644 .env
chmod 600 config/email_config.php

# Secure sensitive files
chown www-data:www-data .env
chown www-data:www-data config/email_config.php
```

### 6. Cron Jobs Setup

```bash
# Weekly notifications (every Sunday at 9 AM)
0 9 * * 0 /usr/bin/php /path/to/IdeaNest/cron/weekly_notifications.php

# Mentor email processing (every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/IdeaNest/cron/mentor_email_cron.php
```

### 7. Security Hardening

1. **Hide sensitive files:**
   ```apache
   # Add to .htaccess
   <Files ".env">
       Order allow,deny
       Deny from all
   </Files>
   
   <Files "*.md">
       Order allow,deny
       Deny from all
   </Files>
   ```

2. **Error logging:**
   ```php
   # Add to php.ini or .htaccess
   log_errors = On
   error_log = /path/to/logs/php_errors.log
   display_errors = Off
   ```

### 8. Testing Email System

```bash
# Test weekly notifications
php cron/weekly_notifications.php

# Test mentor emails
php -r "
require_once 'mentor/email_system.php';
require_once 'Login/Login/db.php';
\$system = new MentorEmailSystem(\$conn, 1);
echo 'Email system loaded successfully';
"
```

### 9. Monitoring

1. **Check email logs:**
   ```sql
   SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 10;
   SELECT * FROM mentor_email_logs ORDER BY sent_at DESC LIMIT 10;
   ```

2. **Monitor error logs:**
   ```bash
   tail -f /path/to/logs/php_errors.log
   tail -f /var/log/apache2/error.log
   ```

### 10. Troubleshooting

#### Common PHPMailer Errors:

1. **"SMTP Error: Could not authenticate"**
   - Check SMTP credentials
   - Verify App Password for Gmail
   - Ensure 2FA is enabled

2. **"SMTP connect() failed"**
   - Check SMTP host and port
   - Verify firewall settings
   - Test network connectivity

3. **"SSL certificate problem"**
   - Set SSL_VERIFY_PEER=false for testing
   - Update CA certificates
   - Use proper SSL configuration

#### Email Not Sending:

```bash
# Check PHP mail configuration
php -m | grep -i mail

# Test SMTP connection
telnet smtp.gmail.com 587

# Check composer autoload
composer dump-autoload
```

### 11. Performance Optimization

1. **Email Queue Processing:**
   ```bash
   # Process email queue every minute
   * * * * * /usr/bin/php /path/to/IdeaNest/cron/process_email_queue.php
   ```

2. **Database Optimization:**
   ```sql
   # Add indexes for email logs
   CREATE INDEX idx_notification_logs_created_at ON notification_logs(created_at);
   CREATE INDEX idx_mentor_email_logs_sent_at ON mentor_email_logs(sent_at);
   ```

### 12. Backup Strategy

```bash
# Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# File backup
tar -czf ideanest_backup_$(date +%Y%m%d).tar.gz /path/to/IdeaNest/
```

## 🔒 Security Checklist

- [ ] Environment variables configured
- [ ] Database credentials secured
- [ ] SMTP credentials secured
- [ ] SSL certificates valid
- [ ] File permissions set correctly
- [ ] Error logging enabled
- [ ] Sensitive files protected
- [ ] Regular backups scheduled
- [ ] Monitoring in place

## 📞 Support

For production issues:
- Check error logs first
- Verify environment configuration
- Test email connectivity
- Contact: ideanest.ict@gmail.com