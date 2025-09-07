# Email Notification System Setup Guide

## Overview
The IdeaNest platform now includes a weekly email notification system that sends students updates about new projects and ideas from other students.

## Features
- ✅ Weekly email notifications (every Sunday at 9:00 AM)
- ✅ User toggle in profile settings (on/off)
- ✅ Beautiful HTML email templates
- ✅ Admin dashboard for monitoring
- ✅ Notification logs and statistics
- ✅ Test functionality for debugging

## Setup Instructions

### 1. Database Setup
Run the SQL commands to add notification features:
```bash
mysql -u root -p ideanest < setup_notifications.sql
```

### 2. Email Configuration
Edit the email settings in these files:
- `cron/weekly_notifications.php` (line 47-50)
- `cron/test_notifications.php` (line 67-70)

Replace with your SMTP credentials:
```php
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
```

### 3. Cron Job Setup
Run the setup script to enable weekly notifications:
```bash
cd /opt/lampp/htdocs/IdeaNest/cron
./setup_cron.sh
```

Or manually add to crontab:
```bash
crontab -e
# Add this line:
0 9 * * 0 /usr/bin/php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php >> /opt/lampp/htdocs/IdeaNest/cron/notification.log 2>&1
```

## File Structure
```
IdeaNest/
├── cron/
│   ├── weekly_notifications.php    # Main notification script
│   ├── test_notifications.php      # Test script
│   ├── setup_cron.sh              # Cron setup script
│   └── notification.log            # Log file (auto-created)
├── Admin/
│   └── notification_dashboard.php  # Admin monitoring dashboard
├── assets/css/
│   └── notification_toggle.css     # Toggle switch styles
├── user/
│   └── user_profile_setting.php    # Updated with notification toggle
└── setup_notifications.sql         # Database setup
```

## Usage

### For Students
1. Go to Profile Settings
2. Toggle "Weekly Email Notifications" on/off
3. Receive weekly updates every Sunday (if enabled)

### For Admins
1. Access notification dashboard: `/Admin/notification_dashboard.php`
2. Monitor statistics and logs
3. Test notifications manually
4. View user opt-in rates

### Testing
Run test notification:
```bash
php /opt/lampp/htdocs/IdeaNest/cron/test_notifications.php
```

## Email Template Features
- Responsive HTML design
- Lists new projects and ideas from last 7 days
- Direct links to view content
- Unsubscribe link to profile settings
- Professional branding

## Security & Privacy
- Users can opt-out anytime
- No spam - only weekly notifications
- Secure SMTP with authentication
- Logs for monitoring and debugging

## Troubleshooting

### Common Issues
1. **Emails not sending**: Check SMTP credentials and Gmail app password
2. **Cron not running**: Verify cron service is active: `systemctl status cron`
3. **Database errors**: Ensure setup_notifications.sql was executed
4. **Permission errors**: Check file permissions on cron directory

### Log Files
- Cron logs: `/opt/lampp/htdocs/IdeaNest/cron/notification.log`
- Database logs: `notification_logs` table
- Apache logs: `/opt/lampp/logs/error_log`

## Configuration Options

### Notification Frequency
To change from weekly to daily, edit cron job:
```bash
# Daily at 9 AM
0 9 * * * /usr/bin/php /path/to/weekly_notifications.php

# Weekly (current)
0 9 * * 0 /usr/bin/php /path/to/weekly_notifications.php
```

### Email Limits
Modify limits in `weekly_notifications.php`:
```php
LIMIT 10  // Change to desired number of projects/ideas per email
```

### SMTP Settings
Supports any SMTP provider:
- Gmail (current setup)
- Outlook/Hotmail
- Custom SMTP servers
- SendGrid, Mailgun, etc.

## Support
For issues or questions, check:
1. Log files for error messages
2. Admin dashboard for statistics
3. Test script for debugging
4. Database notification_logs table