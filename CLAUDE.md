# NativePHP Camera Integration — Claude Context

## What this is

Laravel 13 + NativePHP mobile framework for native Android/iOS apps with camera integration for expense receipt scanning, backed by a multi-tenant Filament v5 admin panel (InvoicePlane-v2's Expenses module pattern) for review and approval.

**Workflow:** mobile app captures a receipt → authenticated REST API creates the expense → secretary reviews and approves it in the Company Filament panel. Both paths are real and tested — see `MobileToCompanyPanelIntegrationTest`.

**Resolved versions** (from `composer.lock`):
- `laravel/framework` 13.0+
- `internachi/modular` — real module architecture (see Modules below)
- `filament/filament` ^5.6
- `spatie/laravel-permission` — roles
- `nativephp/mobile` dev-main (4.9.9), `nativephp/mobile-camera` ^1.0, `nativephp/mobile-ui` 0.4.0
- `phpunit/phpunit` ^10.5 || ^11.0 (pure PHPUnit, no Pest)

---

## Modules (`internachi/modular`)

Each module under `app-modules/{name}/` is a real Composer package (own `composer.json`, wired via a root `path` repository), **not** the old `Modules/` directory this project used to have:

```
app-modules/{name}/
  src/                    # PSR-4 root, namespace Modules\{Name}\
  database/{factories,migrations,seeders}/
  tests/{Feature,Unit}/
  composer.json           # own autoload + extra.laravel.providers (auto-discovered)
```

| Module | Purpose |
|---|---|
| `core` | `Company` (tenant), `BelongsToCompany` trait, `UserRole` enum, roles seeder, `CompanyPanelProvider` (Filament tenant panel), `BaseResource` |
| `expenses` | Everything expense-related: models, REST API, Filament resources — see below |
| `clients` | Stub: `Relation` model (customer/vendor) — only exists so `expenses.customer_id`/`vendor_id` FKs resolve |
| `products` | Stub: `Product`, `ProductUnit` — only exists so `expense_items.item_id`/`unit_id` FKs resolve |
| `invoices` | Stub: `Invoice` — only exists so `expenses.invoice_id` resolves |

The `clients`/`products`/`invoices` stubs are intentionally minimal (model + migration + factory, no business logic, no Filament UI) — they mirror InvoicePlane-v2's schema shape without porting modules this app doesn't use.

Registering a new module: add it to root `composer.json`'s `require` (`modules/{name}: "*"`), it resolves via the `app-modules/*` path repository already configured there.

---

## Multi-tenancy

The tenant model is `Modules\Core\Models\Company`. Users belong to companies via the `company_user` pivot (`App\Models\User::companies()`).

- **`BelongsToCompany` trait** (`Modules\Core\Traits`): auto-assigns `company_id` on create (from the current Filament tenant, session, or the user's first company) and applies a global scope filtering by it. Used by `Expense`, `ExpenseCategory`, `ExpenseItem`, and the stub modules' models.
- **Deliberately does NOT block-all** when an authenticated user has no company (unlike InvoicePlane-v2, which is company-panel-only) — this app also has a personal, non-tenant REST API where users legitimately have no company. Scoping only narrows results once a company context exists.
- **`UserRole` enum** (`Modules\Core\Enums`): `super_admin`, `admin`, `assist`, `client_admin` (secretary/company admin), `client` (mobile employee). `User::canAccessPanel()`/`canAccessTenant()`/`isSuperAdmin()` gate the Filament `company` panel.
- Roles are seeded via `Modules\Core\Database\Seeders\RolesSeeder` (idempotent, `Role::firstOrCreate`).

**Gotcha found the hard way:** Filament v5's `EditAction`/`DeleteAction`/`CreateAction` do NOT call a resource's `canEdit()`/`canCreate()`/`canDelete()` for visibility — they call `getEditAuthorizationResponse()` etc., which falls through to `Gate::inspect()` against any registered Model Policy. If a Policy exists for the model (like `ExpensePolicy`, built for the REST API's user-ownership checks), it silently gates the Filament actions too, regardless of `canEdit()` overrides. `ExpenseResource` fixes this by overriding `get*AuthorizationResponse()` directly. Watch for this on any new resource whose model has a registered Policy.

---

## Expenses module (`app-modules/expenses`)

### Schema

- `expense_categories`: `company_id` (nullable — see migration comment), `category_name`, `description`
- `expenses`: `company_id` (nullable), `user_id` (required — who captured/entered it), `category_id`, `customer_id`/`vendor_id` (→ `relations`), `invoice_id` (→ `invoices`), `expense_number`, `expense_type`, `expense_status`, `expense_amount`, `currency`, `receipt_path`, `description`, `expensed_at`
- `expense_items`: line items on an expense — `item_id`/`unit_id` (→ products), `tax_rate_id`/`tax_rate_2_id`, `quantity`/`price`/`discount`/`subtotal`/`tax_1`/`tax_2`/`tax_total`/`total`

`company_id` is nullable rather than InvoicePlane-v2's `NOT NULL`: this app's camera-capture flow creates expenses outside of any Filament tenant context. Filament-panel-created expenses always get a real `company_id` (see `BelongsToCompany`); personal-API expenses may have none.

### Support classes

- `ExpenseCalculator`: pure line-item math (subtotal, per-tax amounts, total) — no model/DB dependency, fully unit-testable
- `ExpenseNumberGenerator::next(Company $company)`: sequential `EXP-00001`-style numbering, scoped per company

### REST API (Sanctum, `routes/api.php`)

Bearer-token-authenticated CRUD + approve/reject workflow for `Expense`. See `ExpenseApiController`, `ExpensePolicy` (user-ownership authorization — a user can only see/edit/delete their own expenses), `StoreExpenseRequest`/`UpdateExpenseRequest`. Full reference: `API_DOCUMENTATION.md`. Postman collection: `NativePHP-Expenses-API.postman_collection.json`.

### Filament (Company panel)

`Filament/Company/Resources/{ExpenseCategories,Expenses}/` — each split into `{Resource}.php`, `Schemas/{Model}Form.php`, `Tables/{Model}sTable.php`, `Pages/`. `ExpenseCategoryResource` manages create/edit/delete via modal actions on the list page; `ExpenseResource` has a routed create page plus modal edit and real approve/reject row actions. Both delegate to a `Services\{Model}Service` rather than touching Eloquent directly from the UI layer.

Line items are entered via a `Repeater` on the Expense form (`->relationship('items')`) rather than a separate nested Filament resource — InvoicePlane-v2 has an `ExpenseItemResource` scaffolded but never actually wires it into `getRelations()`/`getPages()`, so it was dead code there too.

### Camera → Storage flow (unchanged from the original NativePHP integration)

1. **Native Camera Capture** (`resources/js/NativeReceiptCaptureComponent.vue`) — `Camera::getPhoto()`, fires `PhotoTaken` with path + EXIF orientation, face detection support.
2. **Storage**: `Storage::disk('expenses')` (private, `storage/app/expenses`), path `receipts/YYYY/MM/DD/random_filename.{jpg|png}`, MIME + size validated, random filenames (no original filename leakage).
3. **Database**: `Expense` model, `user_id` FK, `expense_status` enum.

---

## Configuration notes

### Filesystem (`config/filesystems.php`)

```php
'expenses' => [
    'driver' => 'local',
    'root' => storage_path('app/expenses'),
    'url' => env('APP_URL', 'http://localhost') . '/storage/expenses',
    'visibility' => 'private',
]
```

### Testing (`phpunit.xml`)

- SQLite in-memory (`:memory:`), `RefreshDatabase`, foreign keys enabled
- Test discovery: `tests/{Unit,Feature}` **and** `app-modules/*/tests/{Unit,Feature}` — every module's tests run as part of the default suite
- **Run tests with `vendor/bin/phpunit --testdox`**, not `php artisan test` — the latter has a pre-existing environment quirk in sandboxes with no committed `.env` file (tries `file_get_contents('.env')` and reports every test as a warning); unrelated to application code, `vendor/bin/phpunit` is unaffected

---

## Skills imported (from InvoicePlane v2)

Located in `.claude/skills/`. Note `laravel-modules` documents both the current `internachi/modular` convention (`app-modules/{name}/src/`, used here) **and** InvoicePlane-v2's own legacy `nwidart/laravel-modules` layout (`Modules/{Name}/`, no `src/`) for when working directly in that repo — don't confuse the two. Other relevant ones: `filament-multi-tenancy`, `filament-panel-setup`, `filament-resource-pages`, `filament-resource-testing`, `spatie-roles`, `tenant-middleware`, `test-honesty`, `test-gaps`, `pest-control`, `security-review`, `service-layer`.

---

## How to use this codebase

1. **Run tests**: `vendor/bin/phpunit --testdox`
2. **Code review**: Run `senior-laravel-developer-code-reviewer` skill
3. **Test gaps**: Run `test-gaps` skill to identify missing coverage
4. **Test honesty**: Run `test-honesty` to validate factory/schema alignment
5. **Add a module**: see `laravel-modules` skill — new modules go in `app-modules/`, not `Modules/`
