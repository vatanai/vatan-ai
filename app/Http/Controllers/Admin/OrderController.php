<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PlanPurchase;
use App\Models\Product;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\CreditWalletService;
use App\Services\SmsEventService;
use App\Services\Finance\FinanceCaseLedgerService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request, 'all');
    }

    public function processing(Request $request)
    {
        return $this->listing($request, 'processing');
    }

    public function failed(Request $request)
    {
        return $this->listing($request, 'failed');
    }

    public function planPurchases(Request $request)
    {
        $purchases = $this->planPurchasesQuery($request)->latest()->paginate(20)->withQueryString();
        if (! Schema::hasTable('finance_cases')) {
            $purchases->getCollection()->each->setRelation('financeCase', null);
        }
        $stats = [
            'total' => PlanPurchase::count(),
            'completed' => PlanPurchase::where('status', PlanPurchase::COMPLETED)->count(),
            'active' => PlanPurchase::whereIn('status', [PlanPurchase::PENDING, PlanPurchase::REDIRECTED, 'verifying'])->count(),
            'failed' => PlanPurchase::whereIn('status', [PlanPurchase::FAILED, PlanPurchase::EXPIRED, 'cancelled'])->count(),
            'credits' => (int) PlanPurchase::where('status', PlanPurchase::COMPLETED)->sum('granted_tokens'),
            'revenue' => (int) PlanPurchase::where('status', PlanPurchase::COMPLETED)->sum('paid_amount'),
        ];

        return view('admin.orders.plan-purchases', compact('purchases', 'stats'));
    }

    /** خروجی CSV خریدهای پلن، با پشتیبانی از فیلتر و انتخاب ردیف‌ها. */
    public function exportPlanPurchases(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:30'],
            'ids' => ['nullable', 'array', 'max:1000'],
            'ids.*' => ['integer', 'exists:plan_purchases,id'],
        ]);

        $purchaseIds = collect($data['ids'] ?? [])
            ->map(fn ($purchaseId) => (int) $purchaseId)
            ->unique()
            ->values()
            ->all();
        $purchases = $this->planPurchasesQuery($request)
            ->when($purchaseIds !== [], fn (Builder $query) => $query->whereIn('id', $purchaseIds))
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($purchases): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'شماره سفارش', 'شناسه کاربر', 'نام', 'نام خانوادگی', 'شماره تماس', 'ایمیل', 'تاریخ تولد شمسی',
                'وضعیت کاربر', 'پلن فعال کاربر', 'گروه قیمت‌گذاری', 'اعتبار فعلی', 'کل اعتبار خریداری‌شده',
                'کل اعتبار مصرف‌شده', 'تصاویر خلق‌شده', 'دعوت از طرف', 'کد دعوت', 'تاریخ عضویت', 'آخرین ورود',
                'تعداد ورود', 'پلن خریداری‌شده', 'مبلغ پرداختی', 'اعتبار دریافتی', 'درگاه', 'کد پیگیری درگاه',
                'شماره مرجع', 'وضعیت پرداخت', 'دلیل ناموفق‌بودن', 'زمان ایجاد خرید', 'زمان تایید پرداخت',
            ]);

            foreach ($purchases as $purchase) {
                $user = $purchase->user;
                $referrer = $user?->referrer;
                fputcsv($stream, [
                    $purchase->order_number,
                    $user?->id ?: '—',
                    $user?->name ?: '—',
                    $user?->last_name ?: '—',
                    $user?->phone ?: '—',
                    $user?->email ?: '—',
                    $this->jalaliDate($user?->birth_date),
                    $this->userStatusLabel($user?->status),
                    $user?->plan_display_name ?: '—',
                    ($user?->customer_segment ?: 'regular') === 'loyal' ? 'مشتری ثابت' : 'کاربر عادی',
                    (int) ($user?->tokens ?? 0),
                    (int) ($user?->tokens_purchased ?? 0),
                    (int) ($user?->tokens_used ?? 0),
                    (int) ($user?->generated_images_count ?? 0),
                    $referrer ? trim(($referrer->name ?? '') . ' ' . ($referrer->last_name ?? '')) : 'ثبت‌نام مستقیم',
                    $referrer?->referral_code ?: '—',
                    Jalali::formatNumeric($user?->registered_at ?: $user?->created_at),
                    Jalali::formatNumeric($user?->last_login_at),
                    (int) ($user?->login_count ?? 0),
                    $purchase->plan_name,
                    (int) $purchase->paid_amount,
                    (int) $purchase->granted_tokens,
                    $purchase->gateway === 'zarinpal' ? 'زرین‌پال' : ($purchase->gateway === 'zibal' ? 'زیبال' : ($purchase->gateway ?: '—')),
                    $purchase->gateway_track_id ?: '—',
                    $purchase->gateway_reference ?: $purchase->payment_reference ?: '—',
                    PlanPurchase::statusLabel($purchase->status),
                    $purchase->failure_reason ?: '—',
                    Jalali::formatNumeric($purchase->initiated_at ?: $purchase->created_at),
                    Jalali::formatNumeric($purchase->verified_at ?: $purchase->purchased_at),
                ]);
            }

            fclose($stream);
        }, 'vatan-plan-purchases-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function planPurchasesQuery(Request $request): Builder
    {
        $hasAuthEvents = Schema::hasTable('auth_events');
        $hasFinanceCases = Schema::hasTable('finance_cases');
        $userCountRelations = ['generatedImages'];
        if (Schema::hasTable('user_gallery_items')) {
            $userCountRelations[] = 'galleryItems';
        }
        $relations = [
            'plan:id,name,slug',
            'user' => function ($userQuery) use ($hasAuthEvents, $hasFinanceCases, $userCountRelations): void {
                $userQuery->select([
                    'id', 'name', 'last_name', 'email', 'phone', 'avatar', 'birth_date', 'status', 'plan_id',
                    'customer_segment', 'tokens', 'tokens_purchased', 'tokens_used', 'registered_at', 'last_login_at',
                    'login_count', 'created_at', 'referred_by',
                ])->with([
                    'plan:id,name',
                    'referrer:id,name,last_name,phone,referral_code',
                ])->withCount($userCountRelations);
                if ($hasAuthEvents) {
                    $userQuery->with('lastSuccessfulLogin');
                }
                if ($hasFinanceCases) {
                    $userQuery->withCount('financeCases');
                }
            },
        ];
        if ($hasFinanceCases) {
            $relations[] = 'financeCase:id,anchor_plan_purchase_id,case_number';
        }
        $query = PlanPurchase::query()->with($relations);

        $query->when($request->filled('q'), function (Builder $purchaseQuery) use ($request): void {
            $term = trim((string) $request->q);
            $purchaseQuery->where(function (Builder $subQuery) use ($term): void {
                $subQuery->where('order_number', 'like', "%{$term}%")
                    ->orWhere('payment_reference', 'like', "%{$term}%")
                    ->orWhere('gateway_reference', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            });
        });
        $query->when($request->filled('status'), fn (Builder $purchaseQuery) => $purchaseQuery->where('status', $request->status));

        return $query;
    }

    private function jalaliDate($date): string
    {
        if (!$date) {
            return '—';
        }

        [$year, $month, $day] = Jalali::toJalaliYmd(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        );

        return Jalali::toPersianDigits(sprintf('%04d/%02d/%02d', $year, $month, $day));
    }

    private function userStatusLabel(?string $status): string
    {
        return match ($status) {
            'suspended' => 'معلق',
            'deleted' => 'حذف شده',
            default => 'فعال',
        };
    }

    private function listing(Request $request, string $view)
    {
        $hasAuthEvents = Schema::hasTable('auth_events');
        $hasFinanceCases = Schema::hasTable('finance_cases');
        $userCountRelations = ['generatedImages'];
        if (Schema::hasTable('user_gallery_items')) {
            $userCountRelations[] = 'galleryItems';
        }
        $relations = [
            'user' => function ($userQuery) use ($hasAuthEvents, $hasFinanceCases, $userCountRelations): void {
                $userQuery->select([
                    'id', 'name', 'last_name', 'email', 'phone', 'plan_id', 'tokens',
                    'registered_at', 'last_login_at', 'login_count', 'created_at',
                ])->with('plan:id,name,model_tier_key')->withCount($userCountRelations);
                if ($hasAuthEvents) {
                    $userQuery->with('lastSuccessfulLogin');
                }
                if ($hasFinanceCases) {
                    $userQuery->withCount('financeCases');
                }
            },
            'product:id,name_fa,product_code',
        ];
        if (Schema::hasTable('finance_credit_allocations')) {
            $relations[] = 'creditAllocations:id,order_id,finance_case_id';
            $relations[] = 'creditAllocations.financeCase:id,case_number';
        }
        $query = Order::query()->with($relations);

        if ($view === 'processing') $query->whereIn('processing_status', ['queued', 'processing', 'retrying']);
        if ($view === 'failed') $query->where(fn ($q) => $q->where('processing_status', 'failed')->orWhere('status', 'review'));

        $query->when($request->filled('q'), function ($q) use ($request) {
            $term = trim((string) $request->q);
            $q->where(function ($sub) use ($term) {
                $sub->where('order_number', 'like', "%{$term}%")
                    ->orWhere('payment_reference', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            });
        });
        $query->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status));
        $query->when($request->filled('processing_status'), fn ($q) => $q->where('processing_status', $request->processing_status));
        $query->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));

        $orders = $query->latest()->paginate(20)->withQueryString();
        if (! Schema::hasTable('finance_credit_allocations')) {
            $orders->getCollection()->each->setRelation('creditAllocations', collect());
        }
        $products = Product::query()->select('id', 'name_fa')->orderBy('name_fa')->get();
        $stats = [
            'total' => Order::count(),
            'completed' => Order::where('status', 'completed')->count(),
            'active' => Order::whereIn('processing_status', ['queued', 'processing', 'retrying'])->count(),
            'failed' => Order::where('processing_status', 'failed')->count(),
            'credits' => (int) Order::where('payment_status', 'paid')->sum('final_credits'),
        ];

        return view('admin.orders.index', compact('orders', 'products', 'stats', 'view'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'product', 'discount', 'events.admin']);
        return view('admin.orders.show', compact('order'));
    }

    public function retry(Order $order)
    {
        abort_unless(in_array($order->processing_status, ['failed', 'expired', 'stopped'], true), 422);
        $order->update([
            'status' => 'processing', 'processing_status' => 'retrying',
            'attempts' => $order->attempts + 1, 'error_message' => null,
        ]);
        $order->recordEvent('retry', 'اجرای مجدد سفارش', 'سفارش توسط مدیر برای اجرای مجدد به صف فرستاده شد.');
        return back()->with('success', 'سفارش برای اجرای مجدد آماده شد.');
    }

    public function cancel(Request $request, Order $order)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422);
        $order->update(['status' => 'cancelled', 'processing_status' => 'stopped', 'cancelled_at' => now()]);
        $order->recordEvent('cancel', 'لغو سفارش', $data['reason']);
        return back()->with('success', 'سفارش لغو شد.');
    }

    public function refund(Request $request, Order $order)
    {
        $data = $request->validate([
            'credits' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $creditWallet = app(CreditWalletService::class);

        DB::transaction(function () use ($order, $data, $creditWallet) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $remaining = max(0, $locked->final_credits - $locked->refunded_credits);
            abort_if($data['credits'] > $remaining, 422, 'مقدار بازپرداخت بیشتر از اعتبار قابل بازگشت است.');
            abort_if(!$locked->user_id, 422, 'این سفارش کاربر معتبری برای بازگشت اعتبار ندارد.');

            $trackedPromotionalUsed = (int) $locked->promotional_credits_used;
            $trackedPaidUsed = (int) $locked->paid_credits_used;
            $hasSourceTracking = ($trackedPromotionalUsed + $trackedPaidUsed) > 0;
            // سفارش‌های پیش از راه‌اندازی کیف پول تفکیک‌شده، اعتبارشان خریداری‌شده
            // در نظر گرفته می‌شود تا هنگام بازپرداخت، اعتبار هدیه‌ی جدید ایجاد نشود.
            $paidUsed = $hasSourceTracking ? $trackedPaidUsed : (int) $locked->final_credits;
            $promotionalRemaining = max(0, $trackedPromotionalUsed - (int) $locked->promotional_credits_refunded);
            $paidRemaining = max(0, $paidUsed - (int) $locked->paid_credits_refunded);
            $promotionalRefund = min($data['credits'], $promotionalRemaining);
            $paidRefund = $data['credits'] - $promotionalRefund;
            abort_if($paidRefund > $paidRemaining, 422, 'منبع اعتبار این سفارش برای بازپرداخت معتبر نیست.');

            $newRefunded = $locked->refunded_credits + $data['credits'];
            $full = $newRefunded >= $locked->final_credits;
            $user = User::query()->findOrFail($locked->user_id);
            $creditWallet->restore($user, $promotionalRefund, $paidRefund, $hasSourceTracking);
            app(FinanceCaseLedgerService::class)->refundOrder($locked, (int) $data['credits']);
            $locked->update([
                'refunded_credits' => $newRefunded,
                'promotional_credits_refunded' => (int) $locked->promotional_credits_refunded + $promotionalRefund,
                'paid_credits_refunded' => (int) $locked->paid_credits_refunded + $paidRefund,
                'payment_status' => $full ? 'refunded' : 'partially_refunded',
                'refunded_at' => now(),
            ]);
            $locked->recordEvent('refund', $full ? 'بازپرداخت کامل' : 'بازپرداخت جزئی', $data['reason'], [
                'credits' => $data['credits'],
                'promotional_credits' => $promotionalRefund,
                'paid_credits' => $paidRefund,
            ]);
        });

        $order->refresh()->load('user');
        if ($order->user?->phone) app(SmsEventService::class)->send('refund_success', $order->user->phone, [
            'name'=>$order->user->name, 'phone'=>$order->user->phone, 'order_number'=>$order->order_number,
            'amount'=>(string)$data['credits'], 'balance'=>(string)$order->user->fresh()->tokens,
        ]);

        return back()->with('success', 'اعتبار با موفقیت به کاربر بازگردانده شد.');
    }

    public function note(Request $request, Order $order)
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:3000']]);
        $order->update($data);
        $order->recordEvent('note', 'یادداشت داخلی بروزرسانی شد', $data['admin_note'] ?: 'یادداشت پاک شد.');
        return back()->with('success', 'یادداشت سفارش ذخیره شد.');
    }

    public function refunds(Request $request)
    {
        $orders = Order::query()->with(['user', 'product'])
            ->where(fn ($q) => $q->whereNotNull('cancelled_at')->orWhere('refunded_credits', '>', 0))
            ->latest('updated_at')->paginate(20)->withQueryString();
        $stats = [
            'total' => Order::where(fn ($q) => $q->whereNotNull('cancelled_at')->orWhere('refunded_credits', '>', 0))->count(),
            'cancelled' => Order::whereNotNull('cancelled_at')->count(),
            'partial' => Order::where('payment_status', 'partially_refunded')->count(),
            'full' => Order::where('payment_status', 'refunded')->count(),
            'credits' => (int) Order::sum('refunded_credits'),
        ];
        return view('admin.orders.refunds', compact('orders', 'stats'));
    }

    public function analytics(Request $request)
    {
        $days = min(365, max(7, $request->integer('days', 30)));
        $from = now()->subDays($days - 1)->startOfDay();
        $orders = Order::with('product:id,name_fa')->where('created_at', '>=', $from)->get();
        $daily = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'));
        $chart = collect(range(0, $days - 1))->map(function ($offset) use ($from, $daily) {
            $date = $from->copy()->addDays($offset);
            $items = $daily->get($date->format('Y-m-d'), collect());
            return ['date' => $date->format('m/d'), 'orders' => $items->count(), 'credits' => (int) $items->sum('final_credits')];
        });
        $stats = [
            'total' => $orders->count(),
            'credits' => (int) $orders->where('payment_status', 'paid')->sum('final_credits'),
            'success_rate' => $orders->count() ? round($orders->where('processing_status', 'completed')->count() * 100 / $orders->count(), 1) : 0,
            'avg_duration' => (int) round($orders->whereNotNull('processing_duration_ms')->avg('processing_duration_ms') ?? 0),
            'refund_rate' => $orders->count() ? round($orders->where('refunded_credits', '>', 0)->count() * 100 / $orders->count(), 1) : 0,
        ];
        $products = $orders->groupBy(fn ($o) => $o->product?->name_fa ?? 'محصول حذف‌شده')
            ->map(fn ($items, $name) => ['name' => $name, 'count' => $items->count(), 'credits' => (int) $items->sum('final_credits')])
            ->sortByDesc('count')->take(7)->values();
        $statuses = $orders->groupBy('processing_status')->map->count();

        return view('admin.orders.analytics', compact('days', 'chart', 'stats', 'products', 'statuses'));
    }
}
