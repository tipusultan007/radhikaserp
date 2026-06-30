<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['repackaging_order_id', 'type', 'qty', 'reason'])]
class RepackagingAdjustment extends Model
{
    use HasFactory;

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function repackagingOrder()
    {
        return $this->belongsTo(RepackagingOrder::class);
    }
}
