# Expenses Module

The Expenses module handles all expense tracking, receipt management, and photo storage functionality for the NativePHP camera integration.

## Directory Structure

```
Expenses/
├── Models/                    # Eloquent models
│   └── Expense.php           # Expense model with relationships
├── Database/
│   ├── Factories/            # Model factories
│   │   └── ExpenseFactory.php
│   └── Migrations/           # Database migrations
│       └── 2024_09_03_000000_create_expenses_table.php
├── Tests/
│   ├── Feature/              # Feature tests
│   │   ├── CameraToStorageFlowTest.php      # End-to-end photo capture flow
│   │   ├── ReceiptStorageServiceTest.php    # Storage operations
│   │   └── FeatureTestCase.php              # Test base class with auto user creation
│   └── Unit/                 # Unit tests (if added)
├── Providers/
│   └── ExpensesServiceProvider.php  # Module service provider
├── module.json               # Module configuration
└── README.md                 # This file
```

## Features

### Expense Model
- Tracks expenses with amount, currency, description
- Linked to User model via BelongsTo relationship
- Stores receipt file paths
- Supports status tracking (pending/approved/rejected)
- Expense date tracking

### Receipt Storage
- Private storage disk (`storage/app/expenses`)
- Dated directory structure: `receipts/YYYY/MM/DD/`
- Secure random filenames (no user input leakage)
- MIME type validation (jpg, png, gif, webp)
- File size limits (max 10MB)

### Factory
ExpenseFactory provides:
- Default factory state with realistic data
- Modifiers: `pending()`, `approved()`, `rejected()`, `withoutReceipt()`
- Automatic User association

## Testing

Run module tests:

```bash
# All module tests
php artisan test Modules/Expenses/Tests/Feature

# Specific test
php artisan test Modules/Expenses/Tests/Feature/CameraToStorageFlowTest

# With testdox
php artisan test --testdox Modules/Expenses/Tests/Feature
```

### Test Coverage
- **CameraToStorageFlowTest** (28 tests): End-to-end photo capture → storage → database
- **ReceiptStorageServiceTest** (10 tests): File operations and validation
- **Total**: 38 tests, 73 assertions, 100% passing

## Service Provider

The `ExpensesServiceProvider` automatically:
- Loads migrations from `Database/Migrations/`
- Registers the module with Laravel

## Database

The expenses table includes:
- `user_id` (foreign key to users)
- `amount` (decimal:2)
- `currency` (default: USD)
- `receipt_path` (nullable, path to stored receipt file)
- `description` (nullable)
- `expense_date` (datetime)
- `status` (pending/approved/rejected)
- Timestamps (created_at, updated_at)

Cascade delete is enabled for user deletion.

## Usage

### Creating an Expense
```php
use Modules\Expenses\Models\Expense;

$expense = Expense::create([
    'user_id' => $user->id,
    'amount' => 99.99,
    'currency' => 'USD',
    'receipt_path' => 'receipts/2024/09/03/hash.jpg',
    'description' => 'Conference ticket',
    'expense_date' => now(),
    'status' => 'pending',
]);
```

### Using the Factory
```php
use Modules\Expenses\Models\Expense;

// Create a single expense
$expense = Expense::factory()->create();

// Create with specific status
$expense = Expense::factory()->approved()->create();

// Create without receipt
$expense = Expense::factory()->withoutReceipt()->create();

// Create multiple
$expenses = Expense::factory()->count(10)->create();
```

## Architecture Notes

- All Expense-related code is self-contained in this module
- Tests use `RefreshDatabase` for isolation
- `FeatureTestCase` auto-creates test users for FK constraints
- Storage uses fake disk in tests for speed/isolation
- Module follows PSR-4 autoloading standard

## Future Enhancements

- OCR service integration for receipt parsing
- Approval workflow
- Multi-currency support with conversion
- Expense categories
- Recurring expenses
- Export/report functionality

## See Also

- Main CLAUDE.md for architecture overview
- Main README.md for project setup
