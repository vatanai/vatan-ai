<script>
(function () {
  window.logoutFromCurrentPage = async function (button) {
    var returnTo = window.location.pathname + window.location.search + window.location.hash;
    if (button) button.disabled = true;

    try {
      var tokenResponse = await fetch(@json(route('auth.csrf-token')), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      var tokenData = await tokenResponse.json();
      if (!tokenResponse.ok || !tokenData.token) throw new Error('csrf');

      var logoutResponse = await fetch(@json(route('logout')), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': tokenData.token
        },
        body: JSON.stringify({ return_to: returnTo })
      });
      var logoutData = await logoutResponse.json();
      window.location.replace(logoutData.redirect || returnTo);
    } catch (error) {
      if (button) button.disabled = false;
      window.location.reload();
    }
  };

  /* ───── دکمه/منوی تغییر تم در هدر (روز/شب/سیستم) ───── */
  var navThemeBtn  = document.getElementById('nav-theme-toggle');
  var themeMenu    = document.getElementById('theme-menu');
  var themeMenuOpen = false;

  function syncThemeUI() {
    var mode = window.vatanGetThemeMode ? window.vatanGetThemeMode() : 'dark';
    // نگاشت حالت تم به نام آیکون (چون data-icon مقادیر moon/sun/system دارد نه dark/light/system)
    var iconForMode = { dark: 'moon', light: 'sun', system: 'system' };
    var wantIcon = iconForMode[mode] || 'moon';
    if (navThemeBtn) {
      navThemeBtn.querySelectorAll('.theme-trigger-icon').forEach(function (icon) {
        icon.classList.toggle('is-shown', icon.dataset.icon === wantIcon);
      });
    }
    if (themeMenu) {
      themeMenu.querySelectorAll('.theme-menu-item').forEach(function (item) {
        item.classList.toggle('is-active', item.dataset.themeChoice === mode);
      });
    }
    document.querySelectorAll('[data-mobile-theme]').forEach(function (item) {
      item.classList.toggle('is-active', item.dataset.mobileTheme === mode);
      item.setAttribute('aria-checked', item.dataset.mobileTheme === mode ? 'true' : 'false');
    });
  }

  function openThemeMenu() {
    if (!themeMenu) return;
    themeMenu.style.display = 'block';
    navThemeBtn.setAttribute('aria-expanded', 'true');
    themeMenuOpen = true;
  }

  function closeThemeMenu() {
    if (!themeMenu) return;
    themeMenu.style.display = 'none';
    navThemeBtn.setAttribute('aria-expanded', 'false');
    themeMenuOpen = false;
  }

  if (navThemeBtn && themeMenu) {
    syncThemeUI();

    navThemeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      themeMenuOpen ? closeThemeMenu() : openThemeMenu();
    });

    themeMenu.querySelectorAll('.theme-menu-item').forEach(function (item) {
      item.addEventListener('click', function (e) {
        e.stopPropagation();
        window.vatanSetTheme && window.vatanSetTheme(item.dataset.themeChoice);
        closeThemeMenu();
      });
    });

    document.addEventListener('click', function () {
      if (themeMenuOpen) closeThemeMenu();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && themeMenuOpen) closeThemeMenu();
    });

    document.addEventListener('vatan-theme-changed', syncThemeUI);
  }

  document.querySelectorAll('[data-mobile-theme]').forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      window.vatanSetTheme && window.vatanSetTheme(item.dataset.mobileTheme);
      syncThemeUI();
    });
  });

  function detectActiveKey() {
    var path = window.location.pathname;
    if (/\/profile/.test(path))   return 'profile';
    if (/\/trends/.test(path))    return 'trends';
    if (/\/create/.test(path))    return 'create';
    if (/\/explore/.test(path))   return 'explore';
    return 'home';
  }

  var activeKey = detectActiveKey();

  var topLinks = document.querySelectorAll('.topnav-link, .topnav-create');
  topLinks.forEach(function (link) {
    if (link.dataset.key === activeKey) link.classList.add('is-active');
  });

  // مدیریت دراپ‌داون آواتار با انیمیشن ورود و خروج کاملاً هماهنگ
  var trigger = document.getElementById('profile-menu-trigger');
  var dropdown = document.getElementById('vatan-profile-dropdown');

  function showDropdown() {
    dropdown.style.display = 'block';
    dropdown.classList.remove('animate-out');
    dropdown.classList.add('animate-in');
  }

  function hideDropdown() {
    if (dropdown && dropdown.style.display === 'block' && !dropdown.classList.contains('animate-out')) {
      dropdown.classList.remove('animate-in');
      dropdown.classList.add('animate-out');
      
      // تضمین اینکه استایل پنهان‌سازی حتماً پس از پایان کامل انیمیشن CSS رخ می‌دهد
      setTimeout(function() {
        dropdown.style.display = 'none';
        dropdown.classList.remove('animate-out');
      }, 180);
    }
  }

  if (trigger && dropdown) {
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (dropdown.style.display === 'block' && !dropdown.classList.contains('animate-out')) {
        hideDropdown();
      } else {
        showDropdown();
      }
    });

    document.addEventListener('click', function () {
      hideDropdown();
    });

    dropdown.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  // بستن پاپ‌آپ پروفایل با کلیک بیرون یا Esc
  var profilePopups = Array.from(document.querySelectorAll('.topnav-popup'));
  document.addEventListener('click', function (e) {
    var openPopup = profilePopups.find(function (popup) {
      var input = popup.querySelector('input[type="checkbox"]');
      return input && input.checked;
    });
    if (!openPopup || openPopup.contains(e.target)) return;

    // کلیک اول بیرون پاپ‌آپ فقط آن را می‌بندد و نباید لینک/محصول زیرش را فعال کند.
    e.preventDefault();
    e.stopPropagation();
    var input = openPopup.querySelector('input[type="checkbox"]');
    if (input) input.checked = false;
  }, true);
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    profilePopups.forEach(function (popup) {
      var input = popup.querySelector('input[type="checkbox"]');
      if (input) input.checked = false;
    });
  });

  // انیمیشن ردیاب چسبان منوی موبایل (Sliding Thumb)
  var bar   = document.getElementById('vatan-nav-bar');
  var thumb = document.getElementById('vatan-nav-thumb');
  var items = Array.from(document.querySelectorAll('.vatan-nav-item'));

  function getThumbProps(el) {
    if (!bar || !el) return { left: 0, width: 0 };
    // با getBoundingClientRect موقعیت واقعی رندرشده رو می‌خونیم؛ چون صفحه RTL هست
    // محاسبه‌ی دستی بر اساس index (چپ‌به‌راست) اشتباه می‌افتاد و پیل زیر آیتم غلط می‌نشست.
    var barRect = bar.getBoundingClientRect();
    var elRect  = el.getBoundingClientRect();
    return { left: elRect.left - barRect.left + 6, width: elRect.width - 12 };
  }

  function snapThumb(el) {
    var p = getThumbProps(el);
    thumb.style.transition  = 'none';
    thumb.style.left        = p.left + 'px';
    thumb.style.width       = p.width + 'px';
    thumb.style.visibility  = 'visible';
  }

  function slideThumb(el) {
    var p = getThumbProps(el);
    thumb.style.transition = 'left 360ms cubic-bezier(0.22,1,0.36,1), width 360ms cubic-bezier(0.22,1,0.36,1)';
    thumb.style.left       = p.left + 'px';
    thumb.style.width      = p.width + 'px';
    thumb.style.visibility = 'visible';
  }

  function setActive(el) {
    items.forEach(function (i) { i.classList.remove('is-active'); });
    el.classList.add('is-active');
  }

  if (activeKey && bar) {
    var activeEl = bar.querySelector('[data-key="' + activeKey + '"]');
    if (activeEl) {
      setActive(activeEl);
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { snapThumb(activeEl); });
      });
      window.addEventListener('load', function () { snapThumb(activeEl); });
      setTimeout(function () { snapThumb(activeEl); }, 300);
    }
  }

  items.forEach(function (item) {
    item.addEventListener('click', function (e) {
      var href = item.getAttribute('href');
      if(!href || href === '#') return;
      e.preventDefault();
      if (item.classList.contains('is-active')) return;
      setActive(item);
      slideThumb(item);
      setTimeout(function () { window.location.href = href; }, 320);
    });
  });

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var el = bar ? bar.querySelector('.vatan-nav-item.is-active') : null;
      if (el) snapThumb(el);
    }, 100);
  });

  if (window.ResizeObserver && bar) {
    new ResizeObserver(function () {
      var el = bar.querySelector('.vatan-nav-item.is-active');
      if (el) snapThumb(el);
    }).observe(bar);
  }
}());
</script>
