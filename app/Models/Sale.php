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
    'shipping_address',
    'delivery_type'
])]
class Sale extends Model
{
    use HasFactory;
    use \App\Traits\LogsActivity;

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'total_weight' => 'decimal:3',
        'date' => 'date',
        'tracking_updates' => 'array'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function activities()
    {
        return $this->morphMany(\App\Models\ActivityLog::class, 'reference')->latest();
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

    /**
     * Generate the next daily invoice number (INV-YYYYMMDDXXX)
     *
     * @param string|\DateTimeInterface|null $date
     * @return string
     */
    public static function generateInvoiceNo($date = null): string
    {
        $date = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::today();
        $dateStr = $date->format('Ymd');
        $prefix = 'INV-' . $dateStr;

        // Query only the single latest invoice record for this day (O(1) memory)
        $lastInvoice = self::where('invoice_no', 'like', 'INV-' . $dateStr . '%')
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->lockForUpdate()
            ->value('invoice_no');

        $nextSerial = 1;
        if ($lastInvoice && preg_match('/INV-' . $dateStr . '-?(\d+)/i', $lastInvoice, $matches)) {
            $nextSerial = ((int) $matches[1]) + 1;
        }

        do {
            $invoiceNo = $prefix . str_pad($nextSerial, 3, '0', STR_PAD_LEFT);
            $nextSerial++;
        } while (self::where('invoice_no', $invoiceNo)->exists());

        return $invoiceNo;
    }
}
