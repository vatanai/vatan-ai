<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vatan\Seo\Support\Fa;

class Keyword extends SeoModel
{
    protected $table = 'seo_keywords';

    public const STATUSES = ['candidate', 'target', 'archived'];
    public const INTENTS = ['informational' => 'اطلاعاتی', 'commercial' => 'تحقیق خرید', 'transactional' => 'خرید/اقدام', 'navigational' => 'برند/ناوبری'];
    public const SOURCES = ['manual' => 'دستی', 'products' => 'محصولات', 'gsc' => 'سرچ کنسول', 'autocomplete' => 'پیشنهاد گوگل', 'ai' => 'هوش مصنوعی'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'current_position' => 'float',
            'previous_position' => 'float',
            'best_position' => 'float',
            'start_position' => 'float',
            'targeted_at' => 'datetime',
            'rank_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Keyword $keyword) {
            $keyword->keyword = Fa::cleanKeyword($keyword->keyword);
            $keyword->normalized = Fa::normalizeKeyword($keyword->keyword);
        });
    }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function cluster(): BelongsTo { return $this->belongsTo(Cluster::class); }
    public function ranks(): HasMany { return $this->hasMany(KeywordRank::class)->orderBy('date'); }

    public function scopeTargets($q) { return $q->where('status', 'target'); }
    public function scopeCandidates($q) { return $q->where('status', 'candidate'); }

    /** تغییر رتبه نسبت به قبل؛ عدد مثبت یعنی رشد (رتبه کوچک‌تر شده) */
    public function delta(): ?float
    {
        if ($this->current_position === null || $this->previous_position === null) {
            return null;
        }
        return round($this->previous_position - $this->current_position, 1);
    }

    /** رشد از روز هدف‌گذاری */
    public function growthSinceStart(): ?float
    {
        if ($this->current_position === null || $this->start_position === null) {
            return null;
        }
        return round($this->start_position - $this->current_position, 1);
    }

    public function bucket(): string
    {
        $p = $this->current_position;
        return match (true) {
            $p === null => 'none',
            $p <= 3 => 'top3',
            $p <= 10 => 'top10',
            $p <= 20 => 'top20',
            default => 'rest',
        };
    }
}
