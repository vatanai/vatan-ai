<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SmsEventService;

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

    private function listing(Request $request, string $view)
    {
        $query = Order::query()->with(['user:id,name,last_name,email,phone', 'product:id,name_fa,product_code']);

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
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status));
        $query->when($request->filled('processing_status'), fn ($q) => $q->where('processing_status', $request->processing_status));
        $query->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));

        $orders = $query->latest()->paginate(20)->withQueryString();
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

        DB::transaction(function () use ($order, $data) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $remaining = max(0, $locked->final_credits - $locked->refunded_credits);
            abort_if($data['credits'] > $remaining, 422, 'مقدار بازپرداخت بیشتر از اعتبار قابل بازگشت است.');
            abort_if(!$locked->user_id, 422, 'این سفارش کاربر معتبری برای بازگشت اعتبار ندارد.');

            $newRefunded = $locked->refunded_credits + $data['credits'];
            $full = $newRefunded >= $locked->final_credits;
            $locked->user()->increment('tokens', $data['credits']);
            $locked->update([
                'refunded_credits' => $newRefunded,
                'payment_status' => $full ? 'refunded' : 'partially_refunded',
                'refunded_at' => now(),
            ]);
            $locked->recordEvent('refund', $full ? 'بازپرداخت کامل' : 'بازپرداخت جزئی', $data['reason'], ['credits' => $data['credits']]);
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
