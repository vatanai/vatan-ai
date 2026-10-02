<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProfile extends Model
{
    protected $table = 'instagram_ai_profiles';

    protected $fillable = [
        'workspace_id', 'version', 'is_active', 'assistant_name', 'persona_prompt', 'tone', 'reply_length', 'bot_disclosure',
        'forbidden_phrases', 'escalation_keywords', 'min_confidence', 'model', 'reply_mode', 'change_note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'forbidden_phrases' => 'array',
            'escalation_keywords' => 'array',
            'min_confidence' => 'float',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
