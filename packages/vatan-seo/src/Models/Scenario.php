<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends SeoModel
{
    protected $table = 'seo_scenarios';

    public const FREQUENCIES = ['daily' => 'روزانه', 'twice_weekly' => 'دو بار در هفته', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه'];
    public const WEEKDAYS = [6 => 'شنبه', 0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_enabled' => 'boolean',
            'follow_profile' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function runs(): HasMany { return $this->hasMany(Run::class); }
}
