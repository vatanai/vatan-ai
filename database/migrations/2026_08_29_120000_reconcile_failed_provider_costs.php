<?php

use App\Models\AiProviderRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_provider_requests') || !Schema::hasTable('finance_transactions')) {
            return;
        }

        // قبل از اصلاح مسیر هزینه، بعضی درخواست‌های ناموفق با برآورد اولیه
        // به‌صورت pending در دفتر مالی مانده‌اند. این‌ها کسر واقعی نیستند و
        // فقط همان رکوردهای متصل به درخواست failed/canceled اصلاح می‌شوند.
        $failedRequestIds = DB::table('ai_provider_requests')
            ->whereIn('status', ['failed', 'canceled', 'cancelled'])
            ->whereNull('actual_cost_usd')
            ->pluck('id');

        if ($failedRequestIds->isEmpty()) {
            return;
        }

        DB::table('finance_transactions')
            ->where('source_type', AiProviderRequest::class)
            ->whereIn('source_id', $failedRequestIds)
            ->whereIn('status', ['draft', 'pending'])
            ->update([
                'amount_original' => 0,
                'amount_irr' => 0,
                'amount_toman' => 0,
                'status' => 'failed',
                'paid_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // مبلغ قبلی بر اساس برآورد بوده و قابل بازسازی ایمن نیست؛ rollback
        // عمداً دادهٔ مالی اصلاح‌شده را دوباره به مبلغ نادرست برنمی‌گرداند.
    }
};
