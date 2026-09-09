<?php

namespace Database\Seeders;

use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductCreatorRewardService;
use Illuminate\Database\Seeder;

/** داده‌ی آزمایشی محلی برای مشاهده‌ی کامل فازهای مالک محصول و پاداش. */
class PhaseThreeCreatorRewardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->orderBy('id')->firstOrFail();
        $source = Product::query()
            ->where('status', 'active')
            ->whereNotIn('slug', ['phase-3-reward-photo-test', 'phase-3-reward-video-test'])
            ->firstOrFail();

        $photo = $this->product($source, [
            'slug' => 'phase-3-reward-photo-test',
            'product_code' => '930301',
            'name_fa' => 'فاز سه — تست پاداش عکس',
            'name_en' => 'Phase 3 Reward Photo Test',
            'media_type' => 'photo',
            'output_type' => 'image',
        ], $owner);
        $video = $this->product($source, [
            'slug' => 'phase-3-reward-video-test',
            'product_code' => '930302',
            'name_fa' => 'فاز سه — تست پاداش ویدیو',
            'name_en' => 'Phase 3 Reward Video Test',
            'media_type' => 'video',
            'output_type' => 'video',
        ], $owner);

        $image = GeneratedImage::query()->firstOrCreate(
            ['user_id' => $owner->id, 'product_id' => $photo->id, 'image_path' => 'generated/gen_6a99a5532d888.png'],
            ['user_prompt' => 'خروجی آزمایشی فاز سه', 'cost' => 0, 'size' => 1],
        );
        app(ProductCreatorRewardService::class)->rewardForImage($image, [
            'total' => 1,
            'promotional' => 1,
            'paid' => 0,
            'ledger_key' => null,
        ]);

        $videoOutput = 'generated/videos/video_6a99a11ecd56b4.42065323.mp4';
        $videoGeneration = GeneratedVideo::query()->firstOrCreate(
            ['user_id' => $owner->id, 'product_id' => $video->id, 'video_path' => $videoOutput],
            [
                'status' => 'completed',
                'video_url' => asset('storage/' . $videoOutput),
                'credit_reservation' => ['total' => 4, 'promotional' => 0, 'paid' => 4, 'ledger_key' => null],
                'size' => 1,
            ],
        );
        app(ProductCreatorRewardService::class)->rewardForVideo($videoGeneration);

        $this->command?->info('Phase 3 creator reward demo products and outputs are ready.');
        $this->command?->info('Owner user id: ' . $owner->id);
    }

    private function product(Product $source, array $values, User $owner): Product
    {
        $product = Product::query()->firstOrNew(['slug' => $values['slug']]);
        if (! $product->exists) {
            $product = $source->replicate();
            $product->slug = $values['slug'];
        }

        $product->forceFill(array_merge($values, [
            'status' => 'active',
            'creator_reward_owner_id' => $owner->id,
            'creator_reward_enabled' => true,
            'creator_reward_settings' => [
                'image_free' => 1,
                'video_free' => 2,
                'image_paid' => 2,
                'video_paid' => 4,
            ],
            'card_label' => 'تست فاز سه',
        ]))->save();

        return $product->fresh();
    }
}
