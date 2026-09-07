<?php

namespace Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Enums\UserRole;
use Modules\Expenses\Filament\Company\Resources\ExpenseCategories\ExpenseCategoryResource;
use Modules\Expenses\Services\ExpenseCategoryService;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false)
                ->modalWidth('lg')
                ->action(function (array $data): void {
                    app(ExpenseCategoryService::class)->createExpenseCategory($data);
                }),
        ];
    }
}
