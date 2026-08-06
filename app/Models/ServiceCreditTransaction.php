<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCreditTransaction extends Model
{
    protected $fillable = [
        'service_credit_account_id', 'admin_id', 'type', 'amount',
        'occurred_at', 'reference', 'note',
    ];

    protected $casts = ['amount' => 'decimal:6', 'occurred_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ServiceCreditAccount::class, 'service_credit_account_id');
    }
}
