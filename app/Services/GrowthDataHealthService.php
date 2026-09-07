<?php

namespace App\Services;

use App\Models\GrowthContent;
use App\Models\GrowthContentDailyMetric;
use App\Models\GrowthDataSource;
use App\Models\GrowthEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrowthDataHealthService
{
    public function report(): array
    {
        if (! Schema::hasTable('growth_data_sources')) {
            return ['sources' => collect(), 'healthy' => 0, 'warning' => 0, 'error' => 0, 'inactive' => 0];
        }

        $sources = GrowthDataSource::withCount(['mappings', 'rawRecords'])->orderBy('priority')->orderBy('name')->get()
            ->map(fn (GrowthDataSource $source) => $this->inspect($source));

        return [
            'sources' => $sources,
            'healthy' => $sources->where('health', 'healthy')->count(),
            'warning' => $sources->whereIn('health', ['warning', 'idle'])->count(),
            'error' => $sources->where('health', 'error')->count(),
            'inactive' => $sources->where('health', 'inactive')->count(),
        ];
    }

    private function inspect(GrowthDataSource $source): array
    {
        $lastActivity = $this->lastActivity($source);
        $health = 'healthy';
        $message = 'اتصال سالم است';

        if (! $source->is_active) {
            $health = 'inactive';
            $message = 'منبع غیرفعال است';
        } elseif ($source->last_error || $source->connection_status === 'error') {
            $health = 'error';
            $message = $source->last_error ?: 'خطای اتصال ثبت شده است';
        } elseif ($source->ingestion_method === 'manual') {
            [$health, $message] = $this->manualHealth($source, $lastActivity);
        } elseif ($source->slug === 'growth-link-tracker') {
            $activeLinks = Schema::hasTable('growth_links') ? DB::table('growth_links')->where('is_active', true)->count() : 0;
            if ($activeLinks > 0 && ! $lastActivity) {
                $health = 'warning';
                $message = 'لینک فعال است اما هنوز داده‌ای دریافت نشده';
            } elseif ($lastActivity && $lastActivity->lt(now()->subHours(48)) && $activeLinks > 0) {
                $health = 'warning';
                $message = 'بیش از ۴۸ ساعت داده جدید دریافت نشده';
            }
        } elseif (in_array($source->ingestion_method, ['api', 'webhook'], true) && ! $lastActivity) {
            $health = 'idle';
            $message = 'اتصال ساخته شده اما هنوز داده‌ای دریافت نشده';
        } elseif ($lastActivity && $lastActivity->lt(now()->subHours(24)) && ! $source->is_system) {
            $health = 'warning';
            $message = 'آخرین دریافت بیش از ۲۴ ساعت قبل بوده';
        }

        return [
            'source' => $source,
            'health' => $health,
            'message' => $message,
            'last_activity' => $lastActivity,
            'last_activity_human' => $lastActivity ? $lastActivity->diffForHumans() : 'هنوز دریافت نشده',
        ];
    }

    private function manualHealth(GrowthDataSource $source, ?Carbon $lastActivity): array
    {
        $activeContents = Schema::hasTable('growth_contents')
            ? GrowthContent::when($source->channel !== 'all', fn ($query) => $query->where('channel', $source->channel))
                ->where('status', 'active')->count()
            : 0;
        if ($activeContents === 0) {
            return ['healthy', 'محتوای فعالی برای ورود آمار ندارد'];
        }

        $todayCount = Schema::hasTable('growth_content_daily_metrics')
            ? GrowthContentDailyMetric::where('growth_data_source_id', $source->id)->whereDate('metric_date', today())->count()
            : 0;

        if ($todayCount > 0) {
            return ['healthy', "آمار امروز برای {$todayCount} محتوا ثبت شده"];
        }

        return ['warning', $lastActivity ? 'آمار امروز محتواها وارد نشده' : 'منتظر اولین ورود آمار روزانه'];
    }

    private function lastActivity(GrowthDataSource $source): ?Carbon
    {
        $candidates = collect([$source->last_received_at, $source->last_synced_at])->filter();

        if ($source->slug === 'growth-link-tracker' && Schema::hasTable('growth_events')) {
            $candidates->push(GrowthEvent::max('occurred_at'));
        } elseif ($source->slug === 'watan-core') {
            foreach (['users', 'generations', 'orders', 'plan_purchases', 'token_logs'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'updated_at')) {
                    $candidates->push(DB::table($table)->max('updated_at'));
                }
            }
        } elseif ($source->ingestion_method === 'manual' && Schema::hasTable('growth_content_daily_metrics')) {
            $candidates->push(GrowthContentDailyMetric::where('growth_data_source_id', $source->id)->max('updated_at'));
        }

        return $candidates
            ->map(fn ($value) => $value instanceof Carbon ? $value : ($value ? Carbon::parse($value) : null))
            ->filter()
            ->sortDesc()
            ->first();
    }
}
