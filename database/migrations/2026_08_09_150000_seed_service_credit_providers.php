<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('service_credit_accounts')) {
            return;
        }

        $now = now();
        $accounts = [
            ['name' => 'Cloudiva', 'slug' => 'cloudiva', 'currency' => 'USD', 'sync_driver' => 'manual'],
            ['name' => 'Fal.ai', 'slug' => 'fal', 'currency' => 'USD', 'sync_driver' => 'fal'],
            ['name' => 'Replicate', 'slug' => 'replicate', 'currency' => 'USD', 'sync_driver' => 'replicate'],
        ];

        foreach ($accounts as $account) {
            DB::table('service_credit_accounts')->updateOrInsert(
                ['slug' => $account['slug']],
                [
                    'name' => $account['name'],
                    'currency' => $account['currency'],
                    'sync_driver' => $account['sync_driver'],
                    'show_on_dashboard' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('service_credit_accounts')->whereIn('slug', ['openrouter', 'liara'])
            ->update(['show_on_dashboard' => true, 'is_active' => true, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('service_credit_accounts')->whereIn('slug', ['cloudiva', 'fal', 'replicate'])->delete();
    }
};
