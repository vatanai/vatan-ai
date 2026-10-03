<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** وضعیت جریان دایرکت هر کامنت: منتظر کلیک → منتظر فالو → تکمیل. */
class PostFlowSession extends Model
{
    protected $table = 'instagram_post_flow_sessions';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'contact_id', 'conversation_id', 'comment_message_id', 'automation_run_id', 'mode', 'stage',
        'follow_checks', 'follow_status', 'expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PostCampaign::class, 'campaign_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
