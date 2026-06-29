@section('title', 'ثبت محصول جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="/admin/dashboard" class="text-[#a8c4a8] hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="/admin/products" class="text-[#a8c4a8] hover:text-white transition-colors">محصولات</a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="text-white font-semibold">ثبت محصول جدید</span>
      </div>
      <a href="/admin/products" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[#16161c] text-[#a8c4a8] border border-[#222230] transition-all hover:border-[#2e2e3e] hover:text-white no-underline">
        <i class="fa-solid fa-arrow-right text-[11px]"></i>
        بازگشت به لیست
      </a>
    </header>

    <main class="p-6 flex-1 pb-24">

      @if ($errors->any())
        <div class="bg-[#f05c5c]/10 border border-[#f05c5c] rounded-xl p-4 mb-6 text-right">
            <div class="text-[#f05c5c] font-bold text-sm mb-2.5">
                <i class="fa-solid fa-triangle-exclamation"></i> اصلاح خطاهای زیر برای ثبت محصول الزامی است:
            </div>
            <ul class="text-[#ff9191] text-xs pr-5 margin-0 list-disc space-y-1.5 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">ثبت محصول جدید</div>
        <div class="text-xs text-[#4d7a56]">محصول را در ۳ مرحله تنظیم کنید — هویت، هوش مصنوعی، و خروجی</div>
      </div>

      <div class="flex items-center gap-0 mb-7 bg-[#16161c] border border-[#222230] rounded-xl p-1.5">
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent bg-[#a07af5]/10 border-[#a07af5]/20" id="step-tab-1" onclick="goStep(1)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#a07af5] bg-[#a07af5]/15 text-[#a07af5] transition-all" id="step-num-1">۱</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#a07af5] mb-0.5">گام اول</div>
            <div class="step-title text-xs font-bold text-white">هویت و رسانه</div>
          </div>
        </div>
        <div class="w-6 shrink-0 h-px bg-[#222230]"></div>
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent" id="step-tab-2" onclick="goStep(2)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#2e2e3e] text-[#4d7a56] transition-all" id="step-num-2">۲</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#4d7a56] mb-0.5">گام دوم</div>
            <div class="step-title text-xs font-bold text-[#a8c4a8]">هوش مصنوعی</div>
          </div>
        </div>
        <div class="w-6 shrink-0 h-px bg-[#222230]"></div>
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent" id="step-tab-3" onclick="goStep(3)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#2e2e3e] text-[#4d7a56] transition-all" id="step-num-3">۳</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#4d7a56] mb-0.5">گام سوم</div>
            <div class="step-title text-xs font-bold text-[#a8c4a8]">خروجی و قیمت</div>
          </div>
        </div>
      </div>

      <form id="real-product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="block space-y-4" id="panel-1">

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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">دسته‌بندی <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="category" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-main" onchange="updateSubcat()">
                  <option value="">انتخاب کنید</option>
                  <option value="PEOPLE" {{ old('category') == 'PEOPLE' ? 'selected' : '' }}>PEOPLE — شخصی</option>
                  <option value="BUSINESS" {{ old('category') == 'BUSINESS' ? 'selected' : '' }}>BUSINESS — کسب‌وکار</option>
                  <option value="EVENTS" {{ old('category') == 'EVENTS' ? 'selected' : '' }}>EVENTS — مناسبت‌ها</option>
                  <option value="FAMILY" {{ old('category') == 'FAMILY' ? 'selected' : '' }}>FAMILY — خانواده</option>
                  <option value="KIDS" {{ old('category') == 'KIDS' ? 'selected' : '' }}>KIDS — کودکان</option>
                  <option value="PETS" {{ old('category') == 'PETS' ? 'selected' : '' }}>PETS — حیوانات</option>
                  <option value="ENTERTAINMENT" {{ old('category') == 'ENTERTAINMENT' ? 'selected' : '' }}>ENTERTAINMENT — سرگرمی</option>
                  <option value="PRODUCTS" {{ old('category') == 'PRODUCTS' ? 'selected' : '' }}>PRODUCTS — محصولات</option>
                  <option value="AVATARS" {{ old('category') == 'AVATARS' ? 'selected' : '' }}>AVATARS — آواتار</option>
                  <option value="VIDEOS" {{ old('category') == 'VIDEOS' ? 'selected' : '' }}>VIDEOS — ویدیو</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">زیردسته</label>
                <select name="subcategory" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-sub">
                  <option value="">ابتدا دسته انتخاب کنید</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">وضعیت <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="status" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس (draft)</option>
                  <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال (active)</option>
                  <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>غیرفعال (inactive)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تگ‌های جستجو <span class="text-[10px] font-normal text-[#4d7a56] mr-1">Enter بزنید تا اضافه شود</span></label>
                <div class="bg-[#111116] border border-[#222230] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] focus-within:border-[#a07af5]" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
                  <input type="text" id="tags-raw" class="bg-transparent border-none outline-none text-xs text-white flex-1 min-w-[80px] text-right" placeholder="تگ بنویسید و Enter بزنید..." onkeydown="addTag(event)">
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">محصول ویژه</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">نمایش در صفحه اول</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">برچسب «جدید»</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">is_new — روی کارت نمایش</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_new" value="1" {{ old('is_new', true) ? 'checked' : '' }} class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">ترند</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">is_trending</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }} class="sr-only peer">
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
                <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer transition-all bg-[#111116] hover:border-[#a07af5] hover:bg-[#a07af5]/5" onclick="document.getElementById('thumbnail-file').click()">
                  <i class="fa-solid fa-image text-2xl text-[#4d7a56] mb-2 block"></i>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8] mb-0.5" id="thumb-title">کلیک کنید یا بکشید اینجا</div>
                  <div class="text-[11px] text-[#4d7a56]">PNG, JPG — حداقل ۶۰۰×۶۰۰px</div>
                  <input type="file" id="thumbnail-file" name="thumbnail" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'thumb-title')">
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تصویر Cover</label>
                <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer transition-all bg-[#111116] hover:border-[#a07af5] hover:bg-[#a07af5]/5" onclick="document.getElementById('cover-file').click()">
                  <i class="fa-solid fa-panorama text-2xl text-[#4d7a56] mb-2 block"></i>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8] mb-0.5" id="cover-title">کلیک کنید یا بکشید اینجا</div>
                  <div class="text-[11px] text-[#4d7a56]">PNG, JPG — ۱۴۰۰×۶۰۰px</div>
                  <input type="file" id="cover-file" name="cover" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'cover-title')">
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">نمونه خروجی‌ها <span class="text-[10px] font-normal text-[#4d7a56] mr-1">حداکثر ۱۰ تصویر</span></label>
              <div class="grid grid-cols-5 gap-2" onclick="document.getElementById('samples-file').click()">
                <div class="aspect-square rounded-lg border-2 border-dashed border-[#2e2e3e] flex items-center justify-center cursor-pointer transition-all bg-[#111116] text-lg text-[#4d7a56] hover:border-[#a07af5] hover:text-[#a07af5]"><i class="fa-solid fa-plus"></i></div>
                <div class="aspect-square rounded-lg border-2 border-dashed border-[#2e2e3e] flex items-center justify-center cursor-pointer transition-all bg-[#111116] text-lg text-[#4d7a56] hover:border-[#a07af5] hover:text-[#a07af5]"><i class="fa-solid fa-plus"></i></div>
                <div class="aspect-square rounded-lg border-2 border-dashed border-[#2e2e3e] flex items-center justify-center cursor-pointer transition-all bg-[#111116] text-lg text-[#4d7a56] hover:border-[#a07af5] hover:text-[#a07af5]"><i class="fa-solid fa-plus"></i></div>
                <div class="aspect-square rounded-lg border-2 border-dashed border-[#2e2e3e] flex items-center justify-center cursor-pointer transition-all bg-[#111116] text-lg text-[#4d7a56] hover:border-[#a07af5] hover:text-[#a07af5]"><i class="fa-solid fa-plus"></i></div>
                <div class="aspect-square rounded-lg border-2 border-dashed border-[#2e2e3e] flex items-center justify-center cursor-pointer transition-all bg-[#111116] text-lg text-[#4d7a56] hover:border-[#a07af5] hover:text-[#a07af5]"><i class="fa-solid fa-plus"></i></div>
                <input type="file" id="samples-file" name="sample_outputs[]" multiple accept="image/*" class="hidden" onchange="alert('فایل‌ها انتخاب شدند')">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نوع رسانه</label>
                <select name="media_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="photo" {{ old('media_type') == 'photo' ? 'selected' : '' }}>photo — عکس</option>
                  <option value="video" {{ old('media_type') == 'video' ? 'selected' : '' }}>video — ویدیو</option>
                  <option value="both" {{ old('media_type') == 'both' ? 'selected' : '' }}>both — هر دو</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">لینک ویدیوی پیش‌نمایش <span class="text-[10px] font-normal text-[#4d7a56] mr-1">اختیاری</span></label>
                <input type="text" name="preview_video_url" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" value="{{ old('preview_video_url') }}" placeholder="https://...">
              </div>
            </div>
          </div>

        </div><div class="hidden space-y-4" id="panel-2">

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-microchip text-[#a07af5]"></i> پایپ‌لاین هوش مصنوعی</div>

            <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">مدل اصلی (Primary)</div>
            <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5 flex items-center gap-3 transition-colors mb-4 hover:border-[#2e2e3e]">
              <i class="fa-solid fa-grip-vertical text-[#4d7a56] cursor-grab text-xs shrink-0"></i>
              <div class="flex-1">
                <select name="primary_model" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] mb-2">
                  <option value="black-forest-labs/flux-1.1-pro">black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
                  <option value="black-forest-labs/flux-kontext-pro">black-forest-labs/flux-kontext-pro — FLUX Kontext Pro</option>
                  <option value="openai/gpt-image-1">openai/gpt-image-1 — GPT Image 1</option>
                  <option value="stability-ai/stable-diffusion-3.5">stability-ai/stable-diffusion-3.5 — SD 3.5</option>
                  <option value="ideogram-ai/ideogram-v2">ideogram-ai/ideogram-v2 — Ideogram V2</option>
                  <option value="recraft-ai/recraft-v3">recraft-ai/recraft-v3 — Recraft V3</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                  <input type="number" name="timeout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" placeholder="timeout (ثانیه)" value="{{ old('timeout', 60) }}">
                  <select name="pipeline_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                    <option value="image_generation" {{ old('pipeline_type') == 'image_generation' ? 'selected' : '' }}>image_generation</option>
                    <option value="image_editing" {{ old('pipeline_type') == 'image_editing' ? 'selected' : '' }}>image_editing</option>
                    <option value="text_generation" {{ old('pipeline_type') == 'text_generation' ? 'selected' : '' }}>text_generation</option>
                  </select>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20 whitespace-nowrap"><i class="fa-solid fa-star text-[9px]"></i>&nbsp;Primary</span>
            </div>

            <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">Fallback ها <span class="font-normal text-[10px]">(به ترتیب اجرا)</span></div>
            <div id="fallback-list" class="space-y-2">
              <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5 flex items-center gap-3 transition-colors mb-2 hover:border-[#2e2e3e]" id="fb-1">
                <i class="fa-solid fa-grip-vertical text-[#4d7a56] cursor-grab text-xs shrink-0"></i>
                <div class="flex-1">
                  <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                    <option value="stability-ai/stable-diffusion-3.5">stability-ai/stable-diffusion-3.5 — SD 3.5</option>
                    <option value="black-forest-labs/flux-1.1-pro">black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
                    <option value="ideogram-ai/ideogram-v2">ideogram-ai/ideogram-v2 — Ideogram V2</option>
                    <option value="recraft-ai/recraft-v3">recraft-ai/recraft-v3 — Recraft V3</option>
                  </select>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/20 whitespace-nowrap">Fallback ۱</span>
                <button type="button" class="bg-none border-none cursor-pointer text-[#4d7a56] text-xs px-2 py-1 rounded transition-all hover:bg-[#f05c5c]/10 hover:text-[#f05c5c] whitespace-nowrap" onclick="removeFallback('fb-1')"><i class="fa-solid fa-xmark"></i> حذف</button>
              </div>
            </div>
            <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold cursor-pointer transition-all hover:border-[#a07af5] hover:text-[#a07af5] hover:bg-[#a07af5]/5 mt-3" onclick="addFallback()">
              <i class="fa-solid fa-plus"></i> افزودن Fallback
            </button>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-terminal text-[#a07af5]"></i> تنظیمات پرامپت</div>

            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">Prompt Template <span class="text-[#f05c5c] mr-0.5">*</span></label>
              <textarea name="prompt_template" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] min-h-[110px] font-mono ltr text-left leading-relaxed" id="prompt-template" placeholder="A professional portrait of {gender} named {name}, wearing {clothing_style}, {background}, photorealistic, 8k, sharp focus">{{ old('prompt_template') }}</textarea>
              <div class="mt-1.5">
                <div class="text-[10px] color-[#4d7a56] mb-1.5">متغیرهای موجود — کلیک کنید تا درج شود:</div>
                <div class="flex flex-wrap gap-1.5" id="var-chips">
                  <span class="text-[10.5px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 text-[#a8c4a8] cursor-pointer font-mono transition-all hover:border-[#a07af5] hover:text-[#a07af5]" onclick="insertVar('{name}')">&#123;name&#125;</span>
                  <span class="text-[10.5px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 text-[#a8c4a8] cursor-pointer font-mono transition-all hover:border-[#a07af5] hover:text-[#a07af5]" onclick="insertVar('{gender}')">&#123;gender&#125;</span>
                  <span class="text-[10.5px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 text-[#a8c4a8] cursor-pointer font-mono transition-all hover:border-[#a07af5] hover:text-[#a07af5]" onclick="insertVar('{clothing_style}')">&#123;clothing_style&#125;</span>
                  <span class="text-[10.5px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 text-[#a8c4a8] cursor-pointer font-mono transition-all hover:border-[#a07af5] hover:text-[#a07af5]" onclick="insertVar('{background}')">&#123;background&#125;</span>
                  <span class="text-[10.5px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 text-[#a8c4a8] cursor-pointer font-mono transition-all hover:border-[#a07af5] hover:text-[#a07af5]" onclick="insertVar('{birth_date}')">&#123;birth_date&#125;</span>
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">Negative Prompt <span class="text-[10px] font-normal text-[#4d7a56] mr-1">اختیاری</span></label>
              <textarea name="negative_prompt" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] min-h-[60px] font-mono ltr text-left leading-relaxed" placeholder="blurry, low quality, distorted face, cartoon, watermark">{{ old('negative_prompt') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">نمایش پرامپت به کاربر</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">show_prompt_to_user</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="show_prompt_to_user" value="1" {{ old('show_prompt_to_user') ? 'checked' : '' }} class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">Face Swap</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">face_swap_enabled</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="face_swap_enabled" value="1" {{ old('face_swap_enabled') ? 'checked' : '' }} class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">پایپ‌لاین چند مرحله‌ای</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">multi_step_pipeline</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="multi_step_pipeline" value="1" {{ old('multi_step_pipeline') ? 'checked' : '' }} class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-table-list text-[#a07af5]"></i> سازنده فیلدهای ورودی (Input Schema)</div>

            <div class="grid grid-cols-[24px_1fr_1fr_160px_76px_34px] gap-2.5 px-1 pb-1.5 border-b border-[#222230] mb-2 hidden md:grid">
              <div></div>
              <div class="text-[10px] font-bold text-[#4d7a56] tracking-wider uppercase">field_id</div>
              <div class="text-[10px] font-bold text-[#4d7a56] tracking-wider uppercase">برچسب فارسی</div>
              <div class="text-[10px] font-bold text-[#4d7a56] tracking-wider uppercase">نوع</div>
              <div class="text-[10px] font-bold text-[#4d7a56] tracking-wider uppercase text-center">اجباری</div>
              <div></div>
            </div>

            <div id="input-fields-list" class="space-y-2">
              <div class="bg-[#111116] border border-[#222230] rounded-xl p-3 mb-2 grid grid-cols-1 md:grid-cols-[24px_1fr_1fr_160px_76px_34px] gap-2.5 items-center transition-colors hover:border-[#2e2e3e]" id="field-row-1">
                <i class="fa-solid fa-grip-vertical text-[#4d7a56] text-[11px] cursor-grab hidden md:block"></i>
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" placeholder="field_id" value="user_photo">
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" placeholder="برچسب" value="عکس شما">
                <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option selected>image_upload</option><option>video_upload</option><option>text</option><option>textarea</option><option>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
                <div class="flex justify-center"><button type="button" class="w-7 h-7 rounded-md border-2 border-[#0BBF53] bg-[#0BBF53] cursor-pointer flex items-center justify-center text-[11px] transition-all text-white" onclick="toggleReq(this)"><i class="fa-solid fa-check"></i></button></div>
                <button type="button" class="w-7 h-7 rounded-md border border-[#222230] bg-none cursor-pointer flex items-center justify-center text-[11px] text-[#4d7a56] transition-all hover:border-[#f05c5c] hover:text-[#f05c5c] hover:bg-[#f05c5c]/5" onclick="removeField('field-row-1')"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="bg-[#111116] border border-[#222230] rounded-xl p-3 mb-2 grid grid-cols-1 md:grid-cols-[24px_1fr_1fr_160px_76px_34px] gap-2.5 items-center transition-colors hover:border-[#2e2e3e]" id="field-row-2">
                <i class="fa-solid fa-grip-vertical text-[#4d7a56] text-[11px] cursor-grab hidden md:block"></i>
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" placeholder="field_id" value="name">
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" placeholder="برچسب" value="نام">
                <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option>image_upload</option><option>video_upload</option><option selected>text</option><option>textarea</option><option>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
                <div class="flex justify-center"><button type="button" class="w-7 h-7 rounded-md border-2 border-[#2e2e3e] bg-none cursor-pointer flex items-center justify-center text-[11px] transition-all text-[#4d7a56]" onclick="toggleReq(this)"><i class="fa-solid fa-minus"></i></button></div>
                <button type="button" class="w-7 h-7 rounded-md border border-[#222230] bg-none cursor-pointer flex items-center justify-center text-[11px] text-[#4d7a56] transition-all hover:border-[#f05c5c] hover:text-[#f05c5c] hover:bg-[#f05c5c]/5" onclick="removeField('field-row-2')"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="bg-[#111116] border border-[#222230] rounded-xl p-3 mb-2 grid grid-cols-1 md:grid-cols-[24px_1fr_1fr_160px_76px_34px] gap-2.5 items-center transition-colors hover:border-[#2e2e3e]" id="field-row-3">
                <i class="fa-solid fa-grip-vertical text-[#4d7a56] text-[11px] cursor-grab hidden md:block"></i>
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" placeholder="field_id" value="clothing_style">
                <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" placeholder="برچسب" value="استایل لباس">
                <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option>image_upload</option><option>video_upload</option><option>text</option><option>textarea</option><option selected>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
                <div class="flex justify-center"><button type="button" class="w-7 h-7 rounded-md border-2 border-[#0BBF53] bg-[#0BBF53] cursor-pointer flex items-center justify-center text-[11px] transition-all text-white" onclick="toggleReq(this)"><i class="fa-solid fa-check"></i></button></div>
                <button type="button" class="w-7 h-7 rounded-md border border-[#222230] bg-none cursor-pointer flex items-center justify-center text-[11px] text-[#4d7a56] transition-all hover:border-[#f05c5c] hover:text-[#f05c5c] hover:bg-[#f05c5c]/5" onclick="removeField('field-row-3')"><i class="fa-solid fa-trash"></i></button>
              </div>
            </div>

            <div class="mt-3.5">
              <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold cursor-pointer transition-all hover:border-[#a07af5] hover:text-[#a07af5] hover:bg-[#a07af5]/5" onclick="addInputField()">
                <i class="fa-solid fa-plus"></i> افزودن فیلد جدید
              </button>
            </div>
          </div>

        </div><div class="hidden space-y-4" id="panel-3">

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-file-export text-[#a07af5]"></i> تنظیمات خروجی (Output Config)</div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نوع خروجی <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="output_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="image">image — عکس</option>
                  <option value="video">video — ویدیو</option>
                  <option value="image+video">image+video — هر دو</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">فرمت</label>
                <select name="output_format" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="jpg">jpg</option><option value="png">png</option><option value="webp">webp</option><option value="mp4">mp4</option><option value="webm">webm</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تعداد خروجی</label>
                <input type="number" name="output_count" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('output_count', 1) }}" min="1" max="10">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">رزولوشن</label>
                <select name="resolution" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="512×512">512×512</option><option value="768×768">768×768</option><option value="1024×1024" selected>1024×1024</option><option value="1080×1920">1080×1920</option><option value="1920×1080">1920×1080</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نسبت ابعاد</label>
                <select name="aspect_ratio" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="1:1" selected>1:1 — مربع</option><option value="9:16">9:16 — عمودی</option><option value="16:9">16:9 — افقی</option><option value="4:5">4:5 — اینستاگرام</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">روش تحویل</label>
                <select name="delivery_method" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="instant">instant — آنی</option><option value="queued">queued — صف انتظار</option></select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">زمان تخمینی (ثانیه)</label>
                <input type="number" name="estimated_time" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('estimated_time', 30) }}">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">واترمارک</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">watermark_enabled</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="watermark_enabled" value="1" checked class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">موقعیت واترمارک</label>
                <select name="watermark_position" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="corner">corner — گوشه</option><option value="center">center — وسط</option><option value="none">none — بدون</option></select>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-coins text-[#a07af5]"></i> قیمت‌گذاری (Pricing)</div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">مدل قیمت‌گذاری <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="pricing_model" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" onchange="toggleCreditCost(this)">
                  <option value="per_credit">per_credit — کردیتی</option>
                  <option value="free">free — رایگان</option>
                  <option value="subscription_only">subscription_only — اشتراک</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5" id="credit-cost-wrap">
                <label class="text-xs font-semibold text-[#a8c4a8]">هزینه (کردیت) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="number" name="credit_cost" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('credit_cost', 5) }}" min="0">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">سطح قیمتی</label>
                <select name="price_tier" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
                  <option value="basic">basic — پایه</option><option value="standard" selected>standard — استاندارد</option><option value="premium">premium — پریمیوم</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">درصد تخفیف <span class="text-[10px] font-normal text-[#4d7a56] mr-1">۰ = بدون تخفیف</span></label>
                <input type="number" name="discount_percentage" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('discount_percentage', 0) }}" min="0" max="100">
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">رایگان (is_free)</div>
                  <div class="text-[11px] text-[#4d7a56] mt-0.5">بدون نیاز به کردیت</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_free" value="1" class="sr-only peer">
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-palette text-[#a07af5]"></i> تنظیمات نمایش (Display Config)</div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">حالت نمایش</label>
                <select name="display_mode" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="card" selected>card — کارت</option><option value="featured">featured — ویژه</option><option value="minimal">simple — ساده</option></select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">شکل کارت</label>
                <select name="card_shape" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="portrait" selected>portrait — عمودی</option><option value="landscape">landscape — افقی</option><option value="square">square — مربع</option></select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">چیدمان گالری</label>
                <select name="gallery_layout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="grid" selected>grid — شبکه</option><option value="masonry">masonry — آبشاری</option><option value="carousel">slider — اسلایدر</option></select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">برچسب کارت <span class="text-[10px] font-normal text-[#4d7a56] mr-1">مثلاً: پرفروش، ویژه</span></label>
                <input type="text" name="card_label" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('card_label') }}" placeholder="مثال: پرفروش">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">پلتفرم</label>
                <select name="platform" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]"><option value="web">web</option><option value="mobile">mobile</option><option value="both" selected>both — هر دو</option></select>
              </div>
            </div>

            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">رنگ accent محصول</label>
              <div class="flex gap-2 flex-wrap mb-2">
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all border-white scale-110" style="background:#a07af5;" data-color="#a07af5" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#0BBF53;" data-color="#0BBF53" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#f05c5c;" data-color="#f05c5c" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#f5923a;" data-color="#f5923a" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#3b82f6;" data-color="#3b82f6" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#ec4899;" data-color="#ec4899" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#14b8a6;" data-color="#14b8a6" onclick="pickColor(this)"></div>
                <div class="w-7 h-7 rounded-lg cursor-pointer border-2 border-transparent transition-all hover:scale-110" style="background:#eab308;" data-color="#eab308" onclick="pickColor(this)"></div>
              </div>
              <input type="text" name="accent_color" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left max-width-[140px]" id="color-val" value="#a07af5">
            </div>

            <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg max-w-[340px]">
              <div>
                <div class="text-[12.5px] font-semibold text-[#a8c4a8]">نمایش Before/After</div>
                <div class="text-[11px] text-[#4d7a56] mt-0.5">show_before_after slider</div>
              </div>
              <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                <input type="checkbox" name="show_before_after" value="1" checked class="sr-only peer">
                <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
              </label>
            </div>
          </div>

        </div></form>

    </main>

    <div class="sticky bottom-0 bg-[#111116] border-t border-[#222230] p-4 flex items-center justify-between z-40">
      <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#16161c] text-[#a8c4a8] border border-[#222230] hover:border-[#2e2e3e] hover:text-white transition-all" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> مرحله قبل
      </button>
      <div class="text-xs text-[#4d7a56]">
        مرحله <strong class="text-white" id="step-label-num">۱</strong> از ۳
      </div>
      <div class="flex gap-2">
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#16161c] text-[#a8c4a8] border border-[#222230] hover:border-[#2e2e3e] hover:text-white transition-all" onclick="saveDraft(event)">
          <i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-all" id="btn-next" onclick="nextStep()">
          مرحله بعد <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#0BBF53] text-white hover:bg-[#08a443] transition-all" id="btn-submit" onclick="submitProduct()" style="display:none;">
          <i class="fa-solid fa-check"></i> ثبت نهایی محصول
        </button>
      </div>
    </div>

  </div></div>
@endsection

@section('scripts')
<script>
/* ── STEPPER CONTROLLER ── */
let cur = 1;
const pNums = ['۱','۲','۳'];

function goStep(n) {
  for (let i = 1; i <= 3; i++) {
    // مدیریت نمایش مستقیم پنل‌ها با ویژگی بومی استایل
    document.getElementById('panel-' + i).style.display = (i === n) ? 'block' : 'none';
    
    const tab = document.getElementById('step-tab-' + i);
    const num = document.getElementById('step-num-' + i);
    const label = tab.querySelector('.step-label');
    const title = tab.querySelector('.step-title');
    
    // بازنشانی کلاس‌های پایه تب و شمارشگر گام‌ها مبتنی بر تیلویند
    tab.className = "flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent";
    num.className = "w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 transition-all";
    
    if (i === n) {
      // وضعیت گام فعال
      tab.classList.add('bg-[#a07af5]/10', 'border-[#a07af5]/20');
      num.classList.add('border-[#a07af5]', 'bg-[#a07af5]/15', 'text-[#a07af5]');
      num.textContent = pNums[i - 1];
      if(label) label.className = "step-label text-[11px] text-[#a07af5] mb-0.5";
      if(title) title.className = "step-title text-xs font-bold text-white";
    } else if (i < n) {
      // وضعیت گام انجام‌شده
      num.classList.add('border-[#0BBF53]', 'bg-[#0BBF53]/12', 'text-[#0BBF53]');
      num.innerHTML = '<i class="fa-solid fa-check" style="font-size:10px;"></i>';
      if(label) label.className = "step-label text-[11px] text-[#4d7a56] mb-0.5";
      if(title) title.className = "step-title text-xs font-bold text-[#a8c4a8]";
    } else {
      // وضعیت گام‌های آینده
      num.classList.add('border-[#2e2e3e]', 'text-[#4d7a56]');
      num.textContent = pNums[i - 1];
      if(label) label.className = "step-label text-[11px] text-[#4d7a56] mb-0.5";
      if(title) title.className = "step-title text-xs font-bold text-[#a8c4a8]";
    }
  }
  cur = n;
  document.getElementById('btn-prev').style.display   = n > 1 ? 'inline-flex' : 'none';
  document.getElementById('btn-next').style.display   = n < 3 ? 'inline-flex' : 'none';
  document.getElementById('btn-submit').style.display = n === 3 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = pNums[n - 1];
  window.scrollTo({top: 0, behavior: 'smooth'});
}
function nextStep() { if (cur < 3) goStep(cur + 1); }
function prevStep() { if (cur > 1) goStep(cur - 1); }

/* ── TAGS GENERATOR ── */
function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;
  const chip = document.createElement('span');
  // اعمال دیزاین تگ‌ها با کلاس‌های کمکی تیلویند
  chip.className = 'inline-flex items-center gap-1 bg-[#a07af5]/12 border border-[#a07af5]/25 rounded px-2 py-0.5 text-xs text-[#a07af5]';
  chip.innerHTML = v + '<button type="button" class="bg-none border-none cursor-pointer text-[#4d7a56] hover:text-[#f05c5c] font-bold p-0 line-none mr-1" onclick="this.parentElement.remove()">×</button>';
  document.getElementById('tags-wrap').insertBefore(chip, inp);
  inp.value = '';
}

