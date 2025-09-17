# IdeaNest Testing Guide

## Overview
This document provides comprehensive information about the testing framework and test suites implemented in IdeaNest.

## Test Framework Architecture

### UnitTestFramework
- **Location**: `tests/UnitTestFramework.php`
- **Purpose**: Base testing framework with assertion methods
- **Features**:
  - Test registration and execution
  - Assertion methods (assertTrue, assertFalse, assertEquals)
  - Exception handling
  - Execution time tracking

## Test Suites

### 1. GitHub Service Tests (`GitHubServiceTest.php`)
**Purpose**: Test GitHub API integration functions
- `testFetchGitHubProfile()` - Validates profile fetching
- `testFetchGitHubProfileInvalid()` - Tests invalid user handling
- `testFetchGitHubRepos()` - Tests repository fetching
- `testEmptyUsername()` - Tests empty input validation

### 2. Database Tests (`DatabaseTest.php`)
**Purpose**: Test database connectivity and operations
- `testDatabaseConnection()` - Validates MySQL connection
- `testRegisterTableExists()` - Checks table existence
- `testGitHubColumnsExist()` - Validates required columns
- `testBasicCRUDOperations()` - Tests INSERT/SELECT/DELETE
- `testDataIntegrity()` - Validates data consistency

### 3. Security Tests (`SecurityTest.php`)
**Purpose**: Test security measures and vulnerability prevention
- `testSQLInjectionPrevention()` - Tests input sanitization
- `testXSSPrevention()` - Tests script tag escaping
- `testCSRFTokenPresent()` - Tests CSRF protection
- `testInputValidation()` - Tests pattern validation
- `testPasswordHashing()` - Tests secure password handling
- `testFileUploadSecurity()` - Tests file extension validation

### 4. Integration Tests (`IntegrationTest.php`)
**Purpose**: Test component interactions
- `testGitHubAPIIntegration()` - Tests API-database integration
- `testDatabaseIntegration()` - Tests database connectivity
- `testProfileSettingsIntegration()` - Tests form processing
- `testSessionIntegration()` - Tests session management
- `testErrorHandlingIntegration()` - Tests error flows

### 5. Performance Tests (`PerformanceTest.php`)
**Purpose**: Test system performance and resource usage
- `testGitHubAPIResponseTime()` - Tests API response times
- `testDatabaseQueryPerformance()` - Tests query execution speed
- `testMemoryUsage()` - Tests memory consumption
- `testConcurrentRequests()` - Tests concurrent operations
- `testLargeDataHandling()` - Tests large data processing

### 6. Functional Tests (`FunctionalTest.php`)
**Purpose**: Test user-facing functionality
- `testUserCanConnectGitHub()` - Tests GitHub connection flow
- `testGitHubStatsDisplay()` - Tests statistics display
- `testRepositoryDisplay()` - Tests repository listing
- `testErrorHandling()` - Tests user error scenarios
- `testFormValidation()` - Tests form input validation
- `testUserWorkflow()` - Tests complete user workflows

### 7. End-to-End Tests (`E2ETest.php`)
**Purpose**: Test complete user journeys
- `testCompleteUserFlow()` - Tests full user workflow
- `testCrossPageNavigation()` - Tests page navigation
- `testDataPersistence()` - Tests session persistence
- `testUserAuthentication()` - Tests auth flow
- `testGitHubIntegrationFlow()` - Tests GitHub integration

### 8. Validation Tests (`ValidationTest.php`)
**Purpose**: Test input validation and sanitization
- `testEmailValidation()` - Tests email format validation
- `testPasswordValidation()` - Tests password strength
- `testUsernameValidation()` - Tests username patterns
- `testFileUploadValidation()` - Tests file security
- `testSQLInjectionPrevention()` - Tests injection prevention

### 9. API Tests (`APITest.php`)
**Purpose**: Test API connectivity and data handling
- `testGitHubAPIConnection()` - Tests API connectivity
- `testAPIResponseFormat()` - Tests response structure
- `testAPIErrorHandling()` - Tests error responses
- `testAPIRateLimit()` - Tests rate limiting
- `testJSONParsing()` - Tests JSON processing

## Running Tests

### Web Interface
1. Navigate to: `http://localhost/IdeaNest/run_tests.php`
2. View comprehensive HTML report with:
   - Test execution results
   - Performance metrics
   - Coverage summary
   - Detailed error information

### Command Line
```bash
cd /opt/lampp/htdocs/IdeaNest/tests
php TestRunner.php
```

### Individual Test Suites
```bash
# Run specific test suite
php GitHubServiceTest.php
php DatabaseTest.php
php SecurityTest.php
```

## Test Results Interpretation

### Status Indicators
- **PASS** (Green): Test completed successfully
- **FAIL** (Red): Test failed with error
- **SKIP** (Yellow): Test skipped due to missing dependencies

### Performance Metrics
- **Execution Time**: Individual test execution time in milliseconds
- **Memory Usage**: Memory consumption during test execution
- **API Response Time**: External API call response times

### Coverage Areas
- ✅ Unit Testing - Individual function validation
- ✅ Database Testing - Schema and CRUD operations
- ✅ Security Testing - XSS, SQL injection, CSRF protection
- ✅ Integration Testing - Component interaction
- ✅ Performance Testing - Response times and memory usage
- ✅ Functional Testing - User workflows and validation
- ✅ End-to-End Testing - Complete user journeys
- ✅ Error Handling - Exception and edge case management
- ✅ Data Validation - Input sanitization and validation

## Test Configuration

### Database Requirements
- MySQL/MariaDB connection
- `register` table with required columns
- Test data insertion/deletion permissions

### API Requirements
- Internet connectivity for GitHub API tests
- GitHub API rate limits consideration
- Proper User-Agent headers

### Security Requirements
- Session support enabled
- Error reporting configured
- File upload permissions set

## Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Check MySQL service status
   - Verify database credentials in `Login/Login/db.php`
   - Ensure database exists

2. **API Tests Failing**
   - Check internet connectivity
   - Verify GitHub API accessibility
   - Check for rate limiting

3. **Permission Errors**
   - Ensure proper file permissions
   - Check web server configuration
   - Verify session directory permissions

### Debug Mode
Enable detailed error reporting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Best Practices

### Writing New Tests
1. Extend `UnitTestFramework` class
2. Use descriptive test method names
3. Include proper assertions
4. Handle exceptions gracefully
5. Clean up test data

### Test Maintenance
1. Run tests after code changes
2. Update tests when adding features
3. Monitor performance metrics
4. Review failed tests promptly
5. Keep test data minimal

## Continuous Integration

### Automated Testing
- Tests can be integrated into CI/CD pipelines
- Use command-line execution for automation
- Monitor test results and coverage
- Set up notifications for failures

### Performance Monitoring
- Track test execution times
- Monitor memory usage trends
- Set performance thresholds
- Alert on performance degradation

---

**Last Updated**: January 2025
**Version**: 1.0
**Maintainer**: IdeaNest Development Team