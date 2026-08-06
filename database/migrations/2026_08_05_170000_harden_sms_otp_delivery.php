<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_messages')) {
            DB::table('sms_messages')
                ->where(function ($query) {
                    $query->where('type', 'like', '%otp%')
                        ->orWhere('type', 'like', 'authentication:%');
                })
                ->update([
                    'body' => 'رمز یک‌بارمصرف (مخفی‌شده)',
                    'metadata' => null,
                ]);
        }

        if (! Schema::hasTable('sms_templates')) {
            return;
        }

        $canonicalTemplates = [
            'otp_code' => '506694',
            'registration_success' => '506692',
            'plan_purchase_success' => '506695',
            'plan_purchase_failed' => '506696',
        ];

        foreach ($canonicalTemplates as $eventKey => $bodyId) {
            $templates = DB::table('sms_templates')
                ->where('event_key', $eventKey)
                ->where('provider_template_id', $bodyId)
                ->orderByDesc('is_default')
                ->orderByDesc('sent_count')
                ->orderBy('id')
                ->get(['id']);

            $canonicalId = $templates->first()?->id;
            if (! $canonicalId) {
                continue;
            }

            DB::table('sms_templates')->where('event_key', $eventKey)->update(['is_default' => false]);
            DB::table('sms_templates')->where('id', $canonicalId)->update([
                'is_active' => true,
                'is_default' => true,
                'provider_approval_status' => 'approved',
                'updated_at' => now(),
            ]);

            DB::table('sms_templates')
                ->where('event_key', $eventKey)
                ->where('provider_template_id', $bodyId)
                ->where('id', '!=', $canonicalId)
                ->update([
                    'is_active' => false,
                    'is_default' => false,
                    'updated_at' => now(),
                ]);
        }

        DB::table('sms_templates')
            ->where('event_key', 'login_success')
            ->where('provider_template_id', '506692')
            ->update([
                'is_active' => false,
                'is_default' => false,
                'provider_note' => 'این شناسه مخصوص خوش‌آمدگویی ثبت‌نام است.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // اطلاعات حساس پاک‌شده و تعیین الگوی کانونی نباید به وضعیت ناامن قبلی برگردد.
    }
};
