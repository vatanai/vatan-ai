<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * شات‌های تخصصی برای همه‌ی صنف‌های پروداکتی (کنار شات‌های آرایشی و عمومی قبلی).
 * idempotent: کلیدهای موجود (و ویرایش‌های ادمین) دست نمی‌خورند. قیمت پیش‌فرض ۱۲ کردیت مثل بقیه.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_library')) {
            return;
        }

        $sort = 400;
        foreach (self::shots() as [$key, $niches, $nameFa, $nameEn, $description, $category, $tokens, $ratio]) {
            $sort += 10;
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
                'sort' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shot_library')) {
            return;
        }
        $keys = array_column(self::shots(), 0);
        $used = Schema::hasTable('product_shots')
            ? DB::table('product_shots')->join('shot_library', 'shot_library.id', '=', 'product_shots.shot_id')->whereIn('shot_library.key', $keys)->pluck('shot_library.key')->all()
            : [];
        DB::table('shot_library')->whereIn('key', array_diff($keys, $used))->delete();
    }

    /** [key, niches, name_fa, name_en, description_fa, category, tokens, ratio] */
    private static function shots(): array
    {
        $none = ['none'];

        return [
            // پوشاک و مزون
            ['fashion-ghost-mannequin', ['fashion'], 'مانکن نامرئی استودیو', 'Ghost mannequin studio', 'لباس با فرم طبیعی پوشیده‌شده، بدون مانکن یا آدم؛ مطمئن‌ترین شات کاتالوگ پوشاک.', 'hero',
                ['framing' => 'ghost-mannequin', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['softbox-studio'], 'surface' => 'pastel-seamless', 'props' => $none, 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['fashion-model-editorial', ['fashion'], 'مدل تمام‌قد ادیتوریال', 'Full-body editorial model', 'مدل فرضی بزرگسال با لباس، ژست طبیعی مجله‌ای.', 'model',
                ['framing' => 'off-center-editorial', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => $none, 'human' => 'model-full-body', 'mood' => 'editorial'], '4:5'],
            ['fashion-street-style', ['fashion', 'sports'], 'استریت‌استایل شهری', 'Urban street style', 'مدل فرضی در خیابان شهر با حس استریت؛ مناسب ریلز و استوری.', 'lifestyle',
                ['framing' => 'wide-environment', 'camera' => 'eye-level', 'lens' => '35mm-wide', 'lighting' => ['natural-daylight'], 'surface' => 'city-street', 'props' => $none, 'human' => 'model-full-body', 'mood' => 'urban-street'], '9:16'],
            ['fashion-linen-flatlay', ['fashion', 'kids'], 'فلت‌لی روی کتان', 'Linen flat-lay', 'لباس تاشده و مرتب از بالا روی پارچه‌ی کتان با برگ‌های تزئینی.', 'flatlay',
                ['framing' => 'flatlay-topdown', 'camera' => 'top-down', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'linen-fabric', 'props' => ['leaves-branches'], 'human' => 'none', 'mood' => 'warm-natural'], '1:1'],
            ['fashion-fabric-macro', ['fashion'], 'جزئیات پارچه و دوخت', 'Fabric and stitching macro', 'کلوزآپ از بافت پارچه، دکمه و دوخت؛ کیفیت جنس را نشان می‌دهد.', 'detail',
                ['framing' => 'closeup-macro', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'linen-fabric', 'props' => $none, 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],

            // کیف، کفش و چرم
            ['bags-shoes-concrete-hero', ['bags-shoes'], 'هیرو روی بتن با نور دراماتیک', 'Concrete dramatic hero', 'محصول روی بلوک بتنی با نور موضعی؛ حس برند لوکس و جسور.', 'hero',
                ['framing' => 'hero-centered', 'camera' => 'low-angle', 'lens' => '85mm-portrait', 'lighting' => ['dramatic-spot', 'rim-light'], 'surface' => 'concrete-urban', 'props' => $none, 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['bags-shoes-on-feet', ['bags-shoes', 'sports'], 'کفش روی پا در شهر', 'Shoes worn on the street', 'فقط پا و کفش در حال قدم زدن در خیابان؛ طبیعی و واقعی.', 'lifestyle',
                ['framing' => 'three-quarter', 'camera' => 'low-angle', 'lens' => '35mm-wide', 'lighting' => ['natural-daylight'], 'surface' => 'city-street', 'props' => $none, 'human' => 'feet-wearing', 'mood' => 'urban-street'], '4:5'],
            ['bags-shoes-model-carry', ['bags-shoes', 'jewelry'], 'کیف همراه مدل', 'Bag carried by model', 'مدل فرضی بزرگسال با کیف روی شانه یا در دست؛ ادیتوریال.', 'model',
                ['framing' => 'off-center-editorial', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['soft-window'], 'surface' => 'city-street', 'props' => $none, 'human' => 'model-full-body', 'mood' => 'editorial'], '4:5'],
            ['bags-shoes-leather-macro', ['bags-shoes'], 'جزئیات چرم و سگک', 'Leather and hardware macro', 'کلوزآپ از بافت چرم، دوخت و یراق‌آلات.', 'detail',
                ['framing' => 'closeup-macro', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['dramatic-spot', 'rim-light'], 'surface' => 'wooden-table', 'props' => $none, 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['bags-shoes-sand-golden', ['bags-shoes'], 'روی شن در ساعت طلایی', 'Golden hour on sand', 'محصول روی شن نرم با نور گرم غروب؛ حس سفر و تابستان.', 'lifestyle',
                ['framing' => 'three-quarter', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['golden-hour'], 'surface' => 'sand-dune', 'props' => $none, 'human' => 'none', 'mood' => 'warm-natural'], '4:5'],

            // طلا، جواهر و اکسسوری
            ['jewelry-velvet-sparkle', ['jewelry'], 'ویترین مخمل با درخشش', 'Velvet display sparkle', 'جواهر روی پایه‌ی مخمل تیره با درخشش طبیعی؛ کلاسیک جواهرفروشی.', 'hero',
                ['framing' => 'hero-centered', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['dramatic-spot', 'rim-light'], 'surface' => 'velvet-display', 'props' => ['sparkle'], 'human' => 'none', 'mood' => 'clean-luxury'], '4:5'],
            ['jewelry-on-model', ['jewelry'], 'روی بدن مدل (کلوزآپ)', 'Worn by model close-up', 'جواهر یا ساعت روی گردن، گوش یا مچ مدل فرضی؛ اندازه‌ی واقعی را نشان می‌دهد.', 'model',
                ['framing' => 'closeup-macro', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => $none, 'human' => 'model-wearing-closeup', 'mood' => 'editorial'], '4:5'],
            ['jewelry-water-caustics', ['jewelry'], 'روی سطح آب با انعکاس نور', 'Water surface caustics', 'جواهر روی سطح آرام آب با رقص نور؛ سورئال و لوکس.', 'hero',
                ['framing' => 'three-quarter', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['caustics', 'rim-light'], 'surface' => 'water-surface', 'props' => ['sparkle'], 'human' => 'none', 'mood' => 'fresh-cool'], '4:5'],
            ['jewelry-gift-box', ['jewelry', 'services'], 'آماده‌ی هدیه', 'Gift ready', 'محصول کنار جعبه‌ی هدیه‌ی باز روی ساتن؛ مناسب مناسبت‌ها.', 'lifestyle',
                ['framing' => 'three-quarter', 'camera' => '45deg', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'satin-fabric', 'props' => ['gift-box', 'petals'], 'human' => 'none', 'mood' => 'warm-natural'], '4:5'],
            ['jewelry-black-spotlight', ['jewelry'], 'نور موضعی روی زمینه‌ی تیره', 'Black spotlight', 'زمینه‌ی تیره و یک نور موضعی؛ تمام توجه روی جزئیات و براقی.', 'detail',
                ['framing' => 'closeup-macro', 'camera' => 'eye-level', 'lens' => 'macro-100mm', 'lighting' => ['dramatic-spot'], 'surface' => 'dark-gradient', 'props' => ['sparkle'], 'human' => 'none', 'mood' => 'clean-luxury'], '1:1'],

            // غذا و نوشیدنی
            ['food-rustic-table', ['food'], 'میز چوبی با بخار تازه', 'Rustic table with steam', 'غذا یا نوشیدنی روی میز چوبی با بخار و تزئین تازه؛ اشتهاآور.', 'hero',
                ['framing' => 'three-quarter', 'camera' => '45deg', 'lens' => '85mm-portrait', 'lighting' => ['soft-window'], 'surface' => 'wooden-table', 'props' => ['steam', 'fresh-garnish'], 'human' => 'none', 'mood' => 'appetizing'], '4:5'],
            ['food-topdown-spread', ['food'], 'چیدمان از بالا', 'Top-down spread', 'نمای کامل از بالا با مواد اولیه و تزئین؛ مناسب منو و پست.', 'flatlay',
                ['framing' => 'flatlay-topdown', 'camera' => 'top-down', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'wooden-table', 'props' => ['ingredients-scatter', 'fresh-garnish'], 'human' => 'none', 'mood' => 'appetizing'], '1:1'],
            ['food-drink-splash', ['food'], 'پاشش نوشیدنی', 'Drink splash', 'نوشیدنی یا محصول با پاشش منجمد مایع و قطره‌های خنک.', 'hero',
                ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['softbox-studio', 'rim-light'], 'surface' => 'pastel-seamless', 'props' => ['liquid-splash', 'water-droplets'], 'human' => 'none', 'mood' => 'fresh-cool'], '9:16'],
            ['food-hands-serving', ['food', 'home'], 'دست‌ها در حال سرو', 'Hands serving', 'دست‌ها در حال سرو یا استفاده روی کانتر آشپزخانه؛ زنده و واقعی.', 'lifestyle',
                ['framing' => 'off-center-editorial', 'camera' => '45deg', 'lens' => '50mm-natural', 'lighting' => ['natural-daylight'], 'surface' => 'kitchen-counter', 'props' => ['fresh-garnish'], 'human' => 'hands-using', 'mood' => 'cozy-home'], '4:5'],
            ['food-moody-dark', ['food'], 'تیره و سینمایی', 'Dark moody', 'نور موضعی روی زمینه‌ی تیره؛ حس رستوران لوکس.', 'detail',
                ['framing' => 'closeup-macro', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['dramatic-spot'], 'surface' => 'dark-gradient', 'props' => ['steam'], 'human' => 'none', 'mood' => 'appetizing'], '4:5'],

            // خانه و دکوراسیون
            ['home-styled-interior', ['home'], 'داخل دکور مدرن', 'Styled modern interior', 'محصول در یک خانه‌ی شیک و واقعی؛ کاربرد را نشان می‌دهد.', 'lifestyle',
                ['framing' => 'wide-environment', 'camera' => 'eye-level', 'lens' => '35mm-wide', 'lighting' => ['soft-window'], 'surface' => 'modern-interior', 'props' => ['leaves-branches'], 'human' => 'none', 'mood' => 'cozy-home'], '4:5'],
            ['home-golden-table', ['home', 'kids'], 'میز چوبی در نور طلایی', 'Golden hour wooden table', 'نور گرم عصر روی میز چوبی؛ حس آرامش خانه.', 'hero',
                ['framing' => 'three-quarter', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['golden-hour'], 'surface' => 'wooden-table', 'props' => ['leaves-branches'], 'human' => 'none', 'mood' => 'cozy-home'], '4:5'],
            ['home-material-macro', ['home'], 'جزئیات جنس و ساخت', 'Material detail', 'کلوزآپ از جنس، بافت و کیفیت ساخت.', 'detail',
                ['framing' => 'closeup-macro', 'camera' => '45deg', 'lens' => 'macro-100mm', 'lighting' => ['soft-window', 'rim-light'], 'surface' => 'linen-fabric', 'props' => $none, 'human' => 'none', 'mood' => 'warm-natural'], '4:5'],

            // کالای دیجیتال
            ['digital-neon-hero', ['digital', 'automotive'], 'هیرو تکنولوژی با نور نئون', 'Neon tech hero', 'محصول روی زمینه‌ی تیره با نور لبه و نئون؛ حس تکنولوژی.', 'hero',
                ['framing' => 'hero-centered', 'camera' => 'low-angle', 'lens' => '85mm-portrait', 'lighting' => ['neon-accent', 'rim-light'], 'surface' => 'dark-gradient', 'props' => $none, 'human' => 'none', 'mood' => 'bold-tech'], '4:5'],
            ['digital-desk-hands', ['digital'], 'روی میز کار، در حال استفاده', 'Desk in use', 'دست‌ها در حال استفاده از محصول روی میز کار؛ کاربردی و واقعی.', 'lifestyle',
                ['framing' => 'off-center-editorial', 'camera' => '45deg', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'wooden-table', 'props' => $none, 'human' => 'hands-using', 'mood' => 'clean-luxury'], '4:5'],
            ['digital-float-dynamic', ['digital'], 'معلق با حس آینده', 'Futuristic float', 'محصول معلق و کمی چرخیده با نور نئون؛ مناسب کاور و استوری.', 'hero',
                ['framing' => 'three-quarter', 'camera' => 'dutch-tilt', 'lens' => '35mm-wide', 'lighting' => ['neon-accent'], 'surface' => 'dark-gradient', 'props' => ['levitation'], 'human' => 'none', 'mood' => 'bold-tech'], '9:16'],

            // سلامت، ورزش، کودک، خودرو
            ['health-clean-natural', ['health', 'beauty'], 'تمیز و طبیعی با برگ سبز', 'Clean natural greens', 'زمینه‌ی روشن با برگ‌های سبز و نور نرم؛ حس سلامت و اعتماد.', 'hero',
                ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['soft-window'], 'surface' => 'pastel-seamless', 'props' => ['leaves-branches', 'water-droplets'], 'human' => 'none', 'mood' => 'fresh-cool'], '4:5'],
            ['health-hand-hold', ['health'], 'در دست با پس‌زمینه‌ی محو', 'Hand-held soft background', 'محصول در دست؛ مقیاس و اعتماد را نشان می‌دهد.', 'lifestyle',
                ['framing' => 'off-center-editorial', 'camera' => 'eye-level', 'lens' => '85mm-portrait', 'lighting' => ['natural-daylight'], 'surface' => 'outdoor-nature', 'props' => $none, 'human' => 'hand-hold', 'mood' => 'fresh-cool'], '4:5'],
            ['sports-outdoor-action', ['sports'], 'طبیعت و حرکت', 'Outdoor action', 'محصول در طبیعت با نور روز و حس انرژی و حرکت.', 'lifestyle',
                ['framing' => 'wide-environment', 'camera' => 'low-angle', 'lens' => '35mm-wide', 'lighting' => ['natural-daylight'], 'surface' => 'outdoor-nature', 'props' => $none, 'human' => 'none', 'mood' => 'playful-pop'], '4:5'],
            ['kids-playful-bubbles', ['kids'], 'شاد و پاستلی با حباب', 'Playful pastel bubbles', 'زمینه‌ی پاستلی، حباب و نور نرم؛ شاد و امن.', 'hero',
                ['framing' => 'hero-centered', 'camera' => 'eye-level', 'lens' => '50mm-natural', 'lighting' => ['softbox-studio'], 'surface' => 'pastel-seamless', 'props' => ['bubbles'], 'human' => 'none', 'mood' => 'playful-pop'], '4:5'],
            ['automotive-dramatic', ['automotive'], 'دراماتیک روی بتن', 'Dramatic concrete', 'نور موضعی و زمینه‌ی بتنی؛ قدرت و کیفیت قطعه یا لوازم.', 'hero',
                ['framing' => 'three-quarter', 'camera' => 'low-angle', 'lens' => '85mm-portrait', 'lighting' => ['dramatic-spot', 'rim-light'], 'surface' => 'concrete-urban', 'props' => $none, 'human' => 'none', 'mood' => 'bold-tech'], '4:5'],
        ];
    }
};
