<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings')) {
            return;
        }

        DB::table('video_studio_settings')
            ->where(function ($query): void {
                $query->whereNull('font_family')->orWhere('font_family', 'B_Yekan');
            })
            ->update(['font_family' => 'Modam', 'updated_at' => now()]);

        if (Schema::hasColumn('video_studio_settings', 'font_family')) {
            Schema::table('video_studio_settings', function ($table): void {
                $table->string('font_family', 80)->default('Modam')->change();
            });
        }

        DB::table('video_studio_fonts')->where('slug', 'B_Yekan')->update(['is_default' => false]);
        DB::table('video_studio_fonts')->where('slug', 'Modam')->update(['is_default' => true]);
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_settings') && Schema::hasColumn('video_studio_settings', 'font_family')) {
            Schema::table('video_studio_settings', function ($table): void {
                $table->string('font_family', 80)->default('B_Yekan')->change();
            });
        }

        if (Schema::hasTable('video_studio_fonts')) {
            DB::table('video_studio_fonts')->where('slug', 'Modam')->update(['is_default' => false]);
            DB::table('video_studio_fonts')->where('slug', 'B_Yekan')->update(['is_default' => true]);
        }
    }
};
