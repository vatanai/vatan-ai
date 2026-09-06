@extends('layouts.admin')
@section('title', 'لاگ تصاویر کاربران — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[16px] font-extrabold text-[var(--text-h)]">لاگ تصاویر خلق‌شده</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">همه خروجی‌های تصویری ثبت‌شده توسط کاربران</p>
      </div>
      <div class="flex items-center gap-1 p-1 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <a href="{{ route('admin.users.all_activities') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-timeline ml-1 text-[10px]"></i> فعالیت‌ها</a>
        <a href="{{ route('admin.users.index') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-users ml-1 text-[10px]"></i> کاربران</a>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-5 max-[640px]:grid-cols-1">
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">کل تصاویر ثبت‌شده</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--text-h)]">{{ number_format($generatedImages->total()) }}</div>
      </div>
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">تصاویر این صفحه</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--success)]">{{ number_format($generatedImages->count()) }}</div>
      </div>
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">صفحه فعلی</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--info)]">{{ number_format($generatedImages->currentPage()) }}</div>
      </div>
    </div>

    <section class="grid grid-cols-3 gap-4 max-[1100px]:grid-cols-2 max-[640px]:grid-cols-1">
      @forelse($generatedImages as $generatedImage)
        <article class="overflow-hidden rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="aspect-square bg-[var(--page-bg)] border-b border-[var(--border)]">
            @if($generatedImage->image_path)
              <img src="{{ asset('storage/'.$generatedImage->image_path) }}" alt="تصویر خلق‌شده" class="w-full h-full object-cover" loading="lazy">
            @else
              <div class="w-full h-full inline-flex items-center justify-center text-[var(--text-soft)]"><i class="fa-solid fa-image text-[22px]"></i></div>
            @endif
          </div>
          <div class="p-3">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate text-[11px] font-bold text-[var(--text-h)]">{{ trim(($generatedImage->user?->name ?? '').' '.($generatedImage->user?->last_name ?? '')) ?: 'کاربر ناشناس' }}</div>
                <div class="mt-1 text-[9.5px] text-[var(--text-soft)]">{{ $generatedImage->created_at?->diffForHumans() ?? '—' }}</div>
              </div>
              @if($generatedImage->user)
                <a href="{{ route('admin.users.logs', $generatedImage->user->id) }}" class="w-7 h-7 rounded-lg border inline-flex shrink-0 items-center justify-center bg-[var(--page-bg)] border-[var(--border)] text-[var(--info)] hover:border-[var(--info)]" title="تاریخچه کاربر"><i class="fa-solid fa-arrow-up-left-from-circle text-[10px]"></i></a>
              @endif
            </div>
            <div class="mt-3 pt-3 border-t border-[var(--border)] text-[10px] text-[var(--text-soft)]">
              <div class="flex justify-between gap-2"><span>محصول</span><span class="truncate text-[var(--text-main)]">{{ $generatedImage->product?->title ?? $generatedImage->product?->name ?? '—' }}</span></div>
              <div class="flex justify-between gap-2 mt-1.5"><span>هزینه</span><span class="text-[var(--text-main)]">{{ number_format((float) $generatedImage->cost, 2) }}</span></div>
            </div>
          </div>
        </article>
      @empty
        <div class="col-span-full p-12 text-center rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] text-[12px] text-[var(--text-soft)]">هنوز تصویری در سامانه ثبت نشده است.</div>
      @endforelse
    </section>

    <div class="pt-5">{{ $generatedImages->links() }}</div>
  </div>
</main>
@endsection
