@extends('layouts.admin')
@section('title', 'ویرایش محصول — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="/admin/dashboard" class="text-[#a8c4a8] hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="{{ route('admin.products') }}" class="text-[#a8c4a8] hover:text-white transition-colors">محصولات</a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="text-white font-semibold">ویرایش محصول: {{ $product->name_fa }}</span>
      </div>
      <a href="{{ route('admin.products') }}" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[#16161c] text-[#a8c4a8] border border-[#222230] transition-all hover:border-[#2e2e3e] hover:text-white no-underline">
        <i class="fa-solid fa-arrow-right text-[11px]"></i>
        بازگشت به لیست
      </a>
    </header>

    <main class="p-6 flex-1 pb-24">

      @if ($errors->any())
        <div class="bg-[#f05c5c]/10 border border-[#f05c5c] rounded-xl p-4 mb-6 text-right">
            <div class="text-[#f05c5c] font-bold text-sm mb-2.5">
                <i class="fa-solid fa-triangle-exclamation"></i> اصلاح خطاهای زیر برای ذخیره تغییرات الزامی است:
            </div>
            <ul class="text-[#ff9191] text-xs pr-5 margin-0 list-disc space-y-1.5 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @if ($aiModels->isEmpty())
        <div class="bg-[#f0a05c]/10 border border-[#f0a05c] rounded-xl p-4 mb-6 text-right text-xs text-[#f0a05c]">
            <i class="fa-solid fa-circle-exclamation"></i>
            هیچ مدل هوش مصنوعی فعالی در سیستم ثبت نشده است. ابتدا از
            <a href="{{ route('admin.ai-models.create') }}" class="underline font-bold">این صفحه</a>
            حداقل یک مدل اضافه کنید.
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">ویرایش محصول</div>
        <div class="text-xs text-[#4d7a56]">{{ $product->name_fa }} — {{ $product->name_en }}</div>
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

      <form id="real-product-form" action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="status" id="product-status" value="{{ old('status', $product->status) }}">

        <div class="block space-y-4" id="panel-1">
          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-fingerprint text-[#a07af5]"></i> هویت محصول</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نام فارسی <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="text" name="name_fa" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('name_fa', $product->name_fa) }}">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نام انگلیسی <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="text" name="name_en" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" value="{{ old('name_en', $product->name_en) }}" oninput="autoSlug(this)">
              </div>
            </div>

            <div class="grid grid-cols-1 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">آدرس URL (Slug) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="text" name="slug" id="slug-input" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left" value="{{ old('slug', $product->slug) }}">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">توضیح فارسی</label>
                <textarea name="description_fa" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] resize-y min-h-[90px] leading-relaxed">{{ old('description_fa', $product->description_fa) }}</textarea>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">توضیح انگلیسی</label>
                <textarea name="description_en" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] resize-y min-h-[90px] leading-relaxed ltr text-left">{{ old('description_en', $product->description_en) }}</textarea>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">دسته‌بندی <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="category" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-main" onchange="updateSubcat()">
                  <option value="">انتخاب کنید</option>
                  @php $cat = old('category', $product->category); @endphp
                  <option value="PEOPLE" {{ $cat == 'PEOPLE' ? 'selected' : '' }}>PEOPLE — شخصی</option>
                  <option value="BUSINESS" {{ $cat == 'BUSINESS' ? 'selected' : '' }}>BUSINESS — کسب‌وکار</option>
                  <option value="EVENTS" {{ $cat == 'EVENTS' ? 'selected' : '' }}>EVENTS — مناسبت‌ها</option>
                  <option value="FAMILY" {{ $cat == 'FAMILY' ? 'selected' : '' }}>FAMILY — خانواده</option>
                  <option value="AVATARS" {{ $cat == 'AVATARS' ? 'selected' : '' }}>AVATARS — آواتار</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">زیردسته</label>
                <select name="subcategory" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" id="cat-sub">
                  <option value="{{ old('subcategory', $product->subcategory) }}">{{ old('subcategory', $product->subcategory) ?: 'ابتدا دسته انتخاب کنید' }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تگ‌های جستجو <span class="text-[10px] font-normal text-[#4d7a56] mr-1">Enter بزنید</span></label>
                <div class="bg-[#111116] border border-[#222230] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] focus-within:border-[#a07af5]" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
                  @foreach (old('tags', is_array($product->tags) ? $product->tags : []) as $tag)
                    <span class="inline-flex items-center gap-1 bg-[#a07af5]/12 border border-[#a07af5]/25 rounded px-2 py-0.5 text-xs text-[#a07af5]">{{ $tag }}<button type="button" class="text-[#4d7a56] hover:text-[#f05c5c] font-bold mr-1" onclick="this.parentElement.remove()">×</button></span>
                  @endforeach
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
                  <input type="checkbox" name="is_featured" value="1" class="sr-only peer" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">برچسب «جدید»</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_new" value="1" class="sr-only peer" {{ old('is_new', $product->is_new) ? 'checked' : '' }}>
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">ترند</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="is_trending" value="1" class="sr-only peer" {{ old('is_trending', $product->is_trending) ? 'checked' : '' }}>
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-images text-[#a07af5]"></i> رسانه نمایشی</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تصویر کارت (Thumbnail)</label>
                @if ($product->thumbnail)
                  <div class="mb-2 rounded-lg overflow-hidden border border-[#222230]">
                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-32 object-cover">
                  </div>
                @endif
                <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('thumbnail-file').click()">
                  <i class="fa-solid fa-image text-2xl text-[#4d7a56] mb-2 block"></i>
                  <div class="text-xs font-bold text-[#a8c4a8]" id="thumb-title">برای جایگزینی، تصویر جدید انتخاب کنید</div>
                  <input type="file" id="thumbnail-file" name="thumbnail" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'thumb-title')">
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">تصویر کاور (Cover)</label>
                @if ($product->cover)
                  <div class="mb-2 rounded-lg overflow-hidden border border-[#222230]">
                    <img src="{{ asset('storage/' . $product->cover) }}" class="w-full h-32 object-cover">
                  </div>
                @endif
                <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-6 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('cover-file').click()">
                  <i class="fa-solid fa-panorama text-2xl text-[#4d7a56] mb-2 block"></i>
                  <div class="text-xs font-bold text-[#a8c4a8]" id="cover-title">برای جایگزینی، تصویر جدید انتخاب کنید</div>
                  <input type="file" id="cover-file" name="cover" accept="image/*" class="hidden" onchange="updateFileLabel(this, 'cover-title')">
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">نمونه خروجی‌ها (چندگانه)</label>
              @if (is_array($product->sample_outputs) && count($product->sample_outputs))
                <div class="grid grid-cols-4 md:grid-cols-6 gap-2 mb-2">
                  @foreach ($product->sample_outputs as $sample)
                    <img src="{{ asset('storage/' . $sample) }}" class="w-full h-16 object-cover rounded-lg border border-[#222230]">
                  @endforeach
                </div>
              @endif
              <div class="border-2 border-dashed border-[#2e2e3e] rounded-xl p-5 text-center cursor-pointer bg-[#111116] hover:border-[#a07af5]" onclick="document.getElementById('samples-file').click()">
                <i class="fa-solid fa-images text-xl text-[#4d7a56] mb-1 block"></i>
                <div class="text-xs text-[#a8c4a8]" id="samples-title">افزودن تصاویر نمونه جدید (به موارد قبلی اضافه می‌شود)</div>
                <input type="file" id="samples-file" name="sample_outputs[]" multiple accept="image/*" class="hidden" onchange="updateFileLabel(this, 'samples-title', true)">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">نوع رسانه</label>
                <select name="media_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                  @php $mediaType = old('media_type', $product->media_type); @endphp
                  <option value="photo" {{ $mediaType == 'photo' ? 'selected' : '' }}>photo — عکس</option>
                  <option value="video" {{ $mediaType == 'video' ? 'selected' : '' }}>video — ویدیو</option>
                  <option value="both" {{ $mediaType == 'both' ? 'selected' : '' }}>both — هر دو</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">لینک ویدیوی پیش‌نمایش</label>
                <input type="text" name="preview_video_url" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white ltr text-left" value="{{ old('preview_video_url', $product->preview_video_url) }}">
              </div>
            </div>
          </div>
        </div>

        <div class="hidden space-y-4" id="panel-2">
          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-microchip text-[#a07af5]"></i> پایپ‌لاین هوش مصنوعی</div>

            <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">مدل اصلی (Primary) — اولویت ۱</div>
            <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5 flex items-center gap-3 mb-4">
              <div class="flex-1">
                <select name="primary_model" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white w-full focus:border-[#a07af5] mb-2">
                  <option value="">— انتخاب مدل اصلی —</option>
                  @php $primaryModel = old('primary_model', $product->primary_model); @endphp
                  @foreach ($aiModels as $model)
                    <option value="{{ $model->model_id }}" {{ $primaryModel == $model->model_id ? 'selected' : '' }}>
                      {{ $model->name }} ({{ $model->provider }})
                    </option>
                  @endforeach
                </select>
                <div class="grid grid-cols-2 gap-2">
                  <input type="number" name="timeout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" placeholder="timeout (ثانیه)" value="{{ old('timeout', $product->timeout) }}">
                  <select name="pipeline_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                    @php $pipelineType = old('pipeline_type', $product->pipeline_type); @endphp
                    <option value="image_generation" {{ $pipelineType == 'image_generation' ? 'selected' : '' }}>image_generation</option>
                    <option value="image_editing" {{ $pipelineType == 'image_editing' ? 'selected' : '' }}>image_editing</option>
                    <option value="text_generation" {{ $pipelineType == 'text_generation' ? 'selected' : '' }}>text_generation</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">فال‌بک‌ها (Fallback Models) — اولویت ۲، ۳، ...</div>
            <p class="text-[10.5px] text-[#4d7a56] mb-2.5 leading-relaxed">
              اگر مدل اصلی پاسخ نداد، سیستم به ترتیبی که اینجا چیده‌اید سراغ مدل بعدی می‌رود.
            </p>
            <div id="fallback-list" class="space-y-2"></div>
            <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold mt-2" onclick="addFallback()">
              <i class="fa-solid fa-plus"></i> افزودن Fallback
            </button>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-terminal text-[#a07af5]"></i> تنظیمات پرامپت</div>
            <div class="flex flex-col gap-1.5 mb-3.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">Prompt Template <span class="text-[#f05c5c] mr-0.5">*</span></label>
              <textarea name="prompt_template" id="prompt-template" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white min-h-[100px] ltr text-left font-mono">{{ old('prompt_template', $product->prompt_template) }}</textarea>
              <div class="flex flex-wrap gap-1.5 mt-1.5" id="var-chips">
                <span class="text-[11px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 cursor-pointer text-[#a8c4a8]" onclick="insertVar('{name}')">{name}</span>
                <span class="text-[11px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 cursor-pointer text-[#a8c4a8]" onclick="insertVar('{gender}')">{gender}</span>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-table-list text-[#a07af5]"></i> سازنده فیلدهای ورودی (Input Schema)</div>
            <div id="input-fields-list" class="space-y-2"></div>
            <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold mt-3" onclick="addInputField()">
              <i class="fa-solid fa-plus"></i> افزودن فیلد ورودی جدید
            </button>
          </div>
        </div>

        <div class="hidden space-y-4" id="panel-3">
          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-file-export text-[#a07af5]"></i> تنظیمات خروجی</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
                <div>
                  <div class="text-[12.5px] font-semibold text-[#a8c4a8]">فعال‌سازی واترمارک</div>
                </div>
                <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
                  <input type="checkbox" name="watermark_enabled" value="1" class="sr-only peer" {{ old('watermark_enabled', $product->watermark_enabled) ? 'checked' : '' }}>
                  <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
                </label>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">موقعیت واترمارک</label>
                <select name="watermark_position" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                  @php $wmPos = old('watermark_position', $product->watermark_position); @endphp
                  <option value="corner" {{ $wmPos == 'corner' ? 'selected' : '' }}>corner — گوشه</option>
                  <option value="center" {{ $wmPos == 'center' ? 'selected' : '' }}>center — وسط</option>
                  <option value="none" {{ $wmPos == 'none' ? 'selected' : '' }}>none — بدون واترمارک</option>
                </select>
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-coins text-[#a07af5]"></i> قیمت‌گذاری</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">مدل قیمت‌گذاری <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <select name="pricing_model" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" onchange="toggleCreditCost(this)">
                  @php $pricingModel = old('pricing_model', $product->pricing_model); @endphp
                  <option value="free" {{ $pricingModel == 'free' ? 'selected' : '' }}>رایگان (Free)</option>
                  <option value="per_credit" {{ $pricingModel == 'per_credit' ? 'selected' : '' }}>به ازای هر کردیت (Per Credit)</option>
                  <option value="subscription" {{ $pricingModel == 'subscription' ? 'selected' : '' }}>اشتراکی (Subscription)</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5 transition-all {{ $pricingModel != 'per_credit' ? 'opacity-30 pointer-events-none' : '' }}" id="credit-cost-wrap">
                <label class="text-xs font-semibold text-[#a8c4a8]">هزینه کردیت محصول</label>
                <input type="number" name="credit_cost" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" value="{{ old('credit_cost', $product->credit_cost) }}">
              </div>
            </div>
          </div>

          <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
            <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-sliders text-[#a07af5]"></i> تنظیمات کارت و گالری</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">حالت نمایش</label>
                <select name="display_mode" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                  @php $displayMode = old('display_mode', $product->display_mode); @endphp
                  <option value="card" {{ $displayMode == 'card' ? 'selected' : '' }}>card — کارت استاندارد</option>
                  <option value="featured" {{ $displayMode == 'featured' ? 'selected' : '' }}>featured — ویژه بزرگ</option>
                  <option value="simple" {{ $displayMode == 'simple' ? 'selected' : '' }}>simple — ساده</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">شکل کارت</label>
                <select name="card_shape" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                  @php $cardShape = old('card_shape', $product->card_shape); @endphp
                  <option value="portrait" {{ $cardShape == 'portrait' ? 'selected' : '' }}>portrait — عمودی</option>
                  <option value="landscape" {{ $cardShape == 'landscape' ? 'selected' : '' }}>landscape — افقی</option>
                  <option value="square" {{ $cardShape == 'square' ? 'selected' : '' }}>square — مربع</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">چیدمان گالری</label>
                <select name="gallery_layout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
                  @php $galleryLayout = old('gallery_layout', $product->gallery_layout); @endphp
                  <option value="grid" {{ $galleryLayout == 'grid' ? 'selected' : '' }}>grid — شبکه</option>
                  <option value="masonry" {{ $galleryLayout == 'masonry' ? 'selected' : '' }}>masonry — آبشاری</option>
                  <option value="slider" {{ $galleryLayout == 'slider' ? 'selected' : '' }}>slider — اسلایدر</option>
                </select>
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-[#a8c4a8]">برچسب اختیاری روی کارت</label>
              <input type="text" name="card_label" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" value="{{ old('card_label', $product->card_label) }}">
            </div>
          </div>
        </div>
      </form>
    </main>

    <div class="sticky bottom-0 bg-[#111116] border-t border-[#222230] p-4 flex items-center justify-between z-40">
      <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#16161c] text-[#a8c4a8] border border-[#222230] hover:text-white transition-all" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> مرحله قبل
      </button>
      <div class="text-xs text-[#4d7a56]"> مرحله <strong class="text-white" id="step-label-num">۱</strong> از ۳ </div>
      <div class="flex gap-2">
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#0BBF53] text-white hover:bg-[#08a443] transition-all" id="btn-submit-mid" onclick="submitForm(document.getElementById('product-status').value)">
          <i class="fa-solid fa-floppy-disk"></i> ذخیره تغییرات
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-all" id="btn-next" onclick="nextStep()">
          مرحله بعد <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#0BBF53] text-white hover:bg-[#08a443] transition-all" id="btn-submit" onclick="submitForm(document.getElementById('product-status').value)" style="display:none;">
          <i class="fa-solid fa-check"></i> ذخیره نهایی تغییرات
        </button>
      </div>
    </div>

  </div>
</div>
@endsection

@section('scripts')
@php
    // لیست مدل‌های فعال — از کنترلر گرفته شده، برای ساخت داینامیک ردیف‌های fallback استفاده می‌شود
    $aiModelsForJs = $aiModels->map(function ($m) {
        return [
            'id' => $m->model_id,
            'name' => $m->name,
            'provider' => $m->provider,
        ];
    });

    // مدل‌های fallback فعلیِ محصول (برای پر کردن خودکار ردیف‌ها هنگام باز شدن صفحه ویرایش)
    $existingFallbacks = old('fallback_models', is_array($product->fallback_models) ? $product->fallback_models : []);

    // اسکیمای ورودی فعلیِ محصول (برای پر کردن خودکار ردیف‌ها هنگام باز شدن صفحه ویرایش)
    $existingInputSchema = old('input_schema', is_array($product->input_schema) ? $product->input_schema : []);
@endphp
<script>
const AI_MODELS = @json($aiModelsForJs);
const EXISTING_FALLBACKS = @json($existingFallbacks);
const EXISTING_INPUT_SCHEMA = @json($existingInputSchema);

let cur = 1;

function goStep(n) {
  if(n < 1 || n > 3) return;
  cur = n;

  for(let i=1; i<=3; i++) {
    const p = document.getElementById('panel-'+i);
    const tab = document.getElementById('step-tab-'+i);
    const num = document.getElementById('step-num-'+i);

    if(i === n) {
      p.classList.remove('hidden'); p.classList.add('block');
      tab.classList.add('bg-[#a07af5]/10', 'border-[#a07af5]/20');
      num.classList.add('border-[#a07af5]', 'bg-[#a07af5]/15', 'text-[#a07af5]');
    } else {
      p.classList.remove('block'); p.classList.add('hidden');
      tab.classList.remove('bg-[#a07af5]/10', 'border-[#a07af5]/20');
      num.classList.remove('border-[#a07af5]', 'bg-[#a07af5]/15', 'text-[#a07af5]');
    }
  }

  document.getElementById('btn-prev').style.display = n === 1 ? 'none' : 'inline-flex';
  document.getElementById('btn-next').style.display = n === 3 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit-mid').style.display = n === 3 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit').style.display = n === 3 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = n;
  window.scrollTo({top: 0, behavior: 'smooth'});
}

function nextStep() { if(cur < 3) goStep(cur + 1); }
function prevStep() { if(cur > 1) goStep(cur - 1); }

function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;

  const chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[#a07af5]/12 border border-[#a07af5]/25 rounded px-2 py-0.5 text-xs text-[#a07af5]';
  chip.innerHTML = `${v}<button type="button" class="text-[#4d7a56] hover:text-[#f05c5c] font-bold mr-1" onclick="this.parentElement.remove()">×</button>`;
  document.getElementById('tags-wrap').insertBefore(chip, inp);
  inp.value = '';
}

function updateFileLabel(input, id, isMultiple = false) {
  if(input.files && input.files.length > 0) {
    document.getElementById(id).textContent = isMultiple
      ? `${input.files.length} فایل جدید انتخاب شد`
      : `فایل انتخاب شد: ${input.files[0].name}`;
  }
}

function autoSlug(el) {
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

function toggleCreditCost(sel) {
  const w = document.getElementById('credit-cost-wrap');
  if(sel.value === 'per_credit') {
    w.classList.remove('opacity-30', 'pointer-events-none');
  } else {
    w.classList.add('opacity-30', 'pointer-events-none');
  }
}

/* ── افزودن فیلد FALLBACK، با امکان پیش‌انتخاب مقدار (برای پر کردن خودکار حالت ویرایش) ── */
let fbIdx = 0;
function addFallback(preselectedValue = null) {
  if (!AI_MODELS.length) {
    alert('ابتدا حداقل یک مدل هوش مصنوعی فعال در سیستم ثبت کنید.');
    return;
  }

  fbIdx++;
  const div = document.createElement('div');
  div.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3 flex items-center gap-3';
  div.id = `fb-row-${fbIdx}`;

  const options = AI_MODELS.map(m =>
    `<option value="${m.id}" ${preselectedValue === m.id ? 'selected' : ''}>${m.name} (${m.provider})</option>`
  ).join('');

  div.innerHTML = `
    <span class="text-[10px] font-mono text-gray-500 w-14 shrink-0">اولویت ${fbIdx + 1}</span>
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white flex-1 fallback-select-item">
      ${options}
    </select>
    <button type="button" class="text-xs text-[#f05c5c] bg-[#f05c5c]/10 px-2.5 py-1.5 rounded-lg" onclick="document.getElementById('fb-row-${fbIdx}').remove()">حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(div);
}

function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

/* ── افزودن فیلد ورودی، با امکان پیش‌انتخاب مقدار (برای پر کردن خودکار حالت ویرایش) ── */
let fieldIdx = 0;
function addInputField(preset = null) {
  fieldIdx++;
  const div = document.createElement('div');
  div.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3 grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center input-schema-row';
  div.id = `field-row-${fieldIdx}`;

  const fieldId = preset?.field_id ?? '';
  const labelFa = preset?.label_fa ?? '';
  const type = preset?.type ?? 'text';
  const required = preset?.required ?? '1';

  div.innerHTML = `
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white ltr text-left schema-id" placeholder="field_id" value="${fieldId}">
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-label" placeholder="برچسب فارسی" value="${labelFa}">
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-type">
      <option value="text" ${type === 'text' ? 'selected' : ''}>text</option>
      <option value="image_upload" ${type === 'image_upload' ? 'selected' : ''}>image_upload</option>
      <option value="select" ${type === 'select' ? 'selected' : ''}>select</option>
      <option value="toggle" ${type === 'toggle' ? 'selected' : ''}>toggle</option>
    </select>
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-required">
      <option value="1" ${String(required) === '1' ? 'selected' : ''}>اجباری</option>
      <option value="0" ${String(required) === '0' ? 'selected' : ''}>اختیاری</option>
    </select>
    <button type="button" class="text-xs text-[#f05c5c] bg-[#f05c5c]/10 py-2 rounded-lg" onclick="document.getElementById('field-row-${fieldIdx}').remove()">حذف فیلد</button>
  `;
  document.getElementById('input-fields-list').appendChild(div);
}

const subcats = {
  PEOPLE: ['Professional', 'Fashion', 'Lifestyle'],
  BUSINESS: ['Real Estate', 'Medical', 'Education'],
  EVENTS: ['Birthday', 'Wedding', 'Nowruz'],
  FAMILY: ['Parents', 'Kids'],
  AVATARS: ['Gaming', 'Anime', 'Fantasy']
};
function updateSubcat() {
  const main = document.getElementById('cat-main').value;
  const sub = document.getElementById('cat-sub');
  const current = sub.value;
  sub.innerHTML = '';
  if(!main || !subcats[main]) {
    sub.innerHTML = '<option value="">زیردسته ندارد</option>';
    return;
  }
  subcats[main].forEach(s => {
    sub.innerHTML += `<option value="${s}" ${s === current ? 'selected' : ''}>${s}</option>`;
  });
}

function createHiddenInput(name, value) {
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = name; input.value = value;
  return input;
}

function submitForm(statusValue) {
  document.getElementById('product-status').value = statusValue;
  const form = document.getElementById('real-product-form');

  document.querySelectorAll('#tags-wrap span').forEach((chip, idx) => {
    const text = chip.textContent.replace('×', '').trim();
    if(text) form.appendChild(createHiddenInput(`tags[${idx}]`, text));
  });

  document.querySelectorAll('.fallback-select-item').forEach((select, idx) => {
    form.appendChild(createHiddenInput(`fallback_models[${idx}]`, select.value));
  });

  document.querySelectorAll('.input-schema-row').forEach((row, idx) => {
    const fieldId = row.querySelector('.schema-id').value.trim();
    const labelFa = row.querySelector('.schema-label').value.trim();
    const type = row.querySelector('.schema-type').value;
    const required = row.querySelector('.schema-required').value;

    if(fieldId) {
      form.appendChild(createHiddenInput(`input_schema[${idx}][field_id]`, fieldId));
      form.appendChild(createHiddenInput(`input_schema[${idx}][label_fa]`, labelFa));
      form.appendChild(createHiddenInput(`input_schema[${idx}][type]`, type));
      form.appendChild(createHiddenInput(`input_schema[${idx}][required]`, required));
    }
  });

  form.submit();
}

/* ── در بارگذاری اولیه صفحه، فیلدهای داینامیک رو با مقادیر فعلی محصول پر کن ── */
document.addEventListener('DOMContentLoaded', () => {
  if (EXISTING_FALLBACKS.length) {
    EXISTING_FALLBACKS.forEach(modelId => addFallback(modelId));
  }

  if (EXISTING_INPUT_SCHEMA.length) {
    EXISTING_INPUT_SCHEMA.forEach(field => addInputField(field));
  }

  updateSubcat();
  document.getElementById('cat-sub').value = "{{ old('subcategory', $product->subcategory) }}";
});
</script>
@endsection