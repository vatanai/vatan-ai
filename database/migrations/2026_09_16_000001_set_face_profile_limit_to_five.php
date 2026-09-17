<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasColumn('plans', 'face_profile_limit')) {
            return;
        }

        foreach (DB::table('plans')->get(['id', 'features']) as $plan) {
            $features = json_decode((string) ($plan->features ?? '[]'), true);
            $features = is_array($features) ? $features : [];
            $features = collect($features)
                ->reject(fn ($feature) => trim((string) (is_array($feature) ? ($feature['title'] ?? '') : $feature)) === 'پروفایل چهره')
                ->values()
                ->all();
            $features[] = [
                'title' => 'پروفایل چهره',
                'value' => '5',
                'included' => 'limited',
                'highlighted' => true,
                'sort_order' => count($features) + 1,
            ];

            DB::table('plans')->where('id', $plan->id)->update([
                'face_profile_limit' => 5,
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // سقف پنج‌تایی بخشی از رفتار فعلی محصول است و بازگردانی خودکار
        // مقادیر قدیمی پلن‌ها بدون دانستن وضعیت پیشین هر پلن امن نیست.
    }
};
