<?php

namespace Modules\Expenses\Filament\Company\Resources\Expenses\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Enums\UserRole;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Services\ExpenseService;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_status')
                    ->badge()
                    ->formatStateUsing(fn (ExpenseStatus $state) => $state->label())
                    ->color(fn (ExpenseStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('expense_number')->searchable()->sortable(),
                TextColumn::make('category.category_name')->label('Category')->placeholder('-')->toggleable(),
                TextColumn::make('expense_type')->badge()->sortable()->toggleable(),
                TextColumn::make('vendor.name')->label('Vendor')->placeholder('-')->toggleable(),
                TextColumn::make('expensed_at')->date()->sortable(),
                TextColumn::make('expense_amount')->money(fn (Expense $record) => $record->currency)->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                        ->modalWidth('full')
                        ->action(function (Expense $record, array $data): void {
                            app(ExpenseService::class)->updateExpense($record, $data);
                        }),
                    DeleteAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                        ->action(function (Expense $record): void {
                            app(ExpenseService::class)->deleteExpense($record);
                        }),
                    Action::make('approve')
                        ->visible(fn (Expense $record) => (auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                            && ExpenseStatus::SUBMITTED === $record->expense_status)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Expense $record): void {
                            app(ExpenseService::class)->approveExpense($record);
                        }),
                    Action::make('reject')
                        ->visible(fn (Expense $record) => (auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                            && ExpenseStatus::SUBMITTED === $record->expense_status)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Expense $record): void {
                            app(ExpenseService::class)->rejectExpense($record);
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false),
                ]),
            ]);
    }
}
