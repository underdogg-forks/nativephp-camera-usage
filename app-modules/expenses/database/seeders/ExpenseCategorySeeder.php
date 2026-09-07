<?php

namespace Modules\Expenses\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Expenses\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Travel', 'description' => 'Transportation, flights, accommodations'],
            ['category_name' => 'Meals', 'description' => 'Client meals, team lunches, business dining'],
            ['category_name' => 'Utilities', 'description' => 'Electric, water, internet, phone bills'],
            ['category_name' => 'Maintenance', 'description' => 'Equipment repairs, facility upkeep'],
            ['category_name' => 'Office Supplies', 'description' => 'Stationery, office equipment, furniture'],
            ['category_name' => 'Software', 'description' => 'Subscriptions, licenses, cloud services'],
            ['category_name' => 'Professional Services', 'description' => 'Legal, accounting, consulting fees'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['category_name' => $category['category_name']],
                ['description' => $category['description']]
            );
        }
    }
}
