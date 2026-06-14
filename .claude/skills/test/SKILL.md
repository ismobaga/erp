---
name: test
description: Run the PHPUnit test suite for CROMMIX ERP. Supports running all tests, a single file, or a specific test method.
---

# Test – CROMMIX ERP

Run PHPUnit tests against the Laravel application.

## Basic usage

```bash
# Run all tests
php artisan test

# Run with parallel workers (faster)
php artisan test --parallel

# Run a specific test file
php artisan test tests/Feature/InvoiceTest.php

# Run a specific test method
php artisan test --filter test_invoice_can_be_created

# Run a test class
php artisan test --filter InvoiceTest
```

## Test environment

- Tests use the `.env.testing` file if it exists, otherwise `.env`
- The project uses an in-memory SQLite database for tests (check `phpunit.xml`)
- Always run `php artisan config:clear` after changing `.env` values

## Useful flags

| Flag | Purpose |
|------|---------|
| `--parallel` | Run tests in parallel (faster) |
| `--coverage` | Show code coverage (requires Xdebug or PCOV) |
| `--stop-on-failure` | Halt on first failure |
| `--filter <name>` | Run matching test methods/classes |
| `--testsuite Feature` | Run only Feature tests |
| `--testsuite Unit` | Run only Unit tests |

## Test locations

- `tests/Unit/` — Unit tests (models, value objects, services)
- `tests/Feature/` — Feature tests (HTTP, database, full stack)

## Before running tests

Make sure migrations are up to date in the test environment:
```bash
php artisan migrate --env=testing
```
