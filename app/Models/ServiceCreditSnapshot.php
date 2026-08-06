<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCreditSnapshot extends Model
{
    protected $fillable = [
        'service_credit_account_id', 'balance', 'currency', 'captured_at',
    ];

    protected $casts = [
        'balance' => 'decimal:6',
        'captured_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ServiceCreditAccount::class, 'service_credit_account_id');
    }
}
