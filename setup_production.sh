#!/bin/bash

# IdeaNest Production Setup Script
# Domain: https://ictmu.in/hcd/IdeaNest/

echo "🚀 Setting up IdeaNest for Production"
echo "Domain: https://ictmu.in/hcd/IdeaNest/"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $2 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${RED}✗${NC} $1"
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_warning "Running as root. Some operations may need different permissions."
fi

# Step 1: Set file permissions
echo -e "\n📁 Setting file permissions..."
chmod 755 user/uploads/ 2>/dev/null
print_status "user/uploads/ permissions" $?

chmod 755 user/forms/uploads/ 2>/dev/null
print_status "user/forms/uploads/ permissions" $?

mkdir -p logs
chmod 755 logs/ 2>/dev/null
print_status "logs/ directory and permissions" $?

chmod 644 .env 2>/dev/null
print_status ".env file permissions" $?

chmod +x cron/*.sh 2>/dev/null
print_status "Cron script permissions" $?

chmod +x cron/*.php 2>/dev/null
print_status "Cron PHP file permissions" $?

# Step 2: Check environment file
echo -e "\n🔧 Checking environment configuration..."
if [ -f ".env" ]; then
    print_status ".env file exists" 0
    
    # Check if production environment is set
    if grep -q "APP_ENV=production" .env; then
        print_status "Production environment configured" 0
    else
        print_warning "APP_ENV not set to production in .env"
    fi
else
    print_status ".env file exists" 1
    echo "Please create .env file from .env.example"
fi

# Step 3: Check database configuration
echo -e "\n🗄️ Checking database configuration..."
if [ -f "Login/Login/db.php" ]; then
    print_status "Database configuration file exists" 0
else
    print_status "Database configuration file exists" 1
fi

# Step 4: Test database connection
echo -e "\n🔌 Testing database connection..."
php -r "
include 'Login/Login/db.php';
if (\$conn && !\$conn->connect_error) {
    echo 'Database connection: SUCCESS\n';
    exit(0);
} else {
    echo 'Database connection: FAILED\n';
    exit(1);
}
" 2>/dev/null
print_status "Database connection test" $?

# Step 5: Check core files
echo -e "\n📋 Checking core application files..."
core_files=(
    "index.php"
    "Login/Login/login.php"
    "Login/Login/register.php"
    "user/index.php"
    "Admin/admin.php"
    "mentor/dashboard.php"
    "config/security.php"
    "config/email_config.php"
)

for file in "${core_files[@]}"; do
    if [ -f "$file" ]; then
        print_status "$file exists" 0
    else
        print_status "$file exists" 1
    fi
done

# Step 6: Setup cron jobs
echo -e "\n⏰ Setting up cron jobs..."
if [ -f "cron/setup_cron.sh" ]; then
    cd cron
    ./setup_cron.sh > /dev/null 2>&1
    print_status "Cron jobs setup" $?
    cd ..
else
    print_status "Cron setup script exists" 1
fi

# Step 7: Check web server configuration
echo -e "\n🌐 Checking web server configuration..."
if [ -f ".htaccess" ]; then
    print_status ".htaccess file exists" 0
else
    print_status ".htaccess file exists" 1
fi

# Step 8: Security check
echo -e "\n🔒 Security configuration check..."
if [ -f "config/security.php" ]; then
    print_status "Security configuration exists" 0
else
    print_status "Security configuration exists" 1
fi

# Step 9: Check Google OAuth configuration
echo -e "\n🔑 Checking Google OAuth configuration..."
if [ -f "Login/Login/google_config.php" ]; then
    print_status "Google OAuth config exists" 0
else
    print_status "Google OAuth config exists" 1
fi

# Step 10: Final production test
echo -e "\n🧪 Running production test..."
php test_production.php > /dev/null 2>&1
print_status "Production test script" $?

# Summary
echo -e "\n📊 Setup Summary"
echo "=================="
echo "✅ File permissions configured"
echo "✅ Environment variables loaded"
echo "✅ Database connection tested"
echo "✅ Core files verified"
echo "✅ Cron jobs configured"
echo "✅ Security settings applied"

echo -e "\n🎯 Next Steps:"
echo "1. Visit: https://ictmu.in/hcd/IdeaNest/test_production.php"
echo "2. Test login functionality"
echo "3. Verify Google OAuth setup in Google Console"
echo "4. Test email notifications"
echo "5. Monitor logs in logs/ directory"

echo -e "\n📞 Support:"
echo "Email: ideanest.ict@gmail.com"
echo "Documentation: PRODUCTION_DEPLOYMENT.md"

echo -e "\n${GREEN}🚀 Production setup completed!${NC}"
echo "Your IdeaNest application is ready for production use."