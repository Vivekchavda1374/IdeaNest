#!/bin/bash

# IdeaNest Email System Manager
# Master script for all email-related operations

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Project path
PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"

# Function to show main menu
show_main_menu() {
    clear
    echo -e "${PURPLE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║                    IdeaNest Email Manager                    ║${NC}"
    echo -e "${PURPLE}║                  Comprehensive Email System                  ║${NC}"
    echo -e "${PURPLE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}📧 Email Configuration & Testing${NC}"
    echo "  1) Validate Email Configuration"
    echo "  2) Test Complete Email System"
    echo "  3) Test SMTP Connection Only"
    echo "  4) Send Test Email"
    echo ""
    echo -e "${CYAN}🔍 Monitoring & Diagnostics${NC}"
    echo "  5) Monitor Email Failures"
    echo "  6) Quick Health Check"
    echo "  7) View Email System Status"
    echo "  8) Generate Failure Report"
    echo ""
    echo -e "${CYAN}📅 Cron Jobs & Automation${NC}"
    echo "  9) Setup Email Cron Jobs"
    echo " 10) Check Cron Job Status"
    echo " 11) Test Weekly Notifications"
    echo " 12) Test Mentor Email System"
    echo ""
    echo -e "${CYAN}📊 Logs & Reports${NC}"
    echo " 13) View All Email Logs"
    echo " 14) View Recent Failures"
    echo " 15) Clear Email Logs"
    echo " 16) Export Email Statistics"
    echo ""
    echo -e "${CYAN}🔧 Maintenance & Repair${NC}"
    echo " 17) Fix File Permissions"
    echo " 18) Reset Email Configuration"
    echo " 19) Repair Database Tables"
    echo " 20) Update Email Settings"
    echo ""
    echo -e "${CYAN}ℹ️  Information & Help${NC}"
    echo " 21) Show Email System Info"
    echo " 22) Troubleshooting Guide"
    echo " 23) Help & Documentation"
    echo ""
    echo -e "${RED} 0) Exit${NC}"
    echo ""
    echo -n "Select an option (0-23): "
}

# Function to pause and wait for user input
pause() {
    echo ""
    echo -n "Press Enter to continue..."
    read -r
}

# Function to validate email configuration
validate_config() {
    echo -e "${BLUE}Running Email Configuration Validation...${NC}"
    echo ""
    "$PROJECT_PATH/validate_email_config.sh"
    pause
}

# Function to run complete email test
test_complete_system() {
    echo -e "${BLUE}Running Complete Email System Test...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" test-all
    pause
}

# Function to test SMTP only
test_smtp_only() {
    echo -e "${BLUE}Testing SMTP Connection...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" test-smtp
    pause
}

# Function to send test email
send_test_email() {
    echo -e "${BLUE}Sending Test Email...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" test-config
    pause
}

# Function to monitor failures
monitor_failures() {
    echo -e "${BLUE}Monitoring Email Failures...${NC}"
    echo ""
    "$PROJECT_PATH/email_failure_monitor.sh" monitor
    pause
}

# Function to run quick health check
quick_health_check() {
    echo -e "${BLUE}Running Quick Health Check...${NC}"
    echo ""
    "$PROJECT_PATH/email_failure_monitor.sh" quick-check
    pause
}

# Function to show system status
show_system_status() {
    echo -e "${BLUE}Email System Status...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" status
    pause
}

# Function to generate failure report
generate_failure_report() {
    echo -e "${BLUE}Generating Failure Report...${NC}"
    echo ""
    "$PROJECT_PATH/email_failure_monitor.sh" report
    pause
}

# Function to setup cron jobs
setup_cron_jobs() {
    echo -e "${BLUE}Setting up Email Cron Jobs...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" setup-cron
    pause
}

# Function to check cron status
check_cron_status() {
    echo -e "${BLUE}Checking Cron Job Status...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" check-cron
    pause
}

# Function to test weekly notifications
test_weekly_notifications() {
    echo -e "${BLUE}Testing Weekly Notifications...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" test-weekly
    pause
}

# Function to test mentor emails
test_mentor_emails() {
    echo -e "${BLUE}Testing Mentor Email System...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" test-mentor
    pause
}

# Function to view all logs
view_all_logs() {
    echo -e "${BLUE}Viewing All Email Logs...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" view-logs
    pause
}

# Function to view recent failures
view_recent_failures() {
    echo -e "${BLUE}Viewing Recent Failures...${NC}"
    echo ""
    "$PROJECT_PATH/email_failure_monitor.sh" view-failures
    pause
}

