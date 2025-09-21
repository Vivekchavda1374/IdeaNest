# IdeaNest - Academic Project Management Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a web-based platform designed to facilitate the management, sharing, and review of academic projects. It provides features for students, sub-admins, administrators, and mentors, streamlining the project lifecycle from submission to approval.

**Latest Update**: Project cleanup removing unused files and optimizing structure for better maintainability.

## 🚀 Core Features

### 🔗 Basic GitHub Integration
- **Profile Connection**: Link GitHub usernames in profile settings
- **Repository Data**: Fetch basic GitHub profile and repository information
- **API Integration**: Simple GitHub API connectivity for user profiles

### 📧 Email Notification System
- **Weekly Digest Emails**: Automated email notifications
- **SMTP Configuration**: Email settings management
- **Cron Job Support**: Automated background email processing

### 📋 Project Management
- **Secure Project Submission**: Multi-file upload with validation
- **Project Approval Workflow**: Three-tier system (User → SubAdmin → Admin)
- **Real-time Status Tracking**: pending/approved/rejected with notifications
- **File Security**: Protected uploads with access control
- **Project Categories**: Software/Hardware classification system
- **Enhanced Project Details**: Difficulty levels, team size, development time

### 🔐 Authentication System
- **Traditional Login**: Email/password authentication
- **Google OAuth Integration**: JWT-based Google sign-in
- **Password Recovery**: OTP verification with 10-minute expiry
- **Session Management**: Secure session-based security
- **Role-Based Access**: Student/Sub-Admin/Admin/Mentor permissions

### 🎯 Interactive Features
- **Project Engagement**: Like system with AJAX updates
- **Bookmark System**: Save favorite projects for later
- **Comment System**: Project discussions with like support
- **Real-time Feedback**: Interactive elements with instant updates
- **Modal Views**: Enhanced project viewing experience

### 👨‍💼 Admin Features
- **Enhanced Dashboard**: Comprehensive admin dashboard with system analytics
- **Project Review System**: Final approval authority with analytics dashboard
- **User Management**: Role-based access control and detailed user profiles
- **Mentor Management**: Add, remove, and manage mentor accounts with full control
- **SubAdmin Overview**: Complete subadmin management and performance tracking
- **Data Export**: Export system data in multiple formats (CSV, PDF, Excel)
- **Email Configuration**: SMTP settings management in admin panel
- **Notification Dashboard**: Monitor email delivery and user preferences
- **GitHub Analytics**: Track GitHub integration usage

### 🎓 Mentor System
- **Mentor Dashboard**: Basic mentor interface
- **Student Management**: View assigned students
- **Session Management**: Schedule and track mentoring sessions
- **Project Review**: Review student projects
- **Profile Management**: Mentor profile settings
- **Email System**: Send emails to students
- **Activity Tracking**: Monitor mentor activities

### 👥 SubAdmin Features
- **Project Assignment**: Automatic assignment based on classification expertise
- **Review Queue**: Organized project review with priority levels
- **Collaborative Review**: Multiple sub-admins can review projects
- **Performance Metrics**: Track review statistics and workload
- **Support System**: Ticket-based support with admin communication

## 🛠 Technical Stack

### Backend
- **PHP 8.2.4**: Modern PHP with latest features
- **MySQL 10.4.28-MariaDB**: Robust database with optimized queries
- **Apache 2.4**: Web server with mod_rewrite enabled
- **PHPMailer**: Reliable email delivery system

### Frontend
- **HTML5/CSS3**: Modern web standards
- **JavaScript (ES6+)**: Interactive user interfaces
- **Bootstrap 5**: Responsive design framework
- **Font Awesome 6**: Comprehensive icon library
- **AJAX**: Seamless user interactions

### Integrations
- **GitHub API v3**: Repository and profile data
- **Google OAuth 2.0**: Social authentication
- **Cron Jobs**: Automated background tasks
- **Session Management**: Secure user sessions

## 🚀 Getting Started

### Prerequisites
- PHP 8.2.4 or higher
- MySQL 10.4.28-MariaDB or higher
- Apache Web Server with mod_rewrite enabled
- PHPMailer for email functionality
- Cron job support for automated notifications
- Google OAuth 2.0 credentials (optional)
- Internet connection for GitHub API

### Installation

1. **Clone the repository:**
```bash
git clone https://github.com/yourusername/IdeaNest.git
cd IdeaNest
```

2. **Install dependencies:**
```bash
composer install
```

3. **Database setup:**
```bash
# Create MySQL database
mysql -u root -p -e "CREATE DATABASE ideanest;"

# Import base schema
mysql -u root -p ideanest < db/ideanest.sql

# Note: Setup scripts have been removed in v3.1 cleanup
# Database tables are now created automatically on first use
```

4. **Configure database connection:**
```php
// Edit Login/Login/db.php
$host = "localhost";
$user = "root";
$pass = "your_password";
$dbname = "ideanest";
```

5. **Web server configuration:**
```bash
# Set proper permissions
chmod 755 user/uploads/
chmod 644 user/uploads/*
chmod 755 logs/
chmod +x cron/setup_cron.sh

# Enable Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

6. **Email notifications setup:**
```bash
# Configure SMTP in Admin panel
# Setup cron job
cd cron && chmod +x setup_cron.sh && ./setup_cron.sh

