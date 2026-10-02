<?php

namespace App\Services\SmartInstagram\Gateways;

use App\Models\SmartInstagram\Channel;
use App\Services\ComposioClient;
use Illuminate\Support\Facades\Http;

/** اتصال اینستاگرام از مسیر ابزارهای رسمی حساب متصل در Composio. */
class ComposioInstagramGateway implements InstagramChannelGateway
{
    public function __construct(private readonly ComposioClient $client)
    {
    }

    public function sendDirectMessage(Channel $channel, string $recipientId, string $text): GatewayResult
    {
        $result = $this->execute($channel, 'INSTAGRAM_SEND_TEXT_MESSAGE', [
            'recipient_id' => $recipientId,
            'text' => $text,
        ]);

        return $this->toGatewayResult($result, 'پیام دایرکت از مسیر `Composio` ارسال شد.');
    }

    public function sendPrivateReply(Channel $channel, string $commentId, string $text): GatewayResult
    {
        $version = trim((string) config('services.composio.graph_api_version', 'v24.0'), '/');
        $result = $this->client->proxy(
            '/'.$version.'/'.rawurlencode($commentId).'/private_replies',
            'POST',
            ['message' => $text],
            [],
            $this->setting($channel, 'composio_connected_account_id', config('services.composio.connected_account_id')),
        );

        return $result['ok']
            ? GatewayResult::success('پیام خصوصی به صاحب کامنت از مسیر `Composio` ارسال شد.', $result['external_id'], $result['data'])
            : GatewayResult::failure($result['message'], $result['retryable'], ['status' => $result['status']]);
    }

    public function sendPrivateCard(Channel $channel, string $commentId, array $payload): GatewayResult
    {
        $version = trim((string) config('services.composio.graph_api_version', 'v24.0'), '/');
        $result = $this->client->proxy(
            '/'.$version.'/'.rawurlencode((string) $this->setting($channel, 'composio_instagram_user_id', config('services.composio.instagram_user_id', 'me'))).'/messages',
            'POST',
            ['recipient' => ['comment_id' => $commentId], 'message' => ['attachment' => (array) ($payload['attachment'] ?? [])]],
            [],
            $this->setting($channel, 'composio_connected_account_id', config('services.composio.connected_account_id')),
        );
        if ($result['ok'] || empty($payload['fallback_text'])) {
            return $result['ok']
                ? GatewayResult::success('کارت محصول از مسیر `Composio` ارسال شد.', $result['external_id'], $result['data'])
                : GatewayResult::failure($result['message'], $result['retryable'], ['status' => $result['status']]);
        }

        $fallback = $this->client->proxy(
            '/'.$version.'/'.rawurlencode($commentId).'/private_replies',
            'POST',
            ['message' => (string) $payload['fallback_text']],
            [],
            $this->setting($channel, 'composio_connected_account_id', config('services.composio.connected_account_id')),
        );

        return $fallback['ok']
            ? GatewayResult::success('کارت در این حساب قابل ارسال نبود؛ لینک محصول به‌صورت متنی ارسال شد.', $fallback['external_id'], ['fallback' => true])
            : GatewayResult::failure($result['message'], $result['retryable'], ['status' => $result['status']]);
    }

    public function replyToComment(Channel $channel, string $commentId, string $text): GatewayResult
    {
        $result = $this->execute($channel, 'INSTAGRAM_POST_IG_COMMENT_REPLIES', [
            'ig_comment_id' => $commentId,
            'message' => $text,
        ]);

        return $this->toGatewayResult($result, 'پاسخ کامنت از مسیر `Composio` ثبت شد.');
    }

    public function health(Channel $channel): GatewayResult
    {
        $result = $this->execute($channel, 'INSTAGRAM_GET_IG_USER_MEDIA', [
            'ig_user_id' => $this->setting($channel, 'composio_instagram_user_id', config('services.composio.instagram_user_id', 'me')),
            'limit' => 1,
            'fields' => 'id,username,timestamp',
        ]);

        if (!$result['ok']) {
            return $this->toGatewayResult($result, 'تست سلامت `Composio` ناموفق بود.');
        }

        $media = (array) ($result['data']['data'] ?? $result['data']);
        $profile = [];
        if (isset($media[0]) && is_array($media[0])) {
            $profile = array_filter(['username' => $media[0]['username'] ?? null]);
        }

        return GatewayResult::success('اتصال `Composio` و حساب اینستاگرام فعال است.', null, ['profile' => $profile, 'media' => $media]);
    }

    public function downloadMedia(Channel $channel, string $url): GatewayResult
    {
        if (!str_starts_with($url, 'https://')) {
            return GatewayResult::failure('آدرس رسانه معتبر نیست.');
        }

        try {
            $response = Http::connectTimeout(10)->timeout(60)->get($url);
            if ($response->failed()) {
                return GatewayResult::failure('دریافت رسانه ناموفق بود (کد '.$response->status().').', $response->serverError());
            }

            return GatewayResult::success('رسانه دریافت شد.', null, ['body' => $response->body(), 'mime' => $response->header('Content-Type') ?: null]);
        } catch (\Throwable) {
            return GatewayResult::failure('ارتباط برای دریافت رسانه برقرار نشد.', true);
        }
    }

    /** @return array{ok:bool,message:string,data:array,external_id:?string,retryable:bool,status:?int} */
    private function execute(Channel $channel, string $tool, array $arguments): array
    {
        $settings = (array) $channel->settings;

        return $this->client->execute(
            $tool,
            $arguments,
            $this->setting($channel, 'composio_connected_account_id', config('services.composio.connected_account_id')),
            $this->setting($channel, 'composio_user_id', config('services.composio.user_id')),
        );
    }

    private function setting(Channel $channel, string $key, mixed $fallback): mixed
    {
        return data_get((array) $channel->settings, $key, $fallback);
    }

    private function toGatewayResult(array $result, string $successMessage): GatewayResult
    {
        return $result['ok']
            ? GatewayResult::success($successMessage, $result['external_id'], $result['data'])
            : GatewayResult::failure($result['message'], $result['retryable'], ['status' => $result['status']]);
    }
}
