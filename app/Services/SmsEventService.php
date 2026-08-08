<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsEventService
{
    public function __construct(private MeliPayamakService $gateway) {}

    public function send(string $eventKey, string $phone, array $data = [], ?SmsTemplate $template = null, string $type = 'automatic'): bool
    {
        if (!preg_match('/^09\d{9}$/', $phone)) return false;
        $template ??= SmsTemplate::where('event_key', $eventKey)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('id')->first();
        if (!$template || !$template->is_active) return false;

        try {
            $body = $this->render($template->body, $data);
            if (! $this->dispatch($template, $phone, $body, $data, $type . ':' . $eventKey)) {
                return false;
            }
            $template->increment('sent_count');
            $template->forceFill(['last_sent_at' => now()])->save();
            return true;
        } catch (\Throwable $e) {
            Log::warning('SMS event failed', ['event'=>$eventKey, 'template_id'=>$template->id, 'error'=>$e->getMessage()]);
            return false;
        }
    }

    public function test(SmsTemplate $template, string $phone): bool
    {
        return $this->testWithResult($template, $phone)['success'];
    }

    public function testWithResult(SmsTemplate $template, string $phone, string $prefix = 'test'): array
    {
        try {
            $samples = config('sms_events.samples', []);
            if (! $this->dispatch($template, $phone, $this->render($template->body, $samples), $samples, $prefix . ':' . $template->event_key)) {
                return ['success' => false, 'error' => 'شناسه قالب پیامک با رویداد انتخاب‌شده سازگار نیست.'];
            }
            $template->increment('sent_count');
            $template->forceFill(['last_sent_at' => now()])->save();
            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('SMS test failed', ['template_id'=>$template->id, 'error'=>$e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function render(string $body, array $data): string
    {
        $values = array_merge(config('sms_events.samples', []), $data);
        return preg_replace_callback('/\{([a-z_]+)\}/i', fn($m) => (string)($values[$m[1]] ?? $m[0]), $body);
    }

    private function dispatch(SmsTemplate $template, string $phone, string $body, array $data, string $type): bool
    {
        if ($template->provider_method === 'shared') {
            $allowedTemplateIds = (array) config("sms_events.shared_template_ids.{$template->event_key}", []);
            $providerTemplateId = trim((string) $template->provider_template_id);
            if ($allowedTemplateIds !== [] && !in_array($providerTemplateId, array_map('strval', $allowedTemplateIds), true)) {
                Log::error('SMS shared template mismatch blocked', [
                    'event' => $template->event_key,
                    'template_id' => $template->id,
                    'provider_template_id' => $providerTemplateId,
                    'allowed_template_ids' => $allowedTemplateIds,
                    'type' => $type,
                ]);
                return false;
            }

            $variables = $template->provider_variables ?: config("sms_events.events.{$template->event_key}.variables", []);
            $values = collect($variables)->map(fn ($variable) => $data[$variable] ?? config("sms_events.samples.{$variable}", ''))->all();
            $this->gateway->sendShared($phone, $values, (string) $template->provider_template_id, $body, $type);
            return true;
        }
        $this->gateway->sendSimple($phone, $body, type: $type);
        return true;
    }

    public function notifyLowCredit($user): void
    {
        $threshold = (int) SmsSetting::valueOf('credit_low_threshold', '100');
        if ($user?->phone && (int)$user->tokens <= $threshold) {
            $key = 'sms-credit-low:' . $user->id;
            if (!Cache::has($key) && $this->send('credit_low', $user->phone, ['name'=>$user->name, 'phone'=>$user->phone, 'balance'=>(string)$user->tokens, 'threshold'=>(string)$threshold])) {
                Cache::put($key, true, now()->addDay());
            }
        } elseif ($user?->id) {
            Cache::forget('sms-credit-low:' . $user->id);
        }
    }
}
