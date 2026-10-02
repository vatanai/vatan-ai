<?php

namespace App\Console\Commands;

use App\Jobs\SmartInstagram\ProcessInstagramEvent;
use App\Jobs\SmartInstagram\SendOutboundMessage;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\OutboundMessage;
use App\Services\SmartInstagram\EventNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * نگهداری اینستاگرام هوشمند (صف instagram-maintenance پروپوزال ۸.۵):
 * رویدادهای جامانده را دوباره به صف می‌فرستد و ارسال‌های گیرکرده را آزاد می‌کند.
 */
class SmartInstagramMaintenance extends Command
{
    protected $signature = 'smart-instagram:maintenance';

    protected $description = 'بازیابی رویدادها و ارسال‌های جامانده‌ی اینستاگرام هوشمند';

    public function handle(): int
    {
        if (!Schema::hasTable('instagram_outbound_messages')) {
            return self::SUCCESS;
        }

        $events = MarketingEvent::query()
            ->where('channel', 'instagram')
            ->whereIn('event_type', EventNormalizer::SUPPORTED_TYPES)
            ->where('processing_status', 'received')
            ->whereBetween('created_at', [now()->subDays(2), now()->subMinutes(10)])
            ->limit(200)->pluck('id');
        foreach ($events as $id) {
            ProcessInstagramEvent::dispatch($id)->onQueue(config('smart_instagram.queues.ingest', 'default'));
        }

        $stuck = OutboundMessage::query()
            ->where(fn ($q) => $q->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(15))
                ->orWhere(fn ($w) => $w->where('status', 'retrying')->where('next_attempt_at', '<', now()->subMinutes(30))))
            ->limit(100)->get();
        foreach ($stuck as $outbound) {
            $outbound->forceFill(['status' => 'retrying'])->save();
            SendOutboundMessage::dispatch($outbound->id)->onQueue(config('smart_instagram.queues.outbound', 'default'));
        }

        $this->info("events: {$events->count()} · outbound: {$stuck->count()}");

        return self::SUCCESS;
    }
}
