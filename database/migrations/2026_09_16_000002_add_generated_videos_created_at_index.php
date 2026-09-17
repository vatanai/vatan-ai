<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generated_videos') || ! Schema::hasColumn('generated_videos', 'created_at')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM generated_videos'))
            ->contains(fn (object $index): bool => (string) $index->Key_name === 'generated_videos_created_at_index');

        if ($exists) {
            return;
        }

        Schema::table('generated_videos', function (Blueprint $table): void {
            $table->index('created_at', 'generated_videos_created_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('generated_videos')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM generated_videos'))
            ->contains(fn (object $index): bool => (string) $index->Key_name === 'generated_videos_created_at_index');

        if ($exists) {
            Schema::table('generated_videos', function (Blueprint $table): void {
                $table->dropIndex('generated_videos_created_at_index');
            });
        }
    }
};
