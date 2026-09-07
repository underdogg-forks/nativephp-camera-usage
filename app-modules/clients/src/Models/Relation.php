<?php

namespace Modules\Clients\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clients\Database\Factories\RelationFactory;
use Modules\Core\Traits\BelongsToCompany;

/**
 * Stub model: a customer or vendor a company transacts with. Only carries
 * the fields Expenses' customer_id/vendor_id foreign keys actually need.
 */
class Relation extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return RelationFactory::new();
    }
}
