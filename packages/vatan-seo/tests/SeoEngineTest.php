<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\KeywordRank;
use Vatan\Seo\Models\QueryMetric;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Models\TelegramAdmin;
use Vatan\Seo\Services\Scheduler;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class SeoEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeWorld(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();
            if (str_contains($url, 'openrouter.ai/api/v1/models')) {
                return Http::response(['data' => [
                    ['id' => 'google/gemini-2.5-flash-lite', 'pricing' => ['prompt' => '0.0000001', 'completion' => '0.0000004']],
                    ['id' => 'anthropic/claude-sonnet-5.5', 'pricing' => ['prompt' => '0.000002', 'completion' => '0.00001']],
                    ['id' => 'x-ai/grok-4.7', 'pricing' => ['prompt' => '0.000002', 'completion' => '0.000006']],
                ]]);
            }
            if (str_contains($url, 'chat/completions')) {
                $sys = $request['messages'][0]['content'] ?? '';
                $content = match (true) {
                    str_contains($sys, '۳ تا ۶ عبارت جستجوی واقعی') => json_encode(['products' => [['id' => 1, 'seeds' => [['keyword' => 'ساخت عكس محصول با هوش مصنوعی', 'intent' => 'transactional'], ['keyword' => 'عکاسی صنعتی آنلاین', 'intent' => 'commercial']]], ['id' => 2, 'seeds' => [['keyword' => 'ساخت ویدیو تبلیغاتی', 'intent' => 'transactional']]]]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'relevance') => json_encode(['items' => [['keyword' => 'ساخت عکس محصول با هوش مصنوعی', 'relevance' => 95, 'intent' => 'transactional', 'difficulty' => 40, 'reason' => 'دقیقاً محصول اصلی'], ['keyword' => 'عکاسی صنعتی آنلاین', 'relevance' => 80, 'intent' => 'commercial', 'difficulty' => 55, 'reason' => 'مرتبط'], ['keyword' => 'فوتبال', 'relevance' => 5, 'intent' => 'informational', 'difficulty' => 90, 'reason' => 'نامرتبط']]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'خوشه‌بندی') => json_encode(['clusters' => [['name' => 'عکس محصول', 'intent' => 'transactional', 'keywords' => ['ساخت عکس محصول با هوش مصنوعی', 'عکاسی صنعتی آنلاین']]]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'SEO researcher') => '- نتایج برتر فهرست ابزارها هستند\n- شکاف: مثال ایرانی ندارند',
                    str_contains($sys, 'سردبیر ارشد') => json_encode(['angle' => 'راهنمای عملی با مثال فروشگاه‌های ایرانی', 'title_options' => ['ساخت عکس محصول با هوش مصنوعی: راهنمای کامل ۱۴۰۵'], 'outline' => [['h2' => 'ساخت عکس محصول با هوش مصنوعی چیست', 'notes' => 'تعریف'], ['h2' => 'مراحل', 'notes' => '']], 'faqs' => ['هزینه چقدر است؟'], 'secondary_keywords' => ['عکس محصول'], 'internal_links' => [], 'word_target' => 50, 'cta' => ['title' => 'امتحان کن', 'button_label' => 'شروع', 'url' => '/']], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'نویسنده‌ی متخصص') => json_encode(['title' => 'ساخت عکس محصول با هوش مصنوعی: راهنمای کامل', 'slug' => 'ai-product-photo-guide', 'meta_title' => 'ساخت عکس محصول با هوش مصنوعی | راهنمای کامل وطن', 'meta_description' => str_repeat('توضیح کوتاه و جذاب درباره‌ی ساخت عکس محصول ', 4), 'blocks' => [
                        ['type' => 'lead', 'content' => 'ساخت عکس محصول با هوش مصنوعی یعنی تولید عکس حرفه‌ای بدون استودیو.'],
                        ['type' => 'heading', 'content' => 'چرا ساخت عکس محصول با هوش مصنوعی', 'level' => 2], ['type' => 'paragraph', 'content' => 'متن [صفحه اصلی](/) و [محصولات](/products) و [مقالات](/articles).'],
                        ['type' => 'heading', 'content' => 'مراحل', 'level' => 2], ['type' => 'list', 'items' => ['آپلود', 'انتخاب سبک']],
                        ['type' => 'heading', 'content' => 'نکات', 'level' => 2], ['type' => 'paragraph', 'content' => 'نکته‌ها'],
                        ['type' => 'heading', 'content' => 'جمع‌بندی', 'level' => 2], ['type' => 'cta', 'title' => 'شروع', 'button_label' => 'بساز', 'button_url' => '/'],
                        ['type' => 'script', 'content' => 'bad'],
                    ], 'faq' => [['q' => 'رایگان است؟', 'a' => 'بخشی بله.']]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'ویراستار ارشد') => json_encode(['quality' => 82, 'eeat' => 70, 'ai_likeness' => 30, 'issues' => ['مثال بیشتر'], 'fixes' => [], 'verdict' => 'publish'], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'Head of SEO') => json_encode(['focus' => 'تقویت کلمات صفحه‌ی دوم', 'summary' => 'وضعیت پایدار', 'actions' => [['title' => 'لینک داخلی به صفحه‌ی عکس محصول', 'why' => 'رتبه ۶', 'type' => 'onpage', 'impact' => 4, 'effort' => 2], ['title' => 'رفع عنوان‌های تکراری', 'why' => 'خزش', 'type' => 'technical', 'impact' => 4, 'effort' => 2]], 'pages_to_optimize' => ['https://aivatan.com/p1']], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'competitive analyst') => json_encode(['competitors' => [['domain' => 'rival.ir', 'appearances' => 4, 'note' => 'رقیب اصلی'], ['domain' => 'https://www.other.com/x', 'appearances' => 2, 'note' => '']]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'helpful AI search assistant') => json_encode(['answer' => 'سرویس‌های خوبی هست', 'recommended' => [['name' => 'وطن', 'domain' => 'aivatan.com'], ['name' => 'رقیب', 'domain' => 'rival.ir']]], JSON_UNESCAPED_UNICODE),
                    str_contains($sys, 'مدیر سئو') => 'رشد خوب بود.',
                    str_contains($sys, 'llms.txt') => "# وطن\n> پلتفرم ساخت عکس محصول\n\n## محصولات\n- [عکس](https://aivatan.com/p): ساخت عکس",
                    default => 'OK',
                };
                return Http::response(['model' => $request['model'], 'choices' => [['message' => ['content' => $content]]], 'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 500, 'cost' => 0.0123]]);
            }
            if (str_contains($url, 'suggestqueries.google.com')) {
                return Http::response('["x",["ساخت عکس محصول با هوش مصنوعی رایگان","ساخت عکس محصول آنلاین"]]');
            }
            if (str_contains($url, 'api.telegram.org')) {
                return Http::response(['ok' => true, 'result' => []]);
            }
            if (str_contains($url, 'robots.txt')) {
                return Http::response("User-agent: *\nDisallow: /admin\nSitemap: https://aivatan.com/sitemap.xml", 200, ['Content-Type' => 'text/plain']);
            }
            if (str_contains($url, 'sitemap.xml')) {
                return Http::response('<?xml version="1.0"?><urlset><url><loc>https://aivatan.com/</loc></url><url><loc>https://aivatan.com/articles/a</loc></url><url><loc>https://aivatan.com/orphan</loc></url></urlset>', 200, ['Content-Type' => 'application/xml']);
            }
            if (str_starts_with($url, 'http://aivatan.com')) {
                return Http::response('', 301, ['Location' => 'https://aivatan.com/']);
            }
            if (str_contains($url, 'seo-engine-check')) {
                return Http::response('nf', 404, ['Content-Type' => 'text/html']);
            }
            if (str_contains($url, 'aivatan.com')) {
                $html = '<html lang="fa" dir="rtl"><head><title>وطن — ساخت عکس محصول با هوش مصنوعی</title><meta name="description" content="توضیح"><meta name="viewport" content="width=device-width"><link rel="canonical" href="https://aivatan.com/"><script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"وطن"}</script></head><body><h1>ساخت عکس محصول</h1><a href="/articles/a">مقاله</a><a href="/about">درباره</a><img src="x.png">'.str_repeat('<p>کلمه کلمه کلمه</p>', 30).'</body></html>';
                return Http::response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Encoding' => 'br', 'Strict-Transport-Security' => 'max-age=1']);
            }
            return Http::response('', 404);
        });
    }

    protected function admin(): User
    {
        return User::factory()->create();
    }

    public function test_full_flow(): void
    {
        $this->fakeWorld();
        Product::create(['name_fa' => 'عکس محصول حرفه‌ای', 'description_fa' => 'ساخت عکس صنعتی', 'category' => 'عکس', 'meta_keywords' => ['عکس محصول'], 'slug' => 'p1', 'status' => 'active']);
        Product::create(['name_fa' => 'ویدیو تبلیغاتی', 'description_fa' => 'ویدیو', 'category' => 'ویدیو', 'slug' => 'p2', 'status' => 'active']);
        ArticleCategory::create(['name' => 'آموزش']);
        ArticleAuthor::create(['name' => 'تیم وطن']);

        // نصب
        $this->artisan('seo:install', ['--budget' => 20])->assertSuccessful();
        $site = Site::first();
        $this->assertSame('aivatan.com', $site->domain);
        $this->assertSame('starter', $site->profile()['key']);
        $this->assertGreaterThan(40, Task::count());
        $this->assertSame(14, Scenario::count());
        $this->artisan('seo:install')->assertSuccessful();
        $this->assertSame(14, Scenario::count(), 'install must be idempotent');

        $user = $this->admin();
        $this->actingAs($user, 'admin');

        // همه‌ی صفحات
        foreach (['seo.overview', 'seo.keywords.index', 'seo.plan', 'seo.technical', 'seo.content.index', 'seo.scenarios.index', 'seo.activity', 'seo.settings'] as $r) {
            $this->get(route($r))->assertOk()->assertSee('class="seo-tabs"', false)->assertDontSee('COMING-SOON');
        }
        $this->get(route('seo.keywords.index', ['tab' => 'candidates']))->assertOk();
        $this->get(route('seo.plan', ['view' => 'all', 'pillar' => 'infrastructure']))->assertOk();
        $this->get(route('seo.asset', 'seo.css'))->assertOk();

        // کشف کلمات
        $this->post(route('seo.keywords.discover'))->assertRedirect();
        $this->assertDatabaseHas('seo_keywords', ['normalized' => Fa::normalizeKeyword('ساخت عکس محصول با هوش مصنوعی'), 'status' => 'candidate']);
        $this->assertDatabaseMissing('seo_keywords', ['keyword' => 'فوتبال']);
        $kw = Keyword::where('normalized', Fa::normalizeKeyword('ساخت عکس محصول با هوش مصنوعی'))->first();
        $this->assertSame('ساخت عکس محصول با هوش مصنوعی', $kw->keyword, 'Arabic kaf normalized');
        $this->assertGreaterThan(0, \Vatan\Seo\Models\AiCall::sum('cost_usd'));

        // هدف‌گذاری گروهی
        $ids = Keyword::pluck('id')->take(3)->all();
        $this->post(route('seo.keywords.bulk'), ['ids' => $ids, 'action' => 'target'])->assertRedirect();
        $this->assertSame(3, Keyword::targets()->count());
        $this->assertSame(18, Task::where('kind', 'keyword')->count());
        $this->get(route('seo.keywords.show', $kw))->assertOk();
        $this->get(route('seo.keywords.index'))->assertOk()->assertSee($kw->keyword);

        // افزودن دستی
        $this->post(route('seo.keywords.store'), ['keywords' => "ويديو هوش مصنوعی\nعکاسی صنعتی آنلاین", 'as' => 'target'])->assertRedirect();
        $this->assertGreaterThanOrEqual(4, Keyword::targets()->count());

        // تولید محتوا
        $this->post(route('seo.content.generate'), ['keyword_id' => $kw->id, 'mode' => 'full'])->assertRedirect();
        $item = ContentItem::first();
        $this->assertSame('review', $item->status, (string) $item->reviewer_note);
        $this->assertGreaterThanOrEqual(70, $item->seo_score);
        $this->assertNotContains('script', array_column($item->blocks, 'type'));
        $this->get(route('seo.content.show', $item))->assertOk()->assertSee('href="/products"', false);

        // تلگرام: اتصال و تأیید
        $bot = app(\Vatan\Seo\Telegram\SeoBot::class);
        $code = $bot->linkCode($user->id);
        $this->postJson(route('seo.telegram.webhook'), ['message' => ['chat' => ['id' => 555], 'from' => ['first_name' => 'محسن'], 'text' => '/start '.$code]])->assertOk();
        $this->assertTrue(TelegramAdmin::where('chat_id', '555')->exists());
        $this->postJson(route('seo.telegram.webhook'), ['message' => ['chat' => ['id' => 555], 'text' => '/today']])->assertOk();
        $this->postJson(route('seo.telegram.webhook'), ['message' => ['chat' => ['id' => 999], 'text' => '/today']])->assertOk(); // چت غریبه
        $this->postJson(route('seo.telegram.webhook'), ['callback_query' => ['id' => 'cb1', 'data' => 'seo:approve:'.$item->id, 'message' => ['chat' => ['id' => 555]]]])->assertOk();
        $item->refresh();
        $this->assertSame('published', $item->status);
        $article = Article::first();
        $this->assertNotNull($article);
        $this->assertSame('ai-product-photo-guide', $article->slug);
        $this->assertSame('published', $article->status);
        $this->assertTrue(Task::where('keyword_id', $kw->id)->where('playbook_key', 'kw.article')->where('status', 'done')->exists());

        // سناریوها
        foreach (['health_check', 'crawl_audit', 'audit_runner', 'daily_plan', 'weekly_report', 'opportunities', 'cannibalization', 'content_decay', 'gsc_sync'] as $key) {
            $s = Scenario::where('key', $key)->first();
            $this->post(route('seo.scenarios.run', $s))->assertRedirect();
            $s->refresh();
            $this->assertNotNull($s->last_status, $key);
            $this->assertNotSame('failed', $s->last_status, $key.': '.$s->last_summary);
        }
        $this->assertTrue(Task::where('playbook_key', 'infra.robots')->where('status', 'done')->exists(), Task::where('playbook_key', 'infra.robots')->value('last_message'));
        $this->assertTrue(Task::where('playbook_key', 'infra.https')->where('status', 'done')->exists(), Task::where('playbook_key', 'infra.https')->value('last_message'));
        $this->assertTrue(Task::where('playbook_key', 'infra.schema_org')->where('status', 'done')->exists());
        $this->get(route('seo.technical'))->assertOk();

        // رتبه از داده‌ی سرچ کنسول
        foreach ([3, 2] as $i => $daysAgo) {
            QueryMetric::create(['site_id' => $site->id, 'date' => now()->subDays($daysAgo)->toDateString(), 'query' => 'ساخت عکس محصول با هوش مصنوعی', 'query_hash' => sha1($kw->normalized), 'page' => 'https://aivatan.com/p1', 'page_hash' => sha1('https://aivatan.com/p1'), 'clicks' => 3, 'impressions' => 100, 'ctr' => .03, 'position' => $i ? 6.0 : 9.0]);
        }
        $this->post(route('seo.scenarios.run', Scenario::where('key', 'rank_update')->first()))->assertRedirect();
        $kw->refresh();
        $this->assertEquals(6.0, $kw->current_position);
        $this->assertSame(1, KeywordRank::where('keyword_id', $kw->id)->count());
        $this->get(route('seo.overview'))->assertOk();

        // تنظیمات
        $this->post(route('seo.settings.site'), ['name' => 'وطن', 'base_url' => 'https://aivatan.com', 'monthly_budget_usd' => 30])->assertRedirect();
        $this->assertSame('pro', $site->fresh()->profile()['key']);
        $this->post(route('seo.settings.telegram.code'))->assertRedirect();
        $this->post(route('seo.technical.llms'))->assertRedirect();
        $this->get('/llms.txt')->assertOk()->assertSee('وطن');
        $this->post(route('seo.tasks.check-all'))->assertRedirect();
        $task = Task::where('automation', 'manual')->first();
        $this->patch(route('seo.tasks.update', $task), ['status' => 'done'])->assertRedirect();
        $this->assertSame('done', $task->fresh()->status);
        $sc = Scenario::first();
        $this->patch(route('seo.scenarios.update', $sc), ['toggle' => 1])->assertRedirect();
        $this->assertFalse($sc->fresh()->is_enabled);

        foreach (['seo.overview', 'seo.keywords.index', 'seo.plan', 'seo.technical', 'seo.content.index', 'seo.scenarios.index', 'seo.activity', 'seo.settings'] as $r) {
            $this->get(route($r))->assertOk();
        }
        // ── برنامه‌ی ۹۰ روزه ──
        $planner = app(\Vatan\Seo\Services\RoadmapPlanner::class);
        $this->artisan('seo:plan')->assertSuccessful();
        $this->assertGreaterThanOrEqual(13 * 5 - 5, Task::where('playbook_key', 'day.brief')->count(), 'daily tasks across 13 weeks');
        $this->assertGreaterThanOrEqual(12, Task::where('playbook_key', 'wk.tech')->count());
        $this->assertSame(1, Task::where('playbook_key', 'ms.day90')->count());
        $this->assertNotNull(Task::where('playbook_key', 'infra.robots')->value('period_key'));
        $last = Task::where('kind', 'daily')->max('due_on');
        $this->assertTrue(\Illuminate\Support\Carbon::parse($last)->gte(now()->addWeeks(11)), 'horizon ~13 weeks: '.$last);
        // موج کلمات: حداکثر سهمیه در هفته
        $waves = Keyword::targets()->get()->map(fn ($k) => data_get($k->meta, 'wave_week'))->countBy();
        $this->assertLessThanOrEqual($planner->articlesPerWeek($site->fresh()), $waves->max());
        $this->assertGreaterThanOrEqual(4, $waves->keys()->min());
        $this->artisan('seo:plan')->assertSuccessful();
        $count = Task::count();
        $this->artisan('seo:plan')->assertSuccessful();
        $this->assertSame($count, Task::count(), 'planner idempotent');
        $this->get(route('seo.plan'))->assertOk()->assertSee('seo-roadmap', false)->assertSee('راه‌اندازی و خط پایه');
        $this->post(route('seo.plan.rebuild'))->assertRedirect();

        // روزانه: مرور نمای کلی تیک می‌زند
        $this->get(route('seo.overview'))->assertOk()->assertSee('seo-weekcard', false);
        $this->assertTrue(Task::where('playbook_key', 'day.brief')->where('due_on', now('Asia/Tehran')->toDateString())->where('status', 'done')->exists() || ! in_array(now('Asia/Tehran')->dayOfWeek, [6, 0, 1, 2, 3], true));

        // استراتژیست، رقبا، پایش AI، لینک داخلی
        $s = Scenario::where('key', 'strategist_review')->first();
        $this->post(route('seo.scenarios.run', $s))->assertRedirect();
        $this->assertSame('success', $s->fresh()->last_status, (string) $s->fresh()->last_summary);
        $this->assertTrue(Task::where('playbook_key', 'like', 'ai.action.%')->exists());
        $this->post(route('seo.technical.competitors'))->assertRedirect();
        $this->assertContains('rival.ir', array_column((array) $site->fresh()->setting('competitors_detail.items'), 'domain'));
        $this->assertContains('other.com', array_column((array) $site->fresh()->setting('competitors_detail.items'), 'domain'));
        $this->post(route('seo.technical.geo'))->assertRedirect();
        $this->assertGreaterThan(0, data_get($site->fresh()->setting('geo_probe.last'), 'mentioned'));
        $this->get(route('seo.technical'))->assertOk()->assertSee('rival.ir');
        $this->get(route('seo.keywords.show', $kw))->assertOk()->assertSee('پیشنهاد لینک داخلی');

        // دستورهای تلگرام
        foreach (['/ask چرا کلیک کم شد؟', '/plan', '/help', '/budget'] as $cmd) {
            $this->postJson(route('seo.telegram.webhook'), ['message' => ['chat' => ['id' => 555], 'text' => $cmd]])->assertOk();
        }
        $this->assertTrue(\Vatan\Seo\Models\Run::where('action', 'telegram_ask')->exists());
        $this->get(route('seo.settings'))->assertOk()->assertSee('فعال‌سازی هوش مصنوعی');

        // tick
        $this->artisan('seo:tick')->assertSuccessful();
    }

    public function test_budget_cap_blocks_paid_calls(): void
    {
        $this->fakeWorld();
        $this->artisan('seo:install', ['--budget' => 20]);
        $site = Site::first();
        $site->update(['monthly_budget_usd' => 5]);
        \Vatan\Seo\Models\AiCall::create(['site_id' => $site->id, 'role' => 'fast', 'purpose' => 'x', 'model' => 'm', 'cost_usd' => 4.9]);
        $this->expectException(\Vatan\Seo\Ai\BudgetExceededException::class);
        app(\Vatan\Seo\Ai\Ai::class)->text($site, 'writer', 'test', 'x', str_repeat('y', 20000), ['max_tokens' => 8000]);
    }

    public function test_helpers(): void
    {
        $this->assertSame([1405, 7, 18], Fa::toJalali(2026, 10, 10));
        $this->assertSame([1404, 1, 1], Fa::toJalali(2025, 3, 21));
        $this->assertSame(Fa::normalizeKeyword('ساخت‌عكس  محصول'), Fa::normalizeKeyword('ساخت عکس محصول'));
        $this->assertSame('free', Budget::tierKeyFor(0));
        $this->assertSame('starter', Budget::tierKeyFor(20));
        $this->assertSame('pro', Budget::tierKeyFor(30));
        $this->assertSame('growth', Budget::tierKeyFor(80));

        $this->artisan('seo:install', ['--budget' => 20]);
        $s = Scenario::where('key', 'weekly_report')->first(); // پنجشنبه ۱۸:۰۰
        $next = app(Scheduler::class)->nextRun($s)->setTimezone('Asia/Tehran');
        $this->assertSame(4, $next->dayOfWeek);
        $this->assertSame('18:00', $next->format('H:i'));
        $this->assertTrue($next->isFuture());
        $m = Scenario::where('key', 'content_decay')->first();
        $this->assertSame(1, app(Scheduler::class)->nextRun($m)->setTimezone('Asia/Tehran')->day === 5 ? 1 : 0);
    }
}
