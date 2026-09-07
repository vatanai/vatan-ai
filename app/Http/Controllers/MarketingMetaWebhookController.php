<?php

namespace App\Http\Controllers;

use App\Models\MarketingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketingMetaWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = (string) $request->input('hub.mode', $request->input('hub_mode'));
        $verifyToken = (string) $request->input('hub.verify_token', $request->input('hub_verify_token'));
        $challenge = (string) $request->input('hub.challenge', $request->input('hub_challenge'));
        $expectedToken = (string) config('services.meta.webhook_verify_token');
        $valid = $mode === 'subscribe'
            && $expectedToken !== ''
            && hash_equals($expectedToken, $verifyToken)
            && $challenge !== '';

        return $valid ? response($challenge) : response('Forbidden', 403);
    }

    public function receive(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $secret = (string) config('services.meta.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        if ($secret === '' || !hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), $signature)) {
            return response()->json(['ok' => false, 'message' => 'امضای وب‌هوک معتبر نیست.'], 401);
        }
        if (!Schema::hasTable('marketing_events')) return response()->json(['ok' => false, 'message' => 'مدل رویداد هنوز آماده نیست.'], 503);

        $payload = $request->json()->all();
        $stored = 0;
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $field = (string) ($change['field'] ?? '');
                $externalId = (string) ($value['id'] ?? $value['comment_id'] ?? $value['message_id'] ?? '');
                $eventType = match ($field) {
                    'comments' => 'comment.received',
                    'messages' => 'dm.received',
                    default => 'meta.'.$field,
                };
                if ($externalId !== '' && MarketingEvent::query()->where('event_type', $eventType)->where('external_id', $externalId)->exists()) continue;
                MarketingEvent::query()->create([
                    'event_uuid' => (string) Str::uuid(),
                    'event_type' => $eventType,
                    'channel' => 'instagram',
                    'processing_status' => 'received',
                    'external_id' => $externalId !== '' ? $externalId : null,
                    'actor_ref' => $value['from']['id'] ?? null,
                    'payload' => $value,
                    'occurred_at' => now(),
                ]);
                $stored++;
            }
        }

        return response()->json(['ok' => true, 'stored' => $stored]);
    }
}
