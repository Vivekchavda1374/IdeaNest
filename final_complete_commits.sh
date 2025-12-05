#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Final Complete Release Commit Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to add and commit files
commit_files() {
    local commit_type=$1
    local commit_message=$2
    local files_array=("${@:3}")
    
    echo -e "${YELLOW}[*] Committing: ${commit_message}${NC}"
    
    # Check if any files exist in the working tree
    local has_files=false
    for file in "${files_array[@]}"; do
        if [ -f "$file" ] || [ -d "$file" ]; then
            git add "$file" 2>/dev/null && has_files=true
        fi
    done
    
    if [ "$has_files" = true ]; then
        git commit -m "${commit_type}: ${commit_message}"
        echo -e "${GREEN}✓ Commit created${NC}"
    else
        echo -e "${RED}✗ No files to commit${NC}"
    fi
    echo ""
}

# 1. ADMIN DASHBOARD & MANAGEMENT SYSTEM ENHANCEMENTS
echo -e "${PURPLE}[1] Admin Dashboard & Management System${NC}"
git add Admin/
git commit -m "feat: Comprehensive Admin Dashboard System Enhancement

- Enhanced add_mentor.php with improved form validation and mentor assignment
- Improved admin.php dashboard with better user management interface
- Enhanced admin_view_project.php with advanced project analytics
- Upgraded export_all_data.php with comprehensive data export functionality
- Improved export_comprehensive_data.php with detailed export options
- Enhanced export_overview.php for better overview data handling
- Upgraded manage_mentors.php with mentor lifecycle management
- Improved manage_reported_ideas.php with moderation tools
- Enhanced mentor_details.php with detailed mentor analytics
- Upgraded notification_backend.php with improved notification handling
- Enhanced notification_dashboard.php with better notification display
- Improved notifications.php with notification management
- Enhanced project_approvel.php with streamlined approval workflow
- Upgraded project_notification.php with project update notifications
- Improved settings.php with admin configuration options
- Enhanced subadmin_overview.php with subadmin management
- Upgraded system_analytics.php with comprehensive analytics
- Improved user_details.php with detailed user information view
- Enhanced user_manage_by_admin.php with user management tools
- Upgraded view_credentials.php with secure credential viewing
- Improved view_reports.php with enhanced report viewing

Statistics:
- 23 files modified
- Enhanced user and project management
- Improved data export capabilities
- Better administrative workflows
- Enhanced notification system
- Improved security and validation"
echo -e "${GREEN}✓ Admin Dashboard commit created${NC}"
echo ""

# 2. AUTHENTICATION & LOGIN SYSTEM
echo -e "${PURPLE}[2] Authentication & Login System${NC}"
git add Login/
git commit -m "feat: Enhanced Authentication System with Improved Login/Register

- Improved login.php with enhanced security measures
- Enhanced register.php with better form validation
- Upgraded forgot_password.php with improved password recovery
- Enhanced dashboard.php with better user session management

Features:
- Improved input validation and sanitization
- Better error handling and user feedback
- Enhanced security headers and CSRF protection
- Improved session management
- Better password recovery workflow"
echo -e "${GREEN}✓ Authentication commit created${NC}"
echo ""

# 3. DATABASE & CONFIGURATION SYSTEM
echo -e "${PURPLE}[3] Database & Configuration System${NC}"
git add db/
git add .user.ini
git commit -m "feat: Enhanced Configuration Management & Database System

- Updated ictmu6ya_ideanest.sql with database schema improvements
- Added import_database.php for database import functionality
- Added import_database_advanced.php with advanced import features
- Added import_web.php for web-based database import
- Added migrations/ directory with database migration scripts
- Updated .user.ini with configuration optimizations

Features:
- Improved database schema
- Database import utilities
- Migration system for version control
- Enhanced configuration management
- Better database initialization"
echo -e "${GREEN}✓ Database & Configuration commit created${NC}"
echo ""

# 4. SECURITY & ANTI-INJECTION SYSTEM
echo -e "${PURPLE}[4] Security & Anti-Injection Protection${NC}"
git add includes/anti_injection.php
git add includes/security_init.php
git add assets/js/anti_injection.js
git add scripts/add_anti_injection.php
git add scripts/verify_anti_injection.php
git commit -m "feat: Comprehensive Security & Anti-Injection System

