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

class PollStudioVideoGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 210;

    public function __construct(public readonly int $generationId)
    {}

    public function backoff(): array
    {
        return [15, 30, 60, 120];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('studio-video-poll-' . $this->generationId))
            ->releaseAfter(10)
            ->expireAfter(240)];
    }

    public function handle(VideoGenerationService $videos): void
    {
        $videos->pollQueued($this->generationId);
    }

    public function failed(Throwable $exception): void
    {
        app(VideoGenerationService::class)->failQueuedPolling($this->generationId, $exception);
    }
}
