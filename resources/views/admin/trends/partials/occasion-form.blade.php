<div class="content-card p-5 mb-5">
  <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-plus" style="color:var(--primary);"></i> افزودن مناسبت جدید</div>
  <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">عنوان برای تب نمایش داده می‌شود؛ جستجو و دسته‌بندی مشخص می‌کنند چه محصولاتی داخل آن قرار بگیرند.</div>

  <form method="POST" action="{{ route('admin.trends.occasions.store') }}" class="grid grid-cols-4 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1 gap-3 items-end">
    @csrf
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">عنوان مناسبت</label>
      <input type="text" name="title_fa" required class="input-pro w-full" placeholder="مثلاً شب یلدا">
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">عبارت جستجو</label>
      <input type="text" name="query" class="input-pro w-full" placeholder="مثلاً یلدا">
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">دسته‌بندی مرتبط</label>
      <select name="category_id" class="input-pro w-full">
        <option value="">بدون اتصال مستقیم</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name_fa ?: $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">اولویت نمایش</label>
      <input type="number" name="sort_order" min="0" max="255" value="0" class="input-pro w-full">
    </div>
    <label class="flex items-center gap-2 text-[11px] font-bold" style="color:var(--text-soft);"><input type="checkbox" name="is_active" value="1" checked> فعال باشد</label>
    <div class="max-[560px]:col-span-1 col-span-3 flex justify-end">
      <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> افزودن مناسبت</button>
    </div>
  </form>
</div>
