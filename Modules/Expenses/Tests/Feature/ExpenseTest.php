<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;

class ExpenseTest extends FeatureTestCase
{
    #[Test]
    public function it_creates_an_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_number' => 'EXP-001',
            'expense_status' => ExpenseStatus::DRAFT->value,
            'expense_type' => ExpenseType::FIXED->value,
            'expensed_at' => now()->format('Y-m-d H:i:s'),
            'expense_amount' => 120.00,
            'currency' => 'USD',
            'description' => 'Office chairs',
        ];

        /* Act */
        $expense = Expense::create($payload);

        /* Assert */
        $this->assertNotNull($expense->id);
        $this->assertDatabaseHas('expenses', [
            'expense_number' => 'EXP-001',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_status' => ExpenseStatus::DRAFT->value,
            'expense_type' => ExpenseType::FIXED->value,
        ]);
        $this->assertEquals(ExpenseStatus::DRAFT, $expense->expense_status);
        $this->assertEquals(ExpenseType::FIXED, $expense->expense_type);
    }

    #[Test]
    public function it_retrieves_an_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $category = ExpenseCategory::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        /* Act */
        $retrieved = Expense::find($expense->id);

        /* Assert */
        $this->assertNotNull($retrieved);
        $this->assertEquals($expense->id, $retrieved->id);
        $this->assertEquals($expense->expense_number, $retrieved->expense_number);
    }

    #[Test]
    public function it_updates_an_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $category = ExpenseCategory::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);

        /* Act */
        $expense->update([
            'expense_status' => ExpenseStatus::APPROVED->value,
        ]);

        /* Assert */
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_status' => ExpenseStatus::APPROVED->value,
        ]);
    }

    #[Test]
    public function it_deletes_an_expense(): void
    {
        /* Arrange */
        $user = User::find(1);
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        $expenseId = $expense->id;

        /* Act */
        $expense->delete();

        /* Assert */
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
        $this->assertNull(Expense::find($expenseId));
    }

    #[Test]
    public function it_lists_user_expenses(): void
    {
        /* Arrange */
        $user = User::find(1);
        $otherUser = User::find(2);

        Expense::factory()->count(3)->create(['user_id' => $user->id]);
        Expense::factory()->count(2)->create(['user_id' => $otherUser->id]);

        /* Act */
        $userExpenses = Expense::where('user_id', $user->id)->get();

        /* Assert */
        $this->assertCount(3, $userExpenses);
        $this->assertTrue($userExpenses->every(fn ($e) => $e->user_id === $user->id));
    }

    #[Test]
    public function it_casts_expense_enums_correctly(): void
    {
        /* Arrange */
        $user = User::find(1);
        $category = ExpenseCategory::factory()->create();

        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_number' => 'EXP-002',
            'expense_status' => ExpenseStatus::APPROVED,
            'expense_type' => ExpenseType::RECURRING,
            'expensed_at' => now(),
            'expense_amount' => 50.00,
        ]);

        /* Act */
        $retrieved = Expense::find($expense->id);

        /* Assert */
        $this->assertInstanceOf(ExpenseStatus::class, $retrieved->expense_status);
        $this->assertInstanceOf(ExpenseType::class, $retrieved->expense_type);
        $this->assertEquals(ExpenseStatus::APPROVED, $retrieved->expense_status);
        $this->assertEquals(ExpenseType::RECURRING, $retrieved->expense_type);
    }

    #[Test]
    public function it_retrieves_expense_with_category(): void
    {
        /* Arrange */
        $user = User::find(1);
        $category = ExpenseCategory::factory()->create(['category_name' => 'Travel']);
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        /* Act */
        $retrieved = Expense::with('category')->find($expense->id);

        /* Assert */
        $this->assertNotNull($retrieved->category);
        $this->assertEquals('Travel', $retrieved->category->category_name);
    }
}
