<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageOptimizer
{
    public const MAX_EDGE = 1600;
    public const MAX_BYTES_WITHOUT_REENCODE = 450 * 1024;
    public const WEBP_QUALITY = 88;

    /**
     * Store one final product image. Suitable files are copied byte-for-byte;
     * oversized files are resized without cropping and encoded once as WebP.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $info = @getimagesize($file->getRealPath());
        if (!$info || empty($info[0]) || empty($info[1])) {
            throw new RuntimeException('فایل انتخاب‌شده تصویر معتبری نیست.');
        }

        [$width, $height] = $info;
        if ($width * $height > 40_000_000) {
            throw new RuntimeException('ابعاد تصویر بیش از حد مجاز است.');
        }

        if (max($width, $height) <= self::MAX_EDGE && $file->getSize() <= self::MAX_BYTES_WITHOUT_REENCODE) {
            return $file->store($directory, 'public');
        }

        $encoded = extension_loaded('imagick')
            ? $this->withImagick($file->getRealPath(), $width, $height)
            : $this->withGd($file->getRealPath(), $info['mime'] ?? '', $width, $height);

        // If compression was only attempted because of file size and did not help,
        // preserve the original bytes so an already-good image never loses quality.
        if (max($width, $height) <= self::MAX_EDGE && strlen($encoded) >= $file->getSize()) {
            return $file->store($directory, 'public');
        }

        $path = trim($directory, '/') . '/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($path, $encoded);

        return $path;
    }

    private function targetSize(int $width, int $height): array
    {
        $scale = min(1, self::MAX_EDGE / max($width, $height));
        return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
    }

    private function withImagick(string $path, int $width, int $height): string
    {
        $image = new \Imagick($path);
        $image->autoOrient();
        if ($image->getImageColorspace() !== \Imagick::COLORSPACE_SRGB) {
            $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        [$targetWidth, $targetHeight] = $this->targetSize($width, $height);
        if ($targetWidth !== $width || $targetHeight !== $height) {
            $image->resizeImage($targetWidth, $targetHeight, \Imagick::FILTER_LANCZOS, 1, true);
        }
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::WEBP_QUALITY);
        $image->stripImage();
        $blob = $image->getImagesBlob();
        $image->clear();

        if ($blob === '') throw new RuntimeException('بهینه‌سازی تصویر انجام نشد.');
        return $blob;
    }

    private function withGd(string $path, string $mime, int $width, int $height): string
    {
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if (!$source) throw new RuntimeException('فرمت تصویر برای بهینه‌سازی پشتیبانی نمی‌شود.');

        [$targetWidth, $targetHeight] = $this->targetSize($width, $height);
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        ob_start();
        imagewebp($target, null, self::WEBP_QUALITY);
        $blob = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if ($blob === '') throw new RuntimeException('بهینه‌سازی تصویر انجام نشد.');
        return $blob;
    }
}
