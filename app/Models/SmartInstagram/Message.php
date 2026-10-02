<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'instagram_messages';

    protected $fillable = [
        'workspace_id', 'conversation_id', 'external_id', 'direction', 'source_type', 'source_ref', 'parent_external_id',
        'message_type', 'body', 'sent_by', 'admin_id', 'is_internal_note', 'delivery_status', 'marketing_event_id', 'meta', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'in';
    }
}
