@extends('layouts.admin')

@section('title', 'ورودی‌های کاربران — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @if(session('success'))
      <div class="mb-4 p-3 rounded-xl border bg-[var(--success-l)] border-[var(--success-m)] text-[var(--success)] text-[11px]">{{ session('success') }}</div>
    @endif

    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[17px] font-extrabold text-[var(--text-h)]">ورودی‌های کاربران</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">عکس، متن و ویدیوی ورودی فقط با رضایت کاربر ذخیره می‌شوند و فایل اصلی در فضای خصوصی نگهداری می‌شود.</p>
      </div>
      <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] hover:border-[var(--primary)] hover:text-[var(--primary)]">
        <i class="fa-solid fa-users"></i> فهرست کاربران
      </a>
    </div>

    <div class="grid grid-cols-8 gap-3 mb-5 max-[1200px]:grid-cols-4 max-[700px]:grid-cols-2 max-[480px]:grid-cols-1">
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کل ورودی‌ها</span><strong class="block mt-1 text-[23px] text-[var(--text-h)]">{{ number_format($stats['items']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کاربران دارای گالری</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['users']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">ورودی‌های فعال</span><strong class="block mt-1 text-[23px] text-[var(--success)]">{{ number_format($stats['active']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">حجم فایل اصلی</span><strong class="block mt-1 text-[23px] text-[var(--info)]">{{ number_format($stats['storage'] / 1048576, 1) }} مگابایت</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">پیشنهادها</span><strong class="block mt-1 text-[23px] text-[var(--warning)]">{{ number_format($stats['suggestions']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">بازآفرینی موفق</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['recreations']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کمپین‌های آماده</span><strong class="block mt-1 text-[23px] text-[var(--warning)]">{{ number_format($stats['campaigns']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">هزینه ثبت‌شده</span><strong class="block mt-1 text-[23px] text-[var(--info)]">{{ number_format($stats['costs']) }} تومان</strong></div>
    </div>

    <section class="mb-5 p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]">
      <div class="flex items-center gap-2 mb-4">
        <span class="w-9 h-9 rounded-xl inline-flex items-center justify-center bg-[var(--primary-l)] text-[var(--primary)]"><i class="fa-solid fa-sliders"></i></span>
        <div><h2 class="text-[12px] font-bold text-[var(--text-h)]">تنظیمات نگهداری</h2><p class="mt-1 text-[10px] text-[var(--text-soft)]">این محدودیت‌ها برای تصاویر جدید اعمال می‌شوند.</p></div>
      </div>
      <form method="POST" action="{{ route('admin.users.gallery.settings') }}" class="grid grid-cols-6 gap-3 items-end max-[1100px]:grid-cols-3 max-[480px]:grid-cols-1">
        @csrf
        <input type="hidden" name="enabled" value="0">
        <input type="hidden" name="suggestions_enabled" value="0">
        <label class="flex items-center gap-2 min-h-[38px] px-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] cursor-pointer">
          <input type="checkbox" name="enabled" value="1" @checked($config->enabled) class="accent-[var(--primary)]">
          فعال‌سازی قابلیت
        </label>
        <label class="flex items-center gap-2 min-h-[38px] px-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] cursor-pointer">
          <input type="checkbox" name="suggestions_enabled" value="1" @checked($config->suggestions_enabled ?? true) class="accent-[var(--primary)]">
          فعال‌سازی پیشنهادها
        </label>
        <label class="block text-[10px] text-[var(--text-soft)]">مدت نگهداری (روز)<input type="number" name="retention_days" min="1" max="3650" value="{{ $config->retention_days }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">سقف تصاویر هر کاربر<input type="number" name="max_items_per_user" min="1" max="1000" value="{{ $config->max_items_per_user }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">سقف حجم هر کاربر (مگابایت)<input type="number" name="max_storage_mb" min="1" max="20480" value="{{ $config->max_storage_mb }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">بازآفرینی رایگان ماهانه<input type="number" name="free_recreations_per_month" min="0" max="100" value="{{ $config->free_recreations_per_month ?? 1 }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <button type="submit" class="max-[1100px]:col-span-3 max-[480px]:col-span-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--primary)] text-white text-[11px] font-bold hover:opacity-90"><i class="fa-solid fa-check"></i> ذخیره تنظیمات</button>
      </form>
    </section>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
      <div class="p-4 border-b border-[var(--border)] flex items-center justify-between gap-3 flex-wrap">
        <h2 class="text-[12px] font-bold text-[var(--text-h)]">ورودی‌های ذخیره‌شده</h2>
        <form method="GET" class="flex items-center gap-2">
          <input name="q" value="{{ $search }}" placeholder="جستجوی کاربر" class="w-44 px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]">
          <select name="status" class="px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]">
            <option value="all" @selected($status === 'all')>همه</option>
            <option value="active" @selected($status === 'active')>فعال</option>
            <option value="expired" @selected($status === 'expired')>منقضی‌شده</option>
          </select>
          <button class="w-9 h-9 rounded-xl border border-[var(--border)] text-[var(--text-soft)] hover:text-[var(--primary)]" title="جستجو" aria-label="جستجو"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>
      <div class="grid grid-cols-4 gap-3 p-4 max-[1100px]:grid-cols-3 max-[700px]:grid-cols-2 max-[430px]:grid-cols-1">
        @forelse($items as $item)
          <article class="overflow-hidden rounded-2xl border bg-[var(--page-bg)] border-[var(--border)]">
            <div class="relative aspect-square bg-[var(--input-bg)] flex items-center justify-center overflow-hidden">
              @if($item->input_kind === 'image')
                <a href="{{ route('admin.users.gallery.preview', [$item->user_id, $item->id]) }}" target="_blank" rel="noopener" class="block w-full h-full"><img src="{{ route('admin.users.gallery.preview', [$item->user_id, $item->id]) }}" alt="عکس ورودی کاربر" class="w-full h-full object-cover" loading="lazy"></a>
              @elseif($item->input_kind === 'video')
                <video src="{{ route('admin.users.gallery.original', [$item->user_id, $item->id]) }}" class="w-full h-full object-contain" controls preload="metadata"></video>
              @elseif($item->input_kind === 'text')
                <div class="w-full h-full p-4 overflow-hidden text-[11px] leading-7 text-[var(--text-main)] whitespace-pre-wrap">{{ $item->text_preview }}</div>
              @else
                <a href="{{ route('admin.users.gallery.original', [$item->user_id, $item->id]) }}" target="_blank" rel="noopener" class="text-center text-[var(--primary)]"><i class="fa-solid fa-file text-[30px]"></i><span class="block mt-2 text-[10px]">بازکردن فایل</span></a>
              @endif
              <span class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-[var(--card-bg)]/90 border border-[var(--border)] text-[9px] text-[var(--text-main)]">{{ $item->input_label }}</span>
            </div>
            <div class="p-3">
              <a href="{{ route('admin.users.gallery.show', $item->user_id) }}" class="block truncate text-[11px] font-bold text-[var(--text-h)] hover:text-[var(--primary)]">{{ trim(($item->user?->name ?? '').' '.($item->user?->last_name ?? '')) ?: 'کاربر حذف‌شده' }}</a>
              <div class="mt-1 text-[9.5px] text-[var(--text-soft)]">انقضا: {{ \App\Support\Jalali::formatNumeric($item->expires_at) }} · {{ number_format($item->size / 1048576, 2) }} مگابایت</div>
              <div class="mt-2 flex items-center justify-between gap-2">
                <span class="px-2 py-1 rounded-lg border border-[var(--border)] text-[9px] {{ $item->expires_at?->isFuture() ? 'text-[var(--success)]' : 'text-[var(--danger)]' }}">{{ $item->expires_at?->isFuture() ? 'فعال' : 'منقضی' }}</span>
                <a href="{{ route('admin.users.gallery.original', [$item->user_id, $item->id]) }}" target="_blank" rel="noopener" class="text-[10px] text-[var(--info)] hover:text-[var(--primary)]" title="فایل اصلی"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              </div>
            </div>
          </article>
        @empty
          <div class="col-span-full p-12 text-center text-[11px] text-[var(--text-soft)]"><i class="fa-regular fa-photo-film text-[24px] mb-2"></i><p>ورودی‌ای در آرشیو ثبت نشده است.</p></div>
        @endforelse
      </div>
      <div class="p-4 border-t border-[var(--border)]">{{ $items->links() }}</div>
    </section>
  </div>
</main>
@endsection
