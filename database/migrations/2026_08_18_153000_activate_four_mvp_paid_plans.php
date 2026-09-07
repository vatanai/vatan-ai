<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** چهار پلن پولیِ آزمایش MVP؛ سطح رایگان از وضعیت نبود پلن کاربر تأمین می‌شود. */
    public function up(): void
    {
        foreach (['start', 'pro', 'premium', 'business'] as $index => $slug) {
            DB::table('plans')->where('slug', $slug)->update([
                'status' => 'active',
                'sort_order' => $index + 1,
                'updated_at' => now(),
            ]);
        }
        DB::table('plans')->where('slug', 'free')->update(['status' => 'inactive', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('plans')->where('slug', 'start')->update(['status' => 'inactive', 'updated_at' => now()]);
        DB::table('plans')->where('slug', 'free')->update(['status' => 'active', 'updated_at' => now()]);
    }
};
