<?php

namespace Modules\Expenses\Support;

/**
 * Pure line-item math for an ExpenseItem: subtotal, per-tax amounts, and the
 * final total. Mirrors InvoicePlane-v2's expense item totals without any
 * model or database dependency, so it's trivially unit-testable.
 */
class ExpenseCalculator
{
    public static function calculateLineItem(
        float $quantity,
        float $price,
        float $discount = 0.0,
        ?float $taxRate1Percent = null,
        ?float $taxRate2Percent = null,
    ): array {
        $subtotal = round(($quantity * $price) - $discount, 4);

        $tax1 = null !== $taxRate1Percent
            ? round($subtotal * ($taxRate1Percent / 100), 4)
            : 0.0;

        $tax2 = null !== $taxRate2Percent
            ? round($subtotal * ($taxRate2Percent / 100), 4)
            : 0.0;

        $taxTotal = round($tax1 + $tax2, 4);
        $total = round($subtotal + $taxTotal, 4);

        return [
            'subtotal' => $subtotal,
            'tax_1' => $tax1,
            'tax_2' => $tax2,
            'tax_total' => $taxTotal,
            'total' => $total,
        ];
    }

    public static function sumItems(iterable $lineItemTotals): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $total = 0.0;

        foreach ($lineItemTotals as $item) {
            $subtotal += $item['subtotal'];
            $taxTotal += $item['tax_total'];
            $total += $item['total'];
        }

        return [
            'subtotal' => round($subtotal, 4),
            'tax_total' => round($taxTotal, 4),
            'total' => round($total, 4),
        ];
    }
}
