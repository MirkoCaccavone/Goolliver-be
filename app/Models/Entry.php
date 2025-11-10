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
        'processing_status',
        'moderation_score',
        'file_size',
        'mime_type',
        'dimensions',
        'metadata',
        'votes_count',
        'views_count'
    ];

    protected $casts = [
        'dimensions' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'moderation_score' => 'decimal:2',
        'votes_count' => 'integer',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
