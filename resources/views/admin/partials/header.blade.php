  <header class="topbar flex items-center px-6 gap-3 sticky top-0 z-50 flex-shrink-0 max-[768px]:px-4 max-[768px]:gap-2 max-[480px]:px-3">

    <div class="tb-menu-btn flex" onclick="toggleSidebar()" title="باز/بسته کردن منو">
      <i class="fa-solid fa-bars-staggered"></i>
    </div>

    <div class="tb-breadcrumb flex-1 max-[480px]:overflow-hidden">
      <span class="max-[480px]:hidden">پنل مدیریت</span>
      <i class="fa-solid fa-angle-left max-[480px]:hidden"></i>
      <span class="active-crumb" id="breadcrumb">مرکز فرماندهی</span>
    </div>

    <div class="tb-search w-[220px] max-[768px]:w-40 max-[600px]:hidden">
      <i class="fa-solid fa-magnifying-glass si"></i>
      <input type="text" placeholder="جستجو در پنل...">
    </div>

    <div class="tb-chip-btn" onclick="setActiveSub(null,'تنظیمات','CRM','crm-page');document.getElementById('breadcrumb').textContent='CRM'" title="CRM">
      <i class="fa-solid fa-diagram-project"></i>
      <span>CRM</span>
    </div>

    <div class="flex items-center gap-2">
      <div class="tb-btn" onclick="toggleMode()" title="تغییر تم" id="theme-btn">
        <i class="fa-solid fa-moon"></i>
      </div>
      <div class="tb-btn" title="اعلان‌ها">
        <i class="fa-solid fa-bell"></i>
        <div class="tb-notif"></div>
      </div>
      <div class="tb-divider-v"></div>
      <div class="live-chip"><div class="live-dot"></div>لایو</div>
    </div>

  </header>

  <script>
    /* تغییر تم روز/شب هدر و کل پنل (کلاس body.light هماهنگ با admin.css) */
    function toggleMode() {
      const isLight = document.body.classList.toggle('light');
      try { localStorage.setItem('admin-theme', isLight ? 'light' : 'dark'); } catch (e) {}
      const btn = document.getElementById('theme-btn');
      if (btn) {
        btn.innerHTML = isLight
          ? '<i class="fa-solid fa-sun"></i>'
          : '<i class="fa-solid fa-moon"></i>';
      }
    }
    /* هماهنگ کردن آیکون دکمه تم با وضعیت فعلی هنگام بارگذاری صفحه */
    (function () {
      const btn = document.getElementById('theme-btn');
      if (btn) {
        btn.innerHTML = document.body.classList.contains('light')
          ? '<i class="fa-solid fa-sun"></i>'
          : '<i class="fa-solid fa-moon"></i>';
      }
    })();
  </script>
