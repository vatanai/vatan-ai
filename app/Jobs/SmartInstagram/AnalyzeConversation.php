<?php

namespace App\Jobs\SmartInstagram;

use App\Models\SmartInstagram\Conversation;
use App\Services\SmartInstagram\Ai\SalesAssistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** چند پیام پشت‌سرهم از یک مشتری فقط یک تحلیل می‌سازند (unique برای ۶۰ ثانیه). */
class AnalyzeConversation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 90;

    public int $uniqueFor = 60;

    public function __construct(public readonly int $conversationId, public readonly ?int $messageId = null)
    {
    }

    public function uniqueId(): string
    {
        return 'smart-instagram-analyze-'.$this->conversationId;
    }

    public function handle(SalesAssistant $assistant): void
    {
        $conversation = Conversation::query()->find($this->conversationId);
        if ($conversation && !$conversation->ai_paused) {
            $assistant->analyze($conversation, $this->messageId);
        }
    }
}
