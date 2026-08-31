<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings')) {
            return;
        }

        Schema::table('video_studio_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('video_studio_settings', 'hook_text')) {
                $table->text('hook_text')->nullable()->after('caption_guidelines');
            }
            if (!Schema::hasColumn('video_studio_settings', 'caption_text')) {
                $table->text('caption_text')->nullable()->after('hook_text');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('video_studio_settings')) {
            return;
        }

        Schema::table('video_studio_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('video_studio_settings', 'caption_text')) {
                $table->dropColumn('caption_text');
            }
            if (Schema::hasColumn('video_studio_settings', 'hook_text')) {
                $table->dropColumn('hook_text');
            }
        });
    }
};
