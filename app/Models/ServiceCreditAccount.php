<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCreditAccount extends Model
{
    protected $fillable = [
        'name', 'slug', 'currency', 'manual_balance', 'low_balance_threshold',
        'critical_balance_threshold', 'show_on_dashboard', 'alerts_enabled', 'is_active',
        'sync_driver', 'last_synced_at', 'note',
    ];

    protected $casts = [
        'manual_balance' => 'decimal:6',
        'low_balance_threshold' => 'decimal:6',
        'critical_balance_threshold' => 'decimal:6',
        'show_on_dashboard' => 'boolean',
        'alerts_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(ServiceCreditTransaction::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ServiceCreditSnapshot::class, 'service_credit_account_id');
    }
}
