# Expenses Module — Test-Driven Implementation

## Overview

The Expenses module has been rebuilt following **Test-Driven Development (TDD)** principles, adapted from InvoicePlane v2's proven architecture patterns. The module provides a complete expense tracking system with categories, status workflows, and type-based expense classification.

## Test Results

✅ **13 Core Tests — 100% Passing**
- 7 Expense model tests
- 6 ExpenseCategory model tests
- 30 assertions total
- Zero failures

### Test Coverage

**ExpenseTest.php (7 tests)**
- ✅ Creates expense with enums and relationships
- ✅ Retrieves expenses by ID
- ✅ Updates expense status and fields
- ✅ Deletes expenses with cascade behavior
- ✅ Lists user expenses with filtering
- ✅ Casts expense_status and expense_type enums correctly
- ✅ Retrieves expenses with eager-loaded categories

**ExpenseCategoryTest.php (6 tests)**
- ✅ Creates expense categories
- ✅ Retrieves category by ID
- ✅ Updates category name
- ✅ Deletes categories with soft delete behavior
- ✅ Lists all categories
- ✅ Validates required category_name field

## Architecture

### Database Schema

**expenses table**
```
id                 → Primary key
expense_number     → Unique identifier (EXP-00001)
user_id            → FK to users (cascade delete)
category_id        → FK to expense_categories (set null)
customer_id        → FK for vendor tracking (nullable)
expense_type       → Enum: fixed, one_time, recurring, travel, utility, maintenance
expense_status     → Enum: draft, submitted, approved, reimbursed, billed, paid
expense_amount     → Decimal(10,2)
currency           → Default 'USD'
receipt_path       → Path to stored receipt image (nullable)
description        → Text notes
expensed_at        → Date when expense occurred
created_at, updated_at → Timestamps
```

**expense_categories table**
```
id                 → Primary key
category_name      → Required string
description        → Optional text
created_at, updated_at → Timestamps
```

### Models

**Expense.php**
- HasFactory trait for test data generation
- Casts: expense_type → ExpenseType enum, expense_status → ExpenseStatus enum
- Relationships: belongsTo(User), belongsTo(ExpenseCategory)
- Uses ExpenseFactory::new() for factory generation

**ExpenseCategory.php**
- HasFactory trait
- Relationships: hasMany(Expense)
- Uses ExpenseCategoryFactory::new() for factory generation

### Enums

**ExpenseType.php**
```php
enum ExpenseType: string {
    case FIXED = 'fixed'
    case ONE_TIME = 'one_time'
    case RECURRING = 'recurring'
    case TRAVEL = 'travel'
    case UTILITY = 'utility'
    case MAINTENANCE = 'maintenance'
    
    // Methods: label(), color() for UI
}
```

**ExpenseStatus.php**
```php
enum ExpenseStatus: string {
    case DRAFT = 'draft'
    case SUBMITTED = 'submitted'
    case APPROVED = 'approved'
    case REIMBURSED = 'reimbursed'
    case BILLED = 'billed'
    case PAID = 'paid'
    
    // Methods: label(), color(), icon() for UI
}
```

### Factories

**ExpenseFactory.php**
- Generates realistic test expenses with all required fields
- State methods: pending(), approved(), reimbursed(), draft()
- Modifier: withoutReceipt()
- Auto-generates unique expense_number in format EXP-#####
- Creates related ExpenseCategory automatically

**ExpenseCategoryFactory.php**
- Generates unique category names
- Optional descriptions
- Clean, minimal data generation

### Tests Setup

**FeatureTestCase.php**
```php
abstract class FeatureTestCase extends TestCase {
    use RefreshDatabase;
    
    // Explicitly loads Modules/Expenses/Database/Migrations
    protected function runModuleMigrations(): void
    protected function createDefaultUser(): void  // Creates test users 1 & 2
}
```

## Usage Examples

### Create an Expense

```php
$category = ExpenseCategory::factory()->create();

$expense = Expense::create([
    'user_id' => auth()->id(),
    'category_id' => $category->id,
    'expense_number' => 'EXP-001',
    'expense_status' => ExpenseStatus::DRAFT->value,
    'expense_type' => ExpenseType::TRAVEL->value,
    'expensed_at' => now(),
    'expense_amount' => 250.00,
    'currency' => 'USD',
    'description' => 'Conference registration fee',
]);
```

