<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    protected $fillable = [
        'sku',           // SKU Shopee / Standar
        'seller_sku',    // Seller SKU TikTok Shop / Tokopedia
        'stodo_code',    // Kode Acuan Input StoDo
        'name',          // Nama Produk
        'variant',       // Variasi
        'brand',         // Aruna / Moveda
        'unit',
        'notes',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(ProductMapping::class);
    }

    public function waybillItems(): HasMany
    {
        return $this->hasMany(WaybillItem::class);
    }

    public function getDisplaySkuAttribute(): string
    {
        return $this->stodo_code ?: $this->sku;
    }

    public function getDisplayNameAttribute(): string
    {
        $var = $this->variant ? " - {$this->variant}" : "";
        $code = $this->display_sku ? " ({$this->display_sku})" : "";
        return "{$this->name}{$var}{$code}";
    }
}
