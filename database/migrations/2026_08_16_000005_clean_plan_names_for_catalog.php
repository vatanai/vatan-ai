<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'free' => 'پلن رایگان',
            'pro' => 'پلن Pro',
            'premium' => 'پلن Premium',
            'business' => 'پلن Business',
        ];

        foreach ($names as $slug => $name) {
            DB::table('plans')->where('slug', $slug)->update([
                'name' => $name,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // نام‌های دارای ایموجی، داده‌ی نمایشی قدیمی بودند و بازگردانی‌شان لازم نیست.
    }
};
