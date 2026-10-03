<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;

class PostCampaignKeyword extends Model
{
    protected $table = 'instagram_post_campaign_keywords';

    protected $fillable = ['campaign_id', 'keyword', 'normalized', 'match_mode', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
