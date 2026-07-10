<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * ══════════════════════════════════════════════════════════════════
 * Seeder دسته‌بندی‌های وطن — کامل، درختی و idempotent
 * ──────────────────────────────────────────────────────────────────
 * • کل ساختار (۹ دسته اصلی + همه‌ی زیرشاخه‌ها) را یکجا در دیتابیس ثبت می‌کند.
 * • اجرای چندباره داده‌ی تکراری نمی‌سازد (کلید یکتا: path).
 * • تنظیمات دستی مدیر (فعال/غیرفعال، سئو، تصویر) هنگام اجرای دوباره
 *   بازنویسی نمی‌شوند؛ فقط ساختار (نام، slug، آیکون، رنگ، ترتیب) هم‌گام می‌شود.
 *
 * اجرا:
 *   php artisan db:seed --class=Database\\Seeders\\CategorySeeder
 * یا خودکار هنگام دیپلوی از طریق DatabaseSeeder.
 * ══════════════════════════════════════════════════════════════════
 */
class CategorySeeder extends Seeder
{
    /** رنگ پیش‌فرض عمومی در صورت نبود رنگ اختصاصی */
    private const DEFAULT_COLOR = '#6B7280';
    private const DEFAULT_ICON  = 'folder';

    public function run(): void
    {
        foreach ($this->tree() as $order => $root) {
            $this->seedNode($root, null, null, self::DEFAULT_ICON, self::DEFAULT_COLOR, $order + 1);
        }

        $this->command?->info('✔ ساختار کامل دسته‌بندی‌های وطن ثبت/به‌روزرسانی شد.');
    }

