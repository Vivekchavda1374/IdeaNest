#!/bin/bash

###############################################################################
# IdeaNest Security Check Script
# Performs security audit of the application
###############################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ISSUES=0

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}IdeaNest Security Audit${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# Check 1: .env file security
echo -e "${BLUE}[1] Checking .env file...${NC}"
if [ -f ".env" ]; then
    PERMS=$(stat -c %a .env 2>/dev/null || stat -f %A .env 2>/dev/null)
    if [ "$PERMS" != "600" ]; then
        echo -e "${RED}✗ .env permissions should be 600 (currently: $PERMS)${NC}"
        ((ISSUES++))
    else
        echo -e "${GREEN}✓ .env permissions correct${NC}"
    fi
    
    if grep -q "APP_ENV=production" .env; then
        echo -e "${GREEN}✓ APP_ENV set to production${NC}"
    else
        echo -e "${YELLOW}⚠ APP_ENV not set to production${NC}"
        ((ISSUES++))
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        echo -e "${GREEN}✓ APP_DEBUG disabled${NC}"
    else
        echo -e "${RED}✗ APP_DEBUG should be false in production${NC}"
        ((ISSUES++))
    fi
else
    echo -e "${RED}✗ .env file not found${NC}"
    ((ISSUES++))
fi
echo ""

# Check 2: Sensitive files exposure
echo -e "${BLUE}[2] Checking for exposed sensitive files...${NC}"
SENSITIVE_FILES=(".env" "composer.json" "composer.lock" "phpunit.xml" ".git")
for file in "${SENSITIVE_FILES[@]}"; do
    if [ -e "$file" ]; then
        if curl -s -o /dev/null -w "%{http_code}" "http://localhost/$file" | grep -q "200"; then
            echo -e "${RED}✗ $file is publicly accessible${NC}"
            ((ISSUES++))
        else
            echo -e "${GREEN}✓ $file is protected${NC}"
        fi
    fi
done
echo ""

# Check 3: Directory listing
echo -e "${BLUE}[3] Checking directory listing...${NC}"
if [ -f ".htaccess" ]; then
    if grep -q "Options -Indexes" .htaccess; then
        echo -e "${GREEN}✓ Directory listing disabled${NC}"
    else
        echo -e "${RED}✗ Directory listing not disabled in .htaccess${NC}"
        ((ISSUES++))
    fi
else
    echo -e "${YELLOW}⚠ .htaccess file not found${NC}"
    ((ISSUES++))
fi
echo ""

# Check 4: Upload directory security
echo -e "${BLUE}[4] Checking upload directories...${NC}"
UPLOAD_DIRS=("user/uploads" "user/forms/uploads" "user/profile_pictures")
for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        if [ -f "$dir/.htaccess" ]; then
            if grep -q "php_flag engine off" "$dir/.htaccess"; then
                echo -e "${GREEN}✓ $dir: PHP execution disabled${NC}"
            else
                echo -e "${RED}✗ $dir: PHP execution not disabled${NC}"
                ((ISSUES++))
            fi
        else
            echo -e "${RED}✗ $dir: Missing .htaccess${NC}"
            ((ISSUES++))
        fi
    fi
done
echo ""

# Check 5: Debug code
echo -e "${BLUE}[5] Checking for debug code...${NC}"
DEBUG_FUNCTIONS=("var_dump" "print_r" "var_export" "dd(")
for func in "${DEBUG_FUNCTIONS[@]}"; do
    COUNT=$(grep -r "$func" . --include="*.php" --exclude-dir=vendor 2>/dev/null | grep -v "//" | wc -l)
    if [ "$COUNT" -gt 0 ]; then
        echo -e "${YELLOW}⚠ Found $COUNT instances of $func${NC}"
        ((ISSUES++))
    fi
done
if [ "$ISSUES" -eq 0 ]; then
    echo -e "${GREEN}✓ No debug functions found${NC}"
fi
echo ""

# Check 6: Error display
echo -e "${BLUE}[6] Checking error display settings...${NC}"
if grep -r "display_errors.*On" . --include="*.php" 2>/dev/null | grep -v "//"; then
    echo -e "${RED}✗ Found display_errors=On in PHP files${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✓ No display_errors=On found${NC}"
fi
echo ""

# Check 7: HTTPS enforcement
echo -e "${BLUE}[7] Checking HTTPS enforcement...${NC}"
if [ -f ".htaccess" ]; then
    if grep -q "RewriteRule.*https" .htaccess; then
        echo -e "${GREEN}✓ HTTPS redirect configured${NC}"
    else
        echo -e "${YELLOW}⚠ HTTPS redirect not found in .htaccess${NC}"
        ((ISSUES++))
    fi
fi
echo ""

# Check 8: Security headers
echo -e "${BLUE}[8] Checking security headers...${NC}"
HEADERS=("X-Frame-Options" "X-Content-Type-Options" "X-XSS-Protection")
if [ -f ".htaccess" ]; then
    for header in "${HEADERS[@]}"; do
        if grep -q "$header" .htaccess; then
            echo -e "${GREEN}✓ $header configured${NC}"
        else
            echo -e "${YELLOW}⚠ $header not configured${NC}"
            ((ISSUES++))
        fi
    done
else
    echo -e "${YELLOW}⚠ .htaccess not found${NC}"
fi
echo ""

# Check 9: File permissions
echo -e "${BLUE}[9] Checking file permissions...${NC}"
if [ -d "config" ]; then
    CONFIG_PERMS=$(stat -c %a config 2>/dev/null || stat -f %A config 2>/dev/null)
    if [ "$CONFIG_PERMS" = "755" ] || [ "$CONFIG_PERMS" = "750" ]; then
        echo -e "${GREEN}✓ Config directory permissions OK${NC}"
    else
        echo -e "${YELLOW}⚠ Config directory permissions: $CONFIG_PERMS${NC}"
    fi
fi

if [ -d "logs" ]; then
    if [ -f "logs/.htaccess" ]; then
        echo -e "${GREEN}✓ Logs directory protected${NC}"
    else
        echo -e "${RED}✗ Logs directory not protected${NC}"
        ((ISSUES++))
    fi
fi
echo ""

# Check 10: Database credentials
echo -e "${BLUE}[10] Checking for hardcoded credentials...${NC}"
if grep -r "password.*=.*['\"]" . --include="*.php" --exclude-dir=vendor --exclude="*.example.php" 2>/dev/null | grep -v "//" | grep -v "password_hash" | grep -v "password_verify"; then
    echo -e "${YELLOW}⚠ Possible hardcoded credentials found${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✓ No hardcoded credentials found${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}Security Audit Summary${NC}"
echo -e "${BLUE}=========================================${NC}"
if [ "$ISSUES" -eq 0 ]; then
    echo -e "${GREEN}✓ All security checks passed!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠ Found $ISSUES security issues${NC}"
    echo "Please review and fix the issues above"
    exit 1
fi
