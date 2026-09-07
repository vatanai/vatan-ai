<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('products')) return;
        $slugs = DB::table('products')->where('media_type', 'video')->where('slug', 'like', 'video-studio-%')->orderBy('id')->limit(5)->pluck('slug');
        foreach ($slugs as $slug) {
            $product = DB::table('products')->where('slug', $slug)->first();
            $video = json_decode((string) ($product->provider_options ?? '{}'), true) ?: [];
            $config = (array) ($video['video'] ?? []);
            $config['resolutions'] = array_values(array_unique(array_merge((array) ($config['resolutions'] ?? ['480p', '720p']), ['1080p', '4K'])));
            $config['quality_costs'] = ['480p' => 0, '580p' => 1, '720p' => 2, '1080p' => 5, '4K' => 10];
            $config['quality_tiers'] = [
                ['key' => 'standard', 'label' => 'استاندارد', 'resolution' => '720p', 'surcharge' => 0],
                ['key' => 'professional', 'label' => 'حرفه‌ای', 'resolution' => '1080p', 'surcharge' => 5],
                ['key' => 'best', 'label' => 'بهترین خروجی', 'resolution' => '4K', 'surcharge' => 10],
            ];
            $video['video'] = $config;
            DB::table('products')->where('id', $product->id)->update([
                'provider_options' => json_encode($video, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'allowed_resolutions' => json_encode(['480', '580', '720', '1080', '4K'], JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
