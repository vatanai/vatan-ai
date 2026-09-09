<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPurchaseFollowUp extends Model
{
    protected $fillable = [
        'plan_purchase_id',
        'admin_id',
        'task_key',
        'task_label',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function planPurchase(): BelongsTo
    {
        return $this->belongsTo(PlanPurchase::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
