<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCreditAccount extends Model
{
    protected $fillable = [
        'name', 'slug', 'currency', 'manual_balance', 'low_balance_threshold',
        'show_on_dashboard', 'is_active', 'sync_driver', 'last_synced_at', 'note',
    ];

    protected $casts = [
        'manual_balance' => 'decimal:6',
        'low_balance_threshold' => 'decimal:6',
        'show_on_dashboard' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(ServiceCreditTransaction::class);
    }
}
