#!/bin/bash

# Setup cron job for notifications
# Choose frequency: */1 (every minute), */30 (every 30 min), 0 9 * * 0 (weekly Sunday 9AM)

(crontab -l 2>/dev/null; echo "*/1 * * * * /opt/lampp/bin/php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php >> /opt/lampp/htdocs/IdeaNest/cron/notification.log 2>&1") | crontab -

echo "Cron job added successfully!"
echo "Notifications will run every 1 minutes automatically"