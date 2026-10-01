<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPostResponse extends Model
{
    protected $table = 'instagram_post_responses';

    protected $fillable = [
        'post_setting_id',
        'scenario',
        'target',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function postSetting(): BelongsTo
    {
        return $this->belongsTo(InstagramPostSetting::class, 'post_setting_id');
    }

    public function scopeForScenario($query, string $scenario)
    {
        return $query->where('scenario', $scenario);
    }

    public function scopeForTarget($query, string $target)
    {
        return $query->where('target', $target);
    }
}
