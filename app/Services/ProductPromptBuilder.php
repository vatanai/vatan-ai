<?php

namespace App\Services;

use App\Models\Product;

class ProductPromptBuilder
{
    public function __construct(private ProductBuildSchema $schema) {}

    public function build(Product $product, array $values): string
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
        if ($product->identity_preservation) {
            $identity = trim((string) $product->identity_instructions) ?: 'Preserve the exact facial identity, facial features, skin texture, and likeness of the person in every reference image.';
            if ($product->preserve_body) $identity .= ' Preserve the same body shape and proportions.';
            $parts[] = $identity;
        }
        return implode("\n\n", $parts);
    }
}
