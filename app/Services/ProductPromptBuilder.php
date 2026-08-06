<?php

namespace App\Services;

use App\Models\Product;

class ProductPromptBuilder
{
    public function __construct(private ProductBuildSchema $schema) {}

    public function build(Product $product, array $values, ?bool $preserveIdentity = null): string
    {
        $template = $product->prompt_template ?: 'Create a high quality image.';
        $append = [];

        foreach ($this->schema->promptFields($product) as $field) {
            if (in_array($field['type'], ['section', 'divider', 'info'], true)) continue;
            if (!$field['hidden'] && !$this->schema->isVisible($field, $values)) continue;
            if (in_array($field['type'], ['image_upload', 'multi_image', 'file_upload'], true)) {
                $reference = $field['type'] === 'file_upload' ? 'the uploaded reference file' : 'the uploaded reference image(s)';
                $template = str_replace(['{{' . $field['id'] . '}}', '{' . $field['id'] . '}'], $reference, $template);
                continue;
            }
            $raw = $field['hidden'] ? ($field['value'] ?? '') : ($values[$field['id']] ?? $field['value'] ?? '');
            $selected = is_array($raw) ? $raw : [$raw];
            $parts = [];
            foreach ($selected as $value) {
                $option = collect($field['options'])->firstWhere('value', (string) $value);
                $text = trim((string) (($option['prompt'] ?? '') ?: $value));
                if ($text !== '') $parts[] = $text;
            }
            $resolved = implode(', ', $parts);
            if ($field['prompt_wrap'] !== '' && $resolved !== '') $resolved = str_replace('{value}', $resolved, $field['prompt_wrap']);
            if ($field['prompt_mode'] === 'append' && $resolved !== '') $append[] = $resolved;
            if ($field['prompt_mode'] === 'token') {
                $template = str_replace(['{{' . $field['id'] . '}}', '{' . $field['id'] . '}'], $resolved, $template);
            }
        }

        $parts = array_filter([trim((string) $product->system_prompt), trim($template), ...$append]);
        $preserveIdentity ??= (bool) $product->identity_preservation;
        if ($preserveIdentity) {
            $identity = trim((string) $product->identity_instructions) ?: self::defaultIdentityInstructions();
            if ($product->preserve_body) $identity .= ' Preserve the same body shape and proportions.';
            $parts[] = $identity;
        }
        return implode("\n\n", $parts);
    }

    public static function defaultIdentityInstructions(): string
    {
        return 'Preserve the exact identity and recognizable facial likeness of the person shown in the reference images. Keep the same facial structure, face shape, eyes, eyebrows, nose, lips, jawline, skin tone, approximate age, and distinctive natural features. Do not beautify, reshape, replace, merge, or reinterpret the person\'s face. Do not change ethnicity, age, gender presentation, or facial proportions. The reference images define the person\'s identity. The main prompt may change clothing, pose, lighting, background, camera angle, and artistic setting, but the person must remain clearly recognizable as the same individual. Maintain natural skin texture and realistic facial details. Avoid an artificial, overly smooth, generic, or model-like face.';
    }

    public static function defaultIdentityInstructionsFa(): string
    {
        return 'هویت دقیق و شباهت قابل‌تشخیص چهره فرد موجود در تصاویر مرجع را حفظ کن. ساختار و فرم صورت، چشم‌ها، ابروها، بینی، لب‌ها، خط فک، رنگ پوست، سن تقریبی و ویژگی‌های طبیعی متمایز او نباید تغییر کند. چهره را زیباتر، بازطراحی، جایگزین، ترکیب یا بازتفسیر نکن. قومیت، سن، نمود جنسیتی یا تناسبات چهره را تغییر نده. تصاویر مرجع تعریف‌کننده هویت فرد هستند. پرامپت اصلی می‌تواند لباس، ژست، نور، پس‌زمینه، زاویه دوربین و فضای هنری را تغییر دهد، اما فرد باید به‌وضوح همان شخص باقی بماند. بافت طبیعی پوست و جزئیات واقع‌گرایانه چهره را نگه دار و از چهره مصنوعی، بیش‌ازحد صاف، عمومی یا مدل‌گونه پرهیز کن.';
    }
}
