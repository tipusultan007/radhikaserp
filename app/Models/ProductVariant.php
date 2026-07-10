<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'name', 'sku', 'barcode', 'unit_qty', 'unit_type', 'unit_id', 'price', 'status'])]
class ProductVariant extends Model
{
    use HasFactory;

    protected $casts = [
        'unit_qty' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getBaseQuantity()
    {
        if ($this->unit) {
            return $this->unit_qty * $this->unit->multiplier;
        }
        return $this->unit_qty; // fallback
    }
}
