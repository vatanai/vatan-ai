<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCreditLot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'revenue_toman' => 'decimal:2',
            'is_promotional' => 'boolean',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function financeCase() { return $this->belongsTo(FinanceCase::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function purchase() { return $this->belongsTo(PlanPurchase::class, 'plan_purchase_id'); }
    public function tokenLog() { return $this->belongsTo(TokenLog::class); }
    public function allocations() { return $this->hasMany(FinanceCreditAllocation::class); }
}
