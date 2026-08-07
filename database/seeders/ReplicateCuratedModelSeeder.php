<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * مدل‌های منتخب Replicate برای جریان تولید وطن.
 *
 * این Seeder عمداً idempotent است و مدل را با کلید provider + external_model_id
 * پیدا می‌کند تا اجرای مجدد، مدل تکراری نسازد. وضعیت فعال/خاموش مدل موجود
 * تنظیم مدیر است و در اجرای مجدد بازنویسی نمی‌شود.
 */
class ReplicateCuratedModelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->models() as $data) {
            $existing = AiModel::query()
                ->where('provider', 'replicate')
                ->where(function ($query) use ($data) {
                    $query->where('external_model_id', $data['external_model_id'])
                        ->orWhere('openrouter_model_id', $data['openrouter_model_id']);
                })
                ->first();

            if (!$existing) {
                AiModel::create($data);
                continue;
            }

            $existing->fill(Arr::except($data, ['is_active']))->save();
        }

        $this->command?->info('✔ مدل‌های منتخب Replicate ثبت/به‌روزرسانی شدند.');
    }

    private function models(): array
    {
        $common = [
            'provider' => 'replicate',
            'provider_name' => 'Replicate',
            'default_width' => 1024,
            'default_height' => 1024,
            'supports_webhook' => true,
            'is_active' => true,
            'commercial_use' => null,
            'last_verified_at' => now(),
        ];

        return [
            array_merge($common, [
                'name' => 'InstantID — حفظ هویت چهره',
                'openrouter_model_id' => 'zsxkib/instant-id',
                'external_model_id' => 'zsxkib/instant-id',
                'external_version' => '2e4785a4d80dadf580077b2244c8d7c05d8e3faac04a04c02d8e099dd2876789',
                'output_modality' => 'image',
                'task_type' => 'face_consistency',
                'supports_image_input' => true,
                'supports_face_identity' => true,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 8,
                'cost_per_generation_usd' => 0.02,
                'default_parameters' => [
                    'scheduler' => 'EulerDiscreteScheduler',
                    'num_inference_steps' => 30,
                    'guidance_scale' => 7.5,
                    'ip_adapter_scale' => 0.8,
                    'controlnet_conditioning_scale' => 0.8,
                    'num_outputs' => 1,
                    'output_format' => 'webp',
                    'output_quality' => 80,
                ],
                'input_schema' => $this->schema([
                    'image' => ['type' => 'string', 'format' => 'uri'],
                    'pose_image' => ['type' => 'string', 'format' => 'uri'],
                    'prompt' => ['type' => 'string'],
                    'negative_prompt' => ['type' => 'string'],
                    'seed' => ['type' => 'integer'],
                    'scheduler' => ['type' => 'string'],
                    'num_inference_steps' => ['type' => 'integer'],
                    'guidance_scale' => ['type' => 'number'],
                    'ip_adapter_scale' => ['type' => 'number'],
                    'controlnet_conditioning_scale' => ['type' => 'number'],
                    'num_outputs' => ['type' => 'integer'],
                    'output_format' => ['type' => 'string'],
                    'output_quality' => ['type' => 'integer'],
                ], ['image', 'prompt']),
                'capability_config' => $this->capabilities(
                    ['image', 'pose_image', 'prompt', 'negative_prompt', 'seed', 'scheduler', 'num_inference_steps', 'guidance_scale', 'ip_adapter_scale', 'controlnet_conditioning_scale', 'num_outputs', 'output_format', 'output_quality'],
                    ['required_reference_count' => 1, 'reference_fields' => ['image']]
                ),
                'pricing_config' => [
                    'source' => 'replicate',
                    'pricing_type' => 'per_generation',
                    'version_strategy' => 'pinned_latest_verified',
                    'model_url' => 'https://replicate.com/zsxkib/instant-id',
                ],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/zsxkib/instant-id',
                'recommended_category_ids' => [1, 15, 21],
                'description' => 'مدل حفظ هویت چهره از یک تصویر مرجع؛ مناسب پرتره، آواتار و تولید تصویر با شباهت بالا. تصویر ورودی اجباری است.',
                'data_retention_notes' => 'تصویر مرجع برای پردازش به Replicate ارسال می‌شود؛ سیاست نگه‌داری را پیش از استفاده تجاری بررسی کنید.',
            ]),

            array_merge($common, [
                'name' => 'ModelScope FaceFusion — تعویض چهره',
                'openrouter_model_id' => 'lucataco/modelscope-facefusion',
                'external_model_id' => 'lucataco/modelscope-facefusion',
                'external_version' => '52edbb2b42beb4e19242f0c9ad5717211a96c63ff1f0b0320caa518b2745f4f7',
                'output_modality' => 'image',
                'task_type' => 'face_consistency',
                'supports_image_input' => true,
                'supports_face_identity' => true,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 150,
                'cost_per_generation_usd' => 1.82,
                'default_parameters' => [],
                'input_schema' => $this->schema([
                    'user_image' => ['type' => 'string', 'format' => 'uri'],
                    'template_image' => ['type' => 'string', 'format' => 'uri'],
                ], ['user_image', 'template_image']),
                'capability_config' => $this->capabilities(
                    ['user_image', 'template_image'],
                    ['required_reference_count' => 2, 'reference_fields' => ['user_image', 'template_image']]
                ),
                'pricing_config' => [
                    'source' => 'replicate',
                    'pricing_type' => 'per_generation',
                    'version_strategy' => 'pinned_latest_verified',
                    'requested_model_id' => 'lucataco/faceswap',
                    'model_url' => 'https://replicate.com/lucataco/modelscope-facefusion',
                ],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/lucataco/modelscope-facefusion',
                'recommended_category_ids' => [1, 21, 77],
                'description' => 'نسخه معتبر و فعال جایگزین شناسه قدیمی faceswap؛ چهره کاربر را روی تصویر هدف قرار می‌دهد. دو تصویر ورودی لازم است: چهره کاربر و تصویر هدف.',
                'data_retention_notes' => 'هر دو تصویر برای پردازش به Replicate ارسال می‌شوند؛ برای استفاده تجاری، مجوز تصاویر را بررسی کنید.',
            ]),

            array_merge($common, [
                'name' => 'PhotoMaker — تولید هنری با حفظ چهره',
                'openrouter_model_id' => 'tencentarc/photomaker',
                'external_model_id' => 'tencentarc/photomaker',
                'external_version' => 'ddfc2b08d209f9fa8c1eca692712918bd449f695dabb4a958da31802a9570fe4',
                'output_modality' => 'image',
                'task_type' => 'face_consistency',
                'supports_image_input' => true,
                'supports_face_identity' => true,
                'supports_multiple_faces' => true,
                'cost_per_generation' => 12,
                'cost_per_generation_usd' => 0.05,
                'default_parameters' => [
                    'style_name' => 'Photographic (Default)',
                    'num_steps' => 50,
                    'guidance_scale' => 5,
                    'style_strength_ratio' => 20,
                    'num_outputs' => 1,
                ],
                'input_schema' => $this->schema([
                    'input_image' => ['type' => 'string', 'format' => 'uri'],
                    'input_image2' => ['type' => 'string', 'format' => 'uri'],
                    'input_image3' => ['type' => 'string', 'format' => 'uri'],
                    'input_image4' => ['type' => 'string', 'format' => 'uri'],
                    'prompt' => ['type' => 'string'],
                    'negative_prompt' => ['type' => 'string'],
                    'style_name' => ['type' => 'string'],
                    'num_steps' => ['type' => 'integer'],
                    'guidance_scale' => ['type' => 'number'],
                    'style_strength_ratio' => ['type' => 'number'],
                    'num_outputs' => ['type' => 'integer'],
                    'seed' => ['type' => 'integer'],
                ], ['input_image', 'prompt']),
                'capability_config' => $this->capabilities(
                    ['input_image', 'input_image2', 'input_image3', 'input_image4', 'prompt', 'negative_prompt', 'style_name', 'num_steps', 'guidance_scale', 'style_strength_ratio', 'num_outputs', 'seed'],
                    ['required_reference_count' => 1, 'reference_fields' => ['input_image', 'input_image2', 'input_image3', 'input_image4']]
                ),
                'pricing_config' => [
                    'source' => 'replicate',
                    'pricing_type' => 'per_generation',
                    'version_strategy' => 'pinned_latest_verified',
                    'requested_model_id' => 'tencentarc/photomaker-v2',
                    'model_url' => 'https://replicate.com/tencentarc/photomaker',
                ],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/tencentarc/photomaker',
                'recommended_category_ids' => [1, 15, 18, 19, 21],
                'description' => 'نسخه معتبر PhotoMaker برای ساخت پرتره، آواتار و تصاویر هنری با حفظ ویژگی‌های چهره. حداقل یک تصویر ورودی لازم است و عبارت img باید در پرامپت باشد.',
                'data_retention_notes' => 'تصاویر مرجع برای پردازش به Replicate ارسال می‌شوند؛ مجوز استفاده از چهره و تصویر را رعایت کنید.',
            ]),

            array_merge($common, [
                'name' => 'FLUX.1 [dev] — تولید عمومی باکیفیت',
                'openrouter_model_id' => 'black-forest-labs/flux-dev',
                'external_model_id' => 'black-forest-labs/flux-dev',
                'external_version' => null,
                'output_modality' => 'image',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'supports_face_identity' => false,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 8,
                'cost_per_generation_usd' => 0.025,
                'default_parameters' => ['go_fast' => true, 'guidance' => 3.5, 'num_outputs' => 1, 'aspect_ratio' => '1:1', 'output_format' => 'webp', 'output_quality' => 80, 'prompt_strength' => 0.8, 'num_inference_steps' => 28],
                'input_schema' => $this->schema([
                    'prompt' => ['type' => 'string'], 'image' => ['type' => 'string', 'format' => 'uri'], 'seed' => ['type' => 'integer'],
                    'go_fast' => ['type' => 'boolean'], 'guidance' => ['type' => 'number'], 'num_outputs' => ['type' => 'integer'],
                    'aspect_ratio' => ['type' => 'string'], 'output_format' => ['type' => 'string'], 'output_quality' => ['type' => 'integer'],
                    'prompt_strength' => ['type' => 'number'], 'num_inference_steps' => ['type' => 'integer'], 'disable_safety_checker' => ['type' => 'boolean'],
                ], ['prompt']),
                'capability_config' => $this->capabilities(['prompt', 'image', 'seed', 'go_fast', 'guidance', 'num_outputs', 'aspect_ratio', 'output_format', 'output_quality', 'prompt_strength', 'num_inference_steps', 'disable_safety_checker']),
                'pricing_config' => ['source' => 'replicate', 'pricing_type' => 'per_generation', 'version_strategy' => 'official_latest', 'model_url' => 'https://replicate.com/black-forest-labs/flux-dev'],
                'pricing_type' => 'per_generation',
                'commercial_use' => true,
                'terms_url' => 'https://replicate.com/black-forest-labs/flux-dev',
                'recommended_category_ids' => [1, 21, 23, 46, 77, 123],
                'description' => 'مدل رسمی FLUX.1 dev برای تولید تصویر با کیفیت و پیروی قوی از پرامپت؛ نسخه رسمی بدون pin نسخه اجرا می‌شود تا آخرین نسخه Replicate استفاده شود.',
                'data_retention_notes' => 'طبق صفحه مدل رسمی Replicate، ورودی و خروجی برای آموزش استفاده نمی‌شوند؛ شرایط جاری سرویس ملاک است.',
            ]),

            array_merge($common, [
                'name' => 'FLUX.1 [schnell] — تولید سریع و اقتصادی',
                'openrouter_model_id' => 'black-forest-labs/flux-schnell',
                'external_model_id' => 'black-forest-labs/flux-schnell',
                'external_version' => null,
                'output_modality' => 'image',
                'task_type' => 'text_to_image',
                'supports_image_input' => false,
                'supports_face_identity' => false,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 2,
                'cost_per_generation_usd' => 0.003,
                'default_parameters' => ['go_fast' => true, 'num_outputs' => 1, 'aspect_ratio' => '1:1', 'output_format' => 'webp', 'output_quality' => 80, 'num_inference_steps' => 4],
                'input_schema' => $this->schema([
                    'prompt' => ['type' => 'string'], 'seed' => ['type' => 'integer'], 'go_fast' => ['type' => 'boolean'], 'num_outputs' => ['type' => 'integer'],
                    'aspect_ratio' => ['type' => 'string'], 'output_format' => ['type' => 'string'], 'output_quality' => ['type' => 'integer'], 'num_inference_steps' => ['type' => 'integer'],
                ], ['prompt']),
                'capability_config' => $this->capabilities(['prompt', 'seed', 'go_fast', 'num_outputs', 'aspect_ratio', 'output_format', 'output_quality', 'num_inference_steps']),
                'pricing_config' => ['source' => 'replicate', 'pricing_type' => 'per_generation', 'version_strategy' => 'official_latest', 'model_url' => 'https://replicate.com/black-forest-labs/flux-schnell'],
                'pricing_type' => 'per_generation',
                'commercial_use' => true,
                'terms_url' => 'https://replicate.com/black-forest-labs/flux-schnell',
                'recommended_category_ids' => [1, 23, 46, 77, 123],
                'description' => 'نسخه رسمی سریع و کم‌هزینه FLUX برای پیش‌نمایش، تولید انبوه و تست محصول؛ نسخه رسمی همیشه از نام مدل استفاده می‌کند.',
                'data_retention_notes' => 'طبق صفحه مدل رسمی Replicate، ورودی و خروجی برای آموزش استفاده نمی‌شوند؛ شرایط جاری سرویس ملاک است.',
            ]),

            array_merge($common, [
                'name' => 'RealVisXL — فوتورئال جایگزین مدل v4',
                'openrouter_model_id' => 'lucataco/realvisxl-v2.0',
                'external_model_id' => 'lucataco/realvisxl-v2.0',
                'external_version' => '7d6a2f9c4754477b12c14ed2a58f89bb85128edcdd581d24ce58b6926029de08',
                'output_modality' => 'image',
                'task_type' => 'image_to_image',
                'supports_image_input' => true,
                'supports_face_identity' => false,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 4,
                'cost_per_generation_usd' => 0.0052,
                'default_parameters' => ['scheduler' => 'DPMSolverMultistep', 'guidance_scale' => 7, 'negative_prompt' => '(worst quality, low quality, illustration, 3d, 2d, painting, cartoons, sketch), open mouth', 'num_outputs' => 1, 'num_inference_steps' => 40, 'prompt_strength' => 0.8, 'apply_watermark' => true],
                'input_schema' => $this->schema([
                    'prompt' => ['type' => 'string'], 'negative_prompt' => ['type' => 'string'], 'image' => ['type' => 'string', 'format' => 'uri'], 'mask' => ['type' => 'string', 'format' => 'uri'],
                    'width' => ['type' => 'integer'], 'height' => ['type' => 'integer'], 'seed' => ['type' => 'integer'], 'scheduler' => ['type' => 'string'],
                    'guidance_scale' => ['type' => 'number'], 'num_outputs' => ['type' => 'integer'], 'num_inference_steps' => ['type' => 'integer'], 'prompt_strength' => ['type' => 'number'], 'apply_watermark' => ['type' => 'boolean'],
                ], ['prompt']),
                'capability_config' => $this->capabilities(['prompt', 'negative_prompt', 'image', 'mask', 'width', 'height', 'seed', 'scheduler', 'guidance_scale', 'num_outputs', 'num_inference_steps', 'prompt_strength', 'apply_watermark']),
                'pricing_config' => ['source' => 'replicate', 'pricing_type' => 'per_generation', 'version_strategy' => 'pinned_latest_verified', 'requested_model_id' => 'fofr/realvisxl-v4-multi-controlnet', 'model_url' => 'https://replicate.com/lucataco/realvisxl-v2.0'],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/lucataco/realvisxl-v2.0',
                'recommended_category_ids' => [1, 21, 46, 77, 123],
                'description' => 'نسخه معتبر و قابل‌اجرا از خانواده RealVisXL برای خروجی فوتورئال؛ شناسه v4/multi-controlnet ارائه‌شده در متن در کاتالوگ فعلی قابل تأیید نبود.',
                'data_retention_notes' => 'تصویر مرجع در حالت image-to-image به Replicate ارسال می‌شود؛ مجوز استفاده از تصویر را رعایت کنید.',
            ]),

            array_merge($common, [
                'name' => 'Real-ESRGAN — ارتقای کیفیت تصویر',
                'openrouter_model_id' => 'lucataco/real-esrgan',
                'external_model_id' => 'lucataco/real-esrgan',
                'external_version' => '3febd19381dd7e1f52a3ed3260b5b0a5636353de45e37e7c1c3cd814b24077a3',
                'output_modality' => 'image',
                'task_type' => 'upscaling',
                'supports_image_input' => true,
                'supports_face_identity' => false,
                'supports_multiple_faces' => false,
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.0082,
                'default_parameters' => ['scale' => 2, 'face_enhance' => true],
                'input_schema' => $this->schema(['image' => ['type' => 'string', 'format' => 'uri'], 'scale' => ['type' => 'number'], 'face_enhance' => ['type' => 'boolean']], ['image']),
                'capability_config' => $this->capabilities(['image', 'scale', 'face_enhance'], ['required_reference_count' => 1, 'reference_fields' => ['image']]),
                'pricing_config' => ['source' => 'replicate', 'pricing_type' => 'per_generation', 'version_strategy' => 'pinned_latest_verified', 'model_url' => 'https://replicate.com/lucataco/real-esrgan'],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/lucataco/real-esrgan',
                'recommended_category_ids' => [1, 21, 77],
                'description' => 'برای افزایش وضوح و کیفیت تصویر تولیدشده؛ یک تصویر ورودی لازم دارد و می‌تواند جزئیات چهره را نیز تقویت کند.',
                'data_retention_notes' => 'تصویر برای پردازش ارتقا به Replicate ارسال می‌شود.',
            ]),

            array_merge($common, [
                'name' => 'Stable Video Diffusion — عکس به ویدیو',
                'openrouter_model_id' => 'sunfjun/stable-video-diffusion',
                'external_model_id' => 'sunfjun/stable-video-diffusion',
                'external_version' => 'd68b6e09eedbac7a49e3d8644999d93579c386a083768235cabca88796d70d82',
                'output_modality' => 'video',
                'task_type' => 'image_to_video',
                'supports_image_input' => true,
                'supports_face_identity' => false,
                'supports_multiple_faces' => false,
                'supports_video_input' => false,
                'max_duration' => 4,
                'default_width' => 1024,
                'default_height' => 576,
                'cost_per_generation' => 8,
                'cost_per_generation_usd' => 0.037,
                'default_parameters' => ['video_length' => '14_frames_with_svd', 'sizing_strategy' => 'maintain_aspect_ratio', 'frames_per_second' => 6, 'motion_bucket_id' => 127, 'cond_aug' => 0.02, 'decoding_t' => 14],
                'input_schema' => $this->schema(['input_image' => ['type' => 'string', 'format' => 'uri'], 'video_length' => ['type' => 'string'], 'sizing_strategy' => ['type' => 'string'], 'frames_per_second' => ['type' => 'integer'], 'motion_bucket_id' => ['type' => 'integer'], 'cond_aug' => ['type' => 'number'], 'decoding_t' => ['type' => 'integer'], 'seed' => ['type' => 'integer']], ['input_image']),
                'capability_config' => $this->capabilities(['input_image', 'video_length', 'sizing_strategy', 'frames_per_second', 'motion_bucket_id', 'cond_aug', 'decoding_t', 'seed'], ['required_reference_count' => 1, 'reference_fields' => ['input_image']]),
                'pricing_config' => ['source' => 'replicate', 'pricing_type' => 'per_generation', 'version_strategy' => 'pinned_latest_verified', 'requested_model_id' => 'stability-ai/stable-video-diffusion', 'model_url' => 'https://replicate.com/sunfjun/stable-video-diffusion'],
                'pricing_type' => 'per_generation',
                'terms_url' => 'https://replicate.com/sunfjun/stable-video-diffusion',
                'recommended_category_ids' => [78, 83, 89, 90],
                'description' => 'نسخه فعال عکس به ویدیوی کوتاه از خانواده Stable Video Diffusion؛ تصویر ورودی لازم دارد و خروجی معمولاً ۱۴ یا ۲۵ فریم است.',
                'data_retention_notes' => 'تصویر ورودی برای تولید ویدیوی کوتاه به Replicate ارسال می‌شود.',
            ]),
        ];
    }

    private function schema(array $properties, array $required = []): array
    {
        return ['type' => 'object', 'properties' => $properties, 'required' => $required];
    }

    private function capabilities(array $allowedInputs, array $extra = []): array
    {
        return array_merge([
            'allowed_inputs' => $allowedInputs,
            'supports_text_to_image' => true,
            'supports_image_to_image' => false,
            'supports_text_to_video' => false,
            'supports_image_to_video' => false,
        ], $extra);
    }
}
