#!/bin/bash

###############################################################################
# IdeaNest Production Deployment Script
# This script automates the deployment process for production environment
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="IdeaNest"
BACKUP_DIR="backups"
LOG_FILE="logs/deployment.log"

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    warning "Running as root. Consider using a non-root user with sudo."
fi

# Start deployment
log "========================================="
log "Starting $PROJECT_NAME Production Deployment"
log "========================================="

# Step 1: Pre-deployment checks
log "Step 1: Running pre-deployment checks..."

# Check if .env exists
if [ ! -f ".env" ]; then
    error ".env file not found! Copy .env.production.example to .env and configure it."
fi

# Check if APP_ENV is set to production
if ! grep -q "APP_ENV=production" .env; then
    error "APP_ENV is not set to 'production' in .env file!"
fi

# Check if APP_DEBUG is false
if ! grep -q "APP_DEBUG=false" .env; then
    warning "APP_DEBUG should be set to 'false' in production!"
fi

log "Pre-deployment checks passed ✓"

# Step 2: Create backup
log "Step 2: Creating backup..."

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="$BACKUP_DIR/backup_$TIMESTAMP"

mkdir -p "$BACKUP_PATH"

# Backup database
if [ -f ".env" ]; then
    DB_NAME=$(grep DB_NAME .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    if [ ! -z "$DB_NAME" ]; then
        log "Backing up database: $DB_NAME"
        mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/database.sql" 2>/dev/null || warning "Database backup failed"
    fi
fi

# Backup uploads
if [ -d "user/uploads" ]; then
    log "Backing up user uploads..."
    tar -czf "$BACKUP_PATH/uploads.tar.gz" user/uploads/ user/forms/uploads/ 2>/dev/null || warning "Upload backup failed"
fi

# Backup .env
cp .env "$BACKUP_PATH/.env.backup"

log "Backup created at: $BACKUP_PATH ✓"

# Step 3: Set file permissions
log "Step 3: Setting file permissions..."

# Application files (read-only)
find . -type f -not -path "./user/uploads/*" -not -path "./user/forms/uploads/*" -not -path "./logs/*" -not -path "./user/profile_pictures/*" -exec chmod 644 {} \; 2>/dev/null || true
find . -type d -not -path "./user/uploads/*" -not -path "./user/forms/uploads/*" -not -path "./logs/*" -not -path "./user/profile_pictures/*" -exec chmod 755 {} \; 2>/dev/null || true

# Writable directories
chmod -R 775 user/uploads/ 2>/dev/null || true
chmod -R 775 user/forms/uploads/ 2>/dev/null || true
chmod -R 775 logs/ 2>/dev/null || true
chmod -R 775 user/profile_pictures/ 2>/dev/null || true

# Secure configuration files
chmod 600 .env 2>/dev/null || true
chmod 600 config/*.php 2>/dev/null || true

# Executable scripts
chmod 750 cron/*.php 2>/dev/null || true
chmod 750 scripts/*.sh 2>/dev/null || true

log "File permissions set ✓"

# Step 4: Copy production .htaccess
log "Step 4: Configuring Apache..."

if [ -f ".htaccess.production" ]; then
    cp .htaccess.production .htaccess
    log "Production .htaccess configured ✓"
else
    warning ".htaccess.production not found, skipping..."
fi

# Step 5: Clear caches
log "Step 5: Clearing caches..."

# Clear PHP OpCache (if available)
if command -v php &> /dev/null; then
    php -r "if(function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true
fi

# Clear session files older than 24 hours
find /tmp -name "sess_*" -mtime +1 -delete 2>/dev/null || true

log "Caches cleared ✓"

# Step 6: Create necessary directories
log "Step 6: Creating necessary directories..."

mkdir -p logs
mkdir -p user/uploads
mkdir -p user/forms/uploads
mkdir -p user/profile_pictures
mkdir -p backups

# Create .htaccess in sensitive directories
echo "Require all denied" > logs/.htaccess
echo "Require all denied" > config/.htaccess
echo "Require all denied" > backups/.htaccess

log "Directories created ✓"

# Step 7: Verify critical files
log "Step 7: Verifying critical files..."

CRITICAL_FILES=(
    "index.php"
    "Login/Login/login.php"
    "Login/Login/db.php"
    "config/config.php"
    "config/security.php"
    "config/production.php"
    ".env"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        error "Critical file missing: $file"
    fi
done

log "Critical files verified ✓"

# Step 8: Test database connection
log "Step 8: Testing database connection..."

php -r "
require_once 'Login/Login/db.php';
if (\$conn && !\$conn->connect_error) {
    echo 'Database connection successful';
    exit(0);
} else {
    echo 'Database connection failed';
    exit(1);
}
" || error "Database connection test failed!"

log "Database connection test passed ✓"

# Step 9: Security checks
log "Step 9: Running security checks..."

# Check if display_errors is off
if grep -r "display_errors.*On" . --include="*.php" 2>/dev/null; then
    warning "Found display_errors=On in PHP files. Should be Off in production!"
fi

# Check for debug code
if grep -r "var_dump\|print_r\|var_export" . --include="*.php" --exclude-dir=vendor 2>/dev/null | grep -v "//"; then
    warning "Found debug functions (var_dump, print_r) in code!"
fi

# Check for hardcoded credentials
if grep -r "password.*=.*['\"].*['\"]" . --include="*.php" --exclude-dir=vendor --exclude="*.example.php" 2>/dev/null | grep -v "//"; then
    warning "Possible hardcoded credentials found!"
fi

log "Security checks completed ✓"

# Step 10: Setup cron jobs (optional)
log "Step 10: Cron jobs setup..."

info "To setup cron jobs, add these lines to crontab (crontab -e):"
echo ""
echo "# IdeaNest Weekly Notifications (Every Monday at 9:00 AM)"
echo "0 9 * * 1 /usr/bin/php $(pwd)/cron/weekly_notifications.php >> $(pwd)/logs/cron.log 2>&1"
echo ""
echo "# IdeaNest Mentor Emails (Daily at 10:00 AM)"
echo "0 10 * * * /usr/bin/php $(pwd)/cron/send_mentor_emails.php >> $(pwd)/logs/cron.log 2>&1"
echo ""

# Step 11: Final checks
log "Step 11: Running final checks..."

# Check if production.php is loaded
php -r "
if (file_exists('config/production.php')) {
    echo 'Production config found';
} else {
    echo 'Production config missing';
    exit(1);
}
" || error "Production configuration missing!"

log "Final checks passed ✓"

# Deployment summary
log "========================================="
log "Deployment Summary"
log "========================================="
log "Backup Location: $BACKUP_PATH"
log "Environment: Production"
log "PHP Version: $(php -v | head -n 1)"
log "Deployment Time: $(date)"
log "========================================="
log "✓ Deployment completed successfully!"
log "========================================="

info "Next steps:"
echo "1. Test the application: https://yourdomain.com"
echo "2. Check error logs: tail -f logs/error.log"
echo "3. Monitor production status: https://yourdomain.com/production_status.php"
echo "4. Setup SSL certificate if not already done"
echo "5. Configure cron jobs as shown above"
echo "6. Review security settings"
echo ""
info "To rollback, use: ./scripts/rollback.sh $TIMESTAMP"

exit 0
