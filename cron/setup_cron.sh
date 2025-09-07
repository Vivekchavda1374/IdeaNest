#!/bin/bash

# Setup cron job for testing notifications
# Run every 30 minutes for testing

(crontab -l 2>/dev/null; echo "*/1 * * * * /opt/lampp/bin/php -d extension=mysqli /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php >> /opt/lampp/htdocs/IdeaNest/cron/notification.log 2>&1") | crontab -

echo "Cron job added successfully!"
echo "Test notifications will run every 1 minutes using XAMPP PHP"