<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramProductDraft extends Model
{
    public const ACTIVE_STATES = [
        'awaiting_image',
        'awaiting_prompt',
        'awaiting_prompt_confirmation',
        'awaiting_prompt_continue',
        'awaiting_description',
        'processing',
        'review',
        'duplicate',
        'awaiting_edit',
        'awaiting_product_code',
        'awaiting_save_choice',
        'awaiting_explore_tiles',
        'awaiting_setting_prompt',
        // درفت شکست‌خورده هم «فعال» است تا مدیر بتواند تلاش مجدد کند یا متن تازه بفرستد.
        'failed',
        // مسیر پروداکتی (پک شات) در اتصال مستقیم
        'ps_images', 'ps_group', 'ps_occupations', 'ps_brief', 'ps_review', 'ps_edit',
        'ps_shots', 'ps_categories', 'ps_confirm', 'ps_tiles',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'telegram_product_manager_id',
        'telegram_id',
        'chat_id',
        'state',
        'product_mode',
        'pending_edit_field',
        'description',
        'image_paths',
        'image_file_ids',
        'ai_result',
        'input_payload',
        'message_ids',
        'last_message_id',
        'product_id',
        'error_message',
        'processing_started_at',
        'reviewed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'image_paths' => 'array',
        'image_file_ids' => 'array',
        'ai_result' => 'array',
        'input_payload' => 'array',
        'message_ids' => 'array',
        'processing_started_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(TelegramProductManager::class, 'telegram_product_manager_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public const MODE_PORTRAIT = 'portrait';
    public const MODE_PRODUCT = 'product';

    /** درفت مسیر پروداکتی (پک شات)؛ نبود ستون یا مقدار خالی یعنی پرتره. */
    public function isProductMode(): bool
    {
        return ($this->attributes['product_mode'] ?? self::MODE_PORTRAIT) === self::MODE_PRODUCT;
    }

    public function isActive(): bool
    {
        return in_array((string) $this->state, self::ACTIVE_STATES, true);
    }
}
