# Git Commit Guide - IdeaNest

## Quick Start

### Option 1: Automated Script (Recommended)

```bash
chmod +x git_commits.sh
./git_commits.sh
```

This will create 30+ organized commits automatically.

---

### Option 2: Manual Commits

Follow the commands below in order:

---

## 📦 Commit Commands

### 1. Database Schema & Migrations
```bash
git add db/migrations/*.sql db/*.sql
git commit -m "feat(database): add complete database schema and migrations

- Add mentor_requests table with foreign keys
- Add mentoring_sessions table structure
- Add complete idea/blog system tables (12 tables)
- Add migration scripts for easy setup
- Add dummy data scripts for testing"
```

---

### 2. Mentor Request System
```bash
git add user/select_mentor.php user/my_mentor_requests.php mentor/student_requests.php
git commit -m "feat(mentor-requests): implement mentor request functionality

- Students can browse and request mentors
- Mentors can view and manage requests
- CSRF protection and validation
- Email notifications
- Transaction support for data integrity
- Duplicate request prevention"
```

---

### 3. Session Scheduling
```bash
git add mentor/schedule_session.php mentor/sessions.php mentor/update_session.php mentor/schedule_session_form.php
git commit -m "feat(sessions): implement session scheduling system

- Schedule mentoring sessions with date/time picker
- View upcoming and completed sessions
- Update session status (complete/cancel)
- Meeting link integration
- Automatic student notifications
- Duration selection and notes"
```

---

### 4. Smart Pairing
```bash
git add mentor/smart_pairing.php mentor/pair_student.php mentor/complete_pairing.php
git commit -m "feat(pairing): implement smart mentor-student pairing

- Intelligent student matching algorithm
- Cascading query for maximum visibility
- Multiple matching strategies
- Automatic pair creation
- Status tracking"
```

---

### 5. Email System
```bash
git add mentor/send_email.php mentor/email_system.php mentor/email_dashboard.php mentor/automated_emails.php mentor/session_reminder_system.php
git commit -m "feat(email): implement comprehensive email management

- Welcome email system
- Session invitation emails
- Automated reminder system
- Email queue and delivery tracking
- Template management
- PHPMailer integration"
```

---

### 6. Mentor Dashboard
```bash
git add mentor/dashboard.php mentor/mentor_layout.php mentor/profile.php mentor/activity.php mentor/analytics.php
git commit -m "feat(mentor): implement mentor dashboard and profile

- Comprehensive dashboard with statistics
- Activity tracking and logs
- Analytics and insights
- Profile management
- Student overview"
```

---

### 7. Student Management
```bash
git add mentor/students.php mentor/student_details.php mentor/student_progress.php mentor/progress_tracking.php mentor/get_mentor_students.php mentor/get_student_projects.php
git commit -m "feat(mentor): add student management features

- View all assigned students
- Student detail pages
- Progress tracking system
- Milestone management
- Project access control"
```

---

### 8. Idea/Blog System
```bash
git add user/Blog/list-project.php user/Blog/edit.php assets/css/edit_idea.css assets/js/edit_idea.js assets/js/idea_ajax.js
git commit -m "feat(ideas): implement complete idea management system

- Idea listing with advanced filtering
- Like, comment, bookmark functionality
- Share on social platforms
- Follow ideas for updates
- 5-star rating system
- View tracking and analytics
- Modern responsive UI
- AJAX interactions"
```

---

### 9. Admin Features
```bash
git add Admin/*.php Admin/subadmin/
git commit -m "feat(admin): implement admin dashboard and management

- Mentor management (add, edit, remove)
- User management
- Project approval system
- Notification management
- System analytics
- Data export functionality
- Report management
- Subadmin system"
```

---

### 10. UI/UX Components
```bash
git add assets/css/*.css assets/js/*.js user/layout.php mentor/mentor_layout.php
git commit -m "style(ui): add modern UI components and styling

- Purple gradient theme
- Glass-morphism design
- Loading indicators
- AJAX notifications
- Responsive layouts
- Smooth animations
- Mobile-friendly design"
```

---