# Function to clear logs
clear_logs() {
    echo -e "${YELLOW}Are you sure you want to clear all email logs? (y/N)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}Clearing Email Logs...${NC}"
        "$PROJECT_PATH/email_test_suite.sh" clear-logs
        "$PROJECT_PATH/email_failure_monitor.sh" clear-failures
        echo -e "${GREEN}Logs cleared successfully!${NC}"
    else
        echo "Operation cancelled."
    fi
    pause
}

# Function to export email statistics
export_statistics() {
    echo -e "${BLUE}Exporting Email Statistics...${NC}"
    echo ""
    
    STATS_FILE="$PROJECT_PATH/logs/email_statistics_$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "IdeaNest Email System Statistics"
        echo "Generated: $(date)"
        echo "========================================"
        echo ""
        
        echo "=== System Status ==="
        "$PROJECT_PATH/email_test_suite.sh" status 2>/dev/null
        
        echo ""
        echo "=== Recent Test Results ==="
        if [ -f "$PROJECT_PATH/logs/email_test_results.log" ]; then
            tail -20 "$PROJECT_PATH/logs/email_test_results.log"
        fi
        
        echo ""
        echo "=== Recent Failures ==="
        if [ -f "$PROJECT_PATH/logs/email_failures.log" ]; then
            tail -20 "$PROJECT_PATH/logs/email_failures.log"
        fi
        
    } > "$STATS_FILE"
    
    echo -e "${GREEN}Statistics exported to: $STATS_FILE${NC}"
    pause
}

# Function to fix permissions
fix_permissions() {
    echo -e "${BLUE}Fixing File Permissions...${NC}"
    echo ""
    "$PROJECT_PATH/email_test_suite.sh" fix-permissions
    pause
}

# Function to reset email configuration
reset_email_config() {
    echo -e "${YELLOW}This will reset email configuration to defaults. Continue? (y/N)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}Resetting Email Configuration...${NC}"
        
        # Backup current config
        if [ -f "$PROJECT_PATH/.env" ]; then
            cp "$PROJECT_PATH/.env" "$PROJECT_PATH/.env.backup.$(date +%Y%m%d_%H%M%S)"
            echo -e "${GREEN}Current .env backed up${NC}"
        fi
        
        # Copy example config
        if [ -f "$PROJECT_PATH/.env.example" ]; then
            cp "$PROJECT_PATH/.env.example" "$PROJECT_PATH/.env"
            echo -e "${GREEN}Default configuration restored${NC}"
            echo -e "${YELLOW}Please edit .env file with your email settings${NC}"
        else
            echo -e "${RED}No .env.example found${NC}"
        fi
    else
        echo "Operation cancelled."
    fi
    pause
}

