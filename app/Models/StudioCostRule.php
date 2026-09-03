<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioCostRule extends Model
{
    protected $fillable = [
        'product_id', 'ai_model_id', 'media_type', 'provider', 'resolution',
        'aspect_ratio', 'duration_seconds', 'base_cost_usd', 'exchange_rate_toman',
        'cost_toman', 'profit_type', 'profit_value', 'credit_cost', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_cost_usd' => 'decimal:6',
            'exchange_rate_toman' => 'decimal:4',
            'cost_toman' => 'decimal:2',
            'profit_value' => 'decimal:4',
            'duration_seconds' => 'integer',
            'credit_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function aiModel(): BelongsTo { return $this->belongsTo(AiModel::class); }
}
