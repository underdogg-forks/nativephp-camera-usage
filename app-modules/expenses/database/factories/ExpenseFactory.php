<?php

namespace Modules\Expenses\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseCategory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => ExpenseCategory::factory(),
            'expense_number' => $this->faker->unique()->numerify('EXP-#####'),
            'expense_status' => $this->faker->randomElement(ExpenseStatus::cases())->value,
            'expense_type' => $this->faker->randomElement(ExpenseType::cases())->value,
            'expensed_at' => $this->faker->dateTimeBetween('-1 years', '-1 month')->format('Y-m-d'),
            'expense_amount' => $this->faker->randomFloat(4, 10, 500),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'receipt_path' => sprintf(
                'receipts/%s/%s',
                now()->format('Y/m/d'),
                $this->faker->unique()->md5() . '.jpg'
            ),
            'description' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_status' => ExpenseStatus::APPROVED->value,
        ]);
    }

    public function reimbursed(): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_status' => ExpenseStatus::REIMBURSED->value,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);
    }

    public function withoutReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_path' => null,
        ]);
    }
}
