  @php
    /* شماره نسخه‌ی داشبورد — از فایل VERSION در ریشه‌ی پروژه (ردیف «ورژن داشبورد») خونده می‌شه.
       طبق قانون پروژه: هر تغییری روی داشبورد اعمال شد، این عدد یا خودکار با دستور
       «php artisan admin:bump-version» یا دستی با ویرایش همون ردیف توی VERSION عوض
       می‌شه و بلافاصله همین‌جا هم اعمال می‌شه (سند: CLAUDE.md). */
    $adminDashboardVersion = null;
    $versionFilePath = base_path('VERSION');
    if (is_file($versionFilePath)) {
        $versionFileContent = file_get_contents($versionFilePath);
        if (preg_match('/ورژن داشبورد\s*:\s*(\d+)/u', $versionFileContent, $versionMatch)) {
            $adminDashboardVersion = $versionMatch[1];
        }
    }
  @endphp

  <header class="topbar flex items-center px-6 gap-3 sticky top-0 z-50 flex-shrink-0 max-[768px]:px-4 max-[768px]:gap-2 max-[480px]:px-3">

    <button type="button" class="tb-menu-btn flex" onclick="adminToggleSidebar()" title="باز/بسته کردن منو" aria-label="باز یا بسته کردن منوی مدیریت">
      <i class="fa-solid fa-bars-staggered"></i>
    </button>

    @if($adminDashboardVersion)
      <span class="tb-version" title="نسخه پنل مدیریت">V.{{ $adminDashboardVersion }}</span>
    @endif

    <div class="tb-breadcrumb flex-1 max-[480px]:overflow-hidden">
      <span class="max-[480px]:hidden">پنل مدیریت</span>
      <i class="fa-solid fa-angle-left max-[480px]:hidden"></i>
      <span class="active-crumb" id="breadcrumb">مرکز فرماندهی</span>
    </div>

    <div class="tb-search w-[220px] max-[768px]:w-40 max-[600px]:hidden">
      <i class="fa-solid fa-magnifying-glass si"></i>
      <input type="text" placeholder="جستجو در پنل...">
    </div>

    <div class="tb-iran-clock max-[1100px]:hidden" title="ساعت رسمی ایران">
      <span class="tb-iran-clock-time" id="iran-clock-time">--:--:--</span>
      <span class="tb-iran-clock-date" id="iran-clock-date">----/--/--</span>
    </div>

    <a class="tb-chip-btn" href="{{ route('admin.dashboard', ['section' => 'crm']) }}" title="سیستم مدیریت پروژه">
      <i class="fa-solid fa-diagram-project"></i>
      <span>سیستم مدیریت پروژه</span>
    </a>

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
    (function iranClock() {
      const timeEl = document.getElementById('iran-clock-time');
      const dateEl = document.getElementById('iran-clock-date');
      if (!timeEl || !dateEl) return;
      const serverEpoch = {{ now('Asia/Tehran')->getTimestampMs() }};
      const startedAt = Date.now();
      window.AdminIranClock = {
        serverEpoch: serverEpoch,
        startedAt: startedAt,
        now: function () { return new Date(this.serverEpoch + (Date.now() - this.startedAt)); }
      };
      const render = function () {
        const now = window.AdminIranClock.now();
        timeEl.textContent = new Intl.DateTimeFormat('fa-IR', { timeZone:'Asia/Tehran', hour:'2-digit', minute:'2-digit', second:'2-digit', hourCycle:'h23' }).format(now);
        dateEl.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone:'Asia/Tehran', year:'numeric', month:'2-digit', day:'2-digit' }).format(now).replace(/\u200e|\u200f/g, '');
      };
      render();
      window.setInterval(render, 1000);
    })();
  </script>
