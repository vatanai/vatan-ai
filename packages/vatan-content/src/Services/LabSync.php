<?php

namespace Vatan\Content\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vatan\Content\Models\Calendar;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\ProductionVersion;
use Vatan\Content\Models\Reference;
use Vatan\Content\Models\Style;

/**
 * همگام‌سازی پوشه‌ی content-lab (مک) با دیتابیس داشبورد.
 * فایل‌های حجیم (ویدیوی مرجع، موسیقی) در content-lab می‌مانند؛ فقط پیش‌نمایش‌ها
 * (کانتکت‌شیت، کاور، خروجی نهایی) در دیسک عمومی کپی می‌شوند.
 * idempotent است: هر بار اجرا فقط تغییرها را اعمال می‌کند و تصمیم‌های ادمین (وضعیت، تأیید) را بازنویسی نمی‌کند.
 */
class LabSync
{
    private array $log = [];

    public function __construct(private ?string $lab = null, private int $ws = 1)
    {
        $this->lab = $lab ?: config('content-factory.lab_path');
        $this->ws = (int) config('content-factory.workspace_id', 1);
    }

    public function run(array $only = []): array
    {
        $parts = $only ?: ['styles', 'references', 'music', 'calendar', 'productions'];
        foreach ($parts as $p) {
            $this->{'sync'.Str::studly($p)}();
        }

        return $this->log;
    }

    private function disk()
    {
        return Storage::disk(config('content-factory.disk', 'public'));
    }

    public function publishFile(string $abs, string $rel): ?string
    {
        return $this->copyToDisk($abs, $rel);
    }



    private function copyToDisk(string $abs, string $rel): ?string
    {
        if (! is_file($abs)) {
            return null;
        }
        $path = trim(config('content-factory.disk_root', 'content-factory'), '/').'/'.$rel;
        $disk = $this->disk();
        if (! $disk->exists($path) || $disk->size($path) !== filesize($abs)) {
            $disk->put($path, file_get_contents($abs));
        }

        return $path;
    }

    private function json(string $rel): ?array
    {
        $f = $this->lab.'/'.$rel;

        return is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    }

    /** سبک‌ها، پرامپت‌ها و یادگیری اولیه از داده‌ی پایه‌ی پکیج (بدون بازنویسی ویرایش‌های ادمین) */
    public function syncStyles(): void
    {
        $this->log[] = 'پایه: '.implode(' · ', \Vatan\Content\Support\Seed::run($this->ws));
    }

    public function syncReferences(): void
    {
        $dir = $this->lab.'/_system/cache';
        if (! is_dir($dir)) {
            $this->log[] = 'references: کش ingest نبود';

            return;
        }
        $styles = Style::where('workspace_id', $this->ws)->get();
        $n = 0;
        foreach (glob($dir.'/*/meta.json') as $f) {
            $m = json_decode((string) file_get_contents($f), true);
            if (! $m || ($m['kind'] ?? '') !== 'style_reference') {
                continue;
            }
            $sheet = $this->copyToDisk(dirname($f).'/sheet.jpg', 'references/'.$m['slug'].'.jpg');
            $base = pathinfo($m['source'] ?? '', PATHINFO_FILENAME);
            $style = $styles->first(fn ($s) => in_array($base, $s->meta['refs'] ?? [], true));
            $ref = Reference::firstOrNew(['workspace_id' => $this->ws, 'slug' => $m['slug']]);
            $ref->fill([
                'kind' => 'style_reference', 'source_path' => $m['source'] ?? null, 'duration' => $m['duration'] ?? null,
                'width' => $m['width'] ?? null, 'height' => $m['height'] ?? null, 'shot_count' => $m['shot_count'] ?? null,
                'avg_shot_len' => $m['avg_shot_len'] ?? null, 'motion_level' => $m['motion']['level'] ?? null,
                'tempo_bpm' => $m['audio']['tempo_bpm'] ?? null, 'energy' => $m['audio']['energy'] ?? null,
                'colors' => $m['colors'] ?? [], 'sheet_path' => $sheet, 'meta' => $m,
            ]);
            if (! $ref->style_id && $style) {
                $ref->style_id = $style->id;
            }
            $ref->save();
            $n++;
        }
        $this->log[] = "references: {$n}";
    }

