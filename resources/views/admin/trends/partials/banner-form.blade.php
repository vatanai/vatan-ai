@php
  $isEditing = filled($banner);
  $bannerAction = $isEditing ? route('admin.trends.banners.update', $banner) : route('admin.trends.banners.store');
@endphp
<div class="content-card p-5 mb-5">
  <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
    <div class="text-[14px] font-extrabold" style="color:var(--text-h);"><i class="fa-solid fa-panorama" style="color:var(--primary);"></i> {{ $isEditing ? 'ویرایش بنر' : 'بنرهای صفحه ترند' }}</div>
    @if($isEditing)<a href="{{ route('admin.trends.index') }}" class="btn-pro btn-pro-ghost text-[10px]">انصراف از ویرایش</a>@endif
  </div>
  <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">بنر بعد از ردیف انتخابی نمایش داده می‌شود. برای نسخه دسکتاپ ابعاد پیشنهادی <strong>۱۲۰۰×۲۴۰ پیکسل</strong> و برای موبایل <strong>۶۸۰×۲۴۰ پیکسل</strong> است.</div>

  <form method="POST" action="{{ $bannerAction }}" enctype="multipart/form-data" class="grid grid-cols-4 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1 gap-3 items-end">
    @csrf
    @if($isEditing) @method('PUT') @endif
    <div class="col-span-2 max-[900px]:col-span-2 max-[560px]:col-span-1">
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">عنوان بنر</label>
      <input type="text" name="title" required class="input-pro w-full" value="{{ old('title', $banner?->title) }}" placeholder="مثلاً پیشنهاد ویژه این هفته">
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">نمایش در</label>
      <select name="display_target" class="input-pro w-full">
        <option value="both" @selected(old('display_target', $banner?->display_target ?: 'both') === 'both')>موبایل و دسکتاپ</option>
        <option value="mobile" @selected(old('display_target', $banner?->display_target) === 'mobile')>فقط موبایل</option>
        <option value="desktop" @selected(old('display_target', $banner?->display_target) === 'desktop')>فقط دسکتاپ</option>
      </select>
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">بعد از ردیف</label>
      <input type="number" name="row_number" min="4" step="4" max="500" required class="input-pro w-full" value="{{ old('row_number', $banner?->row_number ?: 4) }}">
    </div>
    <div>
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">اولویت</label>
      <input type="number" name="sort_order" min="0" max="999" class="input-pro w-full" value="{{ old('sort_order', $banner?->sort_order ?: 0) }}">
    </div>
    <label class="flex items-center gap-2 text-[11px] font-bold" style="color:var(--text-soft);"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner?->is_active ?? true))> فعال باشد</label>
    <div class="col-span-2 max-[900px]:col-span-2 max-[560px]:col-span-1">
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">تصویر دسکتاپ <span class="font-normal">— ۱۲۰۰×۲۴۰</span></label>
      <input type="file" name="image_desktop" accept="image/jpeg,image/png,image/webp" class="input-pro w-full text-[10px]" @required(!$isEditing && $banner?->display_target !== 'mobile')>
      @if($banner?->image_desktop)<div class="text-[9px] mt-1" style="color:var(--text-soft);">تصویر فعلی حفظ می‌شود مگر فایل جدید انتخاب کنید.</div>@endif
    </div>
    <div class="col-span-2 max-[900px]:col-span-2 max-[560px]:col-span-1">
      <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">تصویر موبایل <span class="font-normal">— ۶۸۰×۲۴۰</span></label>
      <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="input-pro w-full text-[10px]" @required(!$isEditing && $banner?->display_target !== 'desktop')>
      @if($banner?->image_mobile)<div class="text-[9px] mt-1" style="color:var(--text-soft);">تصویر فعلی حفظ می‌شود مگر فایل جدید انتخاب کنید.</div>@endif
    </div>
    <div class="col-span-4 max-[900px]:col-span-2 max-[560px]:col-span-1 flex justify-end">
      <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid {{ $isEditing ? 'fa-floppy-disk' : 'fa-plus' }} text-[11px]"></i> {{ $isEditing ? 'ذخیره بنر' : 'افزودن بنر' }}</button>
    </div>
  </form>
</div>
