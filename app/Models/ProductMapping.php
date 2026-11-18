<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMapping extends Model
{
    protected $fillable = [
        'master_product_id',
        'platform',
        'raw_sku',
        'raw_variant',
        'raw_product_name',
    ];

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}

