<?php

namespace App\Services\ProductShots;

use App\Models\ProductShotSetting;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Log;

/**
 * مدل بینای ارزان برای:
 *  ۱) فیلتر کیفیت ورودی (pre-flight) قبل از ساخت — هیچ اعتباری کسر نمی‌شود؛
 *  ۲) کنترل وفاداری خروجی (QC) بعد از ساخت — آیا محصول همان محصول ورودی است؟
 * خطای مدل بینایی هرگز ساخت را متوقف نمی‌کند؛ فقط نتیجه «بررسی نشد» می‌شود.
 */
class ShotVisionService
{
    public const ISSUES = [
        'blur' => 'عکس تار است',
        'dark' => 'نور عکس کم است',
        'cropped' => 'بخشی از محصول بیرون از کادر است',
        'busy_bg' => 'پس‌زمینه شلوغ است',
        'multiple_products' => 'بیش از یک محصول در عکس است',
        'has_person' => 'فرد در عکس دیده می‌شود',
        'low_res' => 'کیفیت (رزولوشن) عکس پایین است',
        'no_product' => 'محصولی در عکس پیدا نشد',
    ];

    /** مشکلاتی که بدون عکس جدید قابل استفاده نیست. */
    public const BLOCKING = ['no_product', 'blur'];

    public function __construct(private ShotImageStore $images) {}

    /**
     * @return array{verdict:string, usable:bool, issues:array<int,string>, issue_labels:array<int,string>, crop_box:?array, suggestion_fa:string, product_description:?string, checked_by:string, model:?string}
     */
    public function preflight(string $path, ?ProductShotSetting $settings = null): array
    {
        $settings ??= ProductShotSetting::current();
        $issues = [];
        $cropBox = null;
        $description = null;
        $suggestion = '';
        $checkedBy = 'local';
        $model = null;

        [$w, $h] = $this->images->dimensions($path);
        if (min($w, $h) > 0 && min($w, $h) < 500) {
            $issues[] = 'low_res';
        }
        $lum = $this->images->averageLuminance($path);
        if ($lum !== null && $lum < 55) {
            $issues[] = 'dark';
        }

        if ($settings->preflight_enabled) {
            try {
                $model = $settings->preflight_model ?: (string) config('product_shots.vision_model');
                $result = app(OpenRouterService::class)->analyzeImagesJson(
                    $model,
                    $this->preflightInstruction(),
                    [$this->images->visionReference($path)],
                    40,
                );
                $data = (array) $result['content'];
                foreach ((array) ($data['issues'] ?? []) as $issue) {
                    $issue = (string) $issue;
                    if (isset(self::ISSUES[$issue])) {
                        $issues[] = $issue;
                    }
                }
                if (array_key_exists('usable', $data) && $data['usable'] === false && ! array_intersect($issues, self::BLOCKING)) {
                    $issues[] = 'no_product';
                }
                $cropBox = $this->normalizeBox($data['crop_box'] ?? null);
                $suggestion = trim((string) ($data['suggestion_fa'] ?? ''));
                $description = trim((string) ($data['product_description'] ?? '')) ?: null;
                $checkedBy = 'vision';
            } catch (\Throwable $e) {
                Log::warning('ProductShots preflight vision failed', ['error' => $e->getMessage()]);
            }
        }

        $issues = array_values(array_unique($issues));
        $blocking = array_values(array_intersect($issues, self::BLOCKING));
        $verdict = $blocking ? 'red' : ($issues ? 'yellow' : 'green');
        if ($suggestion === '') {
            $suggestion = $this->defaultSuggestion($issues, $verdict);
        }

        return [
            'verdict' => $verdict,
            'usable' => $verdict !== 'red',
            'issues' => $issues,
            'issue_labels' => array_map(fn ($i) => self::ISSUES[$i], $issues),
            'crop_box' => $cropBox,
            'suggestion_fa' => $suggestion,
            'product_description' => $description,
            'checked_by' => $checkedBy,
            'model' => $model,
        ];
    }

