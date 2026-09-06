@extends('layouts.admin')
@section('title', 'فعالیت‌های کاربران — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[16px] font-extrabold text-[var(--text-h)]">فعالیت‌های کاربران</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">رویدادهای ثبت‌شده سامانه به ترتیب جدیدترین مورد</p>
      </div>
      <div class="flex items-center gap-1 p-1 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <a href="{{ route('admin.users.all_logs') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-images ml-1 text-[10px]"></i> لاگ تصاویر</a>
        <a href="{{ route('admin.users.index') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-users ml-1 text-[10px]"></i> کاربران</a>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-5 max-[640px]:grid-cols-1">
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">کل رویدادها</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--text-h)]">{{ number_format($activities->total()) }}</div>
      </div>
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">رویدادهای این صفحه</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--success)]">{{ number_format($activities->count()) }}</div>
      </div>
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">صفحه فعلی</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--info)]">{{ number_format($activities->currentPage()) }}</div>
      </div>
    </div>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
      <div class="divide-y divide-[var(--border)]">
        @forelse($activities as $activity)
          @php
            $levelStyles = [
              'success' => ['bg-[var(--success-l)]', 'border-[var(--success-m)]', 'text-[var(--success)]'],
              'warning' => ['bg-[var(--warning-l)]', 'border-[var(--warning-m)]', 'text-[var(--warning)]'],
              'error' => ['bg-[var(--danger-l)]', 'border-[var(--danger-m)]', 'text-[var(--danger)]'],
              'info' => ['bg-[var(--info-l)]', 'border-[var(--info-m)]', 'text-[var(--info)]'],
            ];
            [$backgroundClass, $borderClass, $textClass] = $levelStyles[$activity->level] ?? $levelStyles['info'];
          @endphp
          <article class="p-4 flex items-start gap-3 max-[560px]:p-3">
            <span class="w-9 h-9 shrink-0 rounded-xl border inline-flex items-center justify-center {{ $backgroundClass }} {{ $borderClass }} {{ $textClass }}">
              <i class="fa-solid fa-clock-rotate-left text-[12px]"></i>
            </span>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                  <div class="text-[12px] font-bold text-[var(--text-h)]">{{ $activity->message }}</div>
                  <div class="mt-1 text-[10.5px] text-[var(--text-soft)]">
                    @if($activity->user)
                      {{ trim(($activity->user->name ?? '').' '.($activity->user->last_name ?? '')) ?: 'کاربر' }}
                    @else
                      کاربر مهمان
                    @endif
                    <span class="mx-1">•</span>{{ $activity->created_at?->diffForHumans() ?? '—' }}
                  </div>
                </div>
                <span class="shrink-0 px-2 py-1 rounded-lg border text-[9.5px] font-semibold {{ $backgroundClass }} {{ $borderClass }} {{ $textClass }}">{{ $activity->type }}</span>
              </div>
              @if(!empty($activity->meta))
                <details class="mt-2.5">
                  <summary class="cursor-pointer text-[10px] text-[var(--text-soft)] hover:text-[var(--text-main)]">نمایش جزئیات رویداد</summary>
                  <pre class="mt-2 max-h-40 overflow-auto p-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[10px] leading-5 text-[var(--text-main)]">{{ json_encode($activity->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                </details>
              @endif
              @if($activity->user)
                <a href="{{ route('admin.users.logs', $activity->user->id) }}" class="inline-flex items-center gap-1 mt-2.5 text-[10px] font-semibold text-[var(--info)] hover:text-[var(--primary)]"><i class="fa-solid fa-arrow-up-left-from-circle"></i> تاریخچه این کاربر</a>
              @endif
            </div>
          </article>
        @empty
          <div class="p-12 text-center text-[12px] text-[var(--text-soft)]">هنوز رویدادی در سامانه ثبت نشده است.</div>
        @endforelse
      </div>
    </section>

    <div class="pt-5">{{ $activities->links() }}</div>
  </div>
</main>
@endsection
