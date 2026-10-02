<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundMessage extends Model
{
    protected $table = 'instagram_outbound_messages';

    protected $fillable = [
        'workspace_id', 'channel_id', 'conversation_id', 'contact_id', 'kind', 'target_ref', 'body', 'origin', 'admin_id',
        'automation_run_id', 'ai_suggestion_id', 'allow_human_lock', 'status', 'policy_reason', 'window_expires_at', 'attempts',
        'next_attempt_at', 'external_id', 'error', 'provider_response', 'idempotency_key', 'message_id', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_human_lock' => 'boolean',
            'window_expires_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'provider_response' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
