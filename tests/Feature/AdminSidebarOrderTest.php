<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_menu_order_is_stable_in_the_rendered_source_without_runtime_moving(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست منو',
            'email' => 'sidebar-order@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin');

        $html = view('admin.partials.sidebar')->render();
        $labels = [
            '<div class="nav-label">مرکز فرماندهی</div>',
            '<div class="nav-label">اعتبار سرویس‌ها</div>',
            '<div class="nav-label">مدیریت محصولات</div>',
            '<div class="nav-label">استودیو تولید</div>',
            '<div class="nav-label">فروش و مارکتینگ</div>',
            '<div class="nav-label">حسابداری وطن</div>',
            '<div class="nav-label">مدل‌های هوشمند</div>',
            '<div class="nav-label">مدیریت وبسایت</div>',
            '<div class="nav-label">کاربران</div>',
            '<div class="nav-label">تنظیمات</div>',
            '<div class="nav-label">آپدیت در آینده</div>',
        ];

        $positions = array_map(fn (string $label) => strpos($html, $label), $labels);

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertSame(1, substr_count($html, 'id="sales-growth-submenu-new"'));
        $this->assertSame(1, substr_count($html, 'id="settings-telegram-submenu-new"'));
        $this->assertSame(1, substr_count($html, 'بات ثبت محصول'));
        $this->assertSame(1, substr_count($html, 'بات فروش تلگرام'));
        $this->assertStringNotContainsString('id="studio-telegram-submenu-new"', $html);
        $this->assertStringNotContainsString('class="sb-section"', $html);
        $this->assertStringNotContainsString('insertBefore(growthSection', $html);
    }
}
