<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCase extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_test' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function purchase() { return $this->belongsTo(PlanPurchase::class, 'anchor_plan_purchase_id'); }
    public function lots() { return $this->hasMany(FinanceCreditLot::class); }
    public function allocations() { return $this->hasMany(FinanceCreditAllocation::class); }
    public function events() { return $this->hasMany(FinanceCaseEvent::class)->orderByDesc('occurred_at')->orderByDesc('id'); }
}
