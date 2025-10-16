#!/bin/bash

# IdeaNest Email Configuration & Testing Suite
# Comprehensive email system testing and management

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Project paths
PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"
PHP_PATH="/opt/lampp/bin/php"
LOG_PATH="$PROJECT_PATH/logs"
CRON_PATH="$PROJECT_PATH/cron"

# Email test results
EMAIL_RESULTS_FILE="$LOG_PATH/email_test_results.log"

# Create log directory if it doesn't exist
mkdir -p "$LOG_PATH"

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "SUCCESS") echo -e "${GREEN}✓ $message${NC}" ;;
        "ERROR") echo -e "${RED}✗ $message${NC}" ;;
        "WARNING") echo -e "${YELLOW}⚠ $message${NC}" ;;
        "INFO") echo -e "${BLUE}ℹ $message${NC}" ;;
        "HEADER") echo -e "${PURPLE}$message${NC}" ;;
    esac
}

# Function to log results
log_result() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$EMAIL_RESULTS_FILE"
}

# Function to show help
show_help() {
    echo -e "${PURPLE}IdeaNest Email Configuration & Testing Suite${NC}"
    echo ""
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  test-all           Run complete email system test"
    echo "  test-config        Test email configuration only"
    echo "  test-smtp          Test SMTP connection"
    echo "  test-weekly        Test weekly notifications"
    echo "  test-mentor        Test mentor email system"
    echo "  test-approval      Test project approval emails"
    echo "  test-rejection     Test project rejection emails"
    echo "  check-cron         Check cron job status"
    echo "  setup-cron         Setup email cron jobs"
    echo "  view-logs          View email logs"
    echo "  clear-logs         Clear email logs"
    echo "  fix-permissions    Fix file permissions"
    echo "  status             Show email system status"
    echo "  help               Show this help message"
}

# Function to test email configuration
test_email_config() {
    print_status "HEADER" "Testing Email Configuration..."
    
    # Check if config files exist
    if [ -f "$PROJECT_PATH/config/email_config.php" ]; then
        print_status "SUCCESS" "Email config file found"
    else
        print_status "ERROR" "Email config file missing"
        return 1
    fi
    
    # Check if .env file exists
    if [ -f "$PROJECT_PATH/.env" ]; then
        print_status "SUCCESS" ".env file found"
        
        # Check for required email variables
        if grep -q "SMTP_HOST" "$PROJECT_PATH/.env"; then
            print_status "SUCCESS" "SMTP_HOST configured"
        else
            print_status "WARNING" "SMTP_HOST not found in .env"
        fi
        
        if grep -q "SMTP_USERNAME" "$PROJECT_PATH/.env"; then
            print_status "SUCCESS" "SMTP_USERNAME configured"
        else
            print_status "WARNING" "SMTP_USERNAME not found in .env"
        fi
    else
        print_status "WARNING" ".env file not found, using defaults"
    fi
    
    # Test database connection
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/Login/Login/db.php';
    if (isset(\$conn) && !\$conn->connect_error) {
        echo 'Database connection: SUCCESS\n';
    } else {
        echo 'Database connection: ERROR\n';
        exit(1);
    }
    "
    
    if [ $? -eq 0 ]; then
        print_status "SUCCESS" "Database connection working"
    else
        print_status "ERROR" "Database connection failed"
        return 1
    fi
}

# Function to test SMTP connection
test_smtp_connection() {
    print_status "HEADER" "Testing SMTP Connection..."
    
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/vendor/autoload.php';
    require_once '$PROJECT_PATH/config/email_config.php';
    require_once '$PROJECT_PATH/Login/Login/db.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    
    try {
        \$mail = setupPHPMailer(\$conn);
        \$mail->SMTPDebug = 0;
        \$mail->addAddress('test@example.com', 'Test User');
        \$mail->Subject = 'SMTP Test';
        \$mail->Body = 'Test message';
        
        // Test SMTP connection without sending
        if (\$mail->smtpConnect()) {
            echo 'SMTP Connection: SUCCESS\n';
            \$mail->smtpClose();
        } else {
            echo 'SMTP Connection: ERROR\n';
        }
    } catch (Exception \$e) {
        echo 'SMTP Connection: ERROR - ' . \$e->getMessage() . '\n';
    }
    "
    
    if [ $? -eq 0 ]; then
        log_result "SMTP connection test passed"
    else
        log_result "SMTP connection test failed"
    fi
}

