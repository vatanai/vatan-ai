<?php

namespace App\Jobs;

use App\Services\VideoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SubmitStudioVideoGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 120;

    public function __construct(public readonly int $generationId)
    {}

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('studio-video-submit-' . $this->generationId))
            ->releaseAfter(10)
            ->expireAfter(180)];
    }

    public function handle(VideoGenerationService $videos): void
    {
        $videos->submitQueued($this->generationId);
    }

    public function failed(Throwable $exception): void
    {
        app(VideoGenerationService::class)->failQueuedSubmission($this->generationId, $exception);
    }
}
