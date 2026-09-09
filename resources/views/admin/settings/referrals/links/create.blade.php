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

      <div class="referral-link-create-grid">
        <section class="content-card referral-link-form-card">
          <div class="referral-card-head">
            <span class="referral-card-icon is-primary"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
            <div><h2>مشخصات لینک دعوت</h2><p>لینک ساخته‌شده به محصول انتخابی هدایت می‌شود و کلیک، ثبت‌نام، خرید و خروجی دعوت‌شده‌ها را ثبت می‌کند.</p></div>
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
                <span class="referral-link-status {{ $linkActive ? 'is-active' : 'is-inactive' }}">{{ $linkActive ? 'فعال' : 'غیرفعال' }}</span>
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
