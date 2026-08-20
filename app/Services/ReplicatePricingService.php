<?php

namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * قیمت رسمی مدل‌های Replicate را از صفحه‌ی خود مدل می‌خواند.
 *
 * Replicate در پاسخ prediction مبلغ نهایی را برنمی‌گرداند؛ مبلغ با توجه به
 * جدول قیمت رسمی مدل و ورودی‌هایی مثل resolution محاسبه می‌شود. بنابراین
 * این سرویس همان جدول رسمی را cache می‌کند تا قیمت هر اجرا قبل از ارسال
 * درخواست مشخص و در رکورد اجرا snapshot شود.
 */
class ReplicatePricingService
{
    public function estimate(AiModel $model, array $payload = [], int $count = 1): array
    {
        $modelId = trim($model->externalModelId());
        if ($model->provider !== 'replicate' || !str_contains($modelId, '/')) {
            return ['available' => false, 'source' => 'Replicate pricing page'];
        }

        $tiers = Cache::remember(
            'replicate.pricing.tiers.v2.' . sha1($modelId),
            now()->addHours(6),
            fn () => $this->fetchTiers($modelId)
        );

        if (!is_array($tiers) || $tiers === []) {
            return ['available' => false, 'source' => 'Replicate pricing page'];
        }

        $requested = $this->normalizeResolution(
            data_get($payload, 'resolution')
                ?? data_get($payload, 'input.resolution')
                ?? data_get($payload, 'quality')
                ?? data_get($payload, 'input.quality')
        );
        $tier = $this->selectTier($tiers, $requested);
        $unitPrice = (float) ($tier['unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            return ['available' => false, 'source' => 'Replicate pricing page'];
        }

        return [
            'available' => true,
            'usd' => round($unitPrice * max(1, $count), 6),
            'unit_price' => $unitPrice,
            'unit' => $tier['unit'] ?? 'unit',
            'source' => 'Replicate official model page',
            'model_id' => $modelId,
            'resolution' => $tier['resolution'] ?? null,
        ];
    }

    private function fetchTiers(string $modelId): array
    {
        try {
            $response = Http::accept('text/html')
                ->connectTimeout(3)
                ->timeout(8)
                ->get('https://replicate.com/' . $modelId);

            if ($response->failed()) return [];

            $tiers = [];
            preg_match_all(
                '/<script[^>]*type="application\\/json"[^>]*>(.*?)<\\/script>/is',
                $response->body(),
                $matches
            );

            foreach ($matches[1] ?? [] as $json) {
                $payload = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
                if (!is_array($payload)) continue;
                foreach ($this->findBillingConfigs($payload) as $config) {
                    foreach ((array) ($config['current_tiers'] ?? []) as $tier) {
                        $price = $this->priceFromTier((array) $tier);
                        if ($price !== null) $tiers[] = $price;
                    }
                }
            }

            return collect($tiers)
                ->unique(fn (array $tier) => ($tier['resolution'] ?? 'default') . '|' . $tier['unit_price'])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function findBillingConfigs(array $value): array
    {
        $found = [];
        if (isset($value['billingConfig']) && is_array($value['billingConfig'])) {
            $found[] = $value['billingConfig'];
        }

        foreach ($value as $child) {
            if (is_array($child)) $found = array_merge($found, $this->findBillingConfigs($child));
        }

        return $found;
    }

    private function priceFromTier(array $tier): ?array
    {
        $priceData = (array) data_get($tier, 'prices.0', []);
        $price = $priceData['price'] ?? null;
        if (!is_string($price) && !is_numeric($price)) return null;

        $numeric = (float) preg_replace('/[^0-9.]/', '', (string) $price);
        if ($numeric <= 0) return null;

        // Replicate بعضی مدل‌ها را به‌صورت «۳ دلار برای هزار تصویر» اعلام
        // می‌کند. قیمت ثبت‌شده در هر اجرا باید قیمت یک خروجی باشد، نه مقدار
        // بسته‌ی هزار‌تایی؛ در غیر این صورت هزینه‌ی FLUX Schnell هزار برابر
        // بیشتر از قیمت رسمی نمایش داده می‌شود.
        $billingText = strtolower(implode(' ', array_filter([
            (string) ($priceData['title'] ?? ''),
            (string) ($priceData['description'] ?? ''),
            (string) data_get($tier, 'description', ''),
        ])));
        $divisor = match (true) {
            preg_match('/(?:per|\/)\s*(?:one\s+)?(?:thousand|1[\s,]?000)\b/i', $billingText) === 1 => 1000,
            preg_match('/(?:per|\/)\s*(?:one\s+)?million\b/i', $billingText) === 1 => 1_000_000,
            preg_match('/(?:per|\/)\s*(?:one\s+)?billion\b/i', $billingText) === 1 => 1_000_000_000,
            default => 1,
        };

        $resolution = data_get($tier, 'criteria.0.value');
        return [
            'resolution' => $this->normalizeResolution($resolution) ?: 'default',
            'unit_price' => round($numeric / $divisor, 12),
            'unit' => $priceData['metric_display'] ?? 'unit',
        ];
    }

    private function selectTier(array $tiers, ?string $requested): array
    {
        if ($requested) {
            $match = collect($tiers)->first(fn (array $tier) => ($tier['resolution'] ?? null) === $requested);
            if ($match) return $match;
        }

        return $tiers[0] ?? [];
    }

    private function normalizeResolution(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        return match ($value) {
            '480', '720', '1080', '1K' => '1K',
            '1440', '2K' => '2K',
            '2160', '4K' => '4K',
            'FALLBACK' => 'fallback',
            '' => null,
            default => $value,
        };
    }
}
