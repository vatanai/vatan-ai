<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\SmartInstagram\AiRun;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Message;
use App\Services\OpenRouterService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Str;

/**
 * پاسخ عمومی شخصی‌سازی‌شده به هر کامنت (نام مخاطب + لحن سبک‌های تعریف‌شده).
 * در صورت خطا یا خاموش‌بودن AI، null برمی‌گرداند و موتور یکی از سه سبک را به‌صورت چرخشی می‌فرستد.
 */
class CommentReplyWriter
{
    public function __construct(
        private readonly PostAiSettings $settings,
        private readonly WorkspaceContext $context,
    ) {
    }

    /** @param array<int,string> $styles */
    public function write(Message $comment, ?Contact $contact, array $styles): ?string
    {
        if (!config('smart_instagram.ai.enabled')) {
            return null;
        }
        $config = $this->settings->get();
        $system = $config['prompts']['personalize']."\n\nنمونه‌ی سبک‌ها (فقط برای لحن؛ کپی نکن):\n- ".implode("\n- ", array_slice($styles, 0, 3))
            ."\n\nفقط JSON: {\"reply\":\"...\"}";
        $user = "نام کاربری: ".($contact?->username ?: '—')."\nنام نمایشی: ".($contact?->display_name ?: '—')."\nمتن کامنت: ".Str::limit((string) $comment->body, 300);

        $started = microtime(true);
        try {
            $response = app(OpenRouterService::class)->generateStructuredText($system, $user, $config['model'] ?: null, 15);
        } catch (\Throwable $e) {
            $this->log($comment, 'failed', null, $e->getMessage(), $started);

            return null;
        }
        $reply = Str::limit(trim((string) data_get($response, 'content.reply', '')), 300, '');
        $this->log($comment, $reply !== '' ? 'success' : 'failed', $response, $reply === '' ? 'پاسخ خالی' : null, $started, $reply);

        return $reply !== '' && !str_contains($reply, '{') ? $reply : null;
    }

    private function log(Message $comment, string $status, ?array $response, ?string $error, float $started, ?string $reply = null): void
    {
        $usage = (array) ($response['usage'] ?? []);
        AiRun::query()->create([
            'workspace_id' => $this->context->id(),
            'conversation_id' => $comment->conversation_id,
            'message_id' => $comment->id,
            'purpose' => 'comment_reply',
            'model' => $response['model'] ?? null,
            'status' => $status,
            'output' => $reply ? ['reply' => $reply] : null,
            'prompt_tokens' => $usage['prompt_tokens'] ?? null,
            'completion_tokens' => $usage['completion_tokens'] ?? null,
            'cost_usd' => isset($usage['cost']) && is_numeric($usage['cost']) ? (float) $usage['cost'] : null,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'error' => $error,
        ]);
    }
}
