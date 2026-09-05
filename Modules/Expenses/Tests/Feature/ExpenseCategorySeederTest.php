<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;

class ExpenseCategorySeederTest extends FeatureTestCase
{
    #[Test]
    public function it_seeds_default_expense_categories(): void
    {
        /* Arrange */
        $expectedCategories = [
            'Travel',
            'Meals',
            'Utilities',
            'Maintenance',
            'Office Supplies',
            'Software',
            'Professional Services',
        ];

        /* Act */
        Artisan::call('db:seed', [
            '--class' => 'Modules\Expenses\Database\Seeders\ExpenseCategorySeeder',
        ]);

        /* Assert */
        $this->assertCount(count($expectedCategories), ExpenseCategory::all());
        foreach ($expectedCategories as $category) {
            $this->assertDatabaseHas('expense_categories', ['category_name' => $category]);
        }
    }

    #[Test]
    public function it_is_idempotent_when_seeding_twice(): void
    {
        /* Act */
        Artisan::call('db:seed', [
            '--class' => 'Modules\Expenses\Database\Seeders\ExpenseCategorySeeder',
        ]);
        Artisan::call('db:seed', [
            '--class' => 'Modules\Expenses\Database\Seeders\ExpenseCategorySeeder',
        ]);

        /* Assert */
        $this->assertCount(7, ExpenseCategory::all());
    }
}
