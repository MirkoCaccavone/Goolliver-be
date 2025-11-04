<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// I pagamenti effettuati per partecipare

class Transaction extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }
}
