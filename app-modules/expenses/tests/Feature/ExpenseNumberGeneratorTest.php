<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Company;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Support\ExpenseNumberGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_the_first_number_for_a_company_with_no_expenses(): void
    {
        /* Arrange */
        $company = Company::factory()->create();

        /* Act */
        $number = ExpenseNumberGenerator::next($company);

        /* Assert */
        $this->assertSame('EXP-00001', $number);
    }

    #[Test]
    public function it_increments_per_existing_expense_for_that_company(): void
    {
        /* Arrange */
        $company = Company::factory()->create();
        Expense::factory()->for($company, 'company')->count(4)->create();

        /* Act */
        $number = ExpenseNumberGenerator::next($company);

        /* Assert */
        $this->assertSame('EXP-00005', $number);
    }

    #[Test]
    public function numbering_is_independent_per_company(): void
    {
        /* Arrange */
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Expense::factory()->for($companyA, 'company')->count(9)->create();

        /* Act */
        $numberForB = ExpenseNumberGenerator::next($companyB);

        /* Assert */
        $this->assertSame('EXP-00001', $numberForB);
    }
}
