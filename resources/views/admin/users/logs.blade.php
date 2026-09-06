@extends('layouts.admin')
@section('title', 'تاریخچه کاربر — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[16px] font-extrabold text-[var(--text-h)]">تاریخچه {{ trim(($user->name ?? '').' '.($user->last_name ?? '')) ?: 'کاربر' }}</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">رویدادها و تصاویر خلق‌شده این کاربر</p>
      </div>
      <div class="flex items-center gap-1 p-1 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <a href="{{ route('admin.users.index') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-users ml-1 text-[10px]"></i> کاربران</a>
        <a href="{{ route('admin.users.all_activities') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-timeline ml-1 text-[10px]"></i> همه فعالیت‌ها</a>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-5 max-[560px]:grid-cols-1">
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">کل رویدادهای کاربر</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--success)]">{{ number_format($activities->total()) }}</div>
      </div>
      <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
        <div class="text-[10.5px] text-[var(--text-soft)]">کل تصاویر خلق‌شده</div>
        <div class="mt-1 text-[21px] leading-none font-extrabold text-[var(--info)]">{{ number_format($generatedImages->total()) }}</div>
      </div>
    </div>

    <div class="grid grid-cols-[minmax(0,1.2fr)_minmax(280px,.8fr)] gap-4 max-[1000px]:grid-cols-1">
      <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--border)] text-[12px] font-bold text-[var(--text-h)]">تایم‌لاین فعالیت‌ها</div>
        <div class="divide-y divide-[var(--border)]">
          @forelse($activities as $activity)
            <article class="p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-[11.5px] font-bold text-[var(--text-h)]">{{ $activity->message }}</div>
                  <div class="mt-1 text-[10px] text-[var(--text-soft)]">{{ $activity->type }} <span class="mx-1">•</span>{{ $activity->created_at?->diffForHumans() ?? '—' }}</div>
                </div>
                <span class="shrink-0 px-2 py-1 rounded-lg border bg-[var(--page-bg)] border-[var(--border)] text-[9.5px] text-[var(--text-soft)]">{{ $activity->level }}</span>
              </div>
              @if(!empty($activity->meta))
                <details class="mt-2.5">
                  <summary class="cursor-pointer text-[10px] text-[var(--text-soft)] hover:text-[var(--text-main)]">جزئیات رویداد</summary>
                  <pre class="mt-2 max-h-32 overflow-auto p-2.5 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[9.5px] leading-5 text-[var(--text-main)]">{{ json_encode($activity->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                </details>
              @endif
            </article>
          @empty
            <div class="p-10 text-center text-[11px] text-[var(--text-soft)]">فعالیتی برای این کاربر ثبت نشده است.</div>
          @endforelse
        </div>
        <div class="p-4 border-t border-[var(--border)]">{{ $activities->appends(request()->except('activities_page'))->links() }}</div>
      </section>

      <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--border)] text-[12px] font-bold text-[var(--text-h)]">تصاویر خلق‌شده</div>
        <div class="grid grid-cols-2 gap-px bg-[var(--border)]">
          @forelse($generatedImages as $generatedImage)
            <div class="bg-[var(--card-bg)]">
              <div class="aspect-square bg-[var(--page-bg)]">
                @if($generatedImage->image_path)
                  <img src="{{ asset('storage/'.$generatedImage->image_path) }}" alt="تصویر خلق‌شده" class="w-full h-full object-cover" loading="lazy">
                @else
                  <div class="w-full h-full inline-flex items-center justify-center text-[var(--text-soft)]"><i class="fa-solid fa-image"></i></div>
                @endif
              </div>
              <div class="p-2.5 text-[9.5px] text-[var(--text-soft)]">{{ $generatedImage->created_at?->diffForHumans() ?? '—' }}</div>
            </div>
          @empty
            <div class="col-span-2 p-10 text-center text-[11px] text-[var(--text-soft)]">تصویری برای این کاربر ثبت نشده است.</div>
          @endforelse
        </div>
        <div class="p-4 border-t border-[var(--border)]">{{ $generatedImages->appends(request()->except('images_page'))->links() }}</div>
      </section>
    </div>
  </div>
</main>
@endsection
