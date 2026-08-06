@extends('layouts.admin')
@section('title', 'گالری مدل‌های صفحه هوم — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">
    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">گالری همه مدل‌های صفحه هوم</h1>
        <p class="text-[13px]" style="color:var(--text-soft);">مرجع تصویری مدیران و طراحان برای مشاهده نام، کد و خروجی واقعی تمام سکشن‌ها</p>
      </div>
      <a href="{{ route('admin.home-builder.index') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت به مدیریت هوم</a>
    </div>

    <div class="flex flex-col gap-5">
      @foreach($typeRegistry as $typeKey => $type)
        <section class="content-card" style="padding:14px;">
          <div class="flex items-center gap-3 mb-4">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:var(--primary-l);color:var(--primary);border:1px solid var(--primary-m);"><i class="{{ $type['icon'] }}"></i></span>
            <div>
              <h2 class="text-[15px] font-extrabold" style="color:var(--text-h);">{{ $type['label'] }}</h2>
              <p class="text-[11px] mt-1" style="color:var(--text-soft);">{{ $type['description'] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 max-[1100px]:grid-cols-1">
            @foreach($type['layouts'] as $layoutKey => $layout)
              <article class="rounded-xl overflow-hidden" style="border:1px solid var(--border);background:var(--input-bg);">
                <div class="flex items-center justify-between gap-2 px-3 py-2" style="border-bottom:1px solid var(--border);">
                  <strong class="text-[12px]" style="color:var(--text-h);">{{ $layout['label'] }}</strong>
                  <code class="text-[10px]" dir="ltr" style="color:var(--text-soft);">{{ $typeKey }}:{{ $layoutKey }}</code>
                </div>
                <iframe loading="lazy" title="{{ $layout['label'] }}" src="{{ route('admin.home-builder.showcase.preview', ['type' => $typeKey, 'layout' => $layoutKey]) }}" style="display:block;width:100%;height:430px;border:0;background:var(--card-bg);"></iframe>
              </article>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  </div>
</main>
@endsection
