@extends('layouts.admin')
@section('title', 'ساخت لینک دعوت برای کاربر — همکاری در فروش')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/referral-settings.css') }}?v={{ filemtime(public_path('admin/css/referral-settings.css')) }}">
@endpush

@php
  $userName = trim(($user->name ?: '').' '.($user->last_name ?: '')) ?: 'کاربر بدون نام';
  $activeLinkCount = $user->referralLinks->where('status', 'active')->whereNull('deactivated_at')->count();
@endphp

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content" dir="rtl">
    <div class="referral-settings-page referral-link-create-page">
      @if(session('success'))
        <div class="referral-alert is-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="referral-alert is-error"><i class="fa-solid fa-triangle-exclamation"></i>{{ $errors->first() }}</div>
      @endif

      <header class="referral-page-head">
        <div>
          <div class="referral-eyebrow"><i class="fa-solid fa-link"></i> همکاری در فروش / ساخت لینک</div>
          <h1>ساخت لینک دعوت برای کاربر</h1>
          <p>یک محصول را برای این کاربر انتخاب کنید؛ لینک دعوت فعال بلافاصله ساخته و در نمای کلی عملکرد او ثبت می‌شود.</p>
        </div>
        <a class="referral-action is-neutral" href="{{ route('admin.referrals.overview', ['inviter_search' => $user->id]) }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به عملکرد کاربر</a>
      </header>

      <section class="content-card referral-link-user-card">
        <div class="referral-card-head">
          @if($user->avatar)
            <img class="referral-inviter-avatar is-image" src="{{ asset('storage/'.$user->avatar) }}" alt="">
          @else
            <span class="referral-inviter-avatar">{{ mb_substr($userName, 0, 1) }}</span>
          @endif
          <div>
            <h2>{{ $userName }}</h2>
            <p>{{ $user->phone ?: 'شماره ثبت نشده' }} <span>•</span> {{ $user->referral_code ? 'کد دعوت: '.$user->referral_code : 'کد دعوت اختصاصی ثبت نشده' }}</p>
          </div>
          <span class="referral-head-status {{ $activeLinkCount > 0 ? 'is-active' : '' }}"><span></span>{{ number_format($activeLinkCount) }} لینک فعال</span>
        </div>
      </section>

      <section class="content-card referral-normal-link-card">
        <div class="referral-card-head">
          <span class="referral-card-icon is-success"><i class="fa-solid fa-house"></i></span>
          <div><h2>۱. لینک عادی دعوت</h2><p>مخاطب با این لینک وارد صفحهٔ اصلی سایت می‌شود؛ کلیک، ثبت‌نام، خرید و خروجی او همچنان با کد همین کاربر رصد می‌شود.</p></div>
          <span class="referral-link-type-badge">ورود به صفحه اصلی</span>
        </div>
        <div class="referral-card-body">
          @if($user->referral_code)
            @php
              $normalLinkUrl = route('referral.visit', ['code' => $user->referral_code]);
            @endphp
            <div class="referral-normal-link-box">
              <div class="referral-normal-link-main"><span>لینک کامل</span><code dir="ltr" title="{{ $normalLinkUrl }}">{{ $normalLinkUrl }}</code></div>
              <button type="button" class="referral-action is-approve referral-copy-button" data-copy-url="{{ $normalLinkUrl }}"><i class="fa-regular fa-copy"></i> کپی لینک کامل</button>
            </div>
            <div class="referral-normal-link-stats">
              <div><strong>{{ number_format($normalLinkStats['visits']) }}</strong><span>کلیک</span></div>
              <div><strong>{{ number_format($normalLinkStats['registrations']) }}</strong><span>ثبت‌نام</span></div>
              <div><strong>{{ number_format($normalLinkStats['purchases']) }}</strong><span>خرید موفق</span></div>
              <div><strong>{{ number_format($normalLinkStats['outputs']) }}</strong><span>خروجی دعوت‌شده‌ها</span></div>
              <div class="is-active"><strong>فعال</strong><span>تا زمان فعال بودن برنامه</span></div>
            </div>
          @else
            <div class="referral-normal-link-missing"><span>برای این کاربر هنوز لینک عادی ساخته نشده است.</span><form method="POST" action="{{ route('admin.referrals.users.standard-link.store', $user) }}">@csrf<button class="referral-action is-approve" type="submit"><i class="fa-solid fa-link"></i> ساخت لینک عادی</button></form></div>
          @endif
        </div>
      </section>

      <div class="referral-link-create-grid">
        <section class="content-card referral-link-form-card">
          <div class="referral-card-head">
            <span class="referral-card-icon is-primary"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
            <div><h2>۲. ساخت لینک محصول</h2><p>مخاطب با این لینک مستقیماً به محصول انتخابی می‌رسد و کلیک، ثبت‌نام، خرید و خروجی دعوت‌شده‌ها جداگانه رصد می‌شود.</p></div>
          </div>
          <form class="referral-card-body" method="POST" action="{{ route('admin.referrals.users.links.store', $user) }}">
            @csrf
            <label class="referral-field">
              <span>محصول مقصد</span>
              <select name="product_id" class="input-pro" required>
                <option value="">انتخاب محصول</option>
                @foreach($products as $product)
                  <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name_fa ?: $product->name_en }} @if($product->product_code) — {{ $product->product_code }} @endif</option>
                @endforeach
              </select>
              <small>اگر برای همین کاربر و محصول لینک فعال وجود داشته باشد، لینک تکراری ساخته نمی‌شود.</small>
            </label>
            <div class="referral-profile-note"><i class="fa-solid fa-circle-info"></i><span>بعد از ساخت، لینک کامل در کارت همین کاربر در نمای کلی همکاری در فروش نمایش داده می‌شود و کاربر نیز آن را در بخش لینک‌های دعوت خود خواهد دید.</span></div>
            <div class="referral-dialog-actions"><a class="referral-action is-neutral" href="{{ route('admin.referrals.overview', ['inviter_search' => $user->id]) }}">انصراف</a><button class="referral-action is-approve" type="submit"><i class="fa-solid fa-link"></i> ساخت لینک دعوت</button></div>
          </form>
        </section>

        <aside class="content-card referral-link-existing-card">
          <div class="referral-card-head"><span class="referral-card-icon is-info"><i class="fa-solid fa-list-check"></i></span><div><h2>لینک‌های قبلی کاربر</h2><p>برای جلوگیری از ساخت لینک تکراری، وضعیت لینک‌های فعلی را ببینید.</p></div></div>
          <div class="referral-card-body">
            @forelse($user->referralLinks as $link)
              @php
                $linkUrl = route('referral.link', ['referralLink' => $link->slug]);
                $linkProduct = $link->product?->name_fa ?: ($link->product?->name_en ?: 'محصول حذف‌شده');
                $linkActive = $link->status === 'active' && $link->deactivated_at === null;
              @endphp
              <div class="referral-existing-link">
                <div><strong>{{ $linkProduct }}</strong><code title="{{ $linkUrl }}">{{ $linkUrl }}</code><small>{{ number_format((int) $link->visits_count) }} کلیک <span>•</span> {{ number_format((int) $link->conversions_count) }} ثبت‌نام</small></div>
                <div class="referral-existing-link-actions">
                  <form method="POST" action="{{ route('admin.referrals.links.toggle', ['referralLink' => $link->id]) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="referral-link-status {{ $linkActive ? 'is-active' : 'is-inactive' }}">{{ $linkActive ? 'فعال' : 'غیرفعال' }}</button>
                  </form>
                  <form method="POST" action="{{ route('admin.referrals.links.destroy', ['referralLink' => $link->id]) }}" onsubmit="return confirm('این لینک حذف شود؟ اگر سابقه داشته باشد به‌صورت خودکار غیرفعال می‌شود.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="referral-icon-button referral-delete-link" title="حذف یا غیرفعال‌سازی لینک" aria-label="حذف یا غیرفعال‌سازی لینک"><i class="fa-solid fa-trash-can"></i></button>
                  </form>
                </div>
              </div>
            @empty
              <div class="referral-subtree-empty">هنوز لینکی برای این کاربر ساخته نشده است.</div>
            @endforelse
          </div>
        </aside>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
document.querySelectorAll('[data-copy-url]').forEach(function(button){button.addEventListener('click',async function(){const url=button.dataset.copyUrl;try{if(navigator.clipboard&&window.isSecureContext)await navigator.clipboard.writeText(url);else{const input=document.createElement('textarea');input.value=url;input.setAttribute('readonly','');input.style.position='fixed';input.style.opacity='0';document.body.appendChild(input);input.select();document.execCommand('copy');input.remove()}button.innerHTML='<i class="fa-solid fa-check"></i> کپی شد';setTimeout(function(){button.innerHTML='<i class="fa-regular fa-copy"></i> کپی لینک کامل'},1800)}catch(error){button.innerHTML='<i class="fa-solid fa-triangle-exclamation"></i> کپی نشد'}})});
</script>
@endsection
