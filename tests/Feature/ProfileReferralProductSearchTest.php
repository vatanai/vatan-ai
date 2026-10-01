<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileReferralProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_find_a_new_active_product_by_name(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = $this->createProduct('محصول تازه همکاری', 'new-referral-product', 'active');
        $this->createProduct('محصول تازه غیرفعال', 'inactive-referral-product', 'inactive');

        $response = $this->actingAs($user)->getJson(route('profile.referral-products.search', [
            'q' => 'تازه همکاری',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $target->id)
            ->assertJsonPath('items.0.name', 'محصول تازه همکاری');
    }

    public function test_referral_panel_exposes_the_live_product_search_endpoint(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('profile.panels', 'referral'));

        $response->assertOk();
        $this->assertStringContainsString(
            route('profile.referral-products.search'),
            (string) $response->json('html'),
        );
    }

    public function test_referral_search_matches_product_names_with_zero_width_spacing_and_arabic_letters(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = $this->createProduct("کت\u{200C}واک كسب و کار", 'referral-normalized-product', 'active');

        $response = $this->actingAs($user)->getJson(route('profile.referral-products.search', [
            'q' => 'کتواک کسب',
        ]));

        $response->assertOk()
            ->assertJsonPath('items.0.id', $target->id)
            ->assertJsonPath('items.0.name', "کت\u{200C}واک كسب و کار");
    }

    private function createProduct(string $name, string $slug, string $status): Product
    {
        return Product::query()->create([
            'name_fa' => $name,
            'name_en' => 'Referral Search Product',
            'slug' => $slug,
            'category' => 'TEST',
            'status' => $status,
            'thumbnail' => 'products/test.jpg',
            'primary_model' => 'test-model',
            'prompt_template' => 'یک تصویر آزمایشی بساز.',
        ]);
    }
}
