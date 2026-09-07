<?php

namespace Modules\Expenses\Filament\Company\Resources\Expenses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Expenses\Filament\Company\Resources\Expenses\ExpenseResource;
use Modules\Expenses\Services\ExpenseService;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // An expense created via the Company panel was manually entered by
        // whichever admin is at the keyboard, not captured by a mobile user
        // — record that here since user_id is required and there is no
        // camera-flow user to attribute it to.
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(ExpenseService::class)->createExpense($data);
    }
}
