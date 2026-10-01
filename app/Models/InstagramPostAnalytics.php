<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPostAnalytics extends Model
{
    protected $table = 'instagram_post_analytics';

    protected $fillable = [
        'post_setting_id',
        'total_comments',
        'keyword_matched',
        'follow_required_users',
        'follow_completed',
        'dm_sent',
        'product_offered',
        'forms_completed',
        'conversions',
        'engagement_rate',
        'follow_completion_rate',
        'conversion_rate',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'engagement_rate' => 'decimal:2',
        'follow_completion_rate' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    public function postSetting(): BelongsTo
    {
        return $this->belongsTo(InstagramPostSetting::class, 'post_setting_id');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function updateRates(): void
    {
        if ($this->total_comments > 0) {
            $this->engagement_rate = ($this->keyword_matched / $this->total_comments) * 100;
        }

        if ($this->follow_required_users > 0) {
            $this->follow_completion_rate = ($this->follow_completed / $this->follow_required_users) * 100;
        }

        if ($this->dm_sent > 0) {
            $this->conversion_rate = ($this->conversions / $this->dm_sent) * 100;
        }

        $this->save();
    }
}
