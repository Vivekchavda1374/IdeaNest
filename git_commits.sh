#!/bin/bash

# Git Commit Script - Functionality-wise commits for IdeaNest
# Run this script to commit all changes in organized, feature-based commits

echo "🚀 Starting Git commits for IdeaNest features..."
echo ""

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo "❌ Not a git repository. Initializing..."
    git init
    git branch -M main
fi

# Function to commit with message
commit_feature() {
    local message="$1"
    shift
    local files=("$@")
    
    echo "📝 Committing: $message"
    git add "${files[@]}"
    git commit -m "$message" || echo "⚠️  Nothing to commit for: $message"
    echo ""
}

# 1. Database Schema and Migrations
echo "=== 1. Database Schema & Migrations ==="
commit_feature "feat(database): add mentor requests table and migrations" \
    "db/migrations/fix_mentor_requests.sql" \
    "db/migrations/verify_and_fix.sql" \
    "db/migrations/apply_fix.php" \
    "db/fix_mentor_requests_complete.sql" \
    "db/create_mentor_requests_simple.sql"

commit_feature "feat(database): add mentoring sessions table structure" \
    "db/migrations/fix_mentoring_sessions.sql" \
    "db/check_and_fix_now.sql"

commit_feature "feat(database): add complete idea/blog system tables" \
    "db/create_idea_tables_complete.sql" \
    "db/create_blog_table_simple.sql" \
    "db/step1_create_blog.sql" \
    "db/step2_create_idea_tables.sql"

commit_feature "feat(database): add dummy data scripts for testing" \
    "db/add_dummy_ideas.sql" \
    "db/useful_queries.sql" \
    "db/view_sessions.sql" \
    "db/check_pairing_availability.sql" \
    "db/check_mentor_requests.sql" \
    "db/fix_session_3.sql"

# 2. Mentor Request System
echo "=== 2. Mentor Request System ==="
commit_feature "feat(mentor-requests): implement user mentor request functionality" \
    "user/select_mentor.php" \
    "user/my_mentor_requests.php"

commit_feature "feat(mentor-requests): add mentor view for student requests" \
    "mentor/student_requests.php"

commit_feature "docs(mentor-requests): add comprehensive documentation" \
    "MENTOR_REQUEST_FIX.md" \
    "FIX_MENTOR_REQUESTS_NOW.md" \
    "QUICK_FIX_GUIDE.md"

# 3. Session Scheduling System
echo "=== 3. Session Scheduling System ==="
commit_feature "feat(sessions): implement session scheduling functionality" \
    "mentor/schedule_session.php" \
    "mentor/sessions.php" \
    "mentor/update_session.php"

commit_feature "feat(sessions): add session management UI and forms" \
    "mentor/schedule_session_form.php" \
    "mentor/create_session.php"

commit_feature "docs(sessions): add session scheduling documentation" \
    "SESSION_SCHEDULING_FIX.md" \
    "SESSION_FIX_QUICKSTART.md" \
    "FIX_SESSIONS_NOT_SHOWING.md" \
    "SESSION_UI_COMPLETE.md"

# 4. Smart Pairing System
echo "=== 4. Smart Pairing System ==="
commit_feature "feat(pairing): implement smart mentor-student pairing" \
    "mentor/smart_pairing.php" \
    "mentor/pair_student.php" \
    "mentor/complete_pairing.php"

commit_feature "docs(pairing): add smart pairing documentation" \
    "FIX_SMART_PAIRING.md"

# 5. Email System
echo "=== 5. Email System ==="
commit_feature "feat(email): implement mentor email management system" \
    "mentor/send_email.php" \
    "mentor/send_email_complete.php" \
    "mentor/email_system.php" \
    "mentor/email_dashboard.php" \
    "mentor/automated_emails.php"

commit_feature "feat(email): add session reminder system" \
    "mentor/session_reminder_system.php" \
    "mentor/session_reminder_system.php.backup"

# 6. Mentor Dashboard
echo "=== 6. Mentor Dashboard ==="
commit_feature "feat(mentor): implement comprehensive mentor dashboard" \
    "mentor/dashboard.php" \
    "mentor/mentor_layout.php" \
    "mentor/mentor_layout.php.backup"

commit_feature "feat(mentor): add mentor profile and activity tracking" \
    "mentor/profile.php" \
    "mentor/activity.php" \
    "mentor/analytics.php"

commit_feature "feat(mentor): add student management features" \
    "mentor/students.php" \
    "mentor/student_details.php" \
    "mentor/student_progress.php" \
    "mentor/get_mentor_students.php" \
    "mentor/get_student_projects.php"

commit_feature "feat(mentor): add progress tracking system" \
    "mentor/progress_tracking.php"

# 7. Blog/Idea System
echo "=== 7. Blog/Idea System ==="
commit_feature "feat(ideas): implement idea listing and management" \
    "user/Blog/list-project.php" \
    "user/Blog/edit.php"

commit_feature "style(ideas): enhance edit page with modern design" \
    "assets/css/edit_idea.css" \
    "assets/js/edit_idea.js"

commit_feature "feat(ideas): add AJAX functionality for interactions" \
    "assets/js/idea_ajax.js"