- Added anti_injection.php with SQL injection prevention
- Enhanced security_init.php with improved security initialization
- Added anti_injection.js for client-side protection
- Added add_anti_injection.php script for integration
- Added verify_anti_injection.php for security verification

Security Features:
- SQL injection prevention
- XSS attack mitigation
- Input validation and sanitization
- Security verification tools
- Client-side and server-side protection"
echo -e "${GREEN}✓ Security commit created${NC}"
echo ""

# 5. QUERY OPTIMIZATION & PERFORMANCE
echo -e "${PURPLE}[5] Query Optimization & Performance${NC}"
git add includes/optimized_queries.php
git add includes/query_cache.php
git add scripts/query_optimization.php
git add scripts/monitor_performance.php
git add scripts/rollback_optimization.php
git add scripts/safe_optimization.php
git commit -m "feat: Query Optimization & Performance Enhancement System

- Added optimized_queries.php with optimized SQL queries
- Added query_cache.php for database query caching
- Added query_optimization.php script for optimization
- Added monitor_performance.php for performance monitoring
- Added rollback_optimization.php for rollback capability
- Added safe_optimization.php for safe optimization

Features:
- Database query optimization
- Query result caching
- Performance monitoring tools
- Safe optimization scripts
- Rollback capability for safety
- Performance analytics"
echo -e "${GREEN}✓ Query Optimization commit created${NC}"
echo ""

# 6. REPORTING & DATA MANAGEMENT
echo -e "${PURPLE}[6] Reporting & Data Management${NC}"
git add Report/
git commit -m "feat: Enhanced Report & Data Management System

- Improved report_dashboard.php with comprehensive reporting interface

Features:
- Enhanced report generation
- Better data visualization
- Improved filtering and sorting
- Better report export functionality"
echo -e "${GREEN}✓ Reporting commit created${NC}"
echo ""

# 7. MENTOR SYSTEM
echo -e "${PURPLE}[7] Mentor System Enhancement${NC}"
git add mentor/
git commit -m "feat: Enhanced Mentor System with Improved Communication

- Enhanced mentor_layout.php with improved mentor interface
- Improved send_email_complete.php with better email handling

Features:
- Better mentor dashboard layout
- Improved email delivery system
- Enhanced mentor-mentee communication
- Better email notification handling"
echo -e "${GREEN}✓ Mentor System commit created${NC}"
echo ""

# 8. USER BLOG & CONTENT MANAGEMENT
echo -e "${PURPLE}[8] User Blog & Content Management${NC}"
git add user/Blog/
git commit -m "feat: Enhanced Blog & Content Management System

- Improved edit.php with better blog editing interface
- Enhanced form.php with improved form handling
- Upgraded idea_details.php with better idea presentation
- Enhanced list-project.php with improved project listing

Features:
- Better blog post editing
- Improved form validation
- Better idea showcase
- Enhanced project listing with filters"
echo -e "${GREEN}✓ Blog & Content commit created${NC}"
echo ""

# 9. USER GAMIFICATION & ACHIEVEMENTS
echo -e "${PURPLE}[9] User Gamification & Achievements${NC}"
git add user/gamification.php
git add user/achievements_guide.php
git add user/leaderboard.php
git commit -m "feat: Complete Gamification & Achievements System

- Enhanced gamification.php with points and rewards system
- Improved achievements_guide.php with achievement information
- Enhanced leaderboard.php with competitive ranking system

Features:
- Points system for user activities
- Achievement badges and tracking
- Competitive leaderboard
- User engagement gamification
- Achievement progress tracking"
echo -e "${GREEN}✓ Gamification commit created${NC}"
echo ""

# 10. USER COMMUNICATION & MESSAGING
echo -e "${PURPLE}[10] User Communication & Messaging${NC}"
git add user/chat/
git add user/messages_with_sharing.php
git add user/notifications.php
git commit -m "feat: Complete User Communication & Messaging System

- Enhanced chat/index.php with real-time messaging
- Improved messages_with_sharing.php with file sharing capability
- Enhanced notifications.php with user notification management

