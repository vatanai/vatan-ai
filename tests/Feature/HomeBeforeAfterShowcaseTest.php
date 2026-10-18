<?php

namespace Tests\Feature;

use App\Services\HomeBuilder\HomeSectionPreviewService;
use App\Models\Admin;
use App\Models\HomeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBeforeAfterShowcaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_preview_renders_all_bundled_before_after_examples_without_product_records(): void
    {
        $item = app(HomeSectionPreviewService::class)->make([
            'type' => 'vt_before_after',
            'layout' => 'default',
            'settings' => [],
        ]);
        $html = view('app.home-builder.sections.vt_before_after', $item)->render();

        $this->assertSame(3, $item['items']->count());
        $this->assertSame(3, substr_count($html, 'class="vt-ba-card"'));
        $this->assertStringContainsString('کرم ضدآفتاب مدیسان', $html);
        $this->assertStringContainsString('ماسک موی سان‌وی', $html);
        $this->assertStringNotContainsString('class="vt-ba-meta"', $html);
        $this->assertStringNotContainsString('بساز', $html);

        foreach ([
            'medisun-before.webp', 'medisun-after.webp',
            'fanoma-before.webp', 'fanoma-after.webp',
            'sunway-before.webp', 'sunway-after.webp',
        ] as $image) {
            $this->assertFileExists(public_path('assets/before-after/' . $image));
            $this->assertStringContainsString($image, $html);
        }

        $this->assertFileExists(public_path('assets/before-after/bracelet-before.webp'));
        $this->assertFileExists(public_path('assets/before-after/bracelet-after.webp'));
    }

    public function test_dashboard_can_save_the_bundled_showcase_as_the_homepage_source(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر ویترین',
            'email' => 'before-after-home@test.local',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $section = HomeSection::query()->create([
            'page_key' => 'app_home',
            'type' => 'vt_before_after',
            'layout' => 'default',
            'title_fa' => 'از عکس ساده تا عکس فروش',
            'settings' => ['source' => 'with_before', 'limit' => 3, 'cta_label' => 'بساز'],
            'status' => 'published',
            'position' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->putJson(route('admin.home-builder.update', $section), [
                'title_fa' => $section->title_fa,
                'layout' => 'default',
                'settings' => ['source' => 'showcase', 'limit' => 3, 'cta_label' => 'بساز'],
                'responsive' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('section.settings.source', 'showcase')
            ->assertJsonPath('section.settings.limit', 3);

        $savedSection = $section->fresh();
        $this->assertSame('showcase', $savedSection->setting('source'));
        $this->assertCount(3, app(\App\Services\HomeBuilder\HomeSectionRenderService::class)
            ->prepare($savedSection)['items']);
    }

    public function test_deployment_migration_switches_the_existing_homepage_section_to_the_bundled_images(): void
    {
        $section = HomeSection::query()->create([
            'page_key' => 'app_home',
            'type' => 'vt_before_after',
            'layout' => 'default',
            'title_fa' => 'از عکس ساده تا عکس فروش',
            'settings' => ['source' => 'with_before', 'limit' => 3, 'cta_label' => 'بساز'],
            'status' => 'published',
            'position' => 1,
        ]);

        $migration = require database_path('migrations/2026_10_17_000001_use_curated_before_after_showcase.php');
        $migration->up();

        $this->assertSame('showcase', $section->fresh()->setting('source'));
        $this->assertSame(3, $section->fresh()->setting('limit'));
        $this->assertFalse($section->fresh()->setting('show_meta'));
        $renderData = app(\App\Services\HomeBuilder\HomeSectionRenderService::class)->prepare($section->fresh());
        $this->assertCount(3, $renderData['items']);
        $html = view('app.home-builder.sections.vt_before_after', $renderData)->render();
        $this->assertSame(3, substr_count($html, 'class="vt-ba-card"'));
        $this->assertStringNotContainsString('class="vt-ba-meta"', $html);
    }
}
