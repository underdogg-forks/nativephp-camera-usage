# Testing Guide

This project includes comprehensive testing at multiple levels: unit tests, feature tests, and end-to-end (E2E) tests.

## Quick Start

```bash
# Run all PHPUnit tests
php artisan test

# Run with test output
php artisan test --testdox

# Run specific test class
php artisan test --filter=ExpenseApiTest

# Run E2E tests with Playwright
npm run test:e2e

# Run E2E tests with UI (interactive)
npm run test:e2e:ui

# Debug E2E tests
npm run test:e2e:debug
```

## Test Structure

### PHPUnit Tests (Backend)

Located in `tests/` and `Modules/Expenses/Tests/`

**Feature Tests** (38 tests):
- `CameraToStorageFlowTest` — End-to-end photo capture → storage → database
- `ReceiptStorageServiceTest` — File operations, validation, metadata
- `ExpenseApiTest` — REST API endpoints (create, read, update, delete)

All tests use `RefreshDatabase` for isolation and `FeatureTestCase` for automatic test user creation.

**Test Coverage**: 38 tests, 73 assertions, 100% passing

```bash
# Run feature tests only
php artisan test --filter=Feature

# Run with coverage report
php artisan test --coverage
```

### Playwright E2E Tests (Frontend)

Located in `tests/e2e/`

**E2E Tests** (10+ scenarios):
- Create, retrieve, update, delete expenses via API
- Receipt upload
- Validation and error handling
- Status filtering
- Multi-user scenarios

```bash
# Run all E2E tests
npm run test:e2e

# Run specific test file
npx playwright test tests/e2e/expenses.spec.ts

# Run single test
npx playwright test -g "should create a new expense"

# Interactive mode
npm run test:e2e:ui

# Debug mode
npm run test:e2e:debug

# View test report
npx playwright show-report
```

## Test Principles

### Arrange-Act-Assert Pattern

All tests follow the AAA pattern:

```php
#[Test]
public function it_creates_an_expense(): void
{
    /* Arrange */
    $user = User::factory()->create();
    
    /* Act */
    $expense = Expense::create([
        'user_id' => $user->id,
        'amount' => 99.99,
    ]);
    
    /* Assert */
    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
}
```

### Test Naming

- PHPUnit: `it_describes_behavior()` (grammatical, lowercase)
- Feature: Test class names describe the feature being tested
- E2E: `should describe user action` (readable, business-focused)

### Real Tests Only

- ✅ Tests exercise real application code
- ✅ Tests verify behavior, not language features
- ❌ No fake tests for metrics (deleted 22 meaningless tests)
- ❌ No tests of PHP built-in functions

## Running Tests in CI/CD

### GitHub Actions

The project is configured to run tests in CI on every push:

```bash
# Tests run with:
php artisan test --testdox

# E2E tests run with:
npm run test:e2e
```

### Docker

```bash
# Run tests in Docker
docker compose run --rm cli php artisan test

# Run E2E tests
docker compose run --rm cli npm run test:e2e
```

## Common Issues

### Tests fail locally but pass in CI

1. Check database connection (SQLite in-memory `:memory:`)
2. Verify all migrations run: `php artisan migrate:fresh`
3. Clear cache: `php artisan cache:clear`

### E2E tests timeout

1. Ensure dev server is running: `php artisan serve`
2. Increase timeout in `playwright.config.ts`
3. Check browser compatibility

### Storage tests fail

1. Verify `Storage::fake('expenses')` in setUp
2. Check `config/filesystems.php` for 'expenses' disk configuration
3. Ensure RefreshDatabase trait is present

## Code Coverage

Generate coverage report:

```bash
php artisan test --coverage
php artisan test --coverage --min=80
```

Current coverage: Core expense operations and storage fully covered.

## Next Steps

1. **Filament Admin Panel** — Add UI tests for resource pages
2. **OCR Integration** — Add tests for receipt parsing
3. **Workflow Tests** — Add approval workflow tests
4. **Performance Tests** — Load testing for concurrent uploads
5. **Security Tests** — OWASP validation and authorization tests

## Test Assets

- Fake JPEG headers for upload testing
- Factory methods with modifiers (pending, approved, rejected)
- Temporary test files cleaned up automatically
- Test database uses cascade delete for data integrity

## Resources

- [PHPUnit Documentation](https://phpunit.de)
- [Playwright Documentation](https://playwright.dev)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Pest vs PHPUnit](https://pestphp.com) — This project uses PHPUnit only
