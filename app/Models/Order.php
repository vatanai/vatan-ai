<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'product_id', 'discount_id', 'status',
        'payment_status', 'processing_status', 'original_credits',
        'discount_credits', 'final_credits', 'refunded_credits', 'discount_code',
        'promotional_credits_used', 'paid_credits_used',
        'promotional_credits_refunded', 'paid_credits_refunded',
        'payment_reference', 'ai_model', 'ai_provider', 'queue_duration_ms',
        'processing_duration_ms', 'attempts', 'input_payload', 'output_payload',
        'error_message', 'admin_note', 'source', 'paid_at', 'processing_started_at',
        'completed_at', 'cancelled_at', 'refunded_at', 'plan_id', 'plan_name',
        'model_tier_key', 'model_tier_name',
    ];

    protected $casts = [
        'input_payload' => 'array', 'output_payload' => 'array',
        'paid_at' => 'datetime', 'processing_started_at' => 'datetime',
        'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (!$order->order_number) {
                do {
                    $number = 'ORD-' . now()->format('ymd') . '-' . random_int(1000, 9999);
                } while (static::where('order_number', $number)->exists());
                $order->order_number = $number;
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function discount(): BelongsTo { return $this->belongsTo(Discount::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function providerRequests(): HasMany { return $this->hasMany(AiProviderRequest::class); }
    public function generatedVideos(): HasMany { return $this->hasMany(GeneratedVideo::class); }
    public function events(): HasMany { return $this->hasMany(OrderEvent::class)->latest(); }
    public function financeSnapshot(): HasOne { return $this->hasOne(FinanceOrderSnapshot::class); }
    public function creditAllocations(): HasMany { return $this->hasMany(FinanceCreditAllocation::class); }
    public function financeEvents(): HasMany { return $this->hasMany(FinanceCaseEvent::class); }

    public function recordEvent(string $type, string $title, ?string $description = null, array $metadata = []): OrderEvent
    {
        return $this->events()->create([
            'admin_id' => auth('admin')->id(), 'type' => $type, 'title' => $title,
            'description' => $description, 'metadata' => $metadata ?: null,
        ]);
    }
}
