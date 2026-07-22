@extends('layouts.admin')
@section('title', 'مدیریت دسته‌بندی‌ها — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">مدیریت دسته‌بندی‌ها</h1>
        <p class="text-xs text-[var(--text-soft)]">مدیریت دسته‌بندی‌ها و لینک صفحه عمومی هر دسته</p>
      </div>
      <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 px-4 h-9 rounded-lg text-xs font-bold bg-[var(--primary)] text-[var(--accent)] no-underline">
        <i class="fa-solid fa-plus"></i> افزودن دسته‌بندی
      </a>
    </div>

    @if(session('success'))
      <div class="mb-4 rounded-xl border border-[var(--success)] p-3 text-xs text-[var(--text-main)] bg-[var(--card-bg)]">
        <i class="fa-solid fa-circle-check text-[var(--success)] ml-1"></i>{{ session('success') }}
      </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
      <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-4 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-3"><span class="text-xs text-[var(--text-soft)]">همه دسته‌بندی‌ها</span><span class="w-9 h-9 rounded-lg bg-[var(--input-bg)] text-[var(--primary)] flex items-center justify-center"><i class="fa-solid fa-layer-group"></i></span></div>
        <strong class="text-2xl text-[var(--text-h)]">{{ number_format($totalCategories) }}</strong>
        <p class="text-[10px] text-[var(--text-soft)] mt-1">مجموع دسته‌های اصلی و زیر‌دسته‌ها</p>
      </div>
      <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-4 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-3"><span class="text-xs text-[var(--text-soft)]">دسته‌های دارای محصول</span><span class="w-9 h-9 rounded-lg bg-[var(--input-bg)] text-[var(--success)] flex items-center justify-center"><i class="fa-solid fa-circle-check"></i></span></div>
        <strong class="text-2xl text-[var(--text-h)]">{{ number_format($activeCategories) }}</strong>
        <p class="text-[10px] text-[var(--text-soft)] mt-1">{{ $totalCategories ? round(($activeCategories / $totalCategories) * 100) : 0 }}٪ از کل دسته‌بندی‌ها</p>
      </div>
      <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-4 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-3"><span class="text-xs text-[var(--text-soft)]">دسته‌های بدون محصول</span><span class="w-9 h-9 rounded-lg bg-[var(--input-bg)] text-[var(--warning)] flex items-center justify-center"><i class="fa-regular fa-folder-open"></i></span></div>
        <strong class="text-2xl text-[var(--text-h)]">{{ number_format($emptyCategories) }}</strong>
        <p class="text-[10px] text-[var(--text-soft)] mt-1">نیازمند اتصال محصول یا بازبینی</p>
      </div>
      <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-4 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-3"><span class="text-xs text-[var(--text-soft)]">پرمصرف‌ترین دسته</span><span class="w-9 h-9 rounded-lg bg-[var(--input-bg)] text-[var(--accent)] flex items-center justify-center"><i class="fa-solid fa-chart-line"></i></span></div>
        <strong class="block text-sm text-[var(--text-h)] truncate" title="{{ $topCategory?->name_fa ?: $topCategory?->name }}">{{ $topCategory?->name_fa ?: $topCategory?->name ?: 'بدون داده' }}</strong>
        <p class="text-[10px] text-[var(--text-soft)] mt-2">{{ number_format($topCategory ? ($usageCounts[$topCategory->id] ?? 0) : 0) }} استفاده · {{ number_format($totalUsage) }} استفاده کل</p>
      </div>
    </div>

    <form method="GET" action="{{ route('admin.categories.index') }}" class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-3 mb-5 shadow-[var(--shadow-card)]">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[minmax(260px,1fr)_repeat(4,minmax(130px,auto))_auto] gap-2">
        <label class="relative"><i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-soft)]"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="جستجو در نام، اسلاگ یا مسیر..." class="w-full h-10 pr-9 pl-3 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-xs text-[var(--text-main)] outline-none focus:border-[var(--primary)]"></label>
        <select name="content" class="h-10 px-3 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-xs text-[var(--text-main)]"><option value="">وضعیت محتوا</option><option value="active" @selected(request('content')==='active')>دارای محصول</option><option value="empty" @selected(request('content')==='empty')>بدون محصول</option></select>
        <select name="visibility" class="h-10 px-3 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-xs text-[var(--text-main)]"><option value="">وضعیت نمایش</option><option value="enabled" @selected(request('visibility')==='enabled')>فعال سیستمی</option><option value="disabled" @selected(request('visibility')==='disabled')>غیرفعال سیستمی</option></select>
        <select name="type" class="h-10 px-3 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-xs text-[var(--text-main)]"><option value="">نوع دسته</option><option value="root" @selected(request('type')==='root')>دسته اصلی</option><option value="child" @selected(request('type')==='child')>زیر‌دسته</option><option value="featured" @selected(request('type')==='featured')>ویژه</option></select>
        <select name="sort" class="h-10 px-3 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-xs text-[var(--text-main)]"><option value="usage" @selected(request('sort','usage')==='usage')>بیشترین مصرف</option><option value="products" @selected(request('sort')==='products')>بیشترین محصول</option><option value="name" @selected(request('sort')==='name')>نام دسته</option><option value="latest" @selected(request('sort')==='latest')>جدیدترین</option><option value="oldest" @selected(request('sort')==='oldest')>قدیمی‌ترین</option></select>
        <div class="flex gap-2"><button class="h-10 px-4 rounded-lg bg-[var(--primary)] text-[var(--accent)] text-xs font-bold cursor-pointer"><i class="fa-solid fa-filter ml-1"></i> اعمال</button>@if(request()->hasAny(['search','content','visibility','type','sort']))<a href="{{ route('admin.categories.index') }}" class="h-10 w-10 rounded-lg border border-[var(--border)] bg-[var(--input-bg)] text-[var(--text-soft)] inline-flex items-center justify-center" title="پاک‌کردن فیلترها"><i class="fa-solid fa-xmark"></i></a>@endif</div>
      </div>
      <div class="mt-2 text-[10px] text-[var(--text-soft)]">{{ number_format($categories->total()) }} دسته‌بندی مطابق فیلترهای انتخاب‌شده</div>
    </form>

    <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl overflow-hidden shadow-[var(--shadow-card)]">
      <div class="p-4 border-b border-[var(--divider)] text-xs font-bold text-[var(--text-main)] flex items-center justify-between">
        <span><i class="fa-solid fa-list text-[var(--primary)] ml-1"></i> دسته‌بندی‌ها بر اساس مصرف</span><span class="text-[10px] font-normal text-[var(--text-soft)]">مصرف = تعداد اجرای واقعی محصولات هر دسته</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-right border-collapse text-xs">
          <thead><tr class="bg-[var(--input-bg)] text-[var(--text-soft)]">
            <th class="p-4 w-20">تصویر</th><th class="p-4">نام دسته‌بندی</th><th class="p-4">اسلاگ</th>
            <th class="p-4 text-center">محصولات</th><th class="p-4 text-center">تعداد استفاده</th><th class="p-4 text-center">وضعیت</th><th class="p-4 text-center">لینک</th><th class="p-4 text-left">عملیات</th>
          </tr></thead>
          <tbody class="divide-y divide-[var(--divider)]">
          @forelse($categories as $category)
            <tr class="hover:bg-[var(--input-bg)] transition-colors">
              <td class="p-4">
                @if($category->image)<img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" class="w-10 h-10 object-cover rounded-lg border border-[var(--border)]">
                @else<div class="w-10 h-10 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] flex items-center justify-center text-[var(--text-soft)]"><i class="fa-regular fa-image"></i></div>@endif
              </td>
              <td class="p-4 font-bold text-[var(--text-main)]">{{ $category->name_fa ?: $category->name }}</td>
              <td class="p-4 font-mono text-[var(--text-soft)]" dir="ltr">{{ $category->slug }}</td>
              <td class="p-4 text-center"><span class="px-2 py-1 rounded-md bg-[var(--input-bg)] text-[var(--text-main)]">{{ $category->products_count }}</span></td>
              <td class="p-4 text-center"><span class="font-bold {{ $category->usage_count > 0 ? 'text-[var(--primary)]' : 'text-[var(--text-soft)]' }}">{{ number_format($category->usage_count) }}</span></td>
              <td class="p-4 text-center">@if($category->products_count > 0)<span class="inline-flex items-center gap-1 text-[10px] text-[var(--success)]"><i class="fa-solid fa-circle text-[6px]"></i> دارای محصول</span>@else<span class="inline-flex items-center gap-1 text-[10px] text-[var(--text-soft)]"><i class="fa-regular fa-circle text-[6px]"></i> خالی</span>@endif</td>
              <td class="p-4 text-center">
                <button type="button" class="copy-category-link inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-[var(--text-soft)] hover:text-[var(--primary)] cursor-pointer" data-url="{{ $category->url() }}" title="کپی لینک دسته‌بندی" aria-label="کپی لینک {{ $category->name }}">
                  <i class="fa-regular fa-copy"></i>
                </button>
              </td>
              <td class="p-4 text-left"><div class="inline-flex gap-2">
                <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[var(--input-bg)] border border-[var(--border)] text-[var(--text-soft)] hover:text-[var(--primary)]" title="ویرایش"><i class="fa-regular fa-pen-to-square"></i></a>
                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('این دسته‌بندی حذف شود؟');">@csrf @method('DELETE')
                  <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--danger)] text-[var(--danger)] bg-transparent cursor-pointer" title="حذف"><i class="fa-regular fa-trash-can"></i></button>
                </form>
              </div></td>
            </tr>
          @empty
            <tr><td colspan="8" class="p-10 text-center text-[var(--text-soft)]"><i class="fa-regular fa-folder-open text-2xl block mb-2"></i>دسته‌بندی مطابق این فیلترها پیدا نشد.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($categories->hasPages())<div class="mt-5">{{ $categories->links() }}</div>@endif
    <div id="copy-toast" class="fixed left-6 bottom-6 hidden rounded-lg bg-[var(--card-bg)] border border-[var(--success)] px-4 py-3 text-xs text-[var(--text-main)] shadow-[var(--shadow-card)]">لینک دسته‌بندی کپی شد.</div>
  </div>
</main>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.copy-category-link').forEach(function (button) {
  button.addEventListener('click', async function () {
    try {
      await navigator.clipboard.writeText(button.dataset.url);
    } catch (error) {
      const input = document.createElement('textarea');
      input.value = button.dataset.url; document.body.appendChild(input); input.select();
      document.execCommand('copy'); input.remove();
    }
    const toast = document.getElementById('copy-toast');
    toast.classList.remove('hidden'); setTimeout(() => toast.classList.add('hidden'), 2200);
  });
});
</script>
@endpush
