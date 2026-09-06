<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('registration_gift_enabled')->default(true);
            $table->unsignedInteger('registration_gift_tokens')->default(50);
            $table->boolean('registration_sms_enabled')->default(true);
            $table->boolean('registration_gift_review_repeated_ip')->default(true);
            $table->boolean('registration_gift_review_repeated_device')->default(true);
            $table->unsignedSmallInteger('registration_gift_cooldown_days')->default(90);
            $table->boolean('referral_enabled')->default(false);
            $table->unsignedInteger('invitee_reward_tokens')->default(0);
            $table->unsignedInteger('inviter_reward_tokens')->default(5);
            $table->string('reward_trigger', 32)->default('first_purchase');
            $table->unsignedSmallInteger('attribution_window_days')->default(30);
            $table->unsignedInteger('daily_inviter_reward_limit')->nullable();
            $table->unsignedInteger('monthly_inviter_reward_limit')->nullable();
            $table->unsignedBigInteger('campaign_token_budget')->nullable();
            $table->timestamp('campaign_starts_at')->nullable();
            $table->timestamp('campaign_ends_at')->nullable();
            $table->boolean('review_repeated_ip')->default(true);
            $table->boolean('review_repeated_device')->default(true);
            $table->boolean('profile_enabled')->default(false);
            $table->string('profile_title')->default('همکاری در فروش وطن');
            $table->string('profile_subtitle')->default('لینکت را به اشتراک بگذار؛ دوستت هدیه می‌گیرد و تو پاداش.');
            $table->text('profile_description')->nullable();
            $table->text('share_message')->nullable();
            $table->timestamps();
        });

        DB::table('referral_settings')->insert([
            'profile_description' => 'لینک اختصاصی خودت را در شبکه‌های اجتماعی یا برای دوستانت بفرست. هر کاربر جدیدی که با لینک تو ثبت‌نام کند و اولین خرید موفقش را انجام دهد، یک دعوت موفق برای تو ثبت می‌شود و پاداش توکنی‌ات خودکار به حسابت می‌آید.',
            'share_message' => 'با لینک دعوت من به وطن بپیوند، ابزارهای هوش مصنوعی را تجربه کن و هدیه شروع بگیر: {referral_link}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('referral_setting_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->json('before_values');
            $table->json('after_values');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('referral_earnings');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->timestamp('referral_attributed_at')->nullable()->after('referred_by');
        });

        DB::table('users')->select('id')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'referral_code' => strtoupper(base_convert((string) $user->id, 10, 36).Str::random(7)),
                ]);
            }
        });

        Schema::create('referral_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code', 20);
            $table->uuid('visitor_token');
            $table->string('landing_url', 2048)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('device_hash', 64)->nullable();
            $table->timestamp('visited_at');
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['inviter_id', 'visited_at']);
            $table->index(['visitor_token', 'visited_at']);
        });

        Schema::create('referral_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->nullable()->constrained('referral_visits')->nullOnDelete();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invitee_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status', 24)->default('qualified');
            $table->string('risk_reason')->nullable();
            $table->char('signup_ip_hash', 64)->nullable();
            $table->char('signup_device_hash', 64)->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->index(['inviter_id', 'status', 'created_at']);
        });

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->nullable()->constrained('referral_conversions')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_type', 32);
            $table->unsignedInteger('amount');
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('status', 24)->default('pending');
            $table->string('event_key')->unique();
            $table->string('reason')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('device_hash', 64)->nullable();
            $table->json('settings_snapshot')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'reward_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('token_logs', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('action');
            $table->string('event_key')->nullable()->unique()->after('source');
            $table->json('metadata')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('token_logs', function (Blueprint $table) {
            $table->dropUnique(['event_key']);
            $table->dropColumn(['source', 'event_key', 'metadata']);
        });

        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referral_conversions');
        Schema::dropIfExists('referral_visits');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_attributed_at']);
        });

        Schema::dropIfExists('referral_setting_logs');
        Schema::dropIfExists('referral_settings');
    }
};
