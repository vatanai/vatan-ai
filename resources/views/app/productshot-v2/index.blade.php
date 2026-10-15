@extends('layouts.app')

@section('page_title', 'پیش‌نمایش صفحات پروداکتی | وطن AI')

@push('meta')
  <meta name="robots" content="noindex,nofollow">
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/productshot-v2.css') }}">
@endpush

@section('content')
<div class="ps2" dir="rtl">
  <header class="ps2-sec-head"><div><h2>پیش‌نمایش بازطراحی صفحات پروداکتی</h2><p>نسخه‌ی آزمایشی برای تأیید؛ مسیرهای اصلی سایت دست‌نخورده‌اند.</p></div></header>
  @if(session('success'))<p class="ps2-flash">{{ session('success') }}</p>@endif
  <p><a class="ps2-btn ps2-btn-brand ps2-list-btn" href="{{ route('app.ps-v2.demo-seed') }}">بازسازی ۱۰ محصول نمونه (فقط لوکال)</a></p>
  <div class="ps2-list">
    @forelse($products as $p)
      <div class="ps2-list-row">
        <div><b>{{ $p->name_fa ?: $p->name_en }}</b><span>{{ $p->route_slug }} · {{ $p->status === 'active' ? 'فعال' : 'غیرفعال' }}</span></div>
        @if($p->status === 'active')
          <a class="ps2-btn ps2-list-btn" href="{{ route('app.ps-v2.pro', $p->route_slug) }}">صفحه محصول</a>
          <a class="ps2-btn ps2-btn-brand ps2-list-btn" href="{{ route('app.ps-v2.crepro', $p->route_slug) }}">صفحه ساخت</a>
        @endif
      </div>
    @empty
      <p>هنوز هیچ محصول پروداکتی دمو در این دیتابیس ثبت نشده.</p>
    @endforelse
  </div>
</div>
@endsection
