{{-- پارشیال: گام اول — هویت محصول --}}
{{-- بازطراحی UI طبق «سند شماره ۱ - ثبت محصول»، بخش اول.
     تمام name های ورودی و منطق Blade (old/duplicateFrom) دقیقاً حفظ شده‌اند — فقط ظاهر و UX تغییر کرده.
     فیلدهای دارای پیشوند NEW فقط UI هستند و به Backend وصل نیستند (نگاه کنید به بادج «برنامه‌نویسی شود»). --}}

@php
  // بادج کوچک کنار فیلدهای جدید تا توسعه‌دهنده Backend دقیقاً بفهمد این مورد هنوز به دیتابیس/API وصل نیست
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
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
      <input type="text" name="name_fa" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)]" value="{{ old('name_fa', $duplicateFrom ? $duplicateFrom->name_fa.' (کپی)' : '') }}" placeholder="مثلاً: عکس حرفه‌ای لینکدین">
      <div class="text-[10px] text-[var(--text3)]">نامی که به کاربر فارسی‌زبان نمایش داده می‌شود</div>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">نام انگلیسی <span class="text-[var(--red)] mr-0.5">*</span></label>
      <input type="text" name="name_en" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] ltr text-left" value="{{ old('name_en', $duplicateFrom ? $duplicateFrom->name_en.'-copy' : '') }}" placeholder="LinkedIn Professional Headshot" oninput="if(typeof autoSlug === 'function') autoSlug(this)">
      <div class="text-[10px] text-[var(--text3)]">همچنین برای ساخت خودکار Slug استفاده می‌شود</div>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">آدرس URL (Slug) <span class="text-[var(--red)] mr-0.5">*</span></label>
      <input type="text" name="slug" id="slug-input" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] ltr text-left" value="{{ old('slug', $duplicateFrom ? $duplicateFrom->slug.'-copy' : '') }}" placeholder="linkedin-professional-headshot" oninput="if(typeof lockSlugManual === 'function') lockSlugManual()">
      <div class="text-[10px] text-[var(--text3)]">به‌صورت خودکار از نام انگلیسی ساخته می‌شود؛ اگر دستی ویرایش کنید دیگر خودکار به‌روزرسانی نمی‌شود @if($duplicateFrom)— این آدرس باید یکتا باشد، در صورت تکراری بودن هنگام ثبت خطا نمایش داده می‌شود@endif.</div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-[var(--text2)]">توضیح فارسی</label>
        <span class="text-[10px] text-[var(--text3)]" id="desc-fa-count">{{ mb_strlen(old('description_fa', optional($duplicateFrom)->description_fa)) }} کاراکتر</span>
      </div>
      <textarea name="description_fa" rows="4" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] resize-y min-h-[100px] leading-relaxed" placeholder="توضیح کوتاهی از محصول برای کاربر..." oninput="document.getElementById('desc-fa-count').textContent = this.value.length + ' کاراکتر'">{{ old('description_fa', optional($duplicateFrom)->description_fa) }}</textarea>
    </div>
    <div class="flex flex-col gap-1.5">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-[var(--text2)]">توضیح انگلیسی</label>
        <span class="text-[10px] text-[var(--text3)]" id="desc-en-count">{{ mb_strlen(old('description_en', optional($duplicateFrom)->description_en)) }} کاراکتر</span>
      </div>
      <textarea name="description_en" rows="4" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)] resize-y min-h-[100px] leading-relaxed ltr text-left" placeholder="Short product description for users..." oninput="document.getElementById('desc-en-count').textContent = this.value.length + ' کاراکتر'">{{ old('description_en', optional($duplicateFrom)->description_en) }}</textarea>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">دسته‌بندی محصول <span class="text-[var(--red)] mr-0.5">*</span></label>
    {{-- سلکت دسته‌بندی درختی: name = category_id (خوانده‌شده توسط کنترلر). ساختار تودرتو با فرورفتگی. --}}
    @php
      $__selectedCat = old('category_id', optional($duplicateFrom)->category_id);
      $__renderCatOptions = function ($categories, $depth = 0) use (&$__renderCatOptions, $__selectedCat) {
          $html = '';
          foreach ($categories as $cat) {
              $prefix   = $depth > 0 ? str_repeat('—', $depth) . ' ' : '';
              $selected = (string) $__selectedCat === (string) $cat->id ? 'selected' : '';
              $html .= '<option value="' . $cat->id . '" ' . $selected . '>'
                     . $prefix . e($cat->name_fa) . '</option>';
              if ($cat->childrenRecursive->isNotEmpty()) {
                  $html .= $__renderCatOptions($cat->childrenRecursive, $depth + 1);
              }
          }
          return $html;
      };
      $__rootCategories = \App\Models\Category::with('childrenRecursive')
          ->whereNull('parent_id')->orderBy('sort_order')->get();
    @endphp
