<?php

namespace App\Jobs;

use App\Services\UserGalleryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupExpiredUserGalleryItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(UserGalleryService $gallery): void
    {
        $gallery->cleanupExpired();
    }
}
