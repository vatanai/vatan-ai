<aside class="sidebar" id="sidebar" dir="rtl">

  {{-- ▌ LOGO --}}
  <div class="sb-logo">
    <a href="/admin/dashboard" class="sb-logo-mark">
      <img src="/assets/img/icon_vatan.svg" alt="وطن">
    </a>
    <div class="sb-logo-texts">
      <img src="/assets/img/vatan-logo.svg" alt="وطن" class="sb-logo-img">
      <div class="sb-logo-sub">Admin Panel</div>
    </div>
  </div>

  {{-- ▌ USER PROFILE (top) --}}
  <div class="sb-user" style="margin-top:0; border-top:none; border-bottom:1px solid var(--border);">
    <div class="sb-av">م</div>
    <div class="sb-user-info">
      <div class="sb-uname">محسن آقاجانی</div>
      <div class="sb-urole">مدیر کل</div>
    </div>
    <div style="width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 6px #22c55e;flex-shrink:0;margin-right:auto;"></div>
  </div>

  {{-- ▌ NAV --}}
  <nav style="flex:1; padding:8px 0;">

    <div class="sb-section">اصلی</div>

    {{-- مرکز فرماندهی --}}
    <div class="nav-item">
      <a href="{{ route('admin.dashboard') }}"
         class="nav-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
        <span class="nav-label">مرکز فرماندهی</span>
      </a>
    </div>

    <div class="sb-section">مدیریت</div>

    {{-- محصولات --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/products*') ? 'active' : '' }}"
           onclick="toggleSub('sub-products','chev-products')">
        <div class="nav-icon"><i class="fa-solid fa-box-open"></i></div>
        <span class="nav-label">محصولات</span>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/products*') ? 'open' : '' }}"
           id="chev-products"></i>
      </div>
    </div>
    <div id="sub-products" class="submenu {{ request()->is('admin/products*') ? 'open' : '' }}"
         data-chev-id="chev-products">
      <div class="sub-track">
        <div class="sub-item {{ request()->is('admin/products') && !request()->is('admin/products/*') ? 'active' : '' }}"
             onclick="window.location='/admin/products'">
          <span class="sub-dot"></span>
          <span class="sub-label">لیست محصولات</span>
        </div>
        <div class="sub-item {{ request()->is('admin/products/create') ? 'active' : '' }}"
             onclick="window.location='/admin/products/create'">
          <span class="sub-dot"></span>
          <span class="sub-label">ثبت محصول جدید</span>
        </div>
        <div class="sub-item {{ request()->is('admin/products/dashboard') ? 'active' : '' }}"
             onclick="window.location='/admin/products/dashboard'">
          <span class="sub-dot"></span>
          <span class="sub-label">داشبورد محصولات</span>
        </div>
        <div class="sub-item {{ request()->is('admin/products/categories') ? 'active' : '' }}"
             onclick="window.location='/admin/products/categories'">
          <span class="sub-dot"></span>
          <span class="sub-label">دسته‌بندی‌ها</span>
        </div>
        <div class="sub-item {{ request()->is('admin/products/pricing') ? 'active' : '' }}"
             onclick="window.location='/admin/products/pricing'">
          <span class="sub-dot"></span>
          <span class="sub-label">قیمت‌گذاری</span>
        </div>
      </div>
    </div>

    {{-- سفارشات --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}"
           onclick="toggleSub('sub-orders','chev-orders')">
        <div class="nav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <span class="nav-label">سفارشات</span>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/orders*') ? 'open' : '' }}"
           id="chev-orders"></i>
      </div>
    </div>
    <div id="sub-orders" class="submenu {{ request()->is('admin/orders*') ? 'open' : '' }}"
         data-chev-id="chev-orders">
      <div class="sub-track">
        <div class="sub-item {{ request()->is('admin/orders') && !request()->is('admin/orders/*') ? 'active' : '' }}"
             onclick="window.location='/admin/orders'">
          <span class="sub-dot"></span>
          <span class="sub-label">لیست سفارشات</span>
        </div>
        <div class="sub-item {{ request()->is('admin/orders/analytics') ? 'active' : '' }}"
             onclick="window.location='/admin/orders/analytics'">
          <span class="sub-dot"></span>
          <span class="sub-label">آنالیتیکس سفارشات</span>
        </div>
      </div>
    </div>

    <div class="sb-section">هوش مصنوعی</div>

    {{-- مدیریت مدل‌ها --}}
    <div class="nav-item">
      <a href="{{ route('admin.prompts.index') }}"
         class="nav-link {{ request()->is('admin/prompts*') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fa-solid fa-microchip"></i></div>
        <span class="nav-label">مدیریت مدل‌ها</span>
      </a>
    </div>

    {{-- لاگ جاب‌ها --}}
    <div class="nav-item">
      <a href="{{ route('admin.jobs') }}"
         class="nav-link {{ request()->is('admin/jobs') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fa-solid fa-list-check"></i></div>
        <span class="nav-label">لاگ جاب‌ها</span>
      </a>
    </div>

    <div class="sb-divider"></div>

    {{-- تنظیمات --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/settings*') || request()->is('admin/dashboard/crm') ? 'active' : '' }}"
           onclick="toggleSub('sub-settings','chev-settings')">
        <div class="nav-icon"><i class="fa-solid fa-gear"></i></div>
        <span class="nav-label">تنظیمات</span>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/settings*') || request()->is('admin/dashboard/crm') ? 'open' : '' }}"
           id="chev-settings"></i>
      </div>
    </div>
    <div id="sub-settings" class="submenu {{ request()->is('admin/dashboard/crm') ? 'open' : '' }}"
         data-chev-id="chev-settings">
      <div class="sub-track">
        <a href="/admin/dashboard/crm" style="text-decoration:none;"
           class="sub-item {{ request()->is('admin/dashboard/crm') ? 'active' : '' }}">
          <span class="sub-dot"></span>
          <span class="sub-label">CRM</span>
        </a>
      </div>
    </div>

    {{-- ─── آپدیت در آینده ─── --}}
    <div style="height:1px; background:linear-gradient(to left,transparent,#a07af5,transparent); opacity:.2; margin:12px 14px;"></div>

    <div class="future-section">
      <div class="sb-future-toggle" onclick="toggleFuture()">
        <i class="fa-solid fa-clock-rotate-left" style="font-size:11px;color:#a07af5;"></i>
        <span class="sb-future-label">آپدیت در آینده</span>
        <i class="fa-solid fa-chevron-down sb-future-chev" id="future-chev"></i>
      </div>

      <div id="future-wrap" class="future-wrap">

        {{-- نظارت --}}
        <div class="future-sub-section">نظارت</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-dashboard', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-gauge-high"></i></div>
            <span class="future-nav-label">داشبورد نظارتی</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-dashboard" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/dashboard/stats" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">آمار لحظه‌ای</span>
            </a>
            <a href="/admin/dashboard/daily" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">آمار روزانه و ماهانه</span>
            </a>
            <a href="/admin/dashboard/alerts" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">هشدارها</span>
            </a>
          </div>
        </div>

        {{-- مدیریت --}}
        <div class="future-sub-section">مدیریت</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-users', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-users"></i></div>
            <span class="future-nav-label">کاربران</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-users" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/users" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لیست کاربران</span>
            </a>
            <a href="/admin/users/smart-lists" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لیست‌های هوشمند</span>
            </a>
            <a href="/admin/users/tokens" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">مدیریت توکن</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-bloggers', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-bullhorn"></i></div>
            <span class="future-nav-label">بلاگرها</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-bloggers" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/bloggers" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لیست بلاگرها</span>
            </a>
            <a href="/admin/bloggers/commission" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">مدیریت کمیسیون</span>
            </a>
            <a href="/admin/bloggers/traffic" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">گزارش ترافیک</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-orders', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <span class="future-nav-label">سفارشات گسترده</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-orders" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/orders" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لیست سفارشات</span>
            </a>
            <a href="/admin/orders/analytics" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">آنالیتیکس سفارشات</span>
            </a>
          </div>
        </div>

        {{-- ارتباطات --}}
        <div class="future-sub-section">ارتباطات</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-tickets', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-ticket"></i></div>
            <span class="future-nav-label">تیکت‌ها</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-tickets" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/tickets" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">تیکت‌های باز</span>
            </a>
            <a href="/admin/tickets/processing" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">در حال بررسی</span>
            </a>
            <a href="/admin/tickets/ai-response" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">پاسخ هوش مصنوعی</span>
            </a>
            <a href="/admin/tickets/report" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">گزارش تیکت‌ها</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-messages', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-comment-dots"></i></div>
            <span class="future-nav-label">پیام‌رسانی</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-messages" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/messages" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">ارسال به کاربر خاص</span>
            </a>
            <a href="/admin/messages/bulk" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">ارسال گروهی</span>
            </a>
            <a href="/admin/messages/scheduled" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">زمان‌بندی پیام</span>
            </a>
            <a href="/admin/messages/history" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">تاریخچه پیام‌ها</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-banners', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-rectangle-ad"></i></div>
            <span class="future-nav-label">بنر و نمایش</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-banners" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/banners" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">بنرهای صفحه اصلی</span>
            </a>
            <a href="/admin/banners/popups" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">پاپ‌آپ عمومی</span>
            </a>
            <a href="/admin/banners/discounts" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">کدهای تخفیف</span>
            </a>
          </div>
        </div>

        {{-- مالی --}}
        <div class="future-sub-section">مالی</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-finance', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <span class="future-nav-label">مالی</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-finance" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/payments" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">تراکنش‌ها</span>
            </a>
            <a href="/admin/payments/manual" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">پرداخت دستی</span>
            </a>
            <a href="/admin/payments/commission" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">کمیسیون بلاگرها</span>
            </a>
            <a href="/admin/payments/revenue-report" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">گزارش درآمد و هزینه</span>
            </a>
            <a href="/admin/payments/forecast" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">پیش‌بینی درآمد</span>
            </a>
          </div>
        </div>

        {{-- آنالیز و مارکتینگ --}}
        <div class="future-sub-section">آنالیز و مارکتینگ</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-analytics', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-chart-line"></i></div>
            <span class="future-nav-label">آنالیز</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-analytics" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/analytics" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">قیف فروش</span>
            </a>
            <a href="/admin/analytics/behavior" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">رفتار کاربر</span>
            </a>
            <a href="/admin/analytics/bloggers" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">آنالیز بلاگرها</span>
            </a>
            <a href="/admin/analytics/campaigns" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">کمپین‌ها</span>
            </a>
            <a href="/admin/analytics/retarget" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">ریتارگت</span>
            </a>
            <a href="/admin/analytics/viral" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">گزارش وایرال</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <a href="/admin/reports" style="text-decoration:none;" class="future-nav-link">
            <div class="future-nav-icon"><i class="fa-solid fa-file-chart-column"></i></div>
            <span class="future-nav-label">گزارش‌ساز</span>
          </a>
        </div>

        {{-- سیستم --}}
        <div class="future-sub-section">سیستم</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-infra', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-server"></i></div>
            <span class="future-nav-label">زیرساخت</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-infra" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/infrastructure" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">وضعیت سرور</span>
            </a>
            <a href="/admin/infrastructure/queue" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">صف پردازش</span>
            </a>
            <a href="/admin/infrastructure/ai-cost" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">هزینه هوش مصنوعی</span>
            </a>
            <a href="/admin/infrastructure/logs" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لاگ خطاها</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-content', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-newspaper"></i></div>
            <span class="future-nav-label">محتوا</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-content" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/content" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">مقالات</span>
            </a>
            <a href="/admin/content/pages" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">صفحات سایت</span>
            </a>
            <a href="/admin/content/media" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">مدیریت رسانه‌ها</span>
            </a>
            <a href="/admin/content/notifications" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">اعلان‌های سیستمی</span>
            </a>
          </div>
        </div>

        <div class="future-nav-item">
          <a href="/admin/crm/attendance" style="text-decoration:none;" class="future-nav-link">
            <div class="future-nav-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <span class="future-nav-label">حضور و غیاب</span>
          </a>
        </div>

        {{-- تنظیمات سیستم --}}
        <div class="future-sub-section">تنظیمات سیستم</div>

        <div class="future-nav-item">
          <div class="future-nav-link" onclick="toggleFutureSub('fsub-settings', this)">
            <div class="future-nav-icon"><i class="fa-solid fa-gear"></i></div>
            <span class="future-nav-label">تنظیمات</span>
            <i class="fa-solid fa-chevron-down nav-chev" style="font-size:9px;"></i>
          </div>
        </div>
        <div id="fsub-settings" class="future-sub-wrap">
          <div class="future-sub-track">
            <a href="/admin/settings/admins" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">مدیریت ادمین‌ها</span>
            </a>
            <a href="/admin/settings/access" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">سطوح دسترسی</span>
            </a>
            <a href="/admin/settings/system" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">تنظیمات سیستم</span>
            </a>
            <a href="/admin/settings/payment-gateway" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">درگاه پرداخت</span>
            </a>
            <a href="/admin/settings/backup" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">پشتیبان‌گیری</span>
            </a>
            <a href="/admin/settings/logs" class="future-sub-item" style="text-decoration:none;">
              <span class="future-sub-dot"></span><span class="future-sub-label">لاگ فعالیت ادمین‌ها</span>
            </a>
          </div>
        </div>

        <div style="height:12px;"></div>
      </div>{{-- /future-wrap --}}
    </div>{{-- /future-section --}}

  </nav>


</aside>
