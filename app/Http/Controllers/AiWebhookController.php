<?php

namespace App\Http\Controllers;

use App\Services\Providers\FalImageProvider;
use App\Services\Providers\ReplicateImageProvider;
use App\Services\WebhookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
}
