# Deployment and Operations Guide

## 🚀 Overview

This guide covers deployment strategies, operational procedures, monitoring, and maintenance for the IdeaNest platform across different environments.

## 📋 Deployment Environments

### Development Environment
- **Purpose**: Local development and testing
- **Requirements**: XAMPP/LAMPP stack
- **Database**: Local MySQL/MariaDB
- **Email**: Test configuration or disabled

### Staging Environment
- **Purpose**: Pre-production testing
- **Requirements**: Production-like setup
- **Database**: Staging database with test data
- **Email**: Test SMTP configuration

### Production Environment
- **Purpose**: Live platform
- **Requirements**: Full security and performance optimization
- **Database**: Production database with backups
- **Email**: Production SMTP with monitoring

## 🔧 Deployment Strategies

### 1. Manual Deployment

#### Prerequisites
```bash
# System requirements
- PHP 8.2+
- MySQL 10.4.28-MariaDB+
- Apache 2.4+ with mod_rewrite
- Composer
- Git
```

#### Deployment Steps
```bash
# 1. Clone repository
git clone https://github.com/yourusername/IdeaNest.git
cd IdeaNest

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Environment setup
cp .env.example .env
nano .env

# 4. Database setup
mysql -u ictmu6ya_ideanest -p -e "CREATE DATABASE ictmu6ya_ideanest;"
mysql -u ictmu6ya_ideanest -p ictmu6ya_ideanest < db/ideanest.sql

# 5. Set permissions
chmod 755 user/uploads/ user/forms/uploads/ logs/
chmod 600 .env
chown -R www-data:www-data .

# 6. Configure web server
sudo a2ensite ideanest.conf
sudo systemctl restart apache2

# 7. Setup cron jobs
cd cron && ./setup_cron.sh
```

### 2. Automated Deployment

#### CI/CD Pipeline Configuration
```yaml
# .github/workflows/deploy.yml
name: Deploy IdeaNest

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run tests
        run: composer test
        
      - name: Deploy to server
        run: |
          rsync -avz --delete \
            --exclude='.env' \
            --exclude='user/uploads/' \
            --exclude='logs/' \
            ./ user@server:/var/www/html/IdeaNest/
```

#### Deployment Script
```bash
#!/bin/bash
# deploy.sh

set -e

DEPLOY_DIR="/var/www/html/IdeaNest"
BACKUP_DIR="/var/backups/ideanest"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Starting deployment at $(date)"

# Create backup
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" -C "$DEPLOY_DIR" .

# Pull latest code
cd "$DEPLOY_DIR"
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations if any
php scripts/migrate.php

# Clear cache
php scripts/clear_cache.php

# Set permissions
chmod 755 user/uploads/ user/forms/uploads/ logs/
chown -R www-data:www-data .

# Restart services
sudo systemctl restart apache2

echo "Deployment completed at $(date)"
```

## 🔄 Environment Management

### Environment Configuration
```bash
# Development (.env.dev)
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=ictmu6ya_ideanest_dev
MAIL_MAILER=log

# Staging (.env.staging)
APP_ENV=staging
APP_DEBUG=false
DB_HOST=staging-db.example.com
DB_NAME=ictmu6ya_ideanest_staging
MAIL_MAILER=smtp

# Production (.env.prod)
APP_ENV=production
APP_DEBUG=false
DB_HOST=prod-db.example.com
DB_NAME=ictmu6ya_ideanest_prod
MAIL_MAILER=smtp
```

### Database Migration Management
```php
<?php
// scripts/migrate.php
require_once 'Login/Login/db.php';

$migrations = [
    '001_add_email_templates.sql',
    '002_add_performance_indexes.sql',
    '003_add_audit_logs.sql'
];

foreach ($migrations as $migration) {
    if (!isMigrationApplied($migration)) {
        executeMigration($migration);
        markMigrationApplied($migration);
        echo "Applied migration: $migration\n";
    }
}
?>
```

## 📊 Monitoring and Logging