<select name="category_id" data-searchable class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none transition-colors w-full focus:border-[var(--accent)]" id="cat-main" required>
  <option value="">انتخاب کنید...</option>
  {!! $__renderCatOptions($__rootCategories) !!}
</select>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">تگ‌های جستجو <span class="text-[10px] font-normal text-[var(--text3)] mr-1">Enter بزنید</span></label>
      <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] focus-within:border-[var(--accent)]" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
        @foreach ((old('tags', optional($duplicateFrom)->tags ?? [])) as $tag)
          <span class="inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]">{{ $tag }}<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" onclick="this.parentElement.remove()">×</button></span>
        @endforeach
        <input type="text" id="tags-raw" class="bg-transparent border-none outline-none text-xs text-[var(--text)] flex-1 min-w-[80px] text-right" placeholder="تگ بنویسید..." onkeydown="if(typeof addTag === 'function') addTag(event)">
      </div>
    </div>
  </div>

  {{-- ── اطلاعات داخلی مدیر (فعال و ذخیره‌شونده) ── --}}
  <div class="border-t border-dashed border-[var(--b2)] pt-4">
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
        <div class="text-[10px] text-[var(--text3)]">فقط مدیر این کد را می‌بیند</div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">یادداشت مدیر</label>
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
    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="نمایش در بخش ویژه صفحه اول سایت">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">محصول ویژه <i class="fa-solid fa-circle-info text-[9px] text-[var(--text3)]"></i></div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش در بخش ویژه سایت</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', optional($duplicateFrom)->is_featured) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="نمایش نشان «جدید» روی کارت محصول">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">برچسب «جدید» <i class="fa-solid fa-circle-info text-[9px] text-[var(--text3)]"></i></div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش نشان جدید روی کارت</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_new" value="1" {{ old('is_new', $duplicateFrom ? $duplicateFrom->is_new : true) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]" title="نمایش در بخش پرطرفدارها/ترند">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5">ترند <i class="fa-solid fa-circle-info text-[9px] text-[var(--text3)]"></i></div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">نمایش در بخش پرطرفدارها</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="is_trending" value="1" {{ old('is_trending', optional($duplicateFrom)->is_trending) ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>
  </div>

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

{{-- ═══════════════════ Card ۳ — رسانه نمایشی ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-images text-[var(--accent)]"></i> رسانه نمایشی</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">تصاویر و ویدیوهایی که محصول را به کاربر نمایش می‌دهند</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    {{-- Thumbnail --}}
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">تصویر کارت (Thumbnail) @if(!$duplicateFrom)<span class="text-[var(--red)] mr-0.5">*</span>@endif</label>
      @if($duplicateFrom?->thumbnail)
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-2.5 mb-1.5">
          <img src="{{ asset('storage/'.$duplicateFrom->thumbnail) }}" class="w-12 h-12 rounded-lg object-cover border border-[var(--b2)] shrink-0">
          <div class="text-[10.5px] text-[var(--text3)] leading-relaxed">تصویر محصول مبدا — اگر تصویر جدیدی انتخاب نکنید، همین تصویر برای محصول کپی‌شده استفاده می‌شود.</div>
        </div>
      @endif
      <div class="upload-zone border-2 border-dashed border-[var(--b2)] rounded-xl p-6 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors relative overflow-hidden w-full" id="thumb-zone" onclick="document.getElementById('thumbnail-file').click()">
        <img id="thumb-preview-img" class="hidden absolute inset-0 w-full h-full object-cover">
        <div id="thumb-empty-state">
          <i class="fa-solid fa-image text-2xl text-[var(--text3)] mb-2 block"></i>
          <div class="text-xs font-bold text-[var(--text2)]" id="thumb-title">{{ $duplicateFrom?->thumbnail ? 'انتخاب تصویر جدید (اختیاری)' : 'انتخاب تصویر Thumbnail' }}</div>
          <div class="text-[10px] text-[var(--text3)] mt-1">بکشید و رها کنید یا کلیک کنید</div>
        </div>
        <button type="button" class="hidden absolute top-1.5 left-1.5 w-6 h-6 rounded-md bg-[var(--bg)]/80 text-[var(--red)] text-[11px] items-center justify-center z-10" id="thumb-remove-btn" aria-label="حذف تصویر Thumbnail" onclick="event.stopPropagation(); removeUpload('thumbnail-file','thumb-preview-img','thumb-empty-state','thumb-remove-btn','thumb-title','انتخاب تصویر Thumbnail')"><i class="fa-solid fa-xmark"></i></button>
        <input type="file" id="thumbnail-file" name="thumbnail" accept="image/*" class="hidden" onchange="updateFileLabel(this,'thumb-title'); previewUpload(this,'thumb-preview-img','thumb-empty-state','thumb-remove-btn')">
      </div>
    </div>

    {{-- Cover --}}
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">تصویر کاور (Cover)</label>
      @if($duplicateFrom?->cover)
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-2.5 mb-1.5">
          <img src="{{ asset('storage/'.$duplicateFrom->cover) }}" class="w-12 h-12 rounded-lg object-cover border border-[var(--b2)] shrink-0">
          <div class="text-[10.5px] text-[var(--text3)] leading-relaxed">کاور محصول مبدا — در صورت عدم آپلود جدید، همین کپی می‌شود.</div>
        </div>
      @endif
      <div class="upload-zone border-2 border-dashed border-[var(--b2)] rounded-xl p-6 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors relative overflow-hidden w-full" id="cover-zone" onclick="document.getElementById('cover-file').click()">
        <img id="cover-preview-img" class="hidden absolute inset-0 w-full h-full object-cover">
        <div id="cover-empty-state">
          <i class="fa-solid fa-panorama text-2xl text-[var(--text3)] mb-2 block"></i>
          <div class="text-xs font-bold text-[var(--text2)]" id="cover-title">انتخاب تصویر کاور اصلی</div>
          <div class="text-[10px] text-[var(--text3)] mt-1">بکشید و رها کنید یا کلیک کنید</div>
        </div>
        <button type="button" class="hidden absolute top-1.5 left-1.5 w-6 h-6 rounded-md bg-[var(--bg)]/80 text-[var(--red)] text-[11px] items-center justify-center z-10" id="cover-remove-btn" aria-label="حذف تصویر کاور" onclick="event.stopPropagation(); removeUpload('cover-file','cover-preview-img','cover-empty-state','cover-remove-btn','cover-title','انتخاب تصویر کاور اصلی')"><i class="fa-solid fa-xmark"></i></button>
        <input type="file" id="cover-file" name="cover" accept="image/*" class="hidden" onchange="updateFileLabel(this,'cover-title'); previewUpload(this,'cover-preview-img','cover-empty-state','cover-remove-btn')">
      </div>
    </div>
  </div>

  {{-- Gallery — نمونه خروجی‌ها --}}
  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)]">نمونه خروجی‌ها (چندگانه)</label>
    @if($duplicateFrom && !empty($duplicateFrom->sample_outputs))
      <div class="flex items-center gap-1.5 flex-wrap bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-2.5 mb-1.5">
        @foreach($duplicateFrom->sample_outputs as $s)
          <img src="{{ asset('storage/'.$s) }}" class="w-10 h-10 rounded object-cover border border-[var(--b2)]">
        @endforeach
        <span class="text-[10.5px] text-[var(--text3)]">نمونه‌های محصول مبدا — با آپلود فایل جدید جایگزین می‌شوند.</span>
      </div>
    @endif
    <div class="upload-zone border-2 border-dashed border-[var(--b2)] rounded-xl p-5 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors w-full" id="samples-zone" onclick="document.getElementById('samples-file').click()">
      <i class="fa-solid fa-images text-xl text-[var(--text3)] mb-1 block"></i>
      <div class="text-xs text-[var(--text2)]" id="samples-title">انتخاب چندین تصویر به عنوان نمونه خروجی</div>
      <div class="text-[10px] text-[var(--text3)] mt-1">بکشید و رها کنید یا کلیک کنید — چند فایل هم‌زمان مجاز است</div>
      <div class="flex flex-wrap gap-1.5 justify-center mt-2.5" id="samples-preview-strip"></div>
      <div class="mt-2.5 space-y-1 text-right" id="samples-upload-queue" onclick="event.stopPropagation()"></div>
      <input type="file" id="samples-file" name="sample_outputs[]" multiple accept="image/*" class="hidden" onchange="updateFileLabel(this,'samples-title',true); previewMultiUpload(this,'samples-preview-strip'); renderUploadQueue(this,'samples-upload-queue')">
    </div>
  </div>

  {{-- نوع رسانه — Radio Card --}}
  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)]">نوع رسانه</label>
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
      <label class="text-xs font-semibold text-[var(--text2)]">لینک ویدیوی پیش‌نمایش</label>
      <input type="text" name="preview_video_url" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left" placeholder="https://..." value="{{ old('preview_video_url', optional($duplicateFrom)->preview_video_url) }}">
    </div>
  </div>

  {{-- ── آیکون محصول (فعال و ذخیره‌شونده) ── --}}
  <div class="border-t border-dashed border-[var(--b2)] pt-4 mt-4">
    <div class="flex flex-col gap-1.5 md:max-w-sm">
      <label class="text-xs font-semibold text-[var(--text2)]">آیکون محصول (Product Icon)</label>
      <div class="border-2 border-dashed border-[var(--b2)] rounded-xl p-4 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors w-full" onclick="document.getElementById('new-product-icon-file').click()">
        <i class="fa-solid fa-icons text-lg text-[var(--text3)] mb-1 block"></i>
        <div class="text-[11px] text-[var(--text2)]" id="new-icon-title">آپلود SVG یا PNG</div>
        <input type="file" id="new-product-icon-file" name="new_product_icon" accept=".svg,.png" class="hidden" onchange="updateFileLabel(this,'new-icon-title')">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-3.5">
      {{-- بند ۷: رنگ کارت محصول (Color Picker) — فقط UI --}}
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">رنگ کارت محصول {!! $newBadge !!}</label>
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <input type="color" name="new_card_color" value="#16594f" class="w-9 h-9 rounded-md border border-[var(--b1)] bg-transparent cursor-pointer shrink-0" oninput="document.getElementById('new-card-color-hex').value = this.value.toUpperCase()">
          <input type="text" id="new-card-color-hex" class="bg-transparent border-none outline-none text-xs text-[var(--text)] ltr text-left flex-1" value="#16594F" readonly>
        </div>
      </div>
      {{-- بند ۷: پیش‌نمایش حالت نمایش گالری (Grid/Slider/Carousel) — فقط UI --}}
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">پیش‌نمایش حالت گالری {!! $newBadge !!}</label>
        <div class="flex gap-2">
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--accent)] bg-[var(--accent)]/8 text-[var(--text)]" data-mode="grid" onclick="setGalleryPreviewMode('grid')"><i class="fa-solid fa-table-cells block mb-1"></i>Grid</button>
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text2)]" data-mode="slider" onclick="setGalleryPreviewMode('slider')"><i class="fa-solid fa-images block mb-1"></i>Slider</button>
          <button type="button" class="gallery-mode-btn flex-1 text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text2)]" data-mode="carousel" onclick="setGalleryPreviewMode('carousel')"><i class="fa-solid fa-rectangle-list block mb-1"></i>Carousel</button>
        </div>
        <input type="hidden" name="new_gallery_preview_mode" id="new-gallery-preview-mode" value="grid">
      </div>
    </div>
  </div>
