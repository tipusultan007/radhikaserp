<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'customer_target_scheme_id',
    'customer_id',
    'product_id',
    'product_variant_id',
    'target_qty',
    'bonus_per_unit'
])]
class CustomerTargetItem extends Model
{
    use HasFactory;

    protected $casts = [
        'target_qty' => 'decimal:2',
        'bonus_per_unit' => 'decimal:2',
    ];

    public function scheme()
    {
        return $this->belongsTo(CustomerTargetScheme::class, 'customer_target_scheme_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

