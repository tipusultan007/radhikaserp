<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'customer_type', 'email', 'password', 'phone', 'address', 'district', 'company', 'credit_limit', 'total_due', 'opening_balance', 'wallet_balance'])]
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \App\Traits\LogsActivity;

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
