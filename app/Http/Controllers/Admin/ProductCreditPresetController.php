<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCreditPreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCreditPresetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(true));
        $preset = DB::transaction(function () use ($data): ProductCreditPreset {
            $key = 'credit_preset_' . Str::lower(Str::random(12));
            while (ProductCreditPreset::query()->where('preset_key', $key)->exists()) {
                $key = 'credit_preset_' . Str::lower(Str::random(12));
            }
            $preset = ProductCreditPreset::create([
                'preset_key' => $key,
                'name' => trim((string) $data['name']),
                ...$this->costColumns($data['costs']),
                'is_default_for_product_creation' => (bool) ($data['is_default_for_product_creation'] ?? false),
            ]);
            if ($preset->is_default_for_product_creation) {
                $this->clearDefaultsExcept($preset);
            }
            return $preset;
        });

        return $this->response($preset, 'پیش‌فرض مصرف اعتبار جدید اضافه شد.');
    }

    public function update(Request $request, ProductCreditPreset $productCreditPreset): JsonResponse
    {
        $data = $request->validate($this->rules(false));
        $preset = DB::transaction(function () use ($data, $productCreditPreset): ProductCreditPreset {
            $updates = [];
            if (array_key_exists('name', $data)) {
                $updates['name'] = trim((string) $data['name']) ?: $productCreditPreset->name;
            }
            if (array_key_exists('costs', $data)) {
                $updates += $this->costColumns(array_replace($productCreditPreset->costs(), (array) $data['costs']));
            }
            if (array_key_exists('is_default_for_product_creation', $data)) {
                $updates['is_default_for_product_creation'] = (bool) $data['is_default_for_product_creation'];
            }
            if ($updates) $productCreditPreset->update($updates);
            if (($data['is_default_for_product_creation'] ?? false) === true) {
                $this->clearDefaultsExcept($productCreditPreset);
            } elseif (! ProductCreditPreset::query()->where('is_default_for_product_creation', true)->exists()) {
                $productCreditPreset->update(['is_default_for_product_creation' => true]);
            }
            return $productCreditPreset->fresh();
        });

        return $this->response($preset, 'پیش‌فرض مصرف اعتبار ذخیره شد.');
    }

    public function destroy(ProductCreditPreset $productCreditPreset): JsonResponse
    {
        if (ProductCreditPreset::query()->count() <= 1) {
            throw ValidationException::withMessages(['preset' => 'حداقل یک پیش‌فرض مصرف اعتبار باید باقی بماند.']);
        }

        DB::transaction(function () use ($productCreditPreset): void {
            $replacement = ProductCreditPreset::query()
                ->where($productCreditPreset->getKeyName(), '!=', $productCreditPreset->getKey())
                ->orderByDesc('is_default_for_product_creation')
                ->orderBy('id')
                ->firstOrFail();

            Product::query()
                ->where('model_configuration->quality_credit_preset_key', $productCreditPreset->preset_key)
                ->get()
                ->each(function (Product $product) use ($replacement): void {
                    $configuration = (array) $product->model_configuration;
                    $configuration['quality_credit_preset_key'] = $replacement->preset_key;
                    $configuration['quality_credit_costs'] = $replacement->costs();
                    $product->update(['model_configuration' => $configuration]);
                });

            $wasDefault = (bool) $productCreditPreset->is_default_for_product_creation;
            $productCreditPreset->delete();
            if ($wasDefault) {
                ProductCreditPreset::query()->update(['is_default_for_product_creation' => false]);
                $replacement->update(['is_default_for_product_creation' => true]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'پیش‌فرض مصرف اعتبار حذف شد.']);
    }

    private function rules(bool $creating): array
    {
        $name = [$creating ? 'required' : 'sometimes', 'string', 'max:100'];
        $costs = [$creating ? 'required' : 'sometimes', 'array'];
        return [
            'name' => $name,
            'costs' => $costs,
            'costs.standard' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:1000000'],
            'costs.professional' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:1000000'],
            'costs.best' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:1000000'],
            'is_default_for_product_creation' => ['sometimes', 'boolean'],
        ];
    }

    private function costColumns(array $costs): array
    {
        return [
            'standard_credit_cost' => (int) $costs['standard'],
            'professional_credit_cost' => (int) $costs['professional'],
            'best_credit_cost' => (int) $costs['best'],
        ];
    }

    private function clearDefaultsExcept(ProductCreditPreset $selected): void
    {
        ProductCreditPreset::query()
            ->where($selected->getKeyName(), '!=', $selected->getKey())
            ->where('is_default_for_product_creation', true)
            ->update(['is_default_for_product_creation' => false]);
    }

    private function response(ProductCreditPreset $preset, string $message): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'preset' => $preset,
            'costs' => $preset->costs(),
            'update_url' => route('admin.product-credit-presets.update', $preset),
            'delete_url' => route('admin.product-credit-presets.destroy', $preset),
            'message' => $message,
        ]);
    }
}
