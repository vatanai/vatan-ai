<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * اصلاح محتوای سناریوی «کلاژ سه‌تایی» (پس از تست واقعی ۱۳ مهر):
 * پیام قبل از کارت شعاری بود، تیتر/توضیح کارت ضعیف بود و متن دکمه کلی بود.
 * فقط فیلدهایی عوض می‌شوند که هنوز دقیقاً همان متن قبلی را دارند (اگر مدیر خودش ویرایش کرده باشد، دست نمی‌خورد).
 * یک نسخه‌ی تازه هم ثبت می‌شود تا از صفحه‌ی جزئیات قابل بازگشت باشد. idempotent است.
 */
return new class extends Migration
{
    private const CHANGES = [
        'card.intro_text' => ['کلاژ سه‌تایی رو با ما بساز!', '{name} جان، اینم لینکی که خواستی 👇'],
        'card.title' => ['ساخت کلاژ سه‌تایی', 'کلاژ سه‌تایی با عکس خودت'],
        'card.subtitle' => ['عکس‌ها رو به هنر تبدیل کن', 'یه عکس بده، سه قاب ادیتوریال بگیر؛ بدون آتلیه و پرامپت'],
        'card.buttons.0.label' => ['شروع کن', 'با عکس خودم بساز'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('instagram_post_campaigns') || !Schema::hasTable('instagram_post_campaign_versions')) {
            return;
        }

        DB::table('instagram_post_campaigns')->orderBy('id')->get()->each(function ($row): void {
            $settings = json_decode((string) $row->settings, true);
            if (!is_array($settings)) {
                return;
            }
            $changed = false;
            foreach (self::CHANGES as $path => [$old, $new]) {
                if (trim((string) data_get($settings, $path)) === $old) {
                    data_set($settings, $path, $new);
                    $changed = true;
                }
            }
            if (!$changed) {
                return;
            }

            DB::transaction(function () use ($row, $settings): void {
                $version = (int) $row->version + 1;
                DB::table('instagram_post_campaigns')->where('id', $row->id)->update([
                    'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                    'version' => $version,
                    'updated_at' => now(),
                ]);
                $keywords = Schema::hasTable('instagram_post_campaign_keywords')
                    ? DB::table('instagram_post_campaign_keywords')->where('campaign_id', $row->id)->get(['keyword', 'match_mode', 'is_active'])
                        ->map(fn ($k) => ['keyword' => $k->keyword, 'match_mode' => $k->match_mode, 'is_active' => (bool) $k->is_active])->values()->all()
                    : [];
                DB::table('instagram_post_campaign_versions')->insertOrIgnore([
                    'campaign_id' => $row->id,
                    'version' => $version,
                    'snapshot' => json_encode([
                        'title' => $row->title,
                        'status' => $row->status,
                        'follow_required' => (bool) $row->follow_required,
                        'public_reply_enabled' => (bool) $row->public_reply_enabled,
                        'dm_enabled' => (bool) $row->dm_enabled,
                        'settings' => $settings,
                        'keywords' => $keywords,
                    ], JSON_UNESCAPED_UNICODE),
                    'admin_id' => null,
                    'note' => 'بهبود متن کارت دایرکت (اصلاح سامانه)',
                    'created_at' => now(),
                ]);
            });
        });
    }

    public function down(): void
    {
        // اصلاح محتوا است؛ بازگشت از صفحه‌ی «نسخه‌ها»ی همان سناریو انجام می‌شود.
    }
};
