<?php

namespace Modules\Expenses\Filament\Company\Resources\Expenses;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\UserRole;
use Modules\Core\Filament\Company\Resources\BaseResource;
use Modules\Expenses\Filament\Company\Resources\Expenses\Pages\CreateExpense;
use Modules\Expenses\Filament\Company\Resources\Expenses\Pages\ListExpenses;
use Modules\Expenses\Filament\Company\Resources\Expenses\Schemas\ExpenseForm;
use Modules\Expenses\Filament\Company\Resources\Expenses\Tables\ExpensesTable;
use Modules\Expenses\Models\Expense;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'Expense';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Expenses';
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
        ];
    }

    /**
     * Filament v5's EditAction/DeleteAction/CreateAction resolve their own
     * visibility via getXAuthorizationResponse() (which defaults to a
     * Gate/Policy check), not via canEdit()/canCreate() etc — overriding
     * only the canX() methods here would leave those actions silently
     * gated by ExpensePolicy (built for the personal REST API's user_id
     * ownership model) instead of this resource's role-based rules.
     */
    public static function getViewAnyAuthorizationResponse(): Response
    {
        return static::hasAnyRole([UserRole::CUSTOMER_ADMIN, UserRole::CUSTOMER])
            ? Response::allow()
            : Response::deny();
    }

    public static function getCreateAuthorizationResponse(): Response
    {
        return static::hasAnyRole([UserRole::CUSTOMER_ADMIN]) ? Response::allow() : Response::deny();
    }

    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return static::hasAnyRole([UserRole::CUSTOMER_ADMIN]) ? Response::allow() : Response::deny();
    }

    public static function getDeleteAuthorizationResponse(Model $record): Response
    {
        return static::hasAnyRole([UserRole::CUSTOMER_ADMIN]) ? Response::allow() : Response::deny();
    }

    /**
     * @param  array<UserRole>  $roles
     */
    protected static function hasAnyRole(array $roles): bool
    {
        return auth()->user()?->hasAnyRole(array_map(fn (UserRole $role) => $role->value, $roles)) ?? false;
    }
}
