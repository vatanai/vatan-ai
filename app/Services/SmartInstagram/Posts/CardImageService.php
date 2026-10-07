<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\Product;
use App\Models\SmartInstagram\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * تصویر کارت دایرکت.
 * اینستاگرام تصویر کارت را مربعی نشان می‌دهد و فقط JPEG/PNG عمومی را می‌پذیرد؛ کاور ریلز (۹:۱۶) یا تصویر WebP
 * محصول یا خالی نمایش داده می‌شود یا بد بریده می‌شود. این سرویس یک JPEG مربعی ۱۰۸۰ (بدون progressive)
 * از منبع می‌سازد، روی دیسک عمومی نگه می‌دارد و تا تغییر منبع دوباره نمی‌سازد.
 */
class CardImageService
{
    private const DIR = 'smart-instagram/cards';

    private const SIZE = 1080;

    /** @return array<int,string> آدرس‌های قابل استفاده به‌ترتیب اولویت */
    public function forPost(?Post $post): array
    {
        if (!$post) {
            return [];
        }

        return array_values(array_unique(array_filter([
            $post->cover_path ? $this->squareFromDisk($post->cover_path, 'post-'.$post->id) : null,
            $this->publicUrl($post->cover_source_url),
            $post->coverUrl(),
        ])));
    }

    /** @return array<int,string> */
    public function forProduct(?Product $product): array
    {
        if (!$product) {
            return [];
        }
        $urls = [];
        foreach (array_merge([$product->cover], (array) $product->sample_outputs, [$product->thumbnail]) as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            if (Str::startsWith($path, ['http://', 'https://'])) {
                $urls[] = $path;
                continue;
            }
            if (Storage::disk('public')->exists($path)) {
                $urls[] = $this->squareFromDisk($path, 'product-'.$product->id);
                break;
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /** JPEG مربعی از فایل روی دیسک عمومی؛ در صورت خطا null (فراخواننده سراغ گزینه‌ی بعدی می‌رود). */
    public function squareFromDisk(string $path, string $key): ?string
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            $bytes = (string) $disk->get($path);
            $prefix = self::DIR.'/'.Str::slug($key).'-';
            $target = $prefix.substr(md5($bytes), 0, 12).'.jpg';
            if ($disk->exists($target)) {
                return $disk->url($target);
            }
            if (!function_exists('imagecreatefromstring')) {
                return null;
            }
            $source = @imagecreatefromstring($bytes);
            if (!$source) {
                return null;
            }
            $w = imagesx($source);
            $h = imagesy($source);
            $side = min($w, $h);
            // تصویر عمودی (ریلز): برش از یک‌سوم بالایی تا چهره و موضوع اصلی حفظ شود؛ افقی: از وسط.
            $x = (int) round(($w - $side) / 2);
            $y = $h > $w ? (int) round(($h - $side) * 0.3) : (int) round(($h - $side) / 2);
            $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopyresampled($canvas, $source, 0, 0, $x, $y, self::SIZE, self::SIZE, $side, $side);
            imageinterlace($canvas, false);
            ob_start();
            imagejpeg($canvas, null, 86);
            $jpeg = (string) ob_get_clean();
            imagedestroy($canvas);
            imagedestroy($source);
            if ($jpeg === '') {
                return null;
            }
            $disk->put($target, $jpeg, 'public');
            // نسخه‌های قدیمی همین منبع پاک می‌شوند (فقط پس از ساخت موفق نسخه‌ی تازه).
            foreach ($disk->files(self::DIR) as $old) {
                if ($old !== $target && str_starts_with($old, $prefix)) {
                    $disk->delete($old);
                }
            }

            return $disk->url($target);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function publicUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : null;
    }
}
