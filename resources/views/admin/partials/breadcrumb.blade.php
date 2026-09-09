@php
    $adminBreadcrumb = [];
    $addBreadcrumb = static function (string $label) use (&$adminBreadcrumb): void {
        $adminBreadcrumb[] = ['label' => $label];
    };

    if (request()->is('admin/home-builder/galleries*')) {
        $addBreadcrumb('مدیریت وبسایت');
        $addBreadcrumb('مدیریت سایت');
        $addBreadcrumb('صفحه اصلی');
        if (isset($gallery) && $gallery) {
            $addBreadcrumb('گالری: ' . $gallery->title);
        }
    } elseif (request()->is('admin/home-builder*')) {
        $addBreadcrumb('مدیریت وبسایت');
        $addBreadcrumb('مدیریت اپلیکیشن');
        $addBreadcrumb('مدیریت صفحه هوم');
    } elseif (request()->is('admin/pages*') || request()->is('admin/content/pages')) {
        $addBreadcrumb('مدیریت وبسایت');
        $addBreadcrumb('مدیریت سایت');
        $addBreadcrumb('مدیریت صفحات سایت');
    } elseif (request()->is('admin/articles*') || request()->is('admin/article-categories*') || request()->is('admin/article-comments*')) {
        $addBreadcrumb('مدیریت وبسایت');
        $addBreadcrumb('مدیریت سایت');
        $addBreadcrumb('مدیریت مقالات');
        $addBreadcrumb(request()->is('admin/article-categories*') ? 'دسته‌بندی مقالات' : (request()->is('admin/article-comments*') ? 'دیدگاه مقالات' : 'فهرست مقالات'));
    } elseif (request()->is('admin/support*')) {
        $addBreadcrumb('مدیریت وبسایت');
        $addBreadcrumb('مدیریت سایت');
        $addBreadcrumb('پشتیبانی و تیکت‌ها');
    } elseif (request()->is('admin/products*') || request()->is('admin/categories*') || request()->is('admin/lab*')) {
        $addBreadcrumb('مدیریت محصولات');
        if (request()->is('admin/categories*')) {
            $addBreadcrumb('دسته‌بندی‌ها');
        } elseif (request()->is('admin/lab*')) {
            $addBreadcrumb('آزمایشگاه');
        } elseif (request()->is('admin/products/create*')) {
            $addBreadcrumb('ثبت محصول عکس');
        } elseif (request()->is('admin/products/videos*')) {
            $addBreadcrumb('ثبت محصول ویدیو');
        } else {
            $addBreadcrumb('لیست محصولات');
        }
    } elseif (request()->is('admin/service-credits*')) {
        $addBreadcrumb('اعتبار سرویس‌ها');
    } elseif (request()->is('admin/users*')) {
        $addBreadcrumb('کاربران');
        if (request()->is('admin/users/gallery*')) {
            $addBreadcrumb('گالری شخصی کاربران');
        } elseif (request()->is('admin/users/tokens*')) {
            $addBreadcrumb('مدیریت اعتبار');
        } elseif (request()->is('admin/users/smart-lists*')) {
            $addBreadcrumb('لیست‌های هوشمند');
        } else {
            $addBreadcrumb('لیست کاربران');
        }
    } elseif (request()->is('admin/marketing-technology*')) {
        $addBreadcrumb('تکنولوژی مارکتینگ');
        $marketingLabels = [
            'content-calendar' => 'تقویم و صف محتوا',
            'scenarios' => 'سناریوهای کامنت و دایرکت',
            'inbox' => 'صندوق گفتگوها',
            'reports' => 'گزارش و تحلیل',
            'costs' => 'مرکز هزینه',
            'integrations' => 'اتصال‌ها و سلامت سرویس',
            'logs' => 'لاگ عملیات',
        ];
        $marketingLabel = 'مرکز فرماندهی';
        foreach ($marketingLabels as $slug => $label) {
            if (request()->is('admin/marketing-technology/' . $slug)) {
                $marketingLabel = $label;
                break;
            }
        }
        $addBreadcrumb($marketingLabel);
    } elseif (request()->is('admin/finance*')) {
        $addBreadcrumb('حسابداری وطن');
    } elseif (request()->is('admin/ai-models*')) {
        $addBreadcrumb('مدل‌های هوشمند');
        $addBreadcrumb(request()->is('admin/ai-models/providers*') ? 'ارائه‌دهندگان' : (request()->is('admin/ai-models/create') ? 'افزودن مدل جدید' : 'مدل‌ها'));
    } elseif (request()->is('admin/referrals*')) {
        $addBreadcrumb('فروش و مارکتینگ');
        if (request()->is('admin/referrals/settings')) {
            $addBreadcrumb('تنظیمات همکاری در فروش');
        } elseif (request()->is('admin/referrals/conversions')) {
            $addBreadcrumb('فهرست دعوت‌ها');
        } elseif (request()->is('admin/referrals/rewards')) {
            $addBreadcrumb('گزارش پاداش‌ها');
        } elseif (request()->is('admin/referrals/visits')) {
            $addBreadcrumb('بازدید لینک‌ها');
        } elseif (request()->is('admin/referrals/reviews')) {
            $addBreadcrumb('صف بررسی');
        } else {
            $addBreadcrumb('همکاری در فروش');
        }
    } elseif (request()->is('admin/plans*')) {
        $addBreadcrumb('فروش و مارکتینگ');
        $addBreadcrumb(request()->is('admin/plans/create') ? 'افزودن پلن' : 'پلن‌ها');
    } elseif (request()->is('admin/orders*')) {
        $addBreadcrumb('فروش و مارکتینگ');
        $addBreadcrumb(request()->is('admin/orders/plan-purchases') ? 'خرید پلن‌ها و پرداخت‌ها' : 'سفارش‌ها');
    } elseif (request()->is('admin/discounts*')) {
        $addBreadcrumb('فروش و مارکتینگ');
        $addBreadcrumb('تخفیف‌ها');
    } elseif (request()->is('admin/growth*')) {
        $addBreadcrumb('رشد');
        $addBreadcrumb(request()->is('admin/growth/users*') ? 'کاربران رشد' : (request()->is('admin/growth/links*') ? 'لینک‌های رشد' : 'مرکز رشد'));
    } elseif (request()->is('admin/settings*')) {
        $addBreadcrumb('تنظیمات');
        if (request()->is('admin/settings/system')) {
            $addBreadcrumb('تنظیمات سیستم');
        } elseif (request()->is('admin/settings/backup*')) {
            $addBreadcrumb('پشتیبان‌گیری');
        } elseif (request()->is('admin/settings/admins*')) {
            $addBreadcrumb('مدیریت ادمین‌ها');
        } elseif (request()->is('admin/settings/new-user-gift')) {
            $addBreadcrumb('هدیه ثبت‌نام کاربران جدید');
        }
    } elseif (request()->is('admin/telegram*') || request()->is('admin/video-studio*')) {
        $addBreadcrumb('استودیو تولید');
        $addBreadcrumb(request()->is('admin/telegram*') ? 'تلگرام' : config('video_studio.admin_label', 'تولید محتوای خودکار'));
    } elseif (request()->is('admin/dashboard/crm')) {
        $addBreadcrumb('تنظیمات');
        $addBreadcrumb('سیستم مدیریت پروژه');
    } elseif (request()->is('admin/dashboard')) {
        $addBreadcrumb(request()->route('section') === 'crm' ? 'سیستم مدیریت پروژه' : 'مرکز فرماندهی');
    } else {
        $addBreadcrumb('مرکز فرماندهی');
    }
@endphp

<nav class="tb-breadcrumb flex-1 max-[480px]:overflow-hidden" aria-label="مسیر صفحه">
    <span class="breadcrumb-ancestor max-[480px]:hidden">پنل مدیریت</span>
    @foreach($adminBreadcrumb as $crumb)
        <i class="fa-solid fa-angle-left breadcrumb-separator {{ $loop->first ? 'max-[480px]:hidden' : '' }}" aria-hidden="true"></i>
        <span class="{{ $loop->last ? 'active-crumb' : 'breadcrumb-ancestor' }} {{ !$loop->last ? 'max-[480px]:hidden' : '' }}" @if($loop->last) id="breadcrumb" @endif>{{ $crumb['label'] }}</span>
    @endforeach
</nav>
