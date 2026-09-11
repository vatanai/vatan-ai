<?php

namespace App\Http\Controllers;

use App\Models\GrowthContent;
use App\Models\GrowthDataSource;
use App\Models\GrowthRawRecord;
use App\Models\MarketingContent;
use App\Models\MarketingEvent;
use App\Models\MarketingLink;
use App\Services\GrowthDataIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GrowthDataWebhookController extends Controller
{
    public function __construct(private readonly GrowthDataIngestionService $ingestion) {}

    public function receive(Request $request, GrowthDataSource $growthDataSource): JsonResponse
    {
        if (! $growthDataSource->is_active || $growthDataSource->ingestion_method !== 'webhook') {
            return response()->json(['ok' => false, 'message' => 'این منبع برای دریافت وب‌هوک فعال نیست.'], 404);
        }

        $secret = (string) data_get($growthDataSource->config, 'api_key', '');
        if ($secret === '' || ! $this->hasValidSignature($request, $secret)) {
            return response()->json(['ok' => false, 'message' => 'امضای وب‌هوک معتبر نیست.'], 401);
        }

        if (! Schema::hasTable('growth_raw_records') || ! Schema::hasTable('marketing_events')) {
            return response()->json(['ok' => false, 'message' => 'مدل داده هنوز آماده نیست.'], 503);
        }

        $payload = $request->json()->all();
        $records = $this->recordsFromPayload($payload);
        $storedEvents = 0;
        $storedMetrics = 0;
        $ignored = 0;

        foreach ($records as $record) {
            $record = is_array($record) ? $record : [];
            if ($record === []) {
                $ignored++;
                continue;
            }

            if ($this->hasContentMetrics($record) && $this->storeContentMetrics($growthDataSource, $record)) {
                $storedMetrics++;
                continue;
            }

            if ($this->storeEvent($growthDataSource, $record)) {
                $storedEvents++;
            } else {
                $ignored++;
            }
        }

        $growthDataSource->update([
            'last_received_at' => now(),
            'last_synced_at' => now(),
            'health_status' => 'healthy',
            'connection_status' => 'active',
            'last_error' => null,
        ]);

        return response()->json([
            'ok' => true,
            'stored_events' => $storedEvents,
            'stored_metrics' => $storedMetrics,
            'ignored' => $ignored,
        ]);
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $raw = $request->getContent();
        $providedSecret = (string) $request->header('X-Webhook-Secret');
        if ($providedSecret !== '' && hash_equals($secret, $providedSecret)) {
            return true;
        }

        $authorization = (string) $request->header('Authorization');
        if (Str::startsWith($authorization, 'Bearer ') && hash_equals($secret, Str::after($authorization, 'Bearer '))) {
            return true;
        }

        $signature = (string) ($request->header('X-Signature-256') ?: $request->header('X-Hub-Signature-256'));
        return $signature !== '' && hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), $signature);
    }

    private function recordsFromPayload(array $payload): array
    {
        if (Arr::isList($payload)) {
            return $payload;
        }

        foreach (['events', 'data', 'records', 'items'] as $key) {
            if (is_array($payload[$key] ?? null) && Arr::isList($payload[$key])) {
                return $payload[$key];
            }
        }

        return [$payload];
    }

    private function hasContentMetrics(array $record): bool
    {
        return collect(['views', 'impressions', 'reach', 'engagements', 'comments', 'shares', 'likes', 'saves'])
            ->contains(fn (string $key) => array_key_exists($key, $record));
    }

    private function storeContentMetrics(GrowthDataSource $source, array $record): bool
    {
        $externalId = (string) ($record['content_id'] ?? $record['media_id'] ?? $record['external_id'] ?? $record['id'] ?? '');
        $content = $externalId !== ''
            ? GrowthContent::query()->where('external_id', $externalId)->first()
            : null;

        if (! $content) {
            return false;
        }

        $this->ingestion->recordContentMetrics(
            $content,
            $source,
            $this->normalizeMetricAliases($record),
            $record['metric_date'] ?? $record['date'] ?? null,
            null,
            'webhook',
            $externalId ?: null,
        );

        return true;
    }

    private function normalizeMetricAliases(array $record): array
    {
        $normalized = $record;
        $normalized['views'] ??= $record['impressions'] ?? $record['reach'] ?? 0;
        $normalized['engagements'] ??= $record['engagement'] ?? $record['interactions'] ?? 0;
        return $normalized;
    }

    private function storeEvent(GrowthDataSource $source, array $record): bool
    {
        $externalId = (string) ($record['event_id'] ?? $record['message_id'] ?? $record['comment_id'] ?? $record['id'] ?? '');
        $eventType = $this->eventType($record);
        if ($eventType === '') {
            GrowthRawRecord::create([
                'growth_data_source_id' => $source->id,
                'external_id' => $externalId ?: null,
                'record_type' => 'event',
                'payload' => $record,
                'normalization_status' => 'failed',
                'error_message' => 'نوع رویداد از دادهٔ ورودی قابل تشخیص نبود.',
                'received_at' => now(),
            ]);
            return false;
        }

        if ($externalId !== '' && MarketingEvent::query()->where('event_type', $eventType)->where('external_id', $externalId)->exists()) {
            return false;
        }

        $content = $this->marketingContent($record);
        $link = $this->marketingLink($record);
        $occurredAt = $record['occurred_at'] ?? $record['created_at'] ?? $record['timestamp'] ?? null;
        $event = MarketingEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => $eventType,
            'channel' => (string) ($record['channel'] ?? $record['platform'] ?? $source->channel ?? 'instagram'),
            'processing_status' => 'received',
            'marketing_content_id' => $content?->id,
            'marketing_link_id' => $link?->id,
            'external_id' => $externalId ?: null,
            'actor_ref' => (string) ($record['actor_id'] ?? data_get($record, 'from.id') ?? data_get($record, 'user.id') ?? ''),
            'visitor_ref' => (string) ($record['visitor_id'] ?? data_get($record, 'visitor.id') ?? ''),
            'payload' => $record,
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
        ]);

        GrowthRawRecord::create([
            'growth_data_source_id' => $source->id,
            'external_id' => $externalId ?: (string) $event->event_uuid,
            'record_type' => 'event',
            'payload' => $record,
            'normalized_data' => ['event_type' => $eventType],
            'normalization_status' => 'normalized',
            'received_at' => now(),
            'normalized_at' => now(),
        ]);

        return true;
    }

    private function eventType(array $record): string
    {
        $type = Str::lower((string) ($record['event_type'] ?? $record['type'] ?? $record['event'] ?? $record['action'] ?? ''));
        $resolved = match (true) {
            Str::contains($type, ['comment']) => 'comment.received',
            Str::contains($type, ['message', 'dm', 'direct']) => 'dm.received',
            Str::contains($type, ['lead', 'form', 'phone', 'contact']) => 'lead.created',
            Str::contains($type, ['click']) => 'link.clicked',
            Str::contains($type, ['open']) => 'link.opened',
            Str::contains($type, ['purchase', 'order', 'payment']) => 'purchase.completed',
            Str::contains($type, ['generation', 'build']) => 'generation.completed',
            default => '',
        };

        if ($resolved !== '') {
            return $resolved;
        }

        return match (true) {
            array_key_exists('comment_id', $record) => 'comment.received',
            array_key_exists('message_id', $record) || array_key_exists('receiver_id', $record) => 'dm.received',
            array_key_exists('phone', $record) || array_key_exists('mobile', $record) => 'lead.created',
            default => '',
        };
    }

    private function marketingContent(array $record): ?MarketingContent
    {
        $externalId = (string) ($record['content_id'] ?? $record['media_id'] ?? $record['content_external_id'] ?? '');
        return $externalId !== '' ? MarketingContent::query()->where('external_id', $externalId)->first() : null;
    }

    private function marketingLink(array $record): ?MarketingLink
    {
        $code = (string) ($record['link_code'] ?? $record['utm_campaign'] ?? $record['campaign'] ?? '');
        return $code !== '' ? MarketingLink::query()->where('code', $code)->first() : null;
    }
}
