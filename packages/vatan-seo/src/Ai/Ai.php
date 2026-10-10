<?php

namespace Vatan\Seo\Ai;

use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Budget;

/**
 * درگاه واحد همه‌ی تماس‌های هوش مصنوعی موتور.
 * قبل از هر تماس: انتخاب مدل بر اساس نقش و پروفایل، برآورد هزینه، بررسی سقف بودجه.
 * بعد از هر تماس: ثبت مدل، توکن و هزینه‌ی واقعی در seo_ai_calls.
 */
class Ai
{
    public function __construct(private OpenRouterClient $client, private ModelRouter $router) {}

    public function available(Site $site, string $role): bool
    {
        return $this->client->configured() && $this->router->resolve($site, $role) !== null;
    }

    /**
     * @param array{critical?:bool, web?:bool, temperature?:float, max_tokens?:int, json?:bool} $opt
     */
    public function text(Site $site, string $role, string $purpose, string $system, string $user, array $opt = []): string
    {
        return $this->call($site, $role, $purpose, $system, $user, $opt)['content'];
    }

    /** خروجی JSON تضمین‌شده (با یک بار تلاش برای ترمیم) */
    public function json(Site $site, string $role, string $purpose, string $system, string $user, array $opt = []): array
    {
        $opt['json'] = true;
        $system .= "\n\nOutput contract: respond with ONE valid JSON object only. No markdown fences, no commentary.";
        $result = $this->call($site, $role, $purpose, $system, $user, $opt);
        $decoded = $this->decode($result['content']);
        if ($decoded === null) {
            throw new AiException('پاسخ مدل JSON معتبر نبود ('.$purpose.').');
        }
        return $decoded;
    }

    public function decode(string $content): ?array
    {
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content)));
        $decoded = json_decode($content, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/su', $content, $m)) {
            $decoded = json_decode($m[0], true);
        }
        return is_array($decoded) ? $decoded : null;
    }

    protected function call(Site $site, string $role, string $purpose, string $system, string $user, array $opt): array
    {
        if (! $this->client->configured()) {
            throw new AiException('کلید OpenRouter تنظیم نشده است.');
        }
        $web = (bool) ($opt['web'] ?? false);
        if ($web) {
            $limit = Budget::limit($site, 'research_calls_per_month');
            if ($limit <= 0 || Budget::researchCallsThisMonth($site) >= $limit) {
                $web = false; // سهمیه‌ی تحقیق زنده تمام شده؛ بدون وب ادامه بده
            }
        }

        $candidates = $this->router->candidates($site, $role);
        if (! $candidates) {
            throw new AiException("نقش «{$role}» در پروفایل بودجه‌ی فعلی خاموش است.");
        }

        $maxTokens = (int) ($opt['max_tokens'] ?? 2000);
        $tokensIn = (int) ceil((mb_strlen($system) + mb_strlen($user)) / 2.6);
        $lastError = null;

        foreach (array_slice($candidates, 0, 3) as $model) {
            unset($res);
            $price = $this->router->price($model);
            $estimate = ($tokensIn * $price['in'] + $maxTokens * $price['out']) / 1_000_000 + ($web ? 0.02 : 0);
            if (! Budget::canSpend($site, $estimate, (bool) ($opt['critical'] ?? false))) {
                if ($estimate > 0 && $this->router->isFreeGroup($site, $role) === false) {
                    throw new BudgetExceededException(sprintf('سقف بودجه‌ی ماهانه اجازه‌ی این کار را نمی‌دهد (برآورد %s، باقیمانده %s).', '$'.number_format($estimate, 4), '$'.number_format(Budget::remaining($site, (bool) ($opt['critical'] ?? false)), 3)));
                }
                continue;
            }

            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => $opt['temperature'] ?? 0.4,
                'max_tokens' => $maxTokens,
            ];
            if (! empty($opt['json'])) {
                $payload['response_format'] = ['type' => 'json_object'];
            }
            if ($web) {
                $payload['plugins'] = [['id' => 'web', 'max_results' => 5]];
            }

            $started = microtime(true);
            try {
                $res = $this->client->chat($payload, (int) config('seo-engine.ai.timeout', 120));
            } catch (AiException $e) {
                // response_format پشتیبانی نشد → یک بار بدون آن
                if (! empty($opt['json']) && in_array($e->getCode(), [400, 422], true) && str_contains(mb_strtolower($e->getMessage()), 'response_format')) {
                    unset($payload['response_format']);
                    try {
                        $res = $this->client->chat($payload, (int) config('seo-engine.ai.timeout', 120));
                    } catch (AiException $e2) {
                        $e = $e2;
                    }
                }
                if (! isset($res)) {
                    $this->log($site, $role, $purpose, $model, $web, 0, 0, 0, true, false, $e->getMessage(), $started);
                    $lastError = $e;
                    continue; // مدل بعدی
                }
            }

            $cost = $res['cost'] ?? (($res['tokens_in'] * $price['in'] + $res['tokens_out'] * $price['out']) / 1_000_000);
            $this->log($site, $role, $purpose, $res['model'] ?: $model, $web, $res['tokens_in'], $res['tokens_out'], (float) $cost, $res['cost'] === null, true, null, $started);

            return $res + ['cost_usd' => (float) $cost];
        }

        throw $lastError ?? new BudgetExceededException('هیچ مدلی در محدوده‌ی بودجه در دسترس نبود.');
    }

    protected function log(Site $site, string $role, string $purpose, string $model, bool $web, int $in, int $out, float $cost, bool $estimated, bool $ok, ?string $error, float $started): void
    {
        try {
            AiCall::create([
                'site_id' => $site->id,
                'run_id' => RunContext::$runId,
                'role' => $role,
                'purpose' => mb_substr($purpose, 0, 80),
                'model' => mb_substr($model, 0, 120),
                'web_search' => $web,
                'tokens_in' => $in,
                'tokens_out' => $out,
                'cost_usd' => $cost,
                'cost_estimated' => $estimated,
                'ok' => $ok,
                'error' => $error ? mb_substr($error, 0, 1000) : null,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        } catch (\Throwable) {
            // ثبت لاگ نباید کار اصلی را بشکند
        }
    }
}
