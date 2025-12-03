# IdeaNest Testing Framework

Comprehensive testing suite for the IdeaNest Academic Project Management Platform.

## Test Structure

```
tests/
├── Unit/                    # Unit tests for individual functions
├── Integration/             # Integration tests for API and database
├── Functional/              # End-to-end workflow tests
├── Performance/             # Load and performance tests
├── UI/                      # Frontend JavaScript tests
├── bootstrap.php            # Test environment setup
└── run_tests.sh            # Test runner script
```

## Quick Start

### 1. Install Dependencies
```bash
composer install
```

### 2. Setup Test Database
```bash
mysql -u ictmu6ya_ideanest -p -e "CREATE DATABASE ictmu6ya_ideanest_test;"
```

### 3. Run All Tests
```bash
./tests/run_tests.sh
```

## Test Types

### Unit Tests
Test individual functions and methods in isolation.

```bash
composer test-unit
# or
./vendor/bin/phpunit --testsuite=unit
```

**Coverage:**
- Input validation and sanitization
- GitHub API service functions
- Authentication helpers
- Data processing utilities

### Integration Tests
Test interactions between components and external services.

```bash
composer test-integration
# or
./vendor/bin/phpunit --testsuite=integration
```

**Coverage:**
- Database operations
- GitHub API integration
- Email service integration
- File upload handling

### Functional Tests
Test complete user workflows and page accessibility.

```bash
composer test-functional
# or
./vendor/bin/phpunit --testsuite=functional
```

**Coverage:**
- User registration and login flows
- Project submission workflow
- Admin approval process
- Mentor-student interactions

### Performance Tests
Benchmark and load testing for critical operations.

```bash
./vendor/bin/phpunit tests/Performance/
```

**Coverage:**
- Database query performance
- API response times
- Memory usage optimization
- Concurrent operation handling

### UI Tests
Frontend JavaScript functionality testing.

Open `tests/UI/JavaScriptTest.html` in a browser to run QUnit tests.

**Coverage:**
- Form validation
- AJAX operations
- GitHub profile display
- Interactive UI elements

## Code Quality

### Static Analysis
```bash
composer phpstan
# or
./vendor/bin/phpstan analyse --configuration=phpstan.neon
```

### Code Style
```bash
composer phpcs
# or
./vendor/bin/phpcs --standard=phpcs.xml
```

### Combined Quality Check
```bash
composer quality
```

## Configuration

### Environment Variables
Set in `phpunit.xml` or environment:
- `DB_HOST` - Test database host (default: localhost)
- `DB_USER` - Test database user (default: root)
- `DB_PASS` - Test database password (default: empty)
- `DB_NAME` - Test database name (default: ictmu6ya_ideanest_test)
- `GITHUB_TEST_USER` - GitHub username for API tests (default: octocat)

### Test Database
The test suite automatically creates and manages a separate test database to avoid affecting production data.

## Continuous Integration

GitHub Actions workflow (`.github/workflows/ci.yml`) runs:
- Unit and integration tests
- Code quality checks
- Coverage reporting
- Multi-PHP version testing (8.1, 8.2)

## Coverage Reports

Generate HTML coverage report (requires Xdebug):
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Writing Tests

### Unit Test Example
```php
<?php
namespace IdeaNest\Tests\Unit;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
```

### Integration Test Example
```php
<?php
namespace IdeaNest\Tests\Integration;
use PHPUnit\Framework\TestCase;

class MyIntegrationTest extends TestCase
{
    private $conn;
    
    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        cleanupTestDatabase();
    }
}
```

## Best Practices

1. **Isolation**: Each test should be independent
2. **Cleanup**: Always clean up test data
3. **Mocking**: Use mocks for external dependencies
4. **Assertions**: Use specific assertions for better error messages
5. **Performance**: Keep tests fast and focused

## Troubleshooting

### Common Issues

**Database Connection Errors:**
- Ensure MySQL is running
- Check database credentials
- Verify test database exists

**GitHub API Rate Limits:**
- Tests use public endpoints with rate limits
- Add delays between API calls if needed
- Use mock data for extensive testing

**Permission Issues:**
- Ensure test runner script is executable: `chmod +x tests/run_tests.sh`
- Check file permissions for uploads directory

### Debug Mode
Run tests with verbose output:
```bash
./vendor/bin/phpunit --verbose --debug
```

## Performance Benchmarks

Expected performance thresholds:
- Database queries: < 100ms
- GitHub API calls: < 5 seconds
- Memory usage: < 10MB for standard operations
- Page load times: < 2 seconds

## Security Testing

The test suite includes:
- Input validation testing
- XSS prevention verification
- SQL injection protection
- File upload security checks

## Contributing

When adding new features:
1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain code coverage above 80%
4. Follow PSR-12 coding standards
5. Update documentation