# Test notifications
php cron/weekly_notifications.php
```

## 🔧 Configuration

### GitHub Integration Setup
1. **User Configuration**:
   - Login to your account
   - Go to Profile Settings
   - Enter GitHub username in GitHub Integration section
   - Save to automatically sync profile and repositories

2. **Admin Configuration**:
   - Monitor GitHub integration usage in admin dashboard
   - View user GitHub connection statistics
   - Manage GitHub-related settings

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

### Google OAuth Setup
1. Create Google Cloud Console project
2. Enable Google+ API
3. Create OAuth 2.0 credentials
4. Add authorized JavaScript origins
5. Update client ID in login.php

## 🏗 Project Structure

```
IdeaNest/
├── Admin/
│   ├── subadmin/                    # SubAdmin management panel
│   ├── admin.php                    # Main admin dashboard
│   ├── admin_view_project.php       # Project review interface
│   ├── manage_mentors.php           # Mentor management
│   ├── user_manage_by_admin.php     # User management
│   ├── system_analytics.php         # System analytics
│   ├── export_data.php              # Data export functionality
│   └── notification_dashboard.php   # Email notification monitoring
├── mentor/
│   ├── dashboard.php               # Mentor dashboard
│   ├── students.php                # Student management
│   ├── sessions.php                # Session management
│   ├── projects.php                # Project review
│   ├── profile.php                 # Mentor profile
│   ├── analytics.php               # Basic analytics
│   └── send_email.php              # Email functionality
├── user/
│   ├── forms/uploads/              # File upload storage
│   ├── Blog/                       # Ideas/Blog functionality
│   ├── forms/                      # Project submission forms
│   ├── index.php                   # User dashboard
│   ├── all_projects.php            # Project listing
│   ├── github_service.php          # GitHub API integration
│   └── user_profile_setting.php    # User profile settings
├── cron/
│   ├── weekly_notifications.php    # Email notification system
│   └── setup_cron.sh              # Cron job setup
├── Login/Login/                    # Authentication system
├── config/                         # Security configuration
├── includes/                       # Validation and error handling
├── assets/                         # CSS/JS/Images
└── db/                            # Database schema
```

## 🧪 Testing

The application should be tested manually by:
- Testing user registration and login
- Verifying project submission and approval workflow
- Testing mentor-student pairing functionality
- Checking email notification system
- Validating GitHub integration features

## 🔧 Troubleshooting

### Common Issues
- **403/500 Errors**: Check .htaccess configuration and file permissions
- **Google OAuth**: Verify client ID and authorized domains
- **Email Issues**: Ensure SMTP credentials and app passwords are correct
- **File Uploads**: Check upload directory permissions (755 recommended)
- **GitHub Integration**: Verify internet connectivity and API limits

### GitHub Integration Issues
```bash
# Test GitHub connectivity
curl -I https://api.github.com/users/octocat

# Check GitHub integration
php user/github_service.php
```

### Email Notification Troubleshooting
```bash
# Test notification manually
php /opt/lampp/htdocs/IdeaNest/cron/weekly_notifications.php

# Check cron job status
crontab -l
```

### File Permissions
```bash
chmod 755 user/uploads/
chmod 644 user/uploads/*
chmod 755 logs/
chmod +x cron/setup_cron.sh
chmod 755 tests/
```

## 🚀 Recent Improvements

### Project Cleanup & Optimization - Latest ✨
- **Removed unused files** - Cleaned up test data and duplicate files
- **Streamlined project structure** - Better organization for maintainability
- **Optimized file storage** - Removed ~95MB of unused test data
- **Enhanced code organization** - Cleaner directory structure

### Core System Features
- Basic mentor management system with dashboard
- Student-mentor pairing functionality
- Session scheduling and management
- Project submission and approval workflow
- Email notification system
- GitHub profile integration
- User authentication with Google OAuth support

## 📊 System Specifications

### File Upload Limits
- **Maximum File Size**: 10MB per file (configurable in .htaccess)
- **Supported File Types**: Images, videos, PDFs, ZIP files
- **Upload Security**: File type validation and secure storage

### Database Performance
- **MySQL/MariaDB**: Optimized queries with prepared statements
- **Session Management**: Secure PHP session handling
- **Data Integrity**: Foreign key constraints where applicable

## 🔒 Security Features

### Data Protection
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Session-based token validation
- **File Upload Security**: Type validation and secure storage
- **Session Management**: Secure session handling

### GitHub Integration Security
- **Public API Only**: No authentication tokens required
- **Input Validation**: Username sanitization
- **Error Handling**: Graceful failure handling

## 📝 Contributing

1. Fork the repository
2. Create your feature branch:
```bash
git checkout -b feature/AmazingFeature
```
3. Commit your changes:
```bash
git commit -m 'Add some AmazingFeature'
```
4. Push to the branch:
```bash
git push origin feature/AmazingFeature
```
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for any changes
- Ensure backward compatibility
- Test across multiple browsers

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email**: ideanest.ict@gmail.com
- **GitHub Issues**: Create an issue for bug reports
- **Documentation**: Check FILE_DOCUMENTATION.md for file details

## 🙏 Acknowledgments

- **PHP Community** for security best practices and frameworks
- **GitHub API** for comprehensive developer data access
- **PHPMailer Team** for reliable email delivery solutions
- **Bootstrap Team** for responsive design framework
- **Font Awesome** for comprehensive iconography
- **MySQL/MariaDB** for robust database management
- **Apache Foundation** for web server technology
- **All Contributors** who helped test and improve the platform

## 🔮 Future Enhancements

### Potential Improvements
- **Enhanced GitHub Integration**: More detailed repository analytics
- **Advanced Mentor Features**: Better session management and tracking
- **Mobile Responsiveness**: Improved mobile user experience
- **API Development**: RESTful API for external integrations
- **Performance Optimization**: Caching and query optimization
- **Enhanced Security**: Additional security measures and monitoring

---

**Made with ❤️ by the IdeaNest Team**

*Empowering academic collaboration through innovative technology*