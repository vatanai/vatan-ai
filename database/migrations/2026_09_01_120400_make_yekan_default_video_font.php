<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('video_studio_fonts')) {
            return;
        }

        DB::table('video_studio_fonts')->update(['is_default' => false]);
        DB::table('video_studio_fonts')->where('slug', 'B_Yekan')->update(['is_default' => true]);

        if (Schema::hasTable('video_studio_settings')) {
            DB::table('video_studio_settings')
                ->where(function ($query): void {
                    $query->whereNull('font_family')->orWhere('font_family', 'Modam');
                })
                ->update(['font_family' => 'B_Yekan']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('video_studio_fonts')) {
            return;
        }

        DB::table('video_studio_fonts')->update(['is_default' => false]);
        DB::table('video_studio_fonts')->where('slug', 'Modam')->update(['is_default' => true]);
    }
};
