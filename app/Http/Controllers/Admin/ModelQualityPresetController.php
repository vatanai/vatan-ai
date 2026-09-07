<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\ModelQualityPreset;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ModelQualityPresetController extends Controller
{
    public function update(Request $request, ModelQualityPreset $modelQualityPreset): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'configuration' => ['sometimes', 'array'],
            'is_default_for_product_creation' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('configuration', $data)) {
            // فرم گام دوم فقط بخش مدل‌ها را می‌فرستد؛ هزینه‌های سه‌گرید نباید
            // هنگام فیکس‌کردن مدل‌ها بی‌صدا از پیش‌فرض حذف شوند.
            $data['configuration'] = array_replace_recursive(
                (array) $modelQualityPreset->configuration,
                (array) $data['configuration']
            );
            $this->validateConfiguration($data['configuration']);
        }

        DB::transaction(function () use ($data, $modelQualityPreset): void {
            $updates = [];
            if (array_key_exists('name', $data)) {
                $updates['name'] = trim((string) $data['name']) ?: $modelQualityPreset->name;
            }
            if (array_key_exists('configuration', $data)) $updates['configuration'] = $data['configuration'];
            if (array_key_exists('is_default_for_product_creation', $data)) {
                $updates['is_default_for_product_creation'] = (bool) $data['is_default_for_product_creation'];
            }
            if ($updates) $modelQualityPreset->update($updates);
            if (($data['is_default_for_product_creation'] ?? false) === true) {
                static::clearProductCreationDefaultsExcept($modelQualityPreset);
            } elseif (! ModelQualityPreset::query()->where('is_default_for_product_creation', true)->exists()) {
                $modelQualityPreset->update(['is_default_for_product_creation' => true]);
            }
        });

        $preset = $modelQualityPreset->fresh();
        return response()->json([
            'ok' => true,
            'preset' => $preset,
            'update_url' => route('admin.model-quality-presets.update', $preset),
            'delete_url' => route('admin.model-quality-presets.destroy', $preset),
            'message' => 'تنظیمات این پیش‌فرض ذخیره شد.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'configuration' => ['nullable', 'array'],
            'is_default_for_product_creation' => ['nullable', 'boolean'],
        ]);
        $configuration = $data['configuration'] ?? ModelQualityPreset::query()->orderBy('id')->value('configuration');
        $configuration = is_array($configuration) ? $configuration : json_decode((string) $configuration, true);
        $configuration = is_array($configuration) ? $configuration : [];
        $this->validateConfiguration($configuration);

        $preset = DB::transaction(function () use ($data, $configuration): ModelQualityPreset {
            $key = 'preset_' . Str::lower(Str::random(12));
            while (ModelQualityPreset::query()->where('preset_key', $key)->exists()) {
                $key = 'preset_' . Str::lower(Str::random(12));
            }
            $preset = ModelQualityPreset::create([
                'preset_key' => $key,
                'name' => trim($data['name']),
                'configuration' => $configuration,
                'is_default_for_product_creation' => (bool) ($data['is_default_for_product_creation'] ?? false),
            ]);
            if ($preset->is_default_for_product_creation) static::clearProductCreationDefaultsExcept($preset);
            return $preset;
        });

        return response()->json([
            'ok' => true,
            'preset' => $preset,
            'update_url' => route('admin.model-quality-presets.update', $preset),
            'delete_url' => route('admin.model-quality-presets.destroy', $preset),
            'message' => 'پیش‌فرض جدید اضافه شد.',
        ]);
    }

    public function destroy(ModelQualityPreset $modelQualityPreset): JsonResponse
    {
        if (ModelQualityPreset::query()->count() <= 1) {
            throw ValidationException::withMessages(['preset' => 'حداقل یک پیش‌فرض باید در سیستم باقی بماند.']);
        }

        DB::transaction(function () use ($modelQualityPreset): void {
            $replacement = ModelQualityPreset::query()
                ->where($modelQualityPreset->getKeyName(), '!=', $modelQualityPreset->id)
                ->orderByDesc('is_default_for_product_creation')
                ->orderBy('id')
                ->firstOrFail();

            Product::query()
                ->where('model_configuration->quality_preset_key', $modelQualityPreset->preset_key)
                ->get()
                ->each(function (Product $product) use ($replacement): void {
                    $configuration = (array) $product->model_configuration;
                    $configuration['quality_preset_key'] = $replacement->preset_key;
                    $product->update(['model_configuration' => $configuration]);
                });

            $wasDefault = (bool) $modelQualityPreset->is_default_for_product_creation;
            $modelQualityPreset->delete();
            if ($wasDefault) {
                ModelQualityPreset::query()->update(['is_default_for_product_creation' => false]);
                $replacement->update(['is_default_for_product_creation' => true]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'پیش‌فرض حذف شد.']);
    }

    private static function clearProductCreationDefaultsExcept(ModelQualityPreset $selected): void
    {
        ModelQualityPreset::query()
            ->where($selected->getKeyName(), '!=', $selected->id)
            ->where('is_default_for_product_creation', true)
            ->update(['is_default_for_product_creation' => false]);
    }

    private function selectionKeys(): array
    {
        return [
            ['quality_models', 'standard'],
            ['quality_models', 'professional'],
            ['quality_models', 'best'],
            ['free_quality_models', 'standard'],
            ['free_quality_models', 'best'],
        ];
    }

    private function validateConfiguration(array $configuration): void
    {
        $errors = [];
        foreach ($this->selectionKeys() as [$group, $quality]) {
            $prefix = "configuration.{$group}.{$quality}";
            $primary = (array) data_get($configuration, "{$group}.{$quality}.primary", []);
            $fallback = (array) data_get($configuration, "{$group}.{$quality}.fallback", []);
            foreach (['primary' => $primary, 'fallback' => $fallback] as $role => $selection) {
                $valid = AiModel::query()->where('is_active', true)
                        ->where('featured_in_lab', true)
                        ->where('provider', $selection['provider'] ?? null)
                        ->where('openrouter_model_id', $selection['model_id'] ?? null)
                        ->exists();
                if (! $valid) $errors["{$prefix}.{$role}.model_id"] = 'مدل انتخاب‌شده در کاتالوگ فعال و قابل انتخاب نیست.';
            }
            if (($primary['provider'] ?? null) === ($fallback['provider'] ?? null)
                && ($primary['model_id'] ?? null) === ($fallback['model_id'] ?? null)) {
                $errors["{$prefix}.fallback.model_id"] = 'مدل جایگزین نمی‌تواند همان مدل اصلی باشد.';
            }
        }
        if ($errors) throw ValidationException::withMessages($errors);

    }
}
