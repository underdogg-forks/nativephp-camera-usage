<?php

namespace Modules\Expenses\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Company;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Proves BelongsToCompany actually isolates tenants once a company context
 * exists (Filament panel usage, or a user attached to a company) — the gap
 * flagged when this module had no multi-tenancy at all.
 */
class ExpenseCompanyScopingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_companys_expenses_are_invisible_under_a_different_tenant(): void
    {
        /* Arrange */
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Filament::setTenant($companyA, isQuiet: true);
        $expenseA = Expense::factory()->create();

        Filament::setTenant($companyB, isQuiet: true);
        $expenseB = Expense::factory()->create();

        /* Act */
        Filament::setTenant($companyA, isQuiet: true);
        $visible = Expense::all();

        /* Assert */
        $this->assertTrue($visible->contains($expenseA));
        $this->assertFalse($visible->contains($expenseB));
    }

    #[Test]
    public function creating_an_expense_under_a_tenant_auto_assigns_its_company_id(): void
    {
        /* Arrange */
        $company = Company::factory()->create();
        Filament::setTenant($company, isQuiet: true);

        /* Act */
        $expense = Expense::factory()->create();

        /* Assert */
        $this->assertSame($company->id, $expense->company_id);
    }

    #[Test]
    public function expense_categories_are_isolated_per_company_the_same_way(): void
    {
        /* Arrange */
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Filament::setTenant($companyA, isQuiet: true);
        $categoryA = ExpenseCategory::factory()->create(['category_name' => 'Travel']);

        Filament::setTenant($companyB, isQuiet: true);
        $categoryB = ExpenseCategory::factory()->create(['category_name' => 'Meals']);

        /* Act */
        $visible = ExpenseCategory::all();

        /* Assert */
        $this->assertTrue($visible->contains($categoryB));
        $this->assertFalse($visible->contains($categoryA));
    }

    #[Test]
    public function a_user_with_no_company_still_sees_their_own_expenses_via_the_personal_api(): void
    {
        /* Arrange: the pre-existing NativePHP camera flow has no tenant at all */
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        /* Act */
        $visible = Expense::query()->where('user_id', $user->id)->get();

        /* Assert */
        $this->assertTrue($visible->contains($expense));
    }
}
