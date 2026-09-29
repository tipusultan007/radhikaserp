<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'customer_target_scheme_id',
    'customer_id',
    'achieved_qty',
    'target_qty',
    'is_target_met',
    'bonus_amount',
    'status',
    'disbursed_at',
    'disbursed_by',
    'journal_id',
    'notes'
])]
class CustomerBonus extends Model
{
    use HasFactory;
    use \App\Traits\LogsActivity;

    protected $casts = [
        'achieved_qty' => 'decimal:2',
        'target_qty' => 'decimal:2',
        'is_target_met' => 'boolean',
        'bonus_amount' => 'decimal:2',
        'disbursed_at' => 'datetime',
    ];

    public function scheme()
    {
        return $this->belongsTo(CustomerTargetScheme::class, 'customer_target_scheme_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function disburser()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}

