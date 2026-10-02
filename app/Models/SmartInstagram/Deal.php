<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    protected $table = 'instagram_deals';

    protected $fillable = [
        'workspace_id', 'contact_id', 'conversation_id', 'title', 'stage', 'value_toman', 'outcome', 'lost_reason',
        'source_type', 'source_ref', 'owner_admin_id', 'stage_changed_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['stage_changed_at' => 'datetime', 'closed_at' => 'datetime', 'value_toman' => 'integer'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'owner_admin_id');
    }
}
