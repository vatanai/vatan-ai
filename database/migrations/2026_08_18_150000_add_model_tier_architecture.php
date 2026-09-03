<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_tier_defaults')) {
            Schema::create('model_tier_defaults', function (Blueprint $table): void {
                $table->id();
                $table->string('tier_key')->unique();
                $table->string('name');
                $table->string('description', 300)->nullable();
                $table->unsignedTinyInteger('grade');
                $table->string('primary_model_id')->nullable();
                $table->string('primary_provider', 40)->nullable();
                $table->string('fallback_model_id')->nullable();
                $table->string('fallback_provider', 40)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('plans', 'model_tier_key')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->string('model_tier_key', 30)->default('free')->index();
                $table->boolean('show_model_tier')->default(true);
            });
        }

        if (! Schema::hasColumn('orders', 'model_tier_key')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('model_tier_key', 30)->nullable()->index();
                $table->string('model_tier_name')->nullable();
                $table->string('plan_name')->nullable();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        $tiers = [
            'free' => ['name' => 'رایگان', 'description' => 'مدل اقتصادی برای اعتبار هدیه و شروع کار', 'grade' => 4],
            'economy' => ['name' => 'اقتصادی', 'description' => 'تعادل کیفیت و هزینه برای استفاده روزمره', 'grade' => 3],
            'pro' => ['name' => 'حرفه‌ای', 'description' => 'کیفیت بالاتر برای خروجی‌های حرفه‌ای', 'grade' => 2],
            'business' => ['name' => 'بیزینس', 'description' => 'بهترین مدل‌های فعال برای خروجی‌های کلیدی', 'grade' => 1],
        ];

        foreach ($tiers as $key => $tier) {
            $exists = DB::table('model_tier_defaults')->where('tier_key', $key)->exists();
            if ($exists) continue;

            $models = DB::table('ai_models')
                ->where('is_active', true)
                ->where('output_modality', 'image')
                ->where('featured_in_lab', true)
                ->get(['openrouter_model_id', 'provider', 'capability_config', 'pricing_config', 'lab_priority']);
            $matching = $models->filter(function ($model) use ($tier) {
                $capabilities = json_decode($model->capability_config ?: '{}', true) ?: [];
                $pricing = json_decode($model->pricing_config ?: '{}', true) ?: [];
                $score = $capabilities['quality_score'] ?? $pricing['quality_score'] ?? 10;
                $grade = $score >= 9.5 ? 1 : ($score >= 9 ? 2 : ($score >= 8.5 ? 3 : 4));
                return $grade === $tier['grade'];
            })->sortBy('lab_priority')->values();
            $primary = $matching->filter(fn ($model) => (float) ($model->cost_per_generation_usd ?? 0) > 0)
                ->sortBy('lab_priority')
                ->first() ?? $matching->first();
            $fallback = $matching->first(fn ($model) => $model->provider !== ($primary->provider ?? null));
            $primary ??= $models->sortBy('lab_priority')->first();
            $fallback ??= $models
                ->filter(fn ($model) => $model->provider !== ($primary->provider ?? null))
                ->sortBy('lab_priority')
                ->first();

            DB::table('model_tier_defaults')->insert([
                'tier_key' => $key,
                'name' => $tier['name'],
                'description' => $tier['description'],
                'grade' => $tier['grade'],
                'primary_model_id' => $primary->openrouter_model_id ?? null,
                'primary_provider' => $primary->provider ?? null,
                'fallback_model_id' => $fallback->openrouter_model_id ?? null,
                'fallback_provider' => $fallback->provider ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('plans')->where('slug', 'free')->update(['model_tier_key' => 'free']);
        DB::table('plans')->where('slug', 'start')->update(['model_tier_key' => 'economy']);
        DB::table('plans')->where('slug', 'pro')->update(['model_tier_key' => 'pro']);
        DB::table('plans')->whereIn('slug', ['premium', 'business', 'enterprise'])->update(['model_tier_key' => 'business']);
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'plan_id')) {
                Schema::table('orders', function (Blueprint $table): void {
                    $table->dropForeign(['plan_id']);
                });
            }
            Schema::table('orders', function (Blueprint $table): void {
                $columns = array_filter([
                    Schema::hasColumn('orders', 'model_tier_key') ? 'model_tier_key' : null,
                    Schema::hasColumn('orders', 'model_tier_name') ? 'model_tier_name' : null,
                    Schema::hasColumn('orders', 'plan_name') ? 'plan_name' : null,
                    Schema::hasColumn('orders', 'plan_id') ? 'plan_id' : null,
                ]);
                if ($columns) $table->dropColumn($columns);
            });
        }

        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table): void {
                $columns = array_filter([
                    Schema::hasColumn('plans', 'model_tier_key') ? 'model_tier_key' : null,
                    Schema::hasColumn('plans', 'show_model_tier') ? 'show_model_tier' : null,
                ]);
                if ($columns) $table->dropColumn($columns);
            });
        }

        Schema::dropIfExists('model_tier_defaults');
    }
};
