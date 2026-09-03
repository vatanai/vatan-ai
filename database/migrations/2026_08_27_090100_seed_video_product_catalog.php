<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('ai_models')) return;

        $textModel = DB::table('ai_models')
            ->where('is_active', true)
            ->where('provider', 'fal')
            ->where('external_model_id', 'fal-ai/wan/v2.2-5b/text-to-video/fast-wan')
            ->first()
            ?: DB::table('ai_models')->where('is_active', true)->where('output_modality', 'video')->where('task_type', 'text_to_video')->whereIn('provider', ['fal', 'replicate'])->first();
        $imageModel = DB::table('ai_models')
            ->where('is_active', true)
            ->where('provider', 'fal')
            ->where('external_model_id', 'fal-ai/wan/v2.2-a14b/image-to-video/turbo')
            ->first()
            ?: DB::table('ai_models')->where('is_active', true)->where('output_modality', 'video')->where('task_type', 'image_to_video')->whereIn('provider', ['fal', 'replicate'])->first();
        if (!$textModel || !$imageModel) return;

        $category = DB::table('categories')->where('name_fa', 'ویدیوهای آماده هوش مصنوعی')->first()
            ?: DB::table('categories')->where('name', 'ویدیوهای آماده هوش مصنوعی')->first()
            ?: DB::table('categories')->orderBy('id')->first();
        $categoryId = $category?->id;
        $categoryName = $category?->name_fa ?: $category?->name ?: 'ویدیوهای آماده هوش مصنوعی';
        $videos = [
            '/assets/videos/a1be8a17-0f52-44e3-8693-6f2d7a3056b2.mp4',
            '/assets/videos/223e22d8-e83b-4862-9813-cdc873688f9b.mp4',
            '/assets/videos/94debe64-1efa-4ef5-a881-1c441f84d10a.mp4',
            '/assets/videos/60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4',
        ];
        $covers = [
            'assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif',
            'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
            'assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg',
            'assets/img/prompt-for-gemini-ai-girl.webp',
            'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
            'assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg',
            'assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg',
            'assets/img/ai-photo-editor-prompt.webp',
            'assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp',
            'assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif',
        ];

        $items = [
            ['portrait-motion', 'حرکت سینمایی چهره', 'Cinematic Face Motion', 'image_to_video', 'required', 'چهره ذخیره‌شده را با حرکت طبیعی سر، مو و نور به یک پرتره زنده تبدیل می‌کند.', ['portrait', 'face', 'cinematic']],
            ['product-orbit', 'چرخش تبلیغاتی محصول', 'Product Orbit Video', 'image_to_video', 'disabled', 'از یک عکس محصول، شات تبلیغاتی با حرکت مداری دوربین می‌سازد.', ['product', 'advertising', 'orbit']],
            ['reels-story', 'ریلز داستانی عمودی', 'Vertical Story Reel', 'text_to_video', 'disabled', 'ایده کوتاه شما را به ویدیوی عمودی آماده شبکه‌های اجتماعی تبدیل می‌کند.', ['reels', 'social', 'vertical']],
            ['fashion-walk', 'کت‌واک هوش مصنوعی', 'AI Fashion Walk', 'image_to_video', 'optional', 'عکس استایل یا چهره را به حرکت کت‌واک نرم و ادیتوریال تبدیل می‌کند.', ['fashion', 'portrait', 'editorial']],
            ['cinematic-scene', 'صحنه سینمایی از متن', 'Text to Cinematic Scene', 'text_to_video', 'disabled', 'سناریوی متنی را با قاب‌بندی و حرکت دوربین سینمایی به ویدیو تبدیل می‌کند.', ['cinematic', 'story', 'text-to-video']],
            ['memory-alive', 'زنده‌کردن خاطره', 'Bring a Memory to Life', 'image_to_video', 'optional', 'عکس قدیمی یا پرتره را با حرکت کنترل‌شده و حفظ حالت اصلی زنده می‌کند.', ['memory', 'portrait', 'animation']],
            ['brand-teaser', 'تیزر کوتاه برند', 'Brand Teaser', 'text_to_video', 'disabled', 'پیام برند را به یک تیزر کوتاه، تمیز و آماده انتشار تبدیل می‌کند.', ['brand', 'teaser', 'business']],
            ['travel-postcard', 'کارت‌پستال متحرک سفر', 'Animated Travel Postcard', 'image_to_video', 'disabled', 'عکس منظره را با عمق، پارالاکس و حرکت نرم دوربین متحرک می‌کند.', ['travel', 'landscape', 'parallax']],
            ['dream-sequence', 'سکانس رویایی', 'Dream Sequence', 'text_to_video', 'disabled', 'توصیف خلاقانه شما را به سکانسی شاعرانه و خیال‌انگیز تبدیل می‌کند.', ['dream', 'creative', 'fantasy']],
            ['logo-reveal', 'نمایش سینمایی لوگو', 'Cinematic Logo Reveal', 'image_to_video', 'disabled', 'لوگو یا نشانه برند را با نور و حرکت کنترل‌شده معرفی می‌کند.', ['logo', 'brand', 'reveal']],
        ];

        foreach ($items as $index => [$suffix, $nameFa, $nameEn, $workflow, $faceMode, $description, $tags]) {
            $slug = 'video-studio-' . $suffix;
            $model = $workflow === 'image_to_video' ? $imageModel : $textModel;
            $durations = $workflow === 'text_to_video' ? [2, 4, 6] : [4, 6, 8];
            $defaultDuration = $durations[0];
            $features = [
                [
                    'field_id' => 'creative_direction', 'type' => 'textarea', 'label_fa' => 'جزئیات صحنه و حرکت',
                    'description' => 'حرکت سوژه، فضا و اتفاق موردنظر را کوتاه و روشن بنویسید.', 'placeholder' => 'مثلاً نور غروب، حرکت آرام موها و نگاه به دوربین...',
                    'required' => '1', 'hidden' => '0', 'default' => '', 'credit_cost' => 0, 'prompt_mode' => 'append', 'prompt_wrap' => 'Creative direction: {value}', 'options' => [], 'order' => 0,
                ],
                [
                    'field_id' => 'visual_style', 'type' => 'select', 'label_fa' => 'استایل بصری',
                    'description' => 'حال‌وهوای کلی خروجی را انتخاب کنید.', 'required' => '1', 'hidden' => '0', 'default' => 'cinematic', 'credit_cost' => 0, 'prompt_mode' => 'append', 'prompt_wrap' => 'Visual style: {value}',
                    'options' => [
                        ['label' => 'سینمایی', 'value' => 'cinematic', 'prompt' => 'Cinematic lighting, filmic color grade, realistic motion.', 'credit' => 0],
                        ['label' => 'تبلیغاتی', 'value' => 'commercial', 'prompt' => 'Premium commercial look, crisp controlled lighting.', 'credit' => 0],
                        ['label' => 'طبیعی', 'value' => 'natural', 'prompt' => 'Natural light, documentary realism, subtle movement.', 'credit' => 0],
                    ],
                    'order' => 1,
                ],
                [
                    'field_id' => 'motion_intensity', 'type' => 'slider', 'label_fa' => 'شدت حرکت',
                    'description' => 'مقدار حرکت سوژه و دوربین را کنترل می‌کند.', 'required' => '0', 'hidden' => '0', 'default' => 45, 'min' => 10, 'max' => 90, 'step' => 5, 'unit' => '٪', 'credit_cost' => 0, 'prompt_mode' => 'append', 'prompt_wrap' => 'Motion intensity: {value} percent.', 'options' => [], 'order' => 2,
                ],
            ];
            $configuration = [
                'workflow' => $workflow,
                'face_profile_mode' => $faceMode,
                'durations' => $durations,
                'default_duration' => $defaultDuration,
                'aspect_ratios' => ['9:16', '16:9', '1:1'],
                'default_aspect_ratio' => in_array($suffix, ['reels-story', 'fashion-walk'], true) ? '9:16' : '16:9',
                'resolutions' => ['480p', '720p'],
                'default_resolution' => '480p',
                'fps' => 24,
                'motion_presets' => [
                    ['key' => 'static', 'label' => 'قاب ثابت', 'description' => 'حرکت سوژه در قاب ثابت', 'prompt' => 'Locked-off camera, stable composition, natural subject motion.'],
                    ['key' => 'dolly_in', 'label' => 'حرکت رو به جلو', 'description' => 'نزدیک‌شدن نرم دوربین', 'prompt' => 'Slow cinematic dolly-in toward the subject, smooth controlled camera motion.'],
                    ['key' => 'orbit', 'label' => 'چرخش دور سوژه', 'description' => 'حرکت مداری نرم', 'prompt' => 'Smooth orbital camera move around the subject with consistent geometry.'],
                    ['key' => 'pan', 'label' => 'پن افقی', 'description' => 'حرکت افقی کنترل‌شده', 'prompt' => 'Controlled cinematic horizontal pan, steady speed and natural parallax.'],
                ],
                'audio_allowed' => false,
                'audio_default' => false,
                'prompt_enhance' => true,
                'allow_promotional_credits' => true,
                'credit_costs_by_duration' => array_combine(array_map('strval', $durations), [6, 10, 14]),
                'model_defaults' => $workflow === 'text_to_video' ? [
                    'video_quality' => 'medium', 'video_write_mode' => 'balanced', 'num_interpolated_frames' => 0,
                ] : [],
            ];

            $existing = DB::table('products')->where('slug', $slug)->first();
            $code = $existing?->product_code ?: $this->uniqueProductCode(870100 + $index);
            $now = now();
            $values = [
                'name_fa' => $nameFa,
                'name_en' => $nameEn,
                'slug' => $slug,
                'product_code' => $code,
                'description_fa' => $description,
                'description_en' => $nameEn . ' powered by an asynchronous AI video workflow.',
                'category_id' => $categoryId,
                'category' => $categoryName,
                'subcategory' => $workflow === 'image_to_video' ? 'عکس به ویدیو' : 'متن به ویدیو',
                'status' => 'active',
                'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_featured' => $index < 4,
                'is_new' => true,
                'is_trending' => in_array($index, [0, 2, 4], true),
                'thumbnail' => $covers[$index],
                'cover' => $covers[$index],
                'sample_outputs' => json_encode([$videos[$index % count($videos)]], JSON_UNESCAPED_SLASHES),
                'media_type' => 'video',
                'preview_video_url' => $videos[$index % count($videos)],
                'primary_model' => $model->openrouter_model_id,
                'ai_provider' => $model->provider,
                'timeout' => 600,
                'pipeline_type' => 'video_generation',
                'subject_type' => $faceMode !== 'disabled' ? 'face' : 'generic',
                'identity_preservation' => $faceMode !== 'disabled',
                'min_reference_images' => $faceMode === 'required' ? 1 : 0,
                'max_reference_images' => 3,
                'fallback_models' => json_encode([], JSON_UNESCAPED_SLASHES),
                'fallback_model_providers' => json_encode([], JSON_UNESCAPED_SLASHES),
                'prompt_template' => 'Create a polished short video based on this direction: {creative_direction}.',
                'system_prompt' => 'Preserve subject identity and geometry across all frames. Prefer coherent natural motion and stable temporal detail.',
                'negative_prompt' => 'flicker, jitter, warped face, extra limbs, unstable geometry, unreadable text, abrupt camera shake',
                'provider_options' => json_encode(['video' => $configuration], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'input_schema' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'output_type' => 'video',
                'output_format' => 'mp4',
                'output_count' => 1,
                'resolution' => '480',
                'allowed_resolutions' => json_encode(['480', '720']),
                'aspect_ratio' => $configuration['default_aspect_ratio'],
                'allowed_aspect_ratios' => json_encode(['9:16', '16:9', '1:1']),
                'delivery_method' => 'queued',
                'estimated_time' => 180,
                'watermark_enabled' => false,
                'pricing_model' => 'per_credit',
                'credit_cost' => 6,
                'price_tier' => 'standard',
                'platform' => 'both',
                'new_internal_code' => 'DEMO-VIDEO-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'updated_at' => $now,
            ];
            if (!$existing) $values['created_at'] = $now;

            DB::table('products')->updateOrInsert(['slug' => $slug], $values);
            if ($categoryId && Schema::hasTable('category_product')) {
                $productId = DB::table('products')->where('slug', $slug)->value('id');
                DB::table('category_product')->insertOrIgnore(['category_id' => $categoryId, 'product_id' => $productId]);
            }
        }
    }

    public function down(): void
    {
        // داده‌های نمونه ممکن است بعد از نصب توسط مدیر ویرایش شده باشند؛ rollback
        // ساختار نباید محتوای قابل‌استفاده یا ویرایش‌های مدیر را حذف کند.
    }

    private function uniqueProductCode(int $preferred): string
    {
        for ($offset = 0; $offset < 1000; $offset++) {
            $code = (string) ($preferred + $offset);
            if (!DB::table('products')->where('product_code', $code)->exists()) return $code;
        }

        do {
            $code = (string) random_int(100000, 999999);
        } while (DB::table('products')->where('product_code', $code)->exists());

        return $code;
    }
};
