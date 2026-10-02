<?php

namespace App\Http\Controllers\SmartInstagram;

use App\Http\Controllers\Controller;
use App\Models\MarketingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ورودی امضاشده برای واسط‌های قابل‌تعویض (n8n / Composio) — پروپوزال ۱۱ و ۱۲.
 * امضا: X-Vatan-Signature = "sha256=" . HMAC_SHA256(secret, timestamp . "." . body)
 * زمان: X-Vatan-Timestamp (ثانیه‌ی یونیکس) — خارج از بازه‌ی مجاز رد می‌شود (ضد تکرار).
 * بدنه: یک رویداد یا {"events":[...]} با کلیدهای type,id,sender{id,username,name},text,media_id,parent_id,timestamp,attachments[]
 */
class IngestWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('smart_instagram.ingest.secret');
        if ($secret === '') {
            return response()->json(['ok' => false, 'message' => 'ورودی واسط غیرفعال است.'], 503);
        }

        $timestamp = (int) $request->header('X-Vatan-Timestamp', 0);
        $tolerance = (int) config('smart_instagram.ingest.tolerance_seconds', 300);
        if ($timestamp <= 0 || abs(time() - $timestamp) > $tolerance) {
            return response()->json(['ok' => false, 'message' => 'زمان درخواست معتبر نیست.'], 401);
        }

        $raw = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$raw, $secret);
        if (!hash_equals($expected, (string) $request->header('X-Vatan-Signature'))) {
            return response()->json(['ok' => false, 'message' => 'امضا معتبر نیست.'], 401);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return response()->json(['ok' => false, 'message' => 'بدنه JSON نیست.'], 422);
        }

        $events = isset($payload['events']) ? (array) $payload['events'] : [$payload];
        $source = Str::limit((string) $request->header('X-Vatan-Source', 'n8n'), 30, '');
        $stored = 0;
        $duplicates = 0;

        foreach (array_slice($events, 0, 100) as $event) {
            $event = (array) $event;
            $id = trim((string) ($event['id'] ?? ''));
            if ($id === '' || empty(data_get($event, 'sender.id'))) {
                continue;
            }
            $externalId = Str::limit(($event['type'] ?? 'dm').':'.$id, 180, '');
            if (MarketingEvent::query()->where('event_type', 'instagram.normalized')->where('external_id', $externalId)->exists()) {
                $duplicates++;
                continue;
            }
            MarketingEvent::query()->create([
                'event_uuid' => (string) Str::uuid(),
                'event_type' => 'instagram.normalized',
                'channel' => 'instagram',
                'processing_status' => 'received',
                'external_id' => $externalId,
                'actor_ref' => (string) data_get($event, 'sender.id'),
                'payload' => $event + ['_source' => $source],
                'occurred_at' => now(),
            ]);
            $stored++;
        }

        return response()->json(['ok' => true, 'stored' => $stored, 'duplicates' => $duplicates]);
    }
}
