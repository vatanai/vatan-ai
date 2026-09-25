<?php

namespace App\Services;

use App\Models\FaceProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * منبع واحد محاسبه و کنترل فضای فایل‌های پروفایل کاربر.
 *
 * آیتم‌های user_gallery عمداً در این سرویس محاسبه نمی‌شوند؛ آن‌ها متعلق به
 * گالری خصوصی/داشبورد هستند و دیسک و چرخهٔ نگهداری مستقل دارند.
 */
class UserStorageService
{
    public const LIMIT_BYTES = 100 * 1024 * 1024;
    public const BLOCK_THRESHOLD_BYTES = 95 * 1024 * 1024;
    public const INPUT_RETENTION_DAYS = 7;
    public const OUTPUT_RETENTION_DAYS = 90;

    public function snapshot(User $user): array
    {
        $inputBytes = Schema::hasTable('user_uploads')
            ? (int) $user->uploadedImages()
                ->get(['file_path', 'size'])
                ->sum(fn ($upload): int => $this->existingFileBytes($upload->file_path, (int) $upload->size))
            : 0;
        $imageBytes = Schema::hasTable('generated_images')
            ? (int) $user->generatedImages()
                ->whereNotNull('image_path')
                ->get(['image_path', 'size'])
                ->sum(fn ($image): int => $this->existingFileBytes($image->image_path, (int) $image->size))
            : 0;
        $videoBytes = Schema::hasTable('generated_videos')
            ? (int) $user->generatedVideos()
                ->whereNotNull('video_path')
                ->get(['video_path', 'size'])
                ->sum(fn ($video): int => $this->existingFileBytes($video->video_path, (int) $video->size))
            : 0;
        $faceBytes = Schema::hasTable('face_profiles')
            ? (int) $user->faceProfiles()->active()->get()->sum(
                fn (FaceProfile $profile): int => collect($profile->referenceImageEntries())
                    ->sum(fn (array $image): int => $this->existingFileBytes($image['path'] ?? null, (int) ($image['size'] ?? 0)))
            )
            : 0;

        return [
            'inputs' => $inputBytes,
            'images' => $imageBytes,
            'videos' => $videoBytes,
            'face_profiles' => $faceBytes,
            'used' => $inputBytes + $imageBytes + $videoBytes + $faceBytes,
            'limit' => self::LIMIT_BYTES,
            'threshold' => self::BLOCK_THRESHOLD_BYTES,
        ];
    }

    /**
     * Snapshot دقیق برای نمایش پروفایل؛ فقط نتیجهٔ آن cache می‌شود.
     * مسیرهای check() عمداً هر بار snapshot تازه می‌خوانند.
     */
    public function profileSnapshot(User $user): array
    {
        return Cache::remember(
            $this->profileSnapshotKey((int) $user->getKey()),
            now()->addSeconds(60),
            fn (): array => $this->snapshot($user),
        );
    }

    public function forgetProfileSnapshot(User $user): void
    {
        $this->forgetProfileSnapshotForUserId((int) $user->getKey());
    }

    public function forgetProfileSnapshotForUserId(int $userId): void
    {
        if ($userId > 0) {
            Cache::forget($this->profileSnapshotKey($userId));
        }
    }

    public function check(User $user, int $incomingBytes = 0, int $estimatedOutputBytes = 0): array
    {
        $snapshot = $this->snapshot($user);
        $projected = max(0, (int) $snapshot['used'])
            + max(0, $incomingBytes)
            + max(0, $estimatedOutputBytes);

        return $snapshot + [
            'incoming' => max(0, $incomingBytes),
            'estimated_output' => max(0, $estimatedOutputBytes),
            'projected' => $projected,
            'allowed' => $projected < self::BLOCK_THRESHOLD_BYTES,
            'percent' => round(($projected / self::LIMIT_BYTES) * 100, 2),
        ];
    }

    public function imageOutputEstimate(int $count = 1): int
    {
        return max(1, $count) * 2 * 1024 * 1024;
    }

    public function blockMessage(array $check): string
    {
        $used = number_format(((int) ($check['projected'] ?? 0)) / 1048576, 1);

        return "فضای ذخیره‌سازی شما به آستانهٔ ۹۵ مگابایت رسیده است ({$used} مگابایت). برای ساخت محصول، ابتدا از بخش محتوای پروفایل فایل‌های قبلی را حذف کنید.";
    }

    public function deletePublicFile(?string $path): void
    {
        if (blank($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }

    /** فقط فایل واقعاً موجود روی دیسک در سهمیه حساب می‌شود؛ رکورد یتیم نباید سهمیه را پر نگه دارد. */
    private function existingFileBytes(?string $path, int $recordedBytes = 0): int
    {
        if (blank($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return 0;
        }

        $path = ltrim((string) $path, '/');
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return 0;
        }

        try {
            return max(0, (int) $disk->size($path));
        } catch (\Throwable) {
            return max(0, $recordedBytes);
        }
    }

    private function profileSnapshotKey(int $userId): string
    {
        return 'profile-storage-snapshot:v2:' . $userId;
    }

}
