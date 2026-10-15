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
        $job = Job::where('production_id', $p->id)->firstOrFail();
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
}
