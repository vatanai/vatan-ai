<?php

namespace App\Services;

use App\Models\CustomerJourney;
use App\Models\CustomerJourneyEvent;
use App\Models\CustomerJourneySetting;
use App\Models\CustomerJourneyStage;
use App\Models\CustomerJourneyTask;
use App\Models\PlanPurchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CustomerJourneyService
{
    public const ACTIVE = 'active';
    public const HUMAN_REVIEW = 'human_review';
    public const PAUSED = 'paused';
    public const CLOSED = 'closed';

    public function sync(User $user, string $reason = 'به‌روزرسانی رفتار کاربر'): CustomerJourney
    {
        $this->ensureReady();

        $imageCount = (int) $user->generatedImages()->whereNotNull('image_path')->count();
        $videoCount = Schema::hasTable('generated_videos')
            ? (int) $user->generatedVideos()->where(function ($query): void {
                $query->whereNotNull('video_path')->orWhereNotNull('video_url');
            })->count()
            : 0;
        $outputCount = $imageCount + $videoCount;
        $purchaseCount = Schema::hasTable('plan_purchases')
            ? (int) $user->planPurchases()->where('status', PlanPurchase::COMPLETED)->count()
            : 0;
        $linkCount = Schema::hasTable('referral_links') ? (int) $user->referralLinks()->count() : 0;
        $lastImage = $user->generatedImages()->latest('created_at')->value('created_at');
        $lastVideo = Schema::hasTable('generated_videos') ? $user->generatedVideos()->latest('created_at')->value('created_at') : null;
        $lastPurchase = Schema::hasTable('plan_purchases')
            ? $user->planPurchases()->where('status', PlanPurchase::COMPLETED)->latest('purchased_at')->value('purchased_at')
            : null;
        $lastActivity = collect([$lastImage, $lastVideo, $lastPurchase, $user->last_login_at])
            ->filter()
            ->map(fn ($value) => Carbon::parse($value))
            ->sortDesc()
            ->first();

        [$point, $substage] = $this->classify($user, $outputCount, $purchaseCount);
        $score = min(100, ($outputCount * 10) + ($purchaseCount * 25) + ($linkCount * 5) + min(20, intdiv(max(0, (int) $user->tokens_used), 5)));
        $journey = CustomerJourney::query()->firstOrNew(['user_id' => $user->id]);
        $isProtected = $journey->exists && in_array($journey->status, [self::HUMAN_REVIEW, self::PAUSED, self::CLOSED], true);
        if ($isProtected) {
            $point = (int) $journey->point;
            $substage = (string) $journey->substage;
        }
        $previousPoint = $journey->exists ? (int) $journey->point : null;
        $previousSubstage = $journey->exists ? $journey->substage : null;
        $changed = $previousPoint !== $point || $previousSubstage !== $substage;
        $now = now();

        $journey->fill([
            'point' => $point,
            'substage' => $substage,
            'source' => $journey->source ?: 'behavior_engine',
            'entered_at' => $changed || ! $journey->entered_at ? $now : $journey->entered_at,
            'last_activity_at' => $lastActivity,
            'next_action_at' => $this->nextActionAt($point, $lastActivity),
            'readiness_score' => $score,
            'last_reason' => $isProtected ? $journey->last_reason : $reason,
            'metadata' => [
                ...((array) $journey->metadata),
                'image_count' => $imageCount,
                'video_count' => $videoCount,
                'output_count' => $outputCount,
                'purchase_count' => $purchaseCount,
                'link_count' => $linkCount,
                'tokens' => (int) $user->tokens,
                'promotional_tokens' => (int) $user->promotional_tokens,
                'synced_at' => $now->toIso8601String(),
            ],
        ]);
        $journey->save();

        if ($changed) {
            CustomerJourneyEvent::query()->create([
                'customer_journey_id' => $journey->id,
                'user_id' => $user->id,
                'event' => $previousPoint === null ? 'journey_started' : 'point_changed',
                'point_before' => $previousPoint,
                'point_after' => $point,
                'substage_before' => $previousSubstage,
                'substage_after' => $substage,
                'credit_before' => null,
                'credit_after' => (int) $user->tokens,
                'reason' => $reason,
                'metadata' => ['image_count' => $imageCount, 'purchase_count' => $purchaseCount],
                'occurred_at' => $now,
            ]);
            $this->replacePendingTask($journey, $point, $now);
        } elseif (! $journey->tasks()->where('status', 'pending')->exists()) {
            $this->replacePendingTask($journey, $point, $journey->next_action_at ?: $now);
        }

        return $journey->fresh(['user']);
    }

    public function moveManually(CustomerJourney $journey, int $point, string $reason, ?int $adminId = null): CustomerJourney
    {
        $this->ensureReady();
        $point = max(1, min(8, $point));
        $beforePoint = (int) $journey->point;
        $beforeSubstage = $journey->substage;
        $substage = $this->manualSubstage($point);
        $journey->update([
            'point' => $point,
            'substage' => $substage,
            'status' => self::ACTIVE,
            'entered_at' => now(),
            'next_action_at' => $this->nextActionAt($point, $journey->last_activity_at),
            'last_reason' => $reason,
        ]);
        CustomerJourneyEvent::query()->create([
            'customer_journey_id' => $journey->id,
            'user_id' => $journey->user_id,
            'event' => 'manual_point_change',
            'point_before' => $beforePoint,
            'point_after' => $point,
            'substage_before' => $beforeSubstage,
            'substage_after' => $substage,
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
        $this->replacePendingTask($journey->fresh(), $point, now());

        return $journey->fresh(['user']);
    }

    public function sendToHumanReview(CustomerJourney $journey, string $reason, ?int $adminId = null): CustomerJourney
    {
        $journey->update(['status' => self::HUMAN_REVIEW, 'last_reason' => $reason, 'next_action_at' => now()]);
        CustomerJourneyEvent::query()->create([
            'customer_journey_id' => $journey->id,
            'user_id' => $journey->user_id,
            'event' => 'human_review_requested',
            'point_before' => $journey->point,
            'point_after' => $journey->point,
            'substage_before' => $journey->substage,
            'substage_after' => $journey->substage,
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
        $journey->tasks()->where('status', 'pending')->update(['status' => 'cancelled']);
        $journey->tasks()->create([
            'user_id' => $journey->user_id,
            'point' => $journey->point,
            'sequence' => 8,
            'task_type' => 'human_review',
            'status' => 'pending',
            'title' => 'بررسی انسانی کاربر',
            'body' => $reason,
            'due_at' => now(),
        ]);

        return $journey->fresh(['user']);
    }

    public function completeTask(CustomerJourneyTask $task, ?int $adminId = null): CustomerJourneyTask
    {
        $task->update(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $adminId]);
        $journey = $task->journey;
        if ($journey && $journey->status === self::HUMAN_REVIEW) {
            $journey->update(['status' => self::ACTIVE, 'cycle_step' => 0, 'cycle_step_at' => now(), 'next_action_at' => now()->addDay()]);
        }
        return $task->fresh(['journey', 'user']);
    }

    public function settings(): array
    {
        if (! Schema::hasTable('customer_journey_settings')) {
            return [];
        }
        return CustomerJourneySetting::query()->get()->keyBy('key')->map(fn (CustomerJourneySetting $setting): array => [
            'label' => $setting->label,
            'value' => (int) $setting->value,
        ])->all();
    }

    public function updateSettings(array $values, ?int $adminId = null): void
    {
        foreach ($values as $key => $value) {
            CustomerJourneySetting::query()->where('key', $key)->update(['value' => (string) max(0, (int) $value), 'updated_by' => $adminId, 'updated_at' => now()]);
        }
    }

    public function pointDefinitions(): array
    {
        if (Schema::hasTable('customer_journey_stages')) {
            return CustomerJourneyStage::query()->orderBy('point')->get()->mapWithKeys(fn (CustomerJourneyStage $stage): array => [
                $stage->point => $stage->toArray(),
            ])->all();
        }

        return $this->defaultPointDefinitions();
    }

    public function updatePointDefinition(int $point, array $values): void
    {
        CustomerJourneyStage::query()->where('point', $point)->update([
            'short' => $values['short'],
            'title' => $values['title'],
            'description' => $values['description'] ?? null,
            'task_title' => $values['task_title'],
            'task_body' => $values['task_body'] ?? null,
            'channel' => $values['channel'],
            'message_template' => $values['message_template'] ?? null,
            'delay_minutes' => (int) $values['delay_minutes'],
            'human_required' => (bool) ($values['human_required'] ?? false),
            'advance_rule' => $values['advance_rule'] ?? null,
            'stop_rule' => $values['stop_rule'] ?? null,
            'enabled' => (bool) ($values['enabled'] ?? true),
            'updated_at' => now(),
        ]);
    }

    private function classify(User $user, int $outputCount, int $purchaseCount): array
    {
        if ($purchaseCount >= 2) {
            return [8, 'repurchase_needed'];
        }
        if ($purchaseCount === 1) {
            return [$outputCount >= 3 ? 7 : 6, $outputCount >= 3 ? 'successful_consumption' : 'first_purchase'];
        }
        if ($outputCount === 0) {
            return [1, 'new'];
        }
        if ($outputCount === 1) {
            return [2, 'first_output'];
        }
        if ($outputCount === 2) {
            return [3, 'second_output'];
        }
        if ($outputCount <= 4) {
            return [4, 'active_usage'];
        }

        return [5, 'purchase_ready'];
    }

    private function manualSubstage(int $point): string
    {
        return [1 => 'new', 2 => 'first_output', 3 => 'second_output', 4 => 'active_usage', 5 => 'purchase_ready', 6 => 'first_purchase', 7 => 'successful_consumption', 8 => 'repurchase_needed'][$point] ?? 'new';
    }

    private function nextActionAt(int $point, ?Carbon $lastActivity): ?Carbon
    {
        $definition = $this->pointDefinitions()[$point] ?? null;
        $delayMinutes = (int) ($definition['delay_minutes'] ?? 0);

        if ($delayMinutes > 0) {
            return ($lastActivity?->copy() ?: now())->addMinutes($delayMinutes);
        }

        return now()->addDays($this->settingValue('inactivity_days', 7));
    }

    private function replacePendingTask(CustomerJourney $journey, int $point, Carbon $dueAt): void
    {
        $journey->tasks()->where('status', 'pending')->update(['status' => 'cancelled']);
        $definition = $this->pointDefinitions()[$point] ?? $this->defaultPointDefinitions()[1];
        $journey->tasks()->create([
            'user_id' => $journey->user_id,
            'point' => $point,
            'sequence' => $point,
            'task_type' => ($definition['human_required'] ?? false) ? 'human_review' : 'manual',
            'status' => 'pending',
            'title' => $definition['task_title'] ?? ('اقدام مرحله: '.$definition['short']),
            'body' => $definition['task_body'] ?? $definition['description'],
            'due_at' => $dueAt,
        ]);
    }

    private function defaultPointDefinitions(): array
    {
        return [
            1 => ['point' => 1, 'title' => 'کلیک روی لینک و ثبت‌نام در سایت', 'short' => 'اولین تجربه', 'description' => 'کاربر وارد شده و حساب خود را ساخته اما هنوز خروجی نگرفته است.', 'icon' => 'fa-user-plus', 'task_title' => 'خوش‌آمدگویی و بررسی ورود', 'task_body' => 'ورود کاربر را بررسی کن و راهنمای کوتاه شروع کار را در اختیارش بگذار.', 'channel' => 'sms', 'message_template' => 'خوش آمدی؛ برای شروع اولین خروجی خودت را بساز.', 'delay_minutes' => 5, 'human_required' => false, 'advance_rule' => 'ثبت‌نام یا ورود موفق کاربر', 'stop_rule' => 'شماره یا اطلاعات تماس نامعتبر باشد.', 'enabled' => true],
            2 => ['point' => 2, 'title' => 'اولین خروجی از سایت', 'short' => 'ساخت اول', 'description' => 'کاربر اولین عکس یا ویدیوی خود را ساخته و باید تجربه موفقش تثبیت شود.', 'icon' => 'fa-wand-magic-sparkles', 'task_title' => 'پیگیری اولین تجربه ساخت', 'task_body' => 'خروجی اول را بررسی کن و یک پیشنهاد ساده برای ساخت بعدی بده.', 'channel' => 'in_app', 'message_template' => 'اولین خروجی آماده است؛ اگر دوست داشتی یک نمونه دیگر هم بساز.', 'delay_minutes' => 180, 'human_required' => false, 'advance_rule' => 'حداقل یک خروجی معتبر ثبت شود.', 'stop_rule' => 'ساخت ناموفق یا نارضایتی کاربر ثبت شود.', 'enabled' => true],
            3 => ['point' => 3, 'title' => 'دومین خروجی از سایت', 'short' => 'ساخت دوم', 'description' => 'کاربر برای بار دوم از محصول استفاده کرده و نشانه خوبی از تکرارپذیری دارد.', 'icon' => 'fa-repeat', 'task_title' => 'تقویت استفاده تکراری', 'task_body' => 'الگوی استفاده کاربر را ببین و قابلیت مرتبط بعدی را معرفی کن.', 'channel' => 'in_app', 'message_template' => 'حالا که ساخت دوم را تجربه کردی، قابلیت‌های بیشتر را امتحان کن.', 'delay_minutes' => 720, 'human_required' => false, 'advance_rule' => 'حداقل دو خروجی معتبر ثبت شود.', 'stop_rule' => 'کاربر در استفاده دوم متوقف یا ناراضی باشد.', 'enabled' => true],
            4 => ['point' => 4, 'title' => 'تجربه ارزش و استفاده مستمر', 'short' => 'استفاده مستمر', 'description' => 'کاربر چند بار از محصول استفاده کرده و باید ارزش واقعی محصول را ببیند.', 'icon' => 'fa-chart-line', 'task_title' => 'نمایش ارزش محصول', 'task_body' => 'بهترین قابلیت مناسب این کاربر را معرفی کن و نتیجه استفاده را ثبت کن.', 'channel' => 'direct', 'message_template' => 'بر اساس استفاده‌ات، این قابلیت می‌تواند نتیجه بهتری برایت بسازد.', 'delay_minutes' => 1440, 'human_required' => false, 'advance_rule' => 'حداقل سه خروجی یا فعالیت تکرارشونده ثبت شود.', 'stop_rule' => 'کاربر چند روز غیرفعال بماند یا درخواست توقف بدهد.', 'enabled' => true],
            5 => ['point' => 5, 'title' => 'آماده تصمیم برای خرید', 'short' => 'آماده خرید', 'description' => 'کاربر ارزش محصول را دیده و باید پیشنهاد خرید مناسب و شفاف دریافت کند.', 'icon' => 'fa-coins', 'task_title' => 'ارائه پیشنهاد خرید', 'task_body' => 'نیاز کاربر را بررسی کن، پلن مناسب را معرفی کن و مانع خرید را ثبت کن.', 'channel' => 'call', 'message_template' => 'اگر بخواهی استفاده‌ات را ادامه بدهی، این پلن برای نیاز تو مناسب‌تر است.', 'delay_minutes' => 1440, 'human_required' => true, 'advance_rule' => 'ثبت علاقه‌مندی یا شروع فرآیند خرید.', 'stop_rule' => 'پاسخ منفی قطعی یا عدم تمایل کاربر.', 'enabled' => true],
            6 => ['point' => 6, 'title' => 'اولین خرید از سایت', 'short' => 'خرید اول', 'description' => 'اولین پرداخت موفق انجام شده و باید تجربه شروع مصرف بدون اصطکاک باشد.', 'icon' => 'fa-cart-shopping', 'task_title' => 'تأیید خرید و شروع مصرف', 'task_body' => 'خرید را بررسی کن و مطمئن شو کاربر می‌داند از اعتبار خود چطور استفاده کند.', 'channel' => 'sms', 'message_template' => 'خریدت با موفقیت انجام شد؛ راهنمای شروع مصرف را از اینجا ببین.', 'delay_minutes' => 30, 'human_required' => false, 'advance_rule' => 'اولین خرید با وضعیت تکمیل‌شده ثبت شود.', 'stop_rule' => 'پرداخت ناموفق یا درخواست بازگشت وجه.', 'enabled' => true],
            7 => ['point' => 7, 'title' => 'مصرف موفق خرید و رضایت', 'short' => 'مصرف موفق', 'description' => 'کاربر خرید کرده و باید از خرید خود نتیجه بگیرد و رضایتش ثبت شود.', 'icon' => 'fa-face-smile', 'task_title' => 'پیگیری رضایت مشتری', 'task_body' => 'مصرف اعتبار و رضایت را بررسی کن و در صورت نیاز پشتیبانی انسانی بده.', 'channel' => 'direct', 'message_template' => 'نتیجه استفاده‌ات چطور بود؟ اگر کمکی لازم داری با ما در ارتباط باش.', 'delay_minutes' => 4320, 'human_required' => false, 'advance_rule' => 'پس از خرید حداقل یک استفاده یا تأیید رضایت ثبت شود.', 'stop_rule' => 'نارضایتی یا مشکل حل‌نشده ثبت شود.', 'enabled' => true],
            8 => ['point' => 8, 'title' => 'بازگشت و خرید مجدد', 'short' => 'خرید مجدد', 'description' => 'کاربر سابقه خرید دارد و باید با پیشنهاد به‌موقع دوباره فعال شود.', 'icon' => 'fa-rotate', 'task_title' => 'پیگیری بازگشت مشتری', 'task_body' => 'زمان مناسب بازگشت را پیدا کن و پیشنهاد متناسب با سابقه کاربر بده.', 'channel' => 'call', 'message_template' => 'برای ادامه کار آماده‌ای؟ می‌توانیم پیشنهاد مناسب استفاده بعدی‌ات را آماده کنیم.', 'delay_minutes' => 10080, 'human_required' => true, 'advance_rule' => 'خرید دوم یا بیشتر با وضعیت تکمیل‌شده ثبت شود.', 'stop_rule' => 'کاربر درخواست عدم تماس یا توقف همکاری بدهد.', 'enabled' => true],
        ];
    }

    private function settingValue(string $key, int $default): int
    {
        return max(0, (int) (CustomerJourneySetting::query()->where('key', $key)->value('value') ?? $default));
    }

    private function ensureReady(): void
    {
        abort_unless(Schema::hasTable('customer_journeys'), 503, 'موتور مسیر کاربران هنوز فعال نشده است.');
    }
}
