<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillItem extends Model
{
    protected $fillable = [
        'waybill_id',
        'master_product_id',
        'raw_product_name',
        'raw_sku',
        'raw_variant',
        'qty',
        'is_mapped',
    ];

    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}

