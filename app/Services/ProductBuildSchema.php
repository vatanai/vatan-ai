<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductBuildSchema
{
    public function fields(Product $product): array
    {
        $fields = collect($product->input_schema ?? [])
            // داده قدیمی/واردشده ممکن است آیتم null یا string داشته باشد؛
            // یک آیتم خراب نباید کل صفحه و دکمه نهایی ساخت را حذف کند.
            ->filter(fn ($field) => is_array($field))
            ->map(fn (array $field) => $this->normalizeField($field))
            ->filter(fn (array $field) => $field['id'] !== '' && !$field['hidden'])
            ->values();

        // سازگاری محصولات قدیمی: توکن‌های قالب پرامپت به فیلدهای پایه تبدیل می‌شوند.
        if ($fields->isEmpty()) {
            preg_match_all('/\{\{?([a-zA-Z][a-zA-Z0-9_]*)\}\}?/', (string) $product->prompt_template, $matches);
            foreach (array_unique($matches[1] ?? []) as $token) {
                $isImage = Str::contains(Str::lower($token), ['image', 'photo', 'portrait', 'face', 'input_file']);
                $fields->push($this->normalizeField([
                    'field_id' => $token,
                    'type' => $isImage ? 'image_upload' : ($token === 'prompt' ? 'prompt' : 'text'),
                    'label_fa' => $this->legacyLabel($token),
                    'description' => $isImage ? 'تصویر واضح و باکیفیت انتخاب کنید.' : '',
                    'placeholder' => $isImage ? '' : 'مقدار دلخواه را وارد کنید...',
                    'required' => '1',
                    'max_size_mb' => 10,
                    'accept' => $isImage ? 'image/*' : '',
                ]));
            }
        }

        if ((int) $product->min_reference_images > 0 && !$fields->contains(fn (array $field) => in_array($field['type'], ['image_upload', 'multi_image'], true))) {
            $fields->prepend($this->normalizeField([
                'field_id' => 'reference_images',
                'type' => (int) $product->max_reference_images > 1 ? 'multi_image' : 'image_upload',
                'label_fa' => 'تصویر مرجع',
                'description' => 'برای حفظ دقیق هویت، تصویر واضح و باکیفیت بارگذاری کنید.',
                'required' => '0',
                'max_files' => min(3, max(2, (int) $product->max_reference_images)),
                'max_size_mb' => 10,
                'accept' => 'image/*',
            ]));
        }

        return $fields->all();
    }

    public function promptFields(Product $product): array
    {
        $raw = collect($product->input_schema ?? [])
            ->filter(fn ($field) => is_array($field))
            ->map(fn (array $field) => $this->normalizeField($field))
            ->values();
        return $raw->isNotEmpty() ? $raw->all() : $this->fields($product);
    }

    public function pageData(Product $product): array
    {
        return [
            'name' => $product->name_fa ?: $product->name_en,
            'description' => $product->description_fa ?: $product->description_en ?: 'تنظیمات را کامل کنید و خروجی اختصاصی خود را بسازید.',
            'cover' => $product->displayImageUrl(),
            'cost' => (int) ($product->credit_cost ?? 0),
            'identity' => [
                'available' => (bool) $product->identity_preservation,
                'extra_cost' => max(0, (int) $product->identity_credit_cost),
                'max_images' => min(3, max(2, (int) ($product->max_reference_images ?: 3))),
            ],
            'estimated_time' => $product->estimated_time ? 'حدود ' . number_format((int) $product->estimated_time) . ' ثانیه' : 'حدود یک دقیقه',
            'output_count' => max(1, (int) ($product->output_count ?? 1)),
            'output_variants' => $product->outputVariantList(),
            'output_aspect_ratios' => $product->allowedAspectRatioList(),
            'default_output_aspect_ratio' => $product->defaultOutputAspectRatio(),
            'output_resolutions' => $product->allowedResolutionList(),
            'default_output_resolution' => $product->defaultOutputResolution(),
            'fields' => $this->fields($product),
            'download_track_url' => route('app.product.download', $product->slug),
            'generate_url' => route('app.create.generate', $product->route_slug),
            'login_url' => route('login', ['redirect' => request()->fullUrl()]),
            'is_authenticated' => auth()->check(),
        ];
    }

    public function rules(Product $product): array
    {
        $rules = [
            'fields' => ['nullable', 'array'], 'uploads' => ['nullable', 'array'], 'variants' => ['nullable', 'array'],
            'output' => ['nullable', 'array'],
            'output.aspect_ratio' => ['nullable', Rule::in($product->allowedAspectRatioList())],
            'output.quality' => ['nullable', Rule::in($product->allowedResolutionList())],
            'identity_preservation' => ['nullable', 'boolean'],
        ];
        foreach ($this->fields($product) as $field) {
            if ($this->isLayout($field['type'])) continue;
            $required = $field['required'] && $this->isVisible($field, (array) request()->input('fields', [])) ? 'required' : 'nullable';
            $key = "fields.{$field['id']}";

            if (in_array($field['type'], ['image_upload', 'multi_image', 'file_upload'], true)) {
                $uploadKey = "uploads.{$field['id']}";
                $rules[$uploadKey] = [$required];
                $maxKb = max(1, (int) ($field['max_size_mb'] ?: 10)) * 1024;
                $fileRules = ['file', "max:{$maxKb}"];
                if ($field['type'] !== 'file_upload') $fileRules[] = 'image';
                if ($field['type'] === 'multi_image') {
                    $rules[$uploadKey] = [$required, 'array', 'max:' . max(1, (int) ($field['max_files'] ?: 4))];
                    $rules[$uploadKey . '.*'] = $fileRules;
                } else {
                    $rules[$uploadKey] = array_merge([$required], $fileRules);
                }
                continue;
            }

            if (in_array($field['type'], ['multi_select'], true)) {
                $rules[$key] = [$required, 'array'];
                if ($field['min'] !== '') $rules[$key][] = 'min:' . (int) $field['min'];
                if ($field['max'] !== '') $rules[$key][] = 'max:' . (int) $field['max'];
                $allowed = collect($field['options'])->pluck('value')->filter()->values()->all();
                if ($allowed !== []) $rules[$key . '.*'] = ['string', Rule::in($allowed)];
                continue;
            }

            if (in_array($field['type'], ['number', 'slider', 'strength', 'seed'], true)) {
                $rules[$key] = [$required, 'numeric'];
                if ($field['min'] !== '') $rules[$key][] = 'min:' . $field['min'];
                if ($field['max'] !== '') $rules[$key][] = 'max:' . $field['max'];
                continue;
            }

            if (in_array($field['type'], ['switch', 'checkbox'], true)) {
                $rules[$key] = $field['type'] === 'checkbox' && $field['required'] ? ['accepted'] : [$required, 'boolean'];
                continue;
            }

            $rules[$key] = [$required, 'string'];
            if ($field['min'] !== '') $rules[$key][] = 'min:' . (int) $field['min'];
            if ($field['max'] !== '') $rules[$key][] = 'max:' . (int) $field['max'];
            $allowed = collect($field['options'])->pluck('value')->filter()->values()->all();
            if ($allowed !== []) $rules[$key][] = Rule::in($allowed);
            if ($field['regex'] !== '') {
                $pattern = $field['regex'];
                $rules[$key][] = function (string $attribute, mixed $value, \Closure $fail) use ($pattern) {
                    $regex = str_starts_with($pattern, '/') ? $pattern : '/' . str_replace('/', '\\/', $pattern) . '/u';
                    if (@preg_match($regex, (string) $value) !== 1) $fail('فرمت مقدار واردشده معتبر نیست.');
                };
            }
        }
        return $rules;
    }

    public function additionalCredit(Product $product, array $values): int
    {
        $total = 0;
        foreach ($this->fields($product) as $field) {
            $value = Arr::get($values, $field['id']);
            if ($this->hasValue($value)) $total += max(0, (int) $field['credit_cost']);
            foreach ($field['options'] as $option) {
                $selected = is_array($value) ? in_array($option['value'], $value, true) : (string) $value === (string) $option['value'];
                if ($selected) $total += max(0, (int) ($option['credit'] ?: 0));
            }
        }
        return $total;
    }

    public function flattenUploads(Request $request): array
    {
        return collect(Arr::flatten((array) $request->file('uploads', [])))->filter()->values()->all();
    }

    public function isVisible(array $field, array $values): bool
    {
        $condition = $field['show_if'] ?? [];
        if (empty($condition['field'])) return true;
        $actual = $values[$condition['field']] ?? null;
        $items = is_array($actual) ? array_map('strval', $actual) : [(string) $actual];
        $expected = (string) ($condition['value'] ?? '');
        return match ($condition['op'] ?? 'eq') {
            'neq' => !in_array($expected, $items, true),
            'has' => collect($items)->contains(fn (string $item) => str_contains($item, $expected)),
            'not_empty' => collect($items)->contains(fn (string $item) => $item !== ''),
            default => in_array($expected, $items, true),
        };
    }

    private function normalizeField(array $field): array
    {
        $options = collect($field['options'] ?? [])->map(function ($option) {
            $option = is_array($option) ? $option : ['value' => $option, 'label' => $option];
            $image = (string) ($option['image'] ?? '');
            if ($image !== '' && !Str::startsWith($image, ['http://', 'https://', '/'])) $image = asset('storage/' . ltrim($image, '/'));
            return [
                'value' => (string) ($option['value'] ?? $option['label'] ?? ''),
                'label' => (string) ($option['label'] ?? $option['value'] ?? ''),
                'prompt' => (string) ($option['prompt'] ?? ''),
                'credit' => $option['credit'] ?? 0,
                'image' => $image,
                'meta' => ((int) ($option['credit'] ?? 0) > 0) ? '+ ' . (int) $option['credit'] . ' توکن' : '',
            ];
        })->values()->all();

        return [
            'id' => (string) ($field['field_id'] ?? ''),
            'type' => (string) ($field['type'] ?? 'text'),
            'label' => (string) ($field['label_fa'] ?? ''),
            'help' => (string) ($field['description'] ?? $field['help_text'] ?? ''),
            'placeholder' => (string) ($field['placeholder'] ?? ''),
            'required' => (string) ($field['required'] ?? '0') === '1' || ($field['required'] ?? false) === true,
            'hidden' => (string) ($field['hidden'] ?? '0') === '1' || ($field['hidden'] ?? false) === true,
            'value' => $field['default'] ?? '',
            'min' => $field['min'] ?? '', 'max' => $field['max'] ?? '', 'step' => $field['step'] ?? '',
            'unit' => (string) ($field['unit'] ?? ''), 'regex' => (string) ($field['regex'] ?? ''),
            'max_files' => $field['max_files'] ?? '', 'max_size_mb' => $field['max_size_mb'] ?? 10,
            'accept' => (string) ($field['accept'] ?? ''), 'credit_cost' => $field['credit_cost'] ?? 0,
            'prompt_mode' => (string) ($field['prompt_mode'] ?? 'token'), 'prompt_wrap' => (string) ($field['prompt_wrap'] ?? ''),
            'show_if' => is_array($field['show_if'] ?? null) ? $field['show_if'] : [], 'options' => $options,
        ];
    }

    private function isLayout(string $type): bool { return in_array($type, ['section', 'divider', 'info'], true); }
    private function hasValue(mixed $value): bool { return is_array($value) ? $value !== [] : !in_array($value, [null, '', false, '0', 0], true); }
    private function legacyLabel(string $token): string
    {
        return match ($token) {
            'input_photo', 'photo', 'image', 'input_image' => 'تصویر اصلی',
            'background' => 'پس‌زمینه دلخواه',
            'prompt' => 'توضیحات شما',
            'style' => 'استایل دلخواه',
            default => str_replace('_', ' ', $token),
        };
    }
}
