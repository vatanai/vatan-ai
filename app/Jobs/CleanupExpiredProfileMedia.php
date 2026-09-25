<?php

namespace App\Jobs;

use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Services\ProfileMediaThumbnailService;
use App\Services\UserStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Schema;

class CleanupExpiredProfileMedia implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(UserStorageService $storage, ProfileMediaThumbnailService $thumbnails): void
    {
        if (Schema::hasTable('generated_images') && Schema::hasColumn('generated_images', 'expires_at')) {
            GeneratedImage::query()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->chunkById(100, function ($items) use ($storage): void {
                    foreach ($items as $item) {
                        $thumbnails->delete($item);
                        $storage->deletePublicFile($item->image_path);
                        $item->delete();
                        $storage->forgetProfileSnapshotForUserId((int) $item->user_id);
                    }
                });
        }

        if (Schema::hasTable('generated_videos') && Schema::hasColumn('generated_videos', 'expires_at')) {
            GeneratedVideo::query()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->chunkById(100, function ($items) use ($storage): void {
                    foreach ($items as $item) {
                        $storage->deletePublicFile($item->video_path);
                        $storage->deletePublicFile($item->poster_path);
                        $item->delete();
                        $storage->forgetProfileSnapshotForUserId((int) $item->user_id);
                    }
                });
        }
    }
}