</div>

{{-- ── بخش فازهای بعدی: خاموش تا وقتی نیاز شود ── --}}
<div class="future-block bg-[var(--s2)] border border-dashed border-[var(--b2)] rounded-xl p-5 mb-5">
  <label class="flex items-center justify-between gap-2 cursor-pointer">
    <div class="text-xs font-bold text-[var(--text2)] flex items-center gap-2"><i class="fa-solid fa-flask text-[var(--text3)]"></i> تنظیمات فاز بعد <span class="text-[10px] font-normal text-[var(--text3)]">(فعلاً نیازی نیست)</span></div>
    <span class="relative w-9 h-5 shrink-0 block">
      <input type="checkbox" class="sr-only peer" onchange="toggleFutureSection(this)">
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
    </span>
  </label>
  <div class="future-section hidden mt-4">
    <div class="flex flex-col gap-1.5 md:max-w-xs">
      <label class="text-xs font-semibold text-[var(--text2)]">ترتیب نمایش</label>
      <input type="number" name="new_display_order" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full" placeholder="مثلاً: 1" value="{{ old('new_display_order', optional($duplicateFrom)->new_display_order) }}">
      <div class="text-[10px] text-[var(--text3)]">ترتیب دلخواه نمایش محصول در لیست‌ها (برای فاز مرتب‌سازی دستی).</div>
    </div>
  </div>
