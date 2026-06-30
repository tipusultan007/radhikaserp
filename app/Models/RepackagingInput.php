<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['repackaging_order_id', 'batch_id', 'product_id', 'qty_used'])]
class RepackagingInput extends Model
{
    use HasFactory;

    protected $casts = [
        'qty_used' => 'decimal:3',
    ];

    public function repackagingOrder()
    {
        return $this->belongsTo(RepackagingOrder::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