### Application Monitoring
```bash
# System monitoring script
#!/bin/bash
# monitor.sh

LOG_FILE="/var/log/ideanest/monitor.log"
ALERT_EMAIL="admin@example.com"

# Check disk space
DISK_USAGE=$(df /var/www/html/IdeaNest | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): Disk usage high: ${DISK_USAGE}%" >> $LOG_FILE
    echo "Disk usage alert: ${DISK_USAGE}%" | mail -s "IdeaNest Alert" $ALERT_EMAIL
fi

# Check database connections
DB_CONNECTIONS=$(mysql -e "SHOW STATUS LIKE 'Threads_connected';" | tail -1 | awk '{print $2}')
if [ $DB_CONNECTIONS -gt 50 ]; then
    echo "$(date): High DB connections: $DB_CONNECTIONS" >> $LOG_FILE
fi

# Check Apache status
if ! systemctl is-active --quiet apache2; then
    echo "$(date): Apache is down" >> $LOG_FILE
    systemctl restart apache2
fi
```

### Log Management
```bash
# Log rotation configuration
# /etc/logrotate.d/ideanest
/var/www/html/IdeaNest/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

### Performance Monitoring
```php
<?php
// includes/performance_monitor.php
class PerformanceMonitor {
    private $startTime;
    private $startMemory;
    
