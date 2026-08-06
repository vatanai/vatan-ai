<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_credit_accounts')) {
            DB::table('service_credit_accounts')
                ->where('slug', 'liara')
                ->update(['sync_driver' => 'liara', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_credit_accounts')) {
            DB::table('service_credit_accounts')
                ->where('slug', 'liara')
                ->update(['sync_driver' => 'manual', 'updated_at' => now()]);
        }
    }
};
