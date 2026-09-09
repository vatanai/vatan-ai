@php
  $creatorRewardLabels = [
      'image_free' => ['پاداش عکس با اعتبار رایگان', 'برای ساخت موفق عکس با اعتبار رایگان'],
      'video_free' => ['پاداش ویدیو با اعتبار رایگان', 'برای ساخت موفق ویدیو با اعتبار رایگان'],
      'image_paid' => ['پاداش عکس با اعتبار خریداری‌شده', 'برای ساخت موفق عکس با اعتبار خریداری‌شده'],
      'video_paid' => ['پاداش ویدیو با اعتبار خریداری‌شده', 'برای ساخت موفق ویدیو با اعتبار خریداری‌شده'],
  ];
@endphp

{{-- ═══════════════════ Card ۰.۱ — پاداش مالک محصول ═══════════════════ --}}
<section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5" data-creator-reward-settings>
  <div class="flex items-start justify-between gap-3 flex-wrap mb-4 pb-3 border-b border-[var(--b1)]">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
        <i class="fa-solid fa-hand-holding-heart text-[var(--accent)]"></i>
        پاداش مالک محصول
      </div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1 max-w-2xl">
        با روشن‌کردن این گزینه، استفاده‌ی موفق کاربران از این محصول برای مالک آن اعتبار پاداش جداگانه ثبت می‌کند؛ این بخش به سیستم رفرال وابسته نیست.
      </div>
    </div>
    <label class="relative w-10 h-6 shrink-0 block cursor-pointer" for="creator-reward-enabled">
      <input type="checkbox" name="creator_reward_enabled" value="1" id="creator-reward-enabled" class="sr-only peer" @checked($creatorRewardEnabled) aria-controls="creator-reward-settings-body" aria-expanded="{{ $creatorRewardEnabled ? 'true' : 'false' }}">
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-4 before:h-4 before:right-1 before:top-1 before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-4 peer-checked:before:bg-white"></span>
    </label>
  </div>

  <div id="creator-reward-settings-body" class="space-y-4 {{ $creatorRewardEnabled ? '' : 'hidden' }}" aria-hidden="{{ $creatorRewardEnabled ? 'false' : 'true' }}">
    <div class="hidden rounded-lg border border-[var(--danger)]/35 bg-[var(--danger)]/10 px-3 py-2.5 text-[10.5px] font-bold leading-6 text-[var(--danger)]" data-creator-reward-alert role="alert" aria-live="polite"></div>
    <div class="rounded-xl border border-[var(--primary-m)] bg-[var(--primary-l)]/35 p-3 flex items-start gap-2 text-[10.5px] text-[var(--text2)]">
      <i class="fa-solid fa-circle-info text-[var(--primary)] mt-0.5"></i>
      <span>مالک محصول باید مشخص باشد و هر چهار مقدار تکمیل شوند. پاداش فقط بعد از ساخت موفق خروجی ثبت می‌شود؛ ساخت ناموفق یا لغوشده برای مالک اعتبار ایجاد نمی‌کند.</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] gap-4 items-start">
      <div class="relative">
        <label for="creator-reward-owner-search" class="block text-[11px] font-bold text-[var(--text2)] mb-1.5">مالک محصول <span class="text-[var(--danger)]">*</span></label>
        <input type="hidden" name="creator_reward_owner_id" id="creator-reward-owner-id" value="{{ $creatorRewardOwnerId }}">
        <input type="search" id="creator-reward-owner-search" autocomplete="off" placeholder="جست‌وجوی نام، شماره یا ایمیل کاربر" value="{{ $creatorRewardOwner?->name ? trim($creatorRewardOwner->name . ' ' . ($creatorRewardOwner->last_name ?? '')) : '' }}" class="w-full h-10 px-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-xs text-[var(--text)] outline-none focus:border-[var(--primary)]" data-creator-reward-owner-search>
        <div class="absolute z-30 top-[70px] right-0 left-0 hidden max-h-52 overflow-y-auto rounded-lg border border-[var(--b1)] bg-[var(--s1)] shadow-lg" data-creator-reward-owner-results></div>
        <div class="min-h-5 mt-1.5 text-[10px] text-[var(--text3)]" data-creator-reward-owner-status>
          {{ $creatorRewardOwner ? 'مالک انتخاب شده است.' : 'برای ثبت پاداش، یک کاربر را انتخاب کنید.' }}
        </div>
        <div class="hidden mt-1.5 text-[10px] text-[var(--danger)]" data-creator-reward-owner-error>انتخاب مالک محصول الزامی است.</div>
      </div>

      <div class="rounded-xl border border-[var(--b1)] bg-[var(--s1)] p-3 text-[10.5px] text-[var(--text3)]">
        <div class="flex items-center gap-2 text-[var(--text2)] font-bold mb-2"><i class="fa-solid fa-shield-halved text-[var(--green)]"></i> منبع مستقل اعتبار</div>
        <p class="m-0 leading-6">این پاداش با عنوان «پاداش مالک محصول» در دفتر مالی جدا ثبت می‌شود و با اعتبار دعوت، درآمد همکاری در فروش و هزینه‌ی ساخت کاربر قاطی نمی‌شود.</p>
      </div>
    </div>

    <div>
      <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
        <div class="text-[11px] font-bold text-[var(--text2)]">مقدار پاداش به ازای هر ساخت موفق</div>
        <span class="text-[10px] text-[var(--text3)]">صفر یعنی پاداش آن حالت غیرفعال است.</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($creatorRewardLabels as $rewardKey => [$rewardTitle, $rewardHint])
          <label class="flex items-center justify-between gap-3 p-3 rounded-xl bg-[var(--s1)] border border-[var(--b1)]">
            <span class="min-w-0">
              <span class="block text-[11px] font-bold text-[var(--text)]">{{ $rewardTitle }}</span>
              <span class="block text-[9.5px] text-[var(--text3)] mt-1">{{ $rewardHint }}</span>
            </span>
            <span class="flex items-center gap-1.5 shrink-0">
              <input type="number" name="creator_reward_settings[{{ $rewardKey }}]" value="{{ $creatorRewardSettings[$rewardKey] ?? $creatorRewardDefaults[$rewardKey] }}" min="0" max="1000000" step="1" inputmode="numeric" class="w-20 h-10 px-2 bg-[var(--s2)] border border-[var(--b1)] rounded-lg text-sm font-bold text-[var(--text)] ltr text-left" data-creator-reward-input="{{ $rewardKey }}">
              <span class="text-[10px] text-[var(--text3)]">اعتبار</span>
            </span>
          </label>
        @endforeach
      </div>
    </div>
  </div>
