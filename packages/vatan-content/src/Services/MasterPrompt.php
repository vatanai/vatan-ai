<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Str;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Job;

/**
 * «پرامپت جامع ساخت» هر پست.
 * قالب اصلی = لایه‌ی master در آزمایشگاه پرامپت (بخش‌ها با «## عنوان» جدا می‌شوند).
 * هر پست نسخه‌ی خودش را دارد (cf_calendar_items.build_prompt): بخش‌ها قابل ویرایش، خاموش/روشن، حذف و اضافه‌اند
 * و متغیرهای {{…}} لحظه‌ی استفاده با داده‌ی تازه‌ی همان پست پر می‌شوند.
 */
class MasterPrompt
{
    public function __construct(private Prompts $prompts, private Ai $ai) {}

    /** @return array{system:string, sections: array<int,array{title:string,body:string,on:bool}>} */
    public function template(): array
    {
        $body = (string) ($this->prompts->active('master')->body ?? '');
        [$system, $user] = array_pad(preg_split('/^-{3,}\s*USER\s*-{3,}\s*$/mu', $body, 2), 2, '');
        $sections = [];
        foreach (preg_split('/^(?=##\s)/mu', trim($user)) as $chunk) {
            if (! trim($chunk)) {
                continue;
            }
            [$head, $rest] = array_pad(preg_split('/\R/u', $chunk, 2), 2, '');
            $sections[] = ['title' => trim(preg_replace('/^##\s*/u', '', $head)), 'body' => trim($rest), 'on' => true];
        }

        return ['system' => trim($system), 'sections' => $sections];
    }

    /** بخش‌های این پست (اگر هنوز شخصی‌سازی نشده، از قالب) */
    public function sections(CalendarItem $item): array
    {
        $own = $item->build_prompt;
        if (is_array($own) && ! empty($own['sections'])) {
            return $own;
        }

        return $this->template() + ['custom' => false];
    }

    public function vars(CalendarItem $item): array
    {
        $item->loadMissing(['styleModel', 'musicTrack']);
        $st = $item->styleModel;
        $bpm = (int) ($item->musicTrack->tempo_bpm ?? 0);
        $images = collect(json_decode(json_encode($item->scenes ?? []), true))->flatMap(fn ($s) => array_filter(array_merge(
            [$s['image'] ?? null, $s['before'] ?? null, $s['after'] ?? null], (array) ($s['images'] ?? []))))
            ->merge(collect($item->story_specs ?? [])->pluck('image'))->push($item->assets['cover_image'] ?? null)->filter()->unique()->values();
        $fid = $this->prompts->active('fidelity');
        $fidelity = $fid ? trim(preg_split('/^-{3,}\s*USER\s*-{3,}\s*$/mu', (string) $fid->body)[0]) : '';
        $json = fn ($v) => $v ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—';

        return [
            'code' => $item->code, 'date' => $item->publish_date ? \Vatan\Content\Support\Fa::date($item->publish_date, false, true) : '—', 'time' => $item->publish_time,
            'format' => $item->format, 'duration' => $item->duration_sec ?: round(collect($item->scenes ?? [])->sum('dur') / 30),
            'pillar' => $item->pillar, 'sector' => $item->sector, 'goal' => $item->goal, 'audience' => $item->audience, 'idea' => $item->idea,
            'experiment' => $item->experiment ?: '—', 'product' => $item->product_label ?: 'برند وطن (معرفی کلی)', 'product_url' => $item->product_url ?: 'aivatan.com',
            'keywords' => implode('، ', (array) $item->keywords) ?: '—', 'hook' => $item->hook, 'hook_family' => $item->ai_plan['hook_family'] ?? '—',
            'narrative' => $item->ai_plan['narrative'] ?? $item->idea, 'scenes' => $json($item->scenes), 'style' => $st->name_fa ?? $item->style,
            'theme' => app(Builder::class)->theme($st), 'style_desc' => $st->description ?? '—', 'style_recipe' => $json($st?->recipe), 'motion' => $st->motion ?? '—',
            'palette' => $json($st?->palette), 'music' => $item->musicTrack ? ($item->musicTrack->title.' · '.$item->musicTrack->mood) : ($item->music_mood ?: 'انتخاب خودکار از کتابخانه‌ی مجاز'),
            'bpm' => $bpm ?: '—', 'beat_frames' => $bpm ? round(1800 / $bpm, 1) : '—', 'music_start' => $item->assets['music_start'] ?? 0,
            'fidelity' => $fidelity, 'images' => $images->implode('، ') ?: 'storage:demo-productshots/d01.webp … (از نمونه‌های وطن)',
            'cover_text' => $item->cover_text ?: '—', 'stories' => $json($item->story_specs), 'caption' => $item->caption ?: '—', 'note' => $item->note ?: '—',
        ];
    }

