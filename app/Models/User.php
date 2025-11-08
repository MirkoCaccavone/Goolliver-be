<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * I campi assegnabili in massa
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',        // nuovo campo facoltativo
        'provider',     // login tramite social (es. google, facebook)
        'provider_id',  // ID univoco del provider
    ];

    /**
     * I campi nascosti nelle risposte JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipi di cast automatici
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relazioni
     */
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