    public function start() {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
    
    public function end($operation = '') {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $this->startTime;
        $memoryUsage = $endMemory - $this->startMemory;
        
        error_log("Performance: $operation - Time: {$executionTime}s, Memory: {$memoryUsage} bytes");
        
        // Alert if performance is poor
        if ($executionTime > 5.0) {
            $this->sendAlert("Slow operation: $operation took {$executionTime}s");
        }
    }
}
?>
```

## 🔒 Security Operations

### Security Monitoring
```bash
# Security audit script
#!/bin/bash
# security_audit.sh

AUDIT_LOG="/var/log/ideanest/security_audit.log"

echo "$(date): Starting security audit" >> $AUDIT_LOG

# Check file permissions
find /var/www/html/IdeaNest -type f -perm /o+w -exec echo "World-writable file: {}" \; >> $AUDIT_LOG

# Check for suspicious files
find /var/www/html/IdeaNest -name "*.php" -exec grep -l "eval\|base64_decode\|shell_exec" {} \; >> $AUDIT_LOG

# Check failed login attempts
grep "Failed login" /var/www/html/IdeaNest/logs/*.log | wc -l >> $AUDIT_LOG

# Check SSL certificate expiry
openssl x509 -in /etc/ssl/certs/ideanest.crt -noout -dates >> $AUDIT_LOG

echo "$(date): Security audit completed" >> $AUDIT_LOG
```

### Backup Operations
```bash
# Automated backup script
#!/bin/bash
# backup.sh

BACKUP_DIR="/var/backups/ideanest"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Database backup
mysqldump -u ictmu6ya_ideanest -p ictmu6ya_ideanest > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql"
gzip "$BACKUP_DIR/db_backup_$TIMESTAMP.sql"

# File backup
tar -czf "$BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz" \
    --exclude='logs/*' \
    --exclude='vendor/*' \
    /var/www/html/IdeaNest/

# Upload to cloud storage (optional)
aws s3 cp "$BACKUP_DIR/" s3://ideanest-backups/ --recursive

# Clean old backups
find "$BACKUP_DIR" -name "*.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $TIMESTAMP"
```

## 🔧 Maintenance Operations

### Routine Maintenance Tasks
```bash
# Daily maintenance script
#!/bin/bash
# daily_maintenance.sh

# Clean temporary files
find /tmp -name "ideanest_*" -mtime +1 -delete

# Optimize database tables
mysql -e "OPTIMIZE TABLE projects, blog, register, admin_approved_projects;"

# Clear application cache
rm -rf /var/www/html/IdeaNest/cache/*

# Update system packages (staging/dev only)
if [ "$ENVIRONMENT" != "production" ]; then
    apt update && apt upgrade -y
fi

# Generate daily reports
php /var/www/html/IdeaNest/scripts/daily_report.php
```

### Database Maintenance
```sql
-- Weekly database maintenance
-- Run every Sunday at 2 AM

-- Clean old logs
DELETE FROM notification_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM mentor_email_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM user_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);

-- Optimize tables
OPTIMIZE TABLE notification_logs, mentor_email_logs, user_activity_log;

-- Update statistics
ANALYZE TABLE projects, blog, register;

-- Check for orphaned records
SELECT COUNT(*) as orphaned_projects 
FROM projects p 
LEFT JOIN register r ON p.user_id = r.id 
WHERE r.id IS NULL;
```

## 📈 Performance Optimization

### Caching Strategy
```php
<?php
// includes/cache_manager.php
class CacheManager {
    private $cacheDir = '/var/cache/ideanest/';
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file) && (time() - filemtime($file)) < 3600) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }
    
    public function set($key, $data) {
        $file = $this->cacheDir . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }
    
    public function clear() {
        array_map('unlink', glob($this->cacheDir . '*.cache'));
    }
}
?>
```

### Database Optimization
```sql
-- Performance indexes
CREATE INDEX idx_projects_status_created ON projects(status, created_at);
CREATE INDEX idx_blog_created_at ON blog(created_at);
CREATE INDEX idx_register_role_status ON register(role, status);
CREATE INDEX idx_mentor_email_queue_priority ON mentor_email_queue(priority, created_at);

-- Partitioning for large tables
ALTER TABLE notification_logs 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## 🚨 Incident Response

### Incident Response Procedures
```bash
# Incident response script
#!/bin/bash
# incident_response.sh

INCIDENT_TYPE=$1
SEVERITY=$2

case $INCIDENT_TYPE in
    "database_down")
        # Restart database service
        systemctl restart mariadb
        # Switch to read-only mode
        touch /var/www/html/IdeaNest/maintenance.flag
        ;;
    "high_load")
        # Enable maintenance mode
        cp maintenance.html index.html
        # Scale resources if cloud deployment
        ;;
    "security_breach")
        # Block suspicious IPs
        # Rotate credentials
        # Enable audit logging
        ;;
esac

# Send alerts
echo "Incident: $INCIDENT_TYPE (Severity: $SEVERITY)" | \
    mail -s "IdeaNest Incident Alert" admin@example.com
```

### Health Checks
```php
<?php
// health_check.php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    require_once 'Login/Login/db.php';
    $stmt = $pdo->query('SELECT 1');
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['checks']['database'] = 'error';
    $health['status'] = 'unhealthy';
}

// File system check
if (is_writable('user/uploads/')) {
    $health['checks']['filesystem'] = 'ok';
} else {
    $health['checks']['filesystem'] = 'error';
    $health['status'] = 'unhealthy';
}

// Email system check
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $health['checks']['email'] = 'ok';
} else {
    $health['checks']['email'] = 'error';
    $health['status'] = 'unhealthy';
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

## 📋 Deployment Checklist

### Pre-Deployment
- [ ] Code review completed
- [ ] Tests passing
- [ ] Security scan completed
- [ ] Database migrations prepared
- [ ] Backup created
- [ ] Maintenance window scheduled

### Deployment
- [ ] Deploy code to staging
- [ ] Run integration tests
- [ ] Deploy to production
- [ ] Run database migrations
- [ ] Update configuration
- [ ] Restart services

### Post-Deployment
- [ ] Health checks passing
- [ ] Performance metrics normal
- [ ] Error logs reviewed
- [ ] User acceptance testing
- [ ] Rollback plan ready
- [ ] Documentation updated

## 🔧 Troubleshooting Guide

### Common Issues

#### Database Connection Errors
```bash
# Check database status
systemctl status mariadb

# Test connection
mysql -u ideanest_user -p -e "SELECT 1;"

# Check configuration
grep -r "DB_" .env
```

#### File Permission Issues
```bash
# Reset permissions
chown -R www-data:www-data /var/www/html/IdeaNest
chmod 755 user/uploads/ user/forms/uploads/ logs/
chmod 600 .env
```

#### Email Delivery Problems
```bash
# Test SMTP connection
telnet smtp.gmail.com 587

# Check email logs
tail -f logs/email_errors.log

# Verify configuration
php -r "require 'config/email_config.php'; var_dump(\$emailConfig);"
```

#### Performance Issues
```bash
# Check system resources
htop
iotop
df -h

# Analyze slow queries
mysql -e "SELECT * FROM information_schema.processlist WHERE time > 10;"

# Check Apache status
apache2ctl status
```

## 📞 Support and Escalation

### Support Contacts
- **Level 1**: Technical Support - support@example.com
- **Level 2**: Development Team - dev@example.com  
- **Level 3**: System Administrators - sysadmin@example.com
- **Emergency**: On-call Engineer - +1-555-0123

### Escalation Matrix
- **P1 (Critical)**: System down, data loss - Immediate escalation
- **P2 (High)**: Major functionality impacted - 2 hour response
- **P3 (Medium)**: Minor issues, workarounds available - 8 hour response
- **P4 (Low)**: Enhancement requests - 24 hour response

---

This deployment and operations guide ensures reliable, secure, and maintainable operation of the IdeaNest platform across all environments.