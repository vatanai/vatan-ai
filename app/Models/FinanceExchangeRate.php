<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceExchangeRate extends Model
{
    protected $fillable = [
        'currency', 'rate_date', 'capture_slot', 'captured_at', 'rate_to_irr', 'rate_to_toman', 'source', 'is_manual', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
            'captured_at' => 'datetime',
            'rate_to_irr' => 'decimal:4',
            'rate_to_toman' => 'decimal:4',
            'is_manual' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
