<?php

namespace Modules\Expenses\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expenses\Models\ExpenseCategory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'category_name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional(0.5)->sentence(),
        ];
    }
}
