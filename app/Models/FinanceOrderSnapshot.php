<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceOrderSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fallback_models' => 'array',
            'source_snapshot' => 'array',
            'estimated_cost_usd' => 'decimal:6',
            'actual_cost_usd' => 'decimal:6',
            'exchange_rate_irr' => 'decimal:4',
            'estimated_cost_irr' => 'decimal:2',
            'actual_cost_irr' => 'decimal:2',
            'direct_cost_irr' => 'decimal:2',
            'allocated_revenue_irr' => 'decimal:2',
            'allocated_infrastructure_irr' => 'decimal:2',
            'allocated_workforce_irr' => 'decimal:2',
            'gross_profit_irr' => 'decimal:2',
            'net_profit_irr' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'exchange_rate_toman' => 'decimal:4',
            'estimated_cost_toman' => 'decimal:2',
            'actual_cost_toman' => 'decimal:2',
            'direct_cost_toman' => 'decimal:2',
            'allocated_revenue_toman' => 'decimal:2',
            'allocated_infrastructure_toman' => 'decimal:2',
            'allocated_workforce_toman' => 'decimal:2',
            'gross_profit_toman' => 'decimal:2',
            'net_profit_toman' => 'decimal:2',
            'ordered_at' => 'datetime',
            'completed_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }
}
