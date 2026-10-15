<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Job;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\Style;

/**
 * «ساخت» یک ردیف تقویم: تولید را می‌سازد، کپشن انسانی را با پرامپت قابل‌ویرایش می‌نویسد
 * و کار رندر را با مشخصات کامل در صف ایجنت مک می‌گذارد.
 */
class Builder
{
    public const THEMES = [
        'editorial-brand-story' => 'editorial', 'contrast-punch' => 'punch', 'kinetic-type-beat' => 'kinetic', 'before-after-split' => 'punch',
        'ui-walkthrough' => 'stage', 'stage-card-showcase' => 'stage', 'persian-tech-grid' => 'techgrid', 'tactile-desk' => 'tactile', 'site-scroll-showcase' => 'stage',
    ];

    public function __construct(private Ai $ai, private Prompts $prompts) {}

    public function theme(?Style $style): string
    {
        return $style?->template ?: (self::THEMES[$style?->slug] ?? 'punch');
    }

    public function production(CalendarItem $item): Production
    {
        $item->loadMissing(['calendar', 'production', 'styleModel', 'musicTrack']);
        $p = $item->production ?: Production::firstOrNew(['workspace_id' => $item->workspace_id, 'code' => $item->code.'-'.($item->calendar->version ?? 'v1')]);
        $p->fill([
            'calendar_item_id' => $item->id, 'title' => (string) $item->hook, 'format' => $item->format,
            'style_slug' => $item->styleModel->slug ?? $item->style_slug, 'music_id' => $item->music_id,
            'product_type' => $item->product_type, 'product_id' => $item->product_id, 'product_label' => $item->product_label, 'product_url' => $item->product_url,
            'keywords' => $item->keywords, 'hashtags' => $item->hashtags,
            'gene' => array_filter([
                'pillar' => $item->pillar, 'sector' => $item->sector, 'goal' => $item->goal, 'audience' => $item->audience, 'hook' => $item->hook,
                'hook_family' => $item->ai_plan['hook_family'] ?? null, 'narrative' => $item->ai_plan['narrative'] ?? null, 'cta' => $item->cta,
                'style' => $item->styleModel->name_fa ?? $item->style, 'motion' => $item->styleModel->motion ?? null,
                'music_mood' => $item->musicTrack->mood ?? $item->music_mood, 'model' => $item->engine, 'prompt_version' => $item->prompt_version,
                'product' => $item->product_label, 'experiment' => $item->experiment,
                'publish_at' => $item->publish_date?->toDateString().' '.$item->publish_time,
            ]),
        ]);
        $p->save();

        return $p;
    }

    /** کپشن: پرامپت «کپشن» (قابل ویرایش در آزمایشگاه پرامپت). در نبود هوش، متن آماده‌ی تقویم. */
    public function caption(CalendarItem $item, Production $p, bool $force = false): array
    {
        if ($p->caption && ! $force) {
            return ['source' => 'existing', 'text' => $p->caption];
        }
        $prompt = $this->prompts->render('caption', [
            'hook' => $item->hook, 'idea' => $item->idea, 'cta' => $item->cta, 'product' => $item->product_label, 'product_url' => $item->product_url,
            'sector' => $item->sector, 'goal' => $item->goal, 'pillar' => $item->pillar, 'keywords' => implode('، ', (array) $item->keywords),
            'draft' => $item->caption, 'format' => $item->format,
        ]);
        $r = $this->ai->text('writer', 'caption', $prompt['system'], $prompt['user'], [], ['max_tokens' => 900, 'temperature' => 0.85]);
        $text = $r ? trim($r['content']) : (string) $item->caption;
        $p->update(['caption' => $text, 'hashtags' => $p->hashtags ?: $item->hashtags]);

        return ['source' => $r ? 'ai' : 'calendar', 'text' => $text, 'prompt_version' => $prompt['version'], 'error' => $r ? null : $this->ai->lastError];
    }

    public function build(CalendarItem $item, string $by = 'admin', bool $regenCaption = false): Job
    {
        $p = $this->production($item);
        $cap = $this->caption($item, $p, $regenCaption);
        $job = Job::create([
            'workspace_id' => $item->workspace_id, 'type' => 'render', 'location' => $item->build_location ?: 'mac', 'status' => 'queued',
            'production_id' => $p->id, 'payload' => $this->spec($item, $p), 'created_by' => $by,
            'message' => 'کپشن: '.['ai' => 'نوشته‌شده با کلاد', 'calendar' => 'متن آماده‌ی تقویم', 'existing' => 'قبلی'][$cap['source']],
        ]);
        $p->update(['status' => 'rendering']);
        $item->update(['production_status' => 'rendering']);

        return $job;
    }

