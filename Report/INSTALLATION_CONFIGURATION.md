# Installation and Configuration Guide

## 📋 Overview

This comprehensive guide covers the complete installation and configuration process for the IdeaNest platform, from initial system setup to production deployment. It includes step-by-step instructions for different operating systems and deployment scenarios.

## 🖥️ System Preparation

### Ubuntu/Debian Installation

Note: This for only Fedora/Pop OS! Linux user.
#### Step 1: Update System Packages
```bash
# Update package repositories
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common
```

#### Step 2: Install Apache Web Server
```bash
# Install Apache
sudo apt install -y apache2

# Enable required modules
sudo a2enmod rewrite ssl headers

# Start and enable Apache
sudo systemctl start apache2
sudo systemctl enable apache2

# Check Apache status
sudo systemctl status apache2
```

#### Step 3: Install PHP 8.2+
```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and required extensions
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
    php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip \
    php8.2-json php8.2-fileinfo php8.2-session libapache2-mod-php8.2

# Verify PHP installation
php -v
```

#### Step 4: Install MySQL/MariaDB
```bash
# Install MariaDB
sudo apt install -y mariadb-server mariadb-client

# Secure MySQL installation
sudo mysql_secure_installation

# Start and enable MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

## 🗄️ Database Setup

### Step 1: Create Database and User
```bash
# Login to MySQL as root
sudo mysql -u root -p

# Create database
CREATE DATABASE ictmu6ya_ideanest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create dedicated user
CREATE USER 'ictmu6ya_ideanest'@'localhost' IDENTIFIED BY 'ictmu6ya_ideanest';

# Grant privileges
GRANT ALL PRIVILEGES ON ictmu6ya_ideanest.* TO 'ictmu6ya_ideanest'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;
```

### Step 2: Import Database Schema
```bash
# Navigate to project directory
cd /var/www/html/IdeaNest

# Import database schema
mysql -u ictmu6ya_ideanest -p ictmu6ya_ideanest < db/ictmu6ya_ideanest.sql

# Verify import
mysql -u ictmu6ya_ideanest -p -e "USE ictmu6ya_ideanest; SHOW TABLES;"
```

### Step 3: Database Configuration Optimization
```sql
-- Connect to MySQL and optimize settings
mysql -u root -p

-- Performance optimization
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456;     -- 256MB
SET GLOBAL query_cache_size = 134217728;         -- 128MB
SET GLOBAL max_connections = 200;

-- Security settings
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
```

## 📁 Application Installation

### Step 1: Clone Repository
```bash
# Navigate to web root
cd /var/www/html

# Clone the repository
git clone https://github.com/yourusername/IdeaNest.git
cd IdeaNest

# Set proper ownership
sudo chown -R www-data:www-data /var/www/html/IdeaNest
```

### Step 2: Install Composer Dependencies
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install project dependencies
composer install --no-dev --optimize-autoloader

# Verify installation
composer show
```

### Step 3: Environment Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit environment file
nano .env
```

#### Environment Configuration (.env)
```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=ictmu6ya_ideanest
DB_USERNAME=ictmu6ya_ideanest
DB_PASS=secure_password_here
DB_CHARSET=utf8mb4

# Application Settings
APP_NAME="IdeaNest"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=UTC

# GitHub Integration
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="IdeaNest Platform"

# Security
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
CSRF_TOKEN_LIFETIME=3600

# File Upload
MAX_FILE_SIZE=10485760  # 10MB in bytes
UPLOAD_PATH=user/forms/uploads/
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,zip,mp4,avi,mov,ppt,pptx,doc,docx
```

### Step 4: Database Connection Configuration
```php
<?php
// Edit Login/Login/db.php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'ictmu6ya_ideanest';
$username = $_ENV['DB_USERNAME'] ?? 'ictmu6ya_ideanest';
$password = $_ENV['DB_PASSWORD'] ?? 'secure_password_here';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE {$charset}_unicode_ci"
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please check configuration.");
}
?>
```

## 🔧 Web Server Configuration

### Apache Virtual Host Configuration
```apache
# Create virtual host file
sudo nano /etc/apache2/sites-available/ideanest.conf

