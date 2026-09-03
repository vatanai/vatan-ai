<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCaseEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:6',
            'amount_toman' => 'decimal:2',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function financeCase() { return $this->belongsTo(FinanceCase::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function purchase() { return $this->belongsTo(PlanPurchase::class, 'plan_purchase_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function tokenLog() { return $this->belongsTo(TokenLog::class); }
}