</div>

<script>
/* تابع مشترک باز/بستن بخش‌های فاز بعد */
function toggleFutureSection(cb){ var b = cb.closest('.future-block'); if(b){ var s = b.querySelector('.future-section'); if(s) s.classList.toggle('hidden', !cb.checked); } }

/* ── ابزارهای کمکی عمومی مربوط به فایلهای آپلود ── */
function updateFileLabel(input, labelId, isMultiple = false) {
  const label = document.getElementById(labelId);
  if (!label) return;
  if (input.files && input.files.length > 0) {
    label.textContent = isMultiple ? input.files.length + ' فایل انتخاب شد' : input.files[0].name;
  }
}

/* ── کامپوننت مستقل: Preview / Replace / Remove برای Uploadهای تک‌فایلی ── */
function previewUpload(input, imgId, emptyStateId, removeBtnId) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function (e) {
    const img = document.getElementById(imgId);
    img.src = e.target.result;
    img.classList.remove('hidden');
    document.getElementById(emptyStateId).classList.add('hidden');
    const btn = document.getElementById(removeBtnId);
    btn.classList.remove('hidden');
    btn.classList.add('flex');
  };
  reader.readAsDataURL(input.files[0]);
}

function removeUpload(inputId, imgId, emptyStateId, removeBtnId, titleId, defaultTitle) {
  const input = document.getElementById(inputId);
  if(input) input.value = '';
  const img = document.getElementById(imgId);
  if(img) img.classList.add('hidden');
  const empty = document.getElementById(emptyStateId);
  if(empty) empty.classList.remove('hidden');
  const btn = document.getElementById(removeBtnId);
  if(btn) {
    btn.classList.add('hidden');
    btn.classList.remove('flex');
  }
  const title = document.getElementById(titleId);
  if(title) title.textContent = defaultTitle;
}

