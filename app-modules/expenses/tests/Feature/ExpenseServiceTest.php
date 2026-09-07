<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Services\ExpenseService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_approves_a_submitted_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create(['expense_status' => ExpenseStatus::SUBMITTED->value]);

        /* Act */
        (new ExpenseService())->approveExpense($expense);

        /* Assert */
        $this->assertSame(ExpenseStatus::APPROVED, $expense->fresh()->expense_status);
    }

    #[Test]
    public function it_rejects_a_submitted_expense_back_to_draft(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create(['expense_status' => ExpenseStatus::SUBMITTED->value]);

        /* Act */
        (new ExpenseService())->rejectExpense($expense);

        /* Assert */
        $this->assertSame(ExpenseStatus::DRAFT, $expense->fresh()->expense_status);
    }

    #[Test]
    public function it_deletes_an_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();

        /* Act */
        (new ExpenseService())->deleteExpense($expense);

        /* Assert */
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
}
