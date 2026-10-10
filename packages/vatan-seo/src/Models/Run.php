<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Run extends SeoModel
{
    protected $table = 'seo_runs';

    public const AGENTS = [
        'strategist' => 'استراتژیست',
        'auditor' => 'حسابرس فنی',
        'researcher' => 'پژوهشگر کلمات',
        'writer' => 'نویسنده',
        'monitor' => 'ناظر رتبه',
        'reporter' => 'گزارشگر',
        'publisher' => 'ناشر',
        'system' => 'سیستم',
    ];

    protected function casts(): array
    {
        return ['output' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'cost_usd' => 'float'];
    }

    public function scenario(): BelongsTo { return $this->belongsTo(Scenario::class); }
    public function task(): BelongsTo { return $this->belongsTo(Task::class); }
}