/* ── FILE INPUT LABELS ── */
function updateFileLabel(input, id) {
  if(input.files && input.files[0]) {
    document.getElementById(id).textContent = "فایل انتخاب شد: " + input.files[0].name;
  }
}

/* ── AUTO URL SLUG ── */
function autoSlug(el) {
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

/* ── SUBCATEGORY DATA MAP ── */
const subcats = {
  PEOPLE:['Professional','Fashion','Lifestyle','Fitness','Beauty'],
  BUSINESS:['Real Estate','Medical','Lawyer','Coach','Education','Entrepreneur'],
  EVENTS:['Birthday','Wedding','Graduation','Valentine','Nowruz','Yalda','Eid'],
  FAMILY:['خانواده کامل','والدین','نوزاد'],
  KIDS:['کودک','نوجوان'],
  PETS:['سگ','گربه','سایر'],
  ENTERTAINMENT:['Anime / Manga','Disney / Pixar','Superhero / Fantasy'],
  PRODUCTS:['محصول تجاری','فود','فشن'],
  AVATARS:['واقع‌گرایانه','کارتونی','سه‌بعدی'],
  VIDEOS:['Personal','Business','Social Media','Kids','AI Tools'],
};
function updateSubcat() {
  const cat = document.getElementById('cat-main').value;
  const sel = document.getElementById('cat-sub');
  sel.innerHTML = '<option value="">انتخاب کنید</option>';
  (subcats[cat] || []).forEach(s => {
    const o = document.createElement('option');
    o.value = s; o.textContent = s; sel.appendChild(o);
  });
}

/* ── DYNAMIC FALLBACK MODELS (TAILWIND UTILITIES) ── */
let fbIdx = 1;
const fbLabels = ['۱','۲','۳','۴','۵'];
function addFallback() {
  fbIdx++;
  const id = 'fb-' + fbIdx;
  const el = document.createElement('div');
  el.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3.5 flex items-center gap-3 transition-colors mb-2 hover:border-[#2e2e3e]';
  el.id = id;
  el.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[#4d7a56] cursor-grab text-xs shrink-0"></i>
    <div class="flex-1">
      <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">
        <option value="recraft-ai/recraft-v3">recraft-ai/recraft-v3 — Recraft V3</option>
        <option value="ideogram-ai/ideogram-v2">ideogram-ai/ideogram-v2 — Ideogram V2</option>
        <option value="stability-ai/stable-diffusion-3.5">stability-ai/stable-diffusion-3.5 — SD 3.5</option>
        <option value="black-forest-labs/flux-1.1-pro">black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
      </select>
    </div>
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/20 whitespace-nowrap">Fallback ${fbLabels[fbIdx-1] || fbIdx}</span>
    <button type="button" class="bg-none border-none cursor-pointer text-[#4d7a56] text-xs px-2 py-1 rounded transition-all hover:bg-[#f05c5c]/10 hover:text-[#f05c5c] whitespace-nowrap" onclick="removeFallback('${id}')"><i class="fa-solid fa-xmark"></i> حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(el);
}
function removeFallback(id) { const el = document.getElementById(id); if(el) el.remove(); }

/* ── INPUT SCHEMA BUILDER (TAILWIND UTILITIES) ── */
let fieldIdx = 3;
const typeOptions = ['image_upload','video_upload','text','textarea','select','multi_select','date','number','url','color_picker','toggle','file_upload'];
function addInputField() {
  fieldIdx++;
  const id = 'field-row-' + fieldIdx;
  const opts = typeOptions.map(t => `<option value="${t}">${t}</option>`).join('');
  const el = document.createElement('div');
  el.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3 mb-2 grid grid-cols-1 md:grid-cols-[24px_1fr_1fr_160px_76px_34px] gap-2.5 items-center transition-colors hover:border-[#2e2e3e]';
  el.id = id;
  el.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[#4d7a56] text-[11px] cursor-grab hidden md:block"></i>
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" placeholder="field_id">
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" placeholder="برچسب فارسی">
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]">${opts}</select>
    <div class="flex justify-center"><button type="button" class="w-7 h-7 rounded-md border-2 border-[#2e2e3e] bg-none cursor-pointer flex items-center justify-center text-[11px] transition-all text-[#4d7a56]" onclick="toggleReq(this)"><i class="fa-solid fa-minus"></i></button></div>
    <button type="button" class="w-7 h-7 rounded-md border border-[#222230] bg-none cursor-pointer flex items-center justify-center text-[11px] text-[#4d7a56] transition-all hover:border-[#f05c5c] hover:text-[#f05c5c] hover:bg-[#f05c5c]/5" onclick="removeField('${id}')"><i class="fa-solid fa-trash"></i></button>
  `;
  document.getElementById('input-fields-list').appendChild(el);
}
function removeField(id) { const el = document.getElementById(id); if(el) el.remove(); }

// تغییر وضعیت دکمه اجباری/اختیاری سازنده فرم با تغییر پویای کلاس‌های تیلویند
function toggleReq(btn) {
  btn.classList.toggle('bg-[#0BBF53]');
  btn.classList.toggle('border-[#0BBF53]');
  btn.classList.toggle('text-white');
  btn.classList.toggle('border-[#2e2e3e]');
  btn.classList.toggle('text-[#4d7a56]');
  
  const isOn = btn.classList.contains('bg-[#0BBF53]');
  btn.innerHTML = isOn ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-minus"></i>';
}

/* ── PROMPT INSERT VARIABLES ── */
function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

/* ── COLOR SWATCHES PICKER ── */
function pickColor(el) {
  document.querySelectorAll('[data-color]').forEach(s => s.classList.remove('border-white', 'scale-110'));
  el.classList.add('border-white', 'scale-110');
  document.getElementById('color-val').value = el.dataset.color;
}

/* ── CREDIT COST DISPLAY TOGGLE ── */
function toggleCreditCost(sel) {
  const w = document.getElementById('credit-cost-wrap');
  if(sel.value === 'per_credit') {
      w.style.opacity = '1'; w.style.pointerEvents = '';
  } else {
      w.style.opacity = '0.35'; w.style.pointerEvents = 'none';
  }
}

/* ── SAVE ACTION ── */
function saveDraft(e) {
  const btn = e.currentTarget;
  const orig = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-check"></i> ذخیره شد';
  btn.style.color = '#0BBF53';
  setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
}

/* ── SUBMIT DATA INTERCEPTOR ── */
function submitProduct() {
  if (confirm('محصول به‌صورت نهایی ثبت شود؟')) {
    const form = document.getElementById('real-product-form');

    // ۱. استخراج تگ‌های متنی جستجو
    const tags = [];
    document.querySelectorAll('#tags-wrap span').forEach(chip => {
       const text = chip.textContent.replace('×', '').trim();
       if(text) tags.push(text);
    });
    tags.forEach((tag, idx) => { form.appendChild(createHiddenInput(`tags[${idx}]`, tag)); });

    // ۲. استخراج لیست به ترتیب مدل‌های Fallback
    const fallbacks = [];
    document.querySelectorAll('#fallback-list select').forEach(select => { fallbacks.push(select.value); });
    fallbacks.forEach((fb, idx) => { form.appendChild(createHiddenInput(`fallback_models[${idx}]`, fb)); });

    // ۳. ساختاردهی آرایه فیلدهای پویای ساخته‌شده (Input Schema Builder)
    document.querySelectorAll('#input-fields-list [id^="field-row-"]').forEach((row, idx) => {
       const inputs = row.querySelectorAll('input, select');
       const fieldId = inputs[0].value.trim();
       const labelFa = inputs[1].value.trim();
       const type = inputs[2].value;
       const isRequired = row.querySelector('button[onclick^="toggleReq"]').classList.contains('bg-[#0BBF53]') ? 1 : 0;

       if(fieldId) {
           form.appendChild(createHiddenInput(`input_schema[${idx}][field_id]`, fieldId));
           form.appendChild(createHiddenInput(`input_schema[${idx}][label_fa]`, labelFa));
           form.appendChild(createHiddenInput(`input_schema[${idx}][type]`, type));
           form.appendChild(createHiddenInput(`input_schema[${idx}][required]`, isRequired));
       }
    });

    form.submit();
  }
}

function createHiddenInput(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = name; input.value = value;
    return input;
}
</script>
@endsection