<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Facades\Http;
use Vatan\Content\Models\AiUsage;

/**
 * لایه‌ی هوش کارخانه: Claude از OpenRouter.
 * اگر پکیج سئو نصب باشد از کلاینت آن (با پل کلادفلر برای سرور ایران) استفاده می‌شود.
 * هر خطا null برمی‌گرداند تا جریان تولید هرگز به‌خاطر نبودن هوش متوقف نشود (fallback به متن آماده‌ی تقویم).
 */
class Ai
{
    public ?string $lastError = null;

    public function model(string $role): string
    {
        return (string) config("content-factory.ai.roles.$role", config('content-factory.ai.roles.writer'));
    }

    /** @param array<int,string> $images data-URL یا URL تصویر */
    public function text(string $role, string $purpose, string $system, string $user, array $images = [], array $opt = []): ?array
    {
        $this->lastError = null;
        $content = $images ? array_merge([['type' => 'text', 'text' => $user]], array_map(fn ($u) => ['type' => 'image_url', 'image_url' => ['url' => $u]], $images)) : $user;
        $payload = [
            'model' => $opt['model'] ?? $this->model($role),
            'messages' => [
                ['role' => 'system', 'content' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]]],
                ['role' => 'user', 'content' => $content],
            ],
            'max_tokens' => $opt['max_tokens'] ?? 2000,
            'temperature' => $opt['temperature'] ?? 0.8,
        ];
        try {
            $res = $this->send($payload, (int) ($opt['timeout'] ?? 90));
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
        }
        AiUsage::create([
            'workspace_id' => (int) config('content-factory.workspace_id', 1), 'channel' => 'openrouter', 'model' => $res['model'] ?? $payload['model'],
            'purpose' => $purpose, 'input_tokens' => $res['tokens_in'] ?? 0, 'output_tokens' => $res['tokens_out'] ?? 0, 'cost_usd' => $res['cost'] ?? 0,
        ]);

        return $res;
    }

    public function json(string $role, string $purpose, string $system, string $user, array $images = [], array $opt = []): ?array
    {
        $r = $this->text($role, $purpose, $system."\nفقط JSON معتبر برگردان، بدون توضیح و بدون Markdown.", $user, $images, $opt + ['temperature' => 0.4]);
        if (! $r) {
            return null;
        }
        $c = trim(preg_replace('/^```(json)?|```$/m', '', $r['content']));
        $s = strpos($c, '{'); $e = strrpos($c, '}');
        $d = $s !== false && $e !== false ? json_decode(substr($c, $s, $e - $s + 1), true) : null;
        if (! is_array($d)) {
            $this->lastError = 'پاسخ JSON نامعتبر بود';
        }

        return is_array($d) ? $d : null;
    }

    private function send(array $payload, int $timeout): array
    {
        if (class_exists(\Vatan\Seo\Ai\OpenRouterClient::class)) {
            $c = app(\Vatan\Seo\Ai\OpenRouterClient::class);
            if ($c->configured()) {
                return $c->chat($payload, $timeout);
            }
        }
        $key = (string) config('services.openrouter.api_key');
        throw_if($key === '', new \RuntimeException('کلید OpenRouter تنظیم نشده است.'));
        $base = rtrim((string) (config('services.openrouter.base_url') ?: 'https://openrouter.ai/api/v1'), '/');
        $r = Http::withToken($key)->timeout($timeout)->post($base.'/chat/completions', $payload + ['usage' => ['include' => true]]);
        throw_unless($r->successful(), new \RuntimeException('OpenRouter HTTP '.$r->status()));

        return ['content' => trim((string) data_get($r->json(), 'choices.0.message.content', '')), 'model' => data_get($r->json(), 'model'),
            'tokens_in' => (int) data_get($r->json(), 'usage.prompt_tokens', 0), 'tokens_out' => (int) data_get($r->json(), 'usage.completion_tokens', 0),
            'cost' => (float) data_get($r->json(), 'usage.cost', 0)];
    }

    public function available(): bool
    {
        if (class_exists(\Vatan\Seo\Ai\OpenRouterClient::class)) {
            return app(\Vatan\Seo\Ai\OpenRouterClient::class)->configured();
        }

        return filled(config('services.openrouter.api_key'));
    }
}
