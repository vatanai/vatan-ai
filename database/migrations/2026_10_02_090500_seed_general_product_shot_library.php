<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** شات‌های عمومی برای پوشاک، کیف‌وکفش، زیبایی و جواهر؛ بدون بازنویسی شات‌های ادمین. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_library')) {
            return;
        }

        $niches = ['beauty', 'fashion', 'bags-shoes', 'jewelry'];
        $shots = [
            ['product-clean-studio', 'استودیوی تمیز فروشگاهی', 'Clean commerce studio', 'شات مطمئن و ساده برای نمایش دقیق محصول در فروشگاه.', 'hero', ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['softbox-studio'], 'surface' => 'pastel-seamless', 'props' => ['none'], 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['product-luxury-plinth', 'ویترین لوکس روی پایه سنگی', 'Luxury stone plinth', 'محصول روی پایه‌ی سنگی با نور لوکس و فضای تبلیغاتی.', 'hero', ['framing' => 'hero-centered', 'camera' => 'low-angle', 'lens' => '85mm-portrait', 'lighting' => ['softbox-studio', 'rim-light'], 'surface' => 'travertine-plinth', 'props' => ['none'], 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['product-editorial-shadow', 'ادیتوریال با سایه‌ی آفتاب', 'Editorial sun shadow', 'کادر مجله‌ای با سایه‌های گرافیکی و فضای کافی برای استفاده تبلیغاتی.', 'lifestyle', ['framing' => 'off-center-editorial', 'camera' => '45deg', 'lens' => '50mm-natural', 'lighting' => ['hard-sun-leaf-shadow'], 'surface' => 'pastel-seamless', 'props' => ['none'], 'human' => 'none', 'mood' => 'editorial'], '4:5'],
            ['product-dynamic-float', 'محصول معلق پویا', 'Dynamic floating product', 'محصول معلق با ترکیب پرانرژی برای استوری و کاور.', 'hero', ['framing' => 'three-quarter', 'camera' => 'dutch-tilt', 'lens' => '35mm-wide', 'lighting' => ['softbox-studio', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => ['levitation'], 'human' => 'none', 'mood' => 'playful-pop'], '9:16'],
            ['product-detail-macro', 'جزئیات ماکرو محصول', 'Product detail macro', 'نمای نزدیک از بافت، متریال و جزئیات شاخص محصول.', 'detail', ['framing' => 'closeup-macro', 'camera' => 'eye-level', 'lens' => 'macro-100mm', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'wet-marble', 'props' => ['none'], 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['product-flatlay-social', 'فلت‌لی شبکه اجتماعی', 'Social flat lay', 'چیدمان از بالا برای پست چنداسلایدی و معرفی کالکشن.', 'flatlay', ['framing' => 'flatlay-topdown', 'camera' => 'top-down', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'satin-fabric', 'props' => ['none'], 'human' => 'none', 'mood' => 'editorial'], '1:1'],
        ];

        foreach ($shots as $index => [$key, $nameFa, $nameEn, $description, $category, $tokens, $ratio]) {
            if (DB::table('shot_library')->where('key', $key)->exists()) {
                continue;
            }
            DB::table('shot_library')->insert([
                'key' => $key,
                'name_fa' => $nameFa,
                'name_en' => $nameEn,
                'description_fa' => $description,
                'category' => $category,
                'niche_tags' => json_encode($niches),
                'tokens' => json_encode($tokens),
                'prompt_template' => null,
                'default_credits' => 12,
                'aspect_ratio_default' => $ratio,
                'sample_image' => null,
                'is_active' => true,
                'sort' => 200 + (($index + 1) * 10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shot_library')) {
            DB::table('shot_library')->whereIn('key', [
                'product-clean-studio', 'product-luxury-plinth', 'product-editorial-shadow',
                'product-dynamic-float', 'product-detail-macro', 'product-flatlay-social',
            ])->delete();
        }
    }
};
