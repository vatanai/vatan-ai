<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** یک شات داخل یک ساخت پک؛ سفارش، اعتبار و خطای خودش را دارد. */
class ShotBatchItem extends Model
{
    public const MAX_ATTEMPTS = 3;

    protected $fillable = [
        'shot_batch_id', 'shot_id', 'shot_key', 'shot_name_fa', 'status', 'credits', 'credits_charged',
        'credits_refunded', 'order_id', 'generated_image_id', 'image_path', 'attempts', 'qc_retries', 'qc',
        'cost_usd', 'ai_model', 'error_message', 'prompt', 'sort', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'credits' => 'integer',
        'credits_charged' => 'integer',
        'credits_refunded' => 'integer',
        'attempts' => 'integer',
        'qc_retries' => 'integer',
        'qc' => 'array',
        'cost_usd' => 'float',
        'sort' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ShotBatch::class, 'shot_batch_id');
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(ShotLibrary::class, 'shot_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function generatedImage(): BelongsTo
    {
        return $this->belongsTo(GeneratedImage::class);
    }

    public function imageUrl(): ?string
    {
        return ShotLibrary::publicUrl($this->image_path);
    }

    public function canRun(): bool
    {
        return in_array($this->status, ['pending', 'failed'], true) && $this->attempts < self::MAX_ATTEMPTS;
    }

    public function toClientArray(): array
    {
        return [
            'id' => $this->id,
            'shot_key' => $this->shot_key,
            'name' => $this->shot_name_fa,
            'status' => $this->status,
            'credits' => $this->credits,
            'credits_charged' => $this->credits_charged,
            'credits_refunded' => $this->credits_refunded,
            'image_url' => $this->imageUrl(),
            'can_retry' => $this->status === 'failed' && $this->attempts < self::MAX_ATTEMPTS,
            'error' => $this->status === 'failed' ? ($this->error_message ?: 'ساخت این شات انجام نشد.') : null,
            'qc' => $this->qc ? ['passed' => (bool) ($this->qc['passed'] ?? true), 'score' => $this->qc['score'] ?? null] : null,
        ];
    }
}
