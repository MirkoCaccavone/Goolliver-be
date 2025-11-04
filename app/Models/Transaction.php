<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// I pagamenti effettuati per partecipare

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contest_id',
        'amount',
        'payment_method',
        'status',
        'transaction_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }
}
