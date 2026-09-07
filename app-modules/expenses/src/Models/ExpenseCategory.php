<?php

namespace Modules\Expenses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\BelongsToCompany;
use Modules\Expenses\Database\Factories\ExpenseCategoryFactory;

class ExpenseCategory extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category_name',
        'description',
    ];

    protected static function newFactory()
    {
        return ExpenseCategoryFactory::new();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