commit_feature "docs(ideas): add idea system documentation" \
    "FIX_IDEA_TABLES.md" \
    "QUICK_FIX_BLOG_TABLES.md" \
    "ADD_DUMMY_IDEAS_GUIDE.md"

# 8. User Interface & Assets
echo "=== 8. User Interface & Assets ==="
commit_feature "feat(ui): add loading and loader components" \
    "assets/css/loading.css" \
    "assets/css/loader.css" \
    "assets/js/loading.js" \
    "assets/js/loader.js"

commit_feature "feat(ui): add AJAX notifications system" \
    "assets/css/ajax_notifications.css"

commit_feature "feat(ui): add user layout components" \
    "user/layout.php"

# 9. Diagnostic and Testing Tools
echo "=== 9. Diagnostic & Testing Tools ==="
commit_feature "feat(tools): add diagnostic scripts for debugging" \
    "check_mentor_requests.php" \
    "check_students.php" \
    "check_sessions.php" \
    "debug_sessions.php" \
    "test_db_connection.php" \
    "quick_check.php"

commit_feature "feat(tools): add mentor testing utilities" \
    "mentor/test_schedule_session.php" \
    "mentor/api/"

# 10. Documentation
echo "=== 10. Documentation ==="
commit_feature "docs(general): add comprehensive setup guides" \
    "SOLUTION_SUMMARY.md" \
    "QUICK_START.md" \
    "SYSTEM_FLOW.md" \
    "README_MENTOR_FIX.md"

commit_feature "docs(fixes): add troubleshooting guides" \
    "SIMPLE_FIX_STEPS.md" \
    "SIMPLE_2_STEP_FIX.md" \
    "QUICK_FIX_SUMMARY.txt"

commit_feature "docs(security): add security documentation" \
    "SECURITY.md" \
    "INJECTION_FIX_GUIDE.md"

# 11. Configuration Files
echo "=== 11. Configuration Files ==="
commit_feature "chore(config): add project configuration files" \
    ".env" \
    ".gitignore" \
    "composer.json" \
    "composer.lock" \
    "php.ini" \
    "phpcs.xml" \
    "phpstan.neon" \
    "phpunit.xml"

commit_feature "chore(config): add security and includes" \
    "includes/security_init.php" \
    "includes/csrf.php" \
    "includes/validation.php" \
    "includes/autoload_simple.php" \
    "includes/loader.php"

# 12. Admin Features
echo "=== 12. Admin Features ==="
commit_feature "feat(admin): add admin dashboard and management" \
    "Admin/admin.php" \
    "Admin/admin_view_project.php" \
    "Admin/sidebar_admin.php"

commit_feature "feat(admin): add mentor management features" \
    "Admin/add_mentor.php" \
    "Admin/manage_mentors.php" \
    "Admin/mentor_details.php" \
    "Admin/remove_mentor.php"

commit_feature "feat(admin): add user management features" \
    "Admin/user_manage_by_admin.php" \
    "Admin/user_details.php"

commit_feature "feat(admin): add notification system" \
    "Admin/notifications.php" \
    "Admin/notification_dashboard.php" \
    "Admin/notification_backend.php" \
    "Admin/project_notification.php"

commit_feature "feat(admin): add data export functionality" \
    "Admin/export_data.php" \
    "Admin/export_all_data.php" \
    "Admin/export_comprehensive_data.php" \
    "Admin/export_overview.php"

commit_feature "feat(admin): add project approval system" \
    "Admin/project_approvel.php" \
    "Admin/manage_reported_ideas.php" \
    "Admin/view_reports.php"

commit_feature "feat(admin): add system analytics and settings" \
    "Admin/system_analytics.php" \
    "Admin/settings.php" \
    "Admin/view_credentials.php"

commit_feature "feat(admin): add subadmin management" \
    "Admin/subadmin/" \
    "Admin/subadmin_overview.php"

# 13. Remaining Files
echo "=== 13. Miscellaneous ==="
commit_feature "feat(core): add main application files" \
    "index.php" \
    "README.md"

commit_feature "feat(login): add authentication system" \
    "Login/"

commit_feature "feat(user): add user features" \
    "user/"

commit_feature "feat(mentor): add remaining mentor features" \
    "mentor/logout.php" \
    "mentor/change_password.php" \
    "mentor/export_data.php" \
    "mentor/export_sessions.php" \
    "mentor/get_notifications.php" \
    "mentor/projects.php"

commit_feature "chore(assets): add remaining assets" \
    "assets/"

commit_feature "chore(misc): add configuration and utility files" \
    "config/" \
    "scripts/" \
    "tests/" \
    "logs/" \
    "backups/" \
    "cron/" \
    "error_pages/" \
    "vendor/"

echo ""
echo "✅ All commits completed!"
echo ""
echo "📊 Commit Summary:"
git log --oneline -20
echo ""
echo "🌿 Current branch: $(git branch --show-current)"
echo ""
echo "Next steps:"
echo "1. Review commits: git log"
echo "2. Create PR: Use create_pr.md template"
echo "3. Push to remote: git push origin main"
