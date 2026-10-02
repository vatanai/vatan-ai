<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $table = 'instagram_message_attachments';

    protected $fillable = [
        'workspace_id', 'message_id', 'type', 'remote_url', 'storage_path', 'mime', 'size_bytes', 'fetch_status',
        'fetch_attempts', 'fetch_error', 'transcript', 'transcript_language', 'transcript_confidence', 'transcript_status',
    ];

    protected $hidden = ['remote_url'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
