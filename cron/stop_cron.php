<?php
// Remove notification cron job
$output = shell_exec('crontab -l 2>/dev/null | grep -v "weekly_notifications.php" | crontab -');
echo "Cron job removed successfully!\n";
echo "Notifications are now stopped.\n";
?>