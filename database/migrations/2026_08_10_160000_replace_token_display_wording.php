<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            ['table' => 'sms_templates', 'column' => 'body'],
            ['table' => 'sms_messages', 'column' => 'body'],
            ['table' => 'sms_campaigns', 'column' => 'body'],
            ['table' => 'sms_settings', 'column' => 'value'],
            ['table' => 'referral_settings', 'column' => 'profile_description'],
            ['table' => 'plans', 'column' => 'token_label'],
            ['table' => 'plans', 'column' => 'features'],
        ];

        foreach ($columns as $entry) {
            $table = $entry['table'];
            $column = $entry['column'];
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, 'like', '%توکن%')
                ->update([$column => DB::raw("REPLACE(`{$column}`, 'توکن', 'اعتبار')")]);
        }
    }

    public function down(): void
    {
        // متن نمایشی «اعتبار» عمداً به عبارت فنی قبلی برگردانده نمی‌شود.
    }
};
