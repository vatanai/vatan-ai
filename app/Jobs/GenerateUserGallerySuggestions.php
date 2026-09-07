<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserGalleryGrowthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateUserGallerySuggestions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(UserGalleryGrowthService $growth): void
    {
        User::query()
            ->whereHas('gallerySetting', fn ($query) => $query->where('enabled', true))
            ->whereHas('galleryPreference', fn ($query) => $query->where('suggestions_enabled', true))
            ->chunkById(100, function ($users) use ($growth): void {
                foreach ($users as $user) {
                    $growth->generateForUser($user);
                }
            });
    }
}
