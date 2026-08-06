<div class="content-card p-5 mb-5">
  <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
    <div class="text-[14px] font-extrabold" style="color:var(--text-h);"><i class="fa-solid fa-magnifying-glass" style="color:var(--primary);"></i> جستجو و افزودن محصول به ترند</div>
    <span class="badge-pro badge-neutral">فقط محصولات فعال قابل افزودن هستند</span>
  </div>
  <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">نام، کد یا اسلاگ محصول را جستجو کنید؛ سپس آن را به فهرست صفحه ترند اضافه کنید.</div>

  <form method="GET" action="{{ route('admin.trends.index') }}" class="flex gap-2 max-[560px]:flex-col">
    <input type="search" name="search" value="{{ $search }}" class="input-pro flex-1" placeholder="جستجوی نام، کد محصول یا اسلاگ..." autocomplete="off">
    <button type="submit" class="btn-pro btn-pro-primary justify-center"><i class="fa-solid fa-magnifying-glass text-[11px]"></i> جستجو</button>
    @if($search !== '')<a href="{{ route('admin.trends.index') }}" class="btn-pro btn-pro-ghost justify-center">پاک کردن</a>@endif
  </form>

  @if($search !== '')
    <div class="mt-5 pt-4 border-t" style="border-color:var(--border);">
      <div class="text-[12px] font-bold mb-3" style="color:var(--text-h);">نتایج قابل افزودن</div>
      <div class="grid grid-cols-3 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1 gap-2">
        @forelse($availableProducts as $product)
          <div class="flex items-center gap-2 rounded-xl p-2.5" style="background:var(--input-bg);border:1px solid var(--border);">
            <img src="{{ $product->displayImageUrl() }}" alt="" class="w-11 h-11 rounded-lg object-cover shrink-0">
            <div class="min-w-0 flex-1">
              <div class="truncate text-[11px] font-bold" style="color:var(--text-h);">{{ $product->name_fa }}</div>
              <div class="text-[9px] mt-0.5" style="color:var(--text-soft);">{{ $product->product_code ?: $product->slug }}</div>
            </div>
            <form method="POST" action="{{ route('admin.trends.products.add', $product) }}">
              @csrf
              <button type="submit" class="icon-action-btn" title="افزودن به ترند" style="color:var(--success);"><i class="fa-solid fa-plus"></i></button>
            </form>
          </div>
        @empty
          <div class="col-span-full empty-state">محصول فعالی با این عبارت پیدا نشد.</div>
        @endforelse
      </div>
    </div>
  @endif
</div>