    /**
     * درج/به‌روزرسانی یک گره و فرزندانش به‌صورت بازگشتی.
     */
    private function seedNode(array $node, ?int $parentId, ?string $parentPath, string $inheritIcon, string $inheritColor, int $order): void
    {
        $slug  = $node['slug'];
        $path  = $parentPath ? ($parentPath . '/' . $slug) : $slug;
        $icon  = $node['icon']  ?? $inheritIcon;
        $color = $node['color'] ?? $inheritColor;

        $structural = [
            'parent_id'  => $parentId,
            'name'       => $node['fa'],
            'name_fa'    => $node['fa'],
            'name_en'    => $node['en'],
            'slug'       => $slug,
            'path'       => $path,
            'icon'       => $icon,
            'color'      => $color,
            'sort_order' => $order,
        ];

        $category = Category::where('path', $path)->first();

        if ($category) {
            // فقط فیلدهای ساختاری هم‌گام می‌شوند؛ is_active / is_featured / سئو / تصویر دست‌نخورده می‌مانند.
            $category->fill($structural)->save();
        } else {
            $category = Category::create($structural + [
                'is_active'   => true,
                'is_featured' => $parentId === null, // دسته‌های اصلی به‌صورت پیش‌فرض ویژه
            ]);
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $childOrder => $child) {
                $this->seedNode($child, $category->id, $path, $icon, $color, $childOrder + 1);
            }
        }
    }

    /** برگ‌ساز کمکی: آرایه‌ی زیرشاخه‌های ساده (fa => slug) را به ساختار گره تبدیل می‌کند. */
    private function leaves(array $map): array
    {
        return array_map(
            fn ($fa, $data) => ['fa' => $fa, 'en' => $data[0], 'slug' => $data[1]],
            array_keys($map),
            array_values($map)
        );
    }

    /**
     * کل درخت دسته‌بندی وطن.
     * هر گره: fa, en, slug و اختیاری icon/color/children.
     * زیرشاخه‌ها آیکون و رنگ را از والد به ارث می‌برند مگر اینکه مقدار اختصاصی داشته باشند.
     */
    private function tree(): array
    {
        return [
            // ۱) چهره و پرتره
            [
                'fa' => 'چهره و پرتره', 'en' => 'Portrait', 'slug' => 'portrait',
                'icon' => 'user-round', 'color' => '#6366F1',
                'children' => $this->leaves([
                    'پرتره حرفه‌ای' => ['Professional Portrait', 'professional'],
                    'عکس پرسنلی'    => ['ID Photo', 'id-photo'],
                    'بیزینسی'       => ['Corporate', 'corporate'],
                    'فشن'           => ['Fashion Portrait', 'fashion'],
                    'لوکس'          => ['Luxury Portrait', 'luxury'],
                    'مدلینگ'        => ['Modeling', 'modeling'],
                    'کودک'          => ['Kids', 'kids'],
                    'نوزاد'         => ['Baby', 'baby'],
                    'خانوادگی'      => ['Family', 'family'],
                    'عاشقانه'       => ['Romantic', 'romantic'],
                    'کاپلی'         => ['Couple', 'couple'],
                    'پدر و مادر'    => ['Parents', 'parents'],
                    'سلفی'          => ['Selfie', 'selfie'],
                    'آواتار'        => ['Avatar', 'avatar'],
                    'کاراکتر'       => ['Character', 'character'],
                    'اکشن فیگور'    => ['Action Figure', 'action-figure'],
                    'کارتونی'       => ['Cartoon', 'cartoon'],
                    'انیمه'         => ['Anime', 'anime'],
                    'سه بعدی'       => ['3D', '3d'],
                    'واقع‌گرایانه'   => ['Realistic', 'realistic'],
                    'فانتزی'        => ['Fantasy', 'fantasy'],
                ]),
            ],

            // ۲) کسب‌وکار و برند
            [
                'fa' => 'کسب‌وکار و برند', 'en' => 'Branding', 'slug' => 'branding',
                'icon' => 'briefcase', 'color' => '#0EA5E9',
                'children' => $this->leaves([
                    'لوگو'        => ['Logo', 'logo'],
                    'لوگو موشن'   => ['Logo Motion', 'logo-motion'],
                    'کارت ویزیت'  => ['Business Card', 'business-card'],
                    'سربرگ'       => ['Letterhead', 'letterhead'],
                    'مهر'         => ['Stamp', 'stamp'],
                    'امضا'        => ['Signature', 'signature'],
                    'ست اداری'    => ['Office Set', 'office-set'],
                ]),
            ],

            // ۳) شبکه‌های اجتماعی (سه سطحی)
            [
                'fa' => 'شبکه‌های اجتماعی', 'en' => 'Social Media', 'slug' => 'social',
                'icon' => 'share-2', 'color' => '#EC4899',
                'children' => [
                    [
                        'fa' => 'اینستاگرام', 'en' => 'Instagram', 'slug' => 'instagram', 'icon' => 'instagram',
                        'children' => $this->leaves([
                            'پست'       => ['Post', 'post'],
                            'استوری'    => ['Story', 'story'],
                            'کاور پست'  => ['Post Cover', 'post-cover'],
                            'کاور ریلز' => ['Reels Cover', 'reels-cover'],
                            'هایلایت'   => ['Highlight', 'highlight'],
                        ]),
                    ],
                    [
                        'fa' => 'یوتیوب', 'en' => 'YouTube', 'slug' => 'youtube', 'icon' => 'youtube',
                        'children' => $this->leaves([
                            'کاور ویدیو'    => ['Video Cover', 'video-cover'],
                            'بنر کانال'     => ['Channel Banner', 'channel-banner'],
                            'تصویر پروفایل' => ['Profile Picture', 'profile-picture'],
                        ]),
                    ],
                    [
                        'fa' => 'تلگرام', 'en' => 'Telegram', 'slug' => 'telegram', 'icon' => 'send',
                        'children' => $this->leaves([
                            'تصویر کانال'   => ['Channel Picture', 'channel-picture'],
                            'تصویر پروفایل' => ['Profile Picture', 'profile-picture'],
                            'پست'           => ['Post', 'post'],
                        ]),
                    ],
                ],
            ],

            // ۴) محصولات و کسب‌وکارها
            [
                'fa' => 'محصولات و کسب‌وکارها', 'en' => 'Businesses', 'slug' => 'business',
                'icon' => 'store', 'color' => '#F59E0B',
                'children' => $this->leaves([
                    'فروشگاه لباس'          => ['Clothing Store', 'clothing-store'],
                    'طلا و جواهر'           => ['Jewelry', 'jewelry'],
                    'رستوران'               => ['Restaurant', 'restaurant'],
                    'کافی‌شاپ'              => ['Coffee Shop', 'coffee-shop'],
                    'فست‌فود'               => ['Fast Food', 'fast-food'],
                    'شیرینی‌فروشی'          => ['Pastry Shop', 'pastry-shop'],
                    'آرایشگاه زنانه'        => ['Women Salon', 'womens-salon'],
                    'آرایشگاه مردانه'       => ['Men Barber', 'mens-barber'],
                    'سالن زیبایی'           => ['Beauty Salon', 'beauty-salon'],
                    'کلینیک زیبایی'         => ['Beauty Clinic', 'beauty-clinic'],
                    'پزشک'                  => ['Doctor', 'doctor'],
                    'دندانپزشکی'            => ['Dental', 'dental'],
                    'مشاور املاک'           => ['Real Estate', 'real-estate'],
                    'نمایشگاه خودرو'        => ['Car Dealership', 'car-dealership'],
                    'فروشگاه موبایل'        => ['Mobile Store', 'mobile-store'],
                    'فروشگاه لوازم خانگی'   => ['Home Appliances', 'home-appliances'],
                    'فروشگاه کیف و کفش'     => ['Bag & Shoe Store', 'bag-shoe-store'],
                    'فروشگاه ساعت'          => ['Watch Store', 'watch-store'],
                    'فروشگاه عطر'           => ['Perfume Store', 'perfume-store'],
                    'فروشگاه لوازم آرایشی'  => ['Cosmetics Store', 'cosmetics-store'],
                    'فروشگاه گل و گیاه'     => ['Flower Shop', 'flower-shop'],
                    'باشگاه ورزشی'          => ['Gym', 'gym'],
                    'مربی شخصی'             => ['Personal Trainer', 'personal-trainer'],
                    'آموزشگاه'              => ['Academy', 'academy'],
                    'مهدکودک'               => ['Kindergarten', 'kindergarten'],
                    'مزون لباس'             => ['Fashion House', 'fashion-house'],
                    'جواهرسازی'             => ['Jewelry Making', 'jewelry-making'],
                    'صنایع دستی'            => ['Handicrafts', 'handicrafts'],
                    'فروشگاه آنلاین'        => ['Online Shop', 'online-shop'],
                    'محصول تبلیغاتی'        => ['Promotional Product', 'promotional-product'],
                    'عکس محصول'             => ['Product Photo', 'product-photo'],
                ]),
            ],

            // ۵) ویدیوهای آماده هوش مصنوعی
            [
                'fa' => 'ویدیوهای آماده هوش مصنوعی', 'en' => 'AI Videos', 'slug' => 'video',
                'icon' => 'clapperboard', 'color' => '#8B5CF6',
                'children' => $this->leaves([
                    'ویدیو تولد'      => ['Birthday Video', 'birthday-video'],
                    'ویدیو عاشقانه'   => ['Romantic Video', 'romantic-video'],
                    'ویدیو فان'       => ['Fun Video', 'fun-video'],
                    'لوگو موشن'       => ['Logo Motion', 'logo-motion'],
                    'معرفی محصول'     => ['Product Showcase', 'product-showcase'],
                    'تبلیغ کوتاه'     => ['Short Ad', 'short-ad'],
                    'ویدیو مناسبتی'   => ['Occasion Video', 'occasion-video'],
                    'ویدیو تبریک'     => ['Greeting Video', 'greeting-video'],
                    'ویدیو دعوت'      => ['Invitation Video', 'invitation-video'],
                    'ویدیو اینستاگرامی' => ['Instagram Video', 'instagram-video'],
                    'ویدیو تبلیغاتی'  => ['Promo Video', 'promo-video'],
                    'ویدیو سینمایی'   => ['Cinematic Video', 'cinematic-video'],
                ]),
            ],

            // ۶) مناسبت‌ها
            [
                'fa' => 'مناسبت‌ها', 'en' => 'Occasions', 'slug' => 'occasions',
                'icon' => 'gift', 'color' => '#EF4444',
                'children' => $this->leaves([
                    'تولد'      => ['Birthday', 'birthday'],
                    'ازدواج'    => ['Wedding', 'wedding'],
                    'ولنتاین'   => ['Valentine', 'valentine'],
                    'نوروز'     => ['Nowruz', 'nowruz'],
                    'یلدا'      => ['Yalda', 'yalda'],
                    'کریسمس'    => ['Christmas', 'christmas'],
                    'رمضان'     => ['Ramadan', 'ramadan'],
                    'محرم'      => ['Muharram', 'muharram'],
                    'روز مادر'  => ['Mother Day', 'mothers-day'],
                    'روز پدر'   => ['Father Day', 'fathers-day'],
                ]),
            ],

            // ۷) پوشاک و مد
            [
                'fa' => 'پوشاک و مد', 'en' => 'Fashion', 'slug' => 'fashion',
                'icon' => 'shirt', 'color' => '#14B8A6',
                'children' => $this->leaves([
                    'لباس'         => ['Clothing', 'clothing'],
                    'کفش'          => ['Shoes', 'shoes'],
                    'کیف'          => ['Bags', 'bags'],
                    'اکسسوری'      => ['Accessories', 'accessories'],
                    'جواهرات'      => ['Jewelry', 'jewelry'],
                    'عینک'         => ['Glasses', 'glasses'],
                    'ساعت'         => ['Watch', 'watch'],
                    'استایل زنانه' => ['Women Style', 'womens-style'],
                    'استایل مردانه'=> ['Men Style', 'mens-style'],
                    'استایل کودک'  => ['Kids Style', 'kids-style'],
                ]),
            ],

            // ۸) قالب‌های آماده
            [
                'fa' => 'قالب‌های آماده', 'en' => 'Templates', 'slug' => 'templates',
                'icon' => 'layout-template', 'color' => '#3B82F6',
                'children' => $this->leaves([
                    'قالب اینستاگرام'  => ['Instagram Template', 'instagram-template'],
                    'قالب استوری'      => ['Story Template', 'story-template'],
                    'قالب پست'         => ['Post Template', 'post-template'],
                    'قالب رزومه'       => ['Resume Template', 'resume-template'],
                    'قالب کارت ویزیت'  => ['Business Card Template', 'business-card-template'],
                    'قالب پوستر'       => ['Poster Template', 'poster-template'],
                    'قالب بنر'         => ['Banner Template', 'banner-template'],
                    'قالب ارائه'       => ['Presentation Template', 'presentation-template'],
                    'قالب دعوت‌نامه'   => ['Invitation Template', 'invitation-template'],
                ]),
            ],

            // ۹) سبک طراحی
            [
                'fa' => 'سبک طراحی', 'en' => 'Design Styles', 'slug' => 'styles',
                'icon' => 'palette', 'color' => '#A855F7',
                'children' => $this->leaves([
                    'مینیمال'      => ['Minimal', 'minimal'],
                    'مدرن'         => ['Modern', 'modern'],
                    'لوکس'         => ['Luxury', 'luxury'],
                    'کلاسیک'       => ['Classic', 'classic'],
                    'وینتیج'       => ['Vintage', 'vintage'],
                    'آینده‌نگر'     => ['Futuristic', 'futuristic'],
                    'سه بعدی'      => ['3D', '3d'],
                    'واقع‌گرایانه'  => ['Realistic', 'realistic'],
                    'کارتونی'      => ['Cartoon', 'cartoon'],
                    'انیمه'        => ['Anime', 'anime'],
                    'فانتزی'       => ['Fantasy', 'fantasy'],
                    'نئون'         => ['Neon', 'neon'],
                    'دارک'         => ['Dark', 'dark'],
                    'روشن'         => ['Light', 'light'],
                ]),
            ],
        ];
    }
}
