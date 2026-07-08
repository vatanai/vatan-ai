<?php

namespace Database\Seeders;

use App\Models\GeneratedImage;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Models\UserUpload;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ══════════════════════════════════════════════════════════════════
 * Seeder کاربر تستِ کامل — فقط برای تست بصری ظاهر سایت روی دیتابیس لوکال
 * ──────────────────────────────────────────────────────────────────
 * یک کاربر واقعی می‌سازه که تقریباً همه‌ی بخش‌های پنل رو پوشش می‌ده:
 * عکس پروفایل واقعی، ۱۰ توکن، ۳۰۰ تومان درآمد رفرال، یک پلن فعال،
 * چند تصویر «ساخته‌شده» (متصل به یک محصول واقعی) و چند عکس شخصی آپلودی.
 *
 * نحوه‌ی اجرا (روی سیستم لوکال خودتان، جایی که به دیتابیس MySQL دسترسی دارید):
 *   php artisan db:seed --class=Database\\Seeders\\TestUserSeeder
 *
 * اجرای دوباره‌ی این Seeder امن است (کاربر تکراری ساخته نمی‌شود).
 * ══════════════════════════════════════════════════════════════════
 */
class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // ۱) اطمینان از وجود حداقل یک پلن فعال (برای نمایش واقعی «پلن» در پروفایل)
        $plan = Plan::where('slug', 'pro-test')->first();
        if (!$plan) {
            $plan = Plan::create([
                'name'  => 'Pro',
                'slug'  => 'pro-test',
                'price' => 490000,
                'tokens' => 500,
            ]);
        }

        // ۲) اطمینان از وجود حداقل یک محصول واقعی (برای اتصال تصاویر ساخته‌شده)
        $product = Product::first();
        if (!$product) {
            $product = Product::create([
                'name_fa' => 'عکس پروفایل هوش مصنوعی',
                'name_en' => 'AI Profile Photo',
                'slug' => 'ai-profile-photo-test',
                'description_fa' => 'محصول نمونه برای تست کاربر کامل در حالت لوکال.',
                'category' => 'PEOPLE',
                'subcategory' => 'Professional',
                'status' => 'active',
                'media_type' => 'photo',
                'pricing_model' => 'per_credit',
                'credit_cost' => 12,
                'thumbnail' => 'products/thumbnails/sample-profile-ai.jpg',
                'primary_model' => 'flux-pro-1.1',
                'prompt_template' => 'یک عکس پروفایل حرفه‌ای از {{input_photo}} بساز.',
                'pipeline_type' => 'image_generation',
                'output_type' => 'image',
                'display_mode' => 'card',
            ]);
        }

        // ۳) ساخت/آپدیت خود کاربر تستی
        $user = User::updateOrCreate(
            ['email' => 'test.user@vatanai.local'],
            [
                'name' => 'علی',
                'last_name' => 'رضایی',
                'phone' => '09121234567',
                'password' => Hash::make('password'),
                'status' => 'active',
                'tokens' => 10,
                'tokens_purchased' => 50,
                'tokens_used' => 40,
                'referral_earnings' => 300,
                'plan_id' => $plan->id,
            ]
        );

        // ۴) کپی عکس پروفایل واقعی (در صورت نبود آواتار قبلی) از دارایی‌های موجود پروژه
        if (!$user->avatar) {
            $avatarSource = public_path('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg');
            if (File::exists($avatarSource)) {
                $avatarPath = 'avatars/test-user-' . Str::random(8) . '.jpeg';
                Storage::disk('public')->put($avatarPath, File::get($avatarSource));
                $user->avatar = $avatarPath;
                $user->save();
            }
        }

        // ۵) چند «تصویر ساخته‌شده» واقعی، متصل به محصول بالا (برای گرید و مودال پیش‌نمایش)
        if ($user->generatedImages()->count() === 0) {
            $samples = [
                'products/thumbnails/sample-profile-ai.jpg',
                'products/thumbnails/sample-bg-remove.jpg',
                'products/thumbnails/sample-wedding-style.jpg',
            ];

            foreach ($samples as $i => $path) {
                $fullPath = storage_path('app/public/' . $path);
                GeneratedImage::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'user_prompt' => 'تصویر نمونه برای تست کاربر کامل',
                    'cost' => 12,
                    'size' => File::exists($fullPath) ? File::size($fullPath) : 0,
                    'created_at' => now()->subDays(2 - $i)->subHours($i * 3),
                    'updated_at' => now()->subDays(2 - $i)->subHours($i * 3),
                ]);
            }
        }

        // ۶) چند عکس شخصی آپلودی (برای تب «فایل‌های تو»)
        if ($user->uploadedImages()->count() === 0) {
            $personalSources = [
                'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
                'assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp',
            ];

            foreach ($personalSources as $i => $rel) {
                $sourceFull = public_path($rel);
                if (!File::exists($sourceFull)) {
                    continue;
                }
                $ext = pathinfo($sourceFull, PATHINFO_EXTENSION);
                $destRelative = 'uploads/personal/test-user-' . Str::random(8) . '.' . $ext;
                Storage::disk('public')->put($destRelative, File::get($sourceFull));

                UserUpload::create([
                    'user_id' => $user->id,
                    'file_path' => $destRelative,
                    'size' => File::size($sourceFull),
                    'mime_type' => File::mimeType($sourceFull) ?: null,
                    'created_at' => now()->subDays(1 + $i),
                    'updated_at' => now()->subDays(1 + $i),
                ]);
            }
        }

        $this->command?->info('✔ کاربر تستی کامل ساخته/به‌روزرسانی شد.');
        $this->command?->info('   ایمیل: ' . $user->email);
        $this->command?->info('   شماره موبایل: ' . $user->phone);
        $this->command?->info('   رمز عبور: password');
    }
}
