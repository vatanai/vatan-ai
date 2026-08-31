<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings')) return;
        Schema::table('video_studio_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('video_studio_settings', 'instagram_prompt')) {
                $table->longText('instagram_prompt')->nullable()->after('prompt_profile');
            }
            if (!Schema::hasColumn('video_studio_settings', 'telegram_prompt')) {
                $table->longText('telegram_prompt')->nullable()->after('instagram_prompt');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('video_studio_settings')) return;
        Schema::table('video_studio_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('video_studio_settings', 'telegram_prompt')) $table->dropColumn('telegram_prompt');
            if (Schema::hasColumn('video_studio_settings', 'instagram_prompt')) $table->dropColumn('instagram_prompt');
        });
    }
};
