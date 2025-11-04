<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// I voti che gli utenti danno alle foto

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
}
