<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * اصلاح داده (idempotent): پیش از رفع حلقه‌ی «پاسخ به کامنت خود پیج»، پاسخ‌های خود حساب (ai_vatan)
 * به‌عنوان کامنت ورودی ثبت و روی آن‌ها قانون اجرا شده بود. این اجراها و نشست‌های جریان دایرکتِ
 * خود حساب حذف می‌شوند تا آمار «کامنت منطبق / دایرکت ارسال‌شده / قیف» درست شود.
 * پیام‌ها و سابقه‌ی ارسال (outbound) برای ممیزی حفظ می‌شوند؛ فقط ارجاعشان به اجرای حذف‌شده خالی می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['instagram_channels', 'instagram_contacts', 'instagram_automation_runs', 'instagram_automation_rules', 'instagram_outbound_messages', 'instagram_post_flow_sessions'] as $table) {
            if (!Schema::hasTable($table)) {
                return;
            }
        }

        $channels = DB::table('instagram_channels')->whereNotNull('username')->where('username', '!=', '')->get(['workspace_id', 'username']);
        foreach ($channels as $channel) {
            $username = mb_strtolower(ltrim(trim((string) $channel->username), '@'));
            if ($username === '') {
                continue;
            }

            $contactIds = DB::table('instagram_contacts')
                ->where('workspace_id', $channel->workspace_id)
                ->whereRaw('LOWER(username) = ?', [$username])
                ->pluck('id');
            if ($contactIds->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($contactIds): void {
                $runs = DB::table('instagram_automation_runs')->whereIn('contact_id', $contactIds)->get(['id', 'rule_id']);
                $runIds = $runs->pluck('id');
                $ruleIds = $runs->pluck('rule_id')->unique()->values();

                DB::table('instagram_post_flow_sessions')->whereIn('contact_id', $contactIds)->delete();
                if ($runIds->isNotEmpty()) {
                    DB::table('instagram_outbound_messages')->whereIn('automation_run_id', $runIds)->update(['automation_run_id' => null]);
                    DB::table('instagram_automation_runs')->whereIn('id', $runIds)->delete();
                }

                // شمارنده‌های قانون از روی اجراهای باقی‌مانده بازسازی می‌شوند.
                foreach ($ruleIds as $ruleId) {
                    $base = DB::table('instagram_automation_runs')->where('rule_id', $ruleId);
                    DB::table('instagram_automation_rules')->where('id', $ruleId)->update([
                        'runs_count' => (clone $base)->count(),
                        'success_count' => (clone $base)->whereIn('status', ['success', 'simulated'])->count(),
                        'failure_count' => (clone $base)->where('status', 'failed')->count(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        // اصلاح داده است؛ بازگشت ندارد.
    }
};
