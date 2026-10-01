<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\Product;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductManager;
use App\Services\TelegramProductDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * انتخاب «قاب‌های نمایش در هوم و اکسپلور» از داخل بات ثبت محصول تلگرام
 * + رفع بن‌بست هشدار «عنوان مشابه» و ثبت خطای جاب پردازش.
 */
class TelegramProductExploreTilesTest extends TestCase
{
    use RefreshDatabase;

    private int $updateId = 5000;

    private function manager(array $permissions = []): TelegramProductManager
    {
        return TelegramProductManager::query()->create([
            'telegram_id' => random_int(700000, 799999),
            'name' => 'مدیر تست',
            'is_active' => true,
            'permissions' => $permissions,
        ]);
    }

    private function draft(TelegramProductManager $manager, string $state = 'review', array $ai = []): TelegramProductDraft
    {
        return TelegramProductDraft::query()->create([
            'id' => (string) Str::uuid(),
            'telegram_product_manager_id' => $manager->id,
            'telegram_id' => $manager->telegram_id,
            'chat_id' => (string) $manager->telegram_id,
            'state' => $state,
            'description' => 'یک کیف چرم دستی مشکی با بند قابل تنظیم',
            'image_paths' => ['products/telegram/test-cover.jpg'],
            'ai_result' => array_merge([
                'name_fa' => 'کیف چرم دستی ' . Str::random(6),
                'name_en' => 'Handmade Leather Bag ' . Str::random(6),
                'description_fa' => 'کیف چرم دستی با دوخت حرفه‌ای',
                'description_en' => 'Handmade leather bag',
                'category' => 'عمومی',
                'category_name' => 'عمومی',
                'tags' => ['کیف', 'چرم'],
                'product_prompt' => 'Studio photo of {prompt}',
            ], $ai),
        ]);
    }

    private function click(TelegramProductManager $manager, string $callback, int $messageId = 900): array
    {
        return app(TelegramProductDraftService::class)->handle([
            'update_id' => ++$this->updateId,
            'event' => 'callback',
            'callback_data' => $callback,
            'telegram_id' => $manager->telegram_id,
            'chat_id' => (string) $manager->telegram_id,
            'message_id' => $messageId,
        ]);
    }

    public function test_publish_asks_for_explore_tiles_instead_of_enabling_all_of_them(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);

        $response = $this->click($manager, 'product:publish');

