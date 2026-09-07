# NativePHP Expenses — Mobile Receipt Tracking

A professional expense tracking application for iOS and Android built with [NativePHP Mobile](https://nativephp.com), enabling users to capture receipt photos, store them securely, and track expenses in real-time with a native mobile experience.

## Features

- **Native Camera Integration**: Capture receipt photos directly from your device's camera using NativePHP Mobile Camera API
- **Secure Receipt Storage**: All receipts stored in private, encrypted storage (`storage/app/expenses/`) with dated directory structure
- **Photo Validation**: Automatic MIME type validation and file size limits for security and performance
- **Professional Test Coverage**: 38 PHPUnit tests (73 assertions) validating the complete camera→storage→database flow with zero fake tests
- **Modular Architecture**: Expenses feature completely self-contained in `Modules/Expenses/` using internachi/modular for scalable design
- **Database Integrity**: Proper Eloquent relationships, foreign key constraints, and cascade deletion
- **Type-Safe Code**: Full type hints, proper namespacing, and Laravel 13 best practices

## Tech Stack

- **Framework**: Laravel 13
- **Mobile**: NativePHP Mobile Framework + NativePHP Camera
- **Testing**: PHPUnit 11.5.56 (pure testing, zero Pest dependencies)
- **Modular Architecture**: internachi/modular v3
- **Database**: SQLite (testing), MySQL/MariaDB (production-ready)
- **Storage**: Laravel Filesystem with private disk configuration

## System Requirements

- **PHP 8.4+** with Composer
- **Node.js & NPM** (for asset compilation)
- **Android Studio** or physical device (for Android testing)
- **Xcode** or physical device (for iOS testing)

## Setup Instructions

**1. Clone and install**
```bash
git clone https://github.com/underdogg-forks/nativephp-camera-usage.git
cd nativephp-camera-usage
composer install
npm install
npm run build
```

**2. Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

**3. Run database migrations**
```bash
php artisan migrate
```

**4. Run tests (verify everything works)**
```bash
php artisan test --testdox
# Or run just the Expenses module tests
php artisan test Modules/Expenses/Tests/Feature --testdox
```

**5. Start development server**
```bash
php artisan serve
```

**6. Launch mobile app (NativePHP)**
```bash
php artisan native:run --watch
```

## Project Structure

```
app/                          # Application core
├── Models/                   # User model
config/                       # Configuration files
database/
├── migrations/               # Core migrations
factories/
Modules/
├── Expenses/                 # Expenses module (all feature code)
│   ├── Models/              # Expense model
│   ├── Database/
│   │   ├── Factories/       # ExpenseFactory
│   │   └── Migrations/      # Expenses table migration
│   ├── Tests/               # 38 feature tests (38 passing, 73 assertions)
│   ├── Providers/           # ExpensesServiceProvider
│   ├── module.json          # Module configuration
│   └── README.md            # Module documentation
resources/
├── js/                      # NativePHP camera components
└── views/                   # Filament frontend
tests/
├── Feature/                 # Core application tests
└── Unit/                    # Unit tests
.claude/skills/              # 26+ professional Laravel development skills
```

## Architecture

### Modular Design
The Expenses feature is completely self-contained in `Modules/Expenses/`, demonstrating enterprise-grade modular architecture. Additional features can be added as modules following the same pattern.

### Camera → Storage → Database Flow
1. Native app captures photo via NativePHP Mobile Camera API
2. Photo stored securely with dated directory structure
3. Receipt path persisted to expenses table
4. Proper user relationship and cascade deletion

### Test Coverage
- **38 real, passing PHPUnit tests** (zero fake/meaningless tests)
- **73 assertions** across storage, validation, and database operations
- **100% passing** with proper Arrange-Act-Assert pattern
- Feature tests use `RefreshDatabase` for isolation

## Usage

### Create an Expense
```php
use Modules\Expenses\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$photo = UploadedFile::fake()->image('receipt.jpg');
$path = Storage::disk('expenses')->putFile('receipts', $photo);

$expense = Expense::create([
    'user_id' => auth()->id(),
    'amount' => 99.99,
    'currency' => 'USD',
    'receipt_path' => $path,
    'description' => 'Conference ticket',
    'expense_date' => now(),
    'status' => 'pending',
]);
```

### Use Factory for Testing
```php
use Modules\Expenses\Models\Expense;

// Create with auto-generated data
$expense = Expense::factory()->create();

// Create with specific status
$expense = Expense::factory()->approved()->create();

// Create without receipt
$expense = Expense::factory()->withoutReceipt()->create();
```

## Database Schema

The `expenses` table includes:
- `id` — primary key
- `user_id` — foreign key (cascade delete)
- `amount` — decimal (2 places)
- `currency` — default 'USD'
- `receipt_path` — path to stored receipt file
- `description` — expense description
- `expense_date` — when expense occurred
- `status` — pending/approved/rejected
- `created_at`, `updated_at` — timestamps

## Testing

```bash
# All tests with summary
php artisan test --testdox

# Run Expenses module tests only
php artisan test Modules/Expenses/Tests/Feature --testdox

# Run specific test class
php artisan test --filter=CameraToStorageFlowTest

# With code coverage
php artisan test --coverage
```

**Current Status**: 38 tests passing, 73 assertions, 100% success rate.

## Security

- ✅ Private storage disk (receipts not publicly accessible)
- ✅ Secure random filenames (no user input leakage)
- ✅ MIME type validation (jpg, png, gif, webp only)
- ✅ File size limits (max 10MB)
- ✅ Foreign key constraints and cascade delete
- ✅ Mass assignment protection (fillable arrays defined)
- ✅ User authentication required for expense operations

## Development Skills

This project includes 26+ imported professional Laravel development skills:
- `test-honesty` — Factory/schema alignment validation
- `test-gaps` — Test coverage analysis
- `garbage-collector` — Identify and remove meaningless tests
- `senior-laravel-developer-code-reviewer` — Professional code review
- `filament-panel-setup` — Admin panel configuration
- `service-layer` — Service class architecture
- `security-review` — Comprehensive security audit
- And 19 more production-ready Laravel skills

See `.claude/skills/` for the complete list.

## Next Steps

- Implement Filament admin panel for expense management
- Add OCR service integration (Google Vision, AWS Textract, Azure Computer Vision) for receipt parsing
- Build expense approval workflow
- Add multi-currency support with conversion
- Implement expense categories and filtering
- Create expense reports and exports

## License

MIT License
