<?php

namespace App\Models;

use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedVideo extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'ai_provider_request_id',
        'external_request_id',
        'status',
        'video_path',
        'video_url',
        'poster_path',
        'user_prompt',
        'input_payload',
        'credit_reservation',
        'duration_seconds',
        'width',
        'height',
        'mime_type',
        'size',
        'cost',
        'error_message',
        'credits_settled_at',
        'credits_restored_at',
        'completed_at',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'credit_reservation' => 'array',
        'duration_seconds' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
        'cost' => 'decimal:6',
        'credits_settled_at' => 'datetime',
        'credits_restored_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function providerRequest(): BelongsTo { return $this->belongsTo(AiProviderRequest::class, 'ai_provider_request_id'); }

    public function getJalaliCreatedAtAttribute(): string
    {
        return Jalali::format($this->created_at);
    }

    public function playbackUrl(): ?string
    {
        if ($this->video_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->video_path)) {
            return asset('storage/' . $this->video_path);
        }

        return $this->video_url ?: null;
    }
}
