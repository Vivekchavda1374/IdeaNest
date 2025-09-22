#!/bin/bash

# IdeaNest Cron Job Management Script

SCRIPT_DIR="/opt/lampp/htdocs/IdeaNest/cron"
LOG_DIR="/opt/lampp/htdocs/IdeaNest/logs"
PHP_PATH="/opt/lampp/bin/php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

show_help() {
    echo "IdeaNest Cron Job Management"
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  install     Install weekly Sunday cron job"
    echo "  remove      Remove all IdeaNest cron jobs"
    echo "  status      Show current cron job status"
    echo "  test        Test weekly notifications manually"
    echo "  logs        Show recent log entries"
    echo "  help        Show this help message"
}

install_cron() {
    echo -e "${YELLOW}Installing weekly Sunday cron job...${NC}"
    
    # Create log directory
    mkdir -p "$LOG_DIR"
    
    # Remove existing IdeaNest cron jobs
    crontab -l 2>/dev/null | grep -v "IdeaNest/cron/weekly_notifications.php" | crontab -
    
    # Add new weekly Sunday cron job (9:00 AM every Sunday)
    (crontab -l 2>/dev/null; echo "0 9 * * 0 $PHP_PATH $SCRIPT_DIR/weekly_notifications.php >> $LOG_DIR/weekly_notifications.log 2>&1") | crontab -
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Cron job installed successfully!${NC}"
        echo "Schedule: Every Sunday at 9:00 AM"
        echo "Log file: $LOG_DIR/weekly_notifications.log"
    else
        echo -e "${RED}✗ Failed to install cron job${NC}"
        exit 1
    fi
}

remove_cron() {
    echo -e "${YELLOW}Removing IdeaNest cron jobs...${NC}"
    
    crontab -l 2>/dev/null | grep -v "IdeaNest/cron/weekly_notifications.php" | crontab -
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Cron jobs removed successfully!${NC}"
    else
        echo -e "${RED}✗ Failed to remove cron jobs${NC}"
        exit 1
    fi
}

show_status() {
    echo -e "${YELLOW}Current cron job status:${NC}"
    echo ""
    
    CRON_EXISTS=$(crontab -l 2>/dev/null | grep "IdeaNest/cron/weekly_notifications.php")
    
    if [ -n "$CRON_EXISTS" ]; then
        echo -e "${GREEN}✓ Weekly notifications cron job is active${NC}"
        echo "Schedule: $CRON_EXISTS"
        
        # Check if log file exists
        if [ -f "$LOG_DIR/weekly_notifications.log" ]; then
            echo -e "${GREEN}✓ Log file exists${NC}"
            echo "Last modified: $(stat -c %y "$LOG_DIR/weekly_notifications.log" 2>/dev/null || echo "Unknown")"
        else
            echo -e "${YELLOW}⚠ Log file not found${NC}"
        fi
    else
        echo -e "${RED}✗ No weekly notifications cron job found${NC}"
    fi
    
    echo ""
    echo "All cron jobs:"
    crontab -l 2>/dev/null || echo "No cron jobs found"
}

test_notifications() {
    echo -e "${YELLOW}Testing weekly notifications manually...${NC}"
    echo ""
    
    if [ -f "$SCRIPT_DIR/test_weekly_notifications.php" ]; then
        $PHP_PATH "$SCRIPT_DIR/test_weekly_notifications.php"
    else
        echo "Running weekly notifications directly..."
        $PHP_PATH "$SCRIPT_DIR/weekly_notifications.php"
    fi
}

show_logs() {
    echo -e "${YELLOW}Recent log entries:${NC}"
    echo ""
    
    if [ -f "$LOG_DIR/weekly_notifications.log" ]; then
        tail -20 "$LOG_DIR/weekly_notifications.log"
    else
        echo -e "${RED}✗ Log file not found: $LOG_DIR/weekly_notifications.log${NC}"
    fi
}

# Main script logic
case "$1" in
    install)
        install_cron
        ;;
    remove)
        remove_cron
        ;;
    status)
        show_status
        ;;
    test)
        test_notifications
        ;;
    logs)
        show_logs
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