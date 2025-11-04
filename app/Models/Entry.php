<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Le foto inviate dagli utenti per un concorso

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contest_id',
        'image_url',
        'caption',
    ];

    // Relazioni

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