    /**
     * مقایسه‌ی خروجی با عکس ورودی.
     *
     * @return array{checked:bool, passed:bool, score:?int, issues:array, summary:string, model:?string, usage:array}
     */
    public function qualityCheck(string $sourcePath, string $outputPath, ?ProductShotSetting $settings = null): array
    {
        $settings ??= ProductShotSetting::current();
        if (! $settings->qc_enabled) {
            return ['checked' => false, 'passed' => true, 'score' => null, 'issues' => [], 'summary' => '', 'model' => null, 'usage' => []];
        }

        $model = $settings->qc_model ?: (string) config('product_shots.vision_model');
        try {
            $result = app(OpenRouterService::class)->analyzeImagesJson(
                $model,
                $this->qcInstruction(),
                [$this->images->visionReference($sourcePath), $this->images->visionReference($outputPath, 1024)],
                45,
            );
            $data = (array) $result['content'];
            $score = max(1, min(5, (int) ($data['fidelity_score'] ?? 0) ?: 1));
            $same = (bool) ($data['same_product'] ?? false);

            return [
                'checked' => true,
                'passed' => $same && $score >= 3,
                'score' => $score,
                'issues' => array_values(array_map('strval', (array) ($data['issues'] ?? []))),
                'summary' => trim((string) ($data['summary'] ?? '')),
                'model' => $model,
                'usage' => (array) ($result['usage'] ?? []),
            ];
        } catch (\Throwable $e) {
            Log::warning('ProductShots QC vision failed', ['error' => $e->getMessage()]);

            return ['checked' => false, 'passed' => true, 'score' => null, 'issues' => [], 'summary' => 'بررسی خودکار انجام نشد.', 'model' => $model, 'usage' => []];
        }
    }

    public function preflightInstruction(): string
    {
        return 'You are a strict quality inspector for e-commerce product photos that will be used as the reference for AI product photography. '
            . 'Inspect the image and return ONLY a JSON object with this exact shape: '
            . '{"usable":true,"issues":[],"crop_box":{"x":0,"y":0,"w":1,"h":1},"product_description":"","suggestion_fa":""}. '
            . 'issues may contain only these codes: blur, dark, cropped, busy_bg, multiple_products, has_person, low_res, no_product. '
            . 'usable=false only if there is no clear single physical product or it is too blurry to see its shape and label. '
            . 'crop_box is the tight bounding box of the main product in normalized 0..1 coordinates (x,y = top-left). '
            . 'product_description: a short English description of the physical product (type, shape, main colors, material, visible brand name), max 25 words. '
            . 'suggestion_fa: one short, friendly Persian sentence telling the user how to take a better photo, or an empty string if the photo is good.';
    }

    public function qcInstruction(): string
    {
        return 'Image 1 is the real product reference photo. Image 2 is an AI-generated advertising photo that must contain the SAME product. '
            . 'Compare only the product itself (ignore scene, background, lighting and props). Check shape, proportions, colors, materials, cap/packaging and logo/label placement. '
            . 'Small unreadable text is acceptable; a different product, wrong color, wrong shape, invented logo or duplicated product is not. '
            . 'Return ONLY JSON: {"same_product":true,"fidelity_score":5,"issues":[],"summary":""} where fidelity_score is 1..5 (5 = identical) and issues are short English phrases.';
    }

    private function normalizeBox(mixed $box): ?array
    {
        if (! is_array($box)) {
            return null;
        }
        $out = [];
        foreach (['x', 'y', 'w', 'h'] as $k) {
            if (! isset($box[$k]) || ! is_numeric($box[$k])) {
                return null;
            }
            $out[$k] = max(0.0, min(1.0, (float) $box[$k]));
        }

        return $out;
    }

    private function defaultSuggestion(array $issues, string $verdict): string
    {
        if ($verdict === 'green') {
            return 'عکس مناسب است.';
        }
        $tips = [
            'blur' => 'گوشی را ثابت نگه دار و روی محصول فوکوس کن.',
            'dark' => 'کنار پنجره و در نور روز عکس بگیر.',
            'cropped' => 'کل محصول را در کادر جا بده.',
            'busy_bg' => 'محصول را جلوی دیوار یا پارچه‌ی ساده بگذار.',
            'multiple_products' => 'فقط یک محصول را در کادر بگذار.',
            'has_person' => 'بهتر است فقط خود محصول در عکس باشد.',
            'low_res' => 'عکس با کیفیت بالاتر بفرست (حداقل ۱۰۰۰ پیکسل).',
            'no_product' => 'یک عکس واضح از جلوی محصول بفرست.',
        ];
        $lines = array_values(array_intersect_key($tips, array_flip($issues)));

        return implode(' ', array_slice($lines, 0, 2));
    }
}
