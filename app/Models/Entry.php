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
        'photo_url',
        'thumbnail_url',
        'caption',
        'title',
        'description',
        'location',
        'camera_model',
        'settings',
        'tags',
        'moderation_status',
        'payment_status',
        'paid_at',
        'payment_amount',
        'payment_method',
        'transaction_id',
        'processing_status',
        'moderation_score',
        'file_size',
        'mime_type',
        'dimensions',
        'metadata',
        'votes_count',
        'views_count',
        'likes_count',
        'vote_score',
        'credit_given',
        'expires_at'
    ];

    protected $casts = [
        'dimensions' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'moderation_score' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'votes_count' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'vote_score' => 'decimal:2',
        'credit_given' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime'
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

    // Relazioni specifiche per tipo di voto
    public function likes()
    {
        return $this->hasMany(Vote::class)->where('vote_type', Vote::TYPE_LIKE);
    }



    // Helper methods per i voti
    public function hasUserVoted($userId, $voteType = Vote::TYPE_LIKE)
    {
        return $this->votes()
            ->where('user_id', $userId)
            ->where('vote_type', $voteType)
            ->exists();
    }

    public function getUserVote($userId, $voteType = Vote::TYPE_LIKE)
    {
        return $this->votes()
            ->where('user_id', $userId)
            ->where('vote_type', $voteType)
            ->first();
    }

    // Scope per filtrare la visibilità
    public function scopeVisible($query)
    {
        return $query->whereIn('moderation_status', ['approved', 'pending']);
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    public function scopePublic($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    // Scope per ranking
    public function scopeOrderByVoteScore($query, $direction = 'desc')
    {
        return $query->orderBy('vote_score', $direction);
    }

    public function scopeOrderByLikes($query, $direction = 'desc')
    {
        return $query->orderBy('likes_count', $direction);
    }

    // Accessors per URL completi
    public function getPhotoUrlAttribute($value)
    {
        if (!$value) return null;

        // Se è già un URL completo, restituiscilo così com'è
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Altrimenti costruisci l'URL completo
        return asset('storage/photos/' . $value);
    }

    public function getThumbnailUrlAttribute($value)
    {
        if (!$value) return null;

        // Se è già un URL completo, restituiscilo così com'è
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Altrimenti costruisci l'URL completo
        return asset('storage/photos/thumbnails/' . $value);
    }
}
