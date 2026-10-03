<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** تنظیمات سناریوی یک پست؛ اجرای واقعی با AutomationRule متصل انجام می‌شود. */
class PostCampaign extends Model
{
    protected $table = 'instagram_post_campaigns';

    protected $fillable = [
        'workspace_id', 'post_id', 'automation_rule_id', 'title', 'status', 'follow_required', 'public_reply_enabled',
        'dm_enabled', 'settings', 'version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'follow_required' => 'boolean',
            'public_reply_enabled' => 'boolean',
            'dm_enabled' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(PostCampaignKeyword::class, 'campaign_id')->orderBy('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostCampaignVersion::class, 'campaign_id')->latest('version');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PostFlowSession::class, 'campaign_id');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
