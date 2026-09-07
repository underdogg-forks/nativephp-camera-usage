<?php

namespace Modules\Products\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToCompany;
use Modules\Products\Database\Factories\ProductUnitFactory;

class ProductUnit extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return ProductUnitFactory::new();
    }
}
