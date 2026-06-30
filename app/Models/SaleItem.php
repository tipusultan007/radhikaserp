<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['sale_id', 'product_variant_id', 'batch_id', 'qty', 'unit_price', 'total_price', 'total_weight'])]
class SaleItem extends Model
{
    use HasFactory;

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_weight' => 'decimal:3',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
