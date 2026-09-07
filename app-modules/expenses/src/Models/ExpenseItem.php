<?php

namespace Modules\Expenses\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TaxRate;
use Modules\Core\Traits\BelongsToCompany;
use Modules\Expenses\Database\Factories\ExpenseItemFactory;
use Modules\Products\Models\Product;
use Modules\Products\Models\ProductUnit;

class ExpenseItem extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price' => 'decimal:4',
        'discount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_1' => 'decimal:4',
        'tax_2' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function taxRate2(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_2_id');
    }

    protected static function newFactory(): Factory
    {
        return ExpenseItemFactory::new();
    }
}
