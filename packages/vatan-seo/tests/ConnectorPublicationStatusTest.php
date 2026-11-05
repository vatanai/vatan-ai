<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;
use Vatan\Seo\Agents\ContentQualityGate;
use Vatan\Seo\Agents\Publisher;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;

class ConnectorPublicationStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('seo_sites', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('domain'); $table->string('base_url');
            $table->string('platform')->default('laravel_local'); $table->text('connector_config')->nullable();
            $table->json('settings')->nullable(); $table->decimal('monthly_budget_usd', 8, 2)->default(20); $table->timestamps();
        });
        Schema::create('seo_content_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->unsignedBigInteger('keyword_id')->nullable();
            $table->string('type'); $table->string('status'); $table->string('title'); $table->string('slug');
            $table->string('meta_title')->nullable(); $table->text('meta_description')->nullable(); $table->json('blocks');
            $table->json('brief')->nullable(); $table->json('quality')->nullable(); $table->unsignedInteger('word_count')->default(0);
            $table->string('published_ref')->nullable(); $table->text('published_url')->nullable(); $table->timestamp('published_at')->nullable();
            $table->string('approved_by')->nullable(); $table->timestamp('approved_at')->nullable(); $table->text('reviewer_note')->nullable();
            $table->timestamps();
        });
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('article_authors', function (Blueprint $table) {
            $table->id(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('articles', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('article_category_id')->nullable(); $table->unsignedBigInteger('article_author_id')->nullable();
            $table->string('title'); $table->string('slug')->unique(); $table->text('excerpt')->nullable(); $table->json('content_blocks')->nullable();
            $table->string('content_type')->nullable(); $table->string('status'); $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable(); $table->json('seo_keywords')->nullable(); $table->boolean('seo_auto_fill')->default(false);
            $table->boolean('is_indexable')->default(true); $table->boolean('allow_comments')->default(true);
            $table->unsignedInteger('reading_minutes')->default(1); $table->timestamp('published_at')->nullable();
            $table->softDeletes(); $table->timestamps();
        });

        config(['seo-engine.host' => array_merge((array) config('seo-engine.host'), [
            'article_model' => Article::class,
            'article_category_model' => ArticleCategory::class,
            'article_author_model' => ArticleAuthor::class,
            'article_route' => null,
            'article_url' => 'articles/{slug}',
            'publish_status' => 'published',
        ])]);
    }

    protected function tearDown(): void
    {
        foreach (['articles', 'article_categories', 'article_authors', 'seo_content_items', 'seo_sites'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    public function test_destination_draft_is_not_recorded_as_publication_or_task_completion(): void
    {
        $site = Site::create(['name' => 'وطن', 'domain' => 'aivatan.com', 'base_url' => 'https://aivatan.com']);
        $site->setConnectorConfig(['publish_status' => 'draft']);
        $site->save();
        $category = ArticleCategory::create(['is_active' => true]);
        $author = ArticleAuthor::create(['is_active' => true]);
        config(['seo-engine.host.publish.category_id' => $category->id, 'seo-engine.host.publish.author_id' => $author->id]);

        $item = ContentItem::create([
            'site_id' => $site->id,
            'type' => 'article',
            'status' => 'review',
            'title' => 'راهنمای آزمایشی',
            'slug' => 'test-guide',
            'meta_title' => 'راهنمای آزمایشی برای فروشگاه‌ها',
            'meta_description' => 'راهنمای آزمایشی برای بررسی وضعیت انتشار مقاله.',
            'blocks' => [['type' => 'lead', 'content' => 'این متن نمونه‌ی کافی برای آزمون است.']],
            'brief' => ['word_target' => 1200],
            'quality' => ['gate' => ['passed' => true]],
            'word_count' => 8,
        ]);

        $gate = \Mockery::mock(ContentQualityGate::class);
        $gate->shouldReceive('evaluate')->once()->with($site, $item)->andReturn(['passed' => true]);
        $result = (new Publisher($gate))->publish($site, $item, 'test');

        self::assertSame('draft', $result->status);
        self::assertSame('draft', Article::whereKey($result->published_ref)->value('status'));
        self::assertNull($result->published_url);
        self::assertNull($result->published_at);
        self::assertStringContainsString('انتشار عمومی انجام نشده', $result->reviewer_note);
    }

    #[RunInSeparateProcess]
    public function test_publication_mode_marks_both_destination_and_seo_item_as_published(): void
    {
        $site = Site::create(['name' => 'وطن', 'domain' => 'aivatan.com', 'base_url' => 'https://aivatan.com']);
        $site->setConnectorConfig(['publish_status' => 'publish']);
        $site->save();
        $category = ArticleCategory::create(['is_active' => true]);
        $author = ArticleAuthor::create(['is_active' => true]);
        config(['seo-engine.host.publish.category_id' => $category->id, 'seo-engine.host.publish.author_id' => $author->id]);

        $item = ContentItem::create([
            'site_id' => $site->id,
            'type' => 'article',
            'status' => 'review',
            'title' => 'راهنمای آزمایشی',
            'slug' => 'test-guide',
            'meta_title' => 'راهنمای آزمایشی برای فروشگاه‌ها',
            'meta_description' => 'راهنمای آزمایشی برای بررسی وضعیت انتشار مقاله.',
            'blocks' => [['type' => 'lead', 'content' => 'این متن نمونه‌ی کافی برای آزمون است.']],
            'brief' => ['word_target' => 1200],
            'quality' => ['gate' => ['passed' => true]],
            'word_count' => 8,
        ]);

        $gate = \Mockery::mock(ContentQualityGate::class);
        $gate->shouldReceive('evaluate')->once()->with($site, $item)->andReturn(['passed' => true]);
        $result = (new Publisher($gate))->publish($site, $item, 'test');

        self::assertSame('published', $result->status);
        self::assertSame('published', Article::whereKey($result->published_ref)->value('status'));
        self::assertSame('https://aivatan.com/articles/test-guide', $result->published_url);
        self::assertNotNull($result->published_at);
    }
}
