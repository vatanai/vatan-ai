@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.links.index').'"><i class="fa-solid fa-arrow-right"></i> بازگشت به لینک‌ها</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'ساخت و کوتاه‌سازی لینک',
  'subtitle' => 'برای هر محتوا یک لینک مستقل بسازید تا مسیر ورود و کیفیت بارگذاری آن جداگانه قابل اندازه‌گیری باشد',
  'actions' => $actions,
])

<div class="g-grid-main">
  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-link"></i> مشخصات لینک</div><div class="g-section-sub">لینک کوتاه بلافاصله پس از ثبت آماده استفاده است</div></div></div>
    <form class="g-form-grid g-card-pad" method="POST" action="{{ route('admin.growth.links.store') }}">
      @csrf
      <div class="g-field g-form-full"><label for="link-title">عنوان داخلی لینک</label><input class="g-input" id="link-title" name="title" value="{{ old('title') }}" required placeholder="مثلاً ریلز معرفی پرتره ـ مرداد"></div>
      <div class="g-field g-form-full"><label for="link-destination">نشانی صفحه مقصد</label><input class="g-input" id="link-destination" name="destination_url" value="{{ old('destination_url') }}" inputmode="url" dir="ltr" required placeholder="https://aivatan.com/app/product/..."></div>
      <div class="g-field"><label for="link-channel">کانال</label><select class="g-input" id="link-channel" name="channel" required>@foreach($channelLabels as $key => $label)<option value="{{ $key }}" @selected(old('channel', 'instagram') === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="g-field"><label for="link-type">نوع محتوا</label><input class="g-input" id="link-type" name="content_type" value="{{ old('content_type') }}" placeholder="پست، ریلز، ویدیو یا پیام"></div>
      <div class="g-field"><label for="link-campaign">نام کمپین</label><input class="g-input" id="link-campaign" name="campaign" value="{{ old('campaign') }}" placeholder="مثلاً کمپین تابستان"></div>
      <div class="g-field"><label for="link-slug">نام کوتاه دلخواه</label><input class="g-input" id="link-slug" name="slug" value="{{ old('slug') }}" dir="ltr" placeholder="summer-portrait"><small class="g-section-sub">فقط حروف انگلیسی، عدد، خط تیره و زیرخط</small></div>
      <label class="g-check g-form-full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> لینک بلافاصله فعال شود</label>
      <div class="g-form-actions"><a class="g-btn" href="{{ route('admin.growth.links.index') }}">انصراف</a><button class="g-btn g-btn-primary" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> ساخت لینک کوتاه</button></div>
    </form>
  </section>

  <aside class="g-stack">
    <section class="g-card g-card-pad">
      <div class="g-section-title"><i class="fa-solid fa-route"></i> معماری ثبت رویداد</div>
      <div class="g-funnel">
        <div class="g-funnel-row"><span class="g-funnel-label">کلیک لینک</span><div class="g-funnel-bar"><span style="width:100%"></span></div><span class="g-badge info">رویداد ۱</span></div>
        <div class="g-funnel-row"><span class="g-funnel-label">انتقال مقصد</span><div class="g-funnel-bar"><span style="width:78%"></span></div><span class="g-badge">خودکار</span></div>
        <div class="g-funnel-row"><span class="g-funnel-label">بازشدن صفحه</span><div class="g-funnel-bar"><span style="width:64%"></span></div><span class="g-badge success">رویداد ۲</span></div>
      </div>
      <div class="g-note"><strong>نکته مهم:</strong> کلیک و بازشدن صفحه مقصد مستقل ذخیره می‌شوند؛ بنابراین کلیک‌هایی که به بارگذاری واقعی صفحه نرسیده‌اند قابل تشخیص خواهند بود.</div>
    </section>
    <section class="g-card g-card-pad">
      <div class="g-section-title"><i class="fa-solid fa-database"></i> نگهداری داده</div>
      <p class="g-section-sub">داده‌ها در پنجره‌های متوالی ۲۴ ساعته خلاصه و نگهداری می‌شوند. شروع پنجره تازه باعث حذف یا صفرشدن پنجره قبلی نمی‌شود.</p>
      <div class="g-breakdown-row"><span>رویداد خام</span><strong>دائمی</strong></div>
      <div class="g-breakdown-row"><span>خلاصه ۲۴ ساعته</span><strong>آرشیوی</strong></div>
      <div class="g-breakdown-row"><span>صفحه جاری</span><strong>زنده</strong></div>
    </section>
  </aside>
</div>
