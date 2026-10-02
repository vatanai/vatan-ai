<?php

namespace App\Models\SmartInstagram;

use App\Models\MarketingIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $table = 'instagram_channels';

    protected $fillable = [
        'workspace_id', 'marketing_integration_id', 'gateway', 'name', 'external_account_id', 'username', 'account_type',
        'status', 'outbound_enabled', 'last_event_at', 'health_checked_at', 'token_expires_at', 'last_error', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'outbound_enabled' => 'boolean',
            'last_event_at' => 'datetime',
            'health_checked_at' => 'datetime',
            'token_expires_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(MarketingIntegration::class, 'marketing_integration_id');
    }

    public function isHealthy(): bool
    {
        return $this->status === 'connected';
    }
}
