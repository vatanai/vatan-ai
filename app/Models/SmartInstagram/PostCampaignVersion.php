<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCampaignVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'instagram_post_campaign_versions';

    protected $fillable = ['campaign_id', 'version', 'snapshot', 'admin_id', 'note'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
