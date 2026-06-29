@extends('layouts.admin')

<aside class="sidebar fixed top-0 bottom-0 right-0 z-[100] w-64 min-h-screen bg-s1 border-l border-b1 flex flex-col overflow-y-auto max-[900px]:translate-x-full max-[900px]:transition-transform max-[900px]:duration-[250ms] max-[900px]:shadow-[-12px_0_32px_rgba(0,0,0,0.4)]" style="scrollbar-width:none;-ms-overflow-style:none;">
  
  <div class="flex items-center gap-[10px] py-[18px] px-4 border-b border-b1 flex-shrink-0">
    <div class="w-9 h-9 rounded-[10px] flex items-center justify-center flex-shrink-0 shadow-[0_0_16px_rgba(11,191,83,0.25)]" style="background:rgba(11,191,83,.08);border:1px solid rgba(11,191,83,.2);">
      <img src="/assets/img/iconvatanai.svg" alt="Vatan AI" class="w-6 h-6" style="filter:drop-shadow(0 0 4px rgba(11,191,83,.4));">
    </div>
    <div>
      <div class="text-sm font-extrabold text-watan-text tracking-[-0.3px]">وطن استودیو</div>
      <div class="text-[9px] text-watan-text3 tracking-[2.5px] uppercase mt-px">Admin Panel</div>
    </div>
  </div>

  <div class="flex items-center gap-[10px] py-3 px-[14px] border-b border-b1 flex-shrink-0">
    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-accent to-[#6a4dcc] flex items-center justify-center text-[13px] font-bold text-white flex-shrink-0">م</div>
    <div class="flex-1 min-w-0">
      <div class="text-xs font-bold text-watan-text">محسن رضایی</div>
      <div class="text-[9px] font-bold py-px px-[6px] rounded-[4px] bg-accent/[0.1] text-accent border border-accent/[0.25] inline-block mt-[2px]">مدیر کل</div>
    </div>
    <div class="w-[7px] h-[7px] rounded-full bg-green shadow-[0_0_6px_rgb(var(--green))] flex-shrink-0"></div>
  </div>

  <nav class="flex-1 py-2">

    <div class="mb-2">
      <a href="/admin/dashboard" class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/dashboard') ? 'bg-s2 active text-accent' : '' }}">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">مرکز فرماندهی</div>
        <span class="nav-badge text-[9px] font-bold py-[2px] px-[6px] rounded-[10px] flex-shrink-0 bg-red/[0.1] text-red border border-red/[0.25]">۳</span>
      </a>
    </div>

    <div class="nav-dropdown-wrapper mb-2">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-2 px-4 pb-1">نظارت</div>
      <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-chart-line"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">داشبورد نظارتی</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
        <a href="/admin/analytics/live" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/analytics/live') ? 'text-accent' : '' }}">
          <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
          <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">آمار لحظه‌ای</div>
        </a>
        <a href="/admin/analytics/daily" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/analytics/daily') ? 'text-accent' : '' }}">
          <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
          <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">آمار روزانه و ماهانه</div>
        </a>
      </div>
    </div>

    <div class="mb-2">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-2 px-4 pb-1">مدیریت</div>
      
      <div class="nav-dropdown-wrapper">
        <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
          <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-users"></i></div>
          <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">کاربران</div>
          <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
        </div>
        <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
          <a href="/admin/users" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/users') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">لیست کاربران</div>
          </a>
          <a href="/admin/users/smart-lists" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/users/smart-lists') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">لیست‌های هوشمند</div>
          </a>
        </div>
      </div>

      <div class="nav-dropdown-wrapper">
        <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
          <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-feather-pointed"></i></div>
          <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">بلاگرها</div>
          <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
        </div>
        <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
          <a href="/admin/bloggers" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/bloggers') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">لیست درخواست‌ها</div>
          </a>
        </div>
      </div>

      <div class="nav-dropdown-wrapper">
        <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
          <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-box-open"></i></div>
          <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">محصولات</div>
          <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
        </div>
        <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
          <a href="/admin/products" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/products') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">مدیریت دوره‌ها و فایل‌ها</div>
          </a>
        </div>
      </div>

      <div class="nav-dropdown-wrapper">
        <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
          <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">هوش مصنوعی</div>
          <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
        </div>
        <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
          <a href="/admin/ai/prompts" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/ai/prompts') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">پرامپت‌های آماده</div>
          </a>
        </div>
      </div>
    </div>

    <div class="mb-2">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-2 px-4 pb-1">سیستم</div>
      
      <a href="/admin/attendance" class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/attendance') ? 'bg-s2 active text-accent' : '' }}">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">حضور و غیاب</div>
      </a>

      <div class="nav-dropdown-wrapper">
        <div class="nav-dropdown-toggle nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2">
          <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text"><i class="fa-solid fa-gear"></i></div>
          <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px]">تنظیمات سیستم</div>
          <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
        </div>
        <div class="sub-nav hidden pt-[2px] pb-1 bg-s1/50 rounded-b-lg mx-[6px]">
          <a href="/admin/settings/logs" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/settings/logs') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">لاگ فعالیت ادمین‌ها</div>
          </a>
          <a href="/admin/settings/crm" class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[15px] mb-px rounded-md transition-colors duration-150 hover:bg-s2 {{ request()->is('admin/settings/crm') ? 'text-accent' : '' }}">
            <div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0"></div>
            <div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text">تنظیمات CRM</div>
            <span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-accent/[0.1] text-accent border border-accent/[0.2]">جدید</span>
          </a>
        </div>
      </div>
    </div>

  </nav>

  <div class="p-[10px] border-t border-b1 flex-shrink-0">
    <form action="/logout" method="POST" class="w-full m-0">
      @csrf
      <button type="submit" class="group flex items-center gap-2 py-2 px-[10px] rounded-lg cursor-pointer transition-colors duration-150 w-full hover:bg-red/[0.1] text-right border-0 bg-transparent">
        <i class="fa-solid fa-right-from-bracket text-watan-text3 text-[13px] group-hover:text-red transition-colors"></i>
        <span class="text-xs font-semibold text-watan-text3 group-hover:text-red transition-colors">خروج از حساب</span>
      </button>
    </form>
  </div>

</aside>