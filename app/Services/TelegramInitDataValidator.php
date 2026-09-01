<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** اعتبارسنجی server-side داده‌ی initData که تلگرام به Mini App می‌دهد. */
class TelegramInitDataValidator
{
    public function validate(string $initData): array
    {
        $token = trim((string) config('services.telegram.bot_token'));
        if ($token === '') {
            throw ValidationException::withMessages(['init_data' => 'توکن بات تلگرام هنوز تنظیم نشده است.']);
        }

        parse_str($initData, $data);
        $receivedHash = (string) ($data['hash'] ?? '');
        if ($receivedHash === '') {
            throw ValidationException::withMessages(['init_data' => 'داده‌ی ورود تلگرام ناقص است.']);
        }

        unset($data['hash']);
        ksort($data);
        $checkString = collect($data)
            ->map(fn ($value, $key) => $key . '=' . $value)
            ->implode("\n");
        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($calculatedHash, $receivedHash)) {
            throw ValidationException::withMessages(['init_data' => 'اعتبار داده‌ی ورود تلگرام تأیید نشد.']);
        }

        $authDate = (int) ($data['auth_date'] ?? 0);
        $maxAge = max(60, (int) config('services.telegram.init_data_max_age', 86400));
        if ($authDate < 1 || abs(now()->timestamp - $authDate) > $maxAge) {
            throw ValidationException::withMessages(['init_data' => 'داده‌ی ورود تلگرام منقضی شده است.']);
        }

        $user = json_decode((string) ($data['user'] ?? '{}'), true);
        if (! is_array($user) || ! isset($user['id'])) {
            throw ValidationException::withMessages(['init_data' => 'اطلاعات کاربر تلگرام در داده‌ی ورود وجود ندارد.']);
        }

        return [
            'auth_date' => $authDate,
            'query_id' => $data['query_id'] ?? null,
            'user' => Arr::only($user, ['id', 'is_bot', 'first_name', 'last_name', 'username', 'language_code', 'is_premium']),
            'raw' => $data,
        ];
    }
}