/* پیش‌نمایش چندفایلی برای گالری نمونه خروجی‌ها */
function previewMultiUpload(input, stripId) {
  const strip = document.getElementById(stripId);
  if(!strip) return;
  strip.innerHTML = '';
  if (!input.files) return;
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = function (e) {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'w-11 h-11 rounded-lg object-cover border border-[var(--b2)]';
      strip.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

/* NEW: Upload Queue System — نمایش صف فایل‌های در حال آپلود برای Uploadهای چندگانه */
function renderUploadQueue(input, queueId) {
  const queue = document.getElementById(queueId);
  if (!queue) return;
  queue.innerHTML = '';
  if (!input.files || !input.files.length) return;
  Array.from(input.files).forEach(file => {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 bg-[var(--bg)] border border-[var(--b1)] rounded-lg px-2 py-1.5 mt-1';
    row.innerHTML = `
      <i class="fa-solid fa-file-image text-[10px] text-[var(--text3)] shrink-0"></i>
      <span class="text-[10px] text-[var(--text2)] flex-1 truncate">${file.name}</span>
      <div class="w-16 h-1 bg-[var(--b1)] rounded-full overflow-hidden shrink-0"><div class="h-full bg-[var(--green)]" style="width:100%"></div></div>
      <span class="text-[9px] text-[var(--green)] shrink-0">آماده</span>
    `;
    queue.appendChild(row);
  });
}

/* ── Drag & Drop عمومی برای Uploadها ── */
function wireUploadZone(zoneId, inputId) {
  const zone = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;
  ['dragover', 'dragenter'].forEach(evt => zone.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation();
    zone.classList.add('border-[var(--accent)]');
  }));
  ['dragleave', 'drop'].forEach(evt => zone.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation();
    zone.classList.remove('border-[var(--accent)]');
  }));
  zone.addEventListener('drop', e => {
    if (e.dataTransfer.files && e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event('change'));
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  ['thumb-zone', 'cover-zone', 'samples-zone'].forEach(id => wireUploadZone(id, id.replace('-zone', '-file')));
});

/* ── Radio Card نوع رسانه: هایلایت کردن کارت انتخاب‌شده ── */
document.querySelectorAll('.media-type-card input[type="radio"]').forEach(radio => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('.media-type-card').forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
    if (radio.checked) radio.closest('.media-type-card').classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
  });
});

/* ── NEW: انتخاب حالت پیش‌نمایش گالری (فقط UI) ── */
function setGalleryPreviewMode(mode) {
  document.getElementById('new-gallery-preview-mode').value = mode;
  document.querySelectorAll('.gallery-mode-btn').forEach(btn => {
    const active = btn.dataset.mode === mode;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/8', active);
    btn.classList.toggle('text-[var(--text)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('bg-[var(--s1)]', !active);
    btn.classList.toggle('text-[var(--text2)]', !active);
  });
}
</script>