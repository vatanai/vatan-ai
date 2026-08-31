<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_settings') || Schema::hasColumn('video_studio_settings', 'prompt_profile')) {
            return;
        }

        Schema::table('video_studio_settings', function (Blueprint $table): void {
            $table->longText('prompt_profile')->nullable()->after('caption_text');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_settings') && Schema::hasColumn('video_studio_settings', 'prompt_profile')) {
            Schema::table('video_studio_settings', function (Blueprint $table): void {
                $table->dropColumn('prompt_profile');
            });
        }
    }
};
