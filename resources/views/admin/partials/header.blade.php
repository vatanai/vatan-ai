  <header class="h-14 bg-s1 border-b border-b1 flex items-center px-6 gap-3 sticky top-0 z-50 flex-shrink-0 max-[768px]:px-4 max-[768px]:gap-2 max-[480px]:px-3">
    <div class="hdr-btn hidden max-[900px]:flex w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 items-center justify-center cursor-pointer text-sm text-watan-text2 transition-colors duration-150 relative flex-shrink-0 hover:border-b2 hover:text-watan-text" onclick="toggleSidebar()" title="منو">
      <i class="fa-solid fa-bars"></i>
    </div>

    <div class="flex-1 flex items-center gap-[6px] text-xs text-watan-text3 max-[480px]:overflow-hidden">
      <span class="max-[480px]:hidden">پنل مدیریت</span>
      <span class="text-watan-text3 opacity-50 max-[480px]:hidden"><i class="fa-solid fa-chevron-left text-[9px]"></i></span>
      <span class="text-watan-text font-bold" id="breadcrumb">مرکز فرماندهی</span>
    </div>

    <div class="flex items-center gap-2 bg-s2 border border-b1 rounded-lg px-3 h-[34px] w-[220px] transition-colors duration-150 focus-within:border-b2 max-[768px]:w-40 max-[600px]:hidden">
      <i class="fa-solid fa-magnifying-glass text-watan-text3 text-xs"></i>
      <input type="text" placeholder="جستجو در پنل..." class="bg-transparent border-0 outline-none text-xs text-watan-text flex-1 min-w-0 placeholder:text-watan-text3" />
    </div>

    <div class="hdr-btn bg-s2 border border-b1 rounded-lg text-xs font-bold px-3 h-[34px] flex items-center gap-2 cursor-pointer transition-colors duration-150 text-watan-text2 hover:border-accent hover:text-accent flex-shrink-0" onclick="setActiveSub(null,'تنظیمات','CRM','crm-page');document.getElementById('breadcrumb').textContent='CRM'" title="CRM">
      <i class="fa-solid fa-diagram-project"></i>
      <span>CRM</span>
    </div>

    <div class="flex items-center gap-2">
      <div class="hdr-btn w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center cursor-pointer text-sm text-watan-text2 transition-colors duration-150 relative flex-shrink-0 hover:border-b2 hover:text-watan-text" onclick="toggleMode()" title="تغییر تم" id="theme-btn">
        <i class="fa-solid fa-moon"></i>
      </div>
      <div class="hdr-btn w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center cursor-pointer text-sm text-watan-text2 transition-colors duration-150 relative flex-shrink-0 hover:border-b2 hover:text-watan-text" title="اعلان‌ها">
        <i class="fa-solid fa-bell"></i>
        <div class="absolute top-[7px] left-[7px] w-[6px] h-[6px] rounded-full bg-red border-[1.5px] border-s1"></div>
      </div>
      <div class="hdr-btn w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center cursor-pointer text-sm text-watan-text2 transition-colors duration-150 relative flex-shrink-0 hover:border-b2 hover:text-watan-text" title="پروفایل">
        <i class="fa-solid fa-user"></i>
      </div>
    </div>
  </header>
