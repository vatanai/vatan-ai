<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CompleteBuildDemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $primary = AiModel::where('is_active', true)->where('openrouter_model_id', 'openai/gpt-image-2')->first()
            ?? AiModel::where('is_active', true)->first();
        $fallbacks = AiModel::where('is_active', true)->where('id', '!=', $primary?->id)->limit(2)->pluck('openrouter_model_id')->filter()->values()->all();
        $category = Category::first();

        $assets = [
            'cover' => ['assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif', 'products/demo/complete-cover.avif'],
            'cinematic' => ['assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'products/demo/style-cinematic.jpeg'],
            'editorial' => ['assets/img/elegant-woman-cafe-portrait-by-promptplum.avif', 'products/demo/style-editorial.avif'],
            'classic' => ['assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp', 'products/demo/style-classic.webp'],
        ];
        foreach ($assets as [$source, $target]) {
            if (is_file(public_path($source))) Storage::disk('public')->put($target, file_get_contents(public_path($source)));
        }

        $schema = [
            $this->field('intro', 'section', 'تصاویر و اطلاعات اصلی', description: 'همه حالت‌های ممکن فرم ثبت محصول در این نمونه فعال شده‌اند.'),
            $this->field('guide', 'info', 'برای حفظ دقیق هویت، عکس واضح و بدون فیلتر بارگذاری کنید.', extra: ['variant' => 'info']),
            $this->field('main_photo', 'image_upload', 'تصویر اصلی چهره', true, 'عکس روبه‌رو با نور طبیعی', extra: ['max_files'=>1,'max_size_mb'=>10,'accept'=>'image/*']),
            $this->field('reference_photos', 'multi_image', 'تصاویر مرجع بیشتر', false, 'تا ۳ زاویه دیگر', extra: ['max_files'=>3,'max_size_mb'=>10,'accept'=>'image/*']),
            $this->field('source_document', 'file_upload', 'فایل مرجع تکمیلی', false, 'فایل PDF یا ZIP اختیاری', extra: ['max_files'=>1,'max_size_mb'=>8,'accept'=>'.pdf,.zip']),
            $this->field('divider_1', 'divider', 'جداکننده'),
            $this->field('creative', 'section', 'تنظیمات خلاقانه', description: 'جزئیات صحنه و ظاهر خروجی را تعیین کنید.'),
            $this->field('short_title', 'text', 'عنوان کوتاه روی تصویر', false, 'مثلاً: شب تهران', extra: ['min'=>2,'max'=>40,'prompt_mode'=>'append','prompt_wrap'=>'Include this exact title: {value}']),
            $this->field('scene_description', 'textarea', 'توضیح کامل صحنه', true, 'فضا، لباس، ژست و جزئیات دلخواه...', extra: ['min'=>10,'max'=>500,'prompt_mode'=>'token']),
            $this->field('custom_prompt', 'prompt', 'دستور اختصاصی', false, 'جزئیات حرفه‌ای موردنظر شما...', extra: ['prompt_mode'=>'append','prompt_wrap'=>'Additional user direction: {value}']),
            $this->field('apparent_age', 'number', 'سن ظاهری', false, '۳۰', extra: ['default'=>30,'min'=>18,'max'=>80,'step'=>1,'prompt_wrap'=>'apparent age {value} years old']),
            $this->field('creative_strength', 'slider', 'میزان خلاقیت', false, extra: ['default'=>65,'min'=>0,'max'=>100,'step'=>5,'unit'=>'٪','prompt_wrap'=>'creative transformation strength {value} percent']),
            $this->field('camera_frame', 'select', 'قاب دوربین', true, extra: ['default'=>'portrait','options'=>$this->options([['close','نمای نزدیک','tight cinematic close-up',0],['portrait','نیم‌تنه','medium portrait framing',0],['full','تمام‌قد','full body framing',2]])]),
            $this->field('mood', 'radio', 'حس‌وحال چهره', true, extra: ['default'=>'confident','options'=>$this->options([['confident','بااعتمادبه‌نفس','confident natural expression',0],['calm','آرام','calm peaceful expression',0],['smile','لبخند ملایم','subtle authentic smile',1]])]),
            $this->field('effects', 'multi_select', 'افکت‌های صحنه', false, extra: ['min'=>0,'max'=>3,'options'=>$this->options([['rain','باران','cinematic rain',1],['fog','مه','atmospheric fog',1],['bokeh','بوکه','soft background bokeh',0],['grain','گرین فیلم','subtle analog film grain',0]])]),
            $this->field('lighting', 'button_group', 'نورپردازی', true, extra: ['default'=>'cinematic','options'=>$this->options([['soft','نرم','soft diffused lighting',0],['cinematic','سینمایی','dramatic cinematic lighting',2],['studio','استودیویی','professional studio lighting',1]])]),
            $this->field('dominant_color', 'color', 'رنگ غالب', false, extra: ['default'=>'#16594f','prompt_wrap'=>'dominant color palette {value}']),
            $this->field('preserve_outfit', 'switch', 'لباس اصلی حفظ شود', false, extra: ['default'=>'1','prompt_wrap'=>'preserve the original outfit: {value}']),
            $this->field('usage_rights', 'checkbox', 'حق استفاده از تصاویر را دارم', true, extra: ['prompt_mode'=>'off']),
            $this->field('conditional_note', 'text', 'جزئیات لباس', true, 'رنگ و مدل لباس', extra: ['show_if'=>['field'=>'preserve_outfit','op'=>'eq','value'=>'0'],'prompt_mode'=>'append','prompt_wrap'=>'Change outfit to: {value}']),
            $this->field('output_section', 'section', 'تنظیمات خروجی'),
            $this->field('style', 'style_preset', 'استایل آماده', true, extra: ['default'=>'cinematic','options'=>[
                ['value'=>'cinematic','label'=>'سینمایی','prompt'=>'cinematic color grading and composition','credit'=>2,'image'=>'products/demo/style-cinematic.jpeg'],
                ['value'=>'editorial','label'=>'ادیتوریال','prompt'=>'high-fashion editorial photography','credit'=>3,'image'=>'products/demo/style-editorial.avif'],
                ['value'=>'classic','label'=>'کلاسیک','prompt'=>'timeless classic portrait photography','credit'=>1,'image'=>'products/demo/style-classic.webp'],
            ]]),
            $this->field('aspect', 'aspect_ratio', 'نسبت تصویر', true, extra: ['default'=>'4:5','prompt_mode'=>'off','options'=>$this->options([['1:1','مربع ۱:۱','',0],['4:5','پرتره ۴:۵','',0],['9:16','استوری ۹:۱۶','',1],['16:9','عریض ۱۶:۹','',1]])]),
            $this->field('quality', 'resolution', 'کیفیت خروجی', true, extra: ['default'=>'2K','prompt_mode'=>'off','options'=>$this->options([['1K','استاندارد 1K','',0],['2K','کیفیت بالا 2K','',2],['4K','فوق‌العاده 4K','',6]])]),
            $this->field('identity_strength_user', 'strength', 'شدت حفظ شباهت', true, extra: ['default'=>92,'min'=>50,'max'=>100,'step'=>1,'unit'=>'٪','prompt_wrap'=>'identity preservation strength {value} percent']),
            $this->field('advanced_section', 'section', 'تنظیمات حرفه‌ای'),
            $this->field('avoid', 'negative_prompt', 'موارد ناخواسته', false, 'تاری، نوشته، اعوجاج دست...', extra: ['prompt_mode'=>'off','max'=>300]),
            $this->field('seed_value', 'seed', 'Seed', false, 'برای خروجی تصادفی خالی بگذارید', extra: ['prompt_mode'=>'off','min'=>0,'max'=>2147483647]),
            $this->field('hidden_directive', 'text', 'دستور داخلی', false, extra: ['hidden'=>'1','default'=>'premium skin texture and anatomically correct hands','prompt_mode'=>'append']),
        ];

        $product = Product::firstOrNew(['slug' => 'complete-build-demo']);
        if (!$product->product_code) $product->product_code = '987654';
        $product->forceFill([
            'name_fa'=>'محصول جامع آزمایشی صفحه بساز','name_en'=>'Complete Build Experience Demo',
            'description_fa'=>'محصول واقعی لوکال برای نمایش و آزمایش تمام قابلیت‌های ثبت محصول و صفحه بساز؛ شامل همه انواع ورودی، شرط نمایش، هزینه افزوده، حفظ هویت و خروجی چندگانه.',
            'description_en'=>'A complete local product for testing every build-page capability.',
            'category_id'=>$category?->id,'category'=>$category?->name_fa ?? $category?->name ?? 'عمومی','subcategory'=>'نمونه جامع',
            'status'=>'active','thumbnail'=>$assets['cover'][1],'cover'=>$assets['cover'][1],
            'sample_outputs'=>[$assets['cinematic'][1],$assets['editorial'][1],$assets['classic'][1]],
            'before_images'=>[$assets['cover'][1]],'media_type'=>'photo','primary_model'=>$primary?->openrouter_model_id ?: 'openai/gpt-image-2',
            'fallback_models'=>$fallbacks,'pipeline_type'=>'image_editing','timeout'=>180,
            'system_prompt'=>'You are an elite identity-preserving portrait generator. Produce photorealistic, anatomically correct, commercially usable imagery.',
            'prompt_template'=>'Create a premium portrait from {main_photo}. Scene: {scene_description}. Camera: {camera_frame}. Expression: {mood}. Lighting: {lighting}. Style: {style}. Effects: {effects}. Color palette: {dominant_color}.',
            'negative_prompt'=>'low quality, blurry face, distorted anatomy, extra fingers, duplicate person, text artifacts',
            'seed'=>20260721,'provider_options'=>['allow_fallbacks'=>true],'input_schema'=>$schema,
            'subject_type'=>'body','identity_preservation'=>true,'identity_strength'=>95,'preserve_body'=>true,
            'identity_instructions'=>'STRICT IDENTITY LOCK: preserve the exact face, facial geometry, skin tone, body proportions, and recognizable identity from all reference images.',
            'min_reference_images'=>1,'max_reference_images'=>4,'pricing_model'=>'per_credit','credit_cost'=>18,
            'output_type'=>'image','output_format'=>'png','output_count'=>4,'resolution'=>'2K','aspect_ratio'=>'4:5',
            'output_variants'=>[
                ['key'=>'cinematic','title'=>'سینمایی','image'=>$assets['cinematic'][1],'prompt'=>'cinematic night portrait with premium film color grading'],
                ['key'=>'editorial','title'=>'ادیتوریال','image'=>$assets['editorial'][1],'prompt'=>'luxury fashion editorial portrait for a magazine cover'],
                ['key'=>'classic','title'=>'کلاسیک','image'=>$assets['classic'][1],'prompt'=>'timeless studio portrait with classic lighting'],
            ],
            'delivery_method'=>'instant','estimated_time'=>75,'price_tier'=>'premium','discount_percentage'=>10,
            'platform'=>'both','accent_color'=>'#a07af5','tags'=>['آزمایشی','حفظ هویت','جامع','پرتره'],
            'display_mode'=>'card','card_shape'=>'portrait','gallery_layout'=>'grid','card_label'=>'نمونه کامل',
            'watermark_enabled'=>true,'watermark_position'=>'bottom-left','new_display_order'=>999,
            'new_internal_code'=>'DEMO-COMPLETE-BUILD','new_admin_note'=>'محصول جامع لوکال؛ برای پروداکشن منتشر نشود.',
            'new_is_premium'=>true,'new_is_recommended'=>true,'new_is_beta'=>true,'new_show_free_badge'=>false,
            'new_min_credit_required'=>18,'new_max_run_per_user'=>20,'new_price_custom_label'=>'نسخه جامع آزمایشی',
            'meta_title'=>'محصول جامع آزمایشی صفحه بساز','meta_description'=>'نمایش همه حالت‌های ویژگی محصول در صفحه بساز وطن AI.','meta_keywords'=>'وطن AI, صفحه بساز, محصول آزمایشی',
            'explore_tiles'=>['1x1','2x2','1x2','2x1'],'is_featured'=>true,'is_new'=>true,'is_trending'=>true,
        ])->save();

        if ($category) {
            $product->category_id = $category->id;
            $product->category = $category->name_fa ?? $category->name ?? 'عمومی';
            $product->save();
            $product->categories()->syncWithoutDetaching([$category->id]);
        }

        $this->command?->info("Complete demo product ready: {$product->route_slug}");
    }

    private function field(string $id, string $type, string $label, bool $required = false, string $placeholder = '', string $description = '', array $extra = []): array
    {
        return array_merge(['field_id'=>$id,'type'=>$type,'label_fa'=>$label,'required'=>$required?'1':'0','hidden'=>'0','description'=>$description,'help_text'=>$description,'placeholder'=>$placeholder,'default'=>'','min'=>'','max'=>'','step'=>'','unit'=>'','regex'=>'','max_files'=>'','max_size_mb'=>'','accept'=>'','credit_cost'=>0,'variant'=>'info','prompt_mode'=>'token','prompt_wrap'=>'','show_if'=>['field'=>'','op'=>'eq','value'=>''],'options'=>[]], $extra);
    }

    private function options(array $rows): array
    {
        return array_map(fn(array $row) => ['value'=>$row[0],'label'=>$row[1],'prompt'=>$row[2],'credit'=>$row[3],'image'=>''], $rows);
    }
}
