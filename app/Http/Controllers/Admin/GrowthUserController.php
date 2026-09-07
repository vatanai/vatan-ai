<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\GrowthAttribution;
use App\Models\GrowthLink;
use App\Models\Order;
use App\Models\PlanPurchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GrowthUserController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $period] = $this->period($request);
        $search = trim((string) $request->query('search'));
        $activity = (string) $request->query('activity', 'all');

        $usersQuery = User::query()
            ->withCount(['generatedImages', 'planPurchases'])
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($activity === 'registered', fn ($query) => $query->whereBetween('registered_at', [$from, $to]))
            ->when($activity === 'logged_in', fn ($query) => $query->whereBetween('last_login_at', [$from, $to]))
            ->when($activity === 'purchased', fn ($query) => $query->whereHas('planPurchases', fn ($purchase) => $purchase->whereBetween('purchased_at', [$from, $to])->where('status', 'completed')))
            ->when($activity === 'generated', fn ($query) => $query->whereHas('generatedImages', fn ($image) => $image->whereBetween('created_at', [$from, $to])))
            ->latest('registered_at');

        $users = $usersQuery->paginate(25)->withQueryString();
        $signups = User::whereBetween('registered_at', [$from, $to])->count();
        $logins = AuthEvent::where('event', 'login_success')->where('successful', true)->whereBetween('occurred_at', [$from, $to])->count();
        $activeUsers = AuthEvent::where('successful', true)->whereBetween('occurred_at', [$from, $to])->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $buyers = PlanPurchase::where('status', 'completed')->whereBetween('purchased_at', [$from, $to])->distinct('user_id')->count('user_id');
        $revenue = (int) PlanPurchase::where('status', 'completed')->whereBetween('purchased_at', [$from, $to])->sum('paid_amount');
        $orders = Order::whereBetween('created_at', [$from, $to])->count();
        $registeredUserIds = User::whereBetween('registered_at', [$from, $to])->pluck('id');
        $signupBuyers = $registeredUserIds->isEmpty() ? 0 : PlanPurchase::where('status', 'completed')
            ->whereIn('user_id', $registeredUserIds)->distinct('user_id')->count('user_id');
        $conversionRate = $signups > 0 ? round(($signupBuyers / $signups) * 100, 1) : 0;

        $days = (int) max(1, min(90, $from->diffInDays($to) + 1));
        $labels = collect(range($days - 1, 0))->map(fn ($offset) => $to->copy()->startOfDay()->subDays($offset)->format('m/d'));
        $signupRows = User::whereBetween('registered_at', [$from, $to])->get(['registered_at'])->groupBy(fn ($user) => $user->registered_at?->format('m/d'));
        $loginRows = AuthEvent::where('event', 'login_success')->whereBetween('occurred_at', [$from, $to])->get(['occurred_at'])->groupBy(fn ($event) => $event->occurred_at->format('m/d'));
        $purchaseRows = PlanPurchase::where('status', 'completed')->whereBetween('purchased_at', [$from, $to])->get(['purchased_at'])->groupBy(fn ($purchase) => $purchase->purchased_at?->format('m/d'));
        $trend = [
            'labels' => $labels->all(),
            'signups' => $labels->map(fn ($day) => $signupRows->get($day, collect())->count())->all(),
            'logins' => $labels->map(fn ($day) => $loginRows->get($day, collect())->count())->all(),
            'purchases' => $labels->map(fn ($day) => $purchaseRows->get($day, collect())->count())->all(),
        ];

        $links = GrowthLink::query()->withCount([
            'events as clicks_count' => fn ($query) => $query->where('event_type', 'click')->whereBetween('occurred_at', [$from, $to]),
            'events as opens_count' => fn ($query) => $query->where('event_type', 'page_open')->whereBetween('occurred_at', [$from, $to]),
        ])->get()->map(function (GrowthLink $link) use ($from, $to) {
            $attributions = GrowthAttribution::where('growth_link_id', $link->id)->whereBetween('attributed_at', [$from, $to]);
            $link->attributed_users = (clone $attributions)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
            $link->purchases_count = (clone $attributions)->whereIn('stage', ['purchase', 'plan_purchase'])->count();
            return $link;
        })->sortByDesc('clicks_count')->take(10);

        return view('admin.growth.page', [
            'partial' => 'admin.growth.users.index', 'title' => 'کاربران و سفر مشتری',
            'users' => $users, 'from' => $from, 'to' => $to, 'period' => $period, 'search' => $search, 'activity' => $activity,
            'metrics' => compact('signups', 'logins', 'activeUsers', 'buyers', 'revenue', 'orders', 'conversionRate'),
            'trend' => $trend, 'links' => $links,
        ]);
    }

    public function show(User $user): View
    {
        $authEvents = AuthEvent::where('user_id', $user->id)->latest('occurred_at')->limit(100)->get()->map(fn ($event) => [
            'time' => $event->occurred_at, 'type' => 'auth', 'title' => $this->authLabel($event->event),
            'detail' => $event->successful ? 'موفق' : 'ناموفق', 'meta' => trim(($event->ip_address ?: '').' '.($event->user_agent ?: '')),
        ]);
        $orders = Order::with('product')->where('user_id', $user->id)->latest()->limit(100)->get()->map(fn ($order) => [
            'time' => $order->created_at, 'type' => 'order', 'title' => 'سفارش '.$order->order_number,
            'detail' => ($order->product?->name ?: 'محصول').' · '.$order->status, 'meta' => number_format($order->final_credits).' اعتبار',
        ]);
        $purchases = PlanPurchase::where('user_id', $user->id)->latest('purchased_at')->limit(100)->get()->map(fn ($purchase) => [
            'time' => $purchase->purchased_at ?: $purchase->created_at, 'type' => 'purchase', 'title' => 'خرید '.$purchase->plan_name,
            'detail' => $purchase->status, 'meta' => number_format($purchase->paid_amount).' تومان',
        ]);
        $images = $user->generatedImages()->with('product')->latest()->limit(100)->get()->map(fn ($image) => [
            'time' => $image->created_at, 'type' => 'generation', 'title' => 'ساخت تصویر',
            'detail' => $image->product?->name ?: 'بدون محصول', 'meta' => number_format((int) $image->cost).' اعتبار',
        ]);
        $timeline = collect()->concat($authEvents)->concat($orders)->concat($purchases)->concat($images)
            ->sortByDesc('time')->take(200)->values();
        $source = GrowthAttribution::with('link')->where('user_id', $user->id)->latest('attributed_at')->first();

        return view('admin.growth.page', [
            'partial' => 'admin.growth.users.show', 'title' => 'سفر کاربر', 'user' => $user,
            'timeline' => $timeline, 'source' => $source,
            'stats' => [
                'logins' => AuthEvent::where('user_id', $user->id)->where('event', 'login_success')->count(),
                'generations' => $user->generatedImages()->count(), 'orders' => Order::where('user_id', $user->id)->count(),
                'purchases' => PlanPurchase::where('user_id', $user->id)->where('status', 'completed')->count(),
                'revenue' => (int) PlanPurchase::where('user_id', $user->id)->where('status', 'completed')->sum('paid_amount'),
            ],
        ]);
    }

    private function period(Request $request): array
    {
        $period = in_array($request->query('period'), ['today', '7', '30', '90', 'custom'], true) ? $request->query('period') : '30';
        $to = $period === 'custom' && $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $from = match ($period) {
            'today' => now()->startOfDay(), '7' => $to->copy()->subDays(6)->startOfDay(),
            '90' => $to->copy()->subDays(89)->startOfDay(),
            'custom' => $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(29)->startOfDay(),
            default => $to->copy()->subDays(29)->startOfDay(),
        };
        return [$from, $to, $period];
    }

    private function authLabel(string $event): string
    {
        return ['otp_sent'=>'ارسال کد ورود','otp_verified'=>'تأیید شماره','otp_verify_failed'=>'کد ورود ناموفق','login_success'=>'ورود موفق','registration_completed'=>'تکمیل ثبت‌نام','otp_send_failed'=>'خطای ارسال کد','otp_request_blocked'=>'تلاش حساب مسدود'][$event] ?? $event;
    }
}