# Function to repair database tables
repair_database() {
    echo -e "${BLUE}Repairing Database Tables...${NC}"
    echo ""
    
    /opt/lampp/bin/php -r "
    require_once '$PROJECT_PATH/Login/Login/db.php';
    
    if (\$conn && !\$conn->connect_error) {
        echo 'Checking database tables...\n';
        
        // Check and repair tables
        \$tables = ['notification_logs', 'mentor_email_queue', 'admin_settings'];
        foreach (\$tables as \$table) {
            \$result = \$conn->query(\"REPAIR TABLE \$table\");
            if (\$result) {
                echo \"✓ Table \$table repaired\n\";
            } else {
                echo \"✗ Failed to repair table \$table\n\";
            }
        }
        
        echo \"Database repair completed\n\";
    } else {
        echo \"Database connection failed\n\";
    }
    "
    pause
}

# Function to update email settings
update_email_settings() {
    echo -e "${BLUE}Update Email Settings${NC}"
    echo ""
    echo "This will open the admin settings page in your browser."
    echo "URL: https://ictmu.in/hcd/IdeaNest/Admin/settings.php"
    echo ""
    echo -e "${YELLOW}Press Enter to continue or Ctrl+C to cancel${NC}"
    read -r
    
    # Try to open in browser (works on most Linux systems)
    if command -v xdg-open > /dev/null; then
        xdg-open "https://ictmu.in/hcd/IdeaNest/Admin/settings.php"
    elif command -v firefox > /dev/null; then
        firefox "https://ictmu.in/hcd/IdeaNest/Admin/settings.php" &
    else
        echo "Please manually open: https://ictmu.in/hcd/IdeaNest/Admin/settings.php"
    fi
    pause
}

# Function to show system info
show_system_info() {
    echo -e "${BLUE}Email System Information${NC}"
    echo "========================================"
    echo ""
    echo "Project Path: $PROJECT_PATH"
    echo "PHP Path: /opt/lampp/bin/php"
    echo "Log Path: $PROJECT_PATH/logs"
    echo ""
    echo "Available Scripts:"
    echo "- email_test_suite.sh (Complete testing)"
    echo "- email_failure_monitor.sh (Failure monitoring)"
    echo "- validate_email_config.sh (Configuration validation)"
    echo "- email_manager.sh (This script)"
    echo ""
    echo "Key Files:"
    echo "- config/email_config.php (Email configuration)"
    echo "- .env (Environment variables)"
    echo "- cron/weekly_notifications.php (Weekly emails)"
    echo "- cron/mentor_email_cron.php (Mentor emails)"
    echo ""
    echo "Database Tables:"
    echo "- notification_logs (Email delivery logs)"
    echo "- mentor_email_queue (Mentor email queue)"
    echo "- admin_settings (System settings)"
    pause
}

# Function to show troubleshooting guide
show_troubleshooting() {
    echo -e "${BLUE}Email System Troubleshooting Guide${NC}"
    echo "========================================"
    echo ""
    echo -e "${YELLOW}Common Issues and Solutions:${NC}"
    echo ""
    echo "1. SMTP Connection Failed:"
    echo "   - Check SMTP credentials in .env file"
    echo "   - Verify Gmail app password (not regular password)"
    echo "   - Check firewall settings"
    echo ""
    echo "2. Emails Not Sending:"
    echo "   - Run: ./email_test_suite.sh test-smtp"
    echo "   - Check notification_logs table for errors"
    echo "   - Verify email settings in admin panel"
    echo ""
    echo "3. Cron Jobs Not Running:"
    echo "   - Run: ./email_test_suite.sh check-cron"
    echo "   - Check crontab: crontab -l"
    echo "   - Verify file permissions"
    echo ""
    echo "4. Database Connection Issues:"
    echo "   - Check Login/Login/db.php configuration"
    echo "   - Verify MySQL service is running"
    echo "   - Test database connection manually"
    echo ""
    echo "5. Permission Issues:"
    echo "   - Run: ./email_test_suite.sh fix-permissions"
    echo "   - Check logs directory permissions"
    echo "   - Verify web server user permissions"
    echo ""
    echo -e "${GREEN}For more help, run option 21 (System Info)${NC}"
    pause
}

# Function to show help
show_help() {
    echo -e "${BLUE}IdeaNest Email Manager Help${NC}"
    echo "========================================"
    echo ""
    echo "This script provides a comprehensive interface for managing"
    echo "the IdeaNest email system. It includes:"
    echo ""
    echo "• Configuration validation and testing"
    echo "• SMTP connectivity testing"
    echo "• Email failure monitoring and reporting"
    echo "• Cron job management"
    echo "• Log file management"
    echo "• System maintenance tools"
    echo ""
    echo "Quick Start:"
    echo "1. Run option 1 to validate your configuration"
    echo "2. Run option 2 to test the complete system"
    echo "3. Run option 9 to setup cron jobs"
    echo "4. Use option 6 for regular health checks"
    echo ""
    echo "For production monitoring, consider setting up:"
    echo "• Daily health checks (option 6)"
    echo "• Weekly failure reports (option 8)"
    echo "• Regular log reviews (option 13)"
    pause
}

# Main script loop
while true; do
    show_main_menu
    read -r choice
    
    case $choice in
        1) validate_config ;;
        2) test_complete_system ;;
        3) test_smtp_only ;;
        4) send_test_email ;;
        5) monitor_failures ;;
        6) quick_health_check ;;
        7) show_system_status ;;
        8) generate_failure_report ;;
        9) setup_cron_jobs ;;
        10) check_cron_status ;;
        11) test_weekly_notifications ;;
        12) test_mentor_emails ;;
        13) view_all_logs ;;
        14) view_recent_failures ;;
        15) clear_logs ;;
        16) export_statistics ;;
        17) fix_permissions ;;
        18) reset_email_config ;;
        19) repair_database ;;
        20) update_email_settings ;;
        21) show_system_info ;;
        22) show_troubleshooting ;;
        23) show_help ;;
        0) 
            echo -e "${GREEN}Thank you for using IdeaNest Email Manager!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option. Please try again.${NC}"
            sleep 2
            ;;
    esac
done