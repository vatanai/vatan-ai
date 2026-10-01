<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramPostSetting extends Model
{
    protected $table = 'instagram_post_settings';

    protected $fillable = [
        'user_id',
        'title',
        'instagram_post_id',
        'instagram_caption',
        'status',
        'started_at',
        'ended_at',
        'require_follow',
        'min_followers',
        'non_follower_response',
        'follower_response',
        'comment_reply_delay',
        'dm_product_delay',
        'dm_form_delay',
        'repeat_policy',
        'log_all_interactions',
        'notify_slack',
        'daily_excel_export',
        'metadata',
    ];

    protected $casts = [
        'non_follower_response' => 'json',
        'follower_response' => 'json',
        'metadata' => 'json',
        'require_follow' => 'boolean',
        'log_all_interactions' => 'boolean',
        'notify_slack' => 'boolean',
        'daily_excel_export' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(InstagramPostKeyword::class, 'post_setting_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InstagramPostResponse::class, 'post_setting_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(InstagramPostProduct::class, 'post_setting_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(InstagramPostAnalytics::class, 'post_setting_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(InstagramPostAuditLog::class, 'post_setting_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTesting($query)
    {
        return $query->where('status', 'testing');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', '!=', 'inactive')
            ->where(function ($q) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>', now());
            });
    }

    // Methods
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'ended_at' => now(),
        ]);
    }

    public function toTesting(): void
    {
        $this->update(['status' => 'testing']);
    }

    public function getActiveKeywords()
    {
        return $this->keywords()->where('is_active', true)->get();
    }

    public function getActiveProducts()
    {
        return $this->products()->where('is_active', true)->get();
    }

    public function getTodayAnalytics()
    {
        return $this->analytics()
            ->whereDate('date', today())
            ->first();
    }

    public function getFollowerResponses()
    {
        return $this->responses()
            ->where('scenario', 'follower')
            ->get()
            ->groupBy('target');
    }

    public function getNonFollowerResponses()
    {
        return $this->responses()
            ->where('scenario', 'non_follower')
            ->get()
            ->groupBy('target');
    }
}
