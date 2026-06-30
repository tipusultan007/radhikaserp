<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'address', 'credit_limit', 'total_due', 'opening_balance', 'wallet_balance'])]
class Customer extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'total_due' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
