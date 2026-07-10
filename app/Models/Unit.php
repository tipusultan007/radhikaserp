<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'short_name', 'parent_id', 'multiplier', 'status'])]
class Unit extends Model
{
    use HasFactory;

    protected $casts = [
        'multiplier' => 'decimal:4',
        'status' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }
}