# Function to test basic email sending
test_basic_email() {
    print_status "HEADER" "Testing Basic Email Sending..."
    
    $PHP_PATH "$PROJECT_PATH/test_email.php" > /dev/null 2>&1
    
    if [ $? -eq 0 ]; then
        print_status "SUCCESS" "Basic email test completed"
        log_result "Basic email test passed"
    else
        print_status "ERROR" "Basic email test failed"
        log_result "Basic email test failed"
    fi
}

# Function to test weekly notifications
test_weekly_notifications() {
    print_status "HEADER" "Testing Weekly Notifications..."
    
    if [ -f "$CRON_PATH/weekly_notifications.php" ]; then
        print_status "INFO" "Running weekly notifications test..."
        
        $PHP_PATH "$CRON_PATH/weekly_notifications.php" > "$LOG_PATH/weekly_test.log" 2>&1
        
        if [ $? -eq 0 ]; then
            print_status "SUCCESS" "Weekly notifications test completed"
            log_result "Weekly notifications test passed"
            
            # Show last few lines of output
            echo -e "${CYAN}Last output:${NC}"
            tail -5 "$LOG_PATH/weekly_test.log"
        else
            print_status "ERROR" "Weekly notifications test failed"
            log_result "Weekly notifications test failed"
            
            # Show error output
            echo -e "${RED}Error output:${NC}"
            cat "$LOG_PATH/weekly_test.log"
        fi
    else
        print_status "ERROR" "Weekly notifications script not found"
    fi
}

# Function to test mentor email system
test_mentor_emails() {
    print_status "HEADER" "Testing Mentor Email System..."
    
    if [ -f "$CRON_PATH/mentor_email_cron.php" ]; then
        print_status "INFO" "Running mentor email test..."
        
        $PHP_PATH "$CRON_PATH/mentor_email_cron.php" > "$LOG_PATH/mentor_test.log" 2>&1
        
        if [ $? -eq 0 ]; then
            print_status "SUCCESS" "Mentor email test completed"
            log_result "Mentor email test passed"
            
            # Show last few lines of output
            echo -e "${CYAN}Last output:${NC}"
            tail -5 "$LOG_PATH/mentor_test.log"
        else
            print_status "ERROR" "Mentor email test failed"
            log_result "Mentor email test failed"
            
            # Show error output
            echo -e "${RED}Error output:${NC}"
            cat "$LOG_PATH/mentor_test.log"
        fi
    else
        print_status "ERROR" "Mentor email script not found"
    fi
}

# Function to test project approval emails
test_approval_emails() {
    print_status "HEADER" "Testing Project Approval Emails..."
    
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/vendor/autoload.php';
    require_once '$PROJECT_PATH/Login/Login/db.php';
    require_once '$PROJECT_PATH/Admin/notification_backend.php';
    
    // Get a test project ID
    \$query = 'SELECT id FROM projects LIMIT 1';
    \$result = \$conn->query(\$query);
    
    if (\$result && \$result->num_rows > 0) {
        \$project = \$result->fetch_assoc();
        \$test_result = sendProjectApprovalEmail(\$project['id'], \$conn);
        
        if (\$test_result['success']) {
            echo 'Project approval email test: SUCCESS\n';
        } else {
            echo 'Project approval email test: ERROR - ' . \$test_result['message'] . '\n';
        }
    } else {
        echo 'Project approval email test: WARNING - No test projects found\n';
    }
    "
    
    if [ $? -eq 0 ]; then
        log_result "Project approval email test completed"
    else
        log_result "Project approval email test failed"
    fi
}

