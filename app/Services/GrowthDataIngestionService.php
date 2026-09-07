<?php

namespace App\Services;

use App\Models\GrowthContent;
use App\Models\GrowthContentDailyMetric;
use App\Models\GrowthDataSource;
use App\Models\GrowthRawRecord;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class GrowthDataIngestionService
{
    public const METRICS = ['views', 'engagements', 'comments', 'shares', 'likes', 'saves'];

    public function recordContentMetrics(
        GrowthContent $content,
        GrowthDataSource $source,
        array $payload,
        CarbonInterface|string|null $metricDate = null,
        ?int $adminId = null,
        string $entryMode = 'manual',
        ?string $externalId = null,
    ): GrowthContentDailyMetric {
        if (! $source->is_active) {
            throw new RuntimeException('منبع داده غیرفعال است.');
        }
        if ($source->channel && $source->channel !== 'all' && $source->channel !== $content->channel) {
            throw new RuntimeException('کانال منبع داده با کانال محتوا هماهنگ نیست.');
        }

        $raw = GrowthRawRecord::create([
            'growth_data_source_id' => $source->id,
            'external_id' => $externalId,
            'record_type' => 'content_metrics',
            'payload' => $payload,
            'normalization_status' => 'pending',
            'received_at' => now(),
        ]);

        try {
            return DB::transaction(function () use ($content, $source, $payload, $metricDate, $adminId, $entryMode, $raw) {
                $normalized = $this->normalize($source, $payload);
                $date = $metricDate ? Carbon::parse($metricDate)->toDateString() : today()->toDateString();
                $values = collect(self::METRICS)->mapWithKeys(
                    fn (string $metric) => [$metric => max(0, (int) ($normalized[$metric] ?? 0))]
                )->all();

                $daily = GrowthContentDailyMetric::updateOrCreate(
                    [
                        'growth_content_id' => $content->id,
                        'growth_data_source_id' => $source->id,
                        'metric_date' => $date,
                    ],
                    $values + [
                        'growth_raw_record_id' => $raw->id,
                        'entry_mode' => $entryMode,
                        'created_by' => $adminId,
                    ]
                );

                $raw->update([
                    'normalized_data' => $values,
                    'normalization_status' => 'normalized',
                    'normalized_at' => now(),
                ]);
                $source->update([
                    'last_received_at' => now(),
                    'last_synced_at' => now(),
                    'health_status' => 'healthy',
                    'connection_status' => $source->source_type === 'internal' ? 'connected' : 'active',
                    'last_error' => null,
                ]);

                $this->refreshContentMetrics($content);

                return $daily;
            });
        } catch (Throwable $exception) {
            $raw->update(['normalization_status' => 'failed', 'error_message' => $exception->getMessage()]);
            $source->update(['health_status' => 'error', 'last_error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function normalize(GrowthDataSource $source, array $payload): array
    {
        $mappings = $source->mappings()->where('is_active', true)->get();
        $normalized = [];

        foreach ($mappings as $mapping) {
            if (! Arr::has($payload, $mapping->source_field)) {
                continue;
            }
            $normalized[$mapping->growth_metric] = $this->transform(
                Arr::get($payload, $mapping->source_field),
                $mapping->transform
            );
        }

        // منبعهای ساده با نام استاندارد، حتی پیش از تعریف نگاشت اختصاصی قابل استفاده‌اند.
        foreach (self::METRICS as $metric) {
            if (! array_key_exists($metric, $normalized) && array_key_exists($metric, $payload)) {
                $normalized[$metric] = max(0, (int) $payload[$metric]);
            }
        }

        if ($normalized === []) {
            throw new RuntimeException('هیچ فیلد قابل نگاشتی در داده ورودی پیدا نشد.');
        }

        return $normalized;
    }

    private function refreshContentMetrics(GrowthContent $content): void
    {
        $rows = $content->dailyMetrics()->with(['source.mappings', 'rawRecord'])
            ->where('metric_date', '>=', today()->subDays(30))->get();
        if ($rows->isEmpty()) {
            return;
        }

        $resolved = [];
        foreach (self::METRICS as $metric) {
            $metricRows = $rows->filter(function (GrowthContentDailyMetric $row) use ($metric) {
                $normalized = $row->rawRecord?->normalized_data;

                return $normalized === null || array_key_exists($metric, $normalized);
            });
            if ($metricRows->isEmpty()) {
                $resolved[$metric] = (int) ($content->{$metric === 'views' ? 'impressions' : $metric} ?? 0);
                continue;
            }

            $latestDate = $metricRows->max(fn (GrowthContentDailyMetric $row) => $row->metric_date->toDateString());
            $candidate = $metricRows->filter(fn (GrowthContentDailyMetric $row) => $row->metric_date->toDateString() === $latestDate)
                ->sortBy(function (GrowthContentDailyMetric $row) use ($metric) {
                    $mapping = $row->source?->mappings->firstWhere('growth_metric', $metric);
                    return sprintf(
                        '%d-%05d-%05d',
                        $mapping?->is_primary ? 0 : 1,
                        $mapping?->fallback_order ?? 999,
                        $row->source?->priority ?? 999
                    );
                })->first();
            $resolved[$metric] = (int) ($candidate?->{$metric} ?? 0);
        }

        $content->update([
            'impressions' => $resolved['views'],
            'engagements' => $resolved['engagements'],
            'comments' => $resolved['comments'],
            'shares' => $resolved['shares'],
            'likes' => $resolved['likes'],
            'saves' => $resolved['saves'],
            'metrics_updated_at' => now(),
        ]);
    }

    private function transform(mixed $value, string $transform): mixed
    {
        return match ($transform) {
            'integer' => max(0, (int) str_replace([',', ' '], '', (string) $value)),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'lowercase' => mb_strtolower(trim((string) $value)),
            default => is_string($value) ? trim($value) : $value,
        };
    }
}
