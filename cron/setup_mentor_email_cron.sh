#!/bin/bash

# Setup Mentor Email Cron Jobs
# This script sets up automated cron jobs for the mentor email system

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CRON_SCRIPT="$SCRIPT_DIR/mentor_email_cron.php"
LOG_DIR="$PROJECT_DIR/logs"

# Create logs directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Make the cron script executable
chmod +x "$CRON_SCRIPT"

# Create log file
touch "$LOG_DIR/mentor_email_cron.log"
chmod 664 "$LOG_DIR/mentor_email_cron.log"

echo "Setting up mentor email cron jobs..."

# Check if cron jobs already exist
if crontab -l 2>/dev/null | grep -q "mentor_email_cron.php"; then
    echo "Mentor email cron jobs already exist. Removing old ones..."
    crontab -l 2>/dev/null | grep -v "mentor_email_cron.php" | crontab -
fi

# Add new cron jobs
(crontab -l 2>/dev/null; echo "# Mentor Email System - Session Reminders (every hour)") | crontab -
(crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php $CRON_SCRIPT >> $LOG_DIR/mentor_email_cron.log 2>&1") | crontab -

echo "Mentor email cron jobs have been set up successfully!"
echo ""
echo "Cron schedule:"
echo "- Session reminders: Every hour"
echo "- Welcome emails: Daily at 9 AM"
echo "- Progress updates: Sundays at 9 AM"
echo ""
echo "Log file: $LOG_DIR/mentor_email_cron.log"
echo ""
echo "To view current cron jobs: crontab -l"
echo "To edit cron jobs: crontab -e"
echo "To remove cron jobs: crontab -r"
echo ""
echo "Testing the cron script..."
php "$CRON_SCRIPT"
echo ""
echo "Setup complete!"