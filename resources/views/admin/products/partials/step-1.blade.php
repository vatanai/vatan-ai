{{-- پارشیال: گام اول — هویت محصول --}}
{{-- بازطراحی UI طبق «سند شماره ۱ - ثبت محصول»، بخش اول.
     تمام name های ورودی و منطق Blade (old/duplicateFrom) دقیقاً حفظ شده‌اند — فقط ظاهر و UX تغییر کرده.
     فیلدهای دارای پیشوند NEW فقط UI هستند و به Backend وصل نیستند (نگاه کنید به بادج «برنامه‌نویسی شود»). --}}

@php
  // بادج کوچک کنار فیلدهای جدید تا توسعه‌دهنده Backend دقیقاً بفهمد این مورد هنوز به دیتابیس/API وصل نیست
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';

  // آیکون «راهنمایی آیتم» — فقط برای فیلدهای واقعاً وصل‌شده به Backend (متن کامل از config/product_field_help.php خوانده می‌شود)
  // نکته مهم: عمداً <span role="button"> است نه <button> واقعی — چون این آیکون گاهی داخل عناصر <label>
  // (از جمله لیبل خودِ سوییچ روشن/خاموش) قرار می‌گیرد؛ <button> چون خودش هم «Labelable» است ممکن بود مرورگر
  // آن را به‌جای چک‌باکس واقعی «کنترل صاحب لیبل» در نظر بگیرد و با کلیک روی خودِ سوییچ (نه آیکون)، به‌جای
  // تغییر وضعیت چک‌باکس، پنجره راهنما باز شود. با <span> این تداخل کاملاً از بین می‌رود.
  $__help = function (string $key, string $title) {
      $text = config('product_field_help.' . $key, '');
      if ($text === '') return '';
      return '<span class="field-help-btn inline-flex items-center justify-center shrink-0 cursor-pointer text-[var(--text3)] hover:text-[var(--accent)] transition-colors" role="button" tabindex="0" data-help-title="' . e($title) . '" data-help-text="' . e($text) . '" aria-label="راهنمایی آیتم"><i class="fa-solid fa-circle-question text-[10px]"></i></span>';
  };

  // منبع «فایل از قبل موجود» برای پیش‌نمایش Thumbnail/Cover/نمونه‌خروجی‌ها: هم در حالت تکثیر محصول
  // ($duplicateFrom) و هم در حالت ویرایش واقعی محصول ($product) باید همان فایل قبلی نمایش داده شود
  // و اجباری‌بودنِ آپلود مجدد Thumbnail برداشته شود (چون فایل موجود، اگر آپلود جدیدی انجام نشود، حفظ می‌ماند).
  $__mediaSource = $duplicateFrom ?? ($product ?? null);
@endphp

