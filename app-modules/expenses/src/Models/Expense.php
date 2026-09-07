<?php

namespace Modules\Expenses\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Clients\Models\Relation;
use Modules\Core\Traits\BelongsToCompany;
use Modules\Expenses\Database\Factories\ExpenseFactory;
use Modules\Expenses\Enums\ExpenseStatus;
use Modules\Expenses\Enums\ExpenseType;
use Modules\Invoices\Models\Invoice;

class Expense extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'category_id',
        'customer_id',
        'vendor_id',
        'invoice_id',
        'expense_number',
        'expense_type',
        'expense_status',
        'expense_amount',
        'currency',
        'receipt_path',
        'description',
        'expensed_at',
        'status',
    ];

    protected $casts = [
        'expense_amount' => 'decimal:2',
        'expensed_at' => 'datetime',
        'expense_type' => ExpenseType::class,
        'expense_status' => ExpenseStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return ExpenseFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Relation::class, 'customer_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Relation::class, 'vendor_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
