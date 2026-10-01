<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPostKeyword extends Model
{
    protected $table = 'instagram_post_keywords';

    protected $fillable = [
        'post_setting_id',
        'keyword',
        'match_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function postSetting(): BelongsTo
    {
        return $this->belongsTo(InstagramPostSetting::class, 'post_setting_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function matches(string $text): bool
    {
        return match($this->match_type) {
            'exact' => strtolower($text) === strtolower($this->keyword),
            'contains' => str_contains(strtolower($text), strtolower($this->keyword)),
            'regex' => preg_match($this->keyword, $text) > 0,
            default => false,
        };
    }
}
