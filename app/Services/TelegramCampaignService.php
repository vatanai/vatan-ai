<?php

namespace App\Services;

use App\Models\TelegramCampaign;
use App\Models\TelegramCampaignLog;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\DB;

/** آماده‌سازی گیرندگان کمپین بدون ارسال؛ اجرای ارسال عمومی عمداً جدا و خاموش است. */
class TelegramCampaignService
{
    public function recipients(array $definition)
    {
        return TelegramUser::query()
            ->where('is_blocked', false)
            ->when(array_key_exists('linked', $definition), fn ($query) => $definition['linked'] ? $query->whereNotNull('user_id') : $query->whereNull('user_id'))
            ->when(! empty($definition['source']), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('source', $definition['source'])))
            ->when(! empty($definition['product_id']), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('product_id', (int) $definition['product_id'])))
            ->when(! empty($definition['active_days']), fn ($query) => $query->where('last_active_at', '>=', now()->subDays(max(1, (int) $definition['active_days']))))
            ->when(! empty($definition['telegram_ids']), fn ($query) => $query->whereIn('telegram_id', array_map('intval', (array) $definition['telegram_ids'])))
            ->when(! empty($definition['created_from']), fn ($query) => $query->whereDate('created_at', '>=', $definition['created_from']))
            ->when(! empty($definition['created_to']), fn ($query) => $query->whereDate('created_at', '<=', $definition['created_to']))
            ->orderBy('id');
    }

    public function prepare(TelegramCampaign $campaign): int
    {
        return DB::transaction(function () use ($campaign): int {
            $count = 0;
            $this->recipients((array) $campaign->segment_definition)->chunkById(500, function ($users) use ($campaign, &$count): void {
                $rows = $users->map(fn (TelegramUser $user) => [
                    'campaign_id' => $campaign->id,
                    'telegram_user_id' => $user->id,
                    'delivery_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();
                if ($rows) {
                    TelegramCampaignLog::query()->upsert($rows, ['campaign_id', 'telegram_user_id'], ['delivery_status', 'updated_at']);
                    $count += count($rows);
                }
            });
            $campaign->update(['recipient_count' => $count, 'status' => 'draft']);
            return $count;
        });
    }
}
