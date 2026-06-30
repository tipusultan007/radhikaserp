<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'type',
        'amount',
        'payment_method',
        'investor_name',
        'reference',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_method');
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
