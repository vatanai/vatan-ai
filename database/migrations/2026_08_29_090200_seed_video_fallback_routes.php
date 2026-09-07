<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('products')->where('media_type', 'video')->where('slug', 'like', 'video-studio-%')->get() as $product) {
            $workflow = data_get(json_decode((string) $product->provider_options, true) ?: [], 'video.workflow');
            $isImage = $workflow === 'image_to_video';
            $fallbacks = $isImage
                ? [['provider' => 'fal', 'model' => 'fal-ai/kling-video/v3/turbo/pro/image-to-video']]
                : [['provider' => 'openrouter', 'model' => 'kwaivgi/kling-v2.5-turbo'], ['provider' => 'openrouter', 'model' => 'runwayml/gen-4-turbo']];
            $fallbacks = array_values(array_filter($fallbacks, fn ($fallback) => DB::table('ai_models')->where('provider', $fallback['provider'])->where('openrouter_model_id', $fallback['model'])->where('is_active', true)->exists()));
            DB::table('products')->where('id', $product->id)->update([
                'fallback_models' => json_encode(array_column($fallbacks, 'model')),
                'fallback_model_providers' => json_encode(array_column($fallbacks, 'provider')),
                'updated_at' => now(),
            ]);
        }
    }
    public function down(): void {}
};
