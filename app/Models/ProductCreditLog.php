<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCreditLog extends Model
{
    protected $fillable = [
        'product_id',
        'admin_id',
        'action',
        'amount',
        'credit_before',
        'credit_after',
        'note',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'integer',
        'credit_before' => 'integer',
        'credit_after' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
