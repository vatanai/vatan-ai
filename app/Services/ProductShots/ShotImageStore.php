<?php

namespace App\Services\ProductShots;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ذخیره و آماده‌سازی تصویرهای «استودیو محصول» روی دیسک public.
 * منطق ذخیره‌ی خروجی هم‌ارز ProductGenerateController::saveGeneratedImage است
 * (کپی مستقل تا کنترلر مسیر چهره‌محور دست نخورد).
 */
class ShotImageStore
{
    public const MAX_SIDE = 2048;

    /**
     * عکس آپلودی کاربر را بازرمزگذاری می‌کند: چرخش EXIF، حذف متادیتا، کوچک‌سازی تا ۲۰۴۸.
     *
     * @return array{path:string, width:int, height:int, size:int, mime:string}
     */
    public function storeUpload(UploadedFile $file, string $directory): array
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);
        if (! $image) {
            throw new RuntimeException('فایل تصویر قابل خواندن نیست.');
        }

        $image = $this->autoOrient($image, $file->getRealPath());
        $image = $this->limitSize($image, self::MAX_SIDE);

        return $this->persistJpeg($image, $directory);
    }

    /**
     * بریدن با کادر نرمال‌شده (۰ تا ۱) و اصلاح نور ملایم؛ برای «اصلاح خودکار» فیلتر کیفیت.
     *
     * @param array{x:float,y:float,w:float,h:float}|null $box
     * @return array{path:string, width:int, height:int, size:int, mime:string}|null
     */
    public function autoFix(string $sourcePath, ?array $box, bool $brighten, string $directory): ?array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            return null;
        }
        $image = @imagecreatefromstring((string) $disk->get($sourcePath));
        if (! $image) {
            return null;
        }
        $changed = false;
        $w = imagesx($image);
        $h = imagesy($image);

        if ($box && $this->isUsefulBox($box)) {
            // ۸٪ حاشیه دور محصول تا لبه‌ها بریده نشوند
            $pad = 0.08;
            $x = max(0, (int) floor(($box['x'] - $pad * $box['w']) * $w));
            $y = max(0, (int) floor(($box['y'] - $pad * $box['h']) * $h));
            $cw = min($w - $x, (int) ceil($box['w'] * (1 + 2 * $pad) * $w));
            $ch = min($h - $y, (int) ceil($box['h'] * (1 + 2 * $pad) * $h));
            if ($cw > 64 && $ch > 64 && ($cw < $w * 0.95 || $ch < $h * 0.95)) {
                $cropped = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $cw, 'height' => $ch]);
                if ($cropped) {
                    imagedestroy($image);
                    $image = $cropped;
                    $changed = true;
                }
            }
        }

        if ($brighten) {
            imagefilter($image, IMG_FILTER_BRIGHTNESS, 28);
            imagefilter($image, IMG_FILTER_CONTRAST, -8);
            $changed = true;
        }

        if (! $changed) {
            imagedestroy($image);

            return null;
        }

        return $this->persistJpeg($image, $directory);
    }

    /** ذخیره‌ی خروجی مدل (base64 یا لینک) و برگرداندن مسیر نسبی روی دیسک public. */
    public function saveProviderOutput(array $result): string
    {
        $items = ! empty($result['data']) && is_array($result['data']) ? $result['data'] : [$result];
        $first = is_array($items[0] ?? null) ? $items[0] : [];
        $filename = 'generated/' . uniqid('shot_') . '.png';

        $b64 = $first['b64_json'] ?? null;
        if ($b64) {
            if (str_contains($b64, 'base64,')) {
                $b64 = explode('base64,', $b64, 2)[1];
            }
            $binary = base64_decode(trim($b64), true);
            if ($binary === false) {
                throw new RuntimeException('خروجی Base64 مدل قابل رمزگشایی نیست.');
            }
            Storage::disk('public')->put($filename, $binary);

            return $filename;
        }

        $url = $first['url'] ?? null;
        if ($url) {
            if (str_starts_with($url, 'data:')) {
                $binary = base64_decode(explode(',', $url, 2)[1] ?? '', true);
                if ($binary === false) {
                    throw new RuntimeException('خروجی data URL مدل قابل رمزگشایی نیست.');
                }
                Storage::disk('public')->put($filename, $binary);

                return $filename;
            }
            $headers = is_array($first['headers'] ?? null) ? $first['headers'] : [];
            $download = Http::withHeaders($headers)->connectTimeout(15)->timeout(120)->get($url);
            if (! $download->successful()) {
                throw new RuntimeException('دانلود خروجی مدل ناموفق بود.');
            }
            Storage::disk('public')->put($filename, $download->body());

            return $filename;
        }

        throw new RuntimeException('پاسخ مدل تصویری معتبر نداشت.');
    }

    /** مرجع تصویر برای ارسال به مدل: URL عمومی در production، data URL در لوکال. */
    public function reference(string $path): ?string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }
        if (app()->environment('production')) {
            return asset('storage/' . ltrim($path, '/'));
        }
        $mime = $disk->mimeType($path) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode((string) $disk->get($path));
    }

    /** نسخه‌ی کوچک (حداکثر ۷۶۸) برای مدل بینای ارزان؛ همیشه data URL تا هزینه و حجم کم بماند. */
    public function visionReference(string $path, int $maxSide = 768): ?string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }
        $image = @imagecreatefromstring((string) $disk->get($path));
        if (! $image) {
            return null;
        }
        $image = $this->limitSize($image, $maxSide);
        ob_start();
        imagejpeg($image, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }

    public function dimensions(string $path): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return [0, 0];
        }
        $info = @getimagesizefromstring((string) $disk->get($path));

        return [(int) ($info[0] ?? 0), (int) ($info[1] ?? 0)];
    }

    /** میانگین روشنایی ۰ تا ۲۵۵ روی نمونه‌ی کوچک. */
    public function averageLuminance(string $path): ?float
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }
        $image = @imagecreatefromstring((string) $disk->get($path));
        if (! $image) {
            return null;
        }
        $small = imagescale($image, 32, 32);
        imagedestroy($image);
        if (! $small) {
            return null;
        }
        $sum = 0;
        for ($x = 0; $x < 32; $x++) {
            for ($y = 0; $y < 32; $y++) {
                $rgb = imagecolorat($small, $x, $y);
                $sum += 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            }
        }
        imagedestroy($small);

        return $sum / 1024;
    }

    /** امتیاز تقریبی وضوح لبه‌ها؛ برای هشدار محلی پیش از فراخوانی مدل بینایی. */
    public function sharpnessScore(string $path): ?float
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) return null;
        $image = @imagecreatefromstring((string) $disk->get($path));
        if (! $image) return null;
        $small = imagescale($image, 96, 96, IMG_BILINEAR_FIXED);
        imagedestroy($image);
        if (! $small) return null;

        $sum = 0.0;
        $sumSq = 0.0;
        $count = 0;
        for ($y = 1; $y < 95; $y++) {
            for ($x = 1; $x < 95; $x++) {
                $center = $this->grayAt($small, $x, $y);
                $laplace = (4 * $center) - $this->grayAt($small, $x - 1, $y) - $this->grayAt($small, $x + 1, $y)
                    - $this->grayAt($small, $x, $y - 1) - $this->grayAt($small, $x, $y + 1);
                $sum += $laplace;
                $sumSq += $laplace * $laplace;
                $count++;
            }
        }
        imagedestroy($small);
        if ($count === 0) return null;
        $mean = $sum / $count;

        return max(0.0, ($sumSq / $count) - ($mean * $mean));
    }

    /**
     * ساخت شیت مرجع ۲×۲ با پس‌زمینه خنثی؛ تصاویر اصلی نیز جداگانه برای مدل حفظ می‌شوند.
     * @param array<int,string> $paths
     */
    public function createProductSheet(array $paths, string $directory, int $size = 2048): ?array
    {
        $paths = array_values(array_slice(array_filter($paths), 0, 4));
        if ($paths === []) return null;
        $size = max(1024, min(3072, $size));
        $canvas = imagecreatetruecolor($size, $size);
        if (! $canvas) return null;
        $background = imagecolorallocate($canvas, 245, 246, 246);
        $divider = imagecolorallocate($canvas, 218, 220, 219);
        imagefill($canvas, 0, 0, $background);

        $gap = max(12, (int) round($size * 0.012));
        $padding = max(24, (int) round($size * 0.025));
        $cell = (int) floor(($size - ($padding * 2) - $gap) / 2);
        $disk = Storage::disk('public');
        foreach ($paths as $index => $path) {
            if (! $disk->exists($path)) continue;
            $source = @imagecreatefromstring((string) $disk->get($path));
            if (! $source) continue;
            $sw = imagesx($source); $sh = imagesy($source);
            $scale = min(($cell - $gap * 2) / max(1, $sw), ($cell - $gap * 2) / max(1, $sh));
            $dw = max(1, (int) round($sw * $scale));
            $dh = max(1, (int) round($sh * $scale));
            $column = $index % 2; $row = intdiv($index, 2);
            $cx = $padding + ($column * ($cell + $gap));
            $cy = $padding + ($row * ($cell + $gap));
            imagefilledrectangle($canvas, $cx, $cy, $cx + $cell, $cy + $cell, $background);
            imagerectangle($canvas, $cx, $cy, $cx + $cell, $cy + $cell, $divider);
            $dx = $cx + (int) floor(($cell - $dw) / 2);
            $dy = $cy + (int) floor(($cell - $dh) / 2);
            imagecopyresampled($canvas, $source, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);
            imagedestroy($source);
        }

        return $this->persistJpeg($canvas, $directory, 94);
    }

    private function persistJpeg(\GdImage $image, string $directory, int $quality = 90): array
    {
        $path = trim($directory, '/') . '/' . Str::uuid() . '.jpg';
        ob_start();
        imagejpeg($image, null, max(70, min(100, $quality)));
        $jpeg = (string) ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);
        Storage::disk('public')->put($path, $jpeg);

        return ['path' => $path, 'width' => $width, 'height' => $height, 'size' => strlen($jpeg), 'mime' => 'image/jpeg'];
    }

    private function limitSize(\GdImage $image, int $maxSide): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if (max($w, $h) <= $maxSide) {
            return $image;
        }
        $scale = $maxSide / max($w, $h);
        $resized = imagescale($image, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)), IMG_BICUBIC);
        if ($resized) {
            imagedestroy($image);

            return $resized;
        }

        return $image;
    }

    private function autoOrient(\GdImage $image, string $realPath): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($realPath);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function isUsefulBox(array $box): bool
    {
        foreach (['x', 'y', 'w', 'h'] as $k) {
            if (! isset($box[$k]) || ! is_numeric($box[$k])) {
                return false;
            }
        }

        return $box['w'] > 0.05 && $box['h'] > 0.05 && $box['x'] >= 0 && $box['y'] >= 0
            && $box['x'] + $box['w'] <= 1.001 && $box['y'] + $box['h'] <= 1.001;
    }

    private function grayAt(\GdImage $image, int $x, int $y): float
    {
        $rgb = imagecolorat($image, $x, $y);

        return 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
    }
}
