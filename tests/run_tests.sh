#!/bin/bash

# IdeaNest Test Runner Script

echo "🚀 Starting IdeaNest Test Suite"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Navigate to project root
cd "$(dirname "$0")/.." || exit 1

# Check if composer dependencies are installed
if [ ! -d "vendor" ] || [ ! -f "composer.lock" ]; then
    echo -e "${YELLOW}Installing/updating dependencies...${NC}"
    composer update --no-interaction
else
    echo -e "${GREEN}Dependencies already installed${NC}"
fi

# Verify PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}❌ PHPUnit not found. Installing dependencies...${NC}"
    composer install --dev --no-interaction
fi

# Create test database
echo -e "${BLUE}Setting up test database...${NC}"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ideanest_test;" 2>/dev/null || echo "Database setup skipped (manual setup required)"

# Check if PHPUnit is available before running tests
if [ -f "vendor/bin/phpunit" ]; then
    # Run Unit Tests
    echo -e "${BLUE}Running Unit Tests...${NC}"
    ./vendor/bin/phpunit tests/Unit/ --colors=always || echo -e "${YELLOW}No unit tests found or tests failed${NC}"
    
    # Run Integration Tests
    echo -e "${BLUE}Running Integration Tests...${NC}"
    ./vendor/bin/phpunit tests/Integration/ --colors=always || echo -e "${YELLOW}No integration tests found or tests failed${NC}"
    
    # Run Functional Tests
    echo -e "${BLUE}Running Functional Tests...${NC}"
    ./vendor/bin/phpunit tests/Functional/ --colors=always || echo -e "${YELLOW}No functional tests found or tests failed${NC}"
else
    echo -e "${RED}❌ PHPUnit not available. Skipping unit tests.${NC}"
fi

# Run Code Quality Checks
echo -e "${BLUE}Running Code Quality Checks...${NC}"

# PHP CodeSniffer
if [ -f "vendor/bin/phpcs" ]; then
    echo -e "${YELLOW}PHP CodeSniffer...${NC}"
    ./vendor/bin/phpcs --standard=PSR12 --ignore=vendor/,tests/ . || echo -e "${YELLOW}Code style issues found${NC}"
else
    echo -e "${RED}❌ PHP CodeSniffer not available${NC}"
fi

# PHPStan
if [ -f "vendor/bin/phpstan" ]; then
    echo -e "${YELLOW}PHPStan Static Analysis...${NC}"
    ./vendor/bin/phpstan analyse --level=5 --no-progress || echo -e "${YELLOW}Static analysis issues found${NC}"
else
    echo -e "${RED}❌ PHPStan not available${NC}"
fi

# Generate Coverage Report (if xdebug is available)
if php -m | grep -q xdebug; then
    echo -e "${BLUE}Generating Coverage Report...${NC}"
    ./vendor/bin/phpunit --coverage-html coverage/
    echo -e "${GREEN}Coverage report generated in coverage/ directory${NC}"
fi

echo -e "${GREEN}✅ Test Suite Complete${NC}"
echo "================================"

# UI Tests reminder
echo -e "${YELLOW}📝 To run UI tests:${NC}"
echo "   Open tests/UI/JavaScriptTest.html in a browser"
echo ""

# Performance test reminder
echo -e "${YELLOW}⚡ For performance testing:${NC}"
echo "   Use tools like Apache Bench (ab) or JMeter"
echo "   Example: ab -n 100 -c 10 https://ictmu.in/hcd/IdeaNest/"