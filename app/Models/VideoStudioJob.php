<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoStudioJob extends Model
{
    protected $fillable = [
        'product_id', 'admin_id', 'source_mode', 'source_url', 'selected_images', 'aspect_ratio',
        'hook_text', 'caption_text', 'keyword', 'dm_template', 'status', 'n8n_execution_id',
        'video_url', 'error_message', 'payload', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'selected_images' => 'array',
        'payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }

    public function shortCode(): string
    {
        return 'P' . strtoupper(base_convert((string) $this->getKey(), 10, 36));
    }
}