<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/IdeaNest
    
    <Directory /var/www/html/IdeaNest>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options SAMEORIGIN
        Header always set X-XSS-Protection "1; mode=block"
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/ideanest_error.log
    CustomLog ${APACHE_LOG_DIR}/ideanest_access.log combined
    
    # Redirect to HTTPS (after SSL setup)
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

# SSL Virtual Host (after certificate installation)
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/IdeaNest
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    <Directory /var/www/html/IdeaNest>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ideanest_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/ideanest_ssl_access.log combined
</VirtualHost>
```

### Enable Site and Restart Apache
```bash
# Enable the site
sudo a2ensite ideanest.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## 🔒 SSL Certificate Installation

### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Test automatic renewal
sudo certbot renew --dry-run

# Setup automatic renewal cron job
sudo crontab -e
# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

### Manual SSL Certificate Installation
```bash
# If using a purchased SSL certificate
sudo mkdir -p /etc/ssl/certs/ideanest
sudo mkdir -p /etc/ssl/private/ideanest

# Copy certificate files
sudo cp your_certificate.crt /etc/ssl/certs/ideanest/
sudo cp your_private.key /etc/ssl/private/ideanest/
sudo cp your_ca_bundle.crt /etc/ssl/certs/ideanest/

# Set proper permissions
sudo chmod 644 /etc/ssl/certs/ideanest/*
sudo chmod 600 /etc/ssl/private/ideanest/*
```

## 📁 File Permissions Setup

### Set Proper Permissions
```bash
# Navigate to project directory
cd /var/www/html/IdeaNest

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set executable permissions for scripts
chmod +x cron/*.sh

# Set writable permissions for upload directories
chmod 755 user/uploads/
chmod 755 user/forms/uploads/
chmod 755 logs/

# Create logs directory if it doesn't exist
mkdir -p logs
chmod 755 logs/

# Set ownership
sudo chown -R www-data:www-data /var/www/html/IdeaNest

# Secure sensitive files
chmod 600 .env
chmod 644 .htaccess
```

### .htaccess Security Configuration
```apache
# Main .htaccess file
RewriteEngine On
RewriteBase /

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent access to sensitive files
<FilesMatch "\.(env|log|sql|md|json|lock)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent access to directories
Options -Indexes

# File upload security
<Directory "user/forms/uploads/">
    <FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
        Order Allow,Deny
        Deny from all
    </FilesMatch>
</Directory>

# Cache control for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
```

## 📧 Email System Configuration

### SMTP Configuration Setup
```bash
# Navigate to admin panel after installation
# Go to: https://yourdomain.com/Admin/settings.php
# Configure SMTP settings through the web interface
```

### Gmail SMTP Configuration
```php
// Example Gmail configuration
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your_email@gmail.com',
    'smtp_password' => 'your_app_password', // Use App Password, not regular password
    'smtp_secure' => 'tls',
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'IdeaNest Platform'
];
```

### Cron Jobs Setup
```bash
# Setup email cron jobs
cd /var/www/html/IdeaNest/cron

# Make scripts executable
chmod +x setup_cron.sh manage_cron.sh

# Run setup script
./setup_cron.sh

# Verify cron jobs
crontab -l

# Manual cron setup (if script fails)
crontab -e
# Add these lines:
0 9 * * 0 /usr/bin/php /var/www/html/IdeaNest/cron/weekly_notifications.php
*/5 * * * * /usr/bin/php /var/www/html/IdeaNest/cron/mentor_email_cron.php
```

## 🔗 External API Configuration

### GitHub Integration Setup
```bash
# 1. Create GitHub OAuth App
# Go to: https://github.com/settings/applications/new
# Application name: IdeaNest Platform
# Homepage URL: https://yourdomain.com
# Authorization callback URL: https://yourdomain.com/user/github_callback.php

# 2. Update .env file with GitHub credentials
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
```

