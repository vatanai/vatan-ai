{{-- پارشیال: گام اول — هویت و رسانه --}}

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-fingerprint text-[#a07af5]"></i> هویت محصول</div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">نام فارسی <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <input type="text" name="name_fa" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('name_fa') }}" placeholder="مثال: عکس حرفه‌ای لینکدین">
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">نام انگلیسی <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <input type="text" name="name_en" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" value="{{ old('name_en') }}" placeholder="LinkedIn Professional Headshot" oninput="autoSlug(this)">
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">آدرس URL (Slug) <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <input type="text" name="slug" id="slug-input" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" value="{{ old('slug') }}" placeholder="linkedin-professional-headshot">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">توضیح فارسی</label>
      <textarea name="description_fa" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] resize-y min-h-[90px] leading-relaxed" placeholder="توضیح کوتاهی از محصول برای کاربر...">{{ old('description_fa') }}</textarea>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">توضیح انگلیسی</label>
      <textarea name="description_en" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] resize-y min-h-[90px] leading-relaxed ltr text-left" placeholder="Short product description for users...">{{ old('description_en') }}</textarea>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">دسته‌بندی <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <select name="category" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-main" onchange="updateSubcat()">
        <option value="">انتخاب کنید</option>
        <option value="PEOPLE" {{ old('category') == 'PEOPLE' ? 'selected' : '' }}>PEOPLE — شخصی</option>
        <option value="BUSINESS" {{ old('category') == 'BUSINESS' ? 'selected' : '' }}>BUSINESS — کسب‌وکار</option>
        <option value="EVENTS" {{ old('category') == 'EVENTS' ? 'selected' : '' }}>EVENTS — مناسبت‌ها</option>
        <option value="FAMILY" {{ old('category') == 'FAMILY' ? 'selected' : '' }}>FAMILY — خانواده</option>
        <option value="AVATARS" {{ old('category') == 'AVATARS' ? 'selected' : '' }}>AVATARS — آواتار</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">زیردسته</label>
      <select name="subcategory" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-sub">
        <option value="">ابتدا دسته انتخاب کنید</option>
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">تگ‌های جستجو <span class="text-[10px] font-normal text-[#4d7a56] mr-1">Enter بزنید</span></label>
      <div class="bg-[#111116] border border-[#222230] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] focus-within:border-[#a07af5]" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
        <input type="text" id="tags-raw" class="bg-transparent border-none outline-none text-xs text-white flex-1 min-w-[80px] text-right" placeholder="تگ بنویسید..." onkeydown="addTag(event)">
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
    <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
      <div>
        <div class="text-[12.5px] font-semibold text-[#a8c4a8]">محصول ویژه</div>
        <div class="text-[11px] text-[#4d7a56] mt-0.5">صفحه اول</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="is_featured" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>
    <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
      <div>
        <div class="text-[12.5px] font-semibold text-[#a8c4a8]">برچسب «جدید»</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="is_new" value="1" checked class="sr-only peer">
        <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>
    <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
      <div>
        <div class="text-[12.5px] font-semibold text-[#a8c4a8]">ترند</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="is_trending" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>
  </div>
</div>

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-images text-[#a07af5]"></i> رسانه نمایشی</div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">تصویر کارت (Thumbnail) <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('thumbnail-file').click()">
        <i class="fa-solid fa-image text-2xl text-[#4d7a56] mb-2 block"></i>
        <div class="text-xs font-bold text-[#a8c4a8]" id="thumb-title">انتخاب تصویر Thumbnail</div>
        <input type="file" id="thumbnail-file" name="thumbnail" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'thumb-title')">
      </div>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">تصویر کاور (Cover)</label>
      <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('cover-file').click()">
        <i class="fa-solid fa-panorama text-2xl text-[#4d7a56] mb-2 block"></i>
        <div class="text-xs font-bold text-[#a8c4a8]" id="cover-title">انتخاب تصویر کاور اصلی</div>
        <input type="file" id="cover-file" name="cover" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'cover-title')">
      </div>
    </div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[#a8c4a8]">نمونه خروجی‌ها (چندگانه)</label>
    <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-5 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('samples-file').click()">
      <i class="fa-solid fa-images text-xl text-[#4d7a56] mb-1 block"></i>
      <div class="text-xs text-[#a8c4a8]" id="samples-title">انتخاب چندین تصویر به عنوان نمونه خروجی</div>
      <input type="file" id="samples-file" name="sample_outputs[]" multiple accept="image/*" class="hidden" onchange="updateFileLabel(this, 'samples-title', true)">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">نوع رسانه</label>
      <select name="media_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="photo">photo — عکس</option>
        <option value="video">video — ویدیو</option>
        <option value="both">both — هر دو</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">لینک ویدیوی پیش‌نمایش</label>
      <input type="text" name="preview_video_url" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white ltr text-left" placeholder="https://...">
    </div>
  </div>
</div>