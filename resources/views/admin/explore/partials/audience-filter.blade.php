@php
  $storedFilters = $mode === 'include'
      ? (array) ($setting->include_filters ?? [])
      : (array) ($setting->exclude_filters ?? []);
  $selectedCategories = array_map('intval', old($mode.'_categories', $storedFilters['categories'] ?? []));
  $selectedTags = old($mode.'_tags', $storedFilters['tags'] ?? []);
  $selectedTraits = old($mode.'_traits', $storedFilters['traits'] ?? []);
  $selectedMedia = old($mode.'_media', $storedFilters['media'] ?? []);
  $selectedProducts = array_map('intval', old($mode.'_products', $storedFilters['products'] ?? []));
  $traitLabels = [
      'featured' => 'ویژه',
      'normal' => 'عادی',
      'new' => 'جدید',
      'trending' => 'ترند',
  ];
  $isExclude = $mode === 'exclude';
@endphp

<section class="explore-audience-box {{ $isExclude ? 'is-exclude' : 'is-include' }}">
  <div class="flex items-start justify-between gap-3 mb-4">
    <div>
      <div class="text-[13px] font-extrabold" style="color:var(--text-h);">
        <i class="fa-solid {{ $isExclude ? 'fa-eye-slash' : 'fa-eye' }}"></i>
        {{ $isExclude ? 'نمایش داده نشوند' : 'نمایش داده شوند' }}
      </div>
      <div class="text-[10.5px] mt-1 leading-5" style="color:var(--text-soft);">
        {{ $isExclude ? 'هر محصولی که با یکی از این قوانین تطبیق داشته باشد حذف می‌شود.' : 'گروه‌های فعال با هم ترکیب می‌شوند؛ داخل هر گروه انتخاب‌ها حالت «یا» دارند.' }}
      </div>
    </div>
    <span class="explore-filter-state">{{ $isExclude ? 'فیلتر معکوس' : 'فیلتر ورودی' }}</span>
  </div>

  <div class="mb-3">
    <div class="text-[10.5px] font-bold mb-2" style="color:var(--text-soft);">نوع محصول</div>
    <div class="flex flex-wrap gap-2">
      @foreach($traitLabels as $value => $label)
        <label class="explore-filter-chip">
          <input type="checkbox" name="{{ $mode }}_traits[]" value="{{ $value }}" {{ in_array($value, $selectedTraits, true) ? 'checked' : '' }}>
          <span>{{ $label }}</span>
        </label>
      @endforeach
    </div>
  </div>

  <div class="mb-3">
    <div class="text-[10.5px] font-bold mb-2" style="color:var(--text-soft);">نوع رسانه</div>
    <div class="flex flex-wrap gap-2">
      @foreach(['photo' => 'تصویر', 'video' => 'ویدیو'] as $value => $label)
        <label class="explore-filter-chip">
          <input type="checkbox" name="{{ $mode }}_media[]" value="{{ $value }}" {{ in_array($value, $selectedMedia, true) ? 'checked' : '' }}>
          <span>{{ $label }}</span>
        </label>
      @endforeach
    </div>
  </div>

  <details class="explore-filter-details" data-filter-group>
    <summary>
      <span><i class="fa-solid fa-layer-group"></i> دسته‌بندی‌ها</span>
      <span class="explore-filter-count" data-filter-count>{{ count($selectedCategories) }}</span>
    </summary>
    <div class="explore-filter-dropdown">
      <input type="search" class="input-pro w-full mb-2" placeholder="جستجوی دسته‌بندی..." data-filter-search autocomplete="off">
      <div class="explore-filter-list">
        @foreach($categories as $category)
          @php $categoryLabel = $category->name_fa ?: $category->name ?: $category->name_en; @endphp
          <label data-filter-item data-filter-text="{{ mb_strtolower($categoryLabel) }}">
            <input type="checkbox" name="{{ $mode }}_categories[]" value="{{ $category->id }}" {{ in_array((int) $category->id, $selectedCategories, true) ? 'checked' : '' }}>
            <span>{{ $categoryLabel }}</span>
          </label>
        @endforeach
      </div>
    </div>
  </details>

  <details class="explore-filter-details" data-filter-group>
    <summary>
      <span><i class="fa-solid fa-hashtag"></i> هشتگ‌ها</span>
      <span class="explore-filter-count" data-filter-count>{{ count($selectedTags) }}</span>
    </summary>
    <div class="explore-filter-dropdown">
      <input type="search" class="input-pro w-full mb-2" placeholder="جستجوی هشتگ..." data-filter-search autocomplete="off">
      <div class="explore-filter-list">
        @forelse($filterTags as $tag)
          <label data-filter-item data-filter-text="{{ mb_strtolower($tag) }}">
            <input type="checkbox" name="{{ $mode }}_tags[]" value="{{ $tag }}" {{ in_array($tag, $selectedTags, true) ? 'checked' : '' }}>
            <span>#{{ $tag }}</span>
          </label>
        @empty
          <span class="block p-2 text-[10.5px]" style="color:var(--text-soft);">هشتگی ثبت نشده است.</span>
        @endforelse
      </div>
    </div>
  </details>

  <details class="explore-filter-details" data-filter-group>
    <summary>
      <span><i class="fa-solid fa-box-open"></i> انتخاب مستقیم محصول</span>
      <span class="explore-filter-count" data-filter-count>{{ count($selectedProducts) }}</span>
    </summary>
    <div class="explore-filter-dropdown">
      <input type="search" class="input-pro w-full mb-2" placeholder="جستجوی نام محصول..." data-filter-search autocomplete="off">
      <div class="explore-filter-list is-products">
        @foreach($products as $product)
          <label data-filter-item data-filter-text="{{ mb_strtolower($product->name_fa) }}">
            <input type="checkbox" name="{{ $mode }}_products[]" value="{{ $product->id }}" {{ in_array((int) $product->id, $selectedProducts, true) ? 'checked' : '' }}>
            <span class="truncate">{{ $product->name_fa }}</span>
            @if($product->is_featured)<small>ویژه</small>@endif
          </label>
        @endforeach
      </div>
    </div>
  </details>
</section>
