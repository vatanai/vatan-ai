@extends('layouts.admin')

@section('title', 'ورودی‌های کاربر — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/user-gallery.css') }}?v={{ filemtime(public_path('admin/css/user-gallery.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @if(session('success'))
      <div class="mb-4 p-3 rounded-xl border bg-[var(--success-l)] border-[var(--success-m)] text-[var(--success)] text-[11px]">{{ session('success') }}</div>
    @endif
    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[17px] font-extrabold text-[var(--text-h)]">ورودی‌های {{ trim(($user->name ?? '').' '.($user->last_name ?? '')) ?: 'کاربر' }}</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">شناسه کاربر: <span dir="ltr">{{ $user->id }}</span> · رضایت ذخیره‌سازی: {{ $setting->enabled ? 'فعال' : 'غیرفعال' }}</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ route('admin.users.gallery.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] hover:border-[var(--primary)] hover:text-[var(--primary)]"><i class="fa-solid fa-photo-film"></i> همه ورودی‌ها</a>
        <a href="{{ route('admin.users.index', ['show_user' => $user->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] hover:border-[var(--primary)] hover:text-[var(--primary)]"><i class="fa-solid fa-user"></i> پروفایل کاربر</a>
      </div>
    </div>

    <section class="user-gallery-shortcuts mb-5" aria-label="میانبرهای کاربر">
      @foreach($shortcutLinks as $shortcut)
        <a href="{{ $shortcut['url'] }}" class="user-gallery-shortcut {{ $shortcut['class'] }}">
          <span class="user-gallery-shortcut-icon"><i class="fa-solid {{ $shortcut['icon'] }}"></i></span>
          <span class="user-gallery-shortcut-copy"><strong>{{ $shortcut['label'] }}</strong><small>{{ $shortcut['description'] }}</small></span>
          <i class="fa-solid fa-arrow-left user-gallery-shortcut-arrow"></i>
        </a>
      @endforeach
    </section>

    <section class="user-gallery-finance mb-5">
      <div class="user-gallery-section-head">
        <div><h2>خلاصه مالی و اعتبار</h2><p>نمایش سریع وضعیت خرید، اعتبار و مصرف مرتبط با این کاربر</p></div>
        <a href="{{ route('admin.finance.cases.index', ['user_id' => $user->id]) }}" class="user-gallery-section-link">گزارش کامل مالی <i class="fa-solid fa-arrow-left"></i></a>
      </div>
      <div class="user-gallery-finance-grid">
        <div class="user-gallery-finance-card"><span>پرونده‌های مالی</span><strong>{{ number_format($financeSummary['cases']) }}</strong><small>{{ number_format($financeSummary['purchases']) }} خرید متصل</small></div>
        <div class="user-gallery-finance-card success"><span>مبلغ خریدها</span><strong>{{ number_format($financeSummary['paid']) }}</strong><small>تومان</small></div>
        <div class="user-gallery-finance-card info"><span>اعتبار دریافت‌شده</span><strong>{{ number_format($financeSummary['granted']) }}</strong><small>اعتبار</small></div>
        <div class="user-gallery-finance-card warning"><span>اعتبار مصرف‌شده</span><strong>{{ number_format($financeSummary['used']) }}</strong><small>{{ number_format($financeSummary['revenue']) }} تومان درآمد تخصیص‌یافته</small></div>
      </div>
    </section>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-visible mb-5" data-creator-product-picker>
      <div class="p-4 border-b border-[var(--border)]">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <h2 class="text-[12px] font-bold text-[var(--text-h)]">اختصاص مالک محصول</h2>
            <p class="mt-1 text-[10px] leading-6 text-[var(--text-soft)]">از همین گالری یک محصول را جست‌وجو و مالک تجاری آن را این کاربر قرار بده. فعال‌سازی پاداش همچنان از تنظیمات خود محصول انجام می‌شود.</p>
          </div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[var(--primary-l)] text-[var(--primary)] text-[9px] font-bold"><i class="fa-solid fa-link"></i> مستقل از رفرال</span>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.users.creator-reward-product.assign', $user) }}" class="p-4 grid grid-cols-[minmax(0,1fr)_auto] gap-3 items-end max-[560px]:grid-cols-1">
        @csrf
        <div class="relative">
          <label for="creator-product-search" class="block mb-1.5 text-[10px] font-bold text-[var(--text-main)]">جست‌وجوی نام، کد یا شناسه محصول</label>
          <input type="hidden" name="product_id" id="creator-product-id" data-creator-product-id>
          <input type="search" id="creator-product-search" autocomplete="off" placeholder="مثلاً فاز سه یا تست سایت۱" class="w-full h-10 px-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] outline-none focus:border-[var(--primary)]" data-creator-product-search>
          <div class="hidden absolute z-30 top-[68px] right-0 left-0 max-h-64 overflow-y-auto rounded-xl border bg-[var(--card-bg)] border-[var(--border)] shadow-xl" data-creator-product-results></div>
          <div class="min-h-5 mt-1.5 text-[10px] text-[var(--text-soft)]" data-creator-product-status>یک محصول را از نتایج انتخاب کن.</div>
        </div>
        <button type="submit" disabled class="h-10 px-4 rounded-xl border border-[var(--primary)] bg-[var(--primary)] text-white text-[10.5px] font-bold disabled:opacity-40 disabled:cursor-not-allowed" data-creator-product-submit><i class="fa-solid fa-user-tag ml-1"></i> اختصاص مالک</button>
      </form>
    </section>

    <section class="user-gallery-builds mb-5">
      <div class="user-gallery-section-head">
        <div><h2>ساخته‌شده‌ها (ورودی و خروجی‌ها)</h2><p>هر کارت یک سفارش را با ورودی، خروجی و خلاصه مالی همان ساخت نشان می‌دهد.</p></div>
        <span class="user-gallery-section-count">{{ number_format(count($builds)) }} ساخت</span>
      </div>
      <div class="user-gallery-build-list">
        @forelse($builds as $build)
          @php($buildStatusClass = in_array($build['status_key'], ['completed', 'success'], true) ? 'success' : (in_array($build['status_key'], ['failed', 'stopped'], true) ? 'danger' : 'warning'))
          <article class="user-gallery-build-card">
            <div class="user-gallery-build-head">
              <div><h3>{{ $build['product_name'] }}</h3><span>{{ $build['order_number'] }} · {{ \App\Support\Jalali::formatNumeric($build['date']) }}</span></div>
              <span class="user-gallery-status {{ $buildStatusClass }}">{{ $build['status'] }}</span>
            </div>
            <div class="user-gallery-build-content">
              <div class="user-gallery-build-media-block"><label>ورودی ساخت</label><div class="user-gallery-build-media">
                @forelse($build['input_media'] as $input)
                  @if($input['type'] === 'text')
                    <a href="{{ $input['url'] }}" target="_blank" rel="noopener" class="user-gallery-build-text" title="{{ $input['text'] ?: $input['label'] }}"><i class="fa-solid fa-align-right"></i><span>{{ $input['text'] ?: $input['label'] }}</span></a>
                  @elseif($input['type'] === 'video')
                    <a href="{{ $input['url'] }}" target="_blank" rel="noopener" class="user-gallery-build-thumb"><video src="{{ $input['url'] }}" preload="metadata" muted playsinline></video><i class="fa-solid fa-play"></i></a>
                  @else
                    <a href="{{ $input['url'] }}" target="_blank" rel="noopener" class="user-gallery-build-thumb"><img src="{{ $input['url'] }}" alt="{{ $input['label'] }}" loading="lazy"></a>
                  @endif
                @empty
                  <span class="user-gallery-build-empty">ورودی ذخیره نشده</span>
                @endforelse
              </div></div>
              <div class="user-gallery-build-media-block"><label>خروجی ساخت</label><div class="user-gallery-build-media">
                @forelse($build['outputs'] as $output)
                  <a href="{{ $output['url'] }}" target="_blank" rel="noopener" class="user-gallery-build-thumb">@if($output['type'] === 'video')<video src="{{ $output['url'] }}" preload="metadata" muted playsinline></video><i class="fa-solid fa-play"></i>@else<img src="{{ $output['url'] }}" alt="{{ $output['label'] }}" loading="lazy">@endif</a>
                @empty
                  <span class="user-gallery-build-empty">خروجی ثبت نشده</span>
                @endforelse
              </div></div>
              <div class="user-gallery-build-facts">
                <div><span>مصرف اعتبار</span><strong>{{ number_format($build['credits']) }}</strong></div>
                <div><span>درآمد تخصیص‌یافته</span><strong>{{ number_format($build['revenue']) }} <small>تومان</small></strong></div>
                <div><span>پرونده مالی</span><strong>{{ $build['case_number'] ?: 'ثبت نشده' }}</strong></div>
              </div>
            </div>
            @if($build['prompt'])<p class="user-gallery-build-prompt"><i class="fa-solid fa-message"></i>{{ $build['prompt'] }}</p>@endif
            <div class="user-gallery-build-actions">
              @if($build['case_url'])<a href="{{ $build['case_url'] }}" class="user-gallery-build-action success"><i class="fa-solid fa-chart-pie"></i> پرونده مالی</a>@endif
              <a href="{{ $build['order_url'] }}" class="user-gallery-build-action"><i class="fa-solid fa-receipt"></i> جزئیات سفارش</a>
              <a href="{{ route('admin.users.gallery.show', $user->id) }}" class="user-gallery-build-action neutral"><i class="fa-solid fa-images"></i> ورودی‌های کاربر</a>
            </div>
          </article>
        @empty
          <div class="user-gallery-build-empty-state"><i class="fa-solid fa-wand-magic-sparkles"></i><strong>هنوز ساختی برای این کاربر ثبت نشده است.</strong><span>پس از ثبت سفارش، خلاصه ورودی، خروجی و مالی آن اینجا نمایش داده می‌شود.</span></div>
        @endforelse
      </div>
    </section>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
      <div class="p-4 border-b border-[var(--border)] flex items-center justify-between"><h2 class="text-[12px] font-bold text-[var(--text-h)]">ورودی‌های ذخیره‌شده</h2><span class="text-[10px] text-[var(--text-soft)]">{{ number_format($items->total()) }} آیتم</span></div>
      <div class="grid grid-cols-5 gap-3 p-4 max-[1100px]:grid-cols-4 max-[800px]:grid-cols-3 max-[560px]:grid-cols-2 max-[390px]:grid-cols-1">
        @forelse($items as $item)
          <article class="overflow-hidden rounded-2xl border bg-[var(--page-bg)] border-[var(--border)]">
            <div class="relative aspect-square bg-[var(--input-bg)] flex items-center justify-center overflow-hidden">
              @if($item->input_kind === 'image')
                <a href="{{ route('admin.users.gallery.preview', [$user->id, $item->id]) }}" target="_blank" rel="noopener" class="block w-full h-full"><img src="{{ route('admin.users.gallery.preview', [$user->id, $item->id]) }}" alt="عکس ورودی" class="w-full h-full object-cover" loading="lazy"></a>
              @elseif($item->input_kind === 'video')
                <video src="{{ route('admin.users.gallery.original', [$user->id, $item->id]) }}" class="w-full h-full object-contain" controls preload="metadata"></video>
              @elseif($item->input_kind === 'text')
                <div class="w-full h-full p-4 overflow-hidden text-[11px] leading-7 text-[var(--text-main)] whitespace-pre-wrap">{{ $item->text_preview }}</div>
              @else
                <a href="{{ route('admin.users.gallery.original', [$user->id, $item->id]) }}" target="_blank" rel="noopener" class="text-center text-[var(--primary)]"><i class="fa-solid fa-file text-[30px]"></i><span class="block mt-2 text-[10px]">بازکردن فایل</span></a>
              @endif
              <span class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-[var(--card-bg)]/90 border border-[var(--border)] text-[9px] text-[var(--text-main)]">{{ $item->input_label }}</span>
            </div>
            <div class="p-2.5">
              <div class="text-[9.5px] text-[var(--text-soft)]">انقضا: {{ \App\Support\Jalali::formatNumeric($item->expires_at) }} · {{ number_format($item->size / 1048576, 2) }} مگابایت</div>
              <div class="mt-2 flex items-center justify-between gap-2">
                <a href="{{ route('admin.users.gallery.original', [$user->id, $item->id]) }}" target="_blank" rel="noopener" class="text-[10px] text-[var(--info)]" title="فایل اصلی"><i class="fa-solid fa-download"></i></a>
                <form method="POST" action="{{ route('admin.users.gallery.destroy', [$user->id, $item->id]) }}" onsubmit="return confirm('این تصویر از گالری حذف شود؟')">@csrf @method('DELETE')<button class="text-[10px] text-[var(--danger)]" title="حذف" aria-label="حذف تصویر"><i class="fa-solid fa-trash"></i></button></form>
              </div>
            </div>
          </article>
        @empty
          <div class="col-span-full p-12 text-center text-[11px] text-[var(--text-soft)]">برای این کاربر ورودی ذخیره‌شده‌ای وجود ندارد.</div>
        @endforelse
      </div>
      <div class="p-4 border-t border-[var(--border)]">{{ $items->links() }}</div>
    </section>
  </div>
</main>
@endsection

@section('scripts')
<script>
(() => {
  const root = document.querySelector('[data-creator-product-picker]');
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';
  const search = root.querySelector('[data-creator-product-search]');
  const hidden = root.querySelector('[data-creator-product-id]');
  const results = root.querySelector('[data-creator-product-results]');
  const status = root.querySelector('[data-creator-product-status]');
  const submit = root.querySelector('[data-creator-product-submit]');
  let timer = null;

  const setStatus = (message, error = false) => {
    status.textContent = message;
    status.classList.toggle('text-[var(--danger)]', error);
    status.classList.toggle('text-[var(--text-soft)]', !error);
  };
  const selectProduct = (product) => {
    hidden.value = product.id;
    search.value = product.name;
    submit.disabled = false;
    results.classList.add('hidden');
    setStatus(product.owner ? `مالک فعلی: ${product.owner}` : 'این محصول مالک مشخصی ندارد؛ آماده‌ی اختصاص است.');
  };
  const render = (products) => {
    results.innerHTML = '';
    if (!products.length) {
      results.innerHTML = '<div class="px-3 py-3 text-[10px] text-[var(--text-soft)]">محصولی پیدا نشد.</div>';
      results.classList.remove('hidden');
      return;
    }
    products.forEach((product) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'w-full px-3 py-2.5 text-right border-b border-[var(--border)] last:border-b-0 hover:bg-[var(--primary-l)]';
      button.innerHTML = '<span class="block text-[11px] font-bold text-[var(--text-main)]"></span><span class="block mt-1 text-[9px] text-[var(--text-soft)]"></span>';
      button.children[0].textContent = product.name;
      button.children[1].textContent = [product.type, product.status, product.owner ? `مالک: ${product.owner}` : 'بدون مالک'].join(' · ');
      button.addEventListener('click', () => selectProduct(product));
      results.appendChild(button);
    });
    results.classList.remove('hidden');
  };
  search.addEventListener('input', () => {
    hidden.value = '';
    submit.disabled = true;
    const value = search.value.trim();
    if (!value) { results.classList.add('hidden'); setStatus('یک محصول را از نتایج انتخاب کن.'); return; }
    clearTimeout(timer);
    timer = setTimeout(async () => {
      try {
        const response = await fetch(@json(route('admin.api.products.search')) + '?q=' + encodeURIComponent(value), { headers: { Accept: 'application/json' } });
        const payload = await response.json();
        render(Array.isArray(payload.data) ? payload.data : []);
      } catch (_) {
        render([]);
        setStatus('جست‌وجوی محصول انجام نشد؛ دوباره تلاش کن.', true);
      }
    }, 220);
  });
  document.addEventListener('click', (event) => { if (!root.contains(event.target)) results.classList.add('hidden'); });
})();
</script>
@endsection
