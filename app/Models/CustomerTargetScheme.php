<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'name',
    'target_month',
    'start_date',
    'end_date',
    'description',
    'status',
    'created_by'
])]
class CustomerTargetScheme extends Model
{
    use HasFactory;
    use \App\Traits\LogsActivity;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(CustomerTargetItem::class, 'customer_target_scheme_id');
    }

    public function bonuses()
    {
        return $this->hasMany(CustomerBonus::class, 'customer_target_scheme_id');
    }
}

