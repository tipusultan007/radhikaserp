<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['repackaging_order_id', 'product_id', 'product_variant_id', 'warehouse_id', 'qty_produced', 'unit_cost', 'total_cost'])]
class RepackagingOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'repackaging_order_id',
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'qty_produced',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'qty_produced' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function repackagingOrder()
    {
        return $this->belongsTo(RepackagingOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
