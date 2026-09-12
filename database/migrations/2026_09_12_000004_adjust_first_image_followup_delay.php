<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'first_image_followup_due_at')) {
            return;
        }

        // رکوردهای سررسیدنشده‌ای که با زمان‌بندی پنج‌ساعته ساخته شده‌اند،
        // چهار ساعت و نیم جلو می‌آیند تا فاصلهٔ واقعی از اولین ساخت، سی دقیقه باشد.
        DB::statement(
            "UPDATE users
             SET first_image_followup_due_at = DATE_SUB(first_image_followup_due_at, INTERVAL 270 MINUTE)
             WHERE first_image_followup_due_at IS NOT NULL
               AND first_image_followup_sent_at IS NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'first_image_followup_due_at')) {
            return;
        }

        DB::statement(
            "UPDATE users
             SET first_image_followup_due_at = DATE_ADD(first_image_followup_due_at, INTERVAL 270 MINUTE)
             WHERE first_image_followup_due_at IS NOT NULL
               AND first_image_followup_sent_at IS NULL"
        );
    }
};
