<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['journal_no', 'date', 'reference_type', 'reference_id', 'notes', 'created_by'])]
class Journal extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
