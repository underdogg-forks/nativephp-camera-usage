<?php

namespace Modules\Expenses\Filament\Company\Resources\Expenses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Enums\UserRole;
use Modules\Expenses\Filament\Company\Resources\Expenses\ExpenseResource;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()?->hasRole(UserRole::CUSTOMER_ADMIN->value) ?? false),
        ];
    }
}
