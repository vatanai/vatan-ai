<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramComment extends Model
{
    protected $fillable = [
        'user_id',
        'instagram_comment_id',
        'instagram_user_id',
        'instagram_username',
        'instagram_media_id',
        'comment_text',
        'contains_keyword',
        'user_is_following',
        'ai_response',
        'status',
        'failure_reason',
        'retry_count',
        'metadata',
        'checked_at',
        'sent_at',
    ];

    protected $casts = [
        'contains_keyword' => 'boolean',
        'user_is_following' => 'boolean',
        'retry_count' => 'integer',
        'metadata' => 'array',
        'checked_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: pending comments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: sent comments
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: failed comments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: keyword matches
     */
    public function scopeWithKeyword($query)
    {
        return $query->where('contains_keyword', true);
    }

    /**
     * Scope: followers only
     */
    public function scopeFollowers($query)
    {
        return $query->where('user_is_following', true);
    }

    /**
     * Get all comments for a specific Instagram media
     */
    public function scopeForMedia($query, string $mediaId)
    {
        return $query->where('instagram_media_id', $mediaId);
    }

    /**
     * Get comments from specific Instagram user
     */
    public function scopeFromUser($query, string $instagramUserId)
    {
        return $query->where('instagram_user_id', $instagramUserId);
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}
