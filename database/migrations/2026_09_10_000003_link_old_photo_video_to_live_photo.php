<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('product_video_relations')) {
            return;
        }

        $videoId = DB::table('products')->where('slug', 'video-studio-memory-alive')->value('id');
        if (! $videoId) {
            return;
        }

        $photoId = DB::table('products')->where('slug', 'ai-fashion-portrait')->value('id')
            ?: DB::table('products')->where('slug', '322171-my-80s-photograph')->value('id');
        if (! $photoId) {
            return;
        }

        DB::table('product_video_relations')->updateOrInsert(
            ['video_product_id' => $videoId, 'photo_product_id' => $photoId],
            ['sort_order' => 0, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        // رابطهٔ نمونه عمداً در rollback حذف نمی‌شود.
    }
};
