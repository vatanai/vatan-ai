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
            $this->gateway->sendSimple($phone, $body, type: $type . ':' . $eventKey);
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
        try {
            $this->gateway->sendSimple($phone, $this->render($template->body, config('sms_events.samples', [])), type: 'test:' . $template->event_key);
            return true;
        } catch (\Throwable $e) {
            Log::warning('SMS test failed', ['template_id'=>$template->id, 'error'=>$e->getMessage()]);
            return false;
        }
    }

    public function render(string $body, array $data): string
    {
        $values = array_merge(config('sms_events.samples', []), $data);
        return preg_replace_callback('/\{([a-z_]+)\}/i', fn($m) => (string)($values[$m[1]] ?? $m[0]), $body);
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
