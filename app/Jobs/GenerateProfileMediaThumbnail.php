<?php

namespace App\Jobs;

use App\Models\GeneratedImage;
use App\Services\ProfileMediaThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ساخت بندانگشتی پروفایل خارج از درخواست اصلی تولید تصویر.
 * اگر صف موقتاً در دسترس نباشد، endpoint نمایش همچنان fallback امن دارد.
 */
class GenerateProfileMediaThumbnail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public function __construct(public int $generatedImageId)
    {
    }

    public function handle(ProfileMediaThumbnailService $thumbnails): void
    {
        $image = GeneratedImage::query()->find($this->generatedImageId);
        if (! $image) {
            return;
        }

        $thumbnails->generate($image);
    }

    public function uniqueId(): string
    {
        return 'profile-thumbnail:' . $this->generatedImageId;
    }
}
