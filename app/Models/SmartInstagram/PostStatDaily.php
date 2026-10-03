<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;

class PostStatDaily extends Model
{
    protected $table = 'instagram_post_stats_daily';

    protected $fillable = ['post_id', 'day', 'like_count', 'comments_count', 'saved_count', 'shares_count'];
}