# Function to check cron job status
check_cron_status() {
    print_status "HEADER" "Checking Cron Job Status..."
    
    # Check if cron jobs are installed
    WEEKLY_CRON=$(crontab -l 2>/dev/null | grep "weekly_notifications.php")
    MENTOR_CRON=$(crontab -l 2>/dev/null | grep "mentor_email_cron.php")
    
    if [ -n "$WEEKLY_CRON" ]; then
        print_status "SUCCESS" "Weekly notifications cron job installed"
        echo -e "${CYAN}Schedule: $WEEKLY_CRON${NC}"
    else
        print_status "ERROR" "Weekly notifications cron job not found"
    fi
    
    if [ -n "$MENTOR_CRON" ]; then
        print_status "SUCCESS" "Mentor email cron job installed"
        echo -e "${CYAN}Schedule: $MENTOR_CRON${NC}"
    else
        print_status "ERROR" "Mentor email cron job not found"
    fi
    
    # Check log files
    if [ -f "$LOG_PATH/weekly_notifications.log" ]; then
        print_status "SUCCESS" "Weekly notifications log exists"
        echo -e "${CYAN}Last modified: $(stat -c %y "$LOG_PATH/weekly_notifications.log")${NC}"
    else
        print_status "WARNING" "Weekly notifications log not found"
    fi
    
    if [ -f "$LOG_PATH/mentor_emails.log" ]; then
        print_status "SUCCESS" "Mentor emails log exists"
        echo -e "${CYAN}Last modified: $(stat -c %y "$LOG_PATH/mentor_emails.log")${NC}"
    else
        print_status "WARNING" "Mentor emails log not found"
    fi
}

# Function to setup cron jobs
setup_cron_jobs() {
    print_status "HEADER" "Setting up Email Cron Jobs..."
    
    if [ -f "$CRON_PATH/setup_cron.sh" ]; then
        chmod +x "$CRON_PATH/setup_cron.sh"
        "$CRON_PATH/setup_cron.sh"
        
        if [ $? -eq 0 ]; then
            print_status "SUCCESS" "Cron jobs setup completed"
            log_result "Cron jobs setup completed"
        else
            print_status "ERROR" "Cron jobs setup failed"
            log_result "Cron jobs setup failed"
        fi
    else
        print_status "ERROR" "Cron setup script not found"
    fi
}

# Function to view email logs
view_email_logs() {
    print_status "HEADER" "Email System Logs..."
    
    echo -e "${CYAN}=== Weekly Notifications Log ===${NC}"
    if [ -f "$LOG_PATH/weekly_notifications.log" ]; then
        tail -20 "$LOG_PATH/weekly_notifications.log"
    else
        echo "No weekly notifications log found"
    fi
    
    echo -e "\n${CYAN}=== Mentor Emails Log ===${NC}"
    if [ -f "$LOG_PATH/mentor_emails.log" ]; then
        tail -20 "$LOG_PATH/mentor_emails.log"
    else
        echo "No mentor emails log found"
    fi
    
    echo -e "\n${CYAN}=== Email Test Results ===${NC}"
    if [ -f "$EMAIL_RESULTS_FILE" ]; then
        tail -20 "$EMAIL_RESULTS_FILE"
    else
        echo "No email test results found"
    fi
}

# Function to clear logs
clear_email_logs() {
    print_status "HEADER" "Clearing Email Logs..."
    
    # Clear log files
    > "$LOG_PATH/weekly_notifications.log" 2>/dev/null
    > "$LOG_PATH/mentor_emails.log" 2>/dev/null
    > "$EMAIL_RESULTS_FILE" 2>/dev/null
    > "$LOG_PATH/weekly_test.log" 2>/dev/null
    > "$LOG_PATH/mentor_test.log" 2>/dev/null
    
    print_status "SUCCESS" "Email logs cleared"
    log_result "Email logs cleared"
}

