<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// I voti che gli utenti danno alle foto

class Vote extends Model
{
    use HasFactory;

    // Tipi di voto disponibili
    const TYPE_LIKE = 'like';

    protected $fillable = [
        'user_id',
        'entry_id',
        'vote_type',
        'ip_address',
        'user_agent',
    ];

    // Relazioni
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    // Scope per tipi di voto
    public function scopeLikes($query)
    {
        return $query->where('vote_type', self::TYPE_LIKE);
    }
}
