<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const POLICY = [
        'enabled' => true,
        'primary_max_attempts' => 3,
        'primary_retry_delays_seconds' => [5, 10],
        'fallback_max_attempts' => 1,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        DB::table('model_quality_presets')->orderBy('id')->each(function (object $preset): void {
            $configuration = json_decode((string) $preset->configuration, true);
            $configuration = is_array($configuration) ? $configuration : [];
            $configuration['image_retry_policy'] = array_replace(
                self::POLICY,
                (array) ($configuration['image_retry_policy'] ?? []),
            );

            DB::table('model_quality_presets')->where('id', $preset->id)->update([
                'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        DB::table('model_quality_presets')->orderBy('id')->each(function (object $preset): void {
            $configuration = json_decode((string) $preset->configuration, true);
            if (! is_array($configuration) || ! array_key_exists('image_retry_policy', $configuration)) {
                return;
            }

            unset($configuration['image_retry_policy']);
            DB::table('model_quality_presets')->where('id', $preset->id)->update([
                'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        });
    }
};
