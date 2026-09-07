<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralConversion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'first_image_at' => 'datetime',
            'first_purchase_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'purchase_amount' => 'integer',
            'discount_amount' => 'integer',
            'commission_amount' => 'integer',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ReferralVisit::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class, 'conversion_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'link_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
