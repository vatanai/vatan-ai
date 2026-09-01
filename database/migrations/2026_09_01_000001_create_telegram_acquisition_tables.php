<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'telegram_gift_claimed_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('telegram_gift_claimed_at')->nullable()->after('registered_at');
            });
        }

        if (Schema::hasTable('referral_settings')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('referral_settings', 'telegram_registration_gift_enabled')) {
                    $table->boolean('telegram_registration_gift_enabled')->default(true)->after('registration_gift_enabled');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_registration_gift_tokens')) {
                    $table->unsignedInteger('telegram_registration_gift_tokens')->default(40)->after('registration_gift_tokens');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_channel_username')) {
                    $table->string('telegram_channel_username', 120)->nullable()->after('telegram_registration_gift_tokens');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_channel_id')) {
                    $table->string('telegram_channel_id', 80)->nullable()->after('telegram_channel_username');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_channel_invite_url')) {
                    $table->string('telegram_channel_invite_url', 2048)->nullable()->after('telegram_channel_id');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_bot_username')) {
                    $table->string('telegram_bot_username', 120)->nullable()->after('telegram_channel_invite_url');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_mini_app_url')) {
                    $table->string('telegram_mini_app_url', 2048)->nullable()->after('telegram_bot_username');
                }
                if (! Schema::hasColumn('referral_settings', 'telegram_membership_required')) {
                    $table->boolean('telegram_membership_required')->default(true)->after('telegram_mini_app_url');
                }
            });
        }

        if (! Schema::hasTable('telegram_users')) {
            Schema::create('telegram_users', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('telegram_id')->unique();
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                $table->string('username', 255)->nullable();
                $table->string('first_name', 255)->nullable();
                $table->string('last_name', 255)->nullable();
                $table->string('language_code', 12)->nullable();
                $table->text('phone')->nullable();
                $table->timestamp('phone_verified_at')->nullable();
                $table->boolean('is_premium')->default(false);
                $table->boolean('is_blocked')->default(false);
                $table->timestamp('blocked_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->timestamp('registration_completed_at')->nullable();
                $table->string('registration_state', 40)->default('idle');
                $table->json('registration_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['last_active_at', 'is_blocked']);
                $table->index(['phone_verified_at']);
            });
        }

        if (! Schema::hasTable('telegram_events')) {
            Schema::create('telegram_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('update_id')->nullable()->unique();
                $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();
                $table->string('event_type', 80);
                $table->string('chat_id', 100)->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['event_type', 'occurred_at']);
                $table->index(['chat_id', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('telegram_product_clicks')) {
            Schema::create('telegram_product_clicks', function (Blueprint $table): void {
                $table->id();
                $table->uuid('launch_token')->unique();
                $table->foreignId('telegram_user_id')->constrained('telegram_users')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('product_key', 255)->nullable();
                $table->string('source', 120)->nullable();
                $table->string('source_channel', 120)->nullable();
                $table->string('source_campaign', 160)->nullable();
                $table->string('channel_id', 100)->nullable();
                $table->string('channel_username', 120)->nullable();
                $table->string('message_id', 100)->nullable();
                $table->string('start_payload', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('clicked_at');
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['telegram_user_id', 'clicked_at']);
                $table->index(['product_id', 'clicked_at']);
                $table->index(['source', 'clicked_at']);
            });
        }

        if (! Schema::hasTable('telegram_bot_contents')) {
            Schema::create('telegram_bot_contents', function (Blueprint $table): void {
                $table->id();
                $table->string('content_key', 100)->unique();
                $table->string('title', 255)->nullable();
                $table->text('body')->nullable();
                $table->string('media_type', 30)->nullable();
                $table->text('media_file_id')->nullable();
                $table->json('buttons')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });

            $now = now();
            $contents = [
                ['content_key' => 'welcome', 'title' => 'خوش‌آمدگویی', 'body' => 'سلام {first_name} عزیز 🌿\nبه وطن خوش اومدی. برای ساخت محصول، اول عضویتت در کانال وطن رو بررسی می‌کنیم.', 'buttons' => json_encode([['text' => 'ثبت‌نام برای شروع', 'callback_data' => 'register']], JSON_UNESCAPED_UNICODE), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'membership_required', 'title' => 'عضویت کانال', 'body' => 'برای ساخت محصول، ابتدا عضو کانال وطن شو و بعد روی «بررسی عضویت» بزن.', 'buttons' => json_encode([['text' => 'عضویت در کانال وطن', 'action' => 'join_channel'], ['text' => 'بررسی عضویت', 'callback_data' => 'membership_check']], JSON_UNESCAPED_UNICODE), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'registration_name', 'title' => 'نام کاربر', 'body' => 'عالیه 🌿\nبرای ساخت محصول، نام و نام خانوادگی‌ات رو در یک پیام بفرست.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'registration_phone', 'title' => 'شماره موبایل', 'body' => 'حالا شماره موبایلت رو با دکمه‌ی زیر بفرست. اگر دکمه برایت کار نکرد، شماره را دستی وارد کن.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'registration_otp', 'title' => 'کد تأیید', 'body' => 'کد تأیید پیامک‌شده را بفرست تا حساب وطن برایت آماده شود.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'registration_done', 'title' => 'ثبت‌نام کامل شد', 'body' => 'حسابت آماده شد 🎉\n{gift_tokens} اعتبار هدیه برای شروع به حسابت اضافه شد.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'returning_user', 'title' => 'کاربر قبلی', 'body' => 'خوش برگشتی {first_name} عزیز 🌿\nحسابت آماده است؛ از اینجا ادامه بده.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'product_selected', 'title' => 'محصول انتخاب‌شده', 'body' => "این همون محصولیه که انتخاب کردی 🌿\n\n{product_name}\n{product_description}", 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'product_missing', 'title' => 'محصول پیدا نشد', 'body' => 'محصول انتخاب‌شده پیدا نشد. می‌تونی از بین همه‌ی قالب‌ها انتخاب کنی.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'build_ready', 'title' => 'ساخت محصول', 'body' => 'صفحه‌ی ساخت محصول آماده است 🌿', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'all_products_ready', 'title' => 'همه‌ی قالب‌ها', 'body' => 'همه‌ی قالب‌های وطن آماده‌اند 🌿', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['content_key' => 'generic_error', 'title' => 'خطای عمومی', 'body' => 'یک لحظه مشکلی پیش آمد. دوباره تلاش کن یا با پشتیبانی در تماس باش.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('telegram_bot_contents')->insert(array_map(
                static fn (array $content): array => array_replace([
                    'media_type' => null,
                    'media_file_id' => null,
                    'buttons' => null,
                    'metadata' => null,
                ], $content),
                $contents,
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_product_clicks');
        Schema::dropIfExists('telegram_events');
        Schema::dropIfExists('telegram_bot_contents');
        Schema::dropIfExists('telegram_users');

        if (Schema::hasTable('referral_settings')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                foreach ([
                    'telegram_registration_gift_enabled', 'telegram_registration_gift_tokens',
                    'telegram_channel_username', 'telegram_channel_id', 'telegram_channel_invite_url', 'telegram_bot_username',
                    'telegram_mini_app_url', 'telegram_membership_required',
                ] as $column) {
                    if (Schema::hasColumn('referral_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'telegram_gift_claimed_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('telegram_gift_claimed_at'));
        }
    }
};
