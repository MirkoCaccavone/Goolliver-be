<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Le foto inviate dagli utenti per un concorso

class Entry extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
