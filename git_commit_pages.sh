#!/bin/bash

# IdeaNest - Individual Page Git Commits Script
# This script creates separate commits for each page/component of the IdeaNest platform

echo "🚀 IdeaNest - Individual Page Git Commits"
echo "=========================================="

# Initialize git if not already done
if [ ! -d ".git" ]; then
    echo "Initializing Git repository..."
    git init
    git config user.name "IdeaNest Developer"
    git config user.email "ideanest.ict@gmail.com"
fi

# Core Authentication & Login System
echo "📝 Committing Authentication System..."
git add Login/Login/login.php Login/Login/register.php Login/Login/db.php
git commit -m "feat: implement core authentication system with login and registration"

git add Login/Login/google_*.php
git commit -m "feat: add Google OAuth 2.0 integration for social authentication"

git add Login/Login/forgot_password.php
git commit -m "feat: implement password reset functionality with email verification"

# Database Schema
echo "📝 Committing Database Schema..."
git add db/ideanest.sql
git commit -m "feat: create comprehensive database schema with 30+ tables and relationships"

# User Dashboard & Profile
echo "📝 Committing User Interface..."
git add user/index.php
git commit -m "feat: create user dashboard with project overview and statistics"

git add user/user_profile_setting.php
git commit -m "feat: implement user profile management with image upload and settings"

git add user/all_projects.php
git commit -m "feat: create project gallery with filtering and search functionality"

git add user/search.php
git commit -m "feat: implement advanced search functionality for projects and ideas"

# Project Management System
echo "📝 Committing Project Management..."
git add user/forms/new_project_add.php
git commit -m "feat: create project submission form with multi-file upload validation"

git add user/forms/uploads/
git commit -m "feat: implement secure file upload system with access control"

git add user/edit_project.php
git commit -m "feat: add project editing functionality for submitted projects"

git add user/project_details.php
git commit -m "feat: create detailed project view with engagement features"

# Ideas & Blog System
echo "📝 Committing Ideas System..."
git add user/Blog/form.php
git commit -m "feat: create idea submission form with rich content support"

git add user/Blog/list-project.php
git commit -m "feat: implement ideas listing with pagination and filtering"

git add user/Blog/edit.php
git commit -m "feat: add idea editing functionality with version control"

git add user/Blog/report_handler.php
git commit -m "feat: implement content moderation and reporting system"

# Interactive Features
echo "📝 Committing Interactive Features..."
git add user/api/like_project.php user/api/like_idea.php
git commit -m "feat: implement AJAX-powered like system for projects and ideas"

git add user/bookmark.php user/api/bookmark_*.php
git commit -m "feat: create bookmark system for saving favorite projects"

git add user/api/comment_*.php
git commit -m "feat: implement nested comment system with real-time updates"

# GitHub Integration
echo "📝 Committing GitHub Integration..."
git add user/github_profile.php user/github_sync.php
git commit -m "feat: implement GitHub API integration for profile synchronization"

git add user/api/github_*.php
git commit -m "feat: add GitHub repository sync and profile display features"

# Mentor System
echo "📝 Committing Mentor System..."
git add mentor/dashboard.php
git commit -m "feat: create comprehensive mentor dashboard with analytics"

git add mentor/students.php mentor/smart_pairing.php
git commit -m "feat: implement AI-powered student-mentor pairing system"

git add mentor/sessions.php
git commit -m "feat: add session management with meeting links and scheduling"

git add mentor/projects.php
git commit -m "feat: implement mentor project access and review system"

git add mentor/email_system.php mentor/email_dashboard.php
git commit -m "feat: create mentor email system with queue management and analytics"

git add mentor/profile.php mentor/analytics.php
git commit -m "feat: add mentor profile management and performance analytics"

git add mentor/api/
git commit -m "feat: implement mentor API endpoints for real-time functionality"

git add user/select_mentor.php
git commit -m "feat: create mentor selection interface for students"

# SubAdmin System
echo "📝 Committing SubAdmin System..."
git add Admin/subadmin/dashboard.php
git commit -m "feat: create SubAdmin dashboard with project assignment overview"

git add Admin/subadmin/assigned_projects.php
git commit -m "feat: implement project assignment system with classification-based routing"

git add Admin/subadmin/profile.php
git commit -m "feat: add SubAdmin profile management with expertise classification"

git add Admin/subadmin/support.php
git commit -m "feat: create support ticket system for SubAdmin-Admin communication"

# Admin Panel
echo "📝 Committing Admin Panel..."
git add Admin/admin.php
git commit -m "feat: create comprehensive admin dashboard with system analytics"

