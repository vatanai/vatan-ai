<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServiceCreditOverviewService;
use App\Services\ServiceCreditTransactionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const SECTIONS = [
        'crm' => 'crm',
        'attendance' => 'misc',
        'products' => 'products-dashboard',
        'productslist' => 'products-list',
        'createproduct' => 'products-create',
        'categories' => 'products-categories',
        'pricing' => 'products-pricing',
        'ai' => 'ai-hub',
        'models' => 'ai-models',
        'prompts' => 'ai-prompts',
        'logs' => 'ai-logs',
    ];

    private function viewData(): array
    {
        return [
            'topProds' => [], 'products' => [], 'cats' => [], 'models' => [],
            'actions' => [], 'cats2' => [], 'discounts' => [], 'pricingData' => [],
        ];
    }

    private function sectionView(?string $section): ?string
    {
        return $section && isset(self::SECTIONS[$section])
            ? 'admin.partials.pages.' . self::SECTIONS[$section]
            : null;
    }

    /**
     * صفحه‌ی فرود پنل مدیریت؛ عمداً فقط از داده‌های ثابت و تنظیمات پروژه ساخته می‌شود
     * تا قبل از ورود به مرکز فرماندهی، هیچ سرویس سنگین یا کوئری گزارشی اجرا نشود.
     */
    public function quickAccess()
    {
        $financeShortcuts = collect(config('finance.sections', []))
            ->take(4)
            ->map(function (string $label, string $section): array {
                $url = $section === 'cases'
                    ? route('admin.finance.cases.index')
                    : route('admin.finance.show', ['section' => $section]);

                return [
                    'title' => $label,
                    'url' => $url,
                    'icon' => match ($section) {
                        'overview' => 'fa-chart-pie',
                        'cases' => 'fa-folder-open',
                        'transactions' => 'fa-arrow-right-arrow-left',
                        default => 'fa-calculator',
                    },
                ];
            })
            ->values()
            ->all();

        return view('admin.quick-access', [
            'quickAccessCards' => [
                [
                    'title' => 'کاربران',
                    'description' => 'مدیریت کاربران، گالری و اعتبار حساب‌ها',
                    'icon' => 'fa-users',
                    'url' => route('admin.users.index'),
                    'priority' => true,
                    'shortcuts' => [
                        ['title' => 'لیست کاربران', 'url' => route('admin.users.index'), 'icon' => 'fa-user-group'],
                        ['title' => 'گالری شخصی کاربران', 'url' => route('admin.users.gallery.index'), 'icon' => 'fa-images'],
                        ['title' => 'کارکتر شیت کاربران', 'url' => route('admin.users.face-profiles.index'), 'icon' => 'fa-id-card'],
                        ['title' => 'مدیریت اعتبار', 'url' => route('admin.users.tokens'), 'icon' => 'fa-coins'],
                    ],
                ],
                [
                    'title' => 'مدیریت محصولات',
                    'description' => 'کنترل محصولات عکس، ویدیو، آزمایشگاه و دسته‌بندی‌ها',
                    'icon' => 'fa-box-open',
                    'url' => route('admin.products'),
                    'priority' => true,
                    'shortcuts' => [
                        ['title' => 'لیست محصولات عکس', 'url' => route('admin.products'), 'icon' => 'fa-image'],
                        ['title' => 'لیست محصولات ویدیو', 'url' => route('admin.products.videos'), 'icon' => 'fa-video'],
                        ['title' => 'ثبت محصول عکس', 'url' => route('admin.products.create'), 'icon' => 'fa-plus'],
                        ['title' => 'ثبت محصول ویدیو — نسخه جدید', 'url' => route('admin.products.video.v2.create'), 'icon' => 'fa-wand-magic-sparkles'],
                    ],
                ],
                [
                    'title' => 'فروش و مارکتینگ',
                    'description' => 'مدیریت پلن‌ها، سفارش‌ها، تخفیف‌ها و همکاری در فروش',
                    'icon' => 'fa-bullseye',
                    'url' => route('admin.plans.index'),
                    'priority' => true,
                    'shortcuts' => [
                        ['title' => 'لیست پلن‌ها', 'url' => route('admin.plans.index'), 'icon' => 'fa-layer-group'],
                        ['title' => 'همه سفارشات', 'url' => route('admin.orders.index'), 'icon' => 'fa-receipt'],
                        ['title' => 'خرید پلن‌ها و پرداخت‌ها', 'url' => route('admin.orders.plan-purchases'), 'icon' => 'fa-cart-shopping'],
                        ['title' => 'نمای کلی', 'url' => route('admin.referrals.overview'), 'icon' => 'fa-share-nodes'],
                    ],
                ],
                [
                    'title' => 'مرکز فرماندهی',
                    'description' => 'نمای کلی عملکرد، اعتبار سرویس‌ها و فعالیت‌های پنل',
                    'icon' => 'fa-bolt-lightning',
                    'url' => route('admin.dashboard', ['center' => 1]),
                    'shortcuts' => [
                        ['title' => 'مرکز فرماندهی', 'url' => route('admin.dashboard', ['center' => 1]), 'icon' => 'fa-gauge-high'],
                        ['title' => 'سیستم مدیریت پروژه', 'url' => route('admin.dashboard', ['section' => 'crm']), 'icon' => 'fa-diagram-project'],
                        ['title' => 'حضور و غیاب', 'url' => route('admin.dashboard', ['section' => 'attendance']), 'icon' => 'fa-calendar-check'],
                        ['title' => 'اعتبار سرویس‌ها', 'url' => route('admin.service-credits.index'), 'icon' => 'fa-coins'],
                    ],
                ],
                [
                    'title' => 'اعتبار سرویس‌ها',
                    'description' => 'پایش موجودی پرووایدرها و بررسی تراکنش‌های اعتبار',
                    'icon' => 'fa-gauge-high',
                    'url' => route('admin.service-credits.providers'),
                    'shortcuts' => [
                        ['title' => 'نمای کلی اعتبار', 'url' => route('admin.service-credits.index'), 'icon' => 'fa-gauge-high'],
                        ['title' => 'میزان اعتبار پرووایدرها', 'url' => route('admin.service-credits.providers'), 'icon' => 'fa-battery-three-quarters'],
                        ['title' => 'بررسی تراکنش‌ها', 'url' => route('admin.service-credits.transactions'), 'icon' => 'fa-arrow-right-arrow-left'],
                        ['title' => 'مدیریت اعتبار کاربران', 'url' => route('admin.users.tokens'), 'icon' => 'fa-user-tag'],
                    ],
                ],
                [
                    'title' => 'استودیو تولید',
                    'description' => 'مدیریت فرایند تولید خودکار محتوای تصویری',
                    'icon' => 'fa-photo-film',
                    'url' => route('admin.video-studio.experimental'),
                    'shortcuts' => [
                        ['title' => config('video_studio.admin_label', 'تولید محتوای خودکار'), 'url' => route('admin.video-studio.experimental'), 'icon' => 'fa-clapperboard'],
                        ['title' => 'داشبورد محصولات', 'url' => route('admin.products.dashboard'), 'icon' => 'fa-chart-line'],
                        ['title' => 'ثبت محصول ویدیو — نسخه جدید', 'url' => route('admin.products.video.v2.create'), 'icon' => 'fa-wand-magic-sparkles'],
                        ['title' => 'ثبت محصول ویدیو — نسخه پشتیبان', 'url' => route('admin.products.video.create'), 'icon' => 'fa-film'],
                    ],
                ],
                [
                    'title' => 'تکنولوژی مارکتینگ',
                    'description' => 'مدیریت تقویم محتوا، سناریوها، گفتگوها و گزارش‌ها',
                    'icon' => 'fa-network-wired',
                    'url' => route('admin.marketing-technology.index'),
                    'shortcuts' => [
                        ['title' => 'مرکز فرماندهی', 'url' => route('admin.marketing-technology.index'), 'icon' => 'fa-gauge-high'],
                        ['title' => 'تقویم و صف محتوا', 'url' => route('admin.marketing-technology.content-calendar'), 'icon' => 'fa-calendar-days'],
                        ['title' => 'صندوق گفتگوها', 'url' => route('admin.marketing-technology.inbox'), 'icon' => 'fa-inbox'],
                        ['title' => 'گزارش و تحلیل', 'url' => route('admin.marketing-technology.reports'), 'icon' => 'fa-chart-line'],
                    ],
                ],
                [
                    'title' => 'حسابداری وطن',
                    'description' => 'نمای مالی، پرونده خریدها و جریان تراکنش‌ها',
                    'icon' => 'fa-chart-pie',
                    'url' => route('admin.finance.show', ['section' => 'overview']),
                    'shortcuts' => $financeShortcuts,
                ],
                [
                    'title' => 'مدل‌های هوشمند',
                    'description' => 'مدیریت ارائه‌دهندگان و مدل‌های هوش مصنوعی',
                    'icon' => 'fa-microchip',
                    'url' => route('admin.ai-models.index'),
                    'shortcuts' => [
                        ['title' => 'ارائه‌دهندگان', 'url' => route('admin.ai-models.providers'), 'icon' => 'fa-server'],
                        ['title' => 'مدل‌ها', 'url' => route('admin.ai-models.index'), 'icon' => 'fa-brain'],
                        ['title' => 'افزودن مدل جدید', 'url' => route('admin.ai-models.create'), 'icon' => 'fa-plus'],
                        ['title' => 'تاریخچه آزمایش مدل‌ها', 'url' => route('admin.product-tests.history'), 'icon' => 'fa-flask'],
                    ],
                ],
                [
                    'title' => 'مدیریت وبسایت',
                    'description' => 'مدیریت صفحات، اپلیکیشن، مقالات و پیامک‌ها',
                    'icon' => 'fa-globe',
                    'url' => route('admin.pages.index'),
                    'shortcuts' => [
                        ['title' => 'مدیریت صفحات سایت', 'url' => route('admin.pages.index'), 'icon' => 'fa-file-lines'],
                        ['title' => 'فهرست مقالات', 'url' => route('admin.articles.index'), 'icon' => 'fa-newspaper'],
                        ['title' => 'مدیریت صفحه هوم', 'url' => route('admin.home-builder.index'), 'icon' => 'fa-house'],
                        ['title' => 'داشبورد پیامک', 'url' => route('admin.sms.index'), 'icon' => 'fa-message'],
                    ],
                ],
                [
                    'title' => 'تنظیمات',
                    'description' => 'تنظیمات سیستم، ادمین‌ها، تلگرام و پشتیبان‌گیری',
                    'icon' => 'fa-gear',
                    'url' => route('admin.settings.system'),
                    'shortcuts' => [
                        ['title' => 'سیستم مدیریت پروژه', 'url' => route('admin.dashboard', ['section' => 'crm']), 'icon' => 'fa-diagram-project'],
                        ['title' => 'مدیریت ادمین‌ها', 'url' => route('admin.settings.admins'), 'icon' => 'fa-user-shield'],
                        ['title' => 'هدیه کاربران جدید', 'url' => route('admin.settings.new-user-gift'), 'icon' => 'fa-gift'],
                        ['title' => 'تنظیمات سیستم', 'url' => route('admin.settings.system'), 'icon' => 'fa-sliders'],
                    ],
                ],
            ],
        ]);
    }

    public function index(
        Request $request,
        $section = null
    )
    {
        $sectionView = $this->sectionView($section);

        // ورود مستقیم به داشبورد همیشه از مرکز فرماندهی سبک «دسترسی سریع» شروع می‌شود.
        // پارامترهای center و mobile_center برای باز کردن مرکز فرماندهی قبلی حفظ شده‌اند.
        if ($section === null && !$request->boolean('center') && !$request->boolean('mobile_center')) {
            return redirect()->route('admin.quick-access');
        }

        // بخش‌های غیر از داشبورد اصلی نباید هزینهٔ آمار و اعتبار را متحمل شوند.
        if ($sectionView) {
            return view('admin.dashboard', [
                ...$this->viewData(),
                'dashboardSection' => $section,
            ]);
        }

        // در موبایل ورود اولیه فقط پوسته‌ی سبک را نمایش می‌دهد؛ مرکز فرماندهی
        // با کلیک کاربر و پارامتر mobile_center بارگذاری می‌شود.
        if ($this->isMobileRequest($request) && !$request->boolean('mobile_center') && !$request->boolean('center')) {
            return view('admin.dashboard', [
                ...$this->viewData(),
                'dashboardSection' => null,
                'mobileShell' => true,
            ]);
        }

        // سرویس‌های سنگین فقط پس از انتخاب مرکز فرماندهی resolve می‌شوند.
        $creditData = app(ServiceCreditOverviewService::class)->get(true);
        $creditTransactions = app(ServiceCreditTransactionReport::class)->latest(5, (float) ($creditData['exchange']['rate'] ?? 0));

        return view('admin.dashboard', [
            ...$this->viewData(),
            'dashboardSection' => null,
            'creditOverview' => $creditData,
            'creditTransactions' => $creditTransactions,
        ]);
    }

    public function fragment(string $section)
    {
        if ($section === 'home') {
            $creditData = app(ServiceCreditOverviewService::class)->get(true);
            $creditTransactions = app(ServiceCreditTransactionReport::class)->latest(5, (float) ($creditData['exchange']['rate'] ?? 0));

            return response(view('admin.partials.pages.dashboard-main', [
                ...$this->viewData(),
                'dashboardSection' => null,
                'creditOverview' => $creditData,
                'creditTransactions' => $creditTransactions,
            ])->render())->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $view = $this->sectionView($section);
        abort_unless($view, 404);

        $html = view($view, [
            ...$this->viewData(),
            'dashboardSection' => $section,
        ])->render();

        if (in_array($section, ['crm', 'attendance'], true)) {
            $html .= view('admin.partials.scripts.shamsi-calendar')->render();
            $html .= view('admin.partials.scripts.dashboard-main-js')->render();
            $html .= '<script src="' . asset('admin/js/crm-api.js') . '"></script>';
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** جست‌وجوی یکپارچه‌ی هدر پنل: مسیرهای مدیریتی، کاربران و محصولات. */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $navigation = [
            ['label' => 'مرکز فرماندهی', 'meta' => 'داشبورد', 'url' => route('admin.dashboard', ['center' => 1]), 'icon' => 'fa-gauge-high', 'keywords' => 'داشبورد مرکز فرماندهی'],
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

    private function isMobileRequest(Request $request): bool
    {
        $clientHint = strtolower((string) $request->header('Sec-CH-UA-Mobile', ''));
        if ($clientHint === '?1' || $clientHint === '1') {
            return true;
        }

        return (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', (string) $request->userAgent());
    }
}
