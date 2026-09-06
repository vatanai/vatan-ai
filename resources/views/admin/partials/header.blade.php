  @php
    $headerCreditAlerts = collect($creditAlerts ?? []);
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

    @include('admin.partials.breadcrumb')

    <div class="tb-search w-[220px] max-[768px]:w-40 max-[600px]:hidden" data-admin-header-search>
      <i class="fa-solid fa-magnifying-glass si"></i>
      <input id="admin-header-search" type="search" placeholder="جستجو در پنل..." autocomplete="off" aria-label="جستجو در پنل" aria-controls="admin-header-search-results" aria-expanded="false">
      <div class="tb-search-results" id="admin-header-search-results" role="listbox" hidden></div>
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
      <div class="tb-alert-wrap">
        <button type="button" class="tb-btn tb-alert-trigger" title="اعلان‌های اعتبار" aria-label="اعلان‌های اعتبار" aria-expanded="false" aria-controls="admin-credit-alert-popover">
          <i class="fa-solid fa-bell"></i>
          @if($headerCreditAlerts->isNotEmpty())<span class="tb-notif-count">{{ $headerCreditAlerts->count() }}</span>@endif
        </button>
        @if($headerCreditAlerts->isNotEmpty())
          <div class="tb-alert-popover" id="admin-credit-alert-popover" hidden>
            <div class="tb-alert-popover-head"><strong>هشدارهای اعتبار</strong><span>{{ $headerCreditAlerts->count() }} مورد</span></div>
            @foreach($headerCreditAlerts->take(5) as $alert)
              <a class="tb-alert-item {{ $alert['level'] }}" href="{{ route('admin.service-credits.index') }}"><i class="fa-solid {{ $alert['level'] === 'critical' ? 'fa-circle-exclamation' : ($alert['level'] === 'offline' ? 'fa-plug-circle-xmark' : 'fa-bell') }}"></i><span><strong>{{ $alert['name'] }}</strong><small>{{ $alert['message'] }}</small></span></a>
            @endforeach
            <a class="tb-alert-popover-link" href="{{ route('admin.service-credits.index') }}">مشاهده و مدیریت همهٔ هشدارها ←</a>
          </div>
        @endif
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
    (function adminHeaderSearch() {
      const wrap = document.querySelector('[data-admin-header-search]');
      const input = document.getElementById('admin-header-search');
      const results = document.getElementById('admin-header-search-results');
      const sidebar = document.getElementById('admin-sidebar');
      if (!wrap || !input || !results || !sidebar) return;

      const links = Array.from(sidebar.querySelectorAll('a[href]'))
        .filter(link => link.getAttribute('href') && !link.getAttribute('href').startsWith('#'))
        .map(link => ({
          href: link.href,
          label: (link.querySelector('.nav-label, .sub-label, .sub-sub-label') || link).textContent.trim(),
        }))
        .filter(item => item.label);

      const normalize = value => String(value || '').trim().toLocaleLowerCase('fa');
      const closeResults = () => {
        results.hidden = true;
        results.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
      };
      const renderResults = () => {
        const query = normalize(input.value);
        if (!query) {
          closeResults();
          return;
        }

        const matches = links.filter(item => normalize(item.label).includes(query)).slice(0, 8);
        results.innerHTML = '';
        if (!matches.length) {
          const empty = document.createElement('div');
          empty.className = 'tb-search-results__empty';
          empty.textContent = 'نتیجه‌ای پیدا نشد';
          results.appendChild(empty);
        } else {
          matches.forEach(item => {
            const link = document.createElement('a');
            link.className = 'tb-search-results__item';
            link.href = item.href;
            link.setAttribute('role', 'option');
            link.innerHTML = '<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>';
            const label = document.createElement('span');
            label.textContent = item.label;
            link.appendChild(label);
            results.appendChild(link);
          });
        }
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
      };

      input.addEventListener('input', renderResults);
      input.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          input.value = '';
          closeResults();
        }
        if (event.key === 'Enter') {
          const first = results.querySelector('a[href]');
          if (first) window.location.href = first.href;
        }
      });
      document.addEventListener('click', event => {
        if (!wrap.contains(event.target)) closeResults();
      });
    })();
    (function adminCreditAlerts() {
      const trigger = document.querySelector('.tb-alert-trigger');
      const popover = document.getElementById('admin-credit-alert-popover');
      if (!trigger || !popover) return;
      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        const isOpen = !popover.hasAttribute('hidden');
        if (isOpen) popover.setAttribute('hidden', 'hidden');
        else popover.removeAttribute('hidden');
        trigger.setAttribute('aria-expanded', String(!isOpen));
      });
      document.addEventListener('click', function (event) {
        if (!popover.contains(event.target) && !trigger.contains(event.target)) {
          popover.setAttribute('hidden', 'hidden');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });
    })();
  </script>