{{-- ═══════════════════ Card ۱ — اطلاعات اصلی ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-fingerprint text-[var(--accent)]"></i> اطلاعات اصلی</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">نام، آدرس، توضیح و دسته‌بندی محصول را وارد کنید</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">نام فارسی <span class="text-[var(--red)] mr-0.5">*</span></label>
      <input type="text" name="name_fa" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)]" value="{{ old('name_fa', $duplicateFrom ? $duplicateFrom->name_fa : '') }}" placeholder="مثلاً: عکس حرفه‌ای لینکدین">
      <div class="text-[10px] text-[var(--text3)] flex items-center gap-1">نامی که به کاربر فارسی‌زبان نمایش داده می‌شود {!! $__help('name_fa', 'نام فارسی') !!}</div>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">نام انگلیسی <span class="text-[var(--red)] mr-0.5">*</span></label>
      <input type="text" name="name_en" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] ltr text-left" value="{{ old('name_en', $duplicateFrom ? $duplicateFrom->name_en : '') }}" placeholder="LinkedIn Professional Headshot" oninput="if(typeof autoSlug === 'function') autoSlug(this)">
      <div class="text-[10px] text-[var(--text3)] flex items-center gap-1">همچنین برای ساخت خودکار Slug استفاده می‌شود {!! $__help('name_en', 'نام انگلیسی') !!}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">آدرس URL (Slug) <span class="text-[var(--red)] mr-0.5">*</span></label>
      <input type="text" name="slug" id="slug-input" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] ltr text-left" value="{{ old('slug', $duplicateFrom ? $duplicateFrom->slug.'-2' : '') }}" placeholder="linkedin-professional-headshot" oninput="if(typeof lockSlugManual === 'function') lockSlugManual()">
      <div class="text-[10px] text-[var(--text3)] flex items-center gap-1"><span>به‌صورت خودکار از نام انگلیسی ساخته می‌شود؛ اگر دستی ویرایش کنید دیگر خودکار به‌روزرسانی نمی‌شود @if($duplicateFrom)— این آدرس باید یکتا باشد، در صورت تکراری بودن هنگام ثبت خطا نمایش داده می‌شود@endif.</span> {!! $__help('slug', 'آدرس URL (Slug)') !!}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">توضیح فارسی {!! $__help('description_fa', 'توضیح فارسی') !!}</label>
        <span class="text-[10px] text-[var(--text3)]" id="desc-fa-count">{{ mb_strlen(old('description_fa', optional($duplicateFrom)->description_fa)) }} کاراکتر</span>
      </div>
      <textarea name="description_fa" rows="4" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] resize-y min-h-[100px] leading-relaxed" placeholder="توضیح کوتاهی از محصول برای کاربر..." oninput="document.getElementById('desc-fa-count').textContent = this.value.length + ' کاراکتر'">{{ old('description_fa', optional($duplicateFrom)->description_fa) }}</textarea>
    </div>
    <div class="flex flex-col gap-1.5">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">توضیح انگلیسی {!! $__help('description_en', 'توضیح انگلیسی') !!}</label>
        <span class="text-[10px] text-[var(--text3)]" id="desc-en-count">{{ mb_strlen(old('description_en', optional($duplicateFrom)->description_en)) }} کاراکتر</span>
      </div>
      <textarea name="description_en" rows="4" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] resize-y min-h-[100px] leading-relaxed ltr text-left" placeholder="Short product description for users..." oninput="document.getElementById('desc-en-count').textContent = this.value.length + ' کاراکتر'">{{ old('description_en', optional($duplicateFrom)->description_en) }}</textarea>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">دسته‌بندی محصول <span class="text-[var(--red)] mr-0.5">*</span> {!! $__help('category_ids', 'دسته‌بندی محصول') !!}</label>
      {{-- دسته‌بندی چندگانه به‌صورت تگ: هر دسته‌ی انتخاب‌شده یک چیپ در cat-tags-wrap است و در لحظه‌ی ثبت فرم
           (submitForm در products-create.js) به‌صورت category_ids[] به کنترلر ارسال می‌شود؛ کنترلر از قبل
           آماده‌ی خواندن این آرایه و sync با رابطه‌ی belongsToMany محصول↔دسته‌بندی است. --}}
      @php
        $__renderCatFlat = function ($categories, $depth = 0) use (&$__renderCatFlat) {
            $flat = [];
            foreach ($categories as $cat) {
                $flat[] = ['id' => $cat->id, 'name' => $cat->name_fa, 'depth' => $depth];
                if ($cat->childrenRecursive->isNotEmpty()) {
                    $flat = array_merge($flat, $__renderCatFlat($cat->childrenRecursive, $depth + 1));
                }
            }
            return $flat;
        };
        $__rootCategories  = \App\Models\Category::with('childrenRecursive')->whereNull('parent_id')->orderBy('sort_order')->get();
        $__categoriesFlat  = $__renderCatFlat($__rootCategories);

        $__selectedCategoryIds = collect(old('category_ids'))->filter()->map(fn ($v) => (int) $v)->values();
        if ($__selectedCategoryIds->isEmpty() && $duplicateFrom) {
            $__selectedCategoryIds = $duplicateFrom->categories->pluck('id');
        }
        if ($__selectedCategoryIds->isEmpty()) {
            $__legacySingleCategoryId = old('category_id', optional($duplicateFrom)->category_id);
            if ($__legacySingleCategoryId) $__selectedCategoryIds = collect([(int) $__legacySingleCategoryId]);
        }
        $__selectedCategoriesInit = collect($__categoriesFlat)->whereIn('id', $__selectedCategoryIds->all())->values();
      @endphp
      <div class="relative" id="cat-multiselect">
        <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] cursor-text focus-within:border-[var(--accent)]" id="cat-tags-wrap" onclick="document.getElementById('cat-search-input').focus()">
          <input type="text" id="cat-search-input" class="bg-transparent border-none outline-none text-xs text-[var(--text)] flex-1 min-w-[120px] text-right" placeholder="جستجو یا انتخاب دسته‌بندی..." autocomplete="off" oninput="renderCatDropdown(this.value)" onfocus="renderCatDropdown(this.value)">
        </div>
        <div class="hidden absolute z-30 mt-1 w-full max-h-56 overflow-y-auto bg-[var(--s2)] border border-[var(--b1)] rounded-lg shadow-xl py-1" id="cat-dropdown"></div>
      </div>
      <div class="text-[10px] text-[var(--text3)]">می‌توانید بیش از یک دسته‌بندی برای این محصول انتخاب کنید — از لیست انتخاب کنید یا جستجو کنید</div>
      <script>window.CATEGORIES_FLAT = @json($__categoriesFlat); window.CATEGORIES_SELECTED_INIT = @json($__selectedCategoriesInit);</script>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">تگ‌های جستجو <span class="text-[10px] font-normal text-[var(--text3)] mr-1">Enter بزنید</span> {!! $__help('tags', 'تگ‌های جستجو') !!}</label>
      <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] focus-within:border-[var(--accent)]" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
        @foreach (collect(old('tags', optional($duplicateFrom)->tags ?? []))->map(fn ($tag) => trim((string) $tag))->filter()->unique(fn ($tag) => mb_strtolower($tag))->values() as $tag)
          <span data-tag-chip class="inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]">{{ $tag }}<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" onclick="this.parentElement.remove()" aria-label="حذف برچسب">×</button></span>
        @endforeach
        <input type="text" id="tags-raw" class="bg-transparent border-none outline-none text-xs text-[var(--text)] flex-1 min-w-[80px] text-right" placeholder="تگ بنویسید..." onkeydown="if(typeof addTag === 'function') addTag(event)">
      </div>
    </div>
  </div>

  {{-- ── تنظیمات خروجی کاربر: سایز و کیفیت ── --}}
  @php
    $__outputSource = $duplicateFrom ?? ($product ?? null);
    $__ratioOptions = [
      'auto' => ['label' => 'خودکار', 'shape' => 'w-5 h-5'],
      '1:1' => ['label' => '۱:۱', 'shape' => 'w-5 h-5'],
      '9:16' => ['label' => '۹:۱۶', 'shape' => 'w-4 h-6'],
      '16:9' => ['label' => '۱۶:۹', 'shape' => 'w-6 h-4'],
      '2:3' => ['label' => '۲:۳', 'shape' => 'w-4 h-6'],
      '3:2' => ['label' => '۳:۲', 'shape' => 'w-6 h-4'],
      '3:4' => ['label' => '۳:۴', 'shape' => 'w-4 h-5'],
      '4:3' => ['label' => '۴:۳', 'shape' => 'w-5 h-4'],
    ];
    $__enabledRatios = collect(old('allowed_aspect_ratios', $__outputSource?->allowedAspectRatioList() ?? array_keys($__ratioOptions)))
      ->map(fn ($value) => (string) $value)->all();
    $__enabledResolutions = collect(old('allowed_resolutions', $__outputSource?->allowedResolutionList() ?? ['720', '1080']))
      ->map(fn ($value) => (string) $value)->all();
  @endphp
  <div class="border-t border-[var(--b1)] pt-5 mt-1">
    <div class="mb-4">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-sliders text-[var(--accent)]"></i> تنظیمات خروجی کاربر</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">گزینه‌های روشن در صفحه ساخت به کاربر نمایش داده می‌شوند؛ حالت پیش‌فرض سایز `۳:۴` و کیفیت `۷۲۰` است.</div>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div class="text-[11px] font-bold text-[var(--text2)]"><i class="fa-solid fa-crop-simple text-[var(--accent)] ml-1.5"></i>سایزهای خروجی</div>
          <span class="text-[9px] text-[var(--text3)]">چند گزینه‌ای</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          @foreach($__ratioOptions as $__ratio => $__ratioMeta)
            <label class="output-option-card relative flex flex-col items-center justify-center gap-1.5 min-h-[76px] rounded-lg border border-[var(--b1)] bg-[var(--s2)] cursor-pointer transition-all hover:border-[var(--accent)] {{ in_array($__ratio, $__enabledRatios, true) ? 'is-enabled border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
              <input type="checkbox" name="allowed_aspect_ratios[]" value="{{ $__ratio }}" class="sr-only peer" {{ in_array($__ratio, $__enabledRatios, true) ? 'checked' : '' }}>
              <span class="{{ $__ratioMeta['shape'] }} border-2 border-[var(--text3)] rounded-[4px] opacity-80 peer-checked:border-[var(--accent)]"></span>
              <span class="text-[10px] font-bold text-[var(--text2)] peer-checked:text-[var(--accent)]">{{ $__ratioMeta['label'] }}</span>
              <span class="absolute top-1.5 left-1.5 w-4 h-4 rounded-full border border-[var(--b2)] bg-[var(--s1)] text-transparent peer-checked:bg-[var(--green)] peer-checked:border-[var(--green)] peer-checked:text-white flex items-center justify-center text-[8px]"><i class="fa-solid fa-check"></i></span>
            </label>
          @endforeach
        </div>
      </div>
      <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div class="text-[11px] font-bold text-[var(--text2)]"><i class="fa-solid fa-display text-[var(--accent)] ml-1.5"></i>کیفیت‌های خروجی</div>
          <span class="text-[9px] text-[var(--text3)]">۷۲۰ و ۱۰۸۰</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
          @foreach(['720' => 'استاندارد', '1080' => 'بالاتر'] as $__resolution => $__resolutionLabel)
            <label class="output-option-card relative flex items-center justify-between gap-2 min-h-[76px] rounded-lg border border-[var(--b1)] bg-[var(--s2)] px-3 cursor-pointer transition-all hover:border-[var(--accent)] {{ in_array($__resolution, $__enabledResolutions, true) ? 'is-enabled border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
              <input type="checkbox" name="allowed_resolutions[]" value="{{ $__resolution }}" class="sr-only peer" {{ in_array($__resolution, $__enabledResolutions, true) ? 'checked' : '' }}>
              <span class="flex flex-col gap-1"><b class="text-[13px] text-[var(--text)] peer-checked:text-[var(--accent)]">{{ $__resolution }}</b><small class="text-[9px] text-[var(--text3)]">{{ $__resolutionLabel }}</small></span>
              <span class="w-5 h-5 rounded-md border border-[var(--b2)] bg-[var(--s1)] text-transparent peer-checked:bg-[var(--green)] peer-checked:border-[var(--green)] peer-checked:text-white flex items-center justify-center text-[9px]"><i class="fa-solid fa-check"></i></span>
            </label>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- ── اطلاعات داخلی مدیر (فعال و ذخیره‌شونده) ── --}}
  <div class="hidden border-t border-dashed border-[var(--b2)] pt-4" data-future-update="اطلاعات داخلی مدیر">
    <div class="text-[10.5px] font-bold text-[var(--text3)] mb-3 tracking-wide uppercase flex items-center gap-1.5"><i class="fa-solid fa-lock text-[10px]"></i> اطلاعات داخلی مدیر</div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">وضعیت محصول {!! $newBadge !!}</label>
        <select name="new_product_status" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
          <option value="draft">پیش‌نویس</option>
          <option value="active">فعال</option>
          <option value="inactive">غیرفعال</option>
        </select>
        <div class="text-[10px] text-[var(--text3)]">وضعیت اولیه نمایش محصول (نمایشی — تصمیم نهایی با دکمه‌های ثبت/پیش‌نویس است).</div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">کد داخلی محصول</label>
        <input type="text" name="new_internal_code" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left w-full" placeholder="فقط برای مدیر — مثلاً PRD-0231" value="{{ old('new_internal_code', optional($duplicateFrom)->new_internal_code) }}">
        <div class="text-[10px] text-[var(--text3)] flex items-center gap-1">فقط مدیر این کد را می‌بیند {!! $__help('new_internal_code', 'کد داخلی محصول') !!}</div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">یادداشت مدیر {!! $__help('new_admin_note', 'یادداشت مدیر') !!}</label>
        <textarea name="new_admin_note" rows="2" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full resize-y" placeholder="یادداشت داخلی — به کاربران نمایش داده نمی‌شود">{{ old('new_admin_note', optional($duplicateFrom)->new_admin_note) }}</textarea>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════ Card ۲ — برچسب‌ها ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-tags text-[var(--accent)]"></i> برچسب‌ها</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">محصول در کدام بخش‌های سایت به‌صورت ویژه نمایش داده شود</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">محصول ویژه {!! $__help('is_featured', 'محصول ویژه') !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش در بخش ویژه سایت</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', optional($duplicateFrom)->is_featured) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">برچسب «جدید» {!! $__help('is_new', 'برچسب «جدید»') !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش نشان جدید روی کارت</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_new" value="1" {{ old('is_new', $duplicateFrom ? $duplicateFrom->is_new : true) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">ترند {!! $__help('is_trending', 'ترند') !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش در صفحه ترندز و فهرست محصولات ترند</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_trending" value="1" {{ old('is_trending', optional($duplicateFrom)->is_trending) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>
  </div>

  <div class="hidden" data-future-update="برچسب‌های ویژه">
    <div class="text-[10.5px] font-bold text-[var(--text3)] mt-4 mb-3 tracking-wide uppercase flex items-center gap-1.5 pt-4 border-t border-dashed border-[var(--b2)]"><i class="fa-solid fa-award text-[10px]"></i> برچسب‌های ویژه</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="نمایش نشان Premium روی کارت محصول">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">NEW Premium {!! $newBadge !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">محصول در رده اشتراک ویژه</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_is_premium" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="نمایش در بخش پیشنهادات ویژه">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">NEW Recommended {!! $newBadge !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش در بخش پیشنهادات</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_is_recommended" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="محصول در مرحله آزمایشی/Beta است">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">NEW Beta {!! $newBadge !!}</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش نشان آزمایشی روی کارت</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_is_beta" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>
    </div>
  </div>
</div>

{{-- ═══════════════════ Card ۳ — رسانه نمایشی ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-images text-[var(--accent)]"></i> رسانه نمایشی</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">تصاویر و ویدیوهایی که محصول را به کاربر نمایش می‌دهند</div>
  </div>

  @php
    $__mainPaths = array_values(array_filter(array_merge(
      [$__mediaSource?->cover],
      (array) ($__mediaSource?->sample_outputs ?? [])
    )));
    $__beforePaths = array_values(array_filter((array) ($__mediaSource?->before_images ?? [])));
  @endphp

  @foreach([
    ['main-images-file','main_images[]','عکس‌های اصلی','بعد از انتخاب تصاویر، کاور را از نوار تصاویر کوچک زیر پیش‌نمایش تعیین کنید.',$__mainPaths],
    ['before-images-file','before_images[]','عکس‌های قبل','تصاویر قبل از اجرای محصول؛ بدون برش و با حفظ نسبت.',$__beforePaths],
  ] as [$inputId,$inputName,$title,$hint,$paths])
    <div class="image-optimizer-group flex flex-col gap-2 mb-4" data-input="{{ $inputId }}" data-existing='@json(array_map(fn($p) => asset("storage/$p"), $paths))'>
      <label class="text-xs font-semibold text-[var(--text2)]">{{ $title }} @if($inputId === 'main-images-file')<span class="text-[var(--red)] mr-0.5">*</span>@endif</label>
      <div class="upload-zone border-2 border-dashed border-[var(--b2)] rounded-xl p-5 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors" onclick="document.getElementById('{{ $inputId }}').click()">
        <i class="fa-solid fa-images text-xl text-[var(--text3)] mb-1 block"></i>
        <div class="text-xs text-[var(--text2)] image-file-label">انتخاب تصاویر</div>
        <div class="text-[10px] text-[var(--text3)] mt-1">{{ $hint }}</div>
        <div class="flex flex-wrap gap-2 justify-center mt-3 image-preview-strip"></div>
        <input type="file" id="{{ $inputId }}" name="{{ $inputName }}" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @if($inputId === 'main-images-file' && empty($paths)) required @endif>
      </div>

      <div class="image-compare-workspace hidden border border-[var(--b1)] rounded-2xl overflow-hidden bg-[var(--s1)]">
        <div class="grid grid-cols-2 gap-0 image-compare-pair">
          <button type="button" class="relative min-w-0 border-l border-[var(--b1)] bg-[var(--bg)] text-right" onclick="openImageCompareModal(this)">
            <div class="absolute top-2 right-2 z-[1] px-2 py-1 rounded-lg bg-[var(--s2)]/90 border border-[var(--b1)] text-[10px] text-[var(--text2)]"><i class="fa-solid fa-file-image ml-1"></i>نسخه اصلی</div>
            <div class="aspect-square flex items-center justify-center overflow-hidden"><img class="image-compare-original w-full h-full object-contain" alt="نسخه اصلی"></div>
            <div class="p-3 border-t border-[var(--b1)] bg-[var(--s2)]">
              <div class="image-original-specs flex items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[10px] text-[var(--text3)]"></div>
            </div>
          </button>
          <button type="button" class="relative min-w-0 bg-[var(--bg)] text-right" onclick="openImageCompareModal(this)">
            <div class="absolute top-2 right-2 z-[1] inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-[var(--s2)]/90 border border-[var(--b1)] text-[10px] text-[var(--text2)]">
              <i class="image-result-icon fa-solid fa-hourglass-half text-[var(--text3)]"></i><span>نسخه بهینه‌شده</span>
            </div>
            <div class="image-result-loading hidden absolute inset-0 z-[2] bg-[var(--s1)]/85 backdrop-blur-sm items-center justify-center flex-col gap-2 text-[11px] text-[var(--text2)]"><i class="fa-solid fa-spinner fa-spin text-xl text-[var(--accent)]"></i><span>در حال پردازش تصویر…</span></div>
            <div class="aspect-square flex items-center justify-center overflow-hidden"><img class="image-compare-optimized w-full h-full object-contain opacity-30" alt="نسخه بهینه‌شده"></div>
            <div class="p-3 border-t border-[var(--b1)] bg-[var(--s2)]">
              <div class="image-optimized-specs flex items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[10px] text-[var(--text3)]"></div>
            </div>
          </button>
        </div>
        <div class="image-compare-thumbs flex gap-2 overflow-x-auto p-3 border-t border-[var(--b1)]"></div>
      </div>

      <div class="flex items-center gap-2 flex-wrap pt-1">
        <button type="button" class="image-optimize-btn btn-pro btn-pro-ghost" onclick="optimizeImageGroup(this)">
          <i class="fa-solid fa-wand-magic-sparkles"></i><span>بهینه‌سازی اتوماتیک</span>
        </button>
        <button type="button" class="btn-pro btn-pro-ghost" onclick="sharpenSelectedImage(this)">
          <i class="fa-solid fa-eye"></i><span>شارپ‌کردن عکس انتخاب‌شده</span>
        </button>
        <span class="image-optimize-status text-[10.5px] text-[var(--text3)]">@if($paths)برای بررسی تصاویر فعلی دکمه را بزنید.@endif</span>
      </div>

      <div class="image-target-panel hidden bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div><div class="text-[11px] font-semibold text-[var(--text2)]"><i class="fa-solid fa-gauge-high text-[var(--accent)] ml-1.5"></i>انتخاب حجم تقریبی خروجی</div><div class="text-[10px] text-[var(--text3)] mt-1">سه خروجی با کیفیت بیشتر و سه خروجی سبک‌تر از پیشنهاد خودکار. مقدار نهایی ممکن است کمی متفاوت باشد.</div></div>
          <div class="shrink-0 text-left"><small class="block text-[9px] text-[var(--text3)]">پیشنهاد خودکار</small><strong class="image-auto-size text-xs text-[var(--green)]">—</strong></div>
        </div>
        <div class="flex items-center justify-between gap-3 mb-3"><span class="text-[10.5px] text-[var(--text2)]">انتخاب نسخه و حجم خروجی عکس انتخاب‌شده</span><button type="button" class="btn-pro btn-pro-ghost image-reoptimize-btn" onclick="optimizeImageGroup(this)"><i class="fa-solid fa-rotate"></i><span>بررسی مجدد</span></button></div>
        <div class="image-volume-options-grid grid grid-cols-2 md:grid-cols-3 xl:grid-cols-9 gap-2 mb-4">
          <button type="button" data-profile="original" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'original')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-file-image text-[var(--accent)] ml-1.5"></i>نسخه اورجینال</span><strong class="image-original-size block text-xs text-[var(--text)] mt-1">—</strong></button>
          <button type="button" data-profile="site-standard" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'site-standard')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-star text-[var(--accent)] ml-1.5"></i>استاندارد پیشنهادی</span><strong class="block text-xs text-[var(--text)] mt-1">حدود ۳۰۰ کیلوبایت</strong></button>
          <button type="button" data-profile="site-light" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'site-light')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-feather text-[var(--accent)] ml-1.5"></i>استاندارد سبک</span><strong class="block text-xs text-[var(--text)] mt-1">حدود ۱۸۰ کیلوبایت</strong></button>
          <div class="image-target-options contents"></div>
        </div>

        <div class="image-size-timeline border-y border-[var(--b1)] py-4 mb-4">
          <div class="flex items-center justify-between gap-3 mb-2"><span class="text-[10.5px] text-[var(--text2)]"><i class="fa-solid fa-chart-line text-[var(--accent)] ml-1.5"></i>انتخاب آزاد حجم روی نمودار</span><strong class="image-range-value text-xs text-[var(--green)]">—</strong></div>
          <input type="range" min="60" max="1200" step="10" value="300" class="image-size-range w-full accent-[var(--green)] cursor-pointer" oninput="previewImageRange(this)" onchange="applyImageRange(this)">
          <div class="flex justify-between text-[9px] text-[var(--text3)] mt-1"><span>سبک‌تر</span><span>حجم استاندارد سایت</span><span>کیفیت بیشتر</span></div>
        </div>

      </div>

      <div class="image-compare-modal hidden fixed inset-0 z-[130] bg-[var(--page-bg)]/95 backdrop-blur-sm p-4 md:p-8" onclick="if(event.target===this) closeImageCompareModal(this)">
        <div class="w-full h-full max-w-[1500px] mx-auto flex flex-col bg-[var(--s2)] border border-[var(--b1)] rounded-2xl overflow-hidden">
          <div class="h-14 shrink-0 px-4 flex items-center justify-between border-b border-[var(--b1)]"><div class="text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-code-compare text-[var(--accent)] ml-2"></i>مقایسه بزرگ قبل و بعد</div><button type="button" class="w-9 h-9 rounded-lg border border-[var(--b1)] text-[var(--text2)]" onclick="closeImageCompareModal(this.closest('.image-compare-modal'))"><i class="fa-solid fa-xmark"></i></button></div>
          <div class="grid grid-cols-2 gap-0 flex-1 min-h-0">
            <div class="min-w-0 border-l border-[var(--b1)] flex flex-col"><div class="px-3 py-2 text-[11px] text-[var(--text2)] border-b border-[var(--b1)]">نسخه اصلی</div><div class="flex-1 min-h-0 p-2 flex items-center justify-center bg-[var(--bg)]"><img class="image-modal-original max-w-full max-h-full object-contain" alt="نسخه اصلی در اندازه بزرگ"></div></div>
            <div class="min-w-0 flex flex-col"><div class="px-3 py-2 text-[11px] text-[var(--text2)] border-b border-[var(--b1)]">نسخه بهینه‌شده</div><div class="flex-1 min-h-0 p-2 flex items-center justify-center bg-[var(--bg)]"><img class="image-modal-optimized max-w-full max-h-full object-contain" alt="نسخه بهینه‌شده در اندازه بزرگ"></div></div>
          </div>
        </div>
      </div>
    </div>
  @endforeach

  {{-- نوع رسانه — Radio Card --}}
  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">نوع رسانه {!! $__help('media_type', 'نوع رسانه') !!}</label>
    @php $curMediaType = old('media_type', optional($duplicateFrom)->media_type ?? 'photo'); @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
      <label class="media-type-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curMediaType == 'photo' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="media_type" value="photo" {{ $curMediaType == 'photo' ? 'checked' : '' }} class="accent-[var(--accent)]">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-image ml-1 text-[var(--text3)]"></i> عکس</span>
      </label>
      <label class="media-type-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curMediaType == 'video' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="media_type" value="video" {{ $curMediaType == 'video' ? 'checked' : '' }} class="accent-[var(--accent)]">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-video ml-1 text-[var(--text3)]"></i> ویدیو</span>
      </label>
      <label class="media-type-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curMediaType == 'both' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="media_type" value="both" {{ $curMediaType == 'both' ? 'checked' : '' }} class="accent-[var(--accent)]">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-layer-group ml-1 text-[var(--text3)]"></i> هر دو</span>
      </label>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">لینک ویدیوی پیش‌نمایش {!! $__help('preview_video_url', 'لینک ویدیوی پیش‌نمایش') !!}</label>
      <input type="text" name="preview_video_url" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left" placeholder="https://..." value="{{ old('preview_video_url', optional($duplicateFrom)->preview_video_url) }}">
    </div>
  </div>

  {{-- ── آیکون محصول (فعال و ذخیره‌شونده) ── --}}
  <div class="border-t border-dashed border-[var(--b2)] pt-4 mt-4">
    <div class="hidden flex flex-col gap-1.5 md:max-w-sm" data-future-update="آیکون محصول">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">آیکون محصول (Product Icon) {!! $__help('new_product_icon', 'آیکون محصول (Product Icon)') !!}</label>
      <div class="border-2 border-dashed border-[var(--b2)] rounded-xl p-4 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors w-full" onclick="document.getElementById('new-product-icon-file').click()">
        <i class="fa-solid fa-icons text-lg text-[var(--text3)] mb-1 block"></i>
        <div class="text-[11px] text-[var(--text2)]" id="new-icon-title">آپلود SVG یا PNG</div>
        <input type="file" id="new-product-icon-file" name="new_product_icon" accept=".svg,.png" class="hidden" onchange="updateFileLabel(this,'new-icon-title')">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-3.5">
      {{-- بند ۷: رنگ کارت محصول (Color Picker) — فقط UI --}}
      <div class="hidden flex flex-col gap-1.5" data-future-update="رنگ کارت محصول">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">رنگ کارت محصول {!! $newBadge !!}</label>
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <input type="color" name="new_card_color" value="#16594f" class="w-9 h-9 rounded-md border border-[var(--b1)] bg-transparent cursor-pointer shrink-0" oninput="document.getElementById('new-card-color-hex').value = this.value.toUpperCase()">
          <input type="text" id="new-card-color-hex" class="bg-transparent border-none outline-none text-xs text-[var(--text)] ltr text-left flex-1" value="#16594F" readonly>
        </div>
      </div>
      {{-- بند ۷: پیش‌نمایش حالت نمایش گالری (Grid/Slider/Carousel) — فقط UI --}}
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">پیش‌نمایش حالت گالری</label>
        <div class="flex gap-2">
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--accent)] bg-[var(--accent)]/8 text-[var(--text)]" data-mode="grid" onclick="setGalleryPreviewMode('grid')"><i class="fa-solid fa-table-cells block mb-1"></i>Grid</button>
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text2)]" data-mode="slider" onclick="setGalleryPreviewMode('slider')"><i class="fa-solid fa-images block mb-1"></i>Slider</button>
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text2)]" data-mode="carousel" onclick="setGalleryPreviewMode('carousel')"><i class="fa-solid fa-rectangle-list block mb-1"></i>Carousel</button>
        </div>
        <div id="gallery-mode-live-preview" class="grid grid-cols-5 gap-1.5 p-2 bg-[var(--bg)] border border-[var(--b1)] rounded-xl max-w-md"></div>
        <input type="hidden" name="new_gallery_preview_mode" id="new-gallery-preview-mode" value="grid">
      </div>
    </div>
  </div>
