<?php

namespace Modules\Expenses\Filament\Company\Resources\Expenses\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Clients\Enums\RelationType;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Support\ExpenseNumberGenerator;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Section::make('Details')->schema([
                    TextInput::make('expense_number')
                        ->required()
                        ->default(fn (string $operation) => 'create' === $operation && Filament::getTenant()
                            ? ExpenseNumberGenerator::next(Filament::getTenant())
                            : null)
                        ->unique(ignoreRecord: true),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'category_name')
                        ->searchable()
                        ->preload(),
                    Select::make('expense_type')
                        ->options(ExpenseType::options())
                        ->required(),
                    Select::make('expense_status')
                        ->options(ExpenseStatus::options())
                        ->required()
                        ->default(ExpenseStatus::DRAFT->value),
                    TextInput::make('expense_amount')
                        ->numeric()
                        ->required(),
                    TextInput::make('currency')
                        ->default('USD')
                        ->maxLength(3),
                    DatePicker::make('expensed_at')
                        ->required(),
                ])->columnSpan(1),

                Section::make('Parties')->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship(
                            name: 'customer',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('relation_type', RelationType::CUSTOMER->value),
                        )
                        ->searchable()
                        ->preload(),
                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->relationship(
                            name: 'vendor',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('relation_type', RelationType::VENDOR->value),
                        )
                        ->searchable()
                        ->preload(),
                ])->columnSpan(1),

                Section::make('Notes')->schema([
                    Textarea::make('description')
                        ->rows(6),
                ])->columnSpan(1),
            ]),

            Section::make('Line items')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->defaultItems(0)
                        ->addActionLabel('Add item')
                        ->columns(4)
                        ->schema([
                            TextInput::make('item_name')->label('Description')->columnSpan(2),
                            TextInput::make('quantity')->numeric()->default(1)->required(),
                            TextInput::make('price')->numeric()->default(0)->required(),
                        ]),
                ])
                ->columnSpanFull()
                ->collapsed(),
        ]);
    }
}
