<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Info su ogni concorso (titolo, descrizione, stato, numero max partecipanti, premio)

class Contest extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'max_participants',
        'prize'
    ];

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Relazione per contare i voti totali del contest (attraverso le entries)
    public function votes()
    {
        return $this->hasManyThrough(Vote::class, Entry::class);
    }
}
