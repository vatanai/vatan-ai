<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCreditAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'revenue_toman' => 'decimal:2',
            'occurred_at' => 'datetime',
            'settled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lot() { return $this->belongsTo(FinanceCreditLot::class, 'finance_credit_lot_id'); }
    public function financeCase() { return $this->belongsTo(FinanceCase::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
