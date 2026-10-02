<?php

namespace App\Jobs\SmartInstagram;

use App\Models\MarketingEvent;
use App\Services\SmartInstagram\InboxIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInstagramEvent implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int,int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $eventId)
    {
    }

    public function uniqueId(): string
    {
        return 'smart-instagram-event-'.$this->eventId;
    }

    public function handle(InboxIngestService $ingest): void
    {
        $event = MarketingEvent::query()->find($this->eventId);
        if ($event) {
            $ingest->process($event);
        }
    }
}
