<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoStudioSetting extends Model
{
    protected $fillable = [
        'product_id', 'source_mode', 'source_url', 'auto_enabled', 'approval_required',
        'auto_generate_hook', 'auto_generate_caption', 'auto_generate_keyword',
        'hook_guidelines', 'caption_guidelines', 'keyword', 'dm_template',
        'hook_text', 'caption_text', 'telegram_caption_text', 'prompt_profile',
        'instagram_prompt', 'telegram_prompt',
        'telegram_buttons',
        'font_family', 'aspect_ratio',
    ];

    protected $casts = [
        'auto_enabled' => 'boolean',
        'approval_required' => 'boolean',
        'auto_generate_hook' => 'boolean',
        'auto_generate_caption' => 'boolean',
        'auto_generate_keyword' => 'boolean',
        'telegram_buttons' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
