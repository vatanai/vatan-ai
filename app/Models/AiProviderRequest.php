<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function aiModel()
    {
        return $this->belongsTo(AiModel::class);
    }
}
