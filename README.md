   # IdeaNest - Academic Project Management Platform

   [![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
   [![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
   [![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

   IdeaNest is a comprehensive web-based platform designed to facilitate academic project management, collaboration, and mentorship. It provides a complete ecosystem for students, mentors, sub-admins, and administrators to manage the entire project lifecycle from idea conception to final approval.

   ## 🚀 Core Features

   ### 🔐 Authentication & User Management
   - **Multi-Role Authentication**: Student/Sub-Admin/Admin/Mentor role-based access
   - **Google OAuth Integration**: Social sign-in with profile completion
   - **Traditional Login**: Email/password authentication with secure sessions
   - **Profile Management**: User profiles with image upload and GitHub integration
   - **Password Reset**: Forgot password functionality with email verification

   ### 📋 Project Management System
   - **Project Submission**: Multi-file upload with validation (images, videos, code, presentations)
   - **Three-Tier Approval**: User → SubAdmin → Admin workflow
   - **Project Categories**: Software/Hardware classification with difficulty levels
   - **Enhanced Project Details**: Team size, development time, target audience, goals
   - **File Security**: Protected uploads with access control and download tracking
   - **Project Status Tracking**: Real-time status updates (pending/approved/rejected)
   - **Project Editing**: Edit submitted projects before approval
   - **Denial Management**: Track and manage rejected projects with reasons

   ### 💡 Ideas & Blog System
   - **Idea Sharing**: Students can share project ideas and concepts
   - **Interactive Features**: Like and comment system for ideas
   - **Idea Management**: Edit, delete, and report inappropriate content
   - **Real-time Engagement**: AJAX-powered interactions
   - **Content Moderation**: Report system with admin review and warning system
   - **Idea Deletion Tracking**: Maintain records of deleted ideas with reasons

   ### 👨🏫 Mentor System
   - **Mentor Dashboard**: Comprehensive mentor interface with analytics
   - **Student-Mentor Pairing**: Request-based pairing system with smart matching
   - **Session Management**: Schedule and track mentoring sessions with meeting links
   - **Email System**: Built-in email functionality with queue management
   - **Activity Tracking**: Monitor mentor activities and student progress
   - **Project Access**: Mentors can access their students' projects
   - **Automated Emails**: Welcome messages, session reminders, and progress updates
   - **Email Analytics**: Track email delivery and engagement statistics

   ### 👥 SubAdmin Features
   - **Project Assignment**: Automatic assignment based on classification expertise
   - **Review Queue**: Organized project review with priority levels
   - **Classification Management**: Request system for changing expertise areas
   - **Support System**: Ticket-based support with admin communication
   - **Performance Tracking**: Review statistics and workload monitoring
   - **Profile Management**: Complete profile setup and domain expertise

   ### 👨💼 Admin Features
   - **Enhanced Dashboard**: System analytics with charts and statistics
   - **User Management**: Complete user lifecycle management with activity logs
   - **Mentor Management**: Add, remove, and manage mentor accounts with detailed profiles
   - **SubAdmin Management**: Full subadmin oversight and performance tracking
   - **Advanced Data Export**: Export system data in multiple formats (CSV, comprehensive, overview, selective)
   - **Export Overview**: Comprehensive data visualization with real-time statistics
   - **Email Configuration**: SMTP settings management with delivery monitoring
   - **Notification Dashboard**: Monitor email delivery and system notifications
   - **Support Ticket Management**: Handle subadmin support requests with threaded replies
   - **Content Moderation**: Manage reported ideas and user warnings with automated tracking
   - **System Settings**: Configure application-wide settings and security parameters
   - **Production Status**: Monitor system health and deployment status
   - **Analytics Dashboard**: Real-time engagement metrics and performance insights

   ### 🔗 GitHub Integration
   - **Profile Connection**: Link GitHub usernames in profile settings
   - **Repository Sync**: Fetch and display GitHub profile and repository data
   - **API Integration**: GitHub API connectivity for user profiles
   - **Real-time Sync**: AJAX-powered GitHub data synchronization
   - **Profile Display**: Dedicated GitHub profile pages for users

   ### 📧 Email Notification System
   - **Weekly Digest Emails**: Automated email notifications for new projects/ideas
   - **Mentor Email System**: Comprehensive email queue with priority management
   - **SMTP Configuration**: Configurable email settings with multiple providers
   - **Cron Job Support**: Automated background email processing
   - **Email Templates**: Customizable notification templates
   - **Delivery Tracking**: Monitor email delivery status and failures
   - **Email Statistics**: Track email performance and engagement

   ### 🎯 Interactive Features
   - **Project Engagement**: Like system with AJAX updates and real-time counters
   - **Bookmark System**: Save favorite projects for later viewing with categories
   - **Comment System**: Project and idea discussions with nested comments and likes
   - **Real-time Feedback**: Interactive elements with instant updates and notifications
   - **Advanced Search**: Search projects and ideas with filters and sorting
   - **Comprehensive Analytics**: Dashboard with charts, statistics, and trend analysis
   - **Activity Logging**: Track user activities and system events with detailed audit trails
   - **Report System**: Content reporting with automated moderation workflow
   - **Idea Management**: Edit, delete, and track idea lifecycle with status updates
   - **User Interactions**: Follow users, track contributions, and engagement metrics

   ## 🛠 Technical Stack

   ### Backend
   - **PHP 8.2+**: Modern PHP with latest features
   - **MySQL 10.4.28-MariaDB**: Robust database with optimized queries and foreign keys
   - **Apache 2.4**: Web server with mod_rewrite enabled
   - **PHPMailer 6.10+**: Reliable email delivery system with queue management
   - **Composer**: Dependency management and autoloading

   ### Frontend
   - **HTML5/CSS3**: Modern web standards with responsive design
   - **JavaScript (ES6+)**: Interactive user interfaces with AJAX
   - **Bootstrap**: Responsive design framework
   - **Font Awesome**: Comprehensive icon library
   - **Custom CSS**: Modular stylesheets for different components
   - **Loading Components**: Enhanced user experience with loading states

   ### Development Tools
   - **PHPUnit**: Unit and integration testing with comprehensive coverage
   - **PHP_CodeSniffer**: Code quality and PSR-12 compliance with custom rules
   - **PHPStan**: Static analysis for code quality (Level 5)
   - **Guzzle HTTP**: HTTP client for API integrations and external services
   - **Composer**: Dependency management with autoloading and optimization
   - **Custom Validation**: Form validation and input sanitization framework
   - **Error Handling**: Comprehensive error logging and debugging tools
   - **Performance Monitoring**: Load testing and optimization utilities

   ### Integrations
   - **GitHub API v3**: Repository and profile data synchronization
   - **Google OAuth 2.0**: Social authentication with profile completion
   - **Cron Jobs**: Automated background tasks for emails and notifications
   - **Session Management**: Secure user sessions with CSRF protection

   ## 🚀 Getting Started

   ### Prerequisites
   - PHP 8.2+ or higher
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

   3. **Environment setup:**
   ```bash
   # Copy environment file
   cp .env.example .env

   # Edit .env with your configuration
   nano .env
   ```

   4. **Database setup:**
   ```bash
   # Create MySQL database
   mysql -u root -p -e "CREATE DATABASE ideanest;"

   # Import database schema
   mysql -u root -p ideanest < db/ideanest.sql
   ```

   5. **Configure database connection:**
   ```php
   // Edit Login/Login/db.php
   $host = "localhost";
   $user = "root";
   $pass = "your_password";
   $dbname = "ideanest";
   ```

   6. **Set file permissions:**
   ```bash
   chmod 755 user/uploads/
   chmod 755 user/forms/uploads/
   chmod 755 logs/
   chmod 644 .env
   chmod +x cron/setup_cron.sh
   chmod +x cron/manage_cron.sh
   ```

   7. **Web server configuration:**
   ```bash
   # Enable Apache mod_rewrite
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

   8. **Email system setup:**
   ```bash
   # Setup cron jobs
   cd cron && ./setup_cron.sh

   # Test email system
   php cron/weekly_notifications.php
   php cron/mentor_email_cron.php
   ```

   For production deployment, see [PRODUCTION_SETUP.md](PRODUCTION_SETUP.md).

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
      # Ubuntu/Pop!_OS/Debian
      cd /opt/lampp/htdocs/IdeaNest/cron
      ./setup_cron.sh
      
      # Fedora/RHEL/CentOS
      cd /opt/lampp/htdocs/IdeaNest/cron
      ./setup_cron_fedora.sh
      
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
   ├── Admin/                          # Admin panel
   │   ├── subadmin/                   # SubAdmin management
   │   │   ├── dashboard.php           # SubAdmin dashboard
   │   │   ├── assigned_projects.php   # Project assignments
   │   │   ├── profile.php             # SubAdmin profile
   │   │   └── support.php             # Support ticket system
   │   ├── admin.php                   # Main admin dashboard
   │   ├── admin_view_project.php      # Project review interface
   │   ├── manage_mentors.php          # Mentor management
   │   ├── user_manage_by_admin.php    # User management
   │   ├── system_analytics.php        # System analytics
   │   ├── export_*.php                # Data export functionality
   │   ├── notification_dashboard.php  # Email monitoring
   │   ├── manage_reported_ideas.php   # Content moderation
   │   └── settings.php                # System settings
   ├── mentor/                         # Mentor system
   │   ├── dashboard.php               # Mentor dashboard
   │   ├── students.php                # Student management
   │   ├── sessions.php                # Session management
   │   ├── projects.php                # Project access
   │   ├── profile.php                 # Mentor profile
   │   ├── analytics.php               # Analytics dashboard
   │   ├── email_system.php            # Email functionality
   │   ├── email_dashboard.php         # Email analytics
   │   ├── smart_pairing.php           # AI-powered pairing
   │   └── api/                        # API endpoints
   ├── user/                           # User interface
   │   ├── forms/                      # Project submission
   │   │   ├── uploads/                # File storage
   │   │   └── new_project_add.php     # Project form
   │   ├── Blog/                       # Ideas system
   │   │   ├── form.php                # Idea submission
   │   │   ├── list-project.php        # Ideas listing
   │   │   ├── edit.php                # Edit ideas
   │   │   └── report_handler.php      # Report system
   │   ├── api/                        # User API endpoints
   │   ├── index.php                   # User dashboard
   │   ├── all_projects.php            # Project gallery
   │   ├── github_*.php                # GitHub integration
   │   ├── bookmark.php                # Bookmark system
   │   ├── search.php                  # Search functionality
   │   ├── select_mentor.php           # Mentor selection
   │   └── user_profile_setting.php    # Profile settings
   ├── Login/Login/                    # Authentication
   │   ├── login.php                   # Login system
   │   ├── register.php                # Registration
   │   ├── google_*.php                # Google OAuth
   │   ├── forgot_password.php         # Password reset
   │   └── db.php                      # Database connection
   ├── cron/                           # Background tasks
   │   ├── weekly_notifications.php    # Weekly emails
   │   ├── mentor_email_cron.php       # Mentor emails
   │   ├── setup_cron.sh               # Cron setup
   │   └── manage_cron.sh              # Cron management
   ├── config/                         # Configuration
   │   ├── email_config.php            # Email settings
   │   └── security.php                # Security config
   ├── includes/                       # Shared components
   │   ├── validation.php              # Input validation
   │   ├── csrf.php                    # CSRF protection
   │   ├── error_handler.php           # Error handling
   │   └── loading_component.php       # UI components
   ├── assets/                         # Static assets
   │   ├── css/                        # Stylesheets
   │   └── js/                         # JavaScript files
   ├── tests/                          # Test suite
   │   ├── Unit/                       # Unit tests
   │   ├── Integration/                # Integration tests
   │   ├── Functional/                 # Functional tests
   │   └── Performance/                # Performance tests
   ├── System Design/                  # Documentation
   │   └── *.mmd                       # Mermaid diagrams
   ├── vendor/                         # Composer dependencies
   ├── db/                             # Database
   │   └── ideanest.sql                # Database schema
   ├── Report/                         # Project documentation
   │   ├── *.docx                      # Academic project reports
   │   ├── ARCHITECTURE_OVERVIEW.md    # System architecture documentation
   │   ├── DATABASE_SCHEMA.md          # Database design documentation
   │   └── USER_MANUAL.md              # User guide and tutorials
   ├── logs/                           # System logs
   │   ├── email_failures.log          # Email delivery logs
   │   ├── mentor_emails.log            # Mentor system logs
   │   └── weekly_notifications.log     # Notification logs
   ├── .env.example                    # Environment template
   ├── .gitignore                      # Git ignore rules
   ├── .htaccess                       # Apache configuration
   ├── composer.json                   # Dependencies and scripts
   ├── composer.lock                   # Dependency lock file
   ├── phpcs.xml                       # Code style configuration
   ├── phpstan.neon                    # Static analysis configuration
   ├── phpunit.xml                     # Test configuration
   ├── ERROR_FIX_REPORT.md             # Error resolution documentation
   ├── PRODUCTION_DEPLOYMENT.md        # Production deployment guide
   ├── PRODUCTION_SETUP.md             # Production setup instructions
   ├── production_status.php           # System health monitoring
   └── SECURITY.md                     # Security policy and guidelines
   ```

   ## 🔧 Database Schema

   The system uses a comprehensive database schema with 30+ tables:

   ### Core Tables
   - **register**: User accounts with GitHub integration and role management
   - **projects**: Project submissions with detailed metadata
   - **admin_approved_projects**: Approved projects with enhanced details
   - **denial_projects**: Rejected projects with reasons
   - **blog**: Ideas and blog posts with engagement tracking

   ### Mentor System
   - **mentors**: Mentor profiles and specializations
   - **mentor_student_pairs**: Active mentor-student relationships
   - **mentoring_sessions**: Session scheduling with meeting links
   - **mentor_requests**: Pairing requests with status tracking
   - **mentor_email_queue**: Email queue with priority management
   - **mentor_email_logs**: Email delivery tracking
   - **mentor_email_stats**: Email performance analytics
   - **mentor_activity_logs**: Activity tracking
   - **mentor_project_access**: Project access permissions

   ### Admin & SubAdmin
   - **subadmins**: SubAdmin accounts with classifications
   - **subadmin_classification_requests**: Classification change requests
   - **support_tickets**: Support ticket system
   - **support_ticket_replies**: Ticket conversation threads
   - **admin_settings**: System configuration
   - **admin_logs**: Administrative actions

   ### Engagement & Interaction
   - **project_likes**: Project engagement tracking
   - **idea_likes**: Idea engagement tracking
   - **project_comments**: Project discussions with nested comments
   - **idea_comments**: Idea discussions
   - **comment_likes**: Comment engagement
   - **bookmark**: User bookmarks

   ### Content Moderation
   - **idea_reports**: Content reporting system
   - **idea_warnings**: User warnings
   - **deleted_ideas**: Deleted content tracking

   ### Notifications & Communication
   - **notification_logs**: Email notification tracking
   - **notification_counters**: Notification statistics
   - **notification_templates**: Email templates
   - **realtime_notifications**: In-app notifications
   - **student_email_preferences**: Email preferences

   ### System & Security
   - **user_activity_log**: User activity tracking
   - **temp_project_ownership**: Temporary project ownership
   - **removed_user**: Deleted user records

   ## 🧪 Testing

   Comprehensive test suite with multiple testing levels:

   ### Test Types
   - **Unit Tests**: Core functionality and validation
   - **Integration Tests**: Database operations and GitHub API
   - **Functional Tests**: Complete user workflows
   - **Performance Tests**: Load testing and optimization
   - **UI Tests**: JavaScript functionality

   ### Running Tests
   ```bash
   # Run all tests
   ./tests/run_tests.sh

   # Run specific test suites
   composer test-unit
   composer test-integration
   composer test-functional

   # Code quality checks
   composer phpcs
   composer phpstan
   composer quality
   ```

   ### Test Configuration
   - **PHPUnit**: Unit and integration testing framework
   - **PHP_CodeSniffer**: PSR-12 coding standards
   - **PHPStan**: Static analysis (Level 5)
   - **Guzzle**: HTTP testing for API integrations

   ## 🔧 Configuration

   ### Environment Setup
   1. **Copy environment file:**
      ```bash
      cp .env.example .env
      ```

   2. **Configure database and email settings in .env**

   ### GitHub Integration
   1. **User Configuration:**
      - Login to your account
      - Go to Profile Settings
      - Enter GitHub username
      - Click "Sync Now" for automatic synchronization

   ### Email System
   1. **Admin Panel Configuration:**
      - Login as admin
      - Go to Settings → Email Configuration
      - Configure SMTP settings
      - Test email delivery

   2. **Cron Job Setup:**
      ```bash
      cd cron
      ./setup_cron.sh
      
      # For production (weekly notifications)
      # Edit crontab: 0 9 * * 0
      ```

   ### Google OAuth
   1. Create Google Cloud Console project
   2. Enable Google+ API
   3. Create OAuth 2.0 credentials
   4. Update client ID in google_config.php

   ## 🔧 Troubleshooting

   ### Common Issues
   - **403/500 Errors**: Check .htaccess and file permissions, verify Apache mod_rewrite
   - **Google OAuth**: Verify client ID, authorized domains, and API credentials
   - **Email Issues**: Check SMTP credentials, app passwords, and firewall settings
   - **File Uploads**: Verify upload directory permissions (755) and PHP upload limits
   - **GitHub Integration**: Check API connectivity, rate limits, and authentication tokens
   - **Cron Jobs**: Ensure proper permissions, paths, and crontab configuration
   - **Database Connection**: Verify credentials, host connectivity, and database existence
   - **Session Issues**: Check session directory permissions and PHP session configuration

   ### File Permissions
   ```bash
   chmod 755 user/uploads/
   chmod 755 user/forms/uploads/
   chmod 755 logs/
   chmod 755 assets/
   chmod 644 .env
   chmod 644 .htaccess
   chmod +x cron/*.sh
   ```

   ### Debug Mode
   ```bash
   # Enable error reporting for debugging
   echo "error_reporting = E_ALL" >> .htaccess
   echo "display_errors = On" >> .htaccess
   
   # Check system status
   php production_status.php
   
   # View error logs
   tail -f logs/email_failures.log
   tail -f logs/mentor_emails.log
   ```

   ### Performance Optimization
   ```bash
   # Enable PHP OPcache
   echo "opcache.enable=1" >> php.ini
   
   # Optimize Composer autoloader
   composer dump-autoload --optimize
   
   # Clear application cache
   php -r "array_map('unlink', glob('cache/*.cache'));"
   ```

   ## 🆕 Latest Features & Enhancements

   ### 📊 Advanced Analytics & Reporting
   - **Export Overview Dashboard**: Real-time system statistics with comprehensive data visualization
   - **Engagement Analytics**: Track user interactions, likes, comments, and project views
   - **Performance Metrics**: Monitor system performance, response times, and resource usage
   - **Trend Analysis**: Identify patterns in user behavior and project submissions
   - **Custom Reports**: Generate tailored reports for different stakeholder needs

   ### 🔍 Enhanced Search & Discovery
   - **Advanced Filtering**: Filter projects by category, difficulty, status, and date ranges
   - **Smart Search**: Intelligent search with auto-suggestions and typo tolerance
   - **Bookmark Categories**: Organize saved projects with custom categories
   - **Recommendation Engine**: Suggest relevant projects based on user interests

   ### 📱 Improved User Experience
   - **Loading Components**: Enhanced loading states for better user feedback
   - **Real-time Notifications**: Instant updates for likes, comments, and system events
   - **Responsive Design**: Optimized for mobile and tablet devices
   - **Accessibility**: WCAG 2.1 compliant interface with keyboard navigation
   - **Dark Mode**: Optional dark theme for better user experience

   ### 🔒 Security Enhancements
   - **Advanced CSRF Protection**: Token-based validation for all forms
   - **Rate Limiting**: Prevent abuse with intelligent rate limiting
   - **Security Headers**: XSS protection and clickjacking prevention
   - **Audit Logging**: Comprehensive security event logging
   - **Two-Factor Authentication**: Optional 2FA for enhanced account security

   ### 🚀 Performance Optimizations
   - **Database Optimization**: Query optimization and efficient indexing
   - **Caching Strategy**: Intelligent caching for improved response times
   - **Asset Optimization**: Minified CSS/JS and optimized images
   - **CDN Integration**: Content delivery network support for static assets

   ## 📊 System Specifications

   ### File Upload Limits
   - **Maximum File Size**: 10MB per file (configurable via admin settings)
   - **Supported File Types**: Images (JPG, PNG, GIF), videos (MP4, AVI), PDFs, ZIP files, presentations (PPT, PPTX)
   - **Upload Security**: File type validation, virus scanning, and secure storage
   - **Download Protection**: Secure file access with user verification and access logging
   - **Storage Management**: Organized file structure with automatic cleanup

   ### Database Performance
   - **MySQL/MariaDB**: Optimized queries with prepared statements and connection pooling
   - **Indexing**: Strategic indexes for performance optimization across 30+ tables
   - **Foreign Keys**: Data integrity with cascading operations and referential constraints
   - **Session Management**: Secure PHP session handling with timeout and regeneration
   - **Query Optimization**: Efficient joins and subqueries for complex data retrieval
   - **Backup Strategy**: Automated database backups with point-in-time recovery

   ### Email System Performance
   - **Queue Management**: Priority-based email processing with retry mechanisms
   - **Batch Processing**: Efficient bulk email handling with rate limiting
   - **Delivery Tracking**: Real-time monitoring of email delivery status
   - **Template System**: Customizable email templates with dynamic content
   - **Analytics**: Comprehensive email performance tracking and engagement metrics
   - **SMTP Failover**: Multiple SMTP provider support with automatic failover

   ### System Monitoring
   - **Real-time Analytics**: Live dashboard with system health metrics
   - **Performance Tracking**: Response time monitoring and optimization alerts
   - **Error Logging**: Comprehensive error tracking with automated notifications
   - **Security Monitoring**: Login attempt tracking and suspicious activity detection
   - **Resource Usage**: Memory, CPU, and storage monitoring with alerts

   ## 🔒 Security Features

   ### Data Protection
   - **SQL Injection Prevention**: Prepared statements throughout
   - **XSS Protection**: Input sanitization and output encoding
   - **CSRF Protection**: Token-based validation
   - **File Upload Security**: Type validation and secure storage
   - **Session Management**: Secure sessions with timeout
   - **Password Security**: Bcrypt hashing with salt
   - **Access Control**: Role-based permissions
   - **Activity Logging**: Comprehensive audit trails

   ### Security Monitoring
   - **Error Logging**: Comprehensive error tracking
   - **Security Headers**: XSS and clickjacking protection
   - **Input Validation**: Server-side validation for all inputs
   - **File Access Control**: Protected file downloads
   - **Rate Limiting**: Login attempt protection

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
   - Use proper error handling and logging
   - Implement security best practices

   ### Code Quality
   ```bash
   # Run code quality checks
   composer quality

   # Fix coding standards
   composer phpcs

   # Run static analysis
   composer phpstan
   ```

   ## 📄 License

   This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

   ## 📞 Support

   For support and questions:
   - **Email**: ideanest.ict@gmail.com
   - **GitHub Issues**: Create an issue for bug reports
   - **Security Issues**: See [SECURITY.md](SECURITY.md) for reporting
   - **Production Setup**: See [PRODUCTION_SETUP.md](PRODUCTION_SETUP.md)

   ## 📚 Documentation

   - **[Production Setup Guide](PRODUCTION_SETUP.md)**: Deployment instructions
   - **[Security Policy](SECURITY.md)**: Security guidelines and reporting
   - **[System Design](System%20Design/)**: Architecture diagrams and documentation
   - **[Test Documentation](tests/README.md)**: Testing guidelines and setup

   ## 🙏 Acknowledgments

   - **PHP Community** for security best practices and frameworks
   - **GitHub API** for developer data access and integration
   - **PHPMailer Team** for reliable email delivery solutions
   - **Bootstrap Team** for responsive design framework
   - **Font Awesome** for comprehensive iconography
   - **MariaDB/MySQL** for robust database management
   - **Apache Foundation** for web server technology
   - **Composer** for dependency management
   - **PHPUnit** for testing framework

   ---

   **Made with ❤️ by the IdeaNest Team**

   *Empowering academic collaboration through innovative technology*