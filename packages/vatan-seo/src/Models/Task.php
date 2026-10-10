<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends SeoModel
{
    protected $table = 'seo_tasks';

    public const STATUSES = [
        'todo' => 'در صف',
        'in_progress' => 'در حال انجام',
        'needs_action' => 'نیاز به اقدام',
        'waiting_approval' => 'منتظر تأیید',
        'done' => 'انجام شد',
        'skipped' => 'رد شد',
        'failed' => 'خطا',
    ];
    public const PILLARS = ['infrastructure' => 'زیرساخت', 'goals' => 'اهداف کلمات کلیدی'];
    public const AUTOMATION = ['auto' => 'خودکار', 'assisted' => 'نیمه‌خودکار', 'manual' => 'دستی'];
    public const FREQUENCIES = ['once' => 'یک‌باره', 'daily' => 'روزانه', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه', 'quarterly' => 'فصلی'];
    public const CATEGORIES = [
        'crawl' => 'خزش', 'index' => 'ایندکس', 'onpage' => 'سئوی داخلی', 'schema' => 'اسکیما', 'performance' => 'سرعت',
        'mobile' => 'موبایل', 'trust' => 'اعتماد و E-E-A-T', 'geo' => 'جستجوی هوش مصنوعی', 'analytics' => 'اتصال داده',
        'research' => 'تحقیق', 'strategy' => 'استراتژی', 'content' => 'محتوا', 'authority' => 'اعتبار و لینک',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'due_on' => 'date',
            'last_checked_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** تاریخ سررسید همیشه به‌صورت Y-m-d ذخیره شود (مقایسه‌ی رشته‌ای درست در MySQL و SQLite) */
    public function setDueOnAttribute($value): void
    {
        $this->attributes['due_on'] = $value ? \Illuminate\Support\Carbon::parse($value)->toDateString() : null;
    }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function keyword(): BelongsTo { return $this->belongsTo(Keyword::class); }

    public function scopeOpen($q) { return $q->whereNotIn('status', ['done', 'skipped']); }

    public function isOverdue(): bool
    {
        return $this->due_on && $this->due_on->isPast() && ! in_array($this->status, ['done', 'skipped'], true);
    }

    public static function priorityFor(int $impact, int $effort, string $automation): int
    {
        return ($impact * 2) - $effort + ($automation === 'auto' ? 1 : 0);
    }
}
