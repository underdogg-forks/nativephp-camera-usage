<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Company;
use Modules\Core\Models\TaxRate;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseItem;
use Modules\Expenses\Support\ExpenseCalculator;
use Modules\Products\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_expense(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();

        /* Act */
        $item = ExpenseItem::factory()->for($expense)->create();

        /* Assert */
        $this->assertTrue($expense->items->contains($item));
        $this->assertSame($expense->id, $item->expense->id);
    }

    #[Test]
    public function it_links_to_a_product_and_unit(): void
    {
        /* Arrange */
        $company = Company::factory()->create();
        $product = Product::factory()->for($company, 'company')->create();

        /* Act */
        $item = ExpenseItem::factory()->create(['item_id' => $product->id]);

        /* Assert */
        $this->assertSame($product->id, $item->item->id);
    }

    #[Test]
    public function it_computes_totals_via_the_calculator_on_creation(): void
    {
        /* Arrange */
        $taxRate = TaxRate::factory()->create(['rate' => 20]);
        $expected = ExpenseCalculator::calculateLineItem(quantity: 3, price: 10, taxRate1Percent: 20);

        /* Act */
        $item = ExpenseItem::factory()->create([
            'quantity' => 3,
            'price' => 10,
            'tax_rate_id' => $taxRate->id,
            ...$expected,
        ]);

        /* Assert */
        $this->assertEquals($expected['subtotal'], (float) $item->subtotal);
        $this->assertEquals($expected['tax_total'], (float) $item->tax_total);
        $this->assertEquals($expected['total'], (float) $item->total);
    }

    #[Test]
    public function deleting_an_expense_cascades_to_its_items(): void
    {
        /* Arrange */
        $expense = Expense::factory()->create();
        $item = ExpenseItem::factory()->for($expense)->create();

        /* Act */
        $expense->delete();

        /* Assert */
        $this->assertDatabaseMissing('expense_items', ['id' => $item->id]);
    }
}
