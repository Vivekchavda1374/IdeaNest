# IdeaNest - Collaborative Academic Project Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a web-based platform designed to facilitate the management, sharing, and review of academic projects. It provides a comprehensive suite of features for students, sub-admins, and administrators, streamlining the project lifecycle from submission to approval.

## ✨ Latest Updates

### Weekly Email Notification System (December 2024)
- **Database-Driven SMTP Configuration**: Uses admin_settings table for email configuration
- **30-Minute Testing Intervals**: Configurable cron job for rapid testing
- **User Notification Preferences**: Toggle switch in profile settings
- **Beautiful HTML Email Templates**: Responsive design with project and idea updates
- **Comprehensive Logging**: Tracks sent/failed notifications in database
- **Production-Ready**: Automated weekly digest system for student engagement

## 🚀 Features

### Email Notification System
- **Weekly Digest Emails**: Automated emails every 7 days (configurable to 30 minutes for testing)
- **User Preferences**: Students can enable/disable notifications in profile settings
- **Database Integration**: Uses existing admin_settings for SMTP configuration
- **Content Filtering**: Shows new projects and ideas from last 7-30 days
- **Logging & Analytics**: Comprehensive tracking of notification delivery
- **Template System**: Professional HTML email templates with responsive design

### Project Management
- **Secure Project Submission**: Multi-file upload with validation
- **Project Approval Workflow**: Three-tier system (User → SubAdmin → Admin)
- **Real-time Status Tracking**: pending/approved/rejected with notifications
- **File Security**: Protected uploads with access control
- **Project Categories**: Software/Hardware classification system

### Authentication System
- Traditional email/password login
- Google OAuth integration with JWT
- Forgot password with OTP verification (10-minute expiry)
- Session-based security management
- Role-based access control (Student/Sub-Admin/Admin)

### Interactive Features
- Project like system with AJAX updates
- Bookmark functionality for project saving
- Comment system with like support
- Real-time interaction feedback
- Modal-based project viewing

### Admin Features
- **Project Review System**: Final approval authority with analytics dashboard
- **User Management**: Role-based access control and activity monitoring
- **Email Configuration**: SMTP settings management in admin panel
- **Notification Dashboard**: Monitor email delivery and user preferences

### SubAdmin Features
- **Project Assignment by Classification**: Automatic assignment based on expertise
- **Review Queue**: Organized project review with priority levels
- **Collaborative Review**: Multiple sub-admins can review projects
- **Performance Metrics**: Track review statistics and workload

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2.4 or higher
- MySQL 10.4.28-MariaDB or higher
- Apache Web Server with mod_rewrite enabled
- PHPMailer for email functionality
- Cron job support for automated notifications
- Google OAuth 2.0 credentials (optional)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/IdeaNest.git
```

2. Install dependencies:
```bash
composer install
```

3. Set up database:
- Create a new MySQL database
- Import the SQL files from the `db` folder
- Configure database connection in `Login/Login/db.php`
- Run `setup_notifications.sql` to add notification tables
- Ensure proper table structure for projects, users, notifications, and logs

4. Configure web server:
- Point your web server to the project directory
- Ensure proper permissions for uploads folders
- Verify .htaccess files are properly configured
- Enable mod_rewrite for Apache

5. Setup email notifications:
- Configure SMTP settings in Admin panel
- Run `cd cron && chmod +x setup_cron.sh && ./setup_cron.sh`
- Test with `php cron/weekly_notifications.php`

---

## 🛠 Configuration

### Email Notification Setup
1. **Admin Panel Configuration**:
   - Login as admin
   - Go to Settings → Email Configuration
   - Configure SMTP settings (Gmail recommended)
   - Set notification preferences

2. **Cron Job Setup**:
   ```bash
   # For testing (every 30 minutes)
   cd /opt/lampp/htdocs/IdeaNest/cron
   ./setup_cron.sh
   
   # For production (weekly)
   # Edit setup_cron.sh and change to: 0 9 * * 0
   ```

3. **Database SMTP Settings**:
   ```sql
   -- Current settings in admin_settings table
   smtp_host: smtp.gmail.com
   smtp_port: 587
   smtp_username: ideanest.ict@gmail.com
   smtp_password: [app-password]
   smtp_secure: tls
   from_email: ideanest.ict@gmail.com
   ```

### Google OAuth Setup
1. Create Google Cloud Console project
2. Enable Google+ API
3. Create OAuth 2.0 credentials
4. Add authorized JavaScript origins
5. Update client ID in login.php

---

## 🔧 Project Structure
```
IdeaNest/
├── Admin/
│   ├── subadmin/                    # SubAdmin panel
│   ├── notification_dashboard.php   # Email notification monitoring
│   └── project_notification.php
├── user/
│   ├── uploads/                     # Secure file storage
│   ├── Blog/                        # Blog functionality
│   ├── user_profile_setting.php     # User preferences with notification toggle
│   └── forms/                       # Project submission forms
├── cron/
│   ├── weekly_notifications.php     # Main notification script
│   ├── setup_cron.sh               # Cron job setup
│   └── notification.log            # Notification logs
├── Login/Login/                     # Authentication system
├── config/                          # Security configuration
├── includes/                        # Error handlers
└── assets/                         # CSS/JS assets
```

---

## 🔧 Troubleshooting

### Common Issues
- **403/500 Errors**: Check .htaccess configuration and file permissions
- **Google OAuth**: Verify client ID and authorized domains
- **Email Issues**: Ensure SMTP credentials and app passwords are correct
- **File Uploads**: Check upload directory permissions (755 recommended)
- **Cron Job Issues**: Check `/opt/lampp/htdocs/IdeaNest/cron/notification.log`
- **Notification Not Sending**: Verify database SMTP settings in admin_settings table
- **Path Errors**: Ensure cron script uses absolute paths with `__DIR__`

### Email Notification Troubleshooting
```bash
# Test notification manually
php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php

# Check cron job status
crontab -l

# View notification logs
tail -f /opt/lampp/htdocs/IdeaNest/cron/notification.log

# Check database logs
SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 10;
```

### File Permissions
```bash
chmod 755 user/uploads/
chmod 644 user/uploads/*
chmod 755 logs/
chmod +x cron/setup_cron.sh
```

---

## 📝 Contributing

1. Fork the repository
2. Create your feature branch:
```bash
git checkout -b feature/EmailNotifications
```
3. Commit your changes:
```bash
git commit -m 'Add weekly email notification system'
```
4. Push to the branch:
```bash
git push origin feature/EmailNotifications
```
5. Open a Pull Request

---

## 🚀 Recent Improvements

### Email Notification System
- Database-driven SMTP configuration
- User preference management
- Automated weekly digest emails
- Comprehensive logging and monitoring
- Production-ready cron job automation

### Database & Backend
- Fixed path issues in notification scripts
- Enhanced logging with proper database schema
- Improved error handling and debugging
- Secure SMTP credential management

### Security Enhancements
- Production-ready security headers
- CSRF protection on all forms
- Secure session management
- Protected file uploads

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 📞 Support

For support, email ideanest.ict@gmail.com or create an issue on GitHub.

---

## 🙏 Acknowledgments

- PHP community for security best practices
- PHPMailer team for reliable email delivery
- Bootstrap team for responsive framework
- Font Awesome for iconography
- All contributors and testers