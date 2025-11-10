<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read_at',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relazione con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope per notifiche non lette
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope per notifiche lette
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope per tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Segna come letta
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Segna come non letta
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Verifica se è letta
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Verifica se è non letta
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }
}
