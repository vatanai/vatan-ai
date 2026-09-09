<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['deactivated_at' => 'datetime'];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ReferralVisit::class, 'link_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ReferralConversion::class, 'link_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deactivated_at === null;
    }
}
