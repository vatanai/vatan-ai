@extends('layouts.admin')

@section('title', 'گالری کاربران — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @if(session('success'))
      <div class="mb-4 p-3 rounded-xl border bg-[var(--success-l)] border-[var(--success-m)] text-[var(--success)] text-[11px]">{{ session('success') }}</div>
    @endif

    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[17px] font-extrabold text-[var(--text-h)]">گالری کاربران</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">ورودی و خروجی هر ساخت کنار هم نمایش داده می‌شود؛ فایل‌های اصلی فقط در فضای خصوصی نگهداری می‌شوند.</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('admin.users.face-profiles.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--primary-l)] border-[var(--primary-m)] text-[var(--primary)] text-[11px] hover:border-[var(--primary)]">
          <i class="fa-solid fa-user"></i> کارکتر شیت‌ها
        </a>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-main)] hover:border-[var(--primary)] hover:text-[var(--primary)]">
          <i class="fa-solid fa-users"></i> فهرست کاربران
        </a>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-3 mb-5 max-[1200px]:grid-cols-4 max-[700px]:grid-cols-2 max-[480px]:grid-cols-1">
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کل ورودی‌ها</span><strong class="block mt-1 text-[23px] text-[var(--text-h)]">{{ number_format($stats['items']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کاربران دارای گالری</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['users']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">ورودی‌های فعال</span><strong class="block mt-1 text-[23px] text-[var(--success)]">{{ number_format($stats['active']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">حجم فایل اصلی</span><strong class="block mt-1 text-[23px] text-[var(--info)]">{{ number_format($stats['storage'] / 1048576, 1) }} مگابایت</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">پیشنهادها</span><strong class="block mt-1 text-[23px] text-[var(--warning)]">{{ number_format($stats['suggestions']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">بازآفرینی موفق</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['recreations']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کمپین‌های آماده</span><strong class="block mt-1 text-[23px] text-[var(--warning)]">{{ number_format($stats['campaigns']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">هزینه ثبت‌شده</span><strong class="block mt-1 text-[23px] text-[var(--info)]">{{ number_format($stats['costs']) }} تومان</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">عکس‌های ورودی</span><strong class="block mt-1 text-[23px] text-[var(--info)]">{{ number_format($stats['input_images']) }}</strong><small class="block mt-1 text-[9px] text-[var(--text-soft)]">{{ number_format($stats['input_image_storage'] / 1048576, 1) }} مگابایت</small></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">عکس‌های ساخته‌شده</span><strong class="block mt-1 text-[23px] text-[var(--success)]">{{ number_format($stats['generated_images']) }}</strong><small class="block mt-1 text-[9px] text-[var(--text-soft)]">{{ number_format($stats['generated_image_storage'] / 1048576, 1) }} مگابایت</small></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">خروجی‌های ویدیویی</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['generated_videos']) }}</strong><small class="block mt-1 text-[9px] text-[var(--text-soft)]">{{ number_format($stats['generated_video_storage'] / 1048576, 1) }} مگابایت</small></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کارکتر شیت فعال</span><strong class="block mt-1 text-[23px] text-[var(--warning)]">{{ number_format($stats['face_profiles']) }}</strong><small class="block mt-1 text-[9px] text-[var(--text-soft)]">{{ number_format($stats['face_storage'] / 1048576, 1) }} مگابایت</small></div>
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
        <label class="block text-[10px] text-[var(--text-soft)]">مدت نگهداری عکس‌های ورودی<select name="retention_days" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"><option value="7" @selected((int) $config->retention_days === 7)>یک هفته</option><option value="30" @selected((int) $config->retention_days === 30)>یک ماه</option><option value="90" @selected((int) $config->retention_days === 90)>۳ ماه</option><option value="180" @selected((int) $config->retention_days === 180)>۶ ماه</option><option value="270" @selected((int) $config->retention_days === 270)>۹ ماه</option><option value="365" @selected((int) $config->retention_days === 365)>۱۲ ماه</option><option value="0" @selected((int) $config->retention_days === 0)>همیشه</option></select></label>
        <label class="block text-[10px] text-[var(--text-soft)]">سقف تصاویر هر کاربر<input type="number" name="max_items_per_user" min="1" max="1000" value="{{ $config->max_items_per_user }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">سقف حجم هر کاربر (مگابایت)<input type="number" name="max_storage_mb" min="1" max="20480" value="{{ $config->max_storage_mb }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">بازآفرینی رایگان ماهانه<input type="number" name="free_recreations_per_month" min="0" max="100" value="{{ $config->free_recreations_per_month ?? 1 }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <button type="submit" class="max-[1100px]:col-span-3 max-[480px]:col-span-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--primary)] text-white text-[11px] font-bold hover:opacity-90"><i class="fa-solid fa-check"></i> ذخیره تنظیمات</button>
      </form>
    </section>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden mb-5">
      <div class="p-4 border-b border-[var(--border)] flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h2 class="text-[12px] font-bold text-[var(--text-h)]">گالری کاربران</h2>
          <p class="mt-1 text-[10px] text-[var(--text-soft)]">ورودی و خروجی هر ساخت کنار هم نمایش داده می‌شود؛ برای دیدن همهٔ جزئیات روی کارت کاربر بزنید.</p>
        </div>
        <span class="text-[10px] text-[var(--text-soft)]">{{ number_format($galleryUsers->total()) }} کاربر</span>
      </div>
      <form method="GET" class="grid grid-cols-6 gap-2 p-4 border-b border-[var(--border)] bg-[var(--page-bg)] items-end max-[1100px]:grid-cols-3 max-[520px]:grid-cols-1">
        <label class="block text-[10px] text-[var(--text-soft)]">جستجوی کاربر<input name="q" value="{{ $search }}" placeholder="نام، تلفن یا ایمیل" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">از تاریخ ساخت<input type="date" name="date_from" value="{{ $from?->format('Y-m-d') }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">تا تاریخ ساخت<input type="date" name="date_to" value="{{ $to?->format('Y-m-d') }}" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"></label>
        <label class="block text-[10px] text-[var(--text-soft)]">نوع خروجی<select name="media_type" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"><option value="all" @selected($mediaType === 'all')>همه</option><option value="image" @selected($mediaType === 'image')>تصویر</option><option value="video" @selected($mediaType === 'video')>ویدیو</option></select></label>
        <label class="block text-[10px] text-[var(--text-soft)]">ترتیب نمایش<select name="sort" class="mt-1 w-full px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]"><option value="newest" @selected($sort === 'newest')>جدیدترین</option><option value="oldest" @selected($sort === 'oldest')>قدیمی‌ترین</option></select></label>
        <div class="flex items-center gap-2"><button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--primary)] text-white text-[11px] font-bold hover:opacity-90"><i class="fa-solid fa-magnifying-glass"></i> اعمال فیلتر</button><a href="{{ route('admin.users.gallery.index') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-[var(--border)] text-[var(--text-soft)] text-[11px] hover:text-[var(--primary)]" title="حذف فیلترها"><i class="fa-solid fa-rotate-left"></i></a></div>
      </form>
      <form method="POST" action="{{ route('admin.users.gallery.bulk-destroy') }}" id="gallery-card-bulk-form">
        @csrf
        @method('DELETE')
        <div class="px-4 pt-4 flex items-center justify-between gap-3 flex-wrap">
          <label class="inline-flex items-center gap-2 text-[10px] text-[var(--text-main)] cursor-pointer"><input type="checkbox" id="gallery-card-select-all" class="accent-[var(--primary)]"> انتخاب کل کارت‌ها</label>
          <button type="submit" id="gallery-card-bulk-delete" disabled class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-[var(--danger-m)] bg-[var(--danger-l)] text-[var(--danger)] text-[10px] disabled:opacity-40 disabled:cursor-not-allowed"><i class="fa-solid fa-trash-can"></i> حذف ورودی کارت‌های انتخاب‌شده</button>
        </div>
      <div class="grid grid-cols-4 gap-3 p-4 max-[1280px]:grid-cols-3 max-[1000px]:grid-cols-2 max-[650px]:grid-cols-1">
        @forelse($galleryCards as $card)
          <article class="relative rounded-2xl border bg-[var(--page-bg)] border-[var(--border)] overflow-hidden">
            <label class="absolute top-2 left-2 z-10 w-7 h-7 rounded-lg inline-flex items-center justify-center bg-[var(--card-bg)]/90 border border-[var(--border)] cursor-pointer"><input type="checkbox" name="user_ids[]" value="{{ $card['user_id'] }}" class="gallery-card-checkbox accent-[var(--primary)]" aria-label="انتخاب کارت {{ $card['user_name'] }}"></label>
            <a href="{{ route('admin.users.gallery.show', $card['user_id']) }}" class="flex items-center justify-between gap-3 p-3 pl-12 border-b border-[var(--border)] hover:bg-[var(--primary-l)] no-underline">
              <div class="min-w-0 flex-1">
                <strong class="block truncate text-[11px] text-[var(--text-h)]">{{ $card['user_name'] }}</strong>
                @if($card['user_phone'])<span class="block mt-1 text-[9px] text-[var(--text-soft)]" dir="ltr">{{ $card['user_phone'] }}</span>@endif
              </div>
              <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[var(--primary-l)] text-[var(--primary)] text-[9px] font-bold"><i class="fa-solid fa-images"></i>{{ number_format($card['output_count']) }} خروجی</span>
            </a>
            @php($pair = $card['pairs'][0] ?? null)
            <div class="p-3">
              @if($pair)
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="truncate text-[9.5px] font-bold text-[var(--text-main)]">{{ $pair['product_name'] }}</span>
                  <span class="shrink-0 text-[9px] text-[var(--text-soft)]">{{ \App\Support\Jalali::formatNumeric($pair['date']) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-0 aspect-square rounded-xl overflow-hidden border border-[var(--border)] bg-[var(--input-bg)]">
                  <div class="relative min-w-0 min-h-0 border-l border-[var(--border)]">
                    @if(!empty($pair['before'][0]))
                      @php($media = $pair['before'][0])
                      @if($media['type'] === 'video')<video src="{{ $media['url'] }}" class="w-full h-full object-cover" preload="metadata" muted playsinline></video>@else<img src="{{ $media['url'] }}" alt="{{ $media['label'] }}" class="w-full h-full object-cover" loading="lazy">@endif
                      @if(count($pair['before']) > 1)<span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-[var(--card-bg)]/90 text-[8px] text-[var(--text-main)]">+{{ count($pair['before']) - 1 }}</span>@endif
                    @else
                      <span class="w-full h-full flex items-center justify-center text-center text-[9px] text-[var(--text-soft)]">ورودی ثبت نشده</span>
                    @endif
                    <span class="absolute top-1 right-1 px-1.5 py-0.5 rounded-md bg-[var(--card-bg)]/90 text-[8px] text-[var(--text-soft)]">قبل</span>
                  </div>
                  <div class="relative min-w-0 min-h-0">
                    @if(!empty($pair['after'][0]))
                      @php($media = $pair['after'][0])
                      <a href="{{ $media['url'] }}" target="_blank" rel="noopener" class="block w-full h-full">@if($media['type'] === 'video')<video src="{{ $media['url'] }}" class="w-full h-full object-cover" preload="metadata" muted playsinline></video>@else<img src="{{ $media['url'] }}" alt="{{ $media['label'] }}" class="w-full h-full object-cover" loading="lazy">@endif</a>
                      @if(count($pair['after']) > 1)<span class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded-md bg-[var(--card-bg)]/90 text-[8px] text-[var(--text-main)]">+{{ count($pair['after']) - 1 }}</span>@endif
                    @else
                      <span class="w-full h-full flex items-center justify-center text-center text-[9px] text-[var(--text-soft)]">خروجی ثبت نشده</span>
                    @endif
                    <span class="absolute top-1 left-1 px-1.5 py-0.5 rounded-md bg-[var(--primary)]/90 text-[8px] text-white">بعد</span>
                  </div>
                </div>
                <a href="{{ route('admin.users.gallery.show', $card['user_id']) }}" class="inline-flex items-center gap-1 mt-2 text-[9px] text-[var(--info)] hover:text-[var(--primary)]">همه جزئیات گالری <i class="fa-solid fa-arrow-left"></i></a>
              @else
                <div class="aspect-square flex items-center justify-center text-center text-[10px] text-[var(--text-soft)]">برای این کاربر هنوز خروجی قابل نمایش ثبت نشده است.</div>
              @endif
            </div>
          </article>
        @empty
          <div class="col-span-full p-12 text-center text-[11px] text-[var(--text-soft)]"><i class="fa-regular fa-images text-[24px] mb-2"></i><p>کاربری با ورودی یا خروجی گالری پیدا نشد.</p></div>
        @endforelse
      </div>
      <div class="p-4 border-t border-[var(--border)]">{{ $galleryUsers->appends(request()->except('gallery_page'))->links() }}</div>
      </form>
    </section>

  </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const cardForm = document.getElementById('gallery-card-bulk-form');
  if (cardForm) {
    const master = document.getElementById('gallery-card-select-all');
    const submit = document.getElementById('gallery-card-bulk-delete');
    const cards = Array.from(cardForm.querySelectorAll('.gallery-card-checkbox'));
    const sync = () => { submit.disabled = !master.checked && !cards.some((item) => item.checked); };
    master.addEventListener('change', () => { cards.forEach((item) => { item.checked = master.checked; }); sync(); });
    cards.forEach((item) => item.addEventListener('change', sync));
    cardForm.addEventListener('submit', (event) => {
      if (!confirm(master.checked ? 'ورودی‌های تمام کارت‌های این صفحه حذف شوند؟' : 'ورودی‌های کارت‌های انتخاب‌شده حذف شوند؟')) event.preventDefault();
    });
  }
});
</script>
</main>
@endsection
