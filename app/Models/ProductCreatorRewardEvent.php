<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCreatorRewardEvent extends Model
{
    protected $fillable = [
        'product_id',
        'owner_user_id',
        'consumer_user_id',
        'generation_id',
        'generated_image_id',
        'generated_video_id',
        'order_id',
        'event_key',
        'media_type',
        'credit_source',
        'reward_credits',
        'status',
        'metadata',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'owner_user_id' => 'integer',
        'consumer_user_id' => 'integer',
        'generation_id' => 'integer',
        'generated_image_id' => 'integer',
        'generated_video_id' => 'integer',
        'order_id' => 'integer',
        'reward_credits' => 'integer',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_user_id');
    }

    public function generatedImage(): BelongsTo
    {
        return $this->belongsTo(GeneratedImage::class);
    }

    public function generatedVideo(): BelongsTo
    {
        return $this->belongsTo(GeneratedVideo::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