# Function to fix file permissions
fix_permissions() {
    print_status "HEADER" "Fixing File Permissions..."
    
    # Set directory permissions
    chmod 755 "$LOG_PATH" 2>/dev/null
    chmod 755 "$CRON_PATH" 2>/dev/null
    
    # Set file permissions
    chmod 644 "$PROJECT_PATH/.env" 2>/dev/null
    chmod +x "$CRON_PATH"/*.sh 2>/dev/null
    chmod 644 "$CRON_PATH"/*.php 2>/dev/null
    chmod 644 "$LOG_PATH"/*.log 2>/dev/null
    
    print_status "SUCCESS" "File permissions fixed"
    log_result "File permissions fixed"
}

# Function to show email system status
show_email_status() {
    print_status "HEADER" "Email System Status Overview..."
    
    echo -e "${CYAN}=== Configuration Status ===${NC}"
    test_email_config
    
    echo -e "\n${CYAN}=== Cron Jobs Status ===${NC}"
    check_cron_status
    
    echo -e "\n${CYAN}=== Recent Activity ===${NC}"
    if [ -f "$EMAIL_RESULTS_FILE" ]; then
        echo "Last 5 test results:"
        tail -5 "$EMAIL_RESULTS_FILE"
    else
        echo "No recent test activity"
    fi
    
    echo -e "\n${CYAN}=== File Status ===${NC}"
    echo "Email config: $([ -f "$PROJECT_PATH/config/email_config.php" ] && echo "✓ Found" || echo "✗ Missing")"
    echo "Test script: $([ -f "$PROJECT_PATH/test_email.php" ] && echo "✓ Found" || echo "✗ Missing")"
    echo "Weekly script: $([ -f "$CRON_PATH/weekly_notifications.php" ] && echo "✓ Found" || echo "✗ Missing")"
    echo "Mentor script: $([ -f "$CRON_PATH/mentor_email_cron.php" ] && echo "✓ Found" || echo "✗ Missing")"
}

# Function to run complete test suite
run_complete_test() {
    print_status "HEADER" "Running Complete Email Test Suite..."
    echo "Started at: $(date)"
    log_result "=== Complete Email Test Suite Started ==="
    
    # Test configuration
    test_email_config
    
    # Test SMTP connection
    test_smtp_connection
    
    # Test basic email
    test_basic_email
    
    # Test weekly notifications
    test_weekly_notifications
    
    # Test mentor emails
    test_mentor_emails
    
    # Test approval emails
    test_approval_emails
    
    # Check cron status
    check_cron_status
    
    print_status "HEADER" "Complete Email Test Suite Finished"
    echo "Completed at: $(date)"
    log_result "=== Complete Email Test Suite Completed ==="
    
    # Show summary
    echo -e "\n${PURPLE}=== Test Summary ===${NC}"
    echo "Check the logs for detailed results:"
    echo "- Email test results: $EMAIL_RESULTS_FILE"
    echo "- Weekly notifications: $LOG_PATH/weekly_notifications.log"
    echo "- Mentor emails: $LOG_PATH/mentor_emails.log"
}

# Main script logic
case "$1" in
    test-all)
        run_complete_test
        ;;
    test-config)
        test_email_config
        ;;
    test-smtp)
        test_smtp_connection
        ;;
    test-weekly)
        test_weekly_notifications
        ;;
    test-mentor)
        test_mentor_emails
        ;;
    test-approval)
        test_approval_emails
        ;;
    check-cron)
        check_cron_status
        ;;
    setup-cron)
        setup_cron_jobs
        ;;
    view-logs)
        view_email_logs
        ;;
    clear-logs)
        clear_email_logs
        ;;
    fix-permissions)
        fix_permissions
        ;;
    status)
        show_email_status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo -e "${RED}Invalid option: $1${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac