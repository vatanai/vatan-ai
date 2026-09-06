@php($editing = isset($category) && $category)
<div class="mb-6 flex items-center justify-between gap-3">
  <div><h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">{{ $editing ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی' }}</h1><p class="text-xs text-[var(--text-soft)]">اطلاعات دسته‌بندی و لینک صفحه عمومی آن را تنظیم کنید.</p></div>
  <a href="{{ route('admin.categories.index') }}" class="px-3 h-9 inline-flex items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--card-bg)] text-[var(--text-soft)] no-underline text-xs"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
</div>
@if($errors->any())<div class="mb-4 border border-[var(--danger)] rounded-xl p-3 bg-[var(--card-bg)] text-xs text-[var(--text-main)]"><ul class="list-disc pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
  @csrf @if($editing) @method('PUT') @endif
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-5 shadow-[var(--shadow-card)]">
      <div class="text-sm font-bold text-[var(--text-main)] pb-3 mb-4 border-b border-[var(--divider)]"><i class="fa-solid fa-folder text-[var(--primary)] ml-1"></i> مشخصات دسته‌بندی</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="text-xs font-bold text-[var(--text-main)]">نام دسته‌بندی <span class="text-[var(--danger)]">*</span><input name="name" required value="{{ old('name', $category?->name) }}" class="mt-2 w-full rounded-lg bg-[var(--input-bg)] border border-[var(--border)] p-2.5 text-[var(--text-main)] outline-none focus:border-[var(--primary)]"></label>
        <label class="text-xs font-bold text-[var(--text-main)]">اسلاگ<input name="slug" value="{{ old('slug', $category?->slug) }}" dir="ltr" class="mt-2 w-full rounded-lg bg-[var(--input-bg)] border border-[var(--border)] p-2.5 text-[var(--text-main)] outline-none focus:border-[var(--primary)]" placeholder="portrait-tools"></label>
        <label class="text-xs font-bold text-[var(--text-main)]">دسته والد<select name="parent_id" class="mt-2 w-full rounded-lg bg-[var(--input-bg)] border border-[var(--border)] p-2.5 text-[var(--text-main)]"><option value="">بدون والد</option>@foreach(\App\Models\Category::where('id','!=',$category?->id ?? 0)->orderBy('name')->get() as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$category?->parent_id)===(string)$parent->id)>{{ $parent->name_fa ?: $parent->name }}</option>@endforeach</select></label>
        <label class="text-xs font-bold text-[var(--text-main)]">لینک دستی دسته‌بندی<input name="custom_url" value="{{ old('custom_url', $category?->custom_url) }}" dir="ltr" class="mt-2 w-full rounded-lg bg-[var(--input-bg)] border border-[var(--border)] p-2.5 text-[var(--text-main)] outline-none focus:border-[var(--primary)]" placeholder="/app/products?categories[]=1"><small class="block mt-1.5 font-normal text-[var(--text-soft)]">اختیاری؛ اگر خالی باشد لینک استاندارد دسته‌بندی خودکار ساخته می‌شود.</small></label>
      </div>
    </div>
    <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-5 shadow-[var(--shadow-card)]">
      <div class="text-sm font-bold text-[var(--text-main)] pb-3 mb-4 border-b border-[var(--divider)]"><i class="fa-regular fa-image text-[var(--primary)] ml-1"></i> تصویر دسته‌بندی</div>
      @if($editing && $category->image)<img src="{{ asset('storage/'.$category->image) }}" class="w-full h-36 object-cover rounded-lg mb-3 border border-[var(--border)]">@endif
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-[var(--text-soft)]">
      <p class="text-[10px] text-[var(--text-soft)] mt-2">JPEG، PNG یا WebP تا ۲ مگابایت</p>
    </div>
  </div>
  <div class="mt-5 flex justify-end"><button class="h-10 px-5 rounded-lg bg-[var(--primary)] text-[var(--accent)] text-xs font-bold cursor-pointer"><i class="fa-solid fa-floppy-disk ml-1"></i> ذخیره دسته‌بندی</button></div>
</form>
