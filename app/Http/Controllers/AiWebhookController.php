<?php

namespace App\Http\Controllers;

use App\Services\Providers\FalImageProvider;
use App\Services\Providers\ReplicateImageProvider;
use App\Services\WebhookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessOpenRouterVideoWebhook;
use App\Services\AiProviderCredentials;

class AiWebhookController extends Controller
{
    public function fal(Request $request, FalImageProvider $provider, WebhookSignatureVerifier $verifier)
    {
        $body = $request->getContent();
        $headers = [
            'x-fal-webhook-request-id' => $request->header('X-Fal-Webhook-Request-Id'),
            'x-fal-webhook-user-id' => $request->header('X-Fal-Webhook-User-Id'),
            'x-fal-webhook-timestamp' => $request->header('X-Fal-Webhook-Timestamp'),
            'x-fal-webhook-signature' => $request->header('X-Fal-Webhook-Signature'),
        ];
        if (!$verifier->verifyFal($body, $headers)) return response()->json(['message' => 'امضای وب‌هوک معتبر نیست.'], 401);

        $normalized = $provider->handleWebhook((array) $request->json()->all());
        Log::info('Fal.ai webhook processed', ['request_id' => $normalized['external_request_id'], 'status' => $normalized['status']]);
        return response()->json(['received' => true]);
    }

    public function replicate(Request $request, ReplicateImageProvider $provider, WebhookSignatureVerifier $verifier)
    {
        $body = $request->getContent();
        $headers = [
            'webhook-id' => $request->header('webhook-id'),
            'webhook-timestamp' => $request->header('webhook-timestamp'),
            'webhook-signature' => $request->header('webhook-signature'),
        ];
        $secret = app(\App\Services\AiProviderCredentials::class)->for('replicate')['webhook_secret'];
        if (!$verifier->verifyReplicate($body, $headers, $secret)) return response()->json(['message' => 'امضای وب‌هوک معتبر نیست.'], 401);

        $normalized = $provider->handleWebhook((array) $request->json()->all());
        Log::info('Replicate webhook processed', ['request_id' => $normalized['external_request_id'], 'status' => $normalized['status']]);
        return response()->json(['received' => true]);
    }

    public function openrouter(Request $request, AiProviderCredentials $credentials)
    {
        $rawBody = $request->getContent();
        $secret = (string) ($credentials->for('openrouter')['webhook_secret'] ?? '');
        if ($secret === '') {
            Log::warning('OpenRouter webhook rejected because its signing secret is not configured.');
            return response()->json(['message' => 'وب‌هوک در دسترس نیست.'], 503);
        }
        if (!$this->verifyOpenRouterSignature($rawBody, (string) $request->header('X-OpenRouter-Signature'), $secret)) {
            return response()->json(['message' => 'امضای وب‌هوک معتبر نیست.'], 401);
        }

        $payload = (array) $request->json()->all();
        $requestId = (string) data_get($payload, 'data.id', data_get($payload, 'id', ''));
        if ($requestId === '') return response()->json(['message' => 'شناسهٔ درخواست موجود نیست.'], 422);
        $deliveryKey = (string) $request->header('X-OpenRouter-Idempotency-Key', $requestId . '-' . data_get($payload, 'data.status', 'unknown'));
        ProcessOpenRouterVideoWebhook::dispatchAfterResponse($payload, $deliveryKey);

        return response()->json(['received' => true], 202);
    }

    private function verifyOpenRouterSignature(string $body, string $header, string $secret): bool
    {
        $parts = collect(explode(',', $header))->mapWithKeys(function (string $part): array {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            return [$key => $value];
        });
        $timestamp = (int) $parts->get('t');
        $signature = (string) $parts->get('v1');
        if ($timestamp < 1 || $signature === '' || abs(now()->timestamp - $timestamp) > 300) return false;

        return hash_equals(hash_hmac('sha256', $timestamp . ',' . $body, $secret), $signature);
    }
}
