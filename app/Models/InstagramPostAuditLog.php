<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPostAuditLog extends Model
{
    protected $table = 'instagram_post_audit_logs';

    protected $fillable = [
        'post_setting_id',
        'instagram_comment_id',
        'instagram_user_id',
        'instagram_username',
        'action',
        'details',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'details' => 'json',
    ];

    public function postSetting(): BelongsTo
    {
        return $this->belongsTo(InstagramPostSetting::class, 'post_setting_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(InstagramComment::class, 'instagram_comment_id');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('created_at');
    }
}
