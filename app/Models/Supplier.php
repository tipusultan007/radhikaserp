<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'phone', 'address', 'country', 'total_payable'])]
class Supplier extends Model
{
    use HasFactory;

    protected $casts = [
        'total_payable' => 'decimal:2',
    ];
}
