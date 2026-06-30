<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['stock_transfer_id', 'product_variant_id', 'batch_id', 'qty'])]
class StockTransferItem extends Model
{
    use HasFactory;

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
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
