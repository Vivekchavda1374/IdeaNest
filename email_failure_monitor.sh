#!/bin/bash

# IdeaNest Email Failure Monitor
# Monitors email system and reports failures

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Paths
PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"
PHP_PATH="/opt/lampp/bin/php"
LOG_PATH="$PROJECT_PATH/logs"
FAILURE_LOG="$LOG_PATH/email_failures.log"

# Create log directory
mkdir -p "$LOG_PATH"

# Function to log failures
log_failure() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] FAILURE: $1" >> "$FAILURE_LOG"
}

# Function to log success
log_success() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> "$FAILURE_LOG"
}

# Function to check database email logs
check_database_failures() {
    echo -e "${BLUE}Checking database for email failures...${NC}"
    
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/Login/Login/db.php';
    
    if (!\$conn || \$conn->connect_error) {
        echo 'Database connection failed\n';
        exit(1);
    }
    
    // Check notification logs for failures
    \$query = \"SELECT COUNT(*) as failed_count FROM notification_logs WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)\";
    \$result = \$conn->query(\$query);
    
    if (\$result) {
        \$row = \$result->fetch_assoc();
        echo 'Failed emails (24h): ' . \$row['failed_count'] . '\n';
        
        if (\$row['failed_count'] > 0) {
            // Get recent failures
            \$failures_query = \"SELECT type, email_to, error_message, created_at FROM notification_logs WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 10\";
            \$failures_result = \$conn->query(\$failures_query);
            
            echo 'Recent failures:\n';
            while (\$failure = \$failures_result->fetch_assoc()) {
                echo '- ' . \$failure['created_at'] . ' | ' . \$failure['type'] . ' | ' . \$failure['email_to'] . ' | ' . \$failure['error_message'] . '\n';
            }
        }
    }
    
    // Check mentor email queue for stuck emails
    \$queue_query = \"SELECT COUNT(*) as stuck_count FROM mentor_email_queue WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)\";
    \$queue_result = \$conn->query(\$queue_query);
    
    if (\$queue_result) {
        \$queue_row = \$queue_result->fetch_assoc();
        echo 'Stuck mentor emails: ' . \$queue_row['stuck_count'] . '\n';
    }
    "
    
    if [ $? -ne 0 ]; then
        log_failure "Database check failed"
        return 1
    fi
}

