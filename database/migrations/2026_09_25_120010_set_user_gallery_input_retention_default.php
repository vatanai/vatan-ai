<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_gallery_configs')) {
            DB::table('user_gallery_configs')->where('id', 1)->where('retention_days', 60)->update([
                'retention_days' => 7,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_gallery_configs')) {
            DB::table('user_gallery_configs')->where('id', 1)->where('retention_days', 7)->update([
                'retention_days' => 60,
                'updated_at' => now(),
            ]);
        }
    }
};
