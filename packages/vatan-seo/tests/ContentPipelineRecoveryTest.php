<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;
use Vatan\Seo\Agents\ContentWriter;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\AutoPublisher;
use Vatan\Seo\Scenarios\ContentPipeline;

class ContentPipelineRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['seo_content_items', 'seo_tasks', 'seo_scenarios', 'seo_keywords', 'seo_sites'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('seo_sites', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('domain'); $table->string('base_url');
            $table->decimal('monthly_budget_usd', 8, 2)->default(20); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('seo_keywords', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->string('keyword'); $table->string('normalized');
            $table->string('status'); $table->string('source')->nullable(); $table->timestamps();
        });
        Schema::create('seo_scenarios', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->string('key'); $table->string('handler');
            $table->json('config')->nullable(); $table->timestamps();
        });
        Schema::create('seo_tasks', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->unsignedBigInteger('keyword_id')->nullable();
            $table->string('playbook_key'); $table->string('status'); $table->date('due_on')->nullable(); $table->timestamps();
        });
        Schema::create('seo_content_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->unsignedBigInteger('keyword_id')->nullable();
            $table->string('type'); $table->string('status'); $table->string('title')->nullable(); $table->json('brief')->nullable();
            $table->json('quality')->nullable(); $table->text('reviewer_note')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['seo_content_items', 'seo_tasks', 'seo_scenarios', 'seo_keywords', 'seo_sites'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function siteAndScenario(int $briefLimit = 16): array
    {
        $site = Site::create([
            'name' => 'Vatan', 'domain' => 'aivatan.com', 'base_url' => 'https://aivatan.com',
            'monthly_budget_usd' => 20,
            'settings' => ['overrides' => ['limits' => ['articles_per_month' => 8, 'briefs_per_month' => $briefLimit]]],
        ]);
        $scenario = Scenario::create(['site_id' => $site->id, 'key' => 'content_pipeline', 'handler' => 'content_pipeline']);

        return [$site, $scenario];
    }

    #[RunInSeparateProcess]
    public function test_stale_failed_article_gets_one_automatic_retry_and_is_published_after_quality_gate(): void
    {
        [$site, $scenario] = $this->siteAndScenario();
        $keyword = $site->keywords()->create([
            'keyword' => 'ساخت عکس محصول با هوش مصنوعی',
            'status' => 'target',
            'source' => 'manual',
        ]);
        $item = ContentItem::create([
            'site_id' => $site->id,
            'keyword_id' => $keyword->id,
            'type' => 'article',
            'status' => 'failed',
            'title' => 'راهنمای عکس محصول',
            'brief' => ['keyword' => $keyword->keyword],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $ai = \Mockery::mock(Ai::class);
        $ai->shouldReceive('available')->once()->with($site, 'strategist')->andReturnTrue();

        $writer = \Mockery::mock(ContentWriter::class);
        $writer->shouldReceive('draft')->once()->withArgs(fn (Site $draftSite, ContentItem $draftItem) => $draftSite->is($site) && $draftItem->is($item))
            ->andReturnUsing(function (Site $draftSite, ContentItem $draftItem) {
                $draftItem->update(['status' => 'review', 'quality' => ['gate' => ['passed' => true]]]);
                return $draftItem->fresh();
            });
        $writer->shouldNotReceive('brief');

        $publisher = \Mockery::mock(AutoPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(fn (Site $publishSite, ContentItem $publishItem, string $source) =>
            $publishSite->is($site) && $publishItem->is($item) && $source === 'seo-auto'
        )->andReturnUsing(fn (Site $publishSite, ContentItem $publishItem) => $publishItem);

        $result = (new ContentPipeline($writer, $ai, $publisher))->handle($site, $scenario);

        self::assertSame('success', $result[0]);
        self::assertSame(1, $result[2]['published']);
        self::assertSame(1, $item->fresh()->brief['pipeline_retry_count']);
        self::assertSame('review', $item->fresh()->status);
    }

    #[RunInSeparateProcess]
    public function test_stale_article_is_not_retried_more_than_once(): void
    {
        [$site, $scenario] = $this->siteAndScenario();
        $keyword = $site->keywords()->create([
            'keyword' => 'ساخت عکس محصول با هوش مصنوعی',
            'status' => 'target',
            'source' => 'manual',
        ]);
        $item = ContentItem::create([
            'site_id' => $site->id,
            'keyword_id' => $keyword->id,
            'type' => 'article',
            'status' => 'failed',
            'title' => 'راهنمای عکس محصول',
            'brief' => ['pipeline_retry_count' => 1],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $ai = \Mockery::mock(Ai::class);
        $ai->shouldReceive('available')->once()->with($site, 'strategist')->andReturnTrue();
        $writer = \Mockery::mock(ContentWriter::class);
        $writer->shouldNotReceive('brief');
        $writer->shouldNotReceive('draft');
        $publisher = \Mockery::mock(AutoPublisher::class);
        $publisher->shouldNotReceive('publish');

        $result = (new ContentPipeline($writer, $ai, $publisher))->handle($site, $scenario);

        self::assertSame('skipped', $result[0]);
        self::assertSame('failed', $item->fresh()->status);
        self::assertSame(1, $item->fresh()->brief['pipeline_retry_count']);
    }

    #[RunInSeparateProcess]
    public function test_existing_stale_brief_is_recovered_even_when_new_brief_quota_is_exhausted(): void
    {
        [$site, $scenario] = $this->siteAndScenario(0);
        $keyword = $site->keywords()->create([
            'keyword' => 'ساخت عکس محصول با هوش مصنوعی',
            'status' => 'target',
            'source' => 'manual',
        ]);
        $item = ContentItem::create([
            'site_id' => $site->id,
            'keyword_id' => $keyword->id,
            'type' => 'article',
            'status' => 'failed',
            'title' => 'راهنمای عکس محصول',
            'brief' => ['keyword' => $keyword->keyword],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $ai = \Mockery::mock(Ai::class);
        $ai->shouldReceive('available')->once()->with($site, 'strategist')->andReturnTrue();
        $writer = \Mockery::mock(ContentWriter::class);
        $writer->shouldReceive('draft')->once()->andReturnUsing(function (Site $draftSite, ContentItem $draftItem) {
            $draftItem->update(['status' => 'failed', 'quality' => ['gate' => ['passed' => false]]]);
            return $draftItem->fresh();
        });
        $writer->shouldNotReceive('brief');
        $publisher = \Mockery::mock(AutoPublisher::class);
        $publisher->shouldNotReceive('publish');

        $result = (new ContentPipeline($writer, $ai, $publisher))->handle($site, $scenario);

        self::assertSame('failed', $result[0]);
        self::assertSame(1, $item->fresh()->brief['pipeline_retry_count']);
    }
}
