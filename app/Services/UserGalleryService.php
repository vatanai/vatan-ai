<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\UserGalleryConfig;
use App\Models\UserGalleryEvent;
use App\Models\UserGalleryItem;
use App\Models\UserGallerySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserGalleryService
{
    private const TABLE_THUMBNAIL_EDGE = 160;
    private const TABLE_THUMBNAIL_QUALITY = 76;

    public function config(): UserGalleryConfig
    {
        return UserGalleryConfig::current();
    }

    public function setting(User $user): UserGallerySetting
    {
        return UserGallerySetting::forUser($user);
    }

    public function syncConsent(User $user, bool $enabled): UserGallerySetting
    {
        $setting = $this->setting($user);
        $setting->forceFill([
            'enabled' => $enabled,
            'consented_at' => $enabled ? ($setting->consented_at ?: now()) : $setting->consented_at,
            'consent_revoked_at' => $enabled ? null : now(),
        ])->save();

        UserGalleryEvent::query()->create([
            'user_id' => $user->id,
            'action' => $enabled ? 'consent_granted' : 'consent_revoked',
            'metadata' => ['source' => 'user_action'],
        ]);

        return $setting->fresh();
    }

    public function isEnabledFor(User $user): bool
    {
        $config = $this->config();

        return $config->enabled && $this->setting($user)->enabled;
    }

    public function capture(
        User $user,
        string $sourceType,
        ?int $sourceId,
        string $sourcePath,
        string $sourceDisk = 'public',
        ?int $size = null,
        ?string $mimeType = null,
        array $metadata = [],
    ): ?UserGalleryItem {
        if (! $this->isEnabledFor($user) || ! $this->sourceExists($sourcePath, $sourceDisk)) {
            return null;
        }

        $sourceContents = $this->sourceContents($sourcePath, $sourceDisk);
        if ($sourceContents === null || $sourceContents === '') {
            return null;
        }

        return $this->storeContents(
            $user,
            $sourceType,
            $sourceId,
            $sourceContents,
            $size ?: strlen($sourceContents),
            $mimeType,
            $metadata,
            $this->extensionFor($mimeType, $sourcePath),
        );
    }

    /** یک نسخهٔ مستقل از خروجی برای گالری داشبورد نگه می‌دارد. */
    public function captureOutput(
        User $user,
        string $sourceType,
        int $sourceId,
        string $sourcePath,
        string $sourceDisk = 'public',
        ?int $size = null,
        ?string $mimeType = null,
        array $metadata = [],
    ): ?UserGalleryItem {
        if (! Schema::hasTable('user_gallery_items') || ! Schema::hasTable('user_gallery_configs')) {
            return null;
        }

        $sourceContents = $this->sourceContentsIfExists($sourcePath, $sourceDisk);
        if ($sourceContents === null || $sourceContents === '') {
            return null;
        }

        $existing = UserGalleryItem::query()
            ->where('user_id', $user->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
        if ($existing) {
            return $existing;
        }

        return $this->storeContents(
            $user,
            $sourceType,
            $sourceId,
            $sourceContents,
            $size ?: strlen($sourceContents),
            $mimeType,
            $metadata,
            $this->extensionFor($mimeType, $sourcePath),
            false,
            90,
            false,
        );
    }

    /** متن خامی که کاربر در صفحهٔ ساخت وارد کرده را مانند سایر ورودی‌ها ذخیره می‌کند. */
    public function captureText(
        User $user,
        string $text,
        ?int $sourceId,
        array $metadata = [],
    ): ?UserGalleryItem {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        return $this->storeContents(
            $user,
            'input_text',
            $sourceId,
            $text,
            strlen($text),
            'text/plain; charset=utf-8',
            array_merge($metadata, ['text' => $text]),
            'txt',
        );
    }

    private function storeContents(
        User $user,
        string $sourceType,
        ?int $sourceId,
        string $sourceContents,
        int $actualSize,
        ?string $mimeType,
        array $metadata,
        string $extension,
        bool $requireConsent = true,
        ?int $retentionDays = null,
        bool $enforceQuota = true,
    ): ?UserGalleryItem {
        if (($requireConsent && ! $this->isEnabledFor($user)) || $sourceContents === '') {
            return null;
        }

        $config = $this->config();
        if ($enforceQuota) {
            if ($user->galleryItems()->count() >= $config->max_items_per_user) {
                return null;
            }

            $maxBytes = max(1, $config->max_storage_mb) * 1024 * 1024;
            if ((int) $user->galleryItems()->sum('size') + $actualSize > $maxBytes) {
                return null;
            }
        }

        $disk = Storage::disk('user_gallery');
        $directory = 'users/' . $user->id . '/' . now()->format('Y/m');
        $baseName = (string) Str::uuid();
        $originalPath = $directory . '/original/' . $baseName . '.' . $extension;
        $previewPath = null;
        $thumbnailPath = null;
        $thumbnailMime = null;

        $disk->put($originalPath, $sourceContents);
        $previewMime = $mimeType;
        if (str_starts_with(strtolower((string) $mimeType), 'image/')) {
            $previewPath = $directory . '/preview/' . $baseName . '.jpg';
            $previewMime = $this->createWatermarkedPreview($sourceContents, $disk, $previewPath) ?: $mimeType;
            if ($previewPath && $disk->exists($previewPath)) {
                $thumbnailExtension = function_exists('imagewebp') ? 'webp' : 'jpg';
                $thumbnailPath = $directory . '/thumbnail/' . $baseName . '.' . $thumbnailExtension;
                $thumbnailMime = $this->createTableThumbnail($disk->get($previewPath), $disk, $thumbnailPath);
                if (! $thumbnailMime) {
                    $thumbnailPath = null;
                }
            }
        }

        $orderId = (int) ($metadata['order_id'] ?? 0) ?: null;
        if (! $orderId && $sourceId && in_array($sourceType, ['upload', 'input_image', 'input_text', 'input_video'], true)) {
            $orderId = Order::query()->whereKey($sourceId)->exists() ? $sourceId : null;
        }
        $buildUuid = $orderId ? Order::query()->whereKey($orderId)->value('build_uuid') : null;

        $item = UserGalleryItem::query()->create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'build_uuid' => $buildUuid,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'original_path' => $originalPath,
            'preview_path' => $previewPath,
            'thumbnail_path' => $thumbnailPath,
            'disk' => 'user_gallery',
            'mime_type' => $mimeType ?: 'application/octet-stream',
            'preview_mime_type' => $previewMime ?: 'image/jpeg',
            'thumbnail_mime_type' => $thumbnailMime,
            'size' => $actualSize,
            'expires_at' => (int) ($retentionDays ?? $config->retention_days) > 0
                ? now()->addDays((int) ($retentionDays ?? $config->retention_days))
                : null,
            'metadata' => $metadata,
        ]);

        UserGalleryEvent::query()->create([
            'user_id' => $user->id,
            'user_gallery_item_id' => $item->id,
            'action' => 'stored',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'metadata' => ['retention_days' => (int) ($retentionDays ?? $config->retention_days)],
        ]);
        app(UserGalleryCostService::class)->recordStorage($item);

        return $item;
    }

    public function deleteItem(UserGalleryItem $item, string $action = 'manual_deleted'): void
    {
        $disk = Storage::disk($item->disk ?: 'user_gallery');
        foreach ([$item->original_path, $item->preview_path, $item->thumbnail_path] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }

        UserGalleryEvent::query()->create([
            'user_id' => $item->user_id,
            'user_gallery_item_id' => $item->id,
            'action' => $action,
            'source_type' => $item->source_type,
            'source_id' => $item->source_id,
        ]);

        $item->delete();
    }

    public function cleanupExpired(): int
    {
        if (! Schema::hasTable('user_gallery_items')) {
            return 0;
        }

        $deleted = 0;
        UserGalleryItem::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$deleted): void {
                foreach ($items as $item) {
                    $this->deleteItem($item, 'auto_expired');
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function response(UserGalleryItem $item, bool $original = false, bool $thumbnail = false)
    {
        $disk = Storage::disk($item->disk ?: 'user_gallery');
        if ($thumbnail) {
            $this->ensureTableThumbnail($item, $disk);
            $item->refresh();
        }

        $path = $original
            ? $item->original_path
            : ($thumbnail ? ($item->thumbnail_path ?: $item->preview_path ?: $item->original_path) : ($item->preview_path ?: $item->original_path));
        abort_unless($path && $disk->exists($path), 404);

        $mime = $original
            ? $item->mime_type
            : ($thumbnail ? ($item->thumbnail_mime_type ?: $item->preview_mime_type) : $item->preview_mime_type);
        $response = response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        if ($original) {
            $response->headers->addCacheControlDirective('no-store');
            $response->setMaxAge(0);
        } else {
            $response->setMaxAge(31536000);
            $response->setImmutable();
        }

        return $response;
    }

    private function ensureTableThumbnail(UserGalleryItem $item, $disk): void
    {
        if ($item->thumbnail_path && $disk->exists($item->thumbnail_path)) {
            return;
        }

        $sourcePath = $item->preview_path ?: $item->original_path;
        if (! $sourcePath || ! $disk->exists($sourcePath) || ! str_starts_with(strtolower((string) $item->mime_type), 'image/')) {
            return;
        }

        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';
        $directory = dirname(dirname($sourcePath)) . '/thumbnail';
        $thumbnailPath = $directory . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.' . $extension;
        $mime = $this->createTableThumbnail($disk->get($sourcePath), $disk, $thumbnailPath);
        if ($mime) {
            $item->forceFill([
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_mime_type' => $mime,
            ])->saveQuietly();
        }
    }

    private function sourceExists(string $path, string $disk): bool
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }

        return Storage::disk($disk)->exists($path);
    }

    private function sourceContentsIfExists(string $path, string $disk): ?string
    {
        if (! $this->sourceExists($path, $disk)) {
            return null;
        }

        return $this->sourceContents($path, $disk);
    }

    private function sourceContents(string $path, string $disk): ?string
    {
        try {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $response = Http::timeout(30)->get($path);

                return $response->successful() ? $response->body() : null;
            }

            return Storage::disk($disk)->get($path);
        } catch (\Throwable $exception) {
            Log::warning('User gallery source could not be read.', [
                'path' => $path,
                'disk' => $disk,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function extensionFor(?string $mimeType, string $sourcePath): string
    {
        return match (strtolower((string) $mimeType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => strtolower(pathinfo(parse_url($sourcePath, PHP_URL_PATH) ?: $sourcePath, PATHINFO_EXTENSION)) ?: 'bin',
        };
    }

    private function createWatermarkedPreview(string $contents, $disk, string $previewPath): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            $disk->put($previewPath, $contents);

            return 'image/jpeg';
        }

        $source = @imagecreatefromstring($contents);
        if (! $source) {
            $disk->put($previewPath, $contents);

            return 'image/jpeg';
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $maxDimension = 960;
        $scale = min(1, $maxDimension / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        $background = imagecolorallocatealpha($canvas, 0, 0, 0, 75);
        $foreground = imagecolorallocatealpha($canvas, 255, 255, 255, 30);
        $x = max(8, $width - 74);
        $y = max(8, $height - 20);
        imagefilledrectangle($canvas, $x - 6, $y - 4, $width - 7, $height - 7, $background);
        imagestring($canvas, 3, $x, $y, 'VATAN', $foreground);

        ob_start();
        imagejpeg($canvas, null, 86);
        $previewContents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        $disk->put($previewPath, $previewContents);

        return 'image/jpeg';
    }

    private function createTableThumbnail(string $contents, $disk, string $thumbnailPath): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if (! $source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, self::TABLE_THUMBNAIL_EDGE / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($canvas, null, self::TABLE_THUMBNAIL_QUALITY);
            $mime = 'image/webp';
        } else {
            imagejpeg($canvas, null, self::TABLE_THUMBNAIL_QUALITY);
            $mime = 'image/jpeg';
        }
        $thumbnail = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        if ($thumbnail === '') {
            return null;
        }

        $disk->put($thumbnailPath, $thumbnail);

        return $disk->exists($thumbnailPath) ? $mime : null;
    }
}