git add Admin/admin_view_project.php
git commit -m "feat: implement admin project review interface with approval workflow"

git add Admin/manage_mentors.php
git commit -m "feat: add mentor management system with account lifecycle control"

git add Admin/user_manage_by_admin.php
git commit -m "feat: create user management interface with activity tracking"

git add Admin/system_analytics.php
git commit -m "feat: implement system analytics with charts and performance metrics"

git add Admin/notification_dashboard.php
git commit -m "feat: create email monitoring dashboard with delivery tracking"

git add Admin/manage_reported_ideas.php
git commit -m "feat: implement content moderation system for reported ideas"

git add Admin/settings.php
git commit -m "feat: add system settings management with email configuration"

# Data Export System
echo "📝 Committing Data Export..."
git add Admin/export_*.php
git commit -m "feat: implement comprehensive data export system with multiple formats"

# Email Notification System
echo "📝 Committing Email System..."
git add cron/weekly_notifications.php
git commit -m "feat: implement automated weekly digest email notifications"

git add cron/mentor_email_cron.php
git commit -m "feat: create mentor email queue processing with priority management"

git add cron/setup_cron.sh cron/manage_cron.sh
git commit -m "feat: add cron job management scripts for automated tasks"

git add config/email_config.php
git commit -m "feat: implement email configuration system with SMTP settings"

# Email Management Scripts
echo "📝 Committing Email Management..."
git add email_manager.sh email_test_suite.sh
git commit -m "feat: create comprehensive email management and testing suite"

git add email_failure_monitor.sh validate_email_config.sh
git commit -m "feat: implement email failure monitoring and configuration validation"

git add setup_email_system.sh
git commit -m "feat: add automated email system setup and initialization"

# Security & Configuration
echo "📝 Committing Security Features..."
git add config/security.php includes/csrf.php
git commit -m "feat: implement comprehensive security features with CSRF protection"

git add includes/validation.php includes/error_handler.php
git commit -m "feat: add input validation and error handling systems"

git add .htaccess
git commit -m "feat: configure Apache security headers and URL rewriting"

# UI Components & Assets
echo "📝 Committing UI Components..."
git add includes/loading_component.php
git commit -m "feat: create reusable UI components with loading states"

git add assets/css/ assets/js/
git commit -m "feat: implement responsive design with custom CSS and JavaScript"

# Testing Suite
echo "📝 Committing Testing Framework..."
git add tests/Unit/ tests/Integration/
git commit -m "feat: implement comprehensive unit and integration test suite"

git add tests/Functional/ tests/Performance/
git commit -m "feat: add functional and performance testing frameworks"

git add phpunit.xml composer.json
git commit -m "feat: configure testing environment with PHPUnit and Composer"

git add tests/run_tests.sh
git commit -m "feat: create automated test execution scripts"

# Documentation
echo "📝 Committing Documentation..."
git add README.md
git commit -m "docs: create comprehensive project documentation with setup guide"

git add PRODUCTION_SETUP.md SECURITY.md
git commit -m "docs: add production deployment and security policy documentation"

git add EMAIL_MANAGEMENT_README.md
git commit -m "docs: create detailed email system management documentation"

git add System\ Design/
git commit -m "docs: add system architecture diagrams and design documentation"

# Configuration Files
echo "📝 Committing Configuration..."
git add .env.example
git commit -m "config: add environment configuration template"

git add composer.json composer.lock
git commit -m "config: configure Composer dependencies and autoloading"

# Logs Directory
echo "📝 Committing Log Structure..."
git add logs/.gitkeep
git commit -m "feat: create log directory structure for system monitoring"

# Final Commit
echo "📝 Creating Final Integration Commit..."
git add .
git commit -m "feat: complete IdeaNest platform integration with all components

- Multi-role authentication system (Student/SubAdmin/Admin/Mentor)
- Comprehensive project management with three-tier approval
- Interactive ideas and blog system with engagement features
- Advanced mentor system with AI-powered pairing
- Email notification system with queue management
- GitHub integration for developer profiles
- Content moderation and reporting system
- System analytics and performance monitoring
- Comprehensive testing suite with multiple test types
- Production-ready security features and configurations
- Complete documentation and setup guides

Platform ready for academic project management and collaboration."

echo ""
echo "✅ All commits created successfully!"
echo ""
echo "📊 Commit Summary:"
git log --oneline | head -20
echo ""
echo "🎉 IdeaNest platform commits completed!"
echo "Total commits: $(git rev-list --count HEAD)"