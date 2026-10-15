<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cf_calendar_items') && Schema::hasColumn('cf_calendar_items', 'engine')) {
            DB::statement('ALTER TABLE `cf_calendar_items` MODIFY `engine` VARCHAR(255) NULL');
        }

        if (Schema::hasTable('cf_styles') && Schema::hasColumn('cf_styles', 'engine')) {
            DB::statement('ALTER TABLE `cf_styles` MODIFY `engine` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        // Deliberately left empty: narrowing these columns could truncate valid data.
    }
};
