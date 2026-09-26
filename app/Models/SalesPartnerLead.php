<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesPartnerLead extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'handle',
        'channel',
        'profile_url',
        'source',
        'acquisition_source',
        'stage',
        'stage_changed_at',
        'status',
        'priority',
        'assigned_to',
        'last_contact_at',
        'next_follow_up_at',
        'last_contact_type',
        'last_contact_result',
        'last_contact_note',
        'contact_count',
        'reply_count',
        'positive_reply_count',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => 'integer',
            'stage_changed_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'contact_count' => 'integer',
            'reply_count' => 'integer',
            'positive_reply_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SalesPartnerActivity::class, 'sales_partner_lead_id');
    }

    public function getContactUrlAttribute(): ?string
    {
        if (filled($this->profile_url)) {
            return $this->profile_url;
        }

        $handle = ltrim(trim((string) $this->handle), '@');
        if ($handle === '') {
            return null;
        }

        return match ($this->channel) {
            'instagram' => "https://instagram.com/{$handle}",
            'telegram' => "https://t.me/{$handle}",
            default => null,
        };
    }
}