    public function syncMusic(): void
    {
        $dir = $this->lab.'/_system/cache';
        $notes = $this->json('02-music/music.json')['tracks'] ?? [];
        $n = 0;
        foreach (glob($dir.'/*/meta.json') ?: [] as $f) {
            $m = json_decode((string) file_get_contents($f), true);
            if (! $m || ! in_array($m['kind'] ?? '', ['music_video', 'music_audio'], true)) {
                continue;
            }
            $note = $notes[$m['slug']] ?? [];
            $audioAbs = isset($m['extracted_audio']) ? $this->lab.'/'.$m['extracted_audio'] : $this->lab.'/'.($m['source'] ?? '');
            $audio = $this->copyToDisk($audioAbs, 'music/'.$m['slug'].'.'.pathinfo($audioAbs, PATHINFO_EXTENSION));
            $t = MusicTrack::firstOrNew(['workspace_id' => $this->ws, 'slug' => $m['slug']]);
            $t->fill([
                'title' => $t->title ?: ($note['title'] ?? Str::limit(pathinfo($m['source'] ?? $m['slug'], PATHINFO_FILENAME), 40, '')),
                'source' => $m['kind'] === 'music_video' ? 'video' : 'file', 'source_path' => $m['source'] ?? null,
                'audio_path' => $audio, 'duration' => $m['duration'] ?? null, 'tempo_bpm' => $m['audio']['tempo_bpm'] ?? null,
                'energy' => $m['audio']['energy'] ?? null, 'meta' => $m,
                'mood' => $t->mood ?: ($note['mood'] ?? null), 'genre' => $t->genre ?: ($note['genre'] ?? null),
            ]);
            if (! $t->exists) {
                // ویدیوهایی که کاربر در پوشه‌ی موسیقی گذاشته، با مجوز تأییدشده ثبت می‌شوند (تصمیم ادمین)
                $t->license_status = 'approved';
                $t->commercial_ok = true;
                $t->license_note = $m['license_note'] ?? 'تأیید ادمین: فایل در پوشه‌ی موسیقی‌های مجاز قرار داده شد';
            }
            $t->save();
            $n++;
        }
        $this->log[] = "music: {$n}";
    }

    public function syncCalendar(): void
    {
        foreach (glob($this->lab.'/05-calendar/calendar-v*.json') ?: [] as $f) {
            $d = json_decode((string) file_get_contents($f), true);
            if (! $d || empty($d['rows']) || ! isset($d['rows'][0]['scenes'])) {
                $this->log[] = basename($f).': قالب قدیمی؛ نادیده گرفته شد';

                continue;
            }
            $this->log[] = \Vatan\Content\Support\Seed::calendar($d, $this->ws);
        }
    }

    public function syncProductions(): void
    {
        $n = 0;
        foreach (glob($this->lab.'/06-productions/*/manifest.json') ?: [] as $f) {
            $m = json_decode((string) file_get_contents($f), true);
            if (! $m || empty($m['code'])) {
                continue;
            }
            $dir = dirname($f);
            $item = null;
            if (! empty($m['calendar_code'])) {
                $item = CalendarItem::where('workspace_id', $this->ws)->where('code', $m['calendar_code'])
                    ->orderByDesc('calendar_id')->first();
            }
            $music = ! empty($m['music_slug']) ? MusicTrack::where('workspace_id', $this->ws)->where('slug', $m['music_slug'])->first() : null;
            $p = Production::firstOrNew(['workspace_id' => $this->ws, 'code' => $m['code']]);
            $p->fill([
                'calendar_item_id' => $item?->id, 'title' => $m['title'] ?? $m['code'], 'format' => $m['format'] ?? null,
                'style_slug' => $m['style_slug'] ?? null, 'music_id' => $music?->id, 'gene' => $m['gene'] ?? null,
                'script' => $m['script'] ?? null, 'caption' => $m['caption'] ?? null, 'hashtags' => $m['hashtags'] ?? null,
                'cost_usd' => $m['cost_usd'] ?? 0, 'build_seconds' => $m['build_seconds'] ?? null,
            ]);
            $p->status = in_array($p->status, ['approved', 'published', 'rejected'], true) ? $p->status : ($m['status'] ?? 'review');
            $p->save();
            foreach ($m['versions'] ?? [] as $v) {
                $vn = (int) $v['version'];
                $root = "productions/{$m['code']}/v{$vn}/";
                $put = fn (?string $rel) => $rel ? $this->copyToDisk($dir.'/'.$rel, $root.basename($rel)) : null;
                $pv = ProductionVersion::firstOrNew(['production_id' => $p->id, 'version' => $vn]);
                $pv->fill([
                    'video_path' => $put($v['video'] ?? null), 'cover_path' => $put($v['cover'] ?? null),
                    'stories' => array_values(array_filter(array_map($put, $v['stories'] ?? []))),
                    'slides' => array_values(array_filter(array_map($put, $v['slides'] ?? []))),
                    'duration' => $v['duration'] ?? null, 'notes' => $v['notes'] ?? null, 'qc' => $v['qc'] ?? null,
                ]);
                $pv->save();
                if ($vn > $p->current_version) {
                    $p->current_version = $vn;
                    $p->cover_path = $pv->cover_path;
                    if (! in_array($p->status, ['approved', 'published'], true)) {
                        $p->status = 'review';
                    }
                    $p->save();
                }
            }
            if ($item) {
                $item->production_status = $p->status;
                $item->save();
            }
            $n++;
        }
        $this->log[] = "productions: {$n}";
    }
}
