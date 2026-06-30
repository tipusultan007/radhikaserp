<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['sale_id', 'amount', 'method', 'date', 'reference'])]
class SalePayment extends Model
{
    use HasFactory;

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
