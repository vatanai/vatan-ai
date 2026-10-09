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
      <div class="ps-field">
        <label for="sp-category-search">دسته‌بندی‌ها</label>
        <div class="ps-category-picker" data-category-picker>
          <div class="ps-category-tags" data-category-tags><input id="sp-category-search" type="search" placeholder="جستجو یا انتخاب دسته‌بندی..." autocomplete="off" data-category-search></div>
          <div class="ps-category-results" data-category-results hidden></div>
        </div>
        <div data-category-inputs></div>
        <div class="ps-hint">می‌توانید چند دسته انتخاب کنید؛ اولین انتخاب، دسته‌ی اصلی محصول می‌شود.</div>
      </div>
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

  @php
    $__psDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $__psImagePaths = collect(array_merge([$product?->cover], (array) ($product?->sample_outputs ?? [])))
      ->filter(fn ($path) => is_string($path) && $path !== ''
        && ! \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:', 'products/thumbnails/default_placeholder', 'products/shot-samples/'])
        && $__psDisk->exists($path))
      ->unique()->values()->all();
  @endphp
  <section class="content-card p-5" id="ps-product-images">
    <div class="ps-card-title"><i class="fa-solid fa-cloud-arrow-up"></i> آپلود عکس محصول</div>
    <div class="ps-card-desc">عکس‌های معرفی محصول را انتخاب کنید؛ اولین عکس کاور کارت محصول است و بقیه در گالری نمایش داده می‌شوند. ترتیب و کاور را با کشیدن تصاویر کوچک تغییر دهید. اگر عکسی بارگذاری نشود، کولاژ نمونه‌ی اسلایدها مبنای کارت خواهد بود.</div>
    <div class="mt-4">
      @include('admin.partials.image-optimizer-group', [
        'inputId' => 'main-images-file',
        'inputName' => 'main_images[]',
        'title' => 'عکس‌های محصول',
        'hint' => 'JPG، PNG یا WebP — تا ۱۲ عکس؛ تصاویر به‌صورت خودکار و بدون برش بهینه می‌شوند.',
        'paths' => $__psImagePaths,
        'required' => false,
      ])
      <input type="hidden" name="remove_main_images" value="0" data-remove-main-images>
    </div>
  </section>
</div>
