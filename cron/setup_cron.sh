#!/bin/bash

# Setup cron job for weekly notifications
# Run every Sunday at 9:00 AM

(crontab -l 2>/dev/null; echo "0 9 * * 0 /usr/bin/php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php >> /opt/lampp/htdocs/IdeaNest/cron/notification.log 2>&1") | crontab -

echo "Cron job added successfully!"
echo "Weekly notifications will run every Sunday at 9:00 AM"