<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'user_id',
        'payment_type',
        'payment_month',
        'payment_date',
        'amount',
        'payment_method_id',
        'notes',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_method_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match ($this->payment_type) {
            'full'    => 'Full Salary',
            'partial' => 'Partial Payment',
            'advance' => 'Advance',
            default   => ucfirst($this->payment_type),
        };
    }

    public function getPaymentTypeBadgeAttribute(): string
    {
        return match ($this->payment_type) {
            'full'    => 'success',
            'partial' => 'warning',
            'advance' => 'info',
            default   => 'secondary',
        };
    }
}
