<?php

namespace Modules\Expenses\Tests\Feature;

use Modules\Expenses\Support\ExpenseCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_subtotal_from_quantity_and_price(): void
    {
        /* Act */
        $totals = ExpenseCalculator::calculateLineItem(quantity: 2, price: 50);

        /* Assert */
        $this->assertSame(100.0, $totals['subtotal']);
        $this->assertSame(100.0, $totals['total']);
    }

    #[Test]
    public function it_subtracts_discount_from_subtotal(): void
    {
        /* Act */
        $totals = ExpenseCalculator::calculateLineItem(quantity: 1, price: 100, discount: 10);

        /* Assert */
        $this->assertSame(90.0, $totals['subtotal']);
    }

    #[Test]
    public function it_applies_a_single_tax_rate(): void
    {
        /* Act */
        $totals = ExpenseCalculator::calculateLineItem(quantity: 1, price: 100, taxRate1Percent: 21);

        /* Assert */
        $this->assertSame(21.0, $totals['tax_1']);
        $this->assertSame(0.0, $totals['tax_2']);
        $this->assertSame(21.0, $totals['tax_total']);
        $this->assertSame(121.0, $totals['total']);
    }

    #[Test]
    public function it_applies_two_stacked_tax_rates(): void
    {
        /* Act */
        $totals = ExpenseCalculator::calculateLineItem(
            quantity: 1,
            price: 100,
            taxRate1Percent: 21,
            taxRate2Percent: 5,
        );

        /* Assert */
        $this->assertSame(21.0, $totals['tax_1']);
        $this->assertSame(5.0, $totals['tax_2']);
        $this->assertSame(26.0, $totals['tax_total']);
        $this->assertSame(126.0, $totals['total']);
    }

    #[Test]
    public function it_sums_multiple_line_items(): void
    {
        /* Arrange */
        $itemA = ExpenseCalculator::calculateLineItem(quantity: 1, price: 100, taxRate1Percent: 21);
        $itemB = ExpenseCalculator::calculateLineItem(quantity: 2, price: 50);

        /* Act */
        $summary = ExpenseCalculator::sumItems([$itemA, $itemB]);

        /* Assert */
        $this->assertSame(200.0, $summary['subtotal']);
        $this->assertSame(21.0, $summary['tax_total']);
        $this->assertSame(221.0, $summary['total']);
    }
}
