<?php

namespace Modules\Expenses\Filament\Company\Resources\ExpenseCategories;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\UserRole;
use Modules\Core\Filament\Company\Resources\BaseResource;
use Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Schemas\ExpenseCategoryForm;
use Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Tables\ExpenseCategoriesTable;
use Modules\Expenses\Models\ExpenseCategory;

class ExpenseCategoryResource extends BaseResource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'Expense category';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Expense categories';
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseCategories::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([UserRole::CUSTOMER_ADMIN->value, UserRole::CUSTOMER->value]) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false;
    }
}
