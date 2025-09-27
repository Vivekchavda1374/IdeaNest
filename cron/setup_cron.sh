#!/bin/bash
# IdeaNest Production Cron Setup
# Domain: https://ictmu.in/hcd/IdeaNest/
# Weekly Sunday notifications - Runs every Sunday at 9:00 AM

echo "Setting up IdeaNest production cron jobs..."

# Production paths
PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"
PHP_PATH="/opt/lampp/bin/php"
LOG_PATH="$PROJECT_PATH/logs"

# Create log directory if it doesn't exist
mkdir -p "$LOG_PATH"
chmod 755 "$LOG_PATH"

# Remove existing IdeaNest cron jobs to avoid duplicates
echo "Removing existing cron jobs..."
crontab -l 2>/dev/null | grep -v "IdeaNest/cron/" | crontab -

# Add weekly notifications cron job (every Sunday at 9:00 AM)
echo "Adding weekly notifications cron job..."
(crontab -l 2>/dev/null; echo "0 9 * * 0 $PHP_PATH $PROJECT_PATH/cron/weekly_notifications.php >> $LOG_PATH/weekly_notifications.log 2>&1") | crontab -

# Add mentor email cron job (every 5 minutes)
echo "Adding mentor email cron job..."
(crontab -l 2>/dev/null; echo "*/5 * * * * $PHP_PATH $PROJECT_PATH/cron/mentor_email_cron.php >> $LOG_PATH/mentor_emails.log 2>&1") | crontab -

# Add cleanup cron job (daily at 2:00 AM)
echo "Adding cleanup cron job..."
(crontab -l 2>/dev/null; echo "0 2 * * * find $LOG_PATH -name '*.log' -mtime +30 -delete") | crontab -

echo "Production cron jobs added successfully!"
echo "Weekly notifications: Every Sunday at 9:00 AM"
echo "Mentor emails: Every 5 minutes"
echo "Log cleanup: Daily at 2:00 AM"
echo "Log directory: $LOG_PATH"

# Test the cron job setup
echo ""
echo "Testing cron job configuration..."
crontab -l | grep "IdeaNest/cron/"

if [ $? -eq 0 ]; then
    echo "✓ Production cron jobs successfully configured"
    echo "✓ Logs will be stored in: $LOG_PATH"
else
    echo "✗ Failed to configure cron jobs"
    exit 1
fi

# Set proper permissions
chmod +x "$PROJECT_PATH/cron/"*.php
chmod +x "$PROJECT_PATH/cron/"*.sh

echo "✓ File permissions set correctly"
echo "Production cron setup completed successfully!"