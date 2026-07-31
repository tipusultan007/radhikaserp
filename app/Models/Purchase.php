<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['purchase_no', 'supplier_id', 'warehouse_id', 'date', 'total_cost', 'purchase_type', 'delivery_cost', 'total_landed_cost', 'cost_breakdown'])]
class Purchase extends Model
{
    use HasFactory;
    use \App\Traits\LogsActivity;

    protected $casts = [
        'date' => 'date',
        'total_cost' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'total_landed_cost' => 'decimal:2',
        'cost_breakdown' => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
