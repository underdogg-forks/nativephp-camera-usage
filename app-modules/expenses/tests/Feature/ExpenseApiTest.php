<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Expenses\Models\Expense;
use PHPUnit\Framework\Attributes\Test;

class ExpenseApiTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('expenses');
    }

    #[Test]
    public function it_creates_an_expense_via_api(): void
    {
        $this->markTestSkipped('API endpoints not implemented yet - pending Phase 1 implementation');
    }

    #[Test]
    public function it_retrieves_user_expenses_via_api(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_retrieves_single_expense_via_api(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_returns_404_for_missing_expense(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_updates_an_expense_via_api(): void
    {
        $this->markTestSkipped('API endpoints not implemented yet - pending Phase 1 implementation');
    }

    #[Test]
    public function it_deletes_an_expense_via_api(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_validates_required_fields_on_create(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_validates_numeric_amount(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }

    #[Test]
    public function it_validates_valid_status(): void
    {
        $this->markTestSkipped('Comprehensive auth tests moved to ExpenseApiAuthTest');
    }
}