        $this->assertSame('awaiting_explore_tiles', $response['status']);
        $this->assertSame('explore_tiles', $response['ui']);
        $this->assertSame(['1x1'], $response['explore_tiles']);
        $this->assertFalse($response['edit_message']);
        $this->assertCount(6, $response['buttons']);
        $this->assertSame(
            ['product:tile:1x1', 'product:tile:2x2', 'product:tile:1x2', 'product:tile:2x1', 'product:tiles_done', 'product:cancel'],
            array_column($response['buttons'], 'callback_data'),
        );
        $this->assertStringStartsWith('✅ ', $response['buttons'][0]['text']);
        $this->assertStringStartsWith('⬜ ', $response['buttons'][1]['text']);
        $this->assertSame('awaiting_explore_tiles', $draft->fresh()->state);
        $this->assertSame(0, Product::query()->where('slug', 'like', 'handmade-leather-bag%')->count());
    }

    public function test_tiles_can_be_toggled_in_place_and_at_least_one_always_stays_on(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);
        $this->click($manager, 'product:publish');

        $on = $this->click($manager, 'product:tile:2x2', 901);
        $this->assertSame(['1x1', '2x2'], $on['explore_tiles']);
        $this->assertTrue($on['edit_message']);
        $this->assertSame('901', $on['edit_message_id']);
        $this->assertStringStartsWith('✅ ', $on['buttons'][1]['text']);

        $off = $this->click($manager, 'product:tile:1x1', 901);
        $this->assertSame(['2x2'], $off['explore_tiles']);
        $this->assertStringStartsWith('⬜ ', $off['buttons'][0]['text']);

        $last = $this->click($manager, 'product:tile:2x2', 901);
        $this->assertSame(['2x2'], $last['explore_tiles']);
        $this->assertStringContainsString('حداقل یک قاب باید روشن بماند.', $last['text']);

        $unknown = $this->click($manager, 'product:tile:9x9', 901);
        $this->assertSame(['2x2'], $unknown['explore_tiles']);
        $this->assertSame(['2x2'], $draft->fresh()->ai_result['explore_tiles']);
    }

    public function test_confirming_the_tiles_publishes_the_product_with_only_the_chosen_tiles(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);
        $this->click($manager, 'product:publish');
        $this->click($manager, 'product:tile:1x2', 901);
        $this->click($manager, 'product:tile:1x1', 901);

        $final = $this->click($manager, 'product:tiles_done', 901);

        $this->assertTrue($final['final_product']);
        $this->assertSame('active', $final['status']);
        $product = Product::query()->findOrFail($final['product_id']);
        $this->assertSame(['1x2'], $product->explore_tiles);
        $this->assertSame('active', $product->status);
        $this->assertSame('published', $draft->fresh()->state);
    }

    public function test_default_is_a_single_square_tile_like_the_dashboard(): void
    {
        $manager = $this->manager();
        $this->draft($manager);
        $this->click($manager, 'product:publish');

        $final = $this->click($manager, 'product:tiles_done');

        $this->assertSame(['1x1'], Product::query()->findOrFail($final['product_id'])->explore_tiles);
    }

    public function test_saving_a_draft_skips_the_tile_step_and_uses_the_default(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);

        $saved = $this->click($manager, 'product:save_draft');

        $this->assertSame('draft', $saved['status']);
        $this->assertSame(['1x1'], Product::query()->findOrFail($saved['product_id'])->explore_tiles);
        $this->assertSame('draft_saved', $draft->fresh()->state);
    }

    public function test_pressing_publish_again_during_the_tile_step_only_shows_the_keyboard_again(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);
        $this->click($manager, 'product:publish');
        $this->click($manager, 'product:tile:2x1', 901);

        $again = $this->click($manager, 'product:publish');

        $this->assertSame('awaiting_explore_tiles', $again['status']);
        $this->assertSame(['1x1', '2x1'], $again['explore_tiles']);
        $this->assertFalse($again['edit_message']);
        $this->assertSame('awaiting_explore_tiles', $draft->fresh()->state);
    }

    public function test_typed_text_during_the_tile_step_shows_the_keyboard_again(): void
    {
        $manager = $this->manager();
        $this->draft($manager);
        $this->click($manager, 'product:publish');

        $response = app(TelegramProductDraftService::class)->handle([
            'update_id' => ++$this->updateId,
            'event' => 'message',
            'text' => 'سلام',
            'telegram_id' => $manager->telegram_id,
            'chat_id' => (string) $manager->telegram_id,
        ]);

        $this->assertSame('awaiting_explore_tiles', $response['status']);
        $this->assertSame('explore_tiles', $response['ui']);
    }

    public function test_stale_tile_buttons_are_ignored_after_the_product_is_published(): void
    {
        $manager = $this->manager();
        $this->draft($manager);
        $this->click($manager, 'product:publish');
        $this->click($manager, 'product:tiles_done');

        // دیگر هیچ درفت فعالی نیست؛ دکمه‌های قدیمی نباید محصول تازه‌ای بسازند.
        $stale = $this->click($manager, 'product:tiles_done');
        $this->assertStringNotContainsString('ثبت و منتشر شد', $stale['text']);
        $this->assertSame(1, Product::query()->where('slug', 'like', 'handmade-leather-bag%')->count());
    }

    public function test_a_manager_without_publish_permission_cannot_touch_the_tiles(): void
    {
        $manager = $this->manager(['publish_product' => false]);
        $this->draft($manager, 'awaiting_explore_tiles', ['explore_tiles' => ['1x1']]);

        $this->assertSame('forbidden', $this->click($manager, 'product:tile:2x2')['status']);
        $this->assertSame('forbidden', $this->click($manager, 'product:tiles_done')['status']);
        $this->assertSame('forbidden', $this->click($manager, 'product:publish')['status']);
    }

    public function test_duplicate_title_is_only_a_warning_the_manager_can_go_past(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager, 'duplicate', ['duplicate_product_id' => 1]);

        // دکمه‌ی «ثبت و تایید نهایی» داخل n8n روی حالت duplicate هم باید کار کند.
        $tiles = $this->click($manager, 'product:publish');
        $this->assertSame('awaiting_explore_tiles', $tiles['status']);
        $this->assertTrue($draft->fresh()->ai_result['duplicate_acknowledged']);

        $final = $this->click($manager, 'product:tiles_done');
        $this->assertTrue($final['final_product']);
        $this->assertSame('published', $draft->fresh()->state);
    }

    public function test_confirm_on_a_duplicate_moves_forward_instead_of_repeating_the_warning(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager, 'duplicate');

        $confirmed = $this->click($manager, 'product:confirm');

        $this->assertSame('awaiting_save_choice', $confirmed['status']);
        $this->assertSame('awaiting_save_choice', $draft->fresh()->state);

        $tiles = $this->click($manager, 'product:publish');
        $this->assertSame('awaiting_explore_tiles', $tiles['status']);
    }

    public function test_a_real_duplicate_title_is_flagged_once_and_then_allowed(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager, 'review', ['name_fa' => 'کیف تکراری تست', 'name_en' => 'Duplicate Bag Test']);
        Product::query()->create([
            'name_fa' => 'کیف تکراری تست', 'name_en' => 'Duplicate Bag Test', 'slug' => 'duplicate-bag-test',
            'product_code' => Product::generateUniqueProductCode(), 'status' => 'active', 'category' => 'عمومی',
            'primary_model' => 'x', 'ai_provider' => 'openrouter', 'thumbnail' => 't.jpg', 'media_type' => 'photo', 'prompt_template' => 'x',
        ]);

        $first = $this->click($manager, 'product:publish');
        $this->assertSame('duplicate', $first['status']);
        $this->assertStringContainsString('محصولی با عنوان مشابه', $first['text']);
        $this->assertSame('duplicate', $draft->fresh()->state);

        $second = $this->click($manager, 'product:publish');
        $this->assertSame('awaiting_explore_tiles', $second['status']);
        $final = $this->click($manager, 'product:tiles_done');
        $this->assertTrue($final['final_product']);
        $this->assertSame(2, Product::query()->where('name_fa', 'کیف تکراری تست')->count());
    }

    public function test_a_failed_processing_job_marks_the_draft_failed_and_the_status_endpoint_returns_a_message(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager, 'processing');

        (new ProcessTelegramProductDraftJob($draft->id))->failed(new \RuntimeException('provider timeout'));

        $draft = $draft->fresh();
        $this->assertSame('failed', $draft->state);
        $this->assertSame('provider timeout', $draft->error_message);

        $status = app(TelegramProductDraftService::class)->status($draft);
        $this->assertSame('failed', $status['status']);
        $this->assertStringContainsString('آماده‌سازی اطلاعات با خطا روبه‌رو شد', $status['text']);
        $this->assertNotEmpty($status['chat_id']);
    }

    public function test_editing_the_settings_is_still_possible_from_the_tile_step(): void
    {
        $manager = $this->manager();
        $draft = $this->draft($manager);
        $this->click($manager, 'product:publish');

        $edit = $this->click($manager, 'product:edit:metadata');

        $this->assertSame('awaiting_edit', $edit['status']);
        $this->assertSame('awaiting_edit', $draft->fresh()->state);
    }
}
