<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Info su ogni concorso (titolo, descrizione, stato, numero max partecipanti, premio)

class Contest extends Model
{
    protected $fillable = [
        'id',
        'title',
        'description',
        'category',
        'status',
        'start_date',
        'end_date',
        'max_participants',
        'current_participants',
        'prize',
        'entry_fee'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'entry_fee' => 'decimal:2',
        'current_participants' => 'integer',
        'max_participants' => 'integer',
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

    /**
     * Check if contest is currently active
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->status === 'active' &&
            $this->start_date <= $now &&
            $this->end_date >= $now &&
            $this->current_participants < $this->max_participants;
    }

    /**
     * Check if user can participate in this contest
     */
    public function canUserParticipate($user): bool
    {
        if (!$user || !$this->isActive()) {
            return false;
        }

        // Check if user already has a completed entry for this contest
        $completedEntry = $this->entries()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->exists();

        return !$completedEntry;
    }
}
