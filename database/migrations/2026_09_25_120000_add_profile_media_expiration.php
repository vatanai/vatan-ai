<?php

use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generated_images') && ! Schema::hasColumn('generated_images', 'expires_at')) {
            Schema::table('generated_images', function ($table): void {
                $table->timestamp('expires_at')->nullable()->index()->after('size');
            });
        }

        if (Schema::hasTable('generated_videos') && ! Schema::hasColumn('generated_videos', 'expires_at')) {
            Schema::table('generated_videos', function ($table): void {
                $table->timestamp('expires_at')->nullable()->index()->after('size');
            });
        }

        $expiresAt = static function (?string $createdAt): ?Carbon {
            return $createdAt ? Carbon::parse($createdAt)->addDays(90) : null;
        };

        if (Schema::hasTable('generated_images')) {
            GeneratedImage::query()->whereNull('expires_at')->chunkById(200, function ($items) use ($expiresAt): void {
                foreach ($items as $item) {
                    $item->update(['expires_at' => $expiresAt($item->created_at?->toDateTimeString())]);
                }
            });
        }

        if (Schema::hasTable('generated_videos')) {
            GeneratedVideo::query()->whereNull('expires_at')->chunkById(200, function ($items) use ($expiresAt): void {
                foreach ($items as $item) {
                    $item->update(['expires_at' => $expiresAt($item->created_at?->toDateTimeString())]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generated_videos') && Schema::hasColumn('generated_videos', 'expires_at')) {
            Schema::table('generated_videos', function ($table): void {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            });
        }

        if (Schema::hasTable('generated_images') && Schema::hasColumn('generated_images', 'expires_at')) {
            Schema::table('generated_images', function ($table): void {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            });
        }
    }
};
