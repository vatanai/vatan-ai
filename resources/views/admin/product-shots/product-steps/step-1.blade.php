<div class="space-y-4">
  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-id-card"></i> هویت و اطلاعات پایه</div><div class="ps-card-desc">اطلاعاتی که در کارت، کاتالوگ و صفحه‌ی ساخت محصول دیده می‌شود.</div>
    <div class="ps-grid mt-4">
      <div class="ps-field"><label for="sp-name-fa">نام فارسی <span class="text-[var(--danger)]">*</span></label><input id="sp-name-fa" name="name_fa" class="input-pro" required data-step-required value="{{ old('name_fa',$product?->name_fa) }}" placeholder="مثلاً پک تبلیغاتی سرم"></div>
      <div class="ps-field"><label for="sp-name-en">نام انگلیسی <span class="text-[var(--danger)]">*</span></label><input id="sp-name-en" name="name_en" class="input-pro" required data-step-required dir="ltr" value="{{ old('name_en',$product?->name_en) }}" placeholder="Serum advertising pack"></div>
    </div>
    <div class="ps-field mt-4"><label for="sp-desc">توضیح برای کاربر</label><textarea id="sp-desc" name="description_fa" class="input-pro" rows="4" placeholder="کاربر با این محصول چه خروجی‌هایی می‌گیرد؟">{{ old('description_fa',$product?->description_fa) }}</textarea></div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-folder-tree"></i> دسته‌بندی و اصناف</div><div class="ps-card-desc">دسته‌بندی محل نمایش محصول است؛ صنف‌ها نوع کسب‌وکار مناسب این پک را مشخص می‌کنند و چندانتخابی‌اند.</div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-4">
      <div class="ps-field"><label for="sp-categories">دسته‌بندی‌ها</label><select id="sp-categories" name="category_ids[]" class="input-pro" multiple size="12" style="height:310px;padding:8px;">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(in_array($category->id,$selectedCategories,true))>{{ $category->parent_id ? '— ' : '' }}{{ $category->name_fa ?: $category->name }}</option>@endforeach</select><div class="ps-hint">برای انتشار حداقل یک دسته لازم است؛ اولین مورد، دسته‌ی اصلی می‌شود.</div></div>
      <div>
        <div class="flex items-center justify-between gap-2 mb-2"><label class="text-xs font-bold text-[var(--text-main)]">اصناف مناسب <span class="text-[var(--danger)]">*</span></label><a href="{{ route('admin.categories.index',['tab'=>'occupations']) }}" target="_blank" class="text-[10px] text-[var(--primary)] no-underline">مدیریت اصناف</a></div>
        <input type="search" class="input-pro mb-2" placeholder="جستجوی صنف..." data-occupation-search>
        <div class="ps-occupation-picker" data-occupation-list>
          @foreach($occupations->groupBy('group_key') as $groupKey => $groupOccupations)
            <div class="ps-occupation-group"><strong>{{ $occupationGroups[$groupKey] ?? $groupKey }}</strong><div>@foreach($groupOccupations as $occupation)<label data-occupation-item data-search="{{ $occupation->name_fa }} {{ $occupation->name_en }}"><input type="checkbox" name="occupation_ids[]" value="{{ $occupation->id }}" @checked(in_array($occupation->id,$selectedOccupations,true))><span>{{ $occupation->name_fa }}</span></label>@endforeach</div></div>
          @endforeach
        </div>
        <div class="ps-hint">حداقل یک صنف الزامی است؛ یک محصول می‌تواند هم‌زمان برای چند صنف مناسب باشد.</div>
      </div>
    </div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-regular fa-image"></i> تصویر اصلی کارت</div><div class="ps-card-desc">اگر تصویر جدا بارگذاری نشود، کولاژ چهار شات پیش‌فرض به‌صورت خودکار مبنای کارت پک خواهد بود.</div>
    <div class="ps-field mt-4"><input type="file" name="cover" id="sp-cover" accept="image/jpeg,image/png,image/webp" class="input-pro">@if($product?->cover)<img src="{{ $product->displayImageUrl() }}" alt="" class="mt-3 w-32 h-32 object-cover rounded-xl border border-[var(--border)]">@endif</div>
  </section>
</div>
