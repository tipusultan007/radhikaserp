<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['ref_no', 'warehouse_id', 'date', 'created_by', 'notes'])]
class RepackagingOrder extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inputs()
    {
        return $this->hasMany(RepackagingInput::class);
    }

    public function outputs()
    {
        return $this->hasMany(RepackagingOutput::class);
    }

    public function adjustments()
    {
        return $this->hasMany(RepackagingAdjustment::class);
    }
}
