<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPartnerActivity extends Model
{
    protected $fillable = [
        'sales_partner_lead_id',
        'admin_id',
        'contact_type',
        'result',
        'note',
        'contacted_at',
        'next_follow_up_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(SalesPartnerLead::class, 'sales_partner_lead_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