    public function fill(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_\.]+)\s*\}\}/u', fn ($m) => (string) ($vars[$m[1]] ?? ''), $text);
    }

    /** @return array{system:string,user:string,text:string} پرامپت نهایی آماده‌ی ارسال */
    public function compile(CalendarItem $item): array
    {
        $s = $this->sections($item);
        $vars = $this->vars($item);
        $user = collect($s['sections'])->filter(fn ($x) => $x['on'] ?? true)->map(fn ($x) => '## '.$x['title']."\n".$this->fill((string) $x['body'], $vars))->implode("\n\n");
        $system = $this->fill((string) ($s['system'] ?? ''), $vars);

        return ['system' => $system, 'user' => $user, 'text' => $system."\n\n".$user];
    }

    /**
     * نوشتن دوباره‌ی صحنه‌ها/استوری/کپشن با همین پرامپت.
     * سرور با OpenRouter؛ اگر در دسترس نبود → کار «ai_write» برای Claude Code روی مک (اشتراک Pro، بدون هزینه‌ی API).
     */
    public function generate(CalendarItem $item, string $by = 'admin', string $where = 'auto'): array
    {
        $c = $this->compile($item);
        if ($where !== 'mac' && $this->ai->available()) {
            $r = $this->ai->text('writer', 'master.generate', $c['system'], $c['user'], [], ['max_tokens' => 7000, 'temperature' => 0.7, 'timeout' => 150]);
            if ($r && ($n = $this->apply($item, (string) $r['content']))) {
                return ['ok' => true, 'where' => 'server', 'message' => "با کلاد (سرور) نوشته شد: {$n}"];
            }
            if ($where === 'server') {
                return ['ok' => false, 'where' => 'server', 'message' => 'هوش سرور جواب معتبر نداد: '.($this->ai->lastError ?: 'JSON نامعتبر')];
            }
        }
        $job = Job::create(['workspace_id' => $item->workspace_id, 'type' => 'ai_write', 'location' => 'mac', 'status' => 'queued', 'created_by' => $by,
            'production_id' => $item->production?->id, 'payload' => ['item_id' => $item->id, 'code' => $item->code, 'system' => $c['system'], 'user' => $c['user']],
            'message' => 'در انتظار Claude Code روی مک']);

        return ['ok' => true, 'where' => 'mac', 'job' => $job, 'message' => 'در صف Claude Code مک قرار گرفت (کار #'.$job->id.').'];
    }

    /** خروجی JSON مدل را روی ردیف می‌نشاند؛ نسخه‌ی قبلی برای بازگشت نگه داشته می‌شود. */
    public function apply(CalendarItem $item, string $text): ?string
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $a = strpos($text, '{');
        $b = strrpos($text, '}');
        $d = $a !== false && $b > $a ? json_decode(substr($text, $a, $b - $a + 1), true) : null;
        if (! is_array($d) || empty($d['scenes']) || ! is_array($d['scenes'])) {
            return null;
        }
        $result = (array) ($item->result ?? []);
        $result['history'] = array_slice(array_merge([['at' => now()->toIso8601String(), 'scenes' => $item->scenes, 'story_specs' => $item->story_specs,
            'caption' => $item->caption, 'cover_text' => $item->cover_text, 'hook' => $item->hook]], (array) ($result['history'] ?? [])), 0, 5);
        $assets = (array) ($item->assets ?? []);
        if (! empty($d['cover_hl'])) {
            $assets['cover_hl'] = (array) $d['cover_hl'];
        }
        $item->fill(array_filter([
            'scenes' => array_values($d['scenes']), 'story_specs' => isset($d['story_specs']) ? array_values((array) $d['story_specs']) : null,
            'cover_text' => $d['cover_text'] ?? null, 'caption' => $d['caption'] ?? null, 'hashtags' => $d['hashtags'] ?? null,
            'keywords' => isset($d['keywords']) ? array_values((array) $d['keywords']) : null, 'hook' => isset($d['hook']) ? Str::limit((string) $d['hook'], 250, '') : null,
        ], fn ($v) => $v !== null) + ['assets' => $assets, 'result' => $result]);
        if (isset($d['story_specs'])) {
            $item->stories = collect($d['story_specs'])->pluck('title')->filter()->values()->all();
        }
        $item->save();

        return count($d['scenes']).' صحنه'.(isset($d['story_specs']) ? ' و '.count((array) $d['story_specs']).' استوری' : '');
    }

    /** بازگشت به نسخه‌ی قبل از آخرین بازنویسی */
    public function undo(CalendarItem $item): bool
    {
        $result = (array) ($item->result ?? []);
        $hist = (array) ($result['history'] ?? []);
        $prev = array_shift($hist);
        $result['history'] = $hist;
        if (! $prev) {
            return false;
        }
        $item->fill(array_filter(['scenes' => $prev['scenes'], 'story_specs' => $prev['story_specs'], 'caption' => $prev['caption'],
            'cover_text' => $prev['cover_text'], 'hook' => $prev['hook']], fn ($v) => $v !== null) + ['result' => $result])->save();

        return true;
    }
}
