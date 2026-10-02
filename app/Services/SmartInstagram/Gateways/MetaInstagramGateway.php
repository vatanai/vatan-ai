<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;
use App\Services\MetaInstagramApiService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** مسیر رسمی Meta Graph API؛ اعتبارنامه از marketing_integrations (رمزنگاری‌شده) خوانده می‌شود. */
class MetaInstagramGateway implements InstagramChannelGateway
{
    /** کدهای خطای متا که با تلاش مجدد ممکن است برطرف شوند (محدودیت نرخ/خطای موقت). */
    private const RETRYABLE_CODES = [1, 2, 4, 17, 32, 341, 613];

    public function __construct(private readonly MetaInstagramApiService $meta)
    {
    }

    public function sendDirectMessage(Channel $channel, string $recipientId, string $text): GatewayResult
    {
        return $this->postMessage($channel, ['recipient' => ['id' => $recipientId], 'message' => ['text' => $text]]);
    }

    public function sendPrivateReply(Channel $channel, string $commentId, string $text): GatewayResult
    {
        return $this->postMessage($channel, ['recipient' => ['comment_id' => $commentId], 'message' => ['text' => $text]]);
    }

    public function replyToComment(Channel $channel, string $commentId, string $text): GatewayResult
    {
        [$token] = $this->credentials($channel);
        if ($token === '') {
            return GatewayResult::failure('اتصال `Meta` برای این کانال کامل نشده است.');
        }

        return $this->wrap(fn () => $this->client($channel, $token)->asForm()->post('/'.rawurlencode($commentId).'/replies', ['message' => $text]));
    }

    public function health(Channel $channel): GatewayResult
    {
        $integration = $channel->integration;
        if (!$integration) {
            return GatewayResult::failure('این کانال به اتصال `Meta` وصل نشده است.');
        }

        $result = $this->meta->testConnection($integration);

        return $result['ok']
            ? GatewayResult::success($result['message'], null, ['profile' => $result['profile'] ?? null, 'media' => $result['media'] ?? []])
            : GatewayResult::failure($result['message']);
    }

    public function downloadMedia(Channel $channel, string $url): GatewayResult
    {
        if (!str_starts_with($url, 'https://')) {
            return GatewayResult::failure('آدرس رسانه معتبر نیست.');
        }

        try {
            $response = Http::connectTimeout(10)->timeout(60)->withOptions(['stream' => false])->get($url);
            if ($response->failed()) {
                return GatewayResult::failure('دریافت رسانه ناموفق بود (کد '.$response->status().').', $response->serverError());
            }

            return GatewayResult::success('رسانه دریافت شد.', null, ['body' => $response->body(), 'mime' => $response->header('Content-Type') ?: null]);
        } catch (\Throwable) {
            return GatewayResult::failure('ارتباط برای دریافت رسانه برقرار نشد.', true);
        }
    }

    private function postMessage(Channel $channel, array $payload): GatewayResult
    {
        [$token, $accountId] = $this->credentials($channel);
        if ($token === '' || $accountId === '') {
            return GatewayResult::failure('اتصال `Meta` برای این کانال کامل نشده است.');
        }

        return $this->wrap(fn () => $this->client($channel, $token)->post('/'.$accountId.'/messages', $payload));
    }

    private function wrap(\Closure $request): GatewayResult
    {
        try {
            /** @var Response $response */
            $response = $request();
        } catch (\Throwable) {
            return GatewayResult::failure('ارتباط با سرویس `Meta` برقرار نشد.', true);
        }

        if ($response->successful()) {
            $id = (string) ($response->json('message_id') ?: $response->json('id') ?: '');

            return GatewayResult::success('پیام به `Meta` تحویل شد.', $id !== '' ? $id : null, ['response' => $response->json()]);
        }

        $code = (int) $response->json('error.code', 0);
        $retryable = $response->serverError() || in_array($code, self::RETRYABLE_CODES, true);

        return GatewayResult::failure(
            (string) ($response->json('error.message') ?: 'سرویس `Meta` درخواست را نپذیرفت.'),
            $retryable,
            ['status' => $response->status(), 'code' => $code, 'subcode' => $response->json('error.error_subcode')]
        );
    }

    /** @return array{0:string,1:string} */
    private function credentials(Channel $channel): array
    {
        $credentials = (array) ($channel->integration?->credentials ?? []);

        return [
            trim((string) ($credentials['access_token'] ?? '')),
            trim((string) ($credentials['instagram_user_id'] ?? $channel->external_account_id ?? '')),
        ];
    }

    private function client(Channel $channel, string $token): PendingRequest
    {
        $graphUrl = (string) data_get($channel->integration?->credentials, 'graph_url', config('services.meta.graph_url', 'https://graph.instagram.com'));

        return Http::baseUrl(rtrim($graphUrl, '/').'/'.trim((string) config('services.meta.graph_version', 'v24.0'), '/'))
            ->withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20);
    }
}
