<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'expense_category_id',
    'warehouse_id',
    'amount',
    'notes',
    'date',
    'reference_type',
    'reference_id',
    'created_by',
    'payment_method_id'
])]
class Expense extends Model
{
    use HasFactory;

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_method_id');
    }
}
