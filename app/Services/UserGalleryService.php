<?php

namespace App\Services;

use App\Models\User;
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

        $config = $this->config();
        if ($user->galleryItems()->count() >= $config->max_items_per_user) {
            return null;
        }

        $sourceContents = $this->sourceContents($sourcePath, $sourceDisk);
        if ($sourceContents === null || $sourceContents === '') {
            return null;
        }

        $actualSize = $size ?: strlen($sourceContents);
        $maxBytes = max(1, $config->max_storage_mb) * 1024 * 1024;
        if ((int) $user->galleryItems()->sum('size') + $actualSize > $maxBytes) {
            return null;
        }

        $disk = Storage::disk('user_gallery');
        $directory = 'users/' . $user->id . '/' . now()->format('Y/m');
        $extension = $this->extensionFor($mimeType, $sourcePath);
        $baseName = (string) Str::uuid();
        $originalPath = $directory . '/original/' . $baseName . '.' . $extension;
        $previewPath = null;

        $disk->put($originalPath, $sourceContents);
        $previewMime = $mimeType;
        if (str_starts_with(strtolower((string) $mimeType), 'image/')) {
            $previewPath = $directory . '/preview/' . $baseName . '.jpg';
            $previewMime = $this->createWatermarkedPreview($sourceContents, $disk, $previewPath) ?: $mimeType;
        }

        $item = UserGalleryItem::query()->create([
            'user_id' => $user->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'original_path' => $originalPath,
            'preview_path' => $previewPath,
            'disk' => 'user_gallery',
            'mime_type' => $mimeType ?: 'application/octet-stream',
            'preview_mime_type' => $previewMime ?: 'image/jpeg',
            'size' => $actualSize,
            'expires_at' => now()->addDays(max(1, $config->retention_days)),
            'metadata' => $metadata,
        ]);

        UserGalleryEvent::query()->create([
            'user_id' => $user->id,
            'user_gallery_item_id' => $item->id,
            'action' => 'stored',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'metadata' => ['retention_days' => (int) $config->retention_days],
        ]);
        app(UserGalleryCostService::class)->recordStorage($item);

        return $item;
    }

    public function deleteItem(UserGalleryItem $item, string $action = 'manual_deleted'): void
    {
        $disk = Storage::disk($item->disk ?: 'user_gallery');
        foreach ([$item->original_path, $item->preview_path] as $path) {
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

    public function response(UserGalleryItem $item, bool $original = false)
    {
        $path = $original ? $item->original_path : ($item->preview_path ?: $item->original_path);
        $disk = Storage::disk($item->disk ?: 'user_gallery');
        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: ($original ? $item->mime_type : $item->preview_mime_type),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function sourceExists(string $path, string $disk): bool
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }

        return Storage::disk($disk)->exists($path);
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
}
