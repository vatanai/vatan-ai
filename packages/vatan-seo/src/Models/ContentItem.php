<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentItem extends SeoModel
{
    protected $table = 'seo_content_items';

    public const STATUSES = [
        'idea' => 'ایده',
        'brief' => 'بریف آماده',
        'drafting' => 'در حال نگارش',
        'review' => 'منتظر تأیید',
        'approved' => 'تأیید شد',
        'published' => 'منتشر شد',
        'rejected' => 'رد شد',
        'failed' => 'خطا',
    ];

    protected function casts(): array
    {
        return [
            'brief' => 'array',
            'blocks' => 'array',
            'faq' => 'array',
            'quality' => 'array',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'planned_for' => 'date',
            'cost_usd' => 'float',
        ];
    }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function keyword(): BelongsTo { return $this->belongsTo(Keyword::class); }
}