    /** آدرس قابل‌دسترسی برای رندر: lab:… (فایل مک)، storage:… (دیسک عمومی)، http… */
    public function resolve(?string $ref): ?string
    {
        if (! $ref) {
            return null;
        }
        if (Str::startsWith($ref, ['http://', 'https://', 'lab:', 'media/', 'brand/'])) {
            return $ref;
        }
        if (Str::startsWith($ref, 'storage:')) {
            return url(Storage::disk(config('content-factory.disk', 'public'))->url(substr($ref, 8)));
        }

        return $ref;
    }

    private function mapImages(array $node): array
    {
        array_walk_recursive($node, function (&$v, $k) {
            if (is_string($v) && in_array($k, ['image', 'before', 'after', 'images'], true) || (is_string($v) && Str::startsWith($v, ['storage:', 'lab:']))) {
                $v = $this->resolve($v);
            }
        });

        return $node;
    }

    public function spec(CalendarItem $item, Production $p): array
    {
        $item->loadMissing(['styleModel', 'musicTrack']);
        $theme = $this->theme($item->styleModel);
        if ($item->format === 'ریلز' && ! $item->musicTrack) {
            $track = ($item->assets['music_slug'] ?? null) ? MusicTrack::where('workspace_id', $item->workspace_id)->where('slug', $item->assets['music_slug'])->first() : null;
            $track ??= MusicTrack::where('workspace_id', $item->workspace_id)->where('license_status', 'approved')->whereNotNull('audio_path')
                ->when($item->styleModel?->template === 'editorial', fn ($q) => $q->orderByRaw("energy = 'high' asc"), fn ($q) => $q->orderByRaw("energy = 'high' desc"))
                ->orderBy('use_count')->first();
            if ($track) {
                $item->update(['music_id' => $track->id]);
                $item->setRelation('musicTrack', $track);
            }
        }
        if ($item->musicTrack) {
            $item->musicTrack->increment('use_count');
            $item->musicTrack->update(['last_used_at' => now()]);
        }
        $music = $item->musicTrack?->audio_path ? url(Storage::disk(config('content-factory.disk', 'public'))->url($item->musicTrack->audio_path)) : null;
        $scenes = $this->mapImages((array) ($item->scenes ?? []));
        $outputs = [];
        $cover = $this->resolve($item->assets['cover_image'] ?? null);
        if ($item->format === 'ریلز') {
            $outputs[] = ['name' => 'reel.mp4', 'role' => 'video', 'composition' => 'VatanReel', 'props' => ['theme' => $theme, 'scenes' => $scenes, 'music' => $music,
                'musicStart' => (float) ($item->assets['music_start'] ?? 0)]];
            $outputs[] = ['name' => 'cover.png', 'role' => 'cover', 'composition' => 'Cover', 'still' => true, 'props' => ['theme' => $theme, 'image' => $cover,
                'title' => $item->cover_text ?: $item->hook, 'titleHl' => $item->assets['cover_hl'] ?? [], 'kicker' => $item->assets['cover_kicker'] ?? null]];
        } elseif ($item->format === 'اسلایدی') {
            foreach ($scenes as $i => $s) {
                $outputs[] = ['name' => 'slide-'.($i + 1).'.png', 'role' => 'slide', 'composition' => 'Slide', 'still' => true, 'props' => $s + ['n' => $i + 1, 'total' => count($scenes)]];
            }
        } else {
            $outputs[] = ['name' => 'post.png', 'role' => 'slide', 'composition' => 'Poster', 'still' => true, 'props' => $scenes[0] ?? ['title' => (string) $item->hook]];
        }
        foreach ((array) ($item->story_specs ?? []) as $i => $st) {
            $outputs[] = ['name' => 'story-'.($i + 1).'.png', 'role' => 'story', 'composition' => 'Story', 'still' => true, 'frame' => 60, 'props' => $this->mapImages($st) + ['theme' => $theme]];
        }

        return ['production_id' => $p->id, 'code' => $p->code, 'version' => $p->current_version + 1, 'format' => $item->format, 'theme' => $theme, 'outputs' => $outputs];
    }
}
