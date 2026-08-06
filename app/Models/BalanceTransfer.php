<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'date',
        'from_account_id',
        'to_account_id',
        'amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'to_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal()
    {
        return $this->morphOne(Journal::class, 'reference');
    }
}
