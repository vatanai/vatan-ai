<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramProductBotSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_open_product_bot_settings_page(): void
    {
        $admin = Admin::query()->create([
            'name' => 'رهبر تست بات محصول',
            'email' => 'telegram-product-settings@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.telegram.product-bot'));

        $response->assertOk()
            ->assertSee('بات ثبت محصول')
            ->assertSee('مدیران مجاز بات')
            ->assertSee('اتصال و رفتار بات');
    }
}