# Function to test SMTP connectivity
test_smtp_health() {
    echo -e "${BLUE}Testing SMTP connectivity...${NC}"
    
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/vendor/autoload.php';
    require_once '$PROJECT_PATH/config/email_config.php';
    require_once '$PROJECT_PATH/Login/Login/db.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    
    try {
        \$mail = setupPHPMailer(\$conn);
        \$mail->SMTPDebug = 0;
        
        if (\$mail->smtpConnect()) {
            echo 'SMTP connection: OK\n';
            \$mail->smtpClose();
        } else {
            echo 'SMTP connection: FAILED\n';
            exit(1);
        }
    } catch (Exception \$e) {
        echo 'SMTP error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "
    
    if [ $? -eq 0 ]; then
        log_success "SMTP connectivity test passed"
    else
        log_failure "SMTP connectivity test failed"
        return 1
    fi
}

# Function to check cron job execution
check_cron_execution() {
    echo -e "${BLUE}Checking cron job execution...${NC}"
    
    # Check if weekly notifications ran recently
    if [ -f "$LOG_PATH/weekly_notifications.log" ]; then
        LAST_WEEKLY=$(stat -c %Y "$LOG_PATH/weekly_notifications.log" 2>/dev/null)
        CURRENT_TIME=$(date +%s)
        DIFF=$((CURRENT_TIME - LAST_WEEKLY))
        
        # If more than 8 days since last run (should run weekly)
        if [ $DIFF -gt 691200 ]; then
            echo -e "${YELLOW}WARNING: Weekly notifications haven't run in $((DIFF/86400)) days${NC}"
            log_failure "Weekly notifications overdue by $((DIFF/86400)) days"
        else
            echo -e "${GREEN}Weekly notifications last ran $((DIFF/86400)) days ago${NC}"
            log_success "Weekly notifications running on schedule"
        fi
    else
        echo -e "${RED}Weekly notifications log not found${NC}"
        log_failure "Weekly notifications log missing"
    fi
    
    # Check if mentor emails ran recently
    if [ -f "$LOG_PATH/mentor_emails.log" ]; then
        LAST_MENTOR=$(stat -c %Y "$LOG_PATH/mentor_emails.log" 2>/dev/null)
        MENTOR_DIFF=$((CURRENT_TIME - LAST_MENTOR))
        
        # If more than 1 hour since last run (should run every 5 minutes)
        if [ $MENTOR_DIFF -gt 3600 ]; then
            echo -e "${YELLOW}WARNING: Mentor emails haven't run in $((MENTOR_DIFF/60)) minutes${NC}"
            log_failure "Mentor emails overdue by $((MENTOR_DIFF/60)) minutes"
        else
            echo -e "${GREEN}Mentor emails last ran $((MENTOR_DIFF/60)) minutes ago${NC}"
            log_success "Mentor emails running on schedule"
        fi
    else
        echo -e "${RED}Mentor emails log not found${NC}"
        log_failure "Mentor emails log missing"
    fi
}

# Function to check email configuration
check_email_config() {
    echo -e "${BLUE}Checking email configuration...${NC}"
    
    # Check if required files exist
    if [ ! -f "$PROJECT_PATH/config/email_config.php" ]; then
        echo -e "${RED}Email config file missing${NC}"
        log_failure "Email config file missing"
        return 1
    fi
    
    if [ ! -f "$PROJECT_PATH/vendor/autoload.php" ]; then
        echo -e "${RED}Composer autoload missing${NC}"
        log_failure "Composer autoload missing"
        return 1
    fi
    
    # Test database connection
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/Login/Login/db.php';
    if (!\$conn || \$conn->connect_error) {
        echo 'Database connection failed\n';
        exit(1);
    } else {
        echo 'Database connection: OK\n';
    }
    "
    
    if [ $? -eq 0 ]; then
        log_success "Email configuration check passed"
    else
        log_failure "Email configuration check failed"
        return 1
    fi
}

# Function to generate failure report
generate_failure_report() {
    echo -e "${BLUE}Generating failure report...${NC}"
    
    REPORT_FILE="$LOG_PATH/email_failure_report_$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "IdeaNest Email System Failure Report"
        echo "Generated: $(date)"
        echo "========================================"
        echo ""
        
        echo "=== Recent Failures (Last 24 hours) ==="
        if [ -f "$FAILURE_LOG" ]; then
            grep "FAILURE" "$FAILURE_LOG" | tail -20
        else
            echo "No failure log found"
        fi
        
        echo ""
        echo "=== System Status ==="
        
        # Check files
        echo "Config file: $([ -f "$PROJECT_PATH/config/email_config.php" ] && echo "OK" || echo "MISSING")"
        echo "Autoload: $([ -f "$PROJECT_PATH/vendor/autoload.php" ] && echo "OK" || echo "MISSING")"
        echo "Weekly script: $([ -f "$PROJECT_PATH/cron/weekly_notifications.php" ] && echo "OK" || echo "MISSING")"
        echo "Mentor script: $([ -f "$PROJECT_PATH/cron/mentor_email_cron.php" ] && echo "OK" || echo "MISSING")"
        
        echo ""
        echo "=== Cron Jobs ==="
        crontab -l 2>/dev/null | grep "IdeaNest" || echo "No IdeaNest cron jobs found"
        
        echo ""
        echo "=== Log Files ==="
        echo "Weekly log: $([ -f "$LOG_PATH/weekly_notifications.log" ] && echo "EXISTS ($(stat -c %y "$LOG_PATH/weekly_notifications.log"))" || echo "MISSING")"
        echo "Mentor log: $([ -f "$LOG_PATH/mentor_emails.log" ] && echo "EXISTS ($(stat -c %y "$LOG_PATH/mentor_emails.log"))" || echo "MISSING")"
        
        echo ""
        echo "=== Database Status ==="
        $PHP_PATH -r "
        require_once '$PROJECT_PATH/Login/Login/db.php';
        if (\$conn && !\$conn->connect_error) {
            \$query = \"SELECT COUNT(*) as failed_count FROM notification_logs WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)\";
            \$result = \$conn->query(\$query);
            if (\$result) {
                \$row = \$result->fetch_assoc();
                echo 'Failed emails (7 days): ' . \$row['failed_count'] . '\n';
            }
            
            \$queue_query = \"SELECT COUNT(*) as pending_count FROM mentor_email_queue WHERE status = 'pending'\";
            \$queue_result = \$conn->query(\$queue_query);
            if (\$queue_result) {
                \$queue_row = \$queue_result->fetch_assoc();
                echo 'Pending mentor emails: ' . \$queue_row['pending_count'] . '\n';
            }
        } else {
            echo 'Database connection failed\n';
        }
        "
        
    } > "$REPORT_FILE"
    
    echo -e "${GREEN}Failure report generated: $REPORT_FILE${NC}"
    
    # Show summary
    echo ""
    echo -e "${YELLOW}=== Report Summary ===${NC}"
    head -20 "$REPORT_FILE"
}

