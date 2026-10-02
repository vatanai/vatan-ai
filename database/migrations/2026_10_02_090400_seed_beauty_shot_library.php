<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 3.C — هشت شات پایه برای نیش آرایشی، عطر و زیبایی با «زبان شات».
 * الهام از سری‌های مرجع HYPHEN (صورتی ملایم) و VÉLORA (نارنجی/طلایی).
 * idempotent: بر اساس key؛ شاتی که ادمین ویرایش کرده بازنویسی نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_library')) {
            return;
        }

        $beauty = ['beauty', 'cosmetics', 'perfume', 'skincare'];
        $shots = [
            [
                'key' => 'beauty-hero-sun-travertine',
                'name_fa' => 'هیرو آفتابی روی تراورتن',
                'name_en' => 'Sunlit travertine hero',
                'description_fa' => 'محصول روی پایه‌ی سنگ تراورتن، آفتاب تند با سایه‌ی برگ و مواد اولیه. حس آرام و لوکس.',
                'category' => 'hero',
                'tokens' => ['framing' => 'hero-centered', 'camera' => 'low-angle', 'lens' => '85mm-portrait', 'lighting' => ['hard-sun-leaf-shadow'], 'surface' => 'travertine-plinth', 'props' => ['ingredients-scatter', 'water-droplets'], 'human' => 'none', 'mood' => 'warm-natural'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-liquid-splash',
                'name_fa' => 'پاشش مایع پرانرژی',
                'name_en' => 'Liquid splash energy',
                'description_fa' => 'پاشش منجمدشده‌ی مایع هم‌رنگ محصول با مواد اولیه و گل. شات انرژی برای استوری و تبلیغ.',
                'category' => 'hero',
                'tokens' => ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['softbox-studio', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => ['liquid-splash', 'ingredients-scatter'], 'human' => 'none', 'mood' => 'playful-pop'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-glass-ribbons-float',
                'name_fa' => 'معلق روی روبان‌های شیشه‌ای',
                'name_en' => 'Floating on glass ribbons',
                'description_fa' => 'محصول معلق و کمی کج میان روبان‌های شیشه‌ای شفاف. سورئال و مدرن.',
                'category' => 'hero',
                'tokens' => ['framing' => 'three-quarter', 'camera' => '45deg', 'lens' => '85mm-portrait', 'lighting' => ['softbox-studio', 'caustics'], 'surface' => 'glass-ribbons', 'props' => ['levitation'], 'human' => 'none', 'mood' => 'clean-luxury'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-macro-droplets',
                'name_fa' => 'ماکرو با قطره‌های آب',
                'name_en' => 'Macro water droplets',
                'description_fa' => 'کلوزآپ ماکرو روی مرمر خیس با قطره‌های تازه و نور لبه. حس آبرسانی و طراوت.',
                'category' => 'detail',
                'tokens' => ['framing' => 'closeup-macro', 'camera' => 'eye-level', 'lens' => 'macro-100mm', 'lighting' => ['rim-light'], 'surface' => 'wet-marble', 'props' => ['water-droplets'], 'human' => 'none', 'mood' => 'fresh-cool'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-hand-hold',
                'name_fa' => 'در دست، کلوزآپ',
                'name_en' => 'Hand-held close-up',
                'description_fa' => 'دست ظریف با مانیکور طبیعی که محصول را نگه داشته؛ زمینه‌ی پاستلی. فقط دست دیده می‌شود.',
                'category' => 'lifestyle',
                'tokens' => ['framing' => 'off-center-editorial', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['soft-window'], 'surface' => 'pastel-seamless', 'props' => ['none'], 'human' => 'hand-hold', 'mood' => 'clean-luxury'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-model-portrait',
                'name_fa' => 'پرتره مدل با محصول',
                'name_en' => 'Model portrait with product',
                'description_fa' => 'پرتره‌ی زیبایی یک مدل فرضی که محصول را کنار صورتش گرفته. ادیتوریال مجله.',
                'category' => 'model',
                'tokens' => ['framing' => 'off-center-editorial', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => ['petals'], 'human' => 'model-portrait-with-prop', 'mood' => 'editorial'],
                'aspect_ratio_default' => '4:5',
            ],
            [
                'key' => 'beauty-flatlay-ingredients',
                'name_fa' => 'فلت‌لی با مواد اولیه',
                'name_en' => 'Ingredients flat-lay',
                'description_fa' => 'چیدمان از بالا روی ساتن با گلبرگ و مواد اولیه. مناسب پست کاروسل.',
                'category' => 'flatlay',
                'tokens' => ['framing' => 'flatlay-topdown', 'camera' => 'top-down', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'satin-fabric', 'props' => ['ingredients-scatter', 'petals'], 'human' => 'none', 'mood' => 'warm-natural'],
                'aspect_ratio_default' => '1:1',
            ],
            [
                'key' => 'beauty-pastel-studio',
                'name_fa' => 'استودیو پاستلی مینیمال',
                'name_en' => 'Minimal pastel studio',
                'description_fa' => 'شات تمیز کاتالوگی روی زمینه‌ی یکدست هم‌رنگ محصول. مطمئن‌ترین شات برای فروشگاه.',
                'category' => 'hero',
                'tokens' => ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['softbox-studio'], 'surface' => 'pastel-seamless', 'props' => ['none'], 'human' => 'none', 'mood' => 'clean-luxury'],
                'aspect_ratio_default' => '4:5',
            ],
        ];

        foreach ($shots as $index => $shot) {
            if (DB::table('shot_library')->where('key', $shot['key'])->exists()) {
                continue;
            }
            DB::table('shot_library')->insert([
                'key' => $shot['key'],
                'name_fa' => $shot['name_fa'],
                'name_en' => $shot['name_en'],
                'description_fa' => $shot['description_fa'],
                'category' => $shot['category'],
                'niche_tags' => json_encode($beauty),
                'tokens' => json_encode($shot['tokens']),
                'prompt_template' => null,
                'default_credits' => 12,
                'aspect_ratio_default' => $shot['aspect_ratio_default'],
                'sample_image' => null,
                'is_active' => true,
                'sort' => ($index + 1) * 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shot_library')) {
            DB::table('shot_library')->where('key', 'like', 'beauty-%')->delete();
        }
    }
};
