<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'first_image_followup_due_at')) {
                    $table->timestamp('first_image_followup_due_at')->nullable()->index()->after('last_login_at');
                }
                if (! Schema::hasColumn('users', 'first_image_followup_sent_at')) {
                    $table->timestamp('first_image_followup_sent_at')->nullable()->after('first_image_followup_due_at');
                }
            });

            // کاربران قدیمی نباید با انتشار این قابلیت، ناگهان پیامย้อนหลัง بگیرند.
            if (Schema::hasTable('generated_images')) {
                DB::table('users')
                    ->whereNull('first_image_followup_due_at')
                    ->whereIn('id', DB::table('generated_images')->whereNotNull('user_id')->distinct()->pluck('user_id'))
                    ->update([
                        'first_image_followup_due_at' => now(),
                        'first_image_followup_sent_at' => now(),
                    ]);
            }
        }

        if (! Schema::hasTable('sms_templates')) {
            return;
        }

        $now = now();
        $followupBody = "{name} جان اولین عکست رو ساختی ✨\nحالا بریم سراغ بعدی‌ها؟ اعتبارت رو شارژ کن\nو با وطن هرچقدر دوست داری تصویر بساز 🤍";
        $registrationBody = '{name} جان از اینکه به وطن خودت رسیدی خوشحالیم... برای شروع، حسابت رو با چند توکن هدیه شارژ کردیم؛ حالا وقتشه ایده‌هات رو بسازی. وطن استودیو';

        // قالبی که قبلاً اشتباهاً روی ثبت‌نام قرار گرفته بود، به رویداد واقعی خودش منتقل می‌شود.
        DB::table('sms_templates')->where('event_key', 'first_image_followup')->update(['is_default' => false]);
        $followup = DB::table('sms_templates')
            ->where('provider_template_id', '529239')
            ->where(function ($query): void {
                $query->where('body', 'like', '%اولین عکست رو ساختی%')
                    ->orWhere('name', 'like', '%اولین عکس%');
            })
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if ($followup) {
            DB::table('sms_templates')->where('id', $followup->id)->update([
                'event_key' => 'first_image_followup',
                'name' => 'پیام بعد از اینکه کاربر اولین عکس خودش رو ساخت',
                'body' => $followupBody,
                'provider_method' => 'shared',
                'provider_template_id' => '529239',
                'provider_variables' => json_encode(['name'], JSON_UNESCAPED_UNICODE),
                'provider_approval_status' => 'approved',
                'provider_note' => 'تأییدشده در پنل ملی‌پیامک',
                'provider_checked_at' => $now,
                'is_active' => true,
                'is_default' => true,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('sms_templates')->insert([
                'event_key' => 'first_image_followup',
                'name' => 'پیام بعد از اینکه کاربر اولین عکس خودش رو ساخت',
                'body' => $followupBody,
                'provider_method' => 'shared',
                'provider_template_id' => '529239',
                'provider_variables' => json_encode(['name'], JSON_UNESCAPED_UNICODE),
                'provider_approval_status' => 'approved',
                'provider_note' => 'تأییدشده در پنل ملی‌پیامک',
                'provider_checked_at' => $now,
                'is_active' => true,
                'is_default' => true,
                'sent_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // پیام خوش‌آمدگویی ثبت‌نام به قالب و شناسهٔ اصلی خودش برمی‌گردد.
        DB::table('sms_templates')->where('event_key', 'registration_success')->update(['is_default' => false]);
        $registration = DB::table('sms_templates')
            ->where('event_key', 'registration_success')
            ->where('provider_template_id', '506692')
            ->orderByDesc('id')
            ->first();

        $registrationData = [
            'event_key' => 'registration_success',
            'name' => 'خوش‌آمدگویی اولین ورود',
            'body' => $registrationBody,
            'provider_method' => 'shared',
            'provider_template_id' => '506692',
            'provider_variables' => json_encode(['name'], JSON_UNESCAPED_UNICODE),
            'provider_approval_status' => 'approved',
            'provider_note' => 'تأییدشده در پنل ملی‌پیامک',
            'provider_checked_at' => $now,
            'is_active' => true,
            'is_default' => true,
            'updated_at' => $now,
        ];

        if ($registration) {
            DB::table('sms_templates')->where('id', $registration->id)->update($registrationData);
        } else {
            DB::table('sms_templates')->insert($registrationData + ['sent_count' => 0, 'created_at' => $now]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'first_image_followup_sent_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('first_image_followup_sent_at');
            });
        }
        if (Schema::hasColumn('users', 'first_image_followup_due_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('first_image_followup_due_at');
            });
        }
        DB::table('sms_templates')->where('event_key', 'first_image_followup')->delete();
    }
};
