# IdeaNest - Academic Project Management Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a comprehensive web-based platform designed to facilitate academic project management, collaboration, and mentorship. It provides a complete ecosystem for students, mentors, sub-admins, and administrators to manage the entire project lifecycle from idea conception to final approval.

## 🚀 Core Features

### 🔐 Authentication & User Management
- **Multi-Role Authentication**: Student/Sub-Admin/Admin/Mentor role-based access
- **Google OAuth Integration**: Social sign-in with profile completion
- **Traditional Login**: Email/password authentication with secure sessions
- **Profile Management**: User profiles with image upload and GitHub integration

### 📋 Project Management System
- **Project Submission**: Multi-file upload with validation (images, videos, code, presentations)
- **Three-Tier Approval**: User → SubAdmin → Admin workflow
- **Project Categories**: Software/Hardware classification with difficulty levels
- **Enhanced Project Details**: Team size, development time, target audience, goals
- **File Security**: Protected uploads with access control
- **Project Status Tracking**: Real-time status updates (pending/approved/rejected)

### 💡 Ideas & Blog System
- **Idea Sharing**: Students can share project ideas and concepts
- **Interactive Features**: Like and comment system for ideas
- **Idea Management**: Edit, delete, and report inappropriate content
- **Real-time Engagement**: AJAX-powered interactions

### 👨🏫 Mentor System
- **Mentor Dashboard**: Comprehensive mentor interface with analytics
- **Student-Mentor Pairing**: Request-based pairing system
- **Session Management**: Schedule and track mentoring sessions
- **Email System**: Built-in email functionality for mentor-student communication
- **Activity Tracking**: Monitor mentor activities and student progress
- **Smart Pairing**: AI-powered mentor-student matching

### 👥 SubAdmin Features
- **Project Assignment**: Automatic assignment based on classification expertise
- **Review Queue**: Organized project review with priority levels
- **Classification Management**: Request system for changing expertise areas
- **Support System**: Ticket-based support with admin communication
- **Performance Tracking**: Review statistics and workload monitoring

### 👨💼 Admin Features
- **Enhanced Dashboard**: System analytics with charts and statistics
- **User Management**: Complete user lifecycle management
- **Mentor Management**: Add, remove, and manage mentor accounts
- **SubAdmin Management**: Full subadmin oversight and performance tracking
- **Data Export**: Export system data in multiple formats
- **Email Configuration**: SMTP settings management
- **Notification Dashboard**: Monitor email delivery and system notifications
- **Support Ticket Management**: Handle subadmin support requests

### 🔗 GitHub Integration
- **Profile Connection**: Link GitHub usernames in profile settings
- **Repository Sync**: Fetch and display GitHub profile and repository data
- **API Integration**: GitHub API connectivity for user profiles
- **Real-time Sync**: Automatic GitHub data synchronization

### 📧 Email Notification System
- **Weekly Digest Emails**: Automated email notifications for new projects/ideas
- **SMTP Configuration**: Configurable email settings
- **Cron Job Support**: Automated background email processing
- **Email Templates**: Customizable notification templates
- **Delivery Tracking**: Monitor email delivery status and failures

### 🎯 Interactive Features
- **Project Engagement**: Like system with AJAX updates
- **Bookmark System**: Save favorite projects for later viewing
- **Comment System**: Project discussions with nested comments
- **Real-time Feedback**: Interactive elements with instant updates
- **Search Functionality**: Search projects and ideas
- **Advanced Analytics**: Comprehensive dashboard with charts and statistics

## 🛠 Technical Stack

### Backend
- **PHP 8.2.4**: Modern PHP with latest features
- **MySQL 10.4.28-MariaDB**: Robust database with optimized queries
- **Apache 2.4**: Web server with mod_rewrite enabled
- **PHPMailer**: Reliable email delivery system
- **Composer**: Dependency management

### Frontend
- **HTML5/CSS3**: Modern web standards with responsive design
- **JavaScript (ES6+)**: Interactive user interfaces
- **Bootstrap 5**: Responsive design framework
- **Font Awesome 6**: Comprehensive icon library
- **AJAX**: Seamless user interactions
- **Chart.js**: Interactive data visualization

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
- Composer for dependency management
- Internet connection for GitHub API and Google OAuth

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

