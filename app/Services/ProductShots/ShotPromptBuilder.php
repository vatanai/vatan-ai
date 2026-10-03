<?php

namespace App\Services\ProductShots;

use App\Models\Product;
use App\Models\ShotLibrary;

/**
 * پرامپت هر شات = قالب ثابت + توکن‌های زبان شات + اطلاعات محصول
 * + بلوک ثابت «وفاداری محصول» (product_fidelity) + الزامات خروجی.
 *
 * مستقل از ProductPromptBuilder (مسیر چهره‌محور) است و آن را تغییر نمی‌دهد.
 */
class ShotPromptBuilder
{
    public const DEFAULT_TEMPLATE = "Create a premium, photorealistic advertising photograph of the exact product shown in the reference image(s){product_clause}.\n\n"
        . "Composition: {framing}, {camera}, {lens}.\n"
        . "Lighting: {lighting}.\n"
        . "Set and surface: {surface}.\n"
        . "Props and energy: {props}.\n"
        . "People: {human}.\n"
        . "Mood: {mood}.{brand_clause}";

    public function build(Product $product, ShotLibrary $shot, string $aspectRatio = '4:5', array $context = []): string
    {
        $tokens = $shot->normalizedTokens();
        $settings = (array) ($product->shot_settings ?? []);

        $values = [];
        foreach (ShotGrammar::AXES as $axis) {
            $values[$axis] = ShotGrammar::phrase($axis, $tokens[$axis] ?? null) ?: $this->axisFallback($axis);
        }

        $productDescription = trim((string) ($context['product_description'] ?? $settings['product_description'] ?? ''));
        $values['product'] = $productDescription !== '' ? $productDescription : 'the product';
        $values['product_clause'] = $productDescription !== '' ? " ({$productDescription})" : '';
        $values['brand_clause'] = $this->brandClause($settings);
        $values['aspect_ratio'] = $aspectRatio;
        $values['fidelity'] = self::productFidelityBlock();

        $template = trim((string) $shot->prompt_template) !== ''
            ? (string) $shot->prompt_template
            : self::DEFAULT_TEMPLATE;

        $body = $this->fill($template, $values);

        $parts = [trim($body)];
        if (! str_contains($template, '{fidelity}')) {
            $parts[] = self::productFidelityBlock();
        }
        if (ShotGrammar::involvesHuman($tokens)) {
            $parts[] = self::humanSafetyBlock();
        }
        $parts[] = "Output requirements: aspect ratio {$aspectRatio}; high resolution; sharp focus on the product; realistic materials, shadows and reflections. Do not add any text, captions, logos, watermarks or price tags that are not on the real product.";
        if ($brandIdentity = $this->brandIdentityBlock($settings)) {
            // طبق قرارداد فرم ادمین، دستور حفظ هویت برند عمداً آخرین بخش
            // پرامپت است تا روی همه‌ی شات‌های همان پک اولویت یکسان داشته باشد.
            $parts[] = $brandIdentity;
        }

        return implode("\n\n", array_filter($parts, fn ($p) => trim($p) !== ''));
    }

    public static function defaultBrandIdentityPrompt(): string
    {
        return 'Keep one coherent brand identity across the complete image set. Preserve the same color language, lighting character, contrast, material treatment and premium art direction in every shot, while allowing each shot to keep its own composition and camera angle.';
    }

    /** بلوک ثابت حفظ محصول — مهم‌ترین اولویت کیفیت طبق تصمیم مالک. */
    public static function productFidelityBlock(): string
    {
        return 'Product fidelity (highest priority): the product in the reference image(s) must appear exactly as it is. '
            . 'Keep its exact shape, silhouette, proportions, size relationships, colors, materials, finish, cap and packaging details. '
            . 'Keep the logo, brand name and all printed text exactly as they appear; never invent, translate, rewrite, blur into new letters or remove text or logos. '
            . 'Do not redesign, recolor, restyle or replace the product, and do not add a second copy of it. '
            . 'Only the scene, background, surface, lighting, props and camera may change.';
    }

    public static function productFidelityBlockFa(): string
    {
        return 'محصول داخل عکس مرجع را عیناً حفظ کن: شکل، نسبت‌ها، رنگ، متریال و جزئیات بسته؛ لوگو و متن را تغییر نده و اختراع نکن؛ فقط صحنه، نور، دکور و دوربین عوض شود.';
    }

    public static function humanSafetyBlock(): string
    {
        return 'Any person shown must be a fictional generic adult model, not resembling any real or famous person, styled modestly and tastefully. The product remains the hero of the image and must stay fully recognizable.';
    }

    private function brandClause(array $settings): string
    {
        $bits = [];
        $palette = trim((string) ($settings['brand_palette'] ?? ''));
        $style = trim((string) ($settings['brand_style'] ?? ''));
        if ($palette !== '') {
            $bits[] = "color palette inspired by {$palette}";
        }
        if ($style !== '') {
            $bits[] = $style;
        }

        return $bits === [] ? '' : "\nBrand look: " . implode('; ', $bits) . '.';
    }

    private function brandIdentityBlock(array $settings): ?string
    {
        if (! filter_var($settings['brand_identity_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $prompt = trim((string) ($settings['brand_identity_prompt'] ?? ''));
        if ($prompt === '') {
            $prompt = self::defaultBrandIdentityPrompt();
        }

        return "Brand identity continuity (apply to this shot and the whole pack):\n{$prompt}";
    }

    private function axisFallback(string $axis): string
    {
        return match ($axis) {
            'framing' => 'a balanced product-focused composition',
            'camera' => 'shot at eye level',
            'lens' => 'using a 50mm lens',
            'lighting' => 'soft flattering studio light',
            'surface' => 'a clean complementary surface',
            'props' => 'with minimal tasteful props',
            'human' => 'no people in the frame',
            'mood' => 'premium commercial mood',
            default => '',
        };
    }

    private function fill(string $template, array $values): string
    {
        return preg_replace_callback('/\{\{?\s*([a-z_]+)\s*\}?\}/', function (array $m) use ($values) {
            return array_key_exists($m[1], $values) ? (string) $values[$m[1]] : $m[0];
        }, $template) ?? $template;
    }
}
