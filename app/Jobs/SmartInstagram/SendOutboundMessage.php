<?php

namespace App\Jobs\SmartInstagram;

use App\Models\SmartInstagram\OutboundMessage;
use App\Services\SmartInstagram\OutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/** retry در خود سرویس با backoff و سقف تلاش مدیریت می‌شود؛ جاب فقط یک‌بار اجرا می‌شود. */
class SendOutboundMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $outboundId)
    {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('smart-instagram-outbound-'.$this->outboundId))->dontRelease()];
    }

    public function handle(OutboundService $outbound): void
    {
        $message = OutboundMessage::query()->find($this->outboundId);
        if ($message) {
            $outbound->deliver($message);
        }
    }
}
