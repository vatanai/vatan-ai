<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_settings') && ! Schema::hasColumn('video_studio_settings', 'telegram_caption_text')) {
            Schema::table('video_studio_settings', function (Blueprint $table): void {
                $table->text('telegram_caption_text')->nullable()->after('caption_text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_settings') && Schema::hasColumn('video_studio_settings', 'telegram_caption_text')) {
            Schema::table('video_studio_settings', function (Blueprint $table): void {
                $table->dropColumn('telegram_caption_text');
            });
        }
    }
};
