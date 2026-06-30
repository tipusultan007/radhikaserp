<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'invoice_no',
    'customer_id',
    'warehouse_id',
    'date',
    'subtotal',
    'discount',
    'total',
    'paid_amount',
    'due_amount',
    'payment_status',
    'created_by',
    'source',
    'is_promotional',
    'delivery_charge',
    'payment_method',
    'dispatched_by',
    'delivered_by',
    'dispatched_at',
    'delivered_at',
    'payment_details',
    'delivery_status',
    'estimate_delivery_date',
    'delivery_method',
    'consignment_id',
    'total_weight',
    'notes',
    'shipping_address'
])]
class Sale extends Model
{
    use HasFactory;

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'total_weight' => 'decimal:3',
        'date' => 'date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
    }
}
