<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class VideoPreviewOptimizer
{
    /**
     * ویدیوی نمایشی را با شروع سریع و فشرده‌سازی سازگار ذخیره می‌کند.
     * اگر سرور `ffmpeg` نداشته باشد، فایل اصلی بدون خراب‌شدن تجربه ذخیره می‌شود.
     */
    public function store(UploadedFile $file, string $directory = 'products/video-previews'): string
    {
        $disk = Storage::disk('public');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $rawPath = tempnam(sys_get_temp_dir(), 'vatan-video-in-');
        $optimizedPath = tempnam(sys_get_temp_dir(), 'vatan-video-out-') . '.mp4';

        try {
            file_put_contents($rawPath, file_get_contents($file->getRealPath()));
            $process = new Process([
                'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $rawPath,
                '-map', '0:v:0', '-map', '0:a?',
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
                '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '128k',
                '-movflags', '+faststart',
                '-vf', "scale='min(1920,iw)':'min(1920,ih)':force_original_aspect_ratio=decrease",
                $optimizedPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && is_file($optimizedPath) && filesize($optimizedPath) > 0) {
                $path = trim($directory, '/') . '/' . Str::uuid() . '.mp4';
                $disk->put($path, file_get_contents($optimizedPath));
                return $path;
            }

            $path = trim($directory, '/') . '/' . Str::uuid() . '.' . $extension;
            $disk->put($path, file_get_contents($rawPath));
            return $path;
        } finally {
            if (is_file($rawPath)) @unlink($rawPath);
            if (is_file($optimizedPath)) @unlink($optimizedPath);
        }
    }
}
