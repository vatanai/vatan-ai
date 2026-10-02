<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSuggestion extends Model
{
    protected $table = 'instagram_ai_suggestions';

    protected $fillable = [
        'workspace_id', 'conversation_id', 'ai_run_id', 'body', 'reason', 'source_ids', 'confidence', 'needs_human',
        'flags', 'status', 'final_body', 'reviewed_by', 'reviewed_at', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'source_ids' => 'array',
            'flags' => 'array',
            'needs_human' => 'boolean',
            'confidence' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