### Google OAuth Setup
```bash
# 1. Create Google Cloud Console Project
# Go to: https://console.cloud.google.com/
# Create new project: IdeaNest Platform

# 2. Enable Google+ API
# Go to: APIs & Services > Library
# Search and enable: Google+ API

# 3. Create OAuth 2.0 Credentials
# Go to: APIs & Services > Credentials
# Create OAuth 2.0 Client ID
# Authorized JavaScript origins: https://yourdomain.com
# Authorized redirect URIs: https://yourdomain.com/Login/Login/google_callback.php

# 4. Update .env file
GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

## 🧪 Testing Installation

### Basic Functionality Tests
```bash
# Test database connection
php -r "
require_once 'Login/Login/db.php';
echo 'Database connection: ' . (isset(\$pdo) ? 'SUCCESS' : 'FAILED') . PHP_EOL;
"

# Test file permissions
php -r "
echo 'Upload directory writable: ' . (is_writable('user/forms/uploads/') ? 'YES' : 'NO') . PHP_EOL;
echo 'Logs directory writable: ' . (is_writable('logs/') ? 'YES' : 'NO') . PHP_EOL;
"

# Test email configuration
php cron/test_email.php

# Run test suite
composer test
```

### Web Interface Tests
```bash
# Test main pages
curl -I https://yourdomain.com/
curl -I https://yourdomain.com/Login/Login/login.php
curl -I https://yourdomain.com/user/index.php

# Test SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

## 🔧 Performance Optimization

### PHP Configuration
```ini
# Edit /etc/php/8.2/apache2/php.ini

# Memory and execution limits
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

# File upload settings
file_uploads = On
upload_max_filesize = 10M
post_max_size = 50M
max_file_uploads = 20

# Session settings
session.gc_maxlifetime = 7200
session.cookie_lifetime = 0
session.cookie_secure = 1
session.cookie_httponly = 1

# OPcache settings
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
```

### MySQL Optimization
```sql
# Edit /etc/mysql/mariadb.conf.d/50-server.cnf

[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M
query_cache_type = 1
max_connections = 200
thread_cache_size = 8
table_open_cache = 2000
```

## 📊 Monitoring Setup

### Log Rotation Configuration
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/ideanest

/var/www/html/IdeaNest/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

### System Monitoring
```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Setup basic monitoring script
cat > /usr/local/bin/ideanest-monitor.sh << 'EOF'
#!/bin/bash
echo "=== IdeaNest System Status ==="
echo "Date: $(date)"
echo "Uptime: $(uptime)"
echo "Disk Usage: $(df -h /var/www/html/IdeaNest | tail -1)"
echo "Memory Usage: $(free -h | grep Mem)"
echo "Apache Status: $(systemctl is-active apache2)"
echo "MySQL Status: $(systemctl is-active mariadb)"
echo "================================"
EOF

chmod +x /usr/local/bin/ideanest-monitor.sh
```

## 🔍 Troubleshooting

### Common Issues and Solutions

#### Database Connection Issues
```bash
# Check MySQL service
sudo systemctl status mariadb

# Test database connection
mysql -u ictmu6ya_ideanest -p ictmu6ya_ideanest -e "SELECT 1;"

# Check database permissions
mysql -u root -p -e "SHOW GRANTS FOR 'ideanest_user'@'ictmu.in';"
```

#### File Permission Issues
```bash
# Reset permissions
sudo chown -R www-data:www-data /var/www/html/IdeaNest
sudo chmod -R 755 /var/www/html/IdeaNest
sudo chmod 755 user/uploads/ user/forms/uploads/ logs/
```

#### Apache Configuration Issues
```bash
# Test Apache configuration
sudo apache2ctl configtest

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Restart Apache
sudo systemctl restart apache2
```

#### SSL Certificate Issues
```bash
# Test SSL certificate
openssl x509 -in /etc/letsencrypt/live/yourdomain.com/fullchain.pem -text -noout

# Renew Let's Encrypt certificate
sudo certbot renew --force-renewal
```

This comprehensive installation and configuration guide ensures a proper setup of the IdeaNest platform with security, performance, and reliability considerations.
