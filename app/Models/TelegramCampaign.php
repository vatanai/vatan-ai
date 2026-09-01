<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramCampaign extends Model
{
    protected $fillable = [
        'created_by', 'name', 'segment_definition', 'status', 'body', 'media_type', 'media_file_id',
        'buttons', 'scheduled_at', 'sent_at', 'recipient_count', 'sent_count', 'failed_count',
    ];

    protected $casts = [
        'segment_definition' => 'array',
        'buttons' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TelegramCampaignLog::class, 'campaign_id');
    }
}
