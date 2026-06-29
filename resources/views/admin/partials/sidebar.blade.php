<aside class="fixed top-0 right-0 bottom-0 w-64 bg-[#111116] border-l border-[#222230] flex flex-col overflow-y-auto z-[100] font-[IRANSansXFaNum] direction-rtl text-white" dir="rtl">

  <div class="flex items-center gap-2.5 px-4 py-[18px] border-b border-[#222230] flex-shrink-0">
    <div class="w-9 h-9 rounded-[10px] bg-[#0BBF53] flex items-center justify-center text-[17px] font-black text-white shadow-[0_0_16px_rgba(11,191,83,.3)]">و</div>
    <div>
      <div class="text-sm font-extrabold">وطن استودیو</div>
      <div class="text-[9px] text-[#4d7a56] tracking-[2.5px] uppercase">Admin Panel</div>
    </div>
  </div>

  <div class="flex items-center gap-2.5 px-3.5 py-2.5 border-b border-[#222230]">
    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#a07af5] to-[#6a4dcc] flex items-center justify-center text-[13px] font-bold flex-shrink-0">م</div>
    <div class="flex-1">
      <div class="text-xs font-bold">محسن رضایی</div>
      <div class="text-[9px] font-bold px-1.5 py-px rounded bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/25 inline-block mt-0.5">مدیر کل</div>
    </div>
    <div class="w-[7px] h-[7px] rounded-full bg-[#0BBF53] shadow-[0_0_6px_#0BBF53] flex-shrink-0"></div>
  </div>

  <nav class="flex-1 py-2">

    <a href="/admin/dashboard"
       class="flex items-center gap-2.5 px-2 mx-1.5 mb-1 rounded-lg h-[38px] no-underline transition-colors {{ request()->is('admin/dashboard') ? 'bg-[#a07af5]/[0.12]' : 'hover:bg-[#16161c]' }}">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] flex-shrink-0 {{ request()->is('admin/dashboard') ? 'text-[#a07af5]' : 'text-[#a8c4a8]' }}">
        <i class="fa-solid fa-bolt-lightning"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold {{ request()->is('admin/dashboard') ? 'text-white' : 'text-[#a8c4a8]' }}">مرکز فرماندهی</div>
    </a>

    <div class="text-[9px] font-bold tracking-[2.5px] uppercase text-[#4d7a56] px-4 pt-3 pb-1">مدیریت محصولات</div>

    <div onclick="toggleSubmenu('products-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer transition-colors {{ request()->is('admin/products*') ? 'bg-[#a07af5]/[0.12]' : 'hover:bg-[#16161c]' }}">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] flex-shrink-0 {{ request()->is('admin/products*') ? 'text-[#a07af5]' : 'text-[#a8c4a8]' }}">
        <i class="fa-solid fa-box-open"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold {{ request()->is('admin/products*') ? 'text-white' : 'text-[#a8c4a8]' }}">محصولات</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56] transition-transform duration-200 chevron-icon" 
         style="{{ request()->is('admin/products*') ? 'transform: rotate(180deg);' : '' }}"></i>
    </div>

    <div id="products-submenu" class="py-0.5 pb-1" style="{{ request()->is('admin/products*') ? '' : 'display: none;' }}">

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">داشبورد محصولات</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در حال طراحی</span>
      </div>

      <a href="/admin/products"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors {{ request()->is('admin/products') ? 'bg-[#a07af5]/10 font-semibold' : 'hover:bg-[#16161c]' }}">
        <div class="w-1 h-1 rounded-full flex-shrink-0 {{ request()->is('admin/products') ? 'bg-[#a07af5]' : 'bg-[#2e2e3e]' }}"></div>
        <div class="flex-1 text-[11.5px] font-medium {{ request()->is('admin/products') ? 'text-white' : 'text-[#a8c4a8]' }}">لیست محصولات</div>
      </a>

      <a href="/admin/products/create"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors {{ request()->is('admin/products/create') ? 'bg-[#a07af5]/10 font-semibold' : 'hover:bg-[#16161c]' }}">
        <div class="w-1 h-1 rounded-full flex-shrink-0 {{ request()->is('admin/products/create') ? 'bg-[#a07af5]' : 'bg-[#2e2e3e]' }}"></div>
        <div class="flex-1 text-[11.5px] font-medium {{ request()->is('admin/products/create') ? 'text-white' : 'text-[#a8c4a8]' }}">ثبت محصول جدید</div>
      </a>

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">دسته‌بندی‌ها</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در حال طراحی</span>
      </div>

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">قیمت‌گذاری</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در July طراحی</span>
      </div>

    </div>

    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">سفارشات</div>
      <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25">در حال طراحی</span>
    </div>

    <div class="text-[9px] font-bold tracking-[2.5px] uppercase text-[#4d7a56] px-4 pt-3 pb-1">هوش مصنوعی</div>

    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-microchip"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">مدیریت مدل‌ها</div>
      <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25">در حال طراحی</span>
    </div>

    <div class="h-px bg-[#222230] mx-3 my-2"></div>

    {{-- تنظیمات - با زیرمنو --}}
    <div onclick="toggleSubmenu('settings-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer transition-colors {{ request()->is('admin/settings*') ? 'bg-[#a07af5]/[0.12]' : 'hover:bg-[#16161c]' }}">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] flex-shrink-0 {{ request()->is('admin/settings*') ? 'text-[#a07af5]' : 'text-[#a8c4a8]' }}">
        <i class="fa-solid fa-gear"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold {{ request()->is('admin/settings*') ? 'text-white' : 'text-[#a8c4a8]' }}">تنظیمات</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56] transition-transform duration-200 chevron-icon"
         style="{{ request()->is('admin/settings*') || request()->is('admin/crm*') ? 'transform: rotate(180deg);' : '' }}"></i>
    </div>

    <div id="settings-submenu" class="py-0.5 pb-1" style="{{ request()->is('admin/settings*') || request()->is('admin/crm*') ? '' : 'display: none;' }}">

      <a href="/admin/crm"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors {{ request()->is('admin/crm') ? 'bg-[#a07af5]/10 font-semibold' : 'hover:bg-[#16161c]' }}">
        <div class="w-1 h-1 rounded-full flex-shrink-0 {{ request()->is('admin/crm') ? 'bg-[#a07af5]' : 'bg-[#2e2e3e]' }}"></div>
        <div class="flex-1 text-[11.5px] font-medium {{ request()->is('admin/crm') ? 'text-white' : 'text-[#a8c4a8]' }}">CRM</div>
      </a>

      <a href="/admin/settings/admins"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors {{ request()->is('admin/settings/admins') ? 'bg-[#a07af5]/10' : 'hover:bg-[#16161c]' }}">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">مدیریت ادمین‌ها</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

      <a href="/admin/settings/access"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors hover:bg-[#16161c]">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">سطوح دسترسی</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

      <a href="/admin/settings/system"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors hover:bg-[#16161c]">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">تنظیمات سیستم</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

      <a href="/admin/settings/payment-gateway"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors hover:bg-[#16161c]">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">درگاه پرداخت</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

      <a href="/admin/settings/backup"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors hover:bg-[#16161c]">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">پشتیبان‌گیری</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

      <a href="/admin/settings/logs"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline transition-colors hover:bg-[#16161c]">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">لاگ فعالیت ادمین‌ها</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/20">آینده</span>
      </a>

    </div>

    {{-- ─────────── آپدیت در آینده ─────────── --}}
    <div class="h-px mx-3 my-3" style="background: linear-gradient(to left, transparent, #a07af5, transparent); opacity: 0.3;"></div>

    <div class="text-[9px] font-bold tracking-[2.5px] uppercase px-4 pt-1 pb-1.5 flex items-center gap-2" style="color:#a07af5;">
      <i class="fa-solid fa-clock-rotate-left text-[8px]"></i>
      آپدیت در آینده
    </div>

    {{-- نظارت --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">نظارت</div>

    <div onclick="toggleSubmenu('future-dashboard-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-gauge-high"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">داشبورد نظارتی</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-dashboard-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/dashboard/stats" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">آمار لحظه‌ای</div>
      </a>
      <a href="/admin/dashboard/daily" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">آمار روزانه و ماهانه</div>
      </a>
      <a href="/admin/dashboard/alerts" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">هشدارها</div>
      </a>
    </div>

    {{-- مدیریت --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">مدیریت</div>

    <div onclick="toggleSubmenu('future-users-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">کاربران</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-users-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/users" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">لیست کاربران</div>
      </a>
      <a href="/admin/users/smart-lists" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">لیست‌های هوشمند</div>
      </a>
      <a href="/admin/users/tokens" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">مدیریت توکن</div>
      </a>
    </div>

    <div onclick="toggleSubmenu('future-bloggers-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-bullhorn"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">بلاگرها</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-bloggers-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/bloggers" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">لیست بلاگرها</div>
      </a>
      <a href="/admin/bloggers/commission" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">مدیریت کمیسیون</div>
      </a>
      <a href="/admin/bloggers/traffic" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">گزارش ترافیک</div>
      </a>
    </div>

    <div onclick="toggleSubmenu('future-orders-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">سفارشات</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-orders-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/orders" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">لیست سفارشات</div>
      </a>
      <a href="/admin/orders/analytics" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">آنالیتیکس سفارشات</div>
      </a>
    </div>

    {{-- ارتباطات --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">ارتباطات</div>

    <div onclick="toggleSubmenu('future-tickets-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-ticket"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">تیکت‌ها</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-tickets-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/tickets" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">تیکت‌های باز</div>
      </a>
      <a href="/admin/tickets/processing" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">در حال بررسی</div>
      </a>
      <a href="/admin/tickets/ai-response" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">پاسخ هوش مصنوعی</div>
      </a>
      <a href="/admin/tickets/report" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">گزارش تیکت‌ها</div>
      </a>
    </div>

    <div onclick="toggleSubmenu('future-messages-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-comment-dots"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">پیام‌رسانی</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-messages-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/messages" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">ارسال به کاربر خاص</div>
      </a>
      <a href="/admin/messages/bulk" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">ارسال گروهی</div>
      </a>
      <a href="/admin/messages/scheduled" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">زمان‌بندی پیام</div>
      </a>
      <a href="/admin/messages/history" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">تاریخچه پیام‌ها</div>
      </a>
    </div>

    <div onclick="toggleSubmenu('future-banners-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-rectangle-ad"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">بنر و نمایش</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-banners-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/banners" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">بنرهای صفحه اصلی</div>
      </a>
      <a href="/admin/banners/popups" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">پاپ‌آپ عمومی</div>
      </a>
      <a href="/admin/banners/discounts" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">کدهای تخفیف</div>
      </a>
    </div>

    {{-- مالی --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">مالی</div>

    <div onclick="toggleSubmenu('future-finance-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-money-bill-wave"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">مالی</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-finance-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/payments" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">تراکنش‌ها</div>
      </a>
      <a href="/admin/payments/manual" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">پرداخت دستی</div>
      </a>
      <a href="/admin/payments/commission" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">کمیسیون بلاگرها</div>
      </a>
      <a href="/admin/payments/revenue-report" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">گزارش درآمد و هزینه</div>
      </a>
      <a href="/admin/payments/forecast" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">پیش‌بینی درآمد</div>
      </a>
    </div>

    {{-- آنالیز و مارکتینگ --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">آنالیز و مارکتینگ</div>

    <div onclick="toggleSubmenu('future-analytics-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-chart-line"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">آنالیز</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-analytics-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/analytics" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">قیف فروش</div>
      </a>
      <a href="/admin/analytics/behavior" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">رفتار کاربر</div>
      </a>
      <a href="/admin/analytics/bloggers" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">آنالیز بلاگرها</div>
      </a>
      <a href="/admin/analytics/campaigns" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">کمپین‌ها</div>
      </a>
      <a href="/admin/analytics/retarget" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">ریتارگت</div>
      </a>
      <a href="/admin/analytics/viral" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">گزارش وایرال</div>
      </a>
    </div>

    <a href="/admin/reports"
       class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] no-underline hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-file-chart-column"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">گزارش‌ساز</div>
    </a>

    {{-- سیستم --}}
    <div class="text-[8.5px] font-bold tracking-[2px] uppercase text-[#4d7a56] px-4 pt-2 pb-0.5">سیستم</div>

    <div onclick="toggleSubmenu('future-infra-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-server"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">زیرساخت</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-infra-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/infrastructure" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">وضعیت سرور</div>
      </a>
      <a href="/admin/infrastructure/queue" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">صف پردازش</div>
      </a>
      <a href="/admin/infrastructure/ai-cost" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">هزینه هوش مصنوعی</div>
      </a>
      <a href="/admin/infrastructure/logs" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">لاگ خطاها</div>
      </a>
    </div>

    <div onclick="toggleSubmenu('future-content-submenu', this)"
         class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-newspaper"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">محتوا</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]/60 transition-transform duration-200 chevron-icon"></i>
    </div>
    <div id="future-content-submenu" style="display:none;" class="py-0.5 pb-1">
      <a href="/admin/content" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">مقالات</div>
      </a>
      <a href="/admin/content/pages" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">صفحات سایت</div>
      </a>
      <a href="/admin/content/media" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">مدیریت رسانه‌ها</div>
      </a>
      <a href="/admin/content/notifications" class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11px] font-medium text-[#a8c4a8]/60">اعلان‌های سیستمی</div>
      </a>
    </div>

    <a href="/admin/crm/attendance"
       class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] no-underline hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-calendar-check"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">حضور و غیاب</div>
    </a>

    <a href="/admin/jobs"
       class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[36px] no-underline hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[12px] text-[#a8c4a8]/60 flex-shrink-0">
        <i class="fa-solid fa-list-check"></i>
      </div>
      <div class="flex-1 text-[12px] font-medium text-[#a8c4a8]/60">لاگ جاب‌ها</div>
    </a>

    <div class="h-4"></div>

  </nav>
</aside>

<script>
function toggleSubmenu(id, headerEl) {
  const submenu = document.getElementById(id);
  const chevron = headerEl.querySelector('.chevron-icon');
  const isOpen = submenu.style.display !== 'none';

  submenu.style.display = isOpen ? 'none' : '';
  chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>