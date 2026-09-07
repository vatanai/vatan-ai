<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_listed_connected_and_editable_in_site_page_management(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست صفحات',
            'email' => 'site-pages@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('صفحه نخست سایت')
            ->assertSee(route('admin.pages.edit', ['sitePage' => 'landing']), false);

        $this->get(route('admin.pages.edit', ['sitePage' => 'landing']))
            ->assertOk()
            ->assertSee('صفحه نخست سایت');

        $this->assertDatabaseHas('site_pages', [
            'key' => 'landing',
            'status' => 'published',
        ]);
    }
}
