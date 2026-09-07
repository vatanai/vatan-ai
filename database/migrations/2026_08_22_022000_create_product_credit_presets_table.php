<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_credit_presets')) {
            Schema::create('product_credit_presets', function (Blueprint $table): void {
                $table->id();
                $table->string('preset_key', 64)->unique();
                $table->string('name', 100);
                $table->unsignedInteger('standard_credit_cost')->default(Product::DEFAULT_QUALITY_CREDIT_COSTS['standard']);
                $table->unsignedInteger('professional_credit_cost')->default(Product::DEFAULT_QUALITY_CREDIT_COSTS['professional']);
                $table->unsignedInteger('best_credit_cost')->default(Product::DEFAULT_QUALITY_CREDIT_COSTS['best']);
                $table->boolean('is_default_for_product_creation')->default(false);
                $table->timestamps();
            });
        }

        $source = Schema::hasTable('model_quality_presets')
            ? DB::table('model_quality_presets')->orderBy('id')->get()
            : collect();

        if ($source->isEmpty()) {
            $source = collect([1, 2, 3, 4])->map(fn (int $number) => (object) [
                'preset_key' => "preset_{$number}",
                'name' => $number === 3 ? 'آزمایش شده' : "پیش‌فرض {$number}",
                'configuration' => null,
                'is_default_for_product_creation' => $number === 3,
            ]);
        }

        foreach ($source as $index => $preset) {
            $configuration = is_array($preset->configuration ?? null)
                ? $preset->configuration
                : json_decode((string) ($preset->configuration ?? ''), true);
            $configured = (array) data_get($configuration, 'quality_credit_costs', []);
            $key = trim((string) ($preset->preset_key ?? '')) ?: 'preset_' . ((int) $index + 1);
            $costs = collect(Product::DEFAULT_QUALITY_CREDIT_COSTS)
                ->mapWithKeys(fn (int $default, string $quality) => [$quality => (is_numeric($configured[$quality] ?? null) && (int) $configured[$quality] > 0) ? (int) $configured[$quality] : $default])
                ->all();

            DB::table('product_credit_presets')->updateOrInsert(
                ['preset_key' => $key],
                [
                    'name' => trim((string) ($preset->name ?? 'پیش‌فرض ' . ((int) $index + 1))) ?: 'پیش‌فرض',
                    'standard_credit_cost' => $costs['standard'],
                    'professional_credit_cost' => $costs['professional'],
                    'best_credit_cost' => $costs['best'],
                    'is_default_for_product_creation' => (bool) ($preset->is_default_for_product_creation ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (! DB::table('product_credit_presets')->where('is_default_for_product_creation', true)->exists()) {
            $fallback = DB::table('product_credit_presets')->where('preset_key', 'preset_3')->first()
                ?: DB::table('product_credit_presets')->orderBy('id')->first();
            if ($fallback) {
                DB::table('product_credit_presets')->update(['is_default_for_product_creation' => false]);
                DB::table('product_credit_presets')->where('id', $fallback->id)->update(['is_default_for_product_creation' => true, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_credit_presets');
    }
};
