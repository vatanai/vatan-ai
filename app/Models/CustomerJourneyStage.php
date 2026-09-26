<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerJourneyStage extends Model
{
    protected $primaryKey = 'point';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'point', 'short', 'title', 'description', 'icon', 'task_title',
        'task_body', 'channel', 'message_template', 'delay_minutes',
        'human_required', 'advance_rule', 'stop_rule', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'point' => 'integer',
            'delay_minutes' => 'integer',
            'human_required' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
