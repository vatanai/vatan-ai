<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\InstagramPostSetting;
use App\Models\InstagramPostKeyword;
use App\Models\InstagramPostResponse;
use App\Models\InstagramPostProduct;

class InstagramTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first admin user
        $user = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$user) {
            $this->command->warn('No users found in database. Skipping seeder.');
            return;
        }

        // Create Instagram post setting
        $post = InstagramPostSetting::create([
            'user_id' => $user->id,
            'instagram_post_id' => 'Dd4VEftOMUV',
            'instagram_post_url' => 'https://www.instagram.com/reel/Dd4VEftOMUV/',
            'post_type' => 'reel',
            'status' => 'testing',
            'caption' => 'تست اتوماسیون کامنت و دایرکت',
            'metadata' => [
                'product_name' => 'پرامپت نیلوفر آبی',
                'test_marker' => 'real_test_' . now()->timestamp,
            ],
        ]);

        // Add keyword
        InstagramPostKeyword::create([
            'instagram_post_setting_id' => $post->id,
            'keyword' => 'نیلوفر',
            'match_type' => 'exact',
            'priority' => 1,
            'is_active' => true,
        ]);

        // Add response for non-followers
        InstagramPostResponse::create([
            'instagram_post_setting_id' => $post->id,
            'scenario' => 'non_follower',
            'target' => 'dm_text',
            'message_body' => '👋 سلام! 

برای دسترسی به پرامپت نیلوفر آبی
اول صفحه رو فالو کن بعد منتظر بمون
۳ ثانیه دیگه پیام رو براتت میفرسته 🎁',
            'order' => 1,
            'is_active' => true,
        ]);

        // Add response for followers (main message)
        InstagramPostResponse::create([
            'instagram_post_setting_id' => $post->id,
            'scenario' => 'follower',
            'target' => 'dm_text',
            'message_body' => '✅ خوش‌آمدی! 

پرامپت نیلوفر آبی رو براتت فرستادم
لطفا دایرکت بررسی کن 👈

قیمت: ۱۲ توکن ⚡',
            'order' => 1,
            'is_active' => true,
        ]);

        // Add comment reply
        InstagramPostResponse::create([
            'instagram_post_setting_id' => $post->id,
            'scenario' => 'follower',
            'target' => 'comment',
            'message_body' => '✅ اطلاعات کامل در DM فرستادم
برو چک کن 👈',
            'order' => 1,
            'is_active' => true,
        ]);

        // Add product
        InstagramPostProduct::create([
            'instagram_post_setting_id' => $post->id,
            'product_name' => 'پرامپت نیلوفر آبی',
            'product_url' => 'https://aivatan.com/app/product/468466-lily-in-the-mirror',
            'product_price' => 12,
            'product_currency' => 'tokens',
            'product_description' => 'پرامپت خاص برای تولید تصاویر با موضوع نیلوفر آبی',
            'metadata' => [
                'prompt_type' => 'image_generation',
            ],
            'stock_quantity' => 999,
            'order' => 1,
            'is_active' => true,
        ]);

        $this->command->info('Instagram test data created successfully!');
        $this->command->info("Post ID: {$post->id}");
        $this->command->info("Instagram Post: Dd4VEftOMUV");
        $this->command->info("Keyword: نیلوفر");
    }
}
