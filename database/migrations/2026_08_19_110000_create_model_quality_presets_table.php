<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            Schema::create('model_quality_presets', function (Blueprint $table): void {
                $table->id();
                $table->string('preset_key', 32)->unique();
                $table->string('name', 100);
                $table->json('configuration')->nullable();
                $table->timestamps();
            });
        }

        $defaults = Schema::hasTable('model_tier_defaults')
            ? DB::table('model_tier_defaults')->get()->keyBy('tier_key')
            : collect();
        $pair = static function (string $tierKey) use ($defaults): array {
            $tier = $defaults->get($tierKey);
            return [
                'primary' => [
                    'model_id' => $tier?->primary_model_id,
                    'provider' => $tier?->primary_provider,
                ],
                'fallback' => [
                    'model_id' => $tier?->fallback_model_id,
                    'provider' => $tier?->fallback_provider,
                ],
            ];
        };
        $configuration = [
            'quality_models' => [
                'standard' => $pair('economy'),
                'professional' => $pair('pro'),
                'best' => $pair('business'),
            ],
            'free_quality_models' => [
                'standard' => $pair('free'),
                'best' => $pair('business'),
            ],
        ];

        foreach ([1, 2, 3, 4] as $number) {
            DB::table('model_quality_presets')->updateOrInsert(
                ['preset_key' => "preset_{$number}"],
                [
                    'name' => "پیش‌فرض {$number}",
                    'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_quality_presets');
    }
};