</section>

<script>
(() => {
  const root = document.querySelector('[data-creator-reward-settings]');
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';

  const toggle = root.querySelector('#creator-reward-enabled');
  const body = root.querySelector('#creator-reward-settings-body');
  const ownerId = root.querySelector('#creator-reward-owner-id');
  const ownerSearch = root.querySelector('[data-creator-reward-owner-search]');
  const ownerResults = root.querySelector('[data-creator-reward-owner-results]');
  const ownerStatus = root.querySelector('[data-creator-reward-owner-status]');
  const ownerError = root.querySelector('[data-creator-reward-owner-error]');
  const rewardAlert = root.querySelector('[data-creator-reward-alert]');
  const inputs = [...root.querySelectorAll('[data-creator-reward-input]')];
  const form = document.getElementById('real-product-form');
  let searchTimer = null;

  const setMessage = (message, isError = false) => {
    if (!ownerStatus) return;
    ownerStatus.textContent = message;
    ownerStatus.classList.toggle('text-[var(--danger)]', isError);
    ownerStatus.classList.toggle('text-[var(--text3)]', !isError);
  };

  const showRewardAlert = (message) => {
    if (!rewardAlert) return;
    rewardAlert.textContent = message;
    rewardAlert.classList.remove('hidden');
  };

  const hideRewardAlert = () => {
    if (!rewardAlert) return;
    rewardAlert.textContent = '';
    rewardAlert.classList.add('hidden');
  };

  const syncRequiredState = () => {
    const enabled = !!toggle?.checked;
    body?.classList.toggle('hidden', !enabled);
    body?.setAttribute('aria-hidden', enabled ? 'false' : 'true');
    toggle?.setAttribute('aria-expanded', enabled ? 'true' : 'false');
    inputs.forEach((input) => {
      input.required = enabled;
      input.disabled = !enabled;
    });
    if (ownerId) ownerId.required = enabled;
    if (!enabled) {
      ownerError?.classList.add('hidden');
      hideRewardAlert();
      setMessage('پاداش مالک محصول خاموش است.');
    } else if (ownerId?.value) {
      setMessage('مالک انتخاب شده است.');
    } else {
      setMessage('برای ثبت پاداش، یک کاربر را انتخاب کنید.');
    }
  };

  const selectOwner = (user) => {
    if (!ownerId || !ownerSearch) return;
    ownerId.value = user.id;
    ownerSearch.value = user.name || `کاربر #${user.id}`;
    ownerResults?.classList.add('hidden');
    ownerError?.classList.add('hidden');
    setMessage(`مالک محصول: ${ownerSearch.value}`);
    if (inputs.every((input) => input.value !== '' && Number(input.value) >= 0)) hideRewardAlert();
  };

  const renderResults = (users) => {
    if (!ownerResults) return;
    ownerResults.innerHTML = '';
    if (!users.length) {
      ownerResults.innerHTML = '<div class="px-3 py-3 text-[10px] text-[var(--text3)]">کاربری پیدا نشد.</div>';
      ownerResults.classList.remove('hidden');
      return;
    }
    users.forEach((user) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'w-full px-3 py-2.5 text-right hover:bg-[var(--primary-l)]/30 border-b border-[var(--b1)] last:border-b-0';
      button.innerHTML = `<span class="block text-[11px] font-bold text-[var(--text)]"></span><span class="block text-[9px] text-[var(--text3)] mt-0.5"></span>`;
      button.children[0].textContent = user.name || `کاربر #${user.id}`;
      button.children[1].textContent = [user.phone, user.email].filter(Boolean).join(' · ');
      button.addEventListener('click', () => selectOwner(user));
      ownerResults.appendChild(button);
    });
    ownerResults.classList.remove('hidden');
  };

  ownerSearch?.addEventListener('input', () => {
    if (!ownerSearch.value.trim()) {
      if (ownerId) ownerId.value = '';
      ownerResults?.classList.add('hidden');
      setMessage('برای ثبت پاداش، یک کاربر را انتخاب کنید.');
      return;
    }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
      try {
        const response = await fetch(@json(route('admin.api.users.search')) + '?q=' + encodeURIComponent(ownerSearch.value.trim()) + '&limit=8', { headers: { Accept: 'application/json' } });
        const payload = await response.json();
        renderResults(Array.isArray(payload.data) ? payload.data : []);
      } catch (_) {
        renderResults([]);
        setMessage('جست‌وجوی کاربر انجام نشد؛ دوباره تلاش کنید.', true);
      }
    }, 220);
  });

  inputs.forEach((input) => input.addEventListener('input', () => {
    if (toggle?.checked && ownerId?.value && inputs.every((item) => item.value !== '' && Number(item.value) >= 0)) {
      hideRewardAlert();
    }
  }));

  document.addEventListener('click', (event) => {
    if (!root.contains(event.target)) ownerResults?.classList.add('hidden');
  });

  window.validateCreatorRewardSettings = () => {
    if (!toggle?.checked) {
      hideRewardAlert();
      return true;
    }
    const missingOwner = !ownerId?.value;
    const missingValue = inputs.find((input) => input.value === '' || Number(input.value) < 0);
    if (!missingOwner && !missingValue) {
      hideRewardAlert();
      return true;
    }
    ownerError?.classList.toggle('hidden', !missingOwner);
    if (missingValue) missingValue.focus();
    else ownerSearch?.focus();
    showRewardAlert('برای فعال‌بودن پاداش مالک محصول، مالک و هر چهار مقدار پاداش را تکمیل کنید.');
    return false;
  };

  form?.addEventListener('submit', (event) => {
    if (!window.validateCreatorRewardSettings()) event.preventDefault();
  });

  toggle?.addEventListener('change', syncRequiredState);
  syncRequiredState();
})();
</script>
