<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'cf_jobs';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array', 'result' => 'array', 'claimed_at' => 'datetime', 'finished_at' => 'datetime'];

    public const TYPES = [
        'ingest_videos' => 'تحلیل ویدیوهای پوشه‌ی مک',
        'ingest_music' => 'تحلیل موزیک‌های پوشه‌ی مک',
        'render' => 'ساخت ویدیو، کاور و استوری',
        'sync_files' => 'همگام‌سازی فایل‌ها',
        'doctor' => 'بررسی ابزارهای مک',
        'ai_write' => 'نوشتن سناریو با Claude Code',
    ];
    public const STATUSES = ['queued' => 'در صف', 'running' => 'در حال اجرا', 'done' => 'انجام شد', 'failed' => 'خطا', 'cancelled' => 'لغو شد'];

    public function production() { return $this->belongsTo(Production::class, 'production_id'); }

    public function appendLog(string $line): void
    {
        $this->log = trim(($this->log ? $this->log."\n" : '').'['.now()->format('H:i:s').'] '.$line);
    }
}
