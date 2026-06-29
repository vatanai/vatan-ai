@extends('layouts.app')

@section('content')
<div class="cp-page" dir="rtl">

  {{-- padding بالا برای notch موبایل --}}
  <div style="padding-top: calc(env(safe-area-inset-top, 0px) + 16px);"></div>

  {{-- بخش ۱: هدر محصول --}}
  @include('app.partials.create.header')

  {{-- بخش ۲: دستورالعمل --}}
  @include('app.partials.create.instructions')

  {{-- بخش ۳: آپلود --}}
  @include('app.partials.create.upload')

  {{-- بخش ۴-۷ بعداً اضافه می‌شوند --}}

  {{-- فاصله پایین برای bottom nav --}}
  <div style="height: calc(env(safe-area-inset-bottom, 0px) + 120px);"></div>

</div>

<style>
.cp-page {
  font-family: 'YekanBakh', sans-serif;
  min-height: 100dvh;
  background: var(--bg, #000);
  direction: rtl;
}
</style>
@endsection
