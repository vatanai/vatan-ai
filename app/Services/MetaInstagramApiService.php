<?php

namespace App\Services;

use App\Models\MarketingIntegration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MetaInstagramApiService
{
    public function authorizationUrl(string $state, string $redirectUri): string
    {
        return rtrim((string) config('services.meta.auth_url', 'https://www.facebook.com'), '/')
            .'/'.trim((string) config('services.meta.graph_version', 'v24.0'), '/').'/dialog/oauth?'
            .http_build_query([
                'client_id' => (string) config('services.meta.app_id'),
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'response_type' => 'code',
                'scope' => implode(',', (array) config('services.meta.oauth_scopes', [])),
            ]);
    }

    public function completeOAuth(string $code, string $redirectUri): array
    {
        try {
            $tokenResponse = Http::baseUrl($this->facebookGraphBaseUrl())
                ->asForm()
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(20)
                ->get('/oauth/access_token', [
                    'client_id' => (string) config('services.meta.app_id'),
                    'client_secret' => (string) config('services.meta.app_secret'),
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ]);

            if ($tokenResponse->failed()) {
                return ['ok' => false, 'message' => $this->errorMessage($tokenResponse), 'pages' => []];
            }

            $userToken = trim((string) $tokenResponse->json('access_token'));
            if ($userToken === '') {
                return ['ok' => false, 'message' => 'متا توکن کاربر را برنگرداند؛ تنظیمات `OAuth` بررسی شود.', 'pages' => []];
            }

            $pagesResponse = $this->client($userToken, $this->facebookGraphUrl())->get('/me/accounts', [
                'fields' => 'id,name,access_token,tasks,instagram_business_account',
                'limit' => 100,
            ]);

            if ($pagesResponse->failed()) {
                return ['ok' => false, 'message' => $this->errorMessage($pagesResponse), 'pages' => []];
            }

            return [
                'ok' => true,
                'message' => 'حساب متا و پیج‌های متصل دریافت شد.',
                'pages' => (array) $pagesResponse->json('data', []),
            ];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'ارتباط با سرویس `Meta` برای تکمیل اتصال برقرار نشد.', 'pages' => []];
        }
    }

    public function testConnection(MarketingIntegration $integration): array
    {
        $credentials = (array) $integration->credentials;
        $token = trim((string) ($credentials['access_token'] ?? ''));
        $instagramUserId = trim((string) ($credentials['instagram_user_id'] ?? ''));

        if ($token === '' || $instagramUserId === '') {
            return ['ok' => false, 'message' => 'توکن و شناسه‌ی اکانت اینستاگرام کامل نشده است.', 'profile' => null, 'media' => []];
        }

        try {
            $profileResponse = $this->client($token, $this->integrationGraphUrl($integration))->get('/'.$instagramUserId, [
                'fields' => 'id,username,name,account_type,media_count',
            ]);
            if ($profileResponse->failed()) {
                return ['ok' => false, 'message' => $this->errorMessage($profileResponse), 'profile' => null, 'media' => []];
            }

            $mediaResponse = $this->client($token, $this->integrationGraphUrl($integration))->get('/'.$instagramUserId.'/media', [
                'fields' => 'id,caption,media_type,timestamp,permalink',
                'limit' => 5,
            ]);

            return [
                'ok' => true,
                'message' => $mediaResponse->successful() ? 'اتصال برقرار است و اطلاعات پروفایل و رسانه دریافت شد.' : 'پروفایل دریافت شد، اما دریافت رسانه‌ها نیاز به بررسی مجوز دارد.',
                'profile' => $profileResponse->json(),
                'media' => $mediaResponse->successful() ? (array) $mediaResponse->json('data', []) : [],
            ];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'ارتباط با سرویس `Meta` برقرار نشد؛ آدرس، مجوز و وضعیت توکن بررسی شود.', 'profile' => null, 'media' => []];
        }
    }

    public function sendPrivateReply(MarketingIntegration $integration, string $commentId, string $text): array
    {
        return $this->sendMessage($integration, ['recipient' => ['comment_id' => $commentId], 'message' => ['text' => $text]]);
    }

    public function sendDirectMessage(MarketingIntegration $integration, string $recipientId, string $text): array
    {
        return $this->sendMessage($integration, ['recipient' => ['id' => $recipientId], 'message' => ['text' => $text]]);
    }

    private function sendMessage(MarketingIntegration $integration, array $payload): array
    {
        $credentials = (array) $integration->credentials;
        $token = trim((string) ($credentials['access_token'] ?? ''));
        $instagramUserId = trim((string) ($credentials['instagram_user_id'] ?? ''));
        if ($token === '' || $instagramUserId === '') return ['ok' => false, 'message' => 'اتصال `Meta` تنظیم نشده است.'];

        try {
            $response = $this->client($token, $this->integrationGraphUrl($integration))->post('/'.$instagramUserId.'/messages', $payload);
            return $response->successful()
                ? ['ok' => true, 'message' => 'پیام برای ارسال به `Meta` تحویل شد.', 'data' => $response->json()]
                : ['ok' => false, 'message' => $this->errorMessage($response), 'data' => $response->json()];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'ارسال پیام به `Meta` با خطای ارتباطی روبه‌رو شد.'];
        }
    }

    private function client(string $token, ?string $graphUrl = null): PendingRequest
    {
        return Http::baseUrl(rtrim((string) ($graphUrl ?: config('services.meta.graph_url', 'https://graph.instagram.com')), '/').'/'.trim((string) config('services.meta.graph_version', 'v24.0'), '/'))
            ->withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20);
    }

    private function integrationGraphUrl(MarketingIntegration $integration): string
    {
        return (string) data_get($integration->credentials, 'graph_url', config('services.meta.graph_url', 'https://graph.instagram.com'));
    }

    private function facebookGraphUrl(): string
    {
        return (string) config('services.meta.facebook_graph_url', 'https://graph.facebook.com');
    }

    private function errorMessage($response): string
    {
        return (string) ($response->json('error.message') ?: 'سرویس `Meta` درخواست را نپذیرفت؛ مجوزها و اعتبار توکن بررسی شود.');
    }
}
