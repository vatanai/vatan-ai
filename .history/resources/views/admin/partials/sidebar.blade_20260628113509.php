<aside class="sidebar fixed top-0 bottom-0 right-0 z-[100] w-64 min-h-screen bg-s1 border-l border-b1 flex flex-col overflow-y-auto max-[900px]:translate-x-full max-[900px]:transition-transform max-[900px]:duration-[250ms] max-[900px]:shadow-[-12px_0_32px_rgba(0,0,0,0.4)]" style="scrollbar-width:none;-ms-overflow-style:none;" onscroll="void(0)">

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

    <div class="">
      <div class="nav-item group active flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="setActive(this,'مرکز فرماندهی','','dashboard-page')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">مرکز فرماندهی</div>
        <span class="nav-badge text-[9px] font-bold py-[2px] px-[6px] rounded-[10px] flex-shrink-0 bg-red/[0.1] text-red border border-red/[0.25]">۳</span>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">نظارت</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'داشبورد')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-chart-line"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">داشبورد</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'داشبورد','آمار لحظه‌ای')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">آمار لحظه‌ای</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'داشبورد','آمار روزانه و ماهانه')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">آمار روزانه و ماهانه</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'داشبورد','هشدارها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">هشدارها</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-red/[0.1] text-red border border-red/[0.2]">۳</span></div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">مدیریت</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'کاربران')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-users"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">کاربران</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/users'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لیست کاربران</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/users'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">جستجو و فیلتر</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/users/smart-lists'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لیست‌های هوشمند</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/users/tokens'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مدیریت توکن</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'بلاگرها')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-bullhorn"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">بلاگرها</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/bloggers'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لیست بلاگرها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/bloggers'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">ساخت لینک اختصاصی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/bloggers/commission'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مدیریت کمیسیون</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/bloggers/traffic'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">گزارش ترافیک</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/bloggers'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">نمایش داشبورد بلاگر</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'محصولات')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-box-open"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">محصولات</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محصولات','داشبورد محصولات','products-dashboard-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">داشبورد محصولات</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محصولات','لیست محصولات','products-list-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لیست محصولات</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محصولات','ثبت محصول جدید','products-create-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">ثبت محصول جدید</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">جدید</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محصولات','دسته‌بندی‌ها','products-categories-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">دسته‌بندی‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محصولات','قیمت‌گذاری','products-pricing-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">قیمت‌گذاری</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'سفارشات')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">سفارشات</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/orders'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لیست سفارشات</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="window.location.href='/admin/orders/analytics'"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">آنالیتیکس سفارشات</div></div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">هوش مصنوعی</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'هوش مصنوعی')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-microchip"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">هوش مصنوعی</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'هوش مصنوعی','نمای کلی','ai-hub-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">نمای کلی</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">جدید</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'هوش مصنوعی','مدیریت مدل‌ها','ai-models-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مدیریت مدل‌ها</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">جدید</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'هوش مصنوعی','پرامپت‌ها','ai-prompts-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پرامپت‌ها</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">جدید</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'هوش مصنوعی','لاگ‌های اجرا','ai-logs-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لاگ‌های اجرا</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">جدید</span></div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">ارتباطات</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'تیکت‌ها')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-ticket"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">تیکت‌ها</div>
        <span class="nav-badge text-[9px] font-bold py-[2px] px-[6px] rounded-[10px] flex-shrink-0 bg-red/[0.1] text-red border border-red/[0.25]">۷</span>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تیکت‌ها','تیکت‌های باز')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">تیکت‌های باز</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-red/[0.1] text-red border border-red/[0.2]">۷</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تیکت‌ها','در حال بررسی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">در حال بررسی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تیکت‌ها','پاسخ هوش مصنوعی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پاسخ هوش مصنوعی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تیکت‌ها','گزارش تیکت‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">گزارش تیکت‌ها</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'پیام‌رسانی')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">پیام‌رسانی</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'پیام‌رسانی','ارسال به کاربر')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">ارسال به کاربر خاص</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'پیام‌رسانی','ارسال گروهی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">ارسال گروهی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'پیام‌رسانی','زمان‌بندی پیام')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">زمان‌بندی پیام</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'پیام‌رسانی','تاریخچه پیام‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">تاریخچه پیام‌ها</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'بنر و نمایش')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-image"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">بنر و نمایش</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'بنر و نمایش','بنرهای صفحه اصلی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">بنرهای صفحه اصلی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'بنر و نمایش','پاپ‌آپ عمومی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پاپ‌آپ عمومی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'بنر و نمایش','پاپ‌آپ اختصاصی محصول')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پاپ‌آپ اختصاصی محصول</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'بنر و نمایش','کدهای تخفیف')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">کدهای تخفیف</div></div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">مالی</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'مالی')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-credit-card"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">مالی</div>
        <span class="nav-badge text-[9px] font-bold py-[2px] px-[6px] rounded-[10px] flex-shrink-0 bg-orange/[0.1] text-orange border border-orange/[0.25]">۲</span>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'مالی','تراکنش‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">تراکنش‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'مالی','پرداخت دستی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پرداخت دستی</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-red/[0.1] text-red border border-red/[0.2]">۲</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'مالی','کمیسیون بلاگرها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">کمیسیون بلاگرها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'مالی','گزارش درآمد و هزینه')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">گزارش درآمد و هزینه</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'مالی','پیش‌بینی درآمد')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پیش‌بینی درآمد</div></div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">آنالیز و مارکتینگ</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'آنالیز')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-chart-bar"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">آنالیز</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','قیف فروش')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">قیف فروش</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','رفتار کاربر')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">رفتار کاربر</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','آنالیز بلاگرها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">آنالیز بلاگرها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','کمپین‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">کمپین‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','ریتارگت')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">ریتارگت</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'آنالیز','گزارش وایرال')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">گزارش وایرال</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="setActive(this,'گزارش‌ساز','','placeholder-page')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-file-lines"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">گزارش‌ساز</div>
      </div>
    </div>

    <div class="">
      <div class="text-[9px] font-bold tracking-[2.5px] text-watan-text3 uppercase pt-3 px-4 pb-1">سیستم</div>
      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'زیرساخت')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-server"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">زیرساخت</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','وضعیت سرور')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">وضعیت سرور</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-green/[0.08] text-green border border-green/[0.2]">آنلاین</span></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','صف پردازش')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">صف پردازش</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','هزینه هوش مصنوعی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">هزینه هوش مصنوعی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','هزینه سرور و استوریج')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">هزینه سرور و استوریج</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','سود هر عکس')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">سود هر عکس</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'زیرساخت','لاگ خطاها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لاگ خطاها</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'محتوا')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-pen-nib"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">محتوا</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محتوا','مقالات')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مقالات</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محتوا','صفحات سایت')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">صفحات سایت</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محتوا','مدیریت رسانه‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مدیریت رسانه‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'محتوا','اعلان‌های سیستمی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">اعلان‌های سیستمی</div></div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="setActive(this,'حضور و غیاب','','attendance-page')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">حضور و غیاب</div>
      </div>

      <div class="nav-item group flex items-center gap-[10px] px-2 mx-[6px] my-px rounded-lg cursor-pointer select-none transition-colors duration-150 hover:bg-s2" onclick="toggleSub(this,'تنظیمات')">
        <div class="nav-icon w-[30px] h-[30px] flex items-center justify-center text-sm flex-shrink-0 text-watan-text transition-colors duration-150"><i class="fa-solid fa-gear"></i></div>
        <div class="nav-label flex-1 text-[12.5px] font-semibold text-watan-text py-[9px] transition-colors duration-150">تنظیمات</div>
        <div class="nav-arrow text-[10px] text-watan-text3 transition-transform duration-200 group-hover:text-watan-text2 flex-shrink-0"><i class="fa-solid fa-chevron-left"></i></div>
      </div>
      <div class="sub-nav hidden pt-[2px] pb-1">
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','مدیریت ادمین‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">مدیریت ادمین‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','سطوح دسترسی')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">سطوح دسترسی</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','تنظیمات سیستم')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">تنظیمات سیستم</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','درگاه پرداخت')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">درگاه پرداخت</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','پشتیبان‌گیری')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">پشتیبان‌گیری</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','لاگ فعالیت ادمین‌ها')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">لاگ فعالیت ادمین‌ها</div></div>
        <div class="sub-item group flex items-center gap-2 py-[6px] px-[10px] mt-px mr-[6px] mb-px ml-[30px] rounded-md cursor-pointer transition-colors duration-150 hover:bg-s2" onclick="setActiveSub(this,'تنظیمات','CRM','crm-page')"><div class="sub-dot w-1 h-1 rounded-full bg-b2 flex-shrink-0 transition-colors duration-150 group-hover:bg-watan-text3"></div><div class="sub-label flex-1 text-[11.5px] font-medium text-watan-text transition-colors duration-150">CRM</div><span class="sub-badge text-[9px] font-bold py-px px-[5px] rounded-lg mr-auto bg-accent/[0.1] text-accent border border-accent/[0.2]">جدید</span></div>
      </div>
    </div>

  </nav>

  <div class="p-[10px] border-t border-b1 flex-shrink-0">
    <div class="group flex items-center gap-2 py-2 px-[10px] rounded-lg cursor-pointer transition-colors duration-150 w-full hover:bg-red/[0.1]">
      <i class="fa-solid fa-right-from-bracket text-watan-text3 text-[13px] group-hover:text-red"></i>
      <span class="text-xs font-semibold text-watan-text3 group-hover:text-red">خروج از حساب</span>
    </div>
  </div>

</aside>
