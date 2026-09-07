<?php

namespace Modules\Expenses\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseItem;
use Modules\Expenses\Support\ExpenseCalculator;

class ExpenseItemFactory extends Factory
{
    protected $model = ExpenseItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $price = $this->faker->randomFloat(2, 5, 200);

        $totals = ExpenseCalculator::calculateLineItem($quantity, $price);

        return [
            'expense_id' => Expense::factory(),
            'item_name' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => 0,
            ...$totals,
        ];
    }
}
