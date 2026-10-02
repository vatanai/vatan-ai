<?php

namespace App\Jobs\SmartInstagram;

use App\Models\SmartInstagram\MessageAttachment;
use App\Services\SmartInstagram\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchMessageAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly int $attachmentId)
    {
    }

    public function handle(MediaService $media): void
    {
        $attachment = MessageAttachment::query()->find($this->attachmentId);
        if ($attachment) {
            $media->fetch($attachment);
        }
    }
}
