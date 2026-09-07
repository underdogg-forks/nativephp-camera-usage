<?php

namespace Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Enums\UserRole;
use Modules\Expenses\Models\ExpenseCategory;
use Modules\Expenses\Services\ExpenseCategoryService;

class ExpenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category_name')->searchable()->sortable(),
                TextColumn::make('description')->limit(50)->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                        ->modalWidth('lg')
                        ->action(function (ExpenseCategory $record, array $data): void {
                            app(ExpenseCategoryService::class)->updateExpenseCategory($record, $data);
                        }),
                    DeleteAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                        ->action(function (ExpenseCategory $record): void {
                            app(ExpenseCategoryService::class)->deleteExpenseCategory($record);
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
