<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Info su ogni concorso (titolo, descrizione, stato, numero max partecipanti, premio)

class Contest extends Model
{
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
