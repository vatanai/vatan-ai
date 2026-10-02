<?php

namespace App\Providers;

use App\Jobs\SmartInstagram\ProcessInstagramEvent;
use App\Models\MarketingEvent;
use App\Services\SmartInstagram\EventNormalizer;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\ServiceProvider;

/**
 * اینستاگرام هوشمند — مستقل از بقیه‌ی بخش‌ها.
 * هر رویداد اینستاگرامی که در دفتر خام marketing_events ثبت شود (وب‌هوک متا یا ورودی امضاشده)،
 * پس از commit تراکنش به صف نرمال‌سازی می‌رود؛ وب‌هوک هرگز منتظر پردازش نمی‌ماند.
 */
class SmartInstagramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(WorkspaceContext::class);
    }

    public function boot(): void
    {
        MarketingEvent::created(function (MarketingEvent $event): void {
            if ($event->channel !== 'instagram' || !in_array($event->event_type, EventNormalizer::SUPPORTED_TYPES, true)) {
                return;
            }

            ProcessInstagramEvent::dispatch($event->id)
                ->onQueue(config('smart_instagram.queues.ingest', 'default'))
                ->afterCommit();
        });
    }
}
