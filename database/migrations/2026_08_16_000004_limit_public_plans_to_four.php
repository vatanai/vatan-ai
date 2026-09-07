<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // این پلن قدیمی نباید در کاتالوگ عمومی یا صفحه نخست نمایش داده شود.
        DB::table('plans')->where('slug', 'start')->update([
            'status' => 'inactive',
            'is_featured' => false,
            'updated_at' => $now,
        ]);

        $display = DB::table('plan_settings')->where('key', 'display')->value('value');
        $display = json_decode($display ?: '[]', true);
        $display = is_array($display) ? $display : [];
        $display['home_limit'] = 4;

        DB::table('plan_settings')->updateOrInsert(
            ['key' => 'display'],
            ['value' => json_encode($display, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        // وضعیت قبلی محیط‌ها ممکن است متفاوت باشد؛ بازگردانی خودکار امن نیست.
    }
};
