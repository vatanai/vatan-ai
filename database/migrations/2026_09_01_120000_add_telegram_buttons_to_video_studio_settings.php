<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings') || Schema::hasColumn('video_studio_settings', 'telegram_buttons')) {
            return;
        }

        Schema::table('video_studio_settings', function (Blueprint $table): void {
            $table->json('telegram_buttons')->nullable()->after('telegram_prompt');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_settings') && Schema::hasColumn('video_studio_settings', 'telegram_buttons')) {
            Schema::table('video_studio_settings', function (Blueprint $table): void {
                $table->dropColumn('telegram_buttons');
            });
        }
    }
};
