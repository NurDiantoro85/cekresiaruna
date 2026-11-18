<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    protected $fillable = [
        'batch_code',
        'file_name',
        'platform',
        'total_pages',
        'total_waybills',
        'total_qty',
        'status',
    ];

    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }
}

