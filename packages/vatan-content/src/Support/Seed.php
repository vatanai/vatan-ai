<?php

namespace Vatan\Content\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Vatan\Content\Models\Calendar;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Insight;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\PromptVersion;
use Vatan\Content\Models\Style;

/**
 * داده‌ی پایه‌ی کارخانه (سبک‌ها، پرامپت‌ها، تقویم، یادگیری اولیه) از database/seed/*.json.
 * idempotent و محافظه‌کار: ویرایش‌های ادمین را بازنویسی نمی‌کند؛ تقویم قفل‌شده دست نمی‌خورد.
 * با مایگریشن اجرا می‌شود تا بعد از دیپلوی هم همه‌چیز سر جایش باشد.
 */
class Seed
{
    private static function json(string $f): array
    {
        $p = dirname(__DIR__, 2).'/database/seed/'.$f;

        return is_file($p) ? (array) json_decode((string) file_get_contents($p), true) : [];
    }

    public static function run(int $ws = 1): array
    {
        if (! Schema::hasTable('cf_styles') || ! Schema::hasColumn('cf_calendar_items', 'scenes')) {
            return ['skip'];
        }
        $log = [];
        $n = 0;
        foreach (self::json('styles.json')['styles'] ?? [] as $s) {
            $st = Style::firstOrNew(['workspace_id' => $ws, 'slug' => $s['slug']]);
            if (! $st->exists) {
                $st->fill(['name_fa' => $s['name_fa'], 'name_en' => $s['name_en'] ?? null, 'description' => $s['desc'] ?? null, 'goals' => $s['goals'] ?? [],
                    'sectors' => $s['sectors'] ?? [], 'duration' => $s['duration'] ?? null, 'aspect' => $s['aspect'] ?? '9:16', 'shots' => $s['shots'] ?? null,
                    'motion' => $s['motion'] ?? null, 'text_style' => $s['text'] ?? null, 'hook_family' => $s['hook_family'] ?? null, 'cta_family' => $s['cta_family'] ?? null,
                    'music_hint' => $s['music'] ?? null, 'engine' => $s['engine'] ?? null, 'status' => $s['status'] ?? 'testing', 'meta' => ['refs' => $s['refs'] ?? []]]);
            }
            foreach (['template' => $s['template'] ?? null, 'recipe' => $s['recipe'] ?? null, 'sort' => $s['sort'] ?? 0, 'folder' => $s['folder'] ?? $s['slug']] as $k => $v) {
                if (empty($st->{$k})) {
                    $st->{$k} = $v;
                }
            }
            if ($st->status === 'pending' && ! empty($s['status'])) {
                $st->status = $s['status'];
            }
            $st->save();
            $n++;
        }
        $log[] = "styles {$n}";

        $n = 0;
        foreach (self::json('prompts.json')['prompts'] ?? [] as $p) {
            if (! PromptVersion::where('workspace_id', $ws)->where('layer', $p['layer'])->exists()) {
                PromptVersion::create(['workspace_id' => $ws, 'layer' => $p['layer'], 'title' => $p['title'], 'version' => 1, 'body' => $p['body'],
                    'status' => 'active', 'editor' => 'Claude', 'notes' => $p['notes'] ?? null]);
                $n++;
            }
        }
        $log[] = "prompts +{$n}";

        $cal = self::json('calendar.json');
        if ($cal) {
            $log[] = self::calendar($cal, $ws);
        }

        if (Insight::where('workspace_id', $ws)->doesntExist()) {
            foreach (self::json('insights.json')['insights'] ?? [] as $i) {
                Insight::create($i + ['workspace_id' => $ws]);
            }
            $log[] = 'insights seeded';
        }

        return $log;
    }

    public static function calendar(array $d, int $ws): string
    {
        $c = Calendar::where('workspace_id', $ws)->where('version', $d['version'])->first();
        if ($c && $c->isLocked()) {
            return 'calendar locked — skip';
        }
        // اگر تقویم پیش‌نویس قدیمی‌تری با همین نسخه هست، با نسخه‌ی کامل جایگزین می‌شود (ردیف‌های تولیدشده حفظ می‌شوند)
        return DB::transaction(function () use ($c, $d, $ws) {
            $c ??= new Calendar(['workspace_id' => $ws, 'version' => $d['version']]);
            $c->fill(['title' => $d['title'], 'starts_on' => $d['range'][0], 'ends_on' => $d['range'][1], 'goal' => $d['goal'], 'summary' => $d['summary'],
                'brief' => $d['brief'], 'source_file' => 'seed/calendar.json', 'status' => $c->status ?: 'draft'])->save();
            $codes = array_column($d['rows'], 'id');
            CalendarItem::where('calendar_id', $c->id)->whereNotIn('code', $codes)->whereDoesntHave('production')->delete();
            $styles = Style::where('workspace_id', $ws)->pluck('id', 'slug');
            $music = MusicTrack::where('workspace_id', $ws)->pluck('id', 'slug');
            foreach ($d['rows'] as $i => $r) {
                $item = CalendarItem::firstOrNew(['calendar_id' => $c->id, 'code' => $r['id']]);
                if ($item->exists && $item->updated_at && $item->created_at && $item->updated_at->gt($item->created_at->copy()->addSeconds(5)) && ! empty($item->scenes)) {
                    continue; // ادمین ویرایش کرده
                }
                [$ptype, $pkey] = array_pad(explode(':', (string) ($r['product'] ?? 'brand:vatan'), 2), 2, null);
                $item->fill([
                    'workspace_id' => $ws, 'publish_date' => $r['date'], 'publish_time' => $r['time'], 'platform' => $r['platform'], 'format' => $r['format'],
                    'pillar' => $r['pillar'], 'sector' => $r['sector'], 'goal' => $r['goal'], 'audience' => $r['audience'], 'idea' => $r['idea'], 'hook' => $r['hook'],
                    'cta' => $r['cta'], 'style_slug' => $r['style_slug'], 'style_id' => $styles[$r['style_slug']] ?? null,
                    'style' => Style::where('workspace_id', $ws)->where('slug', $r['style_slug'])->value('name_fa'),
                    'music_mood' => $r['music'], 'music_id' => $r['music_slug'] ? ($music[$r['music_slug']] ?? null) : null,
                    'product_type' => $ptype, 'product_key' => $pkey, 'product_label' => $r['product_label'], 'engine' => $r['engine'], 'build_location' => $r['build_location'],
                    'duration_sec' => $r['duration_sec'], 'scenes' => $r['scenes'], 'story_specs' => $r['story_specs'], 'stories' => $r['stories'],
                    'cover_text' => $r['cover_text'], 'assets' => $r['assets'] + ['music_slug' => $r['music_slug']], 'caption' => $r['caption'], 'hashtags' => $r['hashtags'],
                    'keywords' => $r['keywords'], 'ai_plan' => $r['ai_plan'], 'experiment' => $r['experiment'], 'note' => $r['note'],
                    'prompt_version' => $r['prompt_version'], 'tracking_url' => $r['tracking'], 'sort' => $i,
                ]);
                $item->save();
            }

            return 'calendar '.$d['version'].' · '.count($d['rows']).' rows';
        });
    }
}
