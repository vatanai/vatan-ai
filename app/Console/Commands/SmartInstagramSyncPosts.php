<?php

namespace App\Console\Commands;

use App\Models\SmartInstagram\Post;
use App\Services\SmartInstagram\Posts\PostSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** «ثبت پست»: همگام‌سازی پست‌های تازه و آمار پست‌هایی که سناریوی فعال/آزمایشی دارند (فقط خواندن؛ هیچ پیامی ارسال نمی‌شود). */
class SmartInstagramSyncPosts extends Command
{
    protected $signature = 'smart-instagram:sync-posts {--limit=25}';

    protected $description = 'همگام‌سازی پست‌ها، کاور و آمار برای بخش ثبت پست اینستاگرام هوشمند';

    public function handle(PostSyncService $sync): int
    {
        if (!Schema::hasTable('instagram_posts')) {
            return self::SUCCESS;
        }

        $recent = $sync->syncRecent((int) $this->option('limit'));
        $this->line($recent['message']);

        $posts = Post::query()->whereHas('campaign', fn ($q) => $q->whereIn('status', ['active', 'test']))
            ->where('media_id', 'not like', 'link:%')->limit(50)->get();
        foreach ($posts as $post) {
            try {
                $sync->syncOne($post);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $this->info($posts->count().' پست دارای سناریو به‌روز شد.');

        return self::SUCCESS;
    }
}
