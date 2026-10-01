<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InstagramPostSetting;
use App\Models\InstagramPostKeyword;
use App\Models\InstagramPostResponse;
use App\Models\InstagramPostProduct;
use Illuminate\Http\JsonResponse;

class InstagramTestController extends Controller
{
    /**
     * Setup Instagram test data
     * Access: /admin/instagram-test-setup
     */
    public function setupTestData(): JsonResponse
    {
        try {
            // Get first user
            $user = User::first();
            if (!$user) {
                return response()->json(['error' => 'No users found'], 400);
            }

            // Create Instagram post setting with all required fields
            $post = InstagramPostSetting::create([
                'user_id' => $user->id,
                'title' => 'تست نیلوفر - Reel',
                'instagram_post_id' => 'Dd4VEftOMUV',
                'instagram_caption' => 'تست اتوماسیون کامنت و دایرکت 🎯',
                'status' => 'testing',
                'require_follow' => true,
                'min_followers' => 0,
                'non_follower_response' => json_encode([
                    'text' => '👋 سلام! برای دسترسی به پرامپت نیلوفر آبی اول صفحه رو فالو کن 🎁',
                ]),
                'follower_response' => json_encode([
                    'text' => '✅ خوش‌آمدی! پرامپت نیلوفر آبی رو براتت فرستادم 👈 قیمت: ۱۲ توکن ⚡',
                ]),
                'comment_reply_delay' => 3,
                'dm_product_delay' => 5,
                'dm_form_delay' => 2,
                'repeat_policy' => 'once_per_user',
                'log_all_interactions' => true,
                'notify_slack' => false,
                'daily_excel_export' => false,
                'metadata' => json_encode([
                    'product_name' => 'پرامپت نیلوفر آبی',
                    'test_marker' => 'real_test_niloofar',
                ]),
            ]);

            // Add keyword
            InstagramPostKeyword::create([
                'post_setting_id' => $post->id,
                'keyword' => 'نیلوفر',
                'match_type' => 'exact',
                'is_active' => true,
            ]);

            // Non-follower DM message
            InstagramPostResponse::create([
                'post_setting_id' => $post->id,
                'scenario' => 'non_follower',
                'target' => 'dm_text',
                'message' => '👋 سلام! برای دسترسی به پرامپت نیلوفر آبی اول صفحه رو فالو کن بعد منتظر بمون ۳ ثانیه دیگه پیام رو براتت میفرسته 🎁',
            ]);

            // Follower DM message
            InstagramPostResponse::create([
                'post_setting_id' => $post->id,
                'scenario' => 'follower',
                'target' => 'dm_text',
                'message' => '✅ خوش‌آمدی! پرامپت نیلوفر آبی رو براتت فرستادم لطفا دایرکت بررسی کن 👈 قیمت: ۱۲ توکن ⚡',
            ]);

            // Follower comment reply
            InstagramPostResponse::create([
                'post_setting_id' => $post->id,
                'scenario' => 'follower',
                'target' => 'comment',
                'message' => '✅ اطلاعات کامل در DM فرستادم برو چک کن 👈',
            ]);

            // Product
            InstagramPostProduct::create([
                'post_setting_id' => $post->id,
                'product_name' => 'پرامپت نیلوفر آبی',
                'description' => 'پرامپت خاص برای تولید تصاویر با موضوع نیلوفر آبی',
                'price' => 12,
                'product_link' => 'https://aivatan.com/app/product/468466-lily-in-the-mirror',
                'is_active' => true,
                'inventory' => 999,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instagram test data created successfully',
                'data' => [
                    'post_id' => $post->id,
                    'instagram_post_id' => 'Dd4VEftOMUV',
                    'keyword' => 'نیلوفر',
                    'product' => 'پرامپت نیلوفر آبی',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }
}
