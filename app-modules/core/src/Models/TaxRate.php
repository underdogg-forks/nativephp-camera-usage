<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Database\Factories\TaxRateFactory;
use Modules\Core\Traits\BelongsToCompany;

class TaxRate extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:4',
    ];

    protected static function newFactory(): Factory
    {
        return TaxRateFactory::new();
    }
}
