<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** پست/ریلز اینستاگرام همگام‌شده (کاور روی دیسک عمومی ذخیره می‌شود تا برای کارت دایرکت هم قابل استفاده باشد). */
class Post extends Model
{
    protected $table = 'instagram_posts';

    protected $fillable = [
        'workspace_id', 'channel_id', 'media_id', 'shortcode', 'permalink', 'media_type', 'product_type', 'caption',
        'cover_path', 'cover_source_url', 'published_at', 'like_count', 'comments_count', 'saved_count', 'shares_count',
        'reach_count', 'stats_synced_at', 'source', 'connection_status', 'sync_error',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'stats_synced_at' => 'datetime', 'telegram_notified_at' => 'datetime'];
    }

    public function campaign(): HasOne
    {
        return $this->hasOne(PostCampaign::class, 'post_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(PostStatDaily::class, 'post_id');
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : ($this->cover_source_url ?: null);
    }

    public function kindLabel(): string
    {
        if ($this->product_type === 'REELS' || ($this->media_type === 'VIDEO' && $this->product_type !== 'STORY')) {
            return 'ریلز';
        }

        return match (true) {
            $this->product_type === 'STORY' => 'استوری',
            $this->media_type === 'CAROUSEL_ALBUM' => 'کاروسل',
            default => 'پست',
        };
    }

    public function kindIcon(): string
    {
        return match ($this->kindLabel()) {
            'ریلز' => 'fa-clapperboard',
            'استوری' => 'fa-circle-notch',
            'کاروسل' => 'fa-clone',
            default => 'fa-image',
        };
    }

    public function shortCaption(int $limit = 90): string
    {
        $caption = trim(preg_replace('/\s+/u', ' ', (string) $this->caption) ?? '');

        return $caption !== '' ? Str::limit($caption, $limit) : 'بدون کپشن';
    }

    public function isVerified(): bool
    {
        return $this->connection_status === 'verified';
    }
}
