<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Enums\RelationType;
use Modules\Clients\Models\Relation;
use Modules\Core\Models\Company;
use Modules\Core\Models\TaxRate;
use Modules\Invoices\Models\Invoice;
use Modules\Products\Models\Product;
use Modules\Products\Models\ProductUnit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression coverage for the FK-only stub modules (Invoices, Clients,
 * Products, Core\TaxRate) that exist solely so Expenses' foreign keys
 * resolve. Not meant to grow business logic of their own.
 */
class StubModulesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_customer_relation_scoped_to_a_company(): void
    {
        /* Arrange */
        $company = Company::factory()->create();

        /* Act */
        $customer = Relation::factory()->for($company, 'company')->customer()->create();

        /* Assert */
        $this->assertSame(RelationType::CUSTOMER->value, $customer->relation_type);
        $this->assertSame($company->id, $customer->company_id);
    }

    #[Test]
    public function it_creates_a_vendor_relation(): void
    {
        /* Arrange & Act */
        $vendor = Relation::factory()->vendor()->create();

        /* Assert */
        $this->assertSame(RelationType::VENDOR->value, $vendor->relation_type);
    }

    #[Test]
    public function it_creates_a_product_with_a_unit(): void
    {
        /* Arrange */
        $company = Company::factory()->create();
        $unit = ProductUnit::factory()->for($company, 'company')->create(['name' => 'hour']);

        /* Act */
        $product = Product::factory()->for($company, 'company')->create(['unit_id' => $unit->id]);

        /* Assert */
        $this->assertSame('hour', $product->unit->name);
    }

    #[Test]
    public function it_creates_an_invoice(): void
    {
        /* Arrange & Act */
        $invoice = Invoice::factory()->create();

        /* Assert */
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function it_creates_a_tax_rate(): void
    {
        /* Arrange & Act */
        $taxRate = TaxRate::factory()->create(['name' => 'VAT', 'rate' => 21]);

        /* Assert */
        $this->assertSame('VAT', $taxRate->name);
        $this->assertEquals(21, $taxRate->rate);
    }
}