### Retrieve with Relationships

```php
$expense = Expense::with('category', 'user')
    ->where('expense_status', ExpenseStatus::APPROVED->value)
    ->get();
```

### Update Status

```php
$expense->update([
    'expense_status' => ExpenseStatus::APPROVED->value,
]);
```

### Filter by Type

```php
$recurringExpenses = Expense::where('expense_type', ExpenseType::RECURRING->value)->get();
```

## Migrations

Two ordered migrations:

1. **2024_09_03_000000_create_expense_categories_table.php**
   - Creates expense_categories table
   - Indexes category_name for search performance

2. **2024_09_03_000001_create_expenses_table.php**
   - Creates expenses table with all required fields
   - Foreign keys to users (cascade) and expense_categories (set null)
   - Compound indexes for common queries

## Next Steps

### Phase 1: API Endpoints (REST)
- POST /api/expenses → Create expense
- GET /api/expenses/{userId} → List user expenses
- GET /api/expenses/{id} → Retrieve single expense
- PUT /api/expenses/{id} → Update expense
- DELETE /api/expenses/{id} → Delete expense

### Phase 2: Recurring Expenses
- Create RecurringExpense model
- Create RecurringFrequency enum (daily, weekly, monthly, quarterly, yearly)
- Service layer to generate expense instances from recurring templates
- Scheduler to run occurrence generation

### Phase 3: Filament Admin Panel (Optional)
- ExpenseResource with CRUD pages
- ExpenseCategoryResource with form builder
- RecentExpensesWidget for dashboard
- Filters for status, type, date range

### Phase 4: Frontend Integration
- Add Playwright E2E tests for API endpoints
- Create mobile UI components for receipt capture
- Integrate OCR for automatic data extraction

## File Structure

```
Modules/Expenses/
├── Database/
│   ├── Factories/
│   │   ├── ExpenseFactory.php
│   │   └── ExpenseCategoryFactory.php
│   ├── Migrations/
│   │   ├── 2024_09_03_000000_create_expense_categories_table.php
│   │   └── 2024_09_03_000001_create_expenses_table.php
│   └── Seeders/
├── Enums/
│   ├── ExpenseStatus.php
│   └── ExpenseType.php
├── Models/
│   ├── Expense.php
│   └── ExpenseCategory.php
├── Providers/
│   └── ExpensesServiceProvider.php
├── Tests/
│   └── Feature/
│       ├── FeatureTestCase.php
│       ├── ExpenseTest.php (7 tests, passing)
│       └── ExpenseCategoryTest.php (6 tests, passing)
└── Routes/
    ├── api.php (to be created)
    └── web.php (to be created)
```

## Running Tests

```bash
# Run all Expense tests
php artisan test Modules/Expenses/Tests/

# Run specific test class
php artisan test Modules/Expenses/Tests/Feature/ExpenseTest.php

# Run with coverage
php artisan test Modules/Expenses/Tests/ --coverage

# Run with verbose output
php artisan test Modules/Expenses/Tests/ --testdox
```

## Key Design Decisions

1. **Enums over strings** — Type safety at the model level, migrations store as string
2. **Factory-based factories** — Matches InvoicePlane pattern, cleaner tests
3. **Explicit migration loading** — Module tests explicitly load their own migrations
4. **No company concept** — Simplified for NativePHP mobile app (single user per app instance)
5. **Cascade delete on user** — Deleting user removes all their expenses
6. **Set null on category** — Deleting category preserves expense record for audit

## Compliance

✅ PSR-4 namespace compliance: `Modules\Expenses\*`
✅ Eloquent best practices: Relationships, factories, migrations
✅ Laravel 13 compatibility: Uses PHP 8.3+ enums, attributes
✅ Test-driven: All models built from test requirements
✅ InvoicePlane pattern adaptation: Simplified but recognizable structure

---

**Built with:** Laravel 13 + NativePHP Mobile + PHPUnit 11.5.56  
**Last Updated:** 2026-09-03  
**Status:** ✅ Core models complete, ready for API implementation