# Function to run quick health check
quick_health_check() {
    echo -e "${BLUE}Running quick email system health check...${NC}"
    
    local failures=0
    
    # Check configuration
    if ! check_email_config > /dev/null 2>&1; then
        ((failures++))
    fi
    
    # Check SMTP
    if ! test_smtp_health > /dev/null 2>&1; then
        ((failures++))
    fi
    
    # Check database failures
    check_database_failures
    
    # Check cron execution
    check_cron_execution
    
    if [ $failures -eq 0 ]; then
        echo -e "${GREEN}✓ Email system health check passed${NC}"
        log_success "Health check passed"
        return 0
    else
        echo -e "${RED}✗ Email system health check failed ($failures issues)${NC}"
        log_failure "Health check failed with $failures issues"
        return 1
    fi
}

# Function to show help
show_help() {
    echo "IdeaNest Email Failure Monitor"
    echo ""
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  monitor        Run complete failure monitoring"
    echo "  quick-check    Run quick health check"
    echo "  database       Check database for email failures"
    echo "  smtp           Test SMTP connectivity"
    echo "  cron           Check cron job execution"
    echo "  config         Check email configuration"
    echo "  report         Generate detailed failure report"
    echo "  view-failures  View recent failures"
    echo "  clear-failures Clear failure log"
    echo "  help           Show this help"
}

# Function to view recent failures
view_failures() {
    echo -e "${BLUE}Recent email failures:${NC}"
    
    if [ -f "$FAILURE_LOG" ]; then
        echo ""
        echo "=== Last 20 failures ==="
        grep "FAILURE" "$FAILURE_LOG" | tail -20
        
        echo ""
        echo "=== Failure summary ==="
        echo "Total failures today: $(grep "FAILURE" "$FAILURE_LOG" | grep "$(date +%Y-%m-%d)" | wc -l)"
        echo "Total failures this week: $(grep "FAILURE" "$FAILURE_LOG" | grep -E "$(date +%Y-%m-)($(date +%d)|$(date -d '1 day ago' +%d)|$(date -d '2 days ago' +%d)|$(date -d '3 days ago' +%d)|$(date -d '4 days ago' +%d)|$(date -d '5 days ago' +%d)|$(date -d '6 days ago' +%d))" | wc -l)"
    else
        echo "No failure log found"
    fi
}

# Function to clear failure log
clear_failures() {
    echo -e "${BLUE}Clearing failure log...${NC}"
    > "$FAILURE_LOG"
    echo -e "${GREEN}Failure log cleared${NC}"
}

# Main script logic
case "$1" in
    monitor)
        echo -e "${BLUE}=== Email System Monitoring ===${NC}"
        check_email_config
        test_smtp_health
        check_database_failures
        check_cron_execution
        ;;
    quick-check)
        quick_health_check
        ;;
    database)
        check_database_failures
        ;;
    smtp)
        test_smtp_health
        ;;
    cron)
        check_cron_execution
        ;;
    config)
        check_email_config
        ;;
    report)
        generate_failure_report
        ;;
    view-failures)
        view_failures
        ;;
    clear-failures)
        clear_failures
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