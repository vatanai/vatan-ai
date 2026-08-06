<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralSettingLog;
use App\Models\ReferralVisit;
use App\Services\ReferralProgramService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralSettingController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.referrals.overview');
    }

    public function overview(Request $request): View
    {
        return $this->renderPage($request, 'overview');
    }

    public function settings(Request $request): View
    {
        return $this->renderPage($request, 'settings');
    }

    public function conversions(Request $request): View
    {
        return $this->renderPage($request, 'conversions', 'conversions');
    }

    public function rewards(Request $request): View
    {
        return $this->renderPage($request, 'rewards', 'rewards');
    }

    public function visits(Request $request): View
    {
        return $this->renderPage($request, 'visits', 'visits');
    }

    public function reviews(Request $request): View
    {
        return $this->renderPage($request, 'reviews');
    }

    private function renderPage(Request $request, string $page, ?string $tab = null): View
    {
        $settings = ReferralSetting::current();
        $stats = [
            'visits' => ReferralVisit::query()->count(),
            'conversions' => ReferralConversion::query()->count(),
            'paid_tokens' => (int) ReferralReward::query()->where('status', 'paid')->sum('amount'),
            'pending' => ReferralReward::query()->where('status', 'pending')->count()
                + ReferralConversion::query()->where('status', 'under_review')->count(),
        ];
        $tabCounts = [
            'conversions' => ReferralConversion::query()->count(),
            'rewards' => ReferralReward::query()->count(),
            'visits' => ReferralVisit::query()->count(),
        ];

        $pageMeta = match ($page) {
            'settings' => ['title' => 'تنظیمات برنامه', 'description' => 'هدیه شروع، شرط آزادشدن پاداش، محدودیت‌ها و متن معرفی را مدیریت کنید.', 'icon' => 'fa-sliders'],
            'conversions' => ['title' => 'فهرست دعوت‌ها', 'description' => 'کاربران دعوت‌کننده و دعوت‌شده، وضعیت خرید و نتیجه هر دعوت را ببینید.', 'icon' => 'fa-user-group'],
            'rewards' => ['title' => 'گزارش پاداش‌ها', 'description' => 'تمام توکن‌های پرداخت‌شده، معلق و ردشده را جست‌وجو و بررسی کنید.', 'icon' => 'fa-coins'],
            'visits' => ['title' => 'بازدید لینک‌ها', 'description' => 'ورودی لینک‌های اختصاصی و نرخ تبدیل آن‌ها به ثبت‌نام را پیگیری کنید.', 'icon' => 'fa-arrow-pointer'],
            'reviews' => ['title' => 'صف بررسی', 'description' => 'دعوت‌ها و پاداش‌های مشکوک را در یک صف مستقل تصمیم‌گیری کنید.', 'icon' => 'fa-shield-halved'],
            default => ['title' => 'نمای کلی همکاری در فروش', 'description' => 'وضعیت برنامه، عملکرد لینک‌ها و مسیرهای مدیریتی را یکجا ببینید.', 'icon' => 'fa-people-arrows-left-right'],
        };

        $records = null;
        $reviewConversions = null;
        $reviewRewards = null;

        if ($tab) {
            $records = match ($tab) {
                'rewards' => $this->rewardQuery($request)->paginate(15)->withQueryString(),
                'visits' => $this->visitQuery($request)->paginate(15)->withQueryString(),
                default => $this->conversionQuery($request)->paginate(15)->withQueryString(),
            };
        } elseif ($page === 'reviews') {
            $reviewConversions = $this->conversionQuery($request)
                ->where('status', 'under_review')
                ->paginate(10, ['*'], 'conversion_page')
                ->withQueryString();
            $reviewRewards = $this->rewardQuery($request)
                ->where('status', 'pending')
                ->paginate(10, ['*'], 'reward_page')
                ->withQueryString();
        }

        return view('admin.settings.referrals', compact(
            'settings', 'stats', 'page', 'pageMeta', 'tab', 'tabCounts',
            'records', 'reviewConversions', 'reviewRewards'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user('admin')?->isLeader(), 403);

        $data = $request->validate([
            'registration_gift_enabled' => ['required', 'boolean'],
            'registration_gift_tokens' => ['required', 'integer', 'min:0', 'max:1000000'],
            'registration_sms_enabled' => ['required', 'boolean'],
            'registration_gift_review_repeated_ip' => ['required', 'boolean'],
            'registration_gift_review_repeated_device' => ['required', 'boolean'],
            'registration_gift_cooldown_days' => ['required', 'integer', 'min:1', 'max:365'],
            'referral_enabled' => ['required', 'boolean'],
            'invitee_reward_tokens' => ['required', 'integer', 'min:0', 'max:1000000'],
            'inviter_reward_tokens' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reward_trigger' => ['required', Rule::in(['registration', 'first_purchase'])],
            'attribution_window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'daily_inviter_reward_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'monthly_inviter_reward_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'campaign_token_budget' => ['nullable', 'integer', 'min:1'],
            'campaign_starts_at' => ['nullable', 'date'],
            'campaign_ends_at' => ['nullable', 'date', 'after:campaign_starts_at'],
            'review_repeated_ip' => ['required', 'boolean'],
            'review_repeated_device' => ['required', 'boolean'],
            'profile_enabled' => ['required', 'boolean'],
            'profile_title' => ['required', 'string', 'max:120'],
            'profile_subtitle' => ['required', 'string', 'max:180'],
            'profile_description' => ['required', 'string', 'max:1000'],
            'share_message' => ['required', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if (! str_contains((string) $value, '{referral_link}')) {
                    $fail('متن اشتراک‌گذاری باید شامل متغیر {referral_link} باشد.');
                }
            }],
        ]);

        DB::transaction(function () use ($request, $data) {
            $settings = ReferralSetting::query()->lockForUpdate()->firstOrFail();
            $before = $settings->only(array_keys($data));
            $settings->update($data);

            ReferralSettingLog::query()->create([
                'admin_id' => $request->user('admin')->id,
                'before_values' => $before,
                'after_values' => $settings->fresh()->only(array_keys($data)),
            ]);
        });

        return back()->with('success', 'تنظیمات هدیه و همکاری در فروش ذخیره شد.');
    }

    public function reviewConversion(Request $request, ReferralConversion $conversion, ReferralProgramService $service): RedirectResponse
    {
        abort_unless($request->user('admin')?->isLeader(), 403);
        $data = $this->reviewData($request);
        $service->reviewConversion($conversion, $data['action'], $request->user('admin'), $data['note'] ?? null);

        return back()->with('success', $data['action'] === 'approve' ? 'دعوت تأیید شد و پاداش‌های آماده پرداخت شدند.' : 'دعوت و پاداش‌های معلق آن رد شدند.');
    }

    public function reviewReward(Request $request, ReferralReward $reward, ReferralProgramService $service): RedirectResponse
    {
        abort_unless($request->user('admin')?->isLeader(), 403);
        $data = $this->reviewData($request);
        $service->reviewReward($reward, $data['action'], $request->user('admin'), $data['note'] ?? null);

        return back()->with('success', $data['action'] === 'approve' ? 'پاداش تأیید و به موجودی کاربر افزوده شد.' : 'پاداش رد شد.');
    }

    public function export(Request $request): StreamedResponse
    {
        $tab = in_array($request->string('tab')->toString(), ['conversions', 'rewards', 'visits'], true)
            ? $request->string('tab')->toString()
            : 'conversions';
        $rows = match ($tab) {
            'rewards' => $this->rewardQuery($request)->get()->map(fn ($item) => [
                $item->id, $item->user?->name, $item->user?->phone, $this->rewardTypeLabel($item->reward_type),
                $item->amount, $this->rewardStatusLabel($item->status), $item->reason, $item->created_at?->format('Y-m-d H:i'),
            ]),
            'visits' => $this->visitQuery($request)->get()->map(fn ($item) => [
                $item->id, $item->inviter?->name, $item->inviter?->phone, $item->referral_code,
                $item->converted_user_id ? 'تبدیل‌شده' : 'بدون ثبت‌نام', $item->visited_at?->format('Y-m-d H:i'), $item->landing_url,
            ]),
            default => $this->conversionQuery($request)->get()->map(fn ($item) => [
                $item->id, $item->inviter?->name, $item->inviter?->phone, $item->invitee?->name,
                $item->invitee?->phone, $this->conversionStatusLabel($item->status), $item->purchase_completed ? 'خرید کرده' : 'بدون خرید',
                $item->risk_reason, $item->created_at?->format('Y-m-d H:i'),
            ]),
        };
        $headers = match ($tab) {
            'rewards' => ['شناسه', 'کاربر', 'موبایل', 'نوع', 'توکن', 'وضعیت', 'دلیل', 'زمان'],
            'visits' => ['شناسه', 'دعوت‌کننده', 'موبایل', 'کد دعوت', 'نتیجه', 'زمان', 'صفحه ورود'],
            default => ['شناسه', 'دعوت‌کننده', 'موبایل دعوت‌کننده', 'دعوت‌شده', 'موبایل دعوت‌شده', 'وضعیت', 'خرید', 'دلیل ریسک', 'زمان'],
        };

        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'referral-'.$tab.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reviewData(Request $request): array
    {
        return $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function conversionQuery(Request $request): Builder
    {
        $query = ReferralConversion::query()
            ->with(['inviter:id,name,last_name,phone,referral_code', 'invitee:id,name,last_name,phone', 'reviewer:id,name'])
            ->withSum(['rewards as paid_tokens' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->withExists(['invitee as purchase_completed' => fn ($q) => $q->whereHas('planPurchases', fn ($p) => $p->where('status', 'completed'))]);
        $this->applyDatesAndSort($query, $request);

        $query->when($request->filled('search'), function (Builder $q) use ($request) {
            $term = '%'.trim($request->string('search')->toString()).'%';
            $q->where(function (Builder $nested) use ($term) {
                $nested->whereHas('inviter', fn ($u) => $u->where('name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('referral_code', 'like', $term))
                    ->orWhereHas('invitee', fn ($u) => $u->where('name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('phone', 'like', $term));
            });
        })->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->string('purchase')->toString() === 'completed', fn ($q) => $q->whereHas('invitee.planPurchases', fn ($p) => $p->where('status', 'completed')))
            ->when($request->string('purchase')->toString() === 'waiting', fn ($q) => $q->whereDoesntHave('invitee.planPurchases', fn ($p) => $p->where('status', 'completed')));

        return $query;
    }

    private function rewardQuery(Request $request): Builder
    {
        $query = ReferralReward::query()->with(['user:id,name,last_name,phone', 'conversion.inviter:id,name,last_name,phone', 'reviewer:id,name']);
        $this->applyDatesAndSort($query, $request);
        $query->when($request->filled('search'), function (Builder $q) use ($request) {
            $term = '%'.trim($request->string('search')->toString()).'%';
            $q->where(function (Builder $nested) use ($term) {
                $nested->where('event_key', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('phone', 'like', $term));
            });
        })->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reward_type'), fn ($q) => $q->where('reward_type', $request->string('reward_type')));

        return $query;
    }

    private function visitQuery(Request $request): Builder
    {
        $query = ReferralVisit::query()->with(['inviter:id,name,last_name,phone,referral_code', 'convertedUser:id,name,last_name,phone']);
        $this->applyDatesAndSort($query, $request, 'visited_at');
        $query->when($request->filled('search'), function (Builder $q) use ($request) {
            $term = '%'.trim($request->string('search')->toString()).'%';
            $q->where(function (Builder $nested) use ($term) {
                $nested->where('referral_code', 'like', $term)
                    ->orWhereHas('inviter', fn ($u) => $u->where('name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('phone', 'like', $term));
            });
        })->when($request->string('conversion')->toString() === 'converted', fn ($q) => $q->whereNotNull('converted_user_id'))
            ->when($request->string('conversion')->toString() === 'not_converted', fn ($q) => $q->whereNull('converted_user_id'));

        return $query;
    }

    private function applyDatesAndSort(Builder $query, Request $request, string $dateColumn = 'created_at'): void
    {
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate($dateColumn, '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate($dateColumn, '<=', $request->date('date_to')))
            ->orderBy($dateColumn, $request->string('sort')->toString() === 'oldest' ? 'asc' : 'desc');
    }

    private function rewardTypeLabel(string $type): string
    {
        return match ($type) { 'registration_gift' => 'هدیه ثبت‌نام', 'invitee_reward' => 'هدیه دعوت‌شده', 'inviter_reward' => 'پاداش دعوت‌کننده', default => $type };
    }

    private function rewardStatusLabel(string $status): string
    {
        return match ($status) { 'paid' => 'پرداخت‌شده', 'pending' => 'در انتظار بررسی', 'rejected' => 'ردشده', 'processing' => 'در حال پردازش', default => $status };
    }

    private function conversionStatusLabel(string $status): string
    {
        return match ($status) { 'qualified' => 'معتبر', 'under_review' => 'نیازمند بررسی', 'rejected' => 'ردشده', default => $status };
    }
}
