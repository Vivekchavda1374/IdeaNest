#!/bin/bash

# IdeaNest Email System Setup Script
# One-time setup for the complete email system

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"
LOG_PATH="$PROJECT_PATH/logs"

echo -e "${PURPLE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${PURPLE}║                IdeaNest Email System Setup                   ║${NC}"
echo -e "${PURPLE}║              Complete Email System Initialization            ║${NC}"
echo -e "${PURPLE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Create directories
echo -e "${BLUE}Step 1: Creating required directories...${NC}"
mkdir -p "$LOG_PATH"
chmod 755 "$LOG_PATH"
echo -e "${GREEN}✓ Log directory created: $LOG_PATH${NC}"

# Step 2: Set file permissions
echo -e "\n${BLUE}Step 2: Setting file permissions...${NC}"
chmod +x "$PROJECT_PATH/email_manager.sh"
chmod +x "$PROJECT_PATH/email_test_suite.sh"
chmod +x "$PROJECT_PATH/email_failure_monitor.sh"
chmod +x "$PROJECT_PATH/validate_email_config.sh"
chmod +x "$PROJECT_PATH/cron/"*.sh
chmod 644 "$PROJECT_PATH/.env" 2>/dev/null
echo -e "${GREEN}✓ File permissions set${NC}"

# Step 3: Check dependencies
echo -e "\n${BLUE}Step 3: Checking dependencies...${NC}"

# Check PHP
if [ -f "/opt/lampp/bin/php" ]; then
    echo -e "${GREEN}✓ PHP found${NC}"
else
    echo -e "${RED}✗ PHP not found at /opt/lampp/bin/php${NC}"
fi

# Check Composer
if [ -f "$PROJECT_PATH/vendor/autoload.php" ]; then
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ Composer dependencies missing${NC}"
    echo "Run: cd $PROJECT_PATH && composer install"
fi

# Check database connection
echo -e "\n${BLUE}Step 4: Testing database connection...${NC}"
/opt/lampp/bin/php -r "
require_once '$PROJECT_PATH/Login/Login/db.php';
if (isset(\$conn) && !\$conn->connect_error) {
    echo '✓ Database connection successful\n';
} else {
    echo '✗ Database connection failed\n';
    exit(1);
}
" && echo -e "${GREEN}Database connection OK${NC}" || echo -e "${RED}Database connection failed${NC}"

# Step 5: Initialize configuration
echo -e "\n${BLUE}Step 5: Initializing email configuration...${NC}"

if [ ! -f "$PROJECT_PATH/.env" ]; then
    if [ -f "$PROJECT_PATH/.env.example" ]; then
        cp "$PROJECT_PATH/.env.example" "$PROJECT_PATH/.env"
        echo -e "${GREEN}✓ .env file created from example${NC}"
        echo -e "${YELLOW}⚠ Please edit .env file with your email settings${NC}"
    else
        echo -e "${YELLOW}⚠ No .env.example found${NC}"
    fi
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Step 6: Test email configuration
echo -e "\n${BLUE}Step 6: Testing email configuration...${NC}"
"$PROJECT_PATH/validate_email_config.sh" | head -20

# Step 7: Setup cron jobs (optional)
echo -e "\n${BLUE}Step 7: Cron jobs setup${NC}"
echo -e "${YELLOW}Would you like to setup email cron jobs now? (y/N)${NC}"
read -r response

if [[ "$response" =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}Setting up cron jobs...${NC}"
    "$PROJECT_PATH/cron/setup_cron.sh"
else
    echo -e "${YELLOW}Skipping cron setup. You can run it later with:${NC}"
    echo "./email_manager.sh (option 9)"
fi

# Step 8: Create initial log files
echo -e "\n${BLUE}Step 8: Creating initial log files...${NC}"
touch "$LOG_PATH/email_test_results.log"
touch "$LOG_PATH/email_failures.log"
touch "$LOG_PATH/weekly_notifications.log"
touch "$LOG_PATH/mentor_emails.log"
chmod 644 "$LOG_PATH"/*.log
echo -e "${GREEN}✓ Log files created${NC}"

# Summary
echo -e "\n${PURPLE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${PURPLE}║                        Setup Complete!                       ║${NC}"
echo -e "${PURPLE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Email system setup completed successfully!${NC}"
echo ""
echo -e "${CYAN}Available Scripts:${NC}"
echo "• ./email_manager.sh          - Master email management interface"
echo "• ./email_test_suite.sh       - Complete email testing suite"
echo "• ./email_failure_monitor.sh  - Email failure monitoring"
echo "• ./validate_email_config.sh  - Configuration validation"
echo ""
echo -e "${CYAN}Quick Start:${NC}"
echo "1. Edit .env file with your email settings"
echo "2. Run: ./email_manager.sh"
echo "3. Choose option 1 to validate configuration"
echo "4. Choose option 2 to test the complete system"
echo ""
echo -e "${CYAN}Next Steps:${NC}"
echo "• Configure your SMTP settings in .env file"
echo "• Test email configuration: ./validate_email_config.sh"
echo "• Setup cron jobs: ./email_manager.sh (option 9)"
echo "• Run health checks: ./email_manager.sh (option 6)"
echo ""
echo -e "${YELLOW}For help and documentation, run: ./email_manager.sh (option 23)${NC}"
echo ""