# Import database schema
mysql -u root -p ideanest < db/ideanest.sql
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
chmod 755 user/forms/uploads/
chmod 755 logs/
chmod +x cron/setup_cron.sh

# Enable Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

6. **Email notifications setup:**
```bash
# Configure SMTP in Admin panel
# Setup cron job for weekly notifications
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
   - Click "Sync Now" to automatically sync profile and repositories

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
│   ├── analytics.php               # Analytics dashboard
│   ├── send_email.php              # Email functionality
│   └── email_dashboard.php         # Email analytics
├── user/
│   ├── forms/uploads/              # File upload storage
│   ├── Blog/                       # Ideas/Blog functionality
│   ├── forms/                      # Project submission forms
│   ├── index.php                   # User dashboard with analytics
│   ├── all_projects.php            # Project listing
│   ├── github_service.php          # GitHub API integration
│   ├── github_profile.php          # GitHub profile display
│   └── user_profile_setting.php    # User profile settings
├── cron/
│   ├── weekly_notifications.php    # Email notification system
│   ├── mentor_email_cron.php       # Mentor email automation
│   └── setup_cron.sh              # Cron job setup
├── Login/Login/                    # Authentication system
├── config/                         # Security configuration
├── includes/                       # Validation and error handling
├── assets/                         # CSS/JS/Images
├── vendor/                         # Composer dependencies
├── tests/                          # Test suite
└── db/                            # Database schema
```

## 🔧 Database Schema

The system uses a comprehensive database schema with the following key tables:

- **register**: User accounts and profiles with GitHub integration
- **projects**: Project submissions and details
- **admin_approved_projects**: Approved projects
- **blog**: Ideas and blog posts
- **mentors**: Mentor information and specializations
- **mentor_student_pairs**: Mentor-student relationships
- **mentoring_sessions**: Session scheduling and management
- **mentor_requests**: Mentor pairing requests
- **subadmins**: SubAdmin accounts with classifications
- **notification_logs**: Email notification tracking
- **bookmark**: User bookmarks
- **project_likes/idea_likes**: Engagement tracking
- **support_tickets**: Support ticket system
- **mentor_email_logs**: Email tracking for mentors

## 🧪 Testing

The application includes comprehensive testing:
- **Unit Tests**: Core functionality testing
- **Integration Tests**: Database and API integration
- **Functional Tests**: User workflow testing
- **Performance Tests**: Load testing capabilities

Run tests using:
```bash
cd tests
./run_tests.sh
```

## 🔧 Troubleshooting

### Common Issues
- **403/500 Errors**: Check .htaccess configuration and file permissions
- **Google OAuth**: Verify client ID and authorized domains
- **Email Issues**: Ensure SMTP credentials and app passwords are correct
- **File Uploads**: Check upload directory permissions (755 recommended)
- **GitHub Integration**: Verify internet connectivity and API limits

### File Permissions
```bash
chmod 755 user/uploads/
chmod 755 user/forms/uploads/
chmod 755 logs/
chmod +x cron/setup_cron.sh
```

## 📊 System Specifications

### File Upload Limits
- **Maximum File Size**: 10MB per file (configurable)
- **Supported File Types**: Images, videos, PDFs, ZIP files, presentations
- **Upload Security**: File type validation and secure storage

### Database Performance
- **MySQL/MariaDB**: Optimized queries with prepared statements
- **Session Management**: Secure PHP session handling
- **Data Integrity**: Foreign key constraints and data validation

## 🔒 Security Features

### Data Protection
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Session-based token validation
- **File Upload Security**: Type validation and secure storage
- **Session Management**: Secure session handling with timeouts

## 📝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/AmazingFeature`
3. Commit your changes: `git commit -m 'Add some AmazingFeature'`
4. Push to the branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for any changes
- Ensure backward compatibility

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email**: ideanest.ict@gmail.com
- **GitHub Issues**: Create an issue for bug reports

## 🙏 Acknowledgments

- **PHP Community** for security best practices
- **GitHub API** for developer data access
- **PHPMailer Team** for email delivery solutions
- **Bootstrap Team** for responsive design framework
- **Font Awesome** for comprehensive iconography
- **Chart.js** for data visualization capabilities

---

**Made with ❤️ by the IdeaNest Team**

*Empowering academic collaboration through innovative technology*