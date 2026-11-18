<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'platform',
        'courier',
        'sender_keywords',
    ];

    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }
}

