<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArticleModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_fa')->nullable();
            $table->string('product_code')->nullable();
            $table->string('slug')->nullable();
            $table->string('route_slug')->nullable();
            $table->string('status')->default('active');
            $table->string('thumbnail')->nullable();
            $table->string('media_type')->default('photo');
            $table->string('preview_video_url')->nullable();
            $table->text('description_fa')->nullable();
            $table->unsignedInteger('credit_cost')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_fa');
            $table->string('path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_08_18_160000_create_articles_module.php');
        $migration->up();
    }

    public function test_default_content_is_public_and_has_seo_endpoints(): void
    {
        self::assertSame(6, Article::query()->count());
        self::assertSame(10, ArticleCategory::query()->count());

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('مقالات وطن')
            ->assertSee('آموزش ساخت عکس با هوش مصنوعی');

        $article = Article::query()->where('slug', 'how-to-create-ai-images')->firstOrFail();
        $this->get($article->publicUrl())
            ->assertOk()
            ->assertSee($article->title)
            ->assertSee('application/ld+json', false);

        self::assertSame(1, $article->fresh()->view_count);
        self::assertSame(1, $article->fresh()->unique_view_count);

        $this->get(route('articles.feed'))->assertOk()->assertHeader('content-type', 'application/rss+xml; charset=UTF-8');
        $sitemap = $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee($article->publicUrl(), false);
        self::assertNotFalse(simplexml_load_string($sitemap->getContent()));
        $this->get(route('site.sitemap'))
            ->assertOk()
            ->assertSee('نقشه سایت')
            ->assertSee(route('sitemap'), false);
        $this->get('/app/articles')->assertRedirect(route('articles.index'));
    }

    public function test_leader_can_create_an_auto_optimized_article_and_regular_admin_cannot_publish(): void
    {
        $leader = Admin::query()->create([
            'name' => 'مدیر ارشد', 'email' => 'leader@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);
        $payload = $this->validPayload();

        $this->actingAs($leader, 'admin')->post(route('admin.articles.store'), $payload)->assertRedirect();

        $article = Article::query()->where('title', $payload['title'])->firstOrFail();
        self::assertSame('published', $article->status);
        self::assertSame($payload['title'] . ' | وطن', $article->meta_title);
        self::assertNotEmpty($article->seo_keywords);
        self::assertNotEmpty($article->hashtags);
        self::assertSame('', data_get($article->content_blocks, '1.button_url', ''));
        self::assertDatabaseHas('article_revisions', ['article_id' => $article->id, 'version' => 1, 'action' => 'created']);

        foreach ([
            route('admin.articles.index'),
            route('admin.articles.create'),
            route('admin.articles.edit', $article),
            route('admin.article-categories.index'),
            route('admin.article-comments.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $admin = Admin::query()->create([
            'name' => 'مدیر محتوا', 'email' => 'editor@example.test', 'password' => 'password',
            'role' => 'admin', 'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin')->post(route('admin.articles.store'), [
            ...$this->validPayload(),
            'title' => 'مقاله غیرمجاز برای انتشار',
        ])->assertForbidden();
    }

    public function test_behavior_events_are_recorded_and_aggregate_dashboard_is_available(): void
    {
        $article = Article::query()->firstOrFail();
        $this->withSession(['visitor' => 'one'])->post(route('articles.events', $article), [
            'event_type' => 'product_cta_click',
            'metadata' => ['product_id' => 42, 'target' => '/app/product/test'],
        ])->assertNoContent();

        self::assertSame(1, $article->fresh()->cta_click_count);
        self::assertDatabaseHas('article_events', ['article_id' => $article->id, 'event_type' => 'product_cta_click']);

        $leader = Admin::query()->create([
            'name' => 'مدیر گزارش', 'email' => 'analytics@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);
        $this->actingAs($leader, 'admin')->get(route('admin.articles.analytics-overview'))
            ->assertOk()->assertSee('گزارش عملکرد مقالات')->assertSee('مقالات پربازدید');
        $this->get(route('admin.articles.analytics', $article))
            ->assertOk()->assertSee('پایش عملکرد مقاله');
    }

    private function validPayload(): array
    {
        return [
            'title' => 'راهنمای حرفه‌ای تست مقالات وطن',
            'excerpt' => 'این یک خلاصه کاربردی برای بررسی ثبت مقاله و تولید خودکار اطلاعات سئو است.',
            'article_category_id' => ArticleCategory::query()->firstOrFail()->id,
            'article_author_id' => ArticleAuthor::query()->firstOrFail()->id,
            'content_type' => 'guide',
            'status' => 'published',
            'content_blocks' => [
                ['type' => 'paragraph', 'content' => 'متن اصلی مقاله برای محاسبه زمان مطالعه و کلیدواژه‌ها.'],
                ['type' => 'cta', 'title' => 'شروع ساخت', 'button_url' => 'javascript:alert(1)'],
            ],
            'tags' => 'آموزش، هوش مصنوعی، وطن',
            'seo_auto_fill' => '1',
            'is_indexable' => '1',
            'allow_comments' => '1',
            'is_featured' => '0',
        ];
    }
}
