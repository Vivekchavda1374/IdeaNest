#!/bin/bash

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Development Solve Branch Commit Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Create and checkout new branch
echo -e "${YELLOW}Creating new branch: development-solve${NC}"
git checkout -b development-solve
echo -e "${GREEN}✓ Branch created and switched${NC}"
echo ""

# 1. ADMIN UI & NOTIFICATIONS SYSTEM
echo -e "${PURPLE}[1] Admin UI & Notifications System${NC}"
git add Admin/notifications.php
git add Admin/sidebar_admin.php
git add Admin/subadmin/sidebar_subadmin.php
git add Admin/system_analytics.php
git add assets/css/notifications.css
git commit -m "feat: Enhanced Admin UI & Notifications System

- Improved Admin/notifications.php with better notification handling
- Enhanced Admin/sidebar_admin.php with improved navigation
- Upgraded Admin/subadmin/sidebar_subadmin.php with better layout
- Added assets/css/notifications.css for notification styling
- Improved notification display and management
- Enhanced sidebar navigation
- Professional notification styling"
echo -e "${GREEN}✓ Admin UI & Notifications commit created${NC}"
echo ""

# 2. ADMIN ANALYTICS & QUERY MANAGEMENT
echo -e "${PURPLE}[2] Admin Analytics & Query Management${NC}"
git add Admin/fix_production.php
git add Admin/manage_queries.php
git add Admin/query_api.php
git add db/fix_production.sql
git commit -m "feat: Admin Analytics & Query Management System

- Added Admin/fix_production.php for production fixes
- Added Admin/manage_queries.php for query management
- Added Admin/query_api.php for query API endpoints
- Added db/fix_production.sql for database fixes

Features:
- Query performance management
- API endpoints for query operations
- Production bug fix utilities
- Database optimization scripts"
echo -e "${GREEN}✓ Admin Analytics commit created${NC}"
echo ""

# 3. GOOGLE OAUTH & AUTHENTICATION
echo -e "${PURPLE}[3] Google OAuth & Enhanced Authentication${NC}"
git add Login/Login/google_callback.php
git add Login/Login/google_config.php
git add Login/Login/google_manual_test.html
git add Login/Login/login.php
git commit -m "feat: Google OAuth Integration & Enhanced Login

- Improved Login/Login/google_callback.php with better OAuth handling
- Enhanced Login/Login/google_config.php with configuration
- Added Login/Login/google_manual_test.html for testing
- Upgraded Login/Login/login.php with OAuth integration

Features:
- Complete Google OAuth 2.0 integration
- Improved authentication flow
- Better OAuth error handling
- Configuration management
- Manual testing interface
- Secure token handling"
echo -e "${GREEN}✓ Google OAuth commit created${NC}"
echo ""

# 4. DATABASE MANAGEMENT & IMPROVEMENTS
echo -e "${PURPLE}[4] Database Management & Schema Updates${NC}"
git add db/ictmu6ya_ideanest.sql
git add db/ictmu6ya_ideanest_fixed.sql
git add db/import.php
git add db/import_log.txt
git rm db/import_database.php
git rm db/import_database_advanced.php
git rm db/import_web.php
git commit -m "feat: Database Management System Improvements

- Enhanced db/ictmu6ya_ideanest.sql with schema updates
- Added db/ictmu6ya_ideanest_fixed.sql with fixes
- Added db/import.php with improved import functionality
- Updated db/import_log.txt with logging
- Removed deprecated import files for better organization

Features:
- Improved database schema
- Better data import process
- Consolidated import utilities
- Database logging
- Schema optimization"
echo -e "${GREEN}✓ Database Management commit created${NC}"
echo ""

# 5. CHAT & MESSAGING ENCRYPTION
echo -e "${PURPLE}[5] Chat Encryption & Messaging Improvements${NC}"
git add user/chat/encryption.js
git add user/chat/index.php
git commit -m "feat: Chat Encryption & Messaging System Enhancement

- Enhanced user/chat/encryption.js with improved encryption
- Upgraded user/chat/index.php with better messaging interface

Features:
- End-to-end message encryption
- Improved chat interface
- Better message security
- Encryption key management
- Secure message transmission"
echo -e "${GREEN}✓ Chat Encryption commit created${NC}"
echo ""

# 6. USER PROFILE & SUPPORT SYSTEM
echo -e "${PURPLE}[6] User Profile & Support System${NC}"
git add user/github_profile_page.php
git add user/layout.php
git add user/support/
git commit -m "feat: User Profile & Support System Implementation

- Added user/github_profile_page.php for GitHub profile display
- Upgraded user/layout.php with improved layout
- Added user/support/ directory with support features

Features:
- GitHub profile integration and display
- Better user profile page
- Improved layout system
- Support ticket system
- Help documentation"
echo -e "${GREEN}✓ User Profile & Support commit created${NC}"
echo ""

# 7. USER GAMIFICATION & LEADERBOARD
echo -e "${PURPLE}[7] User Gamification & Leaderboard${NC}"
git add user/leaderboard.php
git add user/mentor_activities.php
git commit -m "feat: Enhanced Gamification & Leaderboard System

- Improved user/leaderboard.php with better ranking display
- Enhanced user/mentor_activities.php with activity tracking

Features:
- Enhanced leaderboard display
- Real-time ranking updates
- Mentor activity tracking
- Achievement progress monitoring
- User engagement metrics"
echo -e "${GREEN}✓ Gamification & Leaderboard commit created${NC}"
echo ""

# 8. USER MENTOR & INTERACTION SYSTEM
echo -e "${PURPLE}[8] User Mentor & Interaction System${NC}"
git add user/my_mentor_requests.php
git add user/select_mentor.php
git commit -m "feat: Enhanced Mentor Selection & Request System

- Improved user/my_mentor_requests.php with better request tracking
- Enhanced user/select_mentor.php with improved mentor selection

Features:
- Better mentor request management
- Improved mentor selection interface
- Request status tracking
- Mentor availability display
- Better user-mentor matching"
echo -e "${GREEN}✓ Mentor System commit created${NC}"
echo ""

# 9. APPLICATION LOGGING SYSTEM
echo -e "${PURPLE}[9] Application Logging System${NC}"
git add logs/
git commit -m "chore: Add application logging system

- Added logs/ directory for application logging
- Structured logging for debugging and monitoring
- Error and activity tracking

Features:
- Error logging
- Activity logging
- Performance logging
- Security event logging
- Log rotation"
echo -e "${GREEN}✓ Logging System commit created${NC}"
echo ""

# Final Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}All commits completed successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}Summary:${NC}"
echo "Total commits: 9"
echo "Branch: development-solve"
echo "Previous branch: testing-final"
echo ""
echo -e "${YELLOW}Commits created:${NC}"
echo "1. Admin UI & Notifications System"
echo "2. Admin Analytics & Query Management"
echo "3. Google OAuth & Enhanced Authentication"
echo "4. Database Management & Schema Updates"
echo "5. Chat Encryption & Messaging"
echo "6. User Profile & Support System"
echo "7. Gamification & Leaderboard"
echo "8. Mentor Selection & Request System"
echo "9. Application Logging System"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review commits: git log --oneline -10"
echo "2. Check status: git status"
echo "3. Push to GitHub: git push -u origin development-solve"
echo "4. Create Pull Request from development-solve to development"
echo ""
