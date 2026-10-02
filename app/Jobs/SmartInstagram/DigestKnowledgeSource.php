<?php

namespace App\Jobs\SmartInstagram;

use App\Models\SmartInstagram\KnowledgeSource;
use App\Services\SmartInstagram\Ai\SalesAssistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DigestKnowledgeSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly int $sourceId)
    {
    }

    public function handle(SalesAssistant $assistant): void
    {
        $source = KnowledgeSource::query()->find($this->sourceId);
        if ($source) {
            $assistant->digest($source);
        }
    }
}
