<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'instagram_conversations';

    protected $fillable = [
        'workspace_id', 'channel_id', 'contact_id', 'status', 'priority', 'last_source', 'assigned_admin_id',
        'ai_paused', 'needs_human', 'unread_count', 'last_message_preview', 'last_message_direction',
        'last_message_at', 'last_inbound_at', 'last_outbound_at', 'first_response_at', 'first_response_seconds',
        'intent', 'stage', 'urgency', 'summary', 'next_action', 'summary_updated_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_paused' => 'boolean',
            'needs_human' => 'boolean',
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'first_response_at' => 'datetime',
            'summary_updated_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    public function pendingSuggestion(): ?AiSuggestion
    {
        return $this->suggestions()->where('status', 'pending')->latest('id')->first();
    }

    /** فیلترهای صندوق گفتگو (پروپوزال ۵.۲). */
    public function scopeInboxFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'new' => $query->where('status', 'new'),
            'unanswered' => $query->whereIn('status', ['new', 'unanswered']),
            'waiting' => $query->where('status', 'waiting_customer'),
            'assigned' => $query->whereNotNull('assigned_admin_id')->where('status', '!=', 'closed'),
            'closed' => $query->where('status', 'closed'),
            'attention' => $query->where('needs_human', true)->where('status', '!=', 'closed'),
            'ai' => $query->whereHas('suggestions', fn (Builder $q) => $q->where('status', 'pending')),
            default => $query->where('status', '!=', 'closed'),
        };
    }

    public function windowOpen(): bool
    {
        return $this->last_inbound_at !== null
            && $this->last_inbound_at->gt(now()->subHours((int) config('smart_instagram.policy.dm_window_hours', 24)));
    }
}
