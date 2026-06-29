@extends('layouts.admin')
@section('title', 'ثبت محصول جدید مرحله‌ای — پنل مدیریت وطن')

@section('content')
<div class="flex min-h-screen bg-bg text-white" dir="rtl">

  @include('admin.partials.sidebar')

  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    {{-- هدر صفحه --}}
    <header class="sticky top-0 z-50 bg-s1 border-b border-b1 px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-text2">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <a href="{{ route('admin.products') }}" class="hover:text-white">محصولات</a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <span class="text-white font-semibold">ثبت محصول جدید</span>
      </div>
      <div class="flex-1"></div>
      <a href="{{ route('admin.products') }}" class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-s2 text-text2 border border-b1 hover:border-b2 hover:text-white transition-colors">
        <i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت به لیست
      </a>
    </header>

    <main class="px-6 py-6 flex-1 max-w-4xl mx-auto w-full">

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">ثبت مرحله‌ای محصول هوش مصنوعی</div>
        <div class="text-[13px] text-text3">فرم زیر را به گام به گام تکمیل کرده و در مرحله آخر ثبت نهایی کنید.</div>
      </div>

      {{-- نمایش خطاهای ولیدیشن --}}
      @if ($errors->any())
        <div class="bg-red/10 border border-red/30 text-red rounded-xl px-4 py-3 mb-5 text-sm">
          <div class="font-bold mb-1.5">خطاهایی در فرم وجود دارد:</div>
          <ul class="list-disc pr-5 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- نشانگر مراحل (Steps Indicator) --}}
      <div class="bg-s2 border border-b1 rounded-2xl p-4 mb-6 flex justify-between items-center relative overflow-hidden">
        <div class="absolute top-1/2 right-12 left-12 h-0.5 bg-b1 -translate-y-1/2 z-0"></div>
        <div id="wizard-progress-bar" class="absolute top-1/2 right-12 h-0.5 bg-accent -translate-y-1/2 z-0 transition-all duration-300" style="width: 0%; left: auto;"></div>

        <div class="step-indicator-item active flex flex-col items-center gap-1.5 z-10 relative" data-step="1">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-xs font-bold flex items-center justify-center transition-all duration-200 text-text3 step-circle">۱</div>
          <span class="text-[11px] font-bold text-text3 step-label">هویت محصول</span>
        </div>
        <div class="step-indicator-item flex flex-col items-center gap-1.5 z-10 relative" data-step="2">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-xs font-bold flex items-center justify-center transition-all duration-200 text-text3 step-circle">۲</div>
          <span class="text-[11px] font-bold text-text3 step-label">رسانه و کاور</span>
        </div>
        <div class="step-indicator-item flex flex-col items-center gap-1.5 z-10 relative" data-step="3">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-xs font-bold flex items-center justify-center transition-all duration-200 text-text3 step-circle">۳</div>
          <span class="text-[11px] font-bold text-text3 step-label">تنظیمات پرامپت</span>
        </div>
        <div class="step-indicator-item flex flex-col items-center gap-1.5 z-10 relative" data-step="4">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-xs font-bold flex items-center justify-center transition-all duration-200 text-text3 step-circle">۴</div>
          <span class="text-[11px] font-bold text-text3 step-label">خروجی و توکن</span>
        </div>
      </div>

      <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="multistep-product-form">
        @csrf

        {{-- ============================== مرحله ۱: هویت محصول ============================== --}}
        <div class="wizard-step space-y-4" data-step="1">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5">
            <div class="text-[13px] font-bold text-white mb-4 pb-3 border-b border-b1 flex items-center gap-2">
              <i class="fa-solid fa-fingerprint text-accent"></i> گام ۱: هویت اصلی محصول
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">نام فارسی <span class="text-red">*</span></label>
                <input type="text" name="name_fa" value="{{ old('name_fa') }}" required
                       class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent"
                       placeholder="مثال: آواتار کارتونی فانتزی">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">نام انگلیسی <span class="text-red">*</span></label>
                <input type="text" name="name_en" dir="ltr" value="{{ old('name_en') }}" required
                       class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent"
                       placeholder="Fantasy Cartoon Avatar">
              </div>
            </div>

            <div class="mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">آدرس پیوند یکتا (Slug) <span class="text-red">*</span></label>
                <input type="text" name="slug" dir="ltr" value="{{ old('slug') }}" required
                       class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent"
                       placeholder="fantasy-cartoon-avatar">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">توضیحات فارسی</label>
                <textarea name="description_fa" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white min-h-[80px] leading-relaxed focus:outline-none focus:border-accent" placeholder="توضیحی برای کاربران پلتفرم...">{{ old('description_fa') }}</textarea>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">توضیحات انگلیسی</label>
                <textarea name="description_en" dir="ltr" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left min-h-[80px] leading-relaxed focus:outline-none focus:border-accent" placeholder="English details...">{{ old('description_en') }}</textarea>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">دسته‌بندی اصلی <span class="text-red">*</span></label>
                <select name="category" id="category-select" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="">انتخاب کنید</option>
                  @foreach ($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') == $key)>{{ $label }} ({{ $key }})</option>
                  @endforeach
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">زیردسته</label>
                <select name="subcategory" id="subcategory-select" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="">ابتدا دسته را انتخاب کنید</option>
                  @foreach ($subcategoriesMap as $catKey => $subs)
                    @foreach ($subs as $sub)
                      <option value="{{ $sub }}" data-parent="{{ $catKey }}" @selected(old('subcategory') == $sub)>{{ $sub }}</option>
                    @endforeach
                  @endforeach
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">وضعیت انتشار <span class="text-red">*</span></label>
                <select name="status" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="draft" @selected(old('status', 'draft') == 'draft')>پیش‌نویس</option>
                  <option value="active" @selected(old('status') == 'active')>فعال (نمایش عمومی)</option>
                  <option value="inactive" @selected(old('status') == 'inactive')>غیرفعال</option>
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-semibold text-text2">کلمات کلیدی / تگ‌ها <span class="text-[10px] text-text3 font-normal">(با کاما انگلیسی , جدا کنید)</span></label>
              <input type="text" name="tags" value="{{ old('tags') }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white w-full mt-1 focus:outline-none focus:border-accent" placeholder="آواتار, فانتزی, تصویر دیزنی">
            </div>

            <div class="grid grid-cols-3 gap-4">
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12.5px] font-semibold text-text2">محصول ویژه صفحه اصلی</div>
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="accent-green w-4 h-4">
              </label>
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12.5px] font-semibold text-text2">برچسب جدید (is_new)</div>
                <input type="checkbox" name="is_new" value="1" @checked(old('is_new', true)) class="accent-green w-4 h-4">
              </label>
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12.5px] font-semibold text-text2">محصول پرطرفدار (Trending)</div>
                <input type="checkbox" name="is_trending" value="1" @checked(old('is_trending')) class="accent-green w-4 h-4">
              </label>
            </div>
          </div>
        </div>

        {{-- ============================== مرحله ۲: رسانه و دمو ============================== --}}
        <div class="wizard-step space-y-4 hidden" data-step="2">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5">
            <div class="text-[13px] font-bold text-white mb-4 pb-3 border-b border-b1 flex items-center gap-2">
              <i class="fa-solid fa-images text-accent"></i> گام ۲: رسانه و فایل‌های پیش‌نمایش
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">تصویر کاور کارت (Thumbnail) <span class="text-red">*</span></label>
                <div class="border-[1.5px] border-dashed border-b2 rounded-xl p-4 bg-s1 text-center hover:border-accent transition-colors">
                  <input type="file" name="thumbnail" accept="image/*" class="block w-full text-xs text-text2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent/10 file:text-accent file:text-[11px] file:font-semibold">
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">تصویر کاور بزرگ پس‌زمینه (Cover)</label>
                <div class="border-[1.5px] border-dashed border-b2 rounded-xl p-4 bg-s1 text-center hover:border-accent transition-colors">
                  <input type="file" name="cover" accept="image/*" class="block w-full text-xs text-text2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent/10 file:text-accent file:text-[11px] file:font-semibold">
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-semibold text-text2">گالری تصاویر نمونه کار خروجی <span class="text-[10px] text-text3 font-normal">(حداکثر ۱۰ تصویر انتخاب کنید)</span></label>
              <input type="file" name="sample_images[]" accept="image/*" multiple class="bg-s1 border border-b1 rounded-lg px-3 py-2 w-full mt-1.5 file:py-1 file:px-2.5 file:rounded file:border-0 file:bg-accent/10 file:text-accent text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">نوع رسانه ورودی مجاز</label>
                <select name="media_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="photo">فقط عکس (Photo)</option>
                  <option value="video">فقط ویدیو (Video)</option>
                  <option value="both">هر دو (عکس و ویدیو)</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">لینک ویدیوی دمو / پیش‌نمایش آپارات یا یوتیوب</label>
                <input type="text" name="preview_video_url" dir="ltr" value="{{ old('preview_video_url') }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent" placeholder="https://...">
              </div>
            </div>
          </div>
        </div>

        {{-- ============================== مرحله ۳: هسته هوش مصنوعی ============================== --}}
        {{-- ============================== مرحله ۳: هسته هوش مصنوعی ============================== --}}
        <div class="wizard-step space-y-4 hidden" data-step="3">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5">
            <div class="text-[13px] font-bold text-white mb-4 pb-3 border-b border-b1 flex items-center gap-2">
              <i class="fa-solid fa-microchip text-accent"></i> گام ۳: موتور پردازش هوش مصنوعی و پرامپت
            </div>

            <div class="bg-s1 border border-b1 rounded-xl p-4 mb-4">
              <label class="text-xs font-bold text-text2 block mb-2">انتخاب مدل هوش مصنوعی اصلی (Primary Model) <span class="text-red">*</span></label>
              <select name="primary_model" required class="bg-s2 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white w-full mb-3 focus:outline-none focus:border-accent">
                @forelse ($aiModels as $key => $label)
                  <option value="{{ $key }}" @selected(old('primary_model') == $key)>{{ $label }} ({{ $key }})</option>
                @empty
                  <option value="">⚠️ لیست مدل‌های هوش مصنوعی خالی است (بعداً ایجاد می‌شود)</option>
                @endforelse
              </select>
              
              <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1.5">
                  <label class="text-[11px] font-medium text-text3">زمان انقضا تایم‌اوت (ثانیه)</label>
                  <input type="number" name="primary_timeout" value="{{ old('primary_timeout', 60) }}" min="1" required class="bg-s2 border border-b1 rounded-lg px-3 py-2 text-[13px] text-white focus:outline-none focus:border-accent">
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[11px] font-medium text-text3">نوع عملیات مدل</label>
                  <select name="primary_type" required class="bg-s2 border border-b1 rounded-lg px-3 py-2 text-[13px] text-white focus:outline-none focus:border-accent">
                    <option value="image_generation" @selected(old('primary_type', 'image_generation') == 'image_generation')>تولید عکس (Image Generation)</option>
                    <option value="image_editing" @selected(old('primary_type') == 'image_editing')>ویرایش عکس (Image Editing)</option>
                    <option value="text_generation" @selected(old('primary_type') == 'text_generation')>تولید متن (Text Generation)</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-bold text-text2 block mb-1.5">مدل‌های پشتیبان (Fallback Models) <span class="text-[10px] text-text3 font-normal">(حداکثر ۲ مورد به ترتیب اولویت)</span></label>
              <div class="grid grid-cols-2 gap-3">
                <select name="fallback_models[]" class="bg-s1 border border-b1 rounded-lg px-3 py-2 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="">— پله اول فالبک —</option>
                  @forelse ($aiModels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @empty
                    <option value="">لیست خالی است</option>
                  @endforelse
                </select>
                <select name="fallback_models[]" class="bg-s1 border border-b1 rounded-lg px-3 py-2 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="">— پله دوم فالبک —</option>
                  @forelse ($aiModels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @empty
                    <option value="">لیست خالی است</option>
                  @endforelse
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-semibold text-text2">ساختار پرامپت قالب (Prompt Template) <span class="text-red">*</span></label>
              <textarea name="prompt_template" dir="ltr" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white text-left w-full mt-1.5 min-h-[90px] font-mono focus:outline-none focus:border-accent" placeholder="A stunning portrait of {name} as a wizard, background {background}, photorealistic, 8k resolution, sharp focus">{{ old('prompt_template') }}</textarea>
              <div class="text-[10px] text-accent mt-1.5">کلیدهای پویا و تزریقی سیستم: <span dir="ltr" class="font-mono bg-s1/80 px-1 py-px rounded text-text2">{name} {gender} {clothing_style} {background}</span></div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-semibold text-text2">کلمات منفی منع‌شده (Negative Prompt)</label>
              <textarea name="negative_prompt" dir="ltr" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white text-left w-full mt-1.5 min-h-[50px] font-mono focus:outline-none focus:border-accent" placeholder="blurry, bad anatomy, low quality, extra limbs, watermark">{{ old('negative_prompt') }}</textarea>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12px] font-semibold text-text2">نمایش شفاف پرامپت به کاربر</div>
                <input type="checkbox" name="show_prompt_to_user" value="1" @checked(old('show_prompt_to_user')) class="accent-green w-4 h-4">
              </label>
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12px] font-semibold text-text2">فعال بودن قابلیت Face Swap</div>
                <input type="checkbox" name="face_swap_enabled" value="1" @checked(old('face_swap_enabled')) class="accent-green w-4 h-4">
              </label>
              <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
                <div class="text-[12px] font-semibold text-text2">پایپ‌لاین چندمرحله‌ای ترکیبی</div>
                <input type="checkbox" name="multi_step_pipeline" value="1" @checked(old('multi_step_pipeline')) class="accent-green w-4 h-4">
              </label>
            </div>
          </div>
        </div>

        {{-- ============================== مرحله ۴: تنظیمات خروجی و توکن ============================== --}}
        <div class="wizard-step space-y-4 hidden" data-step="4">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5">
            <div class="text-[13px] font-bold text-white mb-4 pb-3 border-b border-b1 flex items-center gap-2">
              <i class="fa-solid fa-coins text-accent"></i> گام ۴: پیکربندی فایل خروجی و سیستم توکن
            </div>

            {{-- بخش توکن و قیمت‌گذاری ساده‌شده --}}
            <div class="bg-s1/60 border border-b1 rounded-xl p-4 mb-4 grid grid-cols-2 gap-4 items-center">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-text2">تعداد توکن مصرفی این محصول <span class="text-red">*</span></label>
                <input type="number" name="credit_cost" id="credit_cost_input" value="{{ old('credit_cost', 5) }}" min="0" required 
                       class="bg-s2 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent"
                       placeholder="مثال: ۵ توکن کسر شود">
                <span class="text-[10px] text-text3">تعداد توکنی که در هر بار اجرای موفق از اکانت کاربر کسر خواهد شد.</span>
              </div>
              <div>
                <label class="flex items-center justify-between px-4 py-3 bg-s2 border border-b1 rounded-lg cursor-pointer mt-5">
                  <div>
                    <div class="text-[12.5px] font-bold text-white">محصول کاملاً رایگان باشد</div>
                    <div class="text-[10px] text-text3">کاربر بدون کسر هیچ توکنی از این ابزار استفاده کند.</div>
                  </div>
                  <input type="checkbox" name="is_free" id="is_free_checkbox" value="1" @checked(old('is_free')) class="accent-green w-5 h-5">
                </label>
              </div>
            </div>

            {{-- بخش تنظیمات اصلی فرمت و اندازه خروجی --}}
            <div class="grid grid-cols-3 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">نوع فرمت خروجی <span class="text-red">*</span></label>
                <select name="output_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="image">تصویر (Image)</option>
                  <option value="video">ویدیو (Video)</option>
                  <option value="image+video">ترکیب هر دو فرمت</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">پسوند فایل خروجی</label>
                <select name="output_format" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="jpg">jpg</option>
                  <option value="png" selected>png</option>
                  <option value="webp">webp</option>
                  <option value="mp4">mp4</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">تعداد فایل تولیدی همزمان</label>
                <input type="number" name="output_count" value="{{ old('output_count', 1) }}" min="1" max="10" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">رزولوشن و ابعاد تصویر</label>
                <select name="resolution" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="512x512">512 × 512</option>
                  <option value="1024x1024" selected>1024 × 1024 (استاندارد)</option>
                  <option value="1080x1920">1080 × 1920 (استوری)</option>
                  <option value="1920x1080">1920 × 1080 (افقی)</option>
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text2">نسبت تصویر (Aspect Ratio)</label>
                <select name="aspect_ratio" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="1:1" selected>1:1 — مربع کامل</option>
                  <option value="9:16">9:16 — عمودی موبایل</option>
                  <option value="16:9">16:9 — واید عریض</option>
                  <option value="4:5">4:5 — پست اینستاگرام</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        {{-- 
          ============================== بخش فیلدهای سیستمی و پنهان (به دستور آقا محسن) ==============================
          این فیلدها در قالب کلی سیستم و دیتابیس باقی ماندند اما کاربر را در مراحل ثبت درگیر نمی‌کنند تا ادمین خلوت بماند.
        --}}
        <div class="hidden" id="mohsen-hidden-fields-container">
          <input type="text" name="delivery_method" value="instant">
          <input type="number" name="estimated_time" value="30">
          <input type="checkbox" name="watermark_enabled" value="1" checked>
          <input type="text" name="watermark_position" value="corner">

          <input type="text" name="pricing_model" value="per_credit">
          <input type="text" name="price_tier" value="standard">
          <input type="number" name="discount_percent" value="0">

          <input type="text" name="display_mode" value="card">
          <input type="text" name="card_shape" value="portrait">
          <input type="text" name="gallery_layout" value="grid">
          <input type="text" name="card_badge" value="">
          <input type="text" name="platform" value="both">
          <input type="text" name="accent_color" value="#a07af5">
          <input type="checkbox" name="show_before_after" value="1" checked>
        </div>

        {{-- دکمه‌های ناوبری مراحل فرم (Navigation Actions) --}}
        <div class="bg-s1 border-t border-b1 px-6 py-4 flex items-center justify-between mt-6 rounded-2xl">
          <div class="text-[12px] text-text3">فرم گام به گام هوشمند وطن استودیو</div>
          <div class="flex gap-2">
            <button type="button" id="btn-prev" class="hidden inline-flex items-center gap-2 px-5 h-10 rounded-[10px] text-[13px] font-bold bg-s2 text-text2 border border-b1 hover:border-b2 hover:text-white transition-colors">
              مرحله قبل
            </button>
            <button type="button" id="btn-next" class="inline-flex items-center gap-2 px-5 h-10 rounded-[10px] text-[13px] font-bold bg-green text-white hover:bg-[#08a443] transition-colors">
              مرحله بعد <i class="fa-solid fa-arrow-left text-[11px]"></i>
            </button>
            <button type="submit" id="btn-submit" class="hidden inline-flex items-center gap-2 px-5 h-10 rounded-[10px] text-[13px] font-bold bg-accent text-white hover:bg-accent-hover transition-colors">
              <i class="fa-solid fa-check"></i> ثبت نهایی و ساخت محصول
            </button>
          </div>
        </div>

      </form>

    </main>
  </div>
</div>

{{-- اسکریپت اختصاصی و بدون باگ کنترل مراحل فرانت-اند --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentStep = 1;
    const totalSteps = 4;

    const steps = document.querySelectorAll('.wizard-step');
    const indicatorItems = document.querySelectorAll('.step-indicator-item');
    const progressBar = document.getElementById('wizard-progress-bar');
    
    const btnNext = document.getElementById('btn-next');
    const btnPrev = document.getElementById('btn-prev');
    const btnSubmit = document.getElementById('btn-submit');

    // لایو ابدیت کردن چیدمان مراحل
    function updateWizard() {
        // تغییر نمایش مرحله جاری
        steps.forEach(step => {
            if(parseInt(step.dataset.step) === currentStep) {
                step.classList.remove('hidden');
            } else {
                step.classList.add('hidden');
            }
        });

        // به‌روزرسانی استایل‌های منوی نشانگر
        indicatorItems.forEach(item => {
            const stepNum = parseInt(item.dataset.step);
            const circle = item.querySelector('.step-circle');
            const label = item.querySelector('.step-label');

            if (stepNum === currentStep) {
                circle.classList.remove('bg-s1', 'border-b1', 'text-text3', 'bg-green', 'border-green', 'text-white');
                circle.classList.add('bg-accent', 'border-accent', 'text-white');
                label.classList.add('text-white');
                label.classList.remove('text-text3');
            } else if (stepNum < currentStep) {
                circle.classList.remove('bg-s1', 'border-b1', 'text-text3', 'bg-accent', 'border-accent', 'text-white');
                circle.classList.add('bg-green', 'border-green', 'text-white');
                label.classList.add('text-text2');
                label.classList.remove('text-text3', 'text-white');
            } else {
                circle.classList.remove('bg-accent', 'border-accent', 'bg-green', 'border-green', 'text-white');
                circle.classList.add('bg-s1', 'border-b1', 'text-text3');
                label.classList.add('text-text3');
                label.classList.remove('text-white', 'text-text2');
            }
        });

        // طول هندسی نوار وضعیت بالا
        const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = progressPercentage + '%';

        // دکمه‌های ناوبری پائین صفحه
        if (currentStep === 1) {
            btnPrev.classList.add('hidden');
        } else {
            btnPrev.classList.remove('hidden');
        }

        if (currentStep === totalSteps) {
            btnNext.classList.add('hidden');
            btnSubmit.classList.remove('hidden');
        } else {
            btnNext.classList.remove('hidden');
            btnSubmit.classList.add('hidden');
        }
    }

    // ایونت کلیک دکمه رفتن به مرحله بعد
    btnNext.addEventListener('click', function () {
        // اعتبار سنجی مقدماتی فیلدهای اجباری هر مرحله قبل از خروج
        const activeStepFields = steps[currentStep - 1].querySelectorAll('[required]');
        let stepValid = true;
        
        activeStepFields.forEach(field => {
            if(!field.checkValidity()) {
                field.reportValidity();
                stepValid = false;
            }
        });

        if (stepValid && currentStep < totalSteps) {
            currentStep++;
            updateWizard();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // ایونت کلیک دکمه بازگشت به مرحله قبل
    btnPrev.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // سوییچ اتوماتیک ساب کاتگوری بر اساس دسته انتخابی
    const categorySelect = document.getElementById('category-select');
    const subcategorySelect = document.getElementById('subcategory-select');
    const subOptions = Array.from(subcategorySelect.options);

    categorySelect.addEventListener('change', function () {
        const selectedCat = this.value;
        subcategorySelect.innerHTML = '<option value="">انتخاب زیردسته</option>';
        
        subOptions.forEach(opt => {
            if (opt.dataset.parent === selectedCat) {
                subcategorySelect.appendChild(opt);
            }
        });
    });

    // منطق چک‌باکس رایگان: غیرفعال کردن پویای اینپوت توکن
    const isFreeCheckbox = document.getElementById('is_free_checkbox');
    const creditCostInput = document.getElementById('credit_cost_input');

    isFreeCheckbox.addEventListener('change', function () {
        if (this.checked) {
            creditCostInput.value = 0;
            creditCostInput.disabled = true;
            creditCostInput.classList.add('opacity-50');
        } else {
            creditCostInput.value = 5;
            creditCostInput.disabled = false;
            creditCostInput.classList.remove('opacity-50');
        }
    });

    // مقداردهی اولیه فیلتر کتگوری در زمان لود لایو
    if(categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection