# IdeaNest - File Documentation

## Project Structure Overview

### Root Directory Files

#### Core Application Files
- **index.php** - Main landing page with responsive design, features showcase, testimonials, and authentication options
- **run_tests.php** - Test suite runner for comprehensive application testing
- **composer.json** - PHP dependency management configuration (PHPMailer)
- **composer.lock** - Locked dependency versions for consistent installations
- **.htaccess** - Apache web server configuration for URL rewriting and security
- **.gitIgnore** - Git version control ignore patterns

#### Database & Setup Files
- **github_integration_update.sql** - Database schema updates for GitHub integration features

#### Documentation Files
- **README.md** - Comprehensive project documentation and setup guide
- **SECURITY.md** - Security policies and vulnerability reporting guidelines
- **TEST_REPORT.md** - Detailed testing results and coverage reports
- **TESTING_GUIDE.md** - Testing procedures and guidelines
- **TESTING_REPORT.md** - Additional testing documentation

### Admin Directory (/Admin/)

#### Main Admin Files
- **admin_dashboard.php** - Enhanced admin dashboard with system analytics and overview
- **admin.php** - Core admin functionality and routing
- **admin_view_project.php** - Detailed project viewing interface for administrators
- **overview.php** - System overview with statistics and key metrics
- **system_analytics.php** - Advanced analytics dashboard with performance metrics
- **logout.php** - Admin session termination and cleanup

#### User Management
- **user_manage_by_admin.php** - Complete user management interface with role controls
- **user_details.php** - Detailed user profile management and editing
- **export_data.php** - Data export functionality (CSV, PDF, Excel formats)
- **export_overview.php** - Export management and overview interface

#### Mentor Management
- **add_mentor.php** - Mentor account creation and setup interface
- **manage_mentors.php** - Comprehensive mentor management dashboard
- **mentor_details.php** - Detailed mentor profile viewing and editing
- **remove_mentor.php** - Mentor account removal with safety checks

#### Project Management
- **project_approvel.php** - Final project approval workflow for administrators
- **project_notification.php** - Project-related notification management

#### Notification System
- **notification_dashboard.php** - Email notification monitoring and analytics
- **notification_backend.php** - Backend processing for notification system
- **notifications.php** - General notification management interface
- **test_email_admin.php** - Email system testing and validation

#### SubAdmin Management
- **subadmin_overview.php** - SubAdmin performance tracking and management

#### Configuration
- **settings.php** - System-wide configuration and SMTP settings
- **sidebar_admin.php** - Admin navigation sidebar component

#### Assets
- **logo-no-background.png** - IdeaNest logo for admin interface

### Admin/subadmin Directory (/Admin/subadmin/)

#### SubAdmin Core Files
- **dashboard.php** - SubAdmin dashboard with project assignment overview
- **assigned_projects.php** - Project review queue and assignment management
- **profile.php** - SubAdmin profile management and settings
- **support.php** - Ticket-based support system with admin communication
- **add_subadmin.php** - New SubAdmin account creation interface
- **sidebar_subadmin.php** - SubAdmin navigation sidebar component

### Assets Directory (/assets/)

#### CSS Stylesheets (/assets/css/)
- **layout_user.css** - Main user interface styling and responsive design
- **login.css** - Authentication pages styling
- **register.css** - User registration form styling
- **admin_view_project.css** - Admin project viewing interface styles
- **project_approvel.css** - Project approval workflow styling
- **user_manage_by_admin.css** - User management interface styles
- **sidebar_admin.css** - Admin sidebar navigation styling
- **sidebar_subadmin.css** - SubAdmin sidebar styling
- **subadmin_dashboard.css** - SubAdmin dashboard specific styles
- **assigned_projects.css** - Project assignment interface styling
- **support_subadmin.css** - Support system interface styling
- **new_project_add.css** - Project submission form styling
- **list_project.css** - Project listing and grid view styles
- **bookmark.css** - Bookmark functionality styling
- **form_idea.css** - Idea submission form styling
- **edit_idea.css** - Idea editing interface styling
- **project_details.css** - Detailed project view styling
- **user_profile.css** - User profile page styling
- **setting.css** - Settings page styling
- **notification_toggle.css** - Notification preferences styling
- **index.css** - Landing page specific styles

#### JavaScript Files (/assets/js/)
- **layout_user.js** - Main user interface interactions and navigation
- **login.js** - Authentication form validation and interactions
- **register.js** - Registration form validation and user feedback
- **admin_view_project.js** - Admin project viewing functionality
- **user_manage_by_admin.js** - User management interface interactions
- **sidebar_admin.js** - Admin sidebar navigation functionality
- **sidebar_subadmin.js** - SubAdmin sidebar interactions
- **support_subadmin.js** - Support system functionality
- **new_project_add.js** - Project submission form validation
- **list_project.js** - Project listing interactions and filtering
- **edit_idea.js** - Idea editing form functionality
- **user_profile.js** - Profile management interactions
- **setting.js** - Settings page functionality

### Config Directory (/config/)

#### Security Configuration
- **security.php** - Production-ready security headers, CSRF protection, and security policies

### Cron Directory (/cron/)

#### Email Notification System
- **weekly_notifications.php** - Automated weekly digest email system
- **mentor_email_cron.php** - Mentor-specific email automation
- **test_notifications.php** - Email system testing and validation
- **notification.log** - Email delivery logs and error tracking

#### System Maintenance
- **setup_cron.sh** - Automated cron job setup script
- **setup_mentor_email_cron.sh** - Mentor email system cron setup
- **stop_cron.php** - Cron job management and termination
- **check_mysql.sh** - Database connectivity monitoring

### Database Directory (/db/)

#### Database Schemas
- **ideanest.sql** - Main database schema with all tables and relationships
- **mentor_email_tables.sql** - Email system specific database tables

### Includes Directory (/includes/)

#### Error Handling
- **error_handler.php** - Centralized error handling and logging system

### Login Directory (/Login/Login/)

#### Authentication System
- **login.php** - User authentication with traditional and Google OAuth
- **register.php** - User registration with validation and verification
- **logout.php** - Session termination and cleanup
- **dashboard.php** - Post-login dashboard routing
- **db.php** - Database connection configuration
- **forgot_password.php** - Password recovery with OTP verification

#### Google OAuth Integration
- **google_auth.php** - Google OAuth authentication handler
- **google_callback.php** - OAuth callback processing
- **google_config.php** - Google OAuth configuration
- **setup_google_auth.php** - Google OAuth setup and configuration

### Mentor Directory (/mentor/)

#### Core Mentor Features
- **dashboard.php** - Mentor dashboard with analytics and student overview
- **students.php** - Student management and pairing interface
- **sessions.php** - Mentoring session management system
- **projects.php** - Student project review and guidance
- **profile.php** - Mentor profile management and expertise areas
- **analytics.php** - Mentoring effectiveness and engagement metrics
- **login.php** - Mentor-specific authentication
- **logout.php** - Mentor session termination

#### Student Management
- **pair_student.php** - Manual student-mentor pairing interface
- **complete_pairing.php** - Pairing completion and confirmation
- **smart_pairing.php** - AI-powered student-mentor matching
- **student_details.php** - Individual student profile and progress
- **student_progress.php** - Progress tracking and milestone management
- **student_requests.php** - Student mentorship request management

#### Session Management
- **schedule_session.php** - Session scheduling with calendar integration
- **create_session.php** - New session creation interface
- **update_session.php** - Session modification and rescheduling

#### Email System
- **email_dashboard.php** - Email analytics and delivery monitoring
- **email_system.php** - Core email functionality and templates
- **email_templates.php** - Email template management
- **send_email.php** - Email composition and sending interface
- **automated_emails.php** - Automated email triggers and scheduling

#### Data Management
- **export_data.php** - Student progress and session data export
- **export_sessions.php** - Session data export functionality
- **get_notifications.php** - Notification retrieval API
- **get_student_projects.php** - Student project data API

