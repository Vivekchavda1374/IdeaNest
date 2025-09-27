#!/bin/bash

# IdeaNest Email Configuration Validator
# Quick validation of email system configuration

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Paths
PROJECT_PATH="/opt/lampp/htdocs/IdeaNest"
PHP_PATH="/opt/lampp/bin/php"

echo -e "${BLUE}IdeaNest Email Configuration Validator${NC}"
echo "========================================"

# Check 1: Required files
echo -e "\n${YELLOW}1. Checking required files...${NC}"

files=(
    "$PROJECT_PATH/config/email_config.php"
    "$PROJECT_PATH/vendor/autoload.php"
    "$PROJECT_PATH/Login/Login/db.php"
    "$PROJECT_PATH/cron/weekly_notifications.php"
    "$PROJECT_PATH/cron/mentor_email_cron.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $(basename "$file")"
    else
        echo -e "${RED}✗${NC} $(basename "$file") - MISSING"
    fi
done

# Check 2: Environment configuration
echo -e "\n${YELLOW}2. Checking environment configuration...${NC}"

if [ -f "$PROJECT_PATH/.env" ]; then
    echo -e "${GREEN}✓${NC} .env file exists"
    
    # Check for required variables
    env_vars=("SMTP_HOST" "SMTP_USERNAME" "SMTP_PASSWORD" "FROM_EMAIL")
    for var in "${env_vars[@]}"; do
        if grep -q "^$var=" "$PROJECT_PATH/.env"; then
            echo -e "${GREEN}✓${NC} $var configured"
        else
            echo -e "${YELLOW}⚠${NC} $var not found in .env"
        fi
    done
else
    echo -e "${YELLOW}⚠${NC} .env file not found (using defaults)"
fi

# Check 3: Database connection
echo -e "\n${YELLOW}3. Testing database connection...${NC}"

$PHP_PATH -r "
require_once '$PROJECT_PATH/Login/Login/db.php';
if (isset(\$conn) && !\$conn->connect_error) {
    echo '✓ Database connection successful\n';
    
    // Check required tables
    \$tables = ['admin_settings', 'notification_logs', 'mentor_email_queue', 'register'];
    foreach (\$tables as \$table) {
        \$result = \$conn->query(\"SHOW TABLES LIKE '\$table'\");
        if (\$result && \$result->num_rows > 0) {
            echo '✓ Table \$table exists\n';
        } else {
            echo '✗ Table \$table missing\n';
        }
    }
} else {
    echo '✗ Database connection failed\n';
    exit(1);
}
"

# Check 4: PHPMailer setup
echo -e "\n${YELLOW}4. Testing PHPMailer setup...${NC}"

$PHP_PATH -r "
require_once '$PROJECT_PATH/vendor/autoload.php';
require_once '$PROJECT_PATH/config/email_config.php';
require_once '$PROJECT_PATH/Login/Login/db.php';

use PHPMailer\PHPMailer\PHPMailer;

try {
    \$mail = setupPHPMailer(\$conn);
    echo '✓ PHPMailer setup successful\n';
    echo '✓ SMTP Host: ' . \$mail->Host . '\n';
    echo '✓ SMTP Port: ' . \$mail->Port . '\n';
    echo '✓ From Email: ' . \$mail->From . '\n';
} catch (Exception \$e) {
    echo '✗ PHPMailer setup failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Check 5: SMTP connectivity
echo -e "\n${YELLOW}5. Testing SMTP connectivity...${NC}"

$PHP_PATH -r "
require_once '$PROJECT_PATH/vendor/autoload.php';
require_once '$PROJECT_PATH/config/email_config.php';
require_once '$PROJECT_PATH/Login/Login/db.php';

use PHPMailer\PHPMailer\PHPMailer;

try {
    \$mail = setupPHPMailer(\$conn);
    \$mail->SMTPDebug = 0;
    
    if (\$mail->smtpConnect()) {
        echo '✓ SMTP connection successful\n';
        \$mail->smtpClose();
    } else {
        echo '✗ SMTP connection failed\n';
        exit(1);
    }
} catch (Exception \$e) {
    echo '✗ SMTP connection error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Check 6: Cron jobs
echo -e "\n${YELLOW}6. Checking cron jobs...${NC}"

WEEKLY_CRON=$(crontab -l 2>/dev/null | grep "weekly_notifications.php")
MENTOR_CRON=$(crontab -l 2>/dev/null | grep "mentor_email_cron.php")

if [ -n "$WEEKLY_CRON" ]; then
    echo -e "${GREEN}✓${NC} Weekly notifications cron job installed"
else
    echo -e "${RED}✗${NC} Weekly notifications cron job not found"
fi

if [ -n "$MENTOR_CRON" ]; then
    echo -e "${GREEN}✓${NC} Mentor email cron job installed"
else
    echo -e "${RED}✗${NC} Mentor email cron job not found"
fi

# Check 7: Log files and permissions
echo -e "\n${YELLOW}7. Checking log files and permissions...${NC}"

LOG_DIR="$PROJECT_PATH/logs"
if [ -d "$LOG_DIR" ]; then
    echo -e "${GREEN}✓${NC} Log directory exists"
    
    if [ -w "$LOG_DIR" ]; then
        echo -e "${GREEN}✓${NC} Log directory is writable"
    else
        echo -e "${RED}✗${NC} Log directory is not writable"
    fi
else
    echo -e "${RED}✗${NC} Log directory missing"
fi

# Check file permissions
if [ -r "$PROJECT_PATH/.env" ]; then
    echo -e "${GREEN}✓${NC} .env file is readable"
else
    echo -e "${YELLOW}⚠${NC} .env file permissions issue"
fi

# Summary
echo -e "\n${BLUE}Configuration Validation Complete${NC}"
echo "========================================"

# Quick test email option
echo -e "\n${YELLOW}Would you like to send a test email? (y/n)${NC}"
read -r response

if [[ "$response" =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}Sending test email...${NC}"
    
    $PHP_PATH -r "
    require_once '$PROJECT_PATH/vendor/autoload.php';
    require_once '$PROJECT_PATH/config/email_config.php';
    require_once '$PROJECT_PATH/Login/Login/db.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    
    try {
        \$mail = setupPHPMailer(\$conn);
        \$mail->addAddress('ideanest.ict@gmail.com', 'Test User');
        \$mail->Subject = 'IdeaNest Email Configuration Test - ' . date('Y-m-d H:i:s');
        \$mail->Body = 'This is a test email from the IdeaNest email configuration validator.\n\nTime: ' . date('Y-m-d H:i:s') . '\nServer: ' . \$_SERVER['HTTP_HOST'] . '\n\nIf you receive this email, your configuration is working correctly!';
        
        if (\$mail->send()) {
            echo '✓ Test email sent successfully!\n';
        } else {
            echo '✗ Test email failed to send\n';
        }
    } catch (Exception \$e) {
        echo '✗ Test email error: ' . \$e->getMessage() . '\n';
    }
    "
fi

echo -e "\n${GREEN}Validation complete!${NC}"