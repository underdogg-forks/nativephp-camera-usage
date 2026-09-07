<?php

namespace Modules\Expenses\Filament\Company\Resources\ExpenseCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)->schema([
                TextInput::make('category_name')
                    ->label('Category name')
                    ->autofocus()
                    ->required()
                    ->maxLength(50),
                Textarea::make('description')
                    ->rows(3),
            ]),
        ]);
    }
}
