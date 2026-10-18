<?php

namespace Vatan\Content\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Vatan\Content\Http\Controllers\AgentController;
use Vatan\Content\Models\Calendar;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Job;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\PromptVersion;
use Vatan\Content\Models\Style;
use Vatan\Content\Support\Seed;

/**
 * تست کارخانه‌ی محتوا. اجرا: DB_DATABASE=<sqlite> vendor/bin/phpunit packages/vatan-content/tests
 */
class ContentFactoryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin()
    {
        $model = config('auth.providers.'.config('auth.guards.admin.provider').'.model');
        $admin = $model::first();
        if (! $admin) {
            $admin = new $model;
            $admin->forceFill(['name' => 'تست', 'email' => 'cf-test@example.com', 'password' => bcrypt('x')])->save();
        }

        return $admin;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['content-factory.runner.inline' => false]);
        Seed::run(1);
        Job::whereIn('status', ['queued', 'running'])->update(['status' => 'cancelled']);
    }

    public function test_seed_is_complete_and_idempotent(): void
    {
        $this->assertSame(9, Style::count());
        $this->assertGreaterThanOrEqual(15, PromptVersion::where('status', 'active')->count());
        $this->assertSame(37, CalendarItem::count());
        $this->assertSame(0, CalendarItem::whereNull('scenes')->count());
        Seed::run(1);
        $this->assertSame(37, CalendarItem::count());
        $this->assertSame('2026-11-21', CalendarItem::max('publish_date') instanceof \DateTimeInterface ? CalendarItem::max('publish_date')->format('Y-m-d') : substr((string) CalendarItem::max('publish_date'), 0, 10));
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->admin(), 'admin');
        foreach (['', '/calendar', '/calendar?view=table', '/productions', '/styles', '/music', '/prompts', '/prompts?layer=caption', '/insights', '/settings', '/jobs/feed'] as $p) {
            $this->get('/'.config('content-factory.host.route_prefix').$p)->assertOk();
        }
        $this->get('/'.config('content-factory.host.route_prefix').'/calendar')->assertSee('cf-help', false)->assertSee('ساخت', false);
    }

    public function test_build_and_agent_roundtrip(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $item = CalendarItem::where('code', 'D01')->first();
        $this->post(route('cf.calendar.build', $item))->assertRedirect();
        $p = Production::where('calendar_item_id', $item->id)->firstOrFail();
        $this->assertNotEmpty($p->caption);
        $job = Job::where('production_id', $p->id)->latest('id')->firstOrFail();
        $this->assertSame('VatanReel', $job->payload['outputs'][0]['composition']);
        $this->assertCount(5, $job->payload['outputs']); // ریلز + کاور + ۳ استوری

        $h = ['X-CF-Token' => AgentController::token()];
        $this->postJson('/api/content-factory/agent/hello', ['host' => 'test-mac'], $h)->assertOk();
        $this->postJson('/api/content-factory/agent/next', [], ['X-CF-Token' => 'bad'])->assertStatus(401);
        $next = $this->postJson('/api/content-factory/agent/next', ['host' => 'test-mac'], $h)->assertOk()->json('job');
        $this->assertSame($job->id, $next['id']);
        $up = $this->post('/api/content-factory/agent/upload', ['dir' => 'productions/t', 'file' => UploadedFile::fake()->create('reel.mp4', 10, 'video/mp4')], $h)->assertOk()->json('path');
        $this->postJson("/api/content-factory/agent/productions/{$p->id}/version", ['version' => 1, 'files' => ['video' => $up, 'stories' => []], 'duration' => 13], $h)->assertOk();
        $this->postJson("/api/content-factory/agent/jobs/{$job->id}/complete", ['status' => 'done'], $h)->assertOk();
        $this->assertSame('review', $p->fresh()->status);
        $this->assertSame('done', $job->fresh()->status);
        $this->get(route('cf.productions.show', $p))->assertOk();
        $this->post(route('cf.productions.approve', $p))->assertRedirect();
        $this->assertSame('approved', $p->fresh()->status);
    }

    public function test_style_crud_and_prompt_versioning(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post(route('cf.styles.store'), ['name_fa' => 'سبک تست', 'name_en' => 'Test Style', 'template' => 'kinetic', 'goals_text' => 'جذب، فروش'])->assertRedirect();
        $s = Style::where('slug', 'test-style')->firstOrFail();
        $this->assertSame(['جذب', 'فروش'], $s->goals);
        $this->patch(route('cf.styles.update', $s), ['name_fa' => 'سبک تست ۲', 'template' => 'editorial'])->assertRedirect();
        $this->assertSame('editorial', $s->fresh()->template);
        $this->post(route('cf.prompts.store'), ['layer' => 'caption', 'body' => 'تست {{hook}}'])->assertRedirect();
        $draft = PromptVersion::where('layer', 'caption')->orderByDesc('version')->first();
        $this->assertSame('draft', $draft->status);
        $this->post(route('cf.prompts.activate', $draft))->assertRedirect();
        $this->assertSame(1, PromptVersion::where('layer', 'caption')->where('status', 'active')->count());
    }

    public function test_calendar_item_edit_with_product_and_json(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $item = CalendarItem::where('code', 'D02')->first();
        $this->patch(route('cf.calendar.item', $item), ['product' => 'brand:vatan', 'keywords_text' => 'الف، ب', 'scenes_json' => '[{"kind":"cover","title":"x"}]'])->assertRedirect();
        $f = $item->fresh();
        $this->assertSame(['الف', 'ب'], $f->keywords);
        $this->assertSame('x', $f->scenes[0]['title']);
        $this->patch(route('cf.calendar.item', $item), ['scenes_json' => '{bad'])->assertSessionHas('error');
    }

    public function test_item_page_full_cycle(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $item = CalendarItem::where('code', 'D02')->firstOrFail();
        $this->get(route('cf.item', $item))->assertOk()->assertSee('پرامپت جامع', false)->assertSee('cf-item.js', false);
        $this->get(route('cf.item.status', $item))->assertOk()->assertJsonStructure(['jobs', 'busy']);
        $scenes = [['type' => 'text', 'dur' => 60, 'text' => 'هوک تازه', 'hl' => ['تازه'], 'bg' => 'dark'], ['type' => 'cta', 'dur' => 90, 'text' => 'ثبت‌نام', 'keyword' => 'عکس']];
        $prompt = ['system' => 'نقش {{code}}', 'sections' => [['title' => 'مأموریت', 'body' => 'هوک: {{hook}}', 'on' => true], ['title' => 'خاموش', 'body' => 'نباید بیاید', 'on' => false]]];
        $this->post(route('cf.item.save', $item), ['hook' => 'هوک ویرایش‌شده', 'scenes_json' => json_encode($scenes), 'story_specs_json' => json_encode([['kind' => 'poll', 'title' => 'سؤال؟']]),
            'plan_json' => json_encode([['step' => 'رندر', 'who' => 'Remotion', 'where' => 'مک', 'prompt' => 'master', 'cost' => '۰']]), 'prompt_json' => json_encode($prompt),
            'cover_hl' => 'الف، ب', 'music_start' => 2.5, 'hook_family' => 'سؤال درد'])->assertSessionHas('success');
        $item->refresh();
        $this->assertSame('هوک ویرایش‌شده', $item->hook);
        $this->assertCount(2, $item->scenes);
        $this->assertSame(5, $item->duration_sec);
        $this->assertSame(['سؤال؟'], $item->stories);
        $this->assertSame(['الف', 'ب'], $item->assets['cover_hl']);
        $this->assertSame('سؤال درد', $item->ai_plan['hook_family']);
        $c = app(\Vatan\Content\Services\MasterPrompt::class)->compile($item);
        $this->assertStringContainsString('هوک: هوک ویرایش‌شده', $c['user']);
        $this->assertStringContainsString('نقش D02', $c['system']);
        $this->assertStringNotContainsString('نباید بیاید', $c['user']);
        // بدون هوش سرور → کار Claude Code مک
        config(['content-factory.ai.enabled' => false]);
        $this->post(route('cf.item.generate', $item), ['where' => 'mac'])->assertSessionHas('success');
        $job = Job::where('type', 'ai_write')->latest('id')->firstOrFail();
        $this->assertSame($item->id, $job->payload['item_id']);
        $h = ['X-CF-Token' => AgentController::token()];
        $out = json_encode(['scenes' => [['type' => 'text', 'dur' => 45, 'text' => 'نوشته‌ی کلاد']], 'story_specs' => [['kind' => 'cta', 'title' => 'بیا']], 'caption' => 'کپشن کلاد', 'hook' => 'هوک کلاد']);
        $this->postJson('/api/content-factory/agent/jobs/'.$job->id.'/complete', ['status' => 'done', 'result' => ['text' => "```json\n".$out."\n```"]], $h)->assertOk();
        $item->refresh();
        $this->assertSame('نوشته‌ی کلاد', $item->scenes[0]['text']);
        $this->assertSame('done', $job->fresh()->status);
        $this->post(route('cf.item.undo', $item))->assertSessionHas('success');
        $this->assertSame('هوک تازه', $item->fresh()->scenes[0]['text']);
        // سفارش ساخت با پرامپت ذخیره‌شده
        $this->post(route('cf.item.build', $item))->assertRedirect(route('cf.item', $item));
        $render = Job::where('type', 'render')->latest('id')->firstOrFail();
        $this->assertStringContainsString('هوک: ', $render->payload['prompt']);
        $this->post(route('cf.item.build', $item))->assertSessionHas('warning');
        // آمار دستی و امتیاز
        $this->post(route('cf.item.metrics', $item), ['window' => '24h', 'views' => 1200, 'reach' => 900, 'likes' => 80, 'saves' => 12])->assertSessionHas('success');
        $snap = $item->fresh()->production->snapshots()->where('window', '24h')->first();
        $this->assertEquals(1200, $snap->metrics['views']);
        $this->get(route('cf.item', $item))->assertOk()->assertSee('بازدید/پخش', false);
        $this->get(route('cf.productions.feed'))->assertOk()->assertJsonStructure(['rows' => [['id', 'code', 'status', 'url']], 'agent', 'at']);
    }

    public function test_styles_cards_and_prompt_layers(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->get(route('cf.styles'))->assertOk()->assertSee('cf-style-grid', false);
        $this->post(route('cf.styles.store'), ['name_fa' => 'سبک آزمون', 'name_en' => 'Test Look', 'template' => 'kinetic', 'palette_text' => '#112233, #abc, red'])->assertRedirect();
        $s = Style::where('name_fa', 'سبک آزمون')->firstOrFail();
        $this->assertSame(['#112233', '#abc'], $s->palette);
        $this->get(route('cf.styles.show', $s))->assertOk()->assertSee('شناسنامه', false);
        $this->post(route('cf.styles.duplicate', $s))->assertRedirect();
        $this->delete(route('cf.styles.destroy', $s))->assertRedirect(route('cf.styles'));
        $this->assertNull(Style::find($s->id));
        $this->post(route('cf.prompts.layer'), ['key' => 'hashtags', 'title' => 'هشتگ‌ها'])->assertRedirect();
        $this->post(route('cf.prompts.store'), ['layer' => 'hashtags', 'body' => 'متن', 'activate' => 1])->assertRedirect();
        $this->assertSame('active', PromptVersion::where('layer', 'hashtags')->value('status'));
        $this->delete(route('cf.prompts.layer.delete', 'hashtags'))->assertRedirect();
        $this->assertSame(0, PromptVersion::where('layer', 'hashtags')->count());
        $this->post(route('cf.prompts.store'), ['layer' => 'master', 'body' => "x\n--- USER ---\n## الف\nب"])->assertRedirect();
        $draft = PromptVersion::where('layer', 'master')->where('status', 'draft')->firstOrFail();
        $this->delete(route('cf.prompts.destroy', $draft))->assertRedirect();
        $this->assertNull(PromptVersion::find($draft->id));
    }
}
