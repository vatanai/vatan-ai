<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    protected $table = 'instagram_ai_runs';

    protected $fillable = [
        'workspace_id', 'conversation_id', 'message_id', 'purpose', 'model', 'profile_version', 'status', 'input_summary',
        'output', 'confidence', 'prompt_tokens', 'completion_tokens', 'cost_usd', 'duration_ms', 'error',
    ];

    protected function casts(): array
    {
        return ['input_summary' => 'array', 'output' => 'array', 'confidence' => 'float'];
    }
}
