<?php

namespace App\Jobs;

use App\Services\Providers\OpenRouterVideoProvider;
use App\Services\VideoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOpenRouterVideoWebhook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 86400;

    public int $tries = 4;

    public function __construct(
        public readonly array $payload,
        public readonly string $deliveryKey,
    ) {}

    public function uniqueId(): string
    {
        return $this->deliveryKey;
    }

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(OpenRouterVideoProvider $provider, VideoGenerationService $videos): void
    {
        $videos->syncFromProvider($provider->handleWebhook($this->payload));
    }
}
