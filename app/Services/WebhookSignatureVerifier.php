<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WebhookSignatureVerifier
{
    public function verifyReplicate(string $body, array $headers, ?string $secret): bool
    {
        if (blank($secret)) return false;
        $id = $headers['webhook-id'] ?? null;
        $timestamp = $headers['webhook-timestamp'] ?? null;
        $signatureHeader = $headers['webhook-signature'] ?? null;
        if (!$id || !$timestamp || !$signatureHeader || abs(time() - (int) $timestamp) > 300) return false;

        $secret = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($secret, true);
        if ($key === false) return false;
        $signed = $id . '.' . $timestamp . '.' . $body;
        $expected = base64_encode(hash_hmac('sha256', $signed, $key, true));

        foreach (preg_split('/\s+/', trim($signatureHeader)) as $candidate) {
            [$version, $value] = array_pad(explode(',', $candidate, 2), 2, null);
            if ($version === 'v1' && $value && hash_equals($expected, $value)) return true;
        }
        return false;
    }

    public function verifyFal(string $body, array $headers): bool
    {
        $requestId = $headers['x-fal-webhook-request-id'] ?? null;
        $userId = $headers['x-fal-webhook-user-id'] ?? null;
        $timestamp = $headers['x-fal-webhook-timestamp'] ?? null;
        $signature = $headers['x-fal-webhook-signature'] ?? null;
        if (!$requestId || !$userId || !$timestamp || !$signature || abs(time() - (int) $timestamp) > 300) return false;

        $jwks = Cache::remember('fal.webhook.jwks', now()->addHours(24), function () {
            try {
                return (array) Http::connectTimeout(10)->timeout(15)
                    ->get('https://rest.fal.ai/.well-known/jwks.json')
                    ->json('keys', []);
            } catch (\Throwable) {
                return [];
            }
        });
        $message = implode("\n", [$requestId, $userId, $timestamp, hash('sha256', $body)]);
        $signatureBytes = @hex2bin($signature);
        if ($signatureBytes === false || !function_exists('sodium_crypto_sign_verify_detached')) return false;

        foreach ($jwks as $key) {
            $encoded = strtr((string) ($key['x'] ?? ''), '-_', '+/');
            $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $publicKey = base64_decode($encoded, true);
            if ($publicKey && sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKey)) return true;
        }
        return false;
    }
}
