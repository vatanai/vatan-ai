<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Jobs\SmartInstagram\ProcessInstagramEvent;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\MessageAttachment;
use App\Models\SmartInstagram\OperationLog;
use App\Models\SmartInstagram\OutboundMessage;
use App\Services\SmartInstagram\EventNormalizer;
use App\Services\SmartInstagram\OutboundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * سلامت و لاگ‌ها (پروپوزال ۸.۵): وضعیت دریافت رویداد، صف ارسال، رسانه و لاگ عملیات.
 * محتوای حساس پیام در این صفحه نمایش داده نمی‌شود.
 */
class HealthController extends Controller
{
    public const TABS = ['overview' => 'نمای کلی', 'events' => 'رویدادهای ورودی', 'outbound' => 'صف ارسال', 'logs' => 'لاگ عملیات'];

    public function index(Request $request): View
    {
        $this->authorizeAbility('view');
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'overview';
        $level = (string) $request->query('level', '');
        $status = (string) $request->query('status', '');

        $eventQuery = MarketingEvent::query()->where('channel', 'instagram')->whereIn('event_type', EventNormalizer::SUPPORTED_TYPES);
        $since = now()->subDay();

        $data = [
            'tab' => $tab,
            'tabs' => self::TABS,
            'level' => $level,
            'status' => $status,
            'summary' => [
                'events_24h' => (clone $eventQuery)->where('created_at', '>=', $since)->count(),
                'events_failed' => (clone $eventQuery)->where('processing_status', 'failed')->count(),
                'events_waiting' => (clone $eventQuery)->where('processing_status', 'received')->where('created_at', '<', now()->subMinutes(5))->count(),
                'last_event' => (clone $eventQuery)->max('created_at'),
                'outbound' => OutboundMessage::query()->where('workspace_id', $this->ws())->where('created_at', '>=', now()->subDays(7))
                    ->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'media_failed' => MessageAttachment::query()->where('workspace_id', $this->ws())->where('fetch_status', 'failed')->count(),
                'jobs_pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
                'jobs_failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('payload', 'like', '%SmartInstagram%')->count() : null,
                'errors_24h' => OperationLog::query()->where('workspace_id', $this->ws())->where('level', 'error')->where('created_at', '>=', $since)->count(),
            ],
            'queueMode' => config('queue.default'),
        ];

        if ($tab === 'events') {
            $data['events'] = (clone $eventQuery)
                ->when($status !== '', fn ($q) => $q->where('processing_status', $status))
                ->latest('id')->paginate(30, ['id', 'event_type', 'processing_status', 'external_id', 'error_message', 'occurred_at', 'processed_at', 'created_at'])
                ->withQueryString();
        }
        if ($tab === 'outbound') {
            $data['outbound'] = OutboundMessage::query()->where('workspace_id', $this->ws())
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->with(['contact:id,username,display_name', 'admin:id,name'])
                ->latest('id')->paginate(30)->withQueryString();
        }
        if ($tab === 'logs' || $tab === 'overview') {
            $data['logs'] = OperationLog::query()->where('workspace_id', $this->ws())
                ->when($level !== '', fn ($q) => $q->where('level', $level))
                ->with('admin:id,name')->latest('id')
                ->paginate($tab === 'overview' ? 8 : 40)->withQueryString();
        }

        return view('admin.smart-instagram.health', $data);
    }

    public function reprocess(MarketingEvent $event): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        abort_unless($event->channel === 'instagram' && in_array($event->event_type, EventNormalizer::SUPPORTED_TYPES, true), 404);
        $event->forceFill(['processing_status' => 'received', 'error_message' => null])->save();
        ProcessInstagramEvent::dispatch($event->id)->onQueue(config('smart_instagram.queues.ingest', 'default'));

        return back()->with('success', 'رویداد #'.$event->id.' دوباره در صف پردازش قرار گرفت.');
    }

    public function retryOutbound(OutboundMessage $outbound, OutboundService $service): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        abort_unless((int) $outbound->workspace_id === $this->ws(), 404);
        $service->retry($outbound);

        return back()->with('success', 'ارسال دوباره در صف قرار گرفت.');
    }
}
