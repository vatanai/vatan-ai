<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('products')) {
            return;
        }

        $product = DB::table('products')
            ->where('slug', 'video-studio-memory-alive')
            ->first();

        if (! $product) {
            return;
        }

        $prompt = 'Animate the provided first-frame image as a natural candid moment from 1980s America. Preserve the subject identity, facial features, clothing, environment, composition, lighting, and authentic 1980s aesthetic exactly. Introduce only subtle, realistic, context-appropriate movement with natural body language and believable interaction with the environment. Keep camera movement cinematic and restrained. Use authentic analog film grain, vintage color, soft imperfections, and realistic motion. No dialogue. Keep every face exactly consistent with the first frame throughout the entire animation. Use the input image original aspect ratio exactly; do not crop, pad, stretch, reframe, letterbox, or pillarbox the image.';
        $systemPrompt = 'Animate the first frame with restrained natural motion. Preserve identity, facial geometry, clothing, environment, composition, and lighting across every frame. The output must use the input image dimensions and aspect ratio exactly; never crop, stretch, pad, letterbox, pillarbox, or reframe.';
        $negativePrompt = 'identity drift, altered face, changed age, changed clothing, extra limbs, warped hands, flicker, jitter, unstable geometry, exaggerated motion, modern elements, dialogue, subtitles, text, watermark, crop, stretch, padding, letterbox, pillarbox, camera shake';
        $providerOptions = [
            'video' => [
                'fps' => 24,
                'workflow' => 'image_to_video',
                'durations' => [4, 6, 8],
                'resolutions' => ['480p', '720p', '1080p', '4K'],
                'aspect_ratios' => ['16:9', '9:16', '1:1'],
                'audio_allowed' => false,
                'audio_default' => false,
                'quality_costs' => ['4K' => 10, '480p' => 0, '580p' => 1, '720p' => 2, '1080p' => 5],
                'quality_tiers' => [
                    ['key' => 'standard', 'label' => 'استاندارد', 'surcharge' => 0, 'resolution' => '720p'],
                    ['key' => 'professional', 'label' => 'حرفه‌ای', 'surcharge' => 8, 'resolution' => '1080p'],
                    ['key' => 'best', 'label' => 'بهترین خروجی', 'surcharge' => 38, 'resolution' => '4K'],
                ],
                'model_defaults' => [],
                'motion_presets' => [
                    ['key' => 'static', 'label' => 'قاب ثابت', 'prompt' => 'Locked-off camera, stable composition, natural subject motion.', 'description' => 'حرکت طبیعی در قاب ثابت'],
                    ['key' => 'dolly_in', 'label' => 'حرکت رو به جلو', 'prompt' => 'Slow cinematic dolly-in toward the subject, smooth controlled camera motion.', 'description' => 'نزدیک‌شدن نرم دوربین'],
                ],
                'prompt_enhance' => true,
                'default_duration' => 6,
                'face_profile_mode' => 'optional',
                'default_resolution' => '720p',
                'default_aspect_ratio' => '16:9',
                'quality_credit_costs' => ['best' => 50, 'standard' => 12, 'professional' => 20],
                'credit_costs_by_duration' => ['4' => 6, '6' => 10, '8' => 14],
                'allow_promotional_credits' => true,
                'preserve_source_aspect_ratio' => true,
            ],
        ];

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'name_fa' => 'متحرک‌سازی عکس قدیمی',
                'name_en' => 'Animate an Old Photo',
                'description_fa' => 'عکس‌های قدیمی را با حرکت‌های ظریف و طبیعی زنده کنید؛ هویت چهره، لباس، محیط و حال‌وهوای اصیل دهه ۱۹۸۰ بدون تغییر حفظ می‌شود.',
                'description_en' => 'Animate an old photograph with subtle, natural movement while preserving identity, composition, and the authentic 1980s atmosphere.',
                'category' => 'ویدیوهای آماده هوش مصنوعی',
                'subcategory' => 'عکس به ویدیو',
                'media_type' => 'video',
                'preview_video_url' => 'assets/videos/old-photo-motion.mp4',
                'sample_outputs' => json_encode(['assets/videos/old-photo-motion.mp4'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'primary_model' => 'fal-ai/wan/v2.2-a14b/image-to-video/turbo',
                'ai_provider' => 'fal',
                'fallback_models' => json_encode(['fal-ai/kling-video/v3/turbo/pro/image-to-video'], JSON_UNESCAPED_SLASHES),
                'fallback_model_providers' => json_encode(['fal']),
                'prompt_template' => $prompt,
                'system_prompt' => $systemPrompt,
                'negative_prompt' => $negativePrompt,
                'output_type' => 'video',
                'output_format' => 'mp4',
                'resolution' => '720',
                'allowed_resolutions' => json_encode(['480', '720', '1080', '2160']),
                'aspect_ratio' => '16:9',
                'allowed_aspect_ratios' => json_encode(['16:9', '9:16', '1:1', '4:3', '3:4', '4:5', '2:3', '3:2']),
                'delivery_method' => 'queued',
                'estimated_time' => 180,
                'pricing_model' => 'per_credit',
                'credit_cost' => 6,
                'price_tier' => 'standard',
                'provider_options' => json_encode($providerOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        if (DB::getSchemaBuilder()->hasTable('product_video_relations')) {
            $photoId = DB::table('products')->where('slug', 'ai-fashion-portrait')->value('id');
            if ($photoId) {
                DB::table('product_video_relations')->updateOrInsert(
                    ['video_product_id' => $product->id, 'photo_product_id' => $photoId],
                    ['sort_order' => 0, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // این مهاجرت دادهٔ نمونهٔ محصول را عمداً حذف نمی‌کند.
    }
};
