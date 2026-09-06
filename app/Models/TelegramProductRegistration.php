<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramProductRegistration extends Model
{
    protected $fillable = ['product_id', 'telegram_product_manager_id', 'draft_id', 'telegram_id', 'input_prompt', 'ai_result', 'status'];

    protected $casts = ['telegram_id' => 'integer', 'ai_result' => 'array'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function manager(): BelongsTo { return $this->belongsTo(TelegramProductManager::class, 'telegram_product_manager_id'); }
    public function draft(): BelongsTo { return $this->belongsTo(TelegramProductDraft::class, 'draft_id'); }
}
