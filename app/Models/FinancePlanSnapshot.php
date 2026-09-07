<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancePlanSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gross_sales_irr' => 'decimal:2',
            'received_irr' => 'decimal:2',
            'gateway_fee_irr' => 'decimal:2',
            'estimated_model_cost_irr' => 'decimal:2',
            'allocated_infrastructure_irr' => 'decimal:2',
            'allocated_workforce_irr' => 'decimal:2',
            'gross_profit_irr' => 'decimal:2',
            'net_profit_irr' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'gross_sales_toman' => 'decimal:2',
            'received_toman' => 'decimal:2',
            'gateway_fee_toman' => 'decimal:2',
            'estimated_model_cost_toman' => 'decimal:2',
            'allocated_infrastructure_toman' => 'decimal:2',
            'allocated_workforce_toman' => 'decimal:2',
            'gross_profit_toman' => 'decimal:2',
            'net_profit_toman' => 'decimal:2',
            'source_snapshot' => 'array',
            'purchased_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }
}
