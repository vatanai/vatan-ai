<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $table = 'instagram_contacts';

    protected $fillable = [
        'workspace_id', 'external_id', 'username', 'display_name', 'phone', 'industry', 'city', 'language',
        'first_source', 'first_source_ref', 'lead_status', 'lead_score', 'score_reason', 'interests',
        'assigned_admin_id', 'consent_contact', 'opted_out', 'user_id', 'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'consent_contact' => 'boolean',
            'opted_out' => 'boolean',
            'last_interaction_at' => 'datetime',
            'lead_score' => 'integer',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'instagram_contact_tag', 'contact_id', 'tag_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function label(): string
    {
        return $this->display_name ?: ($this->username ? '@'.$this->username : 'مخاطب #'.$this->id);
    }

    public function initials(): string
    {
        $name = trim((string) ($this->display_name ?: $this->username ?: '؟'));

        return mb_strtoupper(mb_substr($name, 0, 1));
    }
}
