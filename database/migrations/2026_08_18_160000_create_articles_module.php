<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_authors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->json('same_as')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_indexable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['parent_id', 'is_active', 'sort_order']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_id')->constrained('article_categories')->restrictOnDelete();
            $table->foreignId('article_author_id')->constrained('article_authors')->restrictOnDelete();
            $table->unsignedBigInteger('reviewer_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->json('content_blocks');
            $table->string('content_type', 40)->default('guide')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('og_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->json('hashtags')->nullable();
            $table->boolean('seo_auto_fill')->default(true);
            $table->boolean('is_indexable')->default(true);
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('allow_comments')->default(true);
            $table->unsignedInteger('reading_minutes')->default(1);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('unique_view_count')->default(0);
            $table->unsignedBigInteger('share_count')->default(0);
            $table->unsignedBigInteger('cta_click_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
            $table->index(['article_category_id', 'status', 'published_at'], 'articles_category_status_publish_idx');
        });

        Schema::create('article_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('article_article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('article_tag_id')->constrained('article_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'article_tag_id']);
        });

        Schema::create('article_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('placement', 30)->default('related');
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('cta_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'product_id', 'placement'], 'article_product_placement_unique');
        });

        Schema::create('article_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->string('display_style', 30)->default('grid');
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['article_id', 'is_active', 'sort_order']);
        });

        Schema::create('article_gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_gallery_id')->constrained('article_galleries')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('generated_image_id')->nullable()->index();
            $table->unsignedBigInteger('generation_id')->nullable()->index();
            $table->string('media_type', 20)->default('image');
            $table->string('media_path', 2048);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('article_comments')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['article_id', 'status', 'created_at']);
        });

        Schema::create('article_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_hash', 64)->index();
            $table->string('event_type', 40)->index();
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['article_id', 'event_type', 'created_at']);
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('action', 30)->default('updated');
            $table->string('change_note')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'version']);
        });

        Schema::create('article_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->nullable()->constrained('articles')->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->string('target_url', 2048)->nullable();
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $now = now();
        $authorId = DB::table('article_authors')->insertGetId([
            'name' => 'تیم تحریریه وطن',
            'slug' => 'vatan-editorial-team',
            'title' => 'آموزش و تجربه عملی هوش مصنوعی',
            'bio' => 'تیم تحریریه وطن راهنماها را با نمونه‌های واقعی، پرامپت‌های تست‌شده و تجربه کار با محصولات وطن آماده می‌کند.',
            'same_as' => json_encode([], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categories = [
            ['آموزش ساخت تصویر', 'ai-image-tutorials', 'راهنماهای گام‌به‌گام ساخت و ویرایش تصویر با هوش مصنوعی.'],
            ['آموزش ساخت ویدیو', 'ai-video-tutorials', 'آموزش تبدیل ایده و تصویر به ویدیوی حرفه‌ای.'],
            ['پرامپت‌ها و ایده‌های آماده', 'prompts-and-ideas', 'پرامپت‌های فارسی و ایده‌های تست‌شده برای تولید محتوا.'],
            ['هوش مصنوعی برای کسب‌وکار', 'ai-for-business', 'کاربردهای عملی هوش مصنوعی برای فروش، تبلیغات و برند.'],
            ['شبکه‌های اجتماعی', 'social-media', 'ساخت محتوای حرفه‌ای برای اینستاگرام و شبکه‌های اجتماعی.'],
            ['عکس محصول و تبلیغات', 'product-photography', 'آموزش عکس محصول، بنر و تصویر تبلیغاتی.'],
            ['پرتره و چهره', 'portrait-and-face', 'راهنمای ساخت پرتره و حفظ شباهت چهره.'],
            ['مد و پوشاک', 'fashion', 'تولید تصویر و ویدیوی تخصصی برای پوشاک و مزون.'],
            ['ترندها و مدل‌های هوش مصنوعی', 'ai-models-and-trends', 'بررسی کاربردی مدل‌ها، قابلیت‌ها و ترندهای تازه.'],
            ['راهنمای وطن', 'vatan-guides', 'آموزش استفاده بهتر از امکانات و محصولات وطن.'],
        ];

        $categoryIds = [];
        foreach ($categories as $index => [$name, $slug, $description]) {
            $categoryIds[$slug] = DB::table('article_categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'meta_title' => $name . ' | مقالات وطن',
                'meta_description' => $description,
                'is_active' => true,
                'is_indexable' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $articles = [
            [
                'category' => 'ai-image-tutorials',
                'title' => 'آموزش ساخت عکس با هوش مصنوعی؛ از ایده تا خروجی حرفه‌ای',
                'slug' => 'how-to-create-ai-images',
                'excerpt' => 'در این راهنما یاد می‌گیرید چگونه ایده، تصویر مرجع و سبک مناسب را انتخاب کنید و در وطن به یک خروجی حرفه‌ای برسید.',
                'image' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
                'tags' => ['ساخت تصویر', 'هوش مصنوعی', 'آموزش وطن'],
                'featured' => true,
                'blocks' => $this->guideBlocks('ساخت تصویر حرفه‌ای از یک انتخاب درست شروع می‌شود؛ هدف، تصویر مرجع و کاربرد خروجی را قبل از ساخت مشخص کنید.', 'برای پرتره از عکس واضح روبه‌رو و برای عکس محصول از تصویری با نور یکنواخت استفاده کنید.', 'یک پرتره سینمایی واقع‌گرایانه با نور نرم، جزئیات طبیعی پوست و پس‌زمینه عمیق'),
            ],
            [
                'category' => 'portrait-and-face',
                'title' => 'چطور شباهت چهره را در تصاویر هوش مصنوعی حفظ کنیم؟',
                'slug' => 'preserve-face-identity-ai',
                'excerpt' => 'با انتخاب عکس مرجع مناسب، زاویه درست و تنظیم شدت هویت، خروجی‌هایی طبیعی‌تر و شبیه‌تر به چهره واقعی بسازید.',
                'image' => 'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
                'tags' => ['حفظ چهره', 'پرتره', 'تصویر مرجع'],
                'featured' => true,
                'blocks' => $this->guideBlocks('بیشترین خطاهای شباهت از تصویر مرجع نامناسب ایجاد می‌شوند، نه از متن پرامپت.', 'عکس بدون فیلتر، نور مستقیم و چهره کامل بهترین مرجع است. برای نتیجه دقیق‌تر می‌توانید چند زاویه هماهنگ اضافه کنید.', 'پرتره حرفه‌ای همان شخص با حفظ فرم صورت، چشم‌ها و جزئیات طبیعی چهره'),
            ],
            [
                'category' => 'product-photography',
                'title' => 'ساخت عکس محصول حرفه‌ای بدون استودیو',
                'slug' => 'ai-product-photography-guide',
                'excerpt' => 'محصول خود را با پس‌زمینه تمیز، نور تبلیغاتی و ترکیب‌بندی مناسب فروشگاه و شبکه‌های اجتماعی نمایش دهید.',
                'image' => 'assets/img/ai-photo-editor-prompt.webp',
                'tags' => ['عکس محصول', 'تبلیغات', 'فروشگاه آنلاین'],
                'featured' => true,
                'blocks' => $this->guideBlocks('یک عکس ساده و واضح از محصول می‌تواند پایه یک تصویر تبلیغاتی حرفه‌ای باشد.', 'مرزهای محصول باید مشخص باشد و نوشته یا لوگوی روی بسته‌بندی تا حد ممکن واضح ثبت شود.', 'عکس تبلیغاتی محصول روی سطح مینیمال با نور استودیویی نرم و سایه طبیعی'),
            ],
            [
                'category' => 'ai-video-tutorials',
                'title' => 'آموزش تبدیل عکس به ویدیو با حرکت طبیعی',
                'slug' => 'turn-image-into-ai-video',
                'excerpt' => 'یاد بگیرید حرکت دوربین، سوژه و فضای ویدیو را دقیق توصیف کنید تا یک عکس ثابت به ویدیویی زنده تبدیل شود.',
                'image' => 'assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg',
                'tags' => ['تبدیل عکس به ویدیو', 'ویدیوی هوش مصنوعی', 'حرکت دوربین'],
                'featured' => false,
                'blocks' => $this->guideBlocks('در ویدیوی هوش مصنوعی، حرکت کوتاه و مشخص معمولاً طبیعی‌تر از چند حرکت هم‌زمان است.', 'ابتدا حرکت اصلی را انتخاب کنید؛ حرکت آرام دوربین، پلک‌زدن، وزش مو یا جابه‌جایی نور.', 'حرکت آرام دوربین به سمت سوژه، وزش ملایم باد و نور سینمایی طبیعی'),
            ],
            [
                'category' => 'prompts-and-ideas',
                'title' => '۲۰ ایده و پرامپت آماده برای پرتره‌های سینمایی',
                'slug' => 'cinematic-portrait-prompts',
                'excerpt' => 'مجموعه‌ای از ایده‌های آماده برای نورپردازی، قاب‌بندی و فضای سینمایی که می‌توانید مستقیماً در وطن استفاده کنید.',
                'image' => 'assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg',
                'tags' => ['پرامپت آماده', 'پرتره سینمایی', 'ایده تصویر'],
                'featured' => false,
                'blocks' => $this->guideBlocks('پرامپت خوب فقط فهرست صفت‌ها نیست؛ باید سوژه، نور، زاویه دوربین و حس صحنه را روشن کند.', 'هر ایده را با جنسیت، پوشش، موقعیت و نسبت تصویر موردنیاز خودتان هماهنگ کنید.', 'پرتره سینمایی در خیابان بارانی شب، نورهای نئونی، نمای نزدیک و عمق میدان کم'),
            ],
            [
                'category' => 'social-media',
                'title' => 'راهنمای تولید محتوای اینستاگرام برای فروشگاه‌ها با هوش مصنوعی',
                'slug' => 'ai-instagram-content-for-shops',
                'excerpt' => 'یک مسیر عملی برای ساخت پست، استوری و تصاویر کمپین هماهنگ با هویت بصری فروشگاه و محصول شما.',
                'image' => 'assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp',
                'tags' => ['اینستاگرام', 'تولید محتوا', 'کسب‌وکار'],
                'featured' => false,
                'blocks' => $this->guideBlocks('هماهنگی مجموعه محتوا از کیفیت تک‌تصویر مهم‌تر است؛ رنگ، نور و نوع قاب را در یک کمپین ثابت نگه دارید.', 'برای هر کمپین یک تصویر اصلی، چند برش استوری و یک نسخه بدون متن آماده کنید.', 'تصویر تبلیغاتی مینیمال برای اینستاگرام با فضای خالی مناسب متن و رنگ‌های هماهنگ برند'),
            ],
        ];

        foreach ($articles as $index => $data) {
            $publishedAt = $now->copy()->subDays(30 - ($index * 4));
            $articleId = DB::table('articles')->insertGetId([
                'article_category_id' => $categoryIds[$data['category']],
                'article_author_id' => $authorId,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'],
                'content_blocks' => json_encode($data['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'content_type' => str_contains($data['slug'], 'prompts') ? 'prompt_pack' : 'guide',
                'status' => 'published',
                'featured_image' => $data['image'],
                'featured_image_alt' => $data['title'],
                'og_image' => $data['image'],
                'meta_title' => $data['title'] . ' | وطن',
                'meta_description' => $data['excerpt'],
                'seo_keywords' => json_encode($data['tags'], JSON_UNESCAPED_UNICODE),
                'hashtags' => json_encode(array_map(fn ($tag) => '#' . str_replace(' ', '_', $tag), $data['tags']), JSON_UNESCAPED_UNICODE),
                'seo_auto_fill' => true,
                'is_indexable' => true,
                'is_featured' => $data['featured'],
                'allow_comments' => true,
                'reading_minutes' => 5,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);

            $galleryId = DB::table('article_galleries')->insertGetId([
                'article_id' => $articleId,
                'title' => 'نمونه‌های مرتبط',
                'description' => 'چند نمونه برای درک بهتر نتیجه و انتخاب سبک مناسب.',
                'source_type' => 'manual',
                'display_style' => 'grid',
                'settings' => json_encode(['limit' => 6], JSON_UNESCAPED_UNICODE),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);

            foreach (array_values(array_unique([$data['image'], $articles[($index + 1) % count($articles)]['image'], $articles[($index + 2) % count($articles)]['image']])) as $itemIndex => $image) {
                DB::table('article_gallery_items')->insert([
                    'article_gallery_id' => $galleryId,
                    'media_type' => 'image',
                    'media_path' => $image,
                    'title' => 'نمونه ' . ($itemIndex + 1),
                    'alt_text' => $data['title'] . '، نمونه ' . ($itemIndex + 1),
                    'sort_order' => $itemIndex + 1,
                    'is_active' => true,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ]);
            }

            foreach ($data['tags'] as $tagName) {
                $tagSlug = 'tag-' . substr(sha1($tagName), 0, 12);
                DB::table('article_tags')->updateOrInsert(
                    ['slug' => $tagSlug],
                    ['name' => $tagName, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                );
                $tagId = DB::table('article_tags')->where('slug', $tagSlug)->value('id');
                DB::table('article_article_tag')->insertOrIgnore(['article_id' => $articleId, 'article_tag_id' => $tagId]);
            }
        }

        if (Schema::hasTable('site_pages')) {
            DB::table('site_pages')->where('key', 'articles')->update([
                'status' => 'published',
                'title' => 'مقالات وطن',
                'subtitle' => 'آموزش‌های کاربردی ساخت تصویر، ویدیو و محتوای حرفه‌ای با هوش مصنوعی',
                'meta_title' => 'مقالات و آموزش هوش مصنوعی | وطن',
                'meta_description' => 'راهنماهای کاربردی، پرامپت‌های آماده و آموزش ساخت تصویر و ویدیو با هوش مصنوعی در وطن.',
                'is_indexable' => true,
                'published_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function guideBlocks(string $intro, string $tip, string $prompt): array
    {
        return [
            ['type' => 'lead', 'content' => $intro],
            ['type' => 'heading', 'level' => 2, 'content' => 'از کجا شروع کنیم؟'],
            ['type' => 'paragraph', 'content' => $tip],
            ['type' => 'note', 'title' => 'نکته کاربردی', 'content' => 'قبل از ساخت نسخه نهایی، یک خروجی آزمایشی بسازید و فقط یک متغیر را در هر مرحله تغییر دهید.'],
            ['type' => 'heading', 'level' => 2, 'content' => 'پرامپت پیشنهادی'],
            ['type' => 'prompt', 'content' => $prompt],
            ['type' => 'paragraph', 'content' => 'بعد از ساخت، نتیجه را با هدف اولیه مقایسه کنید و در صورت نیاز نور، زاویه یا جزئیات صحنه را دقیق‌تر بنویسید.'],
        ];
    }

    public function down(): void
    {
        Schema::dropIfExists('article_redirects');
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_events');
        Schema::dropIfExists('article_comments');
        Schema::dropIfExists('article_gallery_items');
        Schema::dropIfExists('article_galleries');
        Schema::dropIfExists('article_product');
        Schema::dropIfExists('article_article_tag');
        Schema::dropIfExists('article_tags');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('article_authors');
    }
};
