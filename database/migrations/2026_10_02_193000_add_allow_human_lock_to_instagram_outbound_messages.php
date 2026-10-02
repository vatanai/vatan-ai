<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instagram_outbound_messages', 'allow_human_lock')) {
            Schema::table('instagram_outbound_messages', function (Blueprint $table): void {
                $table->boolean('allow_human_lock')->default(false)->after('ai_suggestion_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instagram_outbound_messages', 'allow_human_lock')) {
            Schema::table('instagram_outbound_messages', function (Blueprint $table): void {
                $table->dropColumn('allow_human_lock');
            });
        }
    }
};
