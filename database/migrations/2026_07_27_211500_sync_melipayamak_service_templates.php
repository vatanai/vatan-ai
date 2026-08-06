<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_templates', 'provider_approval_status')) {
                $table->string('provider_approval_status', 24)->default('not_configured')->after('provider_variables')->index();
            }
            if (!Schema::hasColumn('sms_templates', 'provider_note')) {
                $table->string('provider_note')->nullable()->after('provider_approval_status');
            }
            if (!Schema::hasColumn('sms_templates', 'provider_submitted_at')) {
                $table->timestamp('provider_submitted_at')->nullable()->after('provider_note');
            }
        });

        $submittedAt = now();
        $templates = [
            'otp_code' => ['502328', ['code'], 'approved', null],
            'login_success' => ['504170', ['name', 'login_time'], 'pending', null],
            'admin_login' => ['504173', ['admin_name', 'login_time', 'ip'], 'pending', null],
            'sms_balance_low' => ['504174', ['provider_name', 'balance', 'threshold'], 'pending', 'الگوی مشترک هشدار اعتبار سرویس'],
            'ai_balance_low' => ['504174', ['provider_name', 'balance', 'threshold'], 'pending', 'الگوی مشترک هشدار اعتبار سرویس'],
            'credit_low' => ['504178', ['name', 'balance'], 'pending', null],
            'order_completed' => ['504179', ['name', 'order_number'], 'pending', null],
            'purchase_success' => ['504181', ['name', 'order_number', 'balance'], 'pending', null],
            'refund_success' => ['504185', ['name', 'amount', 'order_number', 'balance'], 'pending', null],
            'registration_success' => ['504187', ['name', 'gift_credits'], 'pending', null],
        ];

        foreach ($templates as $event => [$bodyId, $variables, $status, $note]) {
            DB::table('sms_templates')->where('event_key', $event)->update([
                'provider_method' => 'shared',
                'provider_template_id' => $bodyId,
                'provider_variables' => json_encode($variables, JSON_UNESCAPED_UNICODE),
                'provider_approval_status' => $status,
                'provider_note' => $note,
                'provider_submitted_at' => $event === 'otp_code' ? null : $submittedAt,
                'updated_at' => $submittedAt,
            ]);
        }

        DB::table('sms_templates')->where('event_key', 'birthday')->update([
            'provider_method' => 'simple',
            'provider_template_id' => null,
            'provider_variables' => null,
            'provider_approval_status' => 'not_applicable',
            'provider_note' => 'مناسب کمپین تبلیغاتی؛ قابل ثبت روی خط خدماتی نیست',
            'provider_submitted_at' => null,
            'updated_at' => $submittedAt,
        ]);
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn(['provider_approval_status', 'provider_note', 'provider_submitted_at']);
        });
    }
};