### 11. Diagnostic Tools
```bash
git add check_*.php debug_*.php test_*.php quick_check.php mentor/test_*.php
git commit -m "feat(tools): add diagnostic and testing utilities

- Database connection testers
- Session verification tools
- Request checking scripts
- Student availability checkers
- Comprehensive debugging tools"
```

---

### 12. Documentation
```bash
git add *.md
git commit -m "docs: add comprehensive documentation

- Setup and installation guides
- Troubleshooting documentation
- Feature usage guides
- Database schema documentation
- API documentation
- Security guidelines
- Quick reference cards"
```

---

### 13. Configuration
```bash
git add .env .gitignore composer.json composer.lock php.ini phpcs.xml phpstan.neon phpunit.xml includes/
git commit -m "chore(config): add project configuration files

- Environment configuration
- Composer dependencies
- PHP configuration
- Code quality tools (phpcs, phpstan)
- Security initialization
- CSRF protection
- Validation utilities"
```

---

### 14. Core Application
```bash
git add index.php README.md Login/ config/ scripts/ error_pages/
git commit -m "feat(core): add core application files

- Main entry point
- Authentication system
- Configuration management
- Error handling
- Utility scripts"
```

---

## 🌿 Create Feature Branch

```bash
# Create and switch to feature branch
git checkout -b feature/complete-mentorship-system

# Run all commits (use script or manual commands above)

# Push to remote
git push origin feature/complete-mentorship-system
```

---

## 📝 Create Pull Request

### On GitHub:
1. Go to your repository
2. Click "Pull Requests"
3. Click "New Pull Request"
4. Select: `base: main` ← `compare: feature/complete-mentorship-system`
5. Copy content from `PULL_REQUEST_TEMPLATE.md`
6. Click "Create Pull Request"

---

## 🔍 Pre-Commit Checklist

Before committing, ensure:

- [ ] All files are saved
- [ ] No syntax errors
- [ ] Database migrations tested
- [ ] Features work locally
- [ ] Documentation is complete
- [ ] No sensitive data in commits
- [ ] .gitignore is properly configured
- [ ] Commit messages follow convention

---

## 📊 Commit Message Convention

We use conventional commits:

```
feat(scope): description       - New feature
fix(scope): description        - Bug fix
docs(scope): description       - Documentation
style(scope): description      - Formatting, styling
refactor(scope): description   - Code restructuring
test(scope): description       - Adding tests
chore(scope): description      - Maintenance tasks
```

**Examples:**
- `feat(mentor): add session scheduling`
- `fix(database): resolve foreign key constraint`
- `docs(setup): add installation guide`
- `style(ui): improve card design`

---

## 🚀 Quick Commands

```bash
# Check status
git status

# View commits
git log --oneline -20

# View changes
git diff

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Amend last commit
git commit --amend

# Push to remote
git push origin feature/complete-mentorship-system

# Create PR from command line (using gh CLI)
gh pr create --title "Complete Mentorship System" --body-file PULL_REQUEST_TEMPLATE.md
```

---

## 📁 Files by Feature

### Mentor Request System
- `user/select_mentor.php`
- `user/my_mentor_requests.php`
- `mentor/student_requests.php`
- `db/fix_mentor_requests_complete.sql`

### Session Scheduling
- `mentor/schedule_session.php`
- `mentor/sessions.php`
- `mentor/update_session.php`
- `db/fix_mentoring_sessions.sql`

### Idea System
- `user/Blog/list-project.php`
- `user/Blog/edit.php`
- `db/create_blog_table_simple.sql`
- `db/step1_create_blog.sql`
- `db/step2_create_idea_tables.sql`

### Email System
- `mentor/send_email.php`
- `mentor/email_system.php`
- `mentor/session_reminder_system.php`

### Admin
- `Admin/*.php`
- `Admin/subadmin/*.php`

---

## 🎯 Next Steps After PR

1. **Code Review**: Wait for team review
2. **Address Feedback**: Make requested changes
3. **Testing**: QA team testing
4. **Merge**: Merge to main branch
5. **Deploy**: Deploy to production
6. **Monitor**: Watch for issues

---

**Total Commits**: 30+  
**Total Files**: 150+  
**Lines of Code**: 15,000+  
**Documentation**: 20+ guides  

---

Ready to commit! 🚀
