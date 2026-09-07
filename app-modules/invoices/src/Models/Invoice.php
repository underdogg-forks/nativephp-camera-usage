<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToCompany;
use Modules\Invoices\Database\Factories\InvoiceFactory;

class Invoice extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return InvoiceFactory::new();
    }
}
