<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiProviderRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'output_urls' => 'array',
        'raw_response' => 'array',
        'estimated_cost_usd' => 'decimal:6',
        'actual_cost_usd' => 'decimal:6',
        'submitted_at' => 'datetime',
        'webhook_received_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiProviderRequest $request): void {
            if ($request->order_id && ($request->isDirty('order_id') || ! $request->build_uuid)) {
                $request->build_uuid = Order::query()->whereKey($request->order_id)->value('build_uuid');
            }
            $request->build_uuid ??= (string) Str::uuid();
        });
    }

    public function aiModel()
    {
        return $this->belongsTo(AiModel::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function generatedVideo()
    {
        return $this->hasOne(GeneratedVideo::class);
    }
}
