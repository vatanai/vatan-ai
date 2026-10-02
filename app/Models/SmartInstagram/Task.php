<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $table = 'instagram_tasks';

    protected $fillable = [
        'workspace_id', 'contact_id', 'conversation_id', 'deal_id', 'type', 'title', 'due_at', 'status',
        'assigned_admin_id', 'result', 'created_via', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_at !== null && $this->due_at->isPast();
    }
}
