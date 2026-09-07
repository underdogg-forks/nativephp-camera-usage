<?php

namespace Modules\Expenses\Tests\Feature;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Livewire\Livewire;
use Modules\Core\Models\Company;
use Modules\Core\Tests\AbstractCompanyPanelTestCase;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Expenses\Filament\Company\Resources\Expenses\Pages\CreateExpense;
use Modules\Expenses\Filament\Company\Resources\Expenses\Pages\ListExpenses;
use Modules\Expenses\Models\Expense;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ListExpenses::class)]
class ExpenseResourceTest extends AbstractCompanyPanelTestCase
{
    #[Test]
    public function it_lists_only_the_current_companys_expenses(): void
    {
        /* Arrange */
        $ownExpense = Expense::factory()->for($this->company, 'company')->create();

        $otherCompany = Company::factory()->create();
        Expense::factory()->for($otherCompany, 'company')->create();

        /* Act & Assert */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->assertCanSeeTableRecords([$ownExpense]);
    }

    #[Test]
    public function customer_admin_can_create_an_expense(): void
    {
        /* Act */
        Livewire::actingAs($this->user)
            ->test(CreateExpense::class)
            ->set('data.expense_number', 'EXP-TEST-01')
            ->set('data.expense_type', ExpenseType::ONE_TIME->value)
            ->set('data.expense_status', ExpenseStatus::DRAFT->value)
            ->set('data.expense_amount', 42.50)
            ->set('data.expensed_at', now()->toDateString())
            ->call('create')
            ->assertHasNoFormErrors();

        /* Assert */
        $this->assertDatabaseHas('expenses', [
            'expense_number' => 'EXP-TEST-01',
            'company_id' => $this->company->id,
        ]);
    }

    #[Test]
    public function it_generates_an_expense_number_by_default_on_the_create_form(): void
    {
        /* Act */
        $component = Livewire::actingAs($this->user)->test(CreateExpense::class);

        /* Assert */
        $this->assertSame('EXP-00001', $component->get('data.expense_number'));
    }

    #[Test]
    public function customer_admin_can_edit_an_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->for($this->company, 'company')->create(['expense_amount' => 10]);

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->callTableAction(EditAction::class, $expense, data: [
                'expense_number' => $expense->expense_number,
                'expense_type' => $expense->expense_type->value,
                'expense_status' => $expense->expense_status->value,
                'expense_amount' => 99,
                'expensed_at' => $expense->expensed_at->toDateString(),
            ]);

        /* Assert */
        $this->assertEquals(99, $expense->fresh()->expense_amount);
    }

    #[Test]
    public function customer_admin_can_delete_an_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->for($this->company, 'company')->create();

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->callTableAction(DeleteAction::class, $expense);

        /* Assert */
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function customer_admin_can_approve_a_submitted_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->for($this->company, 'company')->create([
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ]);

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->callTableAction('approve', $expense);

        /* Assert */
        $this->assertSame(ExpenseStatus::APPROVED, $expense->fresh()->expense_status);
    }

    #[Test]
    public function customer_admin_can_reject_a_submitted_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->for($this->company, 'company')->create([
            'expense_status' => ExpenseStatus::SUBMITTED->value,
        ]);

        /* Act */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->callTableAction('reject', $expense);

        /* Assert */
        $this->assertSame(ExpenseStatus::DRAFT, $expense->fresh()->expense_status);
    }

    #[Test]
    public function approve_action_is_hidden_on_a_draft_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->for($this->company, 'company')->create([
            'expense_status' => ExpenseStatus::DRAFT->value,
        ]);

        /* Act & Assert */
        Livewire::actingAs($this->user)
            ->test(ListExpenses::class)
            ->assertTableActionHidden('approve', $expense);
    }
}
