<?php

namespace App\Jobs;

use App\Models\UserUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredStudioUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        UserUpload::query()
            ->whereIn('status', ['stored', 'failed'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($uploads): void {
                foreach ($uploads as $upload) {
                    Storage::disk('public')->delete((string) $upload->file_path);
                    $upload->delete();
                }
            });
    }
}
