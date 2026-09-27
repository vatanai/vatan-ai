<?php

namespace App\Services;

use App\Models\GeneratedImage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * نسخهٔ سبک و cache‌شوندهٔ خروجی تصویر برای گرید پروفایل.
 * فایل اصلی هیچ‌وقت بازنویسی یا حذف نمی‌شود.
 */
class ProfileMediaThumbnailService
{
    private const MAX_EDGE = 720;
    private const WEBP_QUALITY = 78;

    /** ساخت idempotent بندانگشتی برای صف پس‌زمینه یا اولین درخواست تصویر. */
    public function generate(GeneratedImage $image, int $maxEdge = self::MAX_EDGE): ?string
    {
        $sourcePath = trim((string) $image->image_path);
        if ($sourcePath === '' || filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            return null;
        }

        $thumbnailPath = $this->thumbnailPath($image, $maxEdge);
        if ($disk->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        try {
            $encoded = $this->encode($disk->path($sourcePath), $maxEdge);
            if ($encoded === null) {
                return null;
            }

            $disk->put($thumbnailPath, $encoded);

            return $disk->exists($thumbnailPath) ? $thumbnailPath : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** حذف نسخهٔ مشتق‌شده هنگام حذف خروجی اصلی. */
    public function delete(GeneratedImage $image): void
    {
        Storage::disk('public')->delete([
            $this->thumbnailPath($image),
            $this->thumbnailPath($image, 160),
        ]);
    }

    public function serve(GeneratedImage $image, int $maxEdge = self::MAX_EDGE): BinaryFileResponse|RedirectResponse
    {
        $sourcePath = trim((string) $image->image_path);
        if ($sourcePath === '') {
            abort(404);
        }

        if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            return redirect()->away($sourcePath);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            abort(404);
        }

        $thumbnailPath = $this->generate($image, $maxEdge);
        if ($thumbnailPath === null) {
            abort(404);
        }

        $response = response()->file($disk->path($thumbnailPath));
        $response->headers->set('Content-Type', 'image/webp');
        // خروجی متعلق به کاربر است؛ کش مرورگر مجاز است اما کش اشتراکی نباید آن را نگه دارد.
        $response->setPrivate();
        $response->setMaxAge(31536000);
        $response->setImmutable();
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function thumbnailPath(GeneratedImage $image, int $maxEdge = self::MAX_EDGE): string
    {
        $fingerprint = substr(sha1((string) $image->image_path), 0, 16);

        return 'profile-thumbnails/generated-image-' . $image->getKey() . '-' . $fingerprint . ($maxEdge === self::MAX_EDGE ? '' : '-'.$maxEdge) . '.webp';
    }

    private function encode(string $path, int $maxEdge): ?string
    {
        $info = @getimagesize($path);
        if (! $info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        [$width, $height] = [$info[0], $info[1]];
        $mime = (string) ($info['mime'] ?? '');
        $scale = min(1, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        if (extension_loaded('imagick')) {
            $image = new \Imagick($path);
            $image->autoOrient();
            $image->thumbnailImage($maxEdge, $maxEdge, true, true);
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::WEBP_QUALITY);
            $image->stripImage();
            $blob = $image->getImagesBlob();
            $image->clear();

            return $blob !== '' ? $blob : null;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if (! $source) {
            return null;
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagewebp($target, null, self::WEBP_QUALITY);
        $blob = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $blob !== '' ? $blob : null;
    }
}
