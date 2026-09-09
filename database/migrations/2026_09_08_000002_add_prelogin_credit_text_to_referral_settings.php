<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_settings')) {
            return;
        }

        if (! Schema::hasColumn('referral_settings', 'prelogin_credit_text')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                $table->string('prelogin_credit_text', 255)->nullable()->after('registration_gift_tokens');
            });
        }

        DB::table('referral_settings')
            ->select(['id', 'registration_gift_tokens', 'prelogin_credit_text'])
            ->where(function ($query): void {
                $query->whereNull('prelogin_credit_text')->orWhere('prelogin_credit_text', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($settings): void {
                DB::table('referral_settings')->where('id', $settings->id)->update([
                    'prelogin_credit_text' => 'هدیه ' . (int) $settings->registration_gift_tokens . ' اعتبار',
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('referral_settings') && Schema::hasColumn('referral_settings', 'prelogin_credit_text')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                $table->dropColumn('prelogin_credit_text');
            });
        }
    }
};
