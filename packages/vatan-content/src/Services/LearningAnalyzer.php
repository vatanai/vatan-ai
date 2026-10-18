<?php

namespace Vatan\Content\Services;

use Vatan\Content\Models\Insight;
use Vatan\Content\Models\Production;

/** تولید فرضیه‌های شفاف و قابل بازبینی از امتیازهای هم‌قالب؛ این سرویس خودش تنظیمات تولید را عوض نمی‌کند. */
class LearningAnalyzer
{
    private const DIMENSIONS = [
        'style' => ['سبک', 'style_slug'],
        'hook_family' => ['خانواده‌ی هوک', 'hook_family'],
        'pillar' => ['ستون محتوایی', 'pillar'],
        'music_mood' => ['حال‌وهوای موسیقی', 'music_mood'],
    ];

    public function refresh(int $workspaceId): int
    {
        $productions = Production::where('workspace_id', $workspaceId)
            ->where('status', 'published')
            ->whereNotNull('score_total')
            ->where('published_at', '>=', now()->subDays(90))
            ->get(['id', 'format', 'gene', 'style_slug', 'score_total', 'score_confidence']);

        $createdOrUpdated = 0;
        foreach ($productions->groupBy('format') as $format => $formatPosts) {
            if ($formatPosts->count() < 8) {
                continue;
            }

            $baseline = (float) $formatPosts->avg('score_total');
            foreach (self::DIMENSIONS as $dimension => [$label, $geneKey]) {
                $groups = $formatPosts->groupBy(function ($post) use ($dimension, $geneKey) {
                    $gene = (array) ($post->gene ?? []);

                    return $dimension === 'style'
                        ? ($post->style_slug ?: ($gene[$geneKey] ?? null))
                        : ($gene[$geneKey] ?? null);
                });

                foreach ($groups as $value => $posts) {
                    if (! $value || $posts->count() < 5) {
                        continue;
                    }
                    $average = (float) $posts->avg('score_total');
                    $lift = round($average - $baseline, 1);
                    if ($lift < 10) {
                        continue;
                    }

                    $scope = 'auto:'.$format.':'.$dimension.':'.mb_substr((string) $value, 0, 80);
                    $insight = Insight::firstOrNew(['workspace_id' => $workspaceId, 'kind' => 'hypothesis', 'scope' => $scope]);
                    // تصمیم انسانی پایدار می‌ماند؛ فرضیه‌ی ردشده یا پذیرفته‌شده دوباره باز نمی‌شود.
                    if ($insight->exists && $insight->status !== 'pending') {
                        continue;
                    }

                    $count = $posts->count();
                    $confidence = $count >= 15 ? 'زیاد' : ($count >= 8 ? 'متوسط' : 'کم');
                    $insight->fill([
                        'title' => "{$label} «{$value}» در قالب {$format} بهتر از میانگین بوده",
                        'body' => "در {$count} انتشار امتیازدار، میانگین امتیاز این گروه {$average} از ۱۰۰ بوده؛ میانگین همه‌ی پست‌های هم‌قالب {$baseline} است. این یک همبستگی اولیه است و به‌تنهایی علت موفقیت را ثابت نمی‌کند.",
                        'sample_size' => $count,
                        'confidence' => $confidence,
                        'period' => '۹۰ روز اخیر · '.$format,
                        'evidence' => ['dimension' => $dimension, 'value' => (string) $value, 'format' => (string) $format,
                            'group_average' => $average, 'format_average' => round($baseline, 1), 'lift' => $lift,
                            'production_ids' => $posts->pluck('id')->values()->all()],
                        'suggestion' => 'این الگو را در چند پست تازه به‌صورت آزمایش کنترل‌شده امتحان کنید؛ پیش از تأیید شما هیچ سبک یا پرامپتی خودکار تغییر نمی‌کند.',
                        'status' => 'pending',
                    ])->save();
                    $createdOrUpdated++;
                }
            }
        }

        return $createdOrUpdated;
    }
}
