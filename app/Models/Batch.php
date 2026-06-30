<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['batch_no', 'product_id', 'product_variant_id', 'warehouse_id', 'import_id', 'qty_in', 'qty_out', 'remaining_qty', 'cost_per_unit', 'expiry_date'])]
class Batch extends Model
{
    use HasFactory;

    protected $casts = [
        'qty_in' => 'decimal:3',
        'qty_out' => 'decimal:3',
        'remaining_qty' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
    ];

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

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}
