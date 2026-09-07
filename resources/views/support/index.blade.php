@extends('layouts.app')

@section('page_title', 'پشتیبانی وطن | راهنمای ساخت عکس و ویدیو')

@push('meta')
<meta name="description" content="پشتیبانی وطن برای مشکلات ساخت عکس و ویدیو، پرداخت، اعتبار، حساب کاربری و سایر خدمات پلتفرم.">
<link rel="canonical" href="{{ route('support.index') }}">
<meta property="og:title" content="پشتیبانی وطن">
<meta property="og:description" content="پاسخ‌گویی به پرسش‌ها و پیگیری درخواست‌های کاربران وطن.">
@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/support.css') }}?v={{ filemtime(public_path('css/support.css')) }}">@endpush

@section('content')
<div class="support-page" dir="rtl">
  <div class="page-container support-shell">
    <header class="support-hero"><span class="support-kicker"><i class="fa-solid fa-headset"></i> مرکز پشتیبانی وطن</span><h1>اینجاییم تا تجربه‌ات ساده و بدون دردسر باشد</h1><p>اگر در ساخت تصویر یا ویدیو، پرداخت، اعتبار، حساب کاربری یا هر بخش دیگری مشکلی داشتی، از راه‌های زیر با تیم وطن در ارتباط باش.</p></header>

    <section class="support-channels" aria-labelledby="support-channels-title">
      <div class="support-section-head"><div><span class="support-kicker">راه‌های ارتباط</span><h2 id="support-channels-title">هر راهی که برایت راحت‌تر است</h2></div><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
      <div class="support-channel-grid">
        <a href="{{ route('support.index') }}#new-ticket" class="support-channel support-channel--primary"><i class="fa-solid fa-ticket"></i><span><b>تیکت پشتیبانی</b><small>از طریق پنل کاربری، با امکان پیگیری</small></span><i class="fa-solid fa-arrow-left"></i></a>
        <a href="https://t.me/vatan_support" target="_blank" rel="noopener noreferrer" class="support-channel"><i class="fa-brands fa-telegram"></i><span><b>پشتیبانی تلگرام</b><small>@vatan_support</small></span><i class="fa-solid fa-arrow-up-left-from-square"></i></a>
        <a href="https://t.me/ai_vatan" target="_blank" rel="noopener noreferrer" class="support-channel"><i class="fa-solid fa-bullhorn"></i><span><b>کانال تلگرام وطن</b><small>اخبار و آموزش‌های وطن</small></span><i class="fa-solid fa-arrow-up-left-from-square"></i></a>
        <a href="https://instagram.com/ai_vatan" target="_blank" rel="noopener noreferrer" class="support-channel"><i class="fa-brands fa-instagram"></i><span><b>اینستاگرام وطن</b><small>ارسال دایرکت به @ai_vatan</small></span><i class="fa-solid fa-arrow-up-left-from-square"></i></a>
        <a href="mailto:support@aivatan.com" class="support-channel"><i class="fa-solid fa-envelope"></i><span><b>ایمیل</b><small>support@aivatan.com</small></span><i class="fa-solid fa-arrow-up-left-from-square"></i></a>
      </div>
    </section>

    <div class="support-columns">
      <section class="support-card" id="new-ticket" aria-labelledby="new-ticket-title">
        <div class="support-section-head"><div><span class="support-kicker">پیگیری مستقیم</span><h2 id="new-ticket-title">درخواستت را ثبت کن</h2></div><i class="fa-solid fa-pen-to-square"></i></div>
        @auth
          <form action="{{ route('support.tickets.store') }}" method="POST" class="support-form">
            @csrf
            <label>موضوع<input type="text" name="subject" value="{{ old('subject') }}" placeholder="مثلاً خروجی ساخته نشد" required maxlength="180"></label>
            <label>دسته‌بندی<select name="category" required><option value="image">ساخت عکس</option><option value="video">ساخت ویدیو</option><option value="payment">پرداخت و اعتبار</option><option value="account">حساب کاربری</option><option value="other">سایر موارد</option></select></label>
            <label>شرح درخواست<textarea name="body" rows="5" placeholder="مشکل را کوتاه و دقیق توضیح بده؛ اگر امکانش هست اسکرین‌شات هم ضمیمه کن." required>{{ old('body') }}</textarea></label>
            <button type="submit">ارسال درخواست <i class="fa-solid fa-arrow-left"></i></button>
          </form>
        @else
          <div class="support-login-note"><i class="fa-solid fa-lock"></i><strong>برای ثبت و پیگیری تیکت وارد حساب شو</strong><p>بعد از ورود، تمام گفت‌وگوها و پاسخ‌های تیم پشتیبانی در همین بخش برایت قابل مشاهده است.</p><a href="{{ route('login', ['redirect' => route('support.index').'#new-ticket']) }}">ورود و ثبت درخواست</a></div>
        @endauth
        @if($errors->any())<div class="support-errors">{{ $errors->first() }}</div>@endif
      </section>

      <section class="support-card support-card--guide" aria-labelledby="support-guide-title"><div class="support-section-head"><div><span class="support-kicker">برای پاسخ سریع‌تر</span><h2 id="support-guide-title">این اطلاعات را بفرست</h2></div><i class="fa-solid fa-lightbulb"></i></div><ul><li>موضوع مشکل و نام محصول را بنویس.</li><li>زمان تقریبی رخداد و متن خطا را اضافه کن.</li><li>در صورت امکان، اسکرین‌شات یا شماره سفارش را بفرست.</li><li>لطفاً اطلاعات کارت بانکی یا رمز ورود را ارسال نکن.</li></ul><div class="support-note"><i class="fa-solid fa-clock"></i><span>تیم وطن درخواستت را بررسی می‌کند و در سریع‌ترین زمان ممکن پاسخ می‌دهد.</span></div></section>
    </div>

    @auth
      @if($tickets->isNotEmpty())
        <section class="support-card support-ticket-list" aria-labelledby="my-tickets-title"><div class="support-section-head"><div><span class="support-kicker">تاریخچه درخواست‌ها</span><h2 id="my-tickets-title">تیکت‌های من</h2></div></div><div class="support-ticket-items">@foreach($tickets as $ticket)<a href="{{ route('support.tickets.show', $ticket) }}"><span><b>{{ $ticket->subject }}</b><small>{{ $ticket->ticket_number }} · {{ $ticket->categoryLabel() }}</small></span><strong>{{ $ticket->statusLabel() }} <i class="fa-solid fa-chevron-left"></i></strong></a>@endforeach</div></section>
      @endif
    @endauth
    <p class="support-signoff">وطن؛ بساز، تجربه کن، خلق کن.</p>
  </div>
</div>
@endsection
