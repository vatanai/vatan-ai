<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generation;
use App\Models\Product;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditSynchronizer;
use App\Services\ServiceCreditTransactionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        ServiceCreditOverviewService $creditOverview,
        ServiceCreditSynchronizer $creditSynchronizer,
        ServiceCreditTransactionReport $transactionReport,
        $section = null
    )
    {
        try {
            $stats = [
                'users_count'       => User::count(),
                'generations_count' => Generation::count(),
                'products_count'    => Product::count(),
            ];
        } catch (\Throwable $e) {
            $stats = ['users_count' => 0, 'generations_count' => 0, 'products_count' => 0];
        }

        $creditSynchronizer->sync();
        $creditData = $creditOverview->get(true);
        $creditTransactions = $transactionReport->latest(5, (float) ($creditData['exchange']['rate'] ?? 0));

        return view('admin.dashboard', [
            'stats'    => $stats,
            'topProds' => [],
            'products' => [],
            'cats'     => [],
            'models'   => [],
            'actions'  => [],
            'creditOverview' => $creditData,
            'creditTransactions' => $creditTransactions,
        ]);
    }

    /** جست‌وجوی یکپارچه‌ی هدر پنل: مسیرهای مدیریتی، کاربران و محصولات. */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $navigation = [
            ['label' => 'مرکز فرماندهی', 'meta' => 'داشبورد', 'url' => route('admin.dashboard'), 'icon' => 'fa-gauge-high', 'keywords' => 'داشبورد مرکز فرماندهی'],
            ['label' => 'لیست کاربران', 'meta' => 'کاربران', 'url' => route('admin.users.index'), 'icon' => 'fa-users', 'keywords' => 'کاربر کاربران'],
            ['label' => 'مدیریت اعتبار', 'meta' => 'کاربران', 'url' => route('admin.users.tokens'), 'icon' => 'fa-coins', 'keywords' => 'اعتبار توکن موجودی'],
            ['label' => 'گالری شخصی کاربران', 'meta' => 'کاربران', 'url' => route('admin.users.gallery.index'), 'icon' => 'fa-images', 'keywords' => 'گالری ورودی خروجی'],
            ['label' => 'لیست محصولات', 'meta' => 'مدیریت محصولات', 'url' => route('admin.products'), 'icon' => 'fa-box-open', 'keywords' => 'محصول محصولات'],
            ['label' => 'ثبت محصول جدید', 'meta' => 'مدیریت محصولات', 'url' => route('admin.products.create'), 'icon' => 'fa-plus', 'keywords' => 'محصول ثبت ساخت'],
            ['label' => 'خرید پلن‌ها و پرداخت‌ها', 'meta' => 'سفارش‌ها', 'url' => route('admin.orders.plan-purchases'), 'icon' => 'fa-cart-shopping', 'keywords' => 'خرید سفارش پرداخت پلن'],
            ['label' => 'همکاری در فروش', 'meta' => 'رفرال', 'url' => route('admin.referrals.overview'), 'icon' => 'fa-share-nodes', 'keywords' => 'رفرال دعوت همکاری فروش'],
            ['label' => 'بازدید لینک‌های دعوت', 'meta' => 'رفرال', 'url' => route('admin.referrals.visits'), 'icon' => 'fa-arrow-pointer', 'keywords' => 'رفرال کلیک بازدید لینک دعوت'],
            ['label' => 'ثبت‌نام‌های رفرالی', 'meta' => 'رفرال', 'url' => route('admin.referrals.conversions'), 'icon' => 'fa-user-check', 'keywords' => 'رفرال ثبت نام تبدیل'],
            ['label' => 'تنظیمات هدیه کاربران جدید', 'meta' => 'تنظیمات', 'url' => route('admin.settings.new-user-gift'), 'icon' => 'fa-gift', 'keywords' => 'هدیه اعتبار ثبت نام'],
            ['label' => 'اعتبار سرویس‌ها', 'meta' => 'زیرساخت', 'url' => route('admin.service-credits.index'), 'icon' => 'fa-bolt', 'keywords' => 'اعتبار سرویس مصرف'],
        ];

        $needle = mb_strtolower($query, 'UTF-8');
        $results = collect($navigation)
            ->filter(fn (array $item): bool => str_contains(mb_strtolower($item['label'].' '.$item['meta'].' '.$item['keywords'], 'UTF-8'), $needle))
            ->map(fn (array $item): array => [
                'type' => 'page', 'label' => $item['label'], 'meta' => $item['meta'],
                'url' => $item['url'], 'icon' => $item['icon'],
            ])
            ->values();

        $userResults = User::query()
            ->where(function ($builder) use ($query): void {
                $term = "%{$query}%";
                $builder->where('name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('referral_code', 'like', $term);
            })
            ->latest('id')
            ->limit(6)
            ->get(['id', 'name', 'last_name', 'phone', 'email'])
            ->map(fn (User $user): array => [
                'type' => 'user',
                'label' => trim($user->name.' '.$user->last_name) ?: 'کاربر بدون نام',
                'meta' => $user->phone ?: ($user->email ?: 'کاربر شماره '.$user->id),
                'url' => route('admin.users.index', ['show_user' => $user->id]),
                'icon' => 'fa-user',
            ]);

        $productResults = Product::query()
            ->where(function ($builder) use ($query): void {
                $term = "%{$query}%";
                $builder->where('name_fa', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('product_code', 'like', $term);
            })
            ->latest('id')
            ->limit(6)
            ->get(['id', 'name_fa', 'name_en', 'product_code'])
            ->map(fn (Product $product): array => [
                'type' => 'product',
                'label' => $product->name_fa ?: ($product->name_en ?: 'محصول '.$product->id),
                'meta' => $product->product_code ? 'کد محصول '.$product->product_code : 'مدیریت محصولات',
                'url' => route('admin.products.show', $product),
                'icon' => 'fa-box',
            ]);

        return response()->json([
            'data' => $results->concat($userResults)->concat($productResults)->take(15)->values(),
        ]);
    }
}
