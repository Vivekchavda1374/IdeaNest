#!/bin/bash
weekly Sunday notifications
# Runs every Sunday at 9:00 AM

# Remove existing IdeaNest cron jobs to avoid duplicates
crontab -l 2>/dev/null | grep -v "IdeaNest/cron/weekly_notifications.php" | crontab -

# Add new weekly Sunday cron job
(crontab -l 2>/dev/null; echo "0 9 * * 0 /opt/lampp/bin/php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php >> /opt/lampp/htdocs/IdeaNest/logs/weekly_notifications.log 2>&1") | crontab -

echo "Weekly Sunday cron job added successfully!"
echo "Notifications will run every Sunday at 9:00 AM"
echo "Log file: /opt/lampp/htdocs/IdeaNest/logs/weekly_notifications.log"

# Create log directory if it doesn't exist
mkdir -p /opt/lampp/htdocs/IdeaNest/logs

# Test the cron job setup
echo "Testing cron job..."
crontab -l | grep "weekly_notifications.php"

if [ $? -eq 0 ]; then
    echo "✓ Cron job successfully configured"
else
    echo "✗ Failed to configure cron job"
fi