</div>

{{-- ── تعداد لایک نمایشی ── --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div>
      <label for="base-likes-count" class="text-xs font-bold text-[var(--text)] flex items-center gap-1.5">
        تعداد لایک {!! $__help('base_likes_count', 'تعداد لایک') !!}
      </label>
      <p class="text-[10.5px] text-[var(--text3)] mt-1">لایک‌های واقعی کاربران به این عدد پایه اضافه می‌شوند.</p>
    </div>
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <button type="button" class="h-10 px-3 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[11px] font-semibold text-[var(--text2)] hover:border-[var(--accent)] transition-colors" onclick="randomizeBaseLikes()">
        <i class="fa-solid fa-shuffle ml-1"></i> عدد تصادفی
      </button>
      <div class="relative flex-1 sm:w-40">
        <input id="base-likes-count" type="number" name="base_likes_count" min="0" max="999999999" inputmode="numeric"
               value="{{ old('base_likes_count', $suggestedLikesCount ?? 120) }}"
               class="w-full h-10 bg-[var(--s1)] border border-[var(--b1)] rounded-lg px-9 py-2 text-sm font-bold text-[var(--text)] text-center focus:outline-none focus:border-[var(--accent)]">
        <i class="fa-regular fa-heart absolute right-3 top-1/2 -translate-y-1/2 text-[var(--danger)] text-xs pointer-events-none"></i>
      </div>
    </div>
  </div>
</div>

<script>
function randomizeBaseLikes() {
  var input = document.getElementById('base-likes-count');
  if (input) input.value = Math.floor(Math.random() * 131) + 120;
}
</script>

{{-- ── بخش فازهای بعدی: خاموش تا وقتی نیاز شود ── --}}
<div class="hidden future-block bg-[var(--s2)] border border-dashed border-[var(--b2)] rounded-xl p-5 mb-5" data-future-update="تنظیمات فاز بعد">
  <label class="flex items-center justify-between gap-2 cursor-pointer">
    <div class="text-xs font-bold text-[var(--text2)] flex items-center gap-2"><i class="fa-solid fa-flask text-[var(--text3)]"></i> تنظیمات فاز بعد <span class="text-[10px] font-normal text-[var(--text3)]">(فعلاً نیازی نیست)</span></div>
    <span class="relative w-9 h-5 shrink-0 block">
      <input type="checkbox" class="sr-only peer" onchange="toggleFutureSection(this)">
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
    </span>
  </label>
  <div class="future-section hidden mt-4">
    <div class="flex flex-col gap-1.5 md:max-w-xs">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1">ترتیب نمایش {!! $__help('new_display_order', 'ترتیب نمایش') !!}</label>
      <input type="number" name="new_display_order" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full" placeholder="مثلاً: 1" value="{{ old('new_display_order', optional($duplicateFrom)->new_display_order) }}">
      <div class="text-[10px] text-[var(--text3)]">ترتیب دلخواه نمایش محصول در لیست‌ها (برای فاز مرتب‌سازی دستی).</div>
    </div>
  </div>
</div>

@include('admin.products.partials.step-1-scripts')