#### System Management
- **setup_database.php** - Mentor system database initialization
- **change_password.php** - Mentor password change functionality
- **mentor_layout.php** - Mentor interface layout and navigation

#### API Endpoints (/mentor/api/)
- **get_request_count.php** - Student request count API endpoint

### Tests Directory (/tests/)

#### Test Framework
- **TestRunner.php** - Comprehensive test suite execution
- **UnitTestFramework.php** - Custom unit testing framework

#### Test Categories
- **SecurityTest.php** - Security vulnerability and protection testing
- **DatabaseTest.php** - Database schema and operation testing
- **ValidationTest.php** - Input validation and sanitization testing
- **GitHubServiceTest.php** - GitHub integration functionality testing
- **APITest.php** - API endpoint and response testing
- **PerformanceTest.php** - Performance benchmarking and optimization testing
- **IntegrationTest.php** - Component integration testing
- **FunctionalTest.php** - Feature functionality testing
- **E2ETest.php** - End-to-end user workflow testing

### User Directory (/user/)

#### Core User Features
- **index.php** - User dashboard with project overview and quick actions
- **layout.php** - Main user interface layout with navigation sidebar
- **layout_footer.php** - User interface footer component
- **all_projects.php** - Project browsing and discovery interface
- **bookmark.php** - Bookmarked projects management
- **search.php** - Advanced project search and filtering

#### Project Management
- **edit_project.php** - Project editing and modification interface
- **download.php** - Secure project file download system
- **comment_actions.php** - Project commenting system backend

#### GitHub Integration
- **github_service.php** - GitHub API integration and profile management
- **user_profile_setting.php** - User profile with GitHub integration settings

#### Mentor System
- **select_mentor.php** - Mentor browsing and selection interface
- **my_mentor_requests.php** - Student mentorship request tracking

### User/Blog Directory (/user/Blog/)

#### Idea Management System
- **form.php** - Idea submission form with rich text editing
- **list-project.php** - Idea browsing with like/comment system
- **edit.php** - Idea editing and modification interface
- **config.php** - Blog/Idea system configuration and processing

### User/forms Directory (/user/forms/)

#### Project Submission
- **new_project_add.php** - Comprehensive project submission form with file uploads

#### File Storage (/user/forms/uploads/)
- **uploads/** - Secure file storage with organized directory structure
  - **images/** - Project images and screenshots
  - **videos/** - Project demonstration videos
  - **code_files/** - Source code and technical files
  - **presentations/** - Project presentations and slides
  - **instructions/** - Project documentation and instructions
  - **additional/** - Additional project files and resources

### Vendor Directory (/vendor/)

#### Composer Dependencies
- **autoload.php** - Composer autoloader
- **composer/** - Composer internal files and metadata
- **phpmailer/** - PHPMailer email library for reliable email delivery

## File Purpose Summary

### Authentication & Security
- Secure login/registration with Google OAuth
- Session management and CSRF protection
- Password recovery with OTP verification
- Role-based access control (Student/SubAdmin/Admin/Mentor)

### Project Management
- Multi-file project submission with validation
- Three-tier approval workflow (User → SubAdmin → Admin)
- Project editing and version control
- Secure file storage and download system

### Mentor System
- Student-mentor pairing (manual and AI-powered)
- Session scheduling with calendar integration
- Progress tracking and analytics
- Automated email notifications

### Admin Features
- Comprehensive system analytics
- User and mentor management
- Data export capabilities
- Email notification monitoring

### GitHub Integration
- Profile connection and repository showcase
- Real-time statistics synchronization
- Professional profile pages

### Email System
- Weekly digest notifications
- Mentor communication automation
- User preference management
- Delivery tracking and analytics

### Testing & Quality
- Comprehensive test suite (100% coverage)
- Security vulnerability testing
- Performance benchmarking
- End-to-end workflow testing

This documentation provides a complete overview of every file in the IdeaNest project, their purposes, and how they contribute to the overall platform functionality.