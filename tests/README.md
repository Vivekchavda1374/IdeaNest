# IdeaNest Unit Testing Suite

## Overview
This comprehensive test suite provides unit tests for the IdeaNest project, covering various aspects of the application:

### Test Categories
1. **User Profile Tests**
   - Registration validation
   - Login functionality
   - Profile update checks

2. **Project Tests**
   - Project data validation
   - Project retrieval
   - File upload validation
   - Project status updates

3. **Authentication Tests**
   - Password hashing
   - Password strength validation
   - Email uniqueness
   - Session management

## Prerequisites
- PHP 8.0+
- PHPUnit 9.x
- Configured database connection
- Composer dependencies installed

## Running Tests

### Install Dependencies
```bash
composer install --dev
```

### Execute Tests
```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test suite
php vendor/bin/phpunit tests/UserProfileTest.php
php vendor/bin/phpunit tests/ProjectTest.php
php vendor/bin/phpunit tests/AuthenticationTest.php

# Verbose output
php vendor/bin/phpunit --verbose
```

## Configuration
- Test database should be configured in `Login/Login/db.php`
- Ensure a test user exists with ID 1 in the `register` table

## Test Data Requirements
- Valid test user credentials
- Sample project data
- Diverse input scenarios

## Troubleshooting
- Verify database connection
- Check PHP extensions
- Ensure all dependencies are installed

## Contributing
Please add more test cases to improve coverage and robustness. 