<?php

namespace Tests\Unit;

use App\Services\TelegramInitDataValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TelegramInitDataValidatorTest extends TestCase
{
    public function test_valid_telegram_init_data_is_accepted(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        $data = [
            'auth_date' => (string) now()->timestamp,
            'query_id' => 'AA-test',
            'user' => json_encode(['id' => 123456, 'first_name' => 'آزمایشی'], JSON_UNESCAPED_UNICODE),
        ];
        ksort($data);
        $checkString = collect($data)->map(fn ($value, $key) => $key . '=' . $value)->implode("\n");
        $secret = hash_hmac('sha256', 'test-bot-token', 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $checkString, $secret);

        $result = app(TelegramInitDataValidator::class)->validate(http_build_query($data));

        $this->assertSame(123456, $result['user']['id']);
        $this->assertSame('آزمایشی', $result['user']['first_name']);
    }

    public function test_tampered_telegram_init_data_is_rejected(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');

        $this->expectException(ValidationException::class);
        app(TelegramInitDataValidator::class)->validate('auth_date=' . now()->timestamp . '&user=%7B%22id%22%3A123%7D&hash=invalid');
    }
}
