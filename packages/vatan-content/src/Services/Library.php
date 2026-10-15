<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Facades\Storage;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Reference;
use Vatan\Content\Models\Style;

/**
 * ثبت نتیجه‌ی تحلیل ایجنت مک: ویدیوی مرجع → تشخیص سبک (کلاد با تصویر کانتکت‌شیت، در نبود آن نزدیک‌ترین سبک) ؛
 * موزیک → پوشه‌ی کتابخانه بر اساس انرژی و ضرب‌آهنگ.
 */
class Library
{
    public function __construct(private Ai $ai, private Prompts $prompts) {}

    private function ws(): int
    {
        return (int) config('content-factory.workspace_id', 1);
    }

    /** @return array{style:?Style,confidence:string,reason:string,folder:string} */
    public function classifyReference(array $meta, ?string $sheetPath): array
    {
        $styles = Style::where('workspace_id', $this->ws())->where('status', '!=', 'archived')->orderBy('sort')->get();
        $result = null;
        if ($sheetPath && $this->ai->available()) {
            $disk = Storage::disk(config('content-factory.disk', 'public'));
            $img = $disk->exists($sheetPath) ? 'data:image/jpeg;base64,'.base64_encode($disk->get($sheetPath)) : null;
            $catalog = $styles->map(fn ($s) => ['slug' => $s->slug, 'name' => $s->name_fa, 'desc' => $s->description])->values()->all();
            $p = $this->prompts->render('style', ['styles' => $catalog, 'metrics' => array_intersect_key($meta, array_flip(['duration', 'shot_count', 'avg_shot_len', 'motion', 'audio', 'colors']))]);
            $result = $img ? $this->ai->json('writer', 'style.classify', $p['system'], $p['user'], [$img], ['max_tokens' => 700]) : null;
        }
        if ($result && ! empty($result['slug']) && ($style = $styles->firstWhere('slug', $result['slug']))) {
            return ['style' => $style, 'confidence' => (string) ($result['confidence'] ?? 'متوسط'), 'reason' => (string) ($result['reason'] ?? ''), 'folder' => $style->folder ?: $style->slug,
                'new_style' => $result['new_style'] ?? null];
        }
        // نزدیک‌ترین سبک بر اساس ویژگی‌های عددی (fallback قابل‌توضیح)
        $best = null; $bestD = INF;
        foreach ($styles as $s) {
            $refs = $s->references()->get();
            if ($refs->isEmpty()) {
                continue;
            }
            $shot = $refs->avg('avg_shot_len') ?: 5; $motion = $refs->avg(fn ($r) => ['low' => 0, 'medium' => 1, 'high' => 2][$r->motion_level] ?? 1);
            $d = abs(log(max(0.3, (float) ($meta['avg_shot_len'] ?? 5))) - log(max(0.3, $shot))) + abs((['low' => 0, 'medium' => 1, 'high' => 2][$meta['motion']['level'] ?? 'medium'] ?? 1) - $motion) * 0.6;
            if ($d < $bestD) {
                $bestD = $d; $best = $s;
            }
        }
        if ($best && $bestD < 0.9) {
            return ['style' => $best, 'confidence' => 'کم', 'reason' => 'هوش در دسترس نبود؛ نزدیک‌ترین سبک از روی ریتم برش و حرکت انتخاب شد. لطفاً بازبینی کنید.', 'folder' => $best->folder ?: $best->slug];
        }

        return ['style' => null, 'confidence' => 'نامشخص', 'reason' => 'سبک مشخصی پیدا نشد؛ در پوشه‌ی «بدون سبک» گذاشته شد تا شما تصمیم بگیرید.', 'folder' => '_unsorted'];
    }

    /** @return array{0:Reference,1:array} */
    public function saveReference(array $meta, ?string $sheetPath): array
    {
        $c = $this->classifyReference($meta, $sheetPath);
        $ref = Reference::firstOrNew(['workspace_id' => $this->ws(), 'slug' => $meta['slug']]);
        $ref->fill([
            'kind' => 'style_reference', 'source_path' => $meta['source'] ?? null, 'duration' => $meta['duration'] ?? null, 'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null, 'shot_count' => $meta['shot_count'] ?? null, 'avg_shot_len' => $meta['avg_shot_len'] ?? null,
            'motion_level' => $meta['motion']['level'] ?? null, 'tempo_bpm' => $meta['audio']['tempo_bpm'] ?? null, 'energy' => $meta['audio']['energy'] ?? null,
            'colors' => $meta['colors'] ?? [], 'sheet_path' => $sheetPath ?: $ref->sheet_path, 'meta' => $meta, 'analysis' => $c['reason'],
            'style_confidence' => $c['confidence'], 'status' => $c['style'] ? 'classified' : 'needs_review',
        ]);
        if (! $ref->style_id && $c['style']) {
            $ref->style_id = $c['style']->id;
        }
        $ref->save();

        return [$ref, $c];
    }

    public static function musicFolder(?string $energy, ?int $bpm): array
    {
        $e = in_array($energy, ['low', 'medium', 'high'], true) ? $energy : 'medium';
        $t = ! $bpm ? 'mid' : ($bpm < 95 ? 'slow' : ($bpm <= 125 ? 'mid' : 'fast'));
        $fa = ['low' => 'آرام', 'medium' => 'متوسط', 'high' => 'پرانرژی'][$e].' · '.['slow' => 'کند', 'mid' => 'متوسط', 'fast' => 'تند'][$t];

        return ["{$e}-{$t}", $fa];
    }

    public function saveMusic(array $meta, ?string $audioPath): MusicTrack
    {
        [$folder, $label] = self::musicFolder($meta['audio']['energy'] ?? null, $meta['audio']['tempo_bpm'] ?? null);
        $t = MusicTrack::firstOrNew(['workspace_id' => $this->ws(), 'slug' => $meta['slug']]);
        $t->fill([
            'title' => $t->title ?: mb_substr(pathinfo($meta['source'] ?? $meta['slug'], PATHINFO_FILENAME), 0, 40),
            'source' => ($meta['kind'] ?? '') === 'music_video' ? 'video' : 'file', 'source_path' => $meta['source'] ?? null,
            'audio_path' => $audioPath ?: $t->audio_path, 'duration' => $meta['duration'] ?? null, 'tempo_bpm' => $meta['audio']['tempo_bpm'] ?? null,
            'energy' => $meta['audio']['energy'] ?? null, 'meta' => $meta, 'folder' => $folder, 'mood' => $t->mood ?: $label,
        ]);
        if (! $t->exists) {
            $t->license_status = 'approved';
            $t->commercial_ok = true;
            $t->license_note = 'تأیید ادمین: فایل در پوشه‌ی موسیقی‌های مجاز قرار داده شد';
        }
        $t->save();

        return $t;
    }
}