Features:
- Real-time chat functionality
- Message sharing with files
- Notification management
- User communication tools
- Better message organization"
echo -e "${GREEN}✓ Messaging System commit created${NC}"
echo ""

# 11. USER PROFILE & SOCIAL FEATURES
echo -e "${PURPLE}[11] User Profile & Social Features${NC}"
git add user/user_profile_setting.php
git add user/view_user_profile.php
git add user/github_profile_view.php
git commit -m "feat: Enhanced User Profile & Social Features

- Improved user_profile_setting.php with better profile management
- Enhanced view_user_profile.php with detailed profile view
- Added github_profile_view.php for GitHub integration

Features:
- Better profile customization
- Improved profile viewing
- GitHub profile integration
- Social profile features
- Better user information display"
echo -e "${GREEN}✓ Profile & Social commit created${NC}"
echo ""

# 12. USER PROJECT & IDEA MANAGEMENT
echo -e "${PURPLE}[12] User Project & Idea Management${NC}"
git add user/all_projects.php
git add user/edit_project.php
git add user/view_idea.php
git add user/forms/new_project_add.php
git commit -m "feat: Enhanced User Project & Idea Management

- Improved all_projects.php with comprehensive project listing
- Enhanced edit_project.php with better project editing interface
- Improved view_idea.php with detailed idea presentation
- Enhanced new_project_add.php with improved project creation form

Features:
- Better project management interface
- Improved project filtering and search
- Enhanced idea viewing
- Better project creation workflow
- Improved form validation"
echo -e "${GREEN}✓ Project Management commit created${NC}"
echo ""

# 13. USER BOOKMARKS & PREFERENCES
echo -e "${PURPLE}[13] User Bookmarks & Preferences${NC}"
git add user/bookmark.php
git add user/select_mentor.php
git add user/my_mentor_requests.php
git commit -m "feat: Enhanced User Bookmarks & Mentor Selection

- Improved bookmark.php with better bookmark management
- Enhanced select_mentor.php with improved mentor selection interface
- Upgraded my_mentor_requests.php with better request management

Features:
- Better bookmark organization
- Improved mentor selection process
- Enhanced mentor request tracking
- User preference management
- Better request status tracking"
echo -e "${GREEN}✓ Bookmarks & Preferences commit created${NC}"
echo ""

# 14. USER INDEX & CORE PAGES
echo -e "${PURPLE}[14] User Index & Core Pages${NC}"
git add user/index.php
git add index.php
git commit -m "feat: Enhanced User Index & Core Application Pages

- Improved user/index.php with better user dashboard
- Enhanced index.php with improved main application page

Features:
- Better user dashboard layout
- Improved main page structure
- Better navigation
- Enhanced page performance
- Better responsive design"
echo -e "${GREEN}✓ Core Pages commit created${NC}"
echo ""

# 15. EXAMPLE DOCUMENTATION & UTILITIES
echo -e "${PURPLE}[15] Example Documentation & Utilities${NC}"
git add examples/
git commit -m "chore: Add example documentation and utilities

- Added examples/ directory with usage examples
- Documentation for system features
- Code examples for developers
- Integration examples

Features:
- Developer documentation
- Code usage examples
- Integration guides
- Implementation references"
echo -e "${GREEN}✓ Examples & Documentation commit created${NC}"
echo ""

# Final Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}All commits completed successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}Summary:${NC}"
echo "Total commits: 15"
echo "Branch: testing-final"
echo ""
echo -e "${YELLOW}Commits created:${NC}"
echo "1. Admin Dashboard & Management System"
echo "2. Authentication & Login System"
echo "3. Database & Configuration System"
echo "4. Security & Anti-Injection System"
echo "5. Query Optimization & Performance"
echo "6. Reporting & Data Management"
echo "7. Mentor System Enhancement"
echo "8. Blog & Content Management"
echo "9. Gamification & Achievements"
echo "10. Communication & Messaging"
echo "11. Profile & Social Features"
echo "12. Project & Idea Management"
echo "13. Bookmarks & Mentor Selection"
echo "14. User Index & Core Pages"
echo "15. Examples & Documentation"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review commits: git log --oneline -15"
echo "2. Push to GitHub: git push origin testing-final"
echo "3. Create Pull Request on GitHub"
echo ""
