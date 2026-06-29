@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-bg text-white" dir="rtl">

  @include('admin.partials.sidebar')

  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    {{-- Header --}}
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

    <main class="px-6 py-6 flex-1">

      <div class="mb-5">
        <div class="text-xl font-extrabold tracking-tight mb-1">ثبت محصول جدید</div>
        <div class="text-[13px] text-text3">تمام اطلاعات محصول را وارد کرده و در پایین ثبت کنید</div>
      </div>

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

      <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        {{-- ══════════ هویت محصول ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-fingerprint text-accent"></i> هویت محصول
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">نام فارسی <span class="text-red">*</span></label>
              <input type="text" name="name_fa" value="{{ old('name_fa') }}" required
                     class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent"
                     placeholder="مثال: عکس حرفه‌ای لینکدین">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">نام انگلیسی <span class="text-red">*</span></label>
              <input type="text" name="name_en" dir="ltr" value="{{ old('name_en') }}" required
                     class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent"
                     placeholder="LinkedIn Professional Headshot">
            </div>
          </div>

          <div class="mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">آدرس URL (Slug) <span class="text-red">*</span></label>
              <input type="text" name="slug" dir="ltr" value="{{ old('slug') }}" required
                     class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent"
                     placeholder="linkedin-professional-headshot">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">توضیح فارسی</label>
              <textarea name="description_fa" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white min-h-[90px] leading-relaxed focus:outline-none focus:border-accent" placeholder="توضیح کوتاهی از محصول برای کاربر...">{{ old('description_fa') }}</textarea>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">توضیح انگلیسی</label>
              <textarea name="description_en" dir="ltr" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left min-h-[90px] leading-relaxed focus:outline-none focus:border-accent" placeholder="Short product description for users...">{{ old('description_en') }}</textarea>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">دسته‌بندی <span class="text-red">*</span></label>
              <select name="category" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="">انتخاب کنید</option>
                @foreach ($categories as $key => $label)
                  <option value="{{ $key }}" @selected(old('category') == $key)>{{ $key }} — {{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">زیردسته</label>
              <select name="subcategory" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="">ابتدا دسته را انتخاب کنید</option>
                @foreach ($subcategoriesMap as $catKey => $subs)
                  @foreach ($subs as $sub)
                    <option value="{{ $sub }}" data-parent="{{ $catKey }}" @selected(old('subcategory') == $sub)>{{ $sub }}</option>
                  @endforeach
                @endforeach
              </select>
              <div class="text-[10px] text-text3">زیردسته‌های مرتبط با دسته‌ی انتخابی نمایش داده می‌شود</div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">وضعیت <span class="text-red">*</span></label>
              <select name="status" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="draft" @selected(old('status', 'draft') == 'draft')>پیش‌نویس (draft)</option>
                <option value="active" @selected(old('status') == 'active')>فعال (active)</option>
                <option value="inactive" @selected(old('status') == 'inactive')>غیرفعال (inactive)</option>
              </select>
            </div>
          </div>

          <div class="mb-3.5">
            <label class="text-xs font-semibold text-text2">تگ‌های جستجو <span class="text-[10px] text-text3 font-normal">با کاما (,) از هم جدا کنید</span></label>
            <input type="text" name="tags" dir="rtl" value="{{ old('tags') }}"
                   class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white w-full mt-1.5 focus:outline-none focus:border-accent"
                   placeholder="مثال: لینکدین, پرسنال برندینگ, عکس حرفه‌ای">
          </div>

          <div class="grid grid-cols-3 gap-3.5">
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">محصول ویژه</div>
                <div class="text-[11px] text-text3">نمایش در صفحه اول</div>
              </div>
              <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="accent-green w-4 h-4">
            </label>
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">برچسب «جدید»</div>
                <div class="text-[11px] text-text3">is_new</div>
              </div>
              <input type="checkbox" name="is_new" value="1" @checked(old('is_new', true)) class="accent-green w-4 h-4">
            </label>
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">ترند</div>
                <div class="text-[11px] text-text3">is_trending</div>
              </div>
              <input type="checkbox" name="is_trending" value="1" @checked(old('is_trending')) class="accent-green w-4 h-4">
            </label>
          </div>
        </div>

        {{-- ══════════ رسانه نمایشی ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-images text-accent"></i> رسانه نمایشی
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">تصویر کارت (Thumbnail) <span class="text-red">*</span></label>
              <div class="border-[1.5px] border-dashed border-b2 rounded-xl px-4 py-5 text-center bg-s1 hover:border-accent transition-colors">
                <input type="file" name="thumbnail" accept="image/*" class="block w-full text-[12px] text-text2 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent/10 file:text-accent file:text-[11px] file:font-semibold">
                <div class="text-[11px] text-text3 mt-2">PNG, JPG — حداقل ۶۰۰×۶۰۰px</div>
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">تصویر Cover</label>
              <div class="border-[1.5px] border-dashed border-b2 rounded-xl px-4 py-5 text-center bg-s1 hover:border-accent transition-colors">
                <input type="file" name="cover" accept="image/*" class="block w-full text-[12px] text-text2 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent/10 file:text-accent file:text-[11px] file:font-semibold">
                <div class="text-[11px] text-text3 mt-2">PNG, JPG — ۱۴۰۰×۶۰۰px</div>
              </div>
            </div>
          </div>

          <div class="mb-3.5">
            <label class="text-xs font-semibold text-text2">نمونه خروجی‌ها <span class="text-[10px] text-text3 font-normal">حداکثر ۱۰ تصویر</span></label>
            <input type="file" name="sample_images[]" accept="image/*" multiple
                   class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white w-full mt-1.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent/10 file:text-accent file:text-[11px] file:font-semibold focus:outline-none focus:border-accent">
          </div>

          <div class="grid grid-cols-2 gap-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">نوع رسانه</label>
              <select name="media_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="photo">photo — عکس</option>
                <option value="video">video — ویدیو</option>
                <option value="both">both — هر دو</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">لینک ویدیوی پیش‌نمایش <span class="text-[10px] text-text3 font-normal">اختیاری</span></label>
              <input type="text" name="preview_video_url" dir="ltr" value="{{ old('preview_video_url') }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent" placeholder="https://...">
            </div>
          </div>
        </div>

        {{-- ══════════ پایپ‌لاین هوش مصنوعی ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-microchip text-accent"></i> پایپ‌لاین هوش مصنوعی
          </div>

          <div class="text-[11px] font-bold text-text3 mb-2 tracking-wide">مدل اصلی (Primary)</div>
          <div class="bg-s1 border border-b1 rounded-xl px-4 py-3.5 mb-4.5">
            <select name="primary_model" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white w-full mb-2 focus:outline-none focus:border-accent">
              @foreach ($aiModels as $key => $label)
                <option value="{{ $key }}" @selected(old('primary_model') == $key)>{{ $key }} — {{ $label }}</option>
              @endforeach
            </select>
            <div class="grid grid-cols-2 gap-2">
              <input type="number" name="primary_timeout" value="{{ old('primary_timeout', 60) }}" min="1" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent" placeholder="timeout (ثانیه)">
              <select name="primary_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="image_generation" @selected(old('primary_type', 'image_generation') == 'image_generation')>image_generation</option>
                <option value="image_editing" @selected(old('primary_type') == 'image_editing')>image_editing</option>
                <option value="text_generation" @selected(old('primary_type') == 'text_generation')>text_generation</option>
              </select>
            </div>
          </div>

          <div class="text-[11px] font-bold text-text3 mb-2 tracking-wide">Fallback ها <span class="font-normal text-[10px]">(به ترتیب اجرا، حداکثر ۳ مورد)</span></div>
          <div class="grid grid-cols-3 gap-2.5">
            @for ($i = 1; $i <= 3; $i++)
              <div class="flex flex-col gap-1.5">
                <label class="text-[11px] text-text3">Fallback {{ $i }}</label>
                <select name="fallback_models[]" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                  <option value="">— انتخاب نشود —</option>
                  @foreach ($aiModels as $key => $label)
                    <option value="{{ $key }}">{{ $key }} — {{ $label }}</option>
                  @endforeach
                </select>
              </div>
            @endfor
          </div>
        </div>

        {{-- ══════════ تنظیمات پرامپت ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-terminal text-accent"></i> تنظیمات پرامپت
          </div>

          <div class="mb-3.5">
            <label class="text-xs font-semibold text-text2">Prompt Template <span class="text-red">*</span></label>
            <textarea name="prompt_template" dir="ltr" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white text-left w-full mt-1.5 min-h-[110px] font-mono focus:outline-none focus:border-accent" placeholder="A professional portrait of {gender} named {name}, wearing {clothing_style}, {background}, photorealistic, 8k, sharp focus">{{ old('prompt_template') }}</textarea>
            <div class="text-[10px] text-text3 mt-1.5">متغیرهای قابل استفاده: <span dir="ltr" class="font-mono">{name} {gender} {clothing_style} {background} {birth_date}</span></div>
          </div>

          <div class="mb-3.5">
            <label class="text-xs font-semibold text-text2">Negative Prompt <span class="text-[10px] text-text3 font-normal">اختیاری</span></label>
            <textarea name="negative_prompt" dir="ltr" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white text-left w-full mt-1.5 min-h-[60px] font-mono focus:outline-none focus:border-accent" placeholder="blurry, low quality, distorted face, cartoon, watermark">{{ old('negative_prompt') }}</textarea>
          </div>

          <div class="grid grid-cols-3 gap-3.5">
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">نمایش پرامپت به کاربر</div>
                <div class="text-[11px] text-text3">show_prompt_to_user</div>
              </div>
              <input type="checkbox" name="show_prompt_to_user" value="1" @checked(old('show_prompt_to_user')) class="accent-green w-4 h-4">
            </label>
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">Face Swap</div>
                <div class="text-[11px] text-text3">face_swap_enabled</div>
              </div>
              <input type="checkbox" name="face_swap_enabled" value="1" @checked(old('face_swap_enabled')) class="accent-green w-4 h-4">
            </label>
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">پایپ‌لاین چند مرحله‌ای</div>
                <div class="text-[11px] text-text3">multi_step_pipeline</div>
              </div>
              <input type="checkbox" name="multi_step_pipeline" value="1" @checked(old('multi_step_pipeline')) class="accent-green w-4 h-4">
            </label>
          </div>
        </div>

        {{-- ══════════ سازنده فیلدهای ورودی ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-table-list text-accent"></i> سازنده فیلدهای ورودی (Input Schema)
          </div>

          <div class="grid grid-cols-[1fr_1fr_160px_90px] gap-2.5 px-1 pb-1.5 mb-2 border-b border-b1">
            <div class="text-[10px] font-bold text-text3 uppercase tracking-wide">field_id</div>
            <div class="text-[10px] font-bold text-text3 uppercase tracking-wide">برچسب فارسی</div>
            <div class="text-[10px] font-bold text-text3 uppercase tracking-wide">نوع</div>
            <div class="text-[10px] font-bold text-text3 uppercase tracking-wide text-center">اجباری</div>
          </div>

          @php
            $defaultFields = old('field_id') ? null : [
                ['id' => 'user_photo', 'label' => 'عکس شما', 'type' => 'image_upload', 'required' => true],
                ['id' => 'name', 'label' => 'نام', 'type' => 'text', 'required' => false],
                ['id' => 'clothing_style', 'label' => 'استایل لباس', 'type' => 'select', 'required' => true],
            ];
            $rowCount = $defaultFields ? count($defaultFields) : count(old('field_id', []));
          @endphp

          @for ($i = 0; $i < max($rowCount, 1); $i++)
            @php
              $fid = $defaultFields ? $defaultFields[$i]['id'] : old('field_id')[$i] ?? '';
              $flabel = $defaultFields ? $defaultFields[$i]['label'] : old('field_label')[$i] ?? '';
              $ftype = $defaultFields ? $defaultFields[$i]['type'] : old('field_type')[$i] ?? 'text';
              $frequired = $defaultFields ? $defaultFields[$i]['required'] : isset(old('field_required')[$i]);
            @endphp
            <div class="bg-s1 border border-b1 rounded-xl px-3.5 py-3 mb-2 grid grid-cols-[1fr_1fr_160px_60px_34px] gap-2.5 items-center">
              <input type="text" name="field_id[]" dir="ltr" value="{{ $fid }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white text-left focus:outline-none focus:border-accent" placeholder="field_id">
              <input type="text" name="field_label[]" value="{{ $flabel }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent" placeholder="برچسب فارسی">
              <select name="field_type[]" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                @foreach ($inputFieldTypes as $type)
                  <option value="{{ $type }}" @selected($ftype == $type)>{{ $type }}</option>
                @endforeach
              </select>
              <label class="flex items-center justify-center">
                <input type="checkbox" name="field_required[{{ $i }}]" value="1" @checked($frequired) class="accent-green w-5 h-5">
              </label>
              <div></div>
            </div>
          @endfor

          <div class="text-[11px] text-text3 mt-1">برای افزودن فیلد بیشتر، ردیف جدید را در سرور (ادمین) اضافه کنید یا این بخش را پس از ساخت محصول از صفحه ویرایش کامل کنید.</div>
        </div>

        {{-- ══════════ تنظیمات خروجی ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-file-export text-accent"></i> تنظیمات خروجی (Output Config)
          </div>

          <div class="grid grid-cols-3 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">نوع خروجی <span class="text-red">*</span></label>
              <select name="output_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="image">image — عکس</option>
                <option value="video">video — ویدیو</option>
                <option value="image+video">image+video — هر دو</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">فرمت</label>
              <select name="output_format" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="jpg">jpg</option>
                <option value="png">png</option>
                <option value="webp">webp</option>
                <option value="mp4">mp4</option>
                <option value="webm">webm</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">تعداد خروجی</label>
              <input type="number" name="output_count" value="{{ old('output_count', 1) }}" min="1" max="10" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">رزولوشن</label>
              <select name="resolution" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="512x512">512×512</option>
                <option value="768x768">768×768</option>
                <option value="1024x1024" selected>1024×1024</option>
                <option value="1080x1920">1080×1920</option>
                <option value="1920x1080">1920×1080</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">نسبت ابعاد</label>
              <select name="aspect_ratio" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="1:1" selected>1:1 — مربع</option>
                <option value="9:16">9:16 — عمودی</option>
                <option value="16:9">16:9 — افقی</option>
                <option value="4:5">4:5 — اینستاگرام</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">روش تحویل</label>
              <select name="delivery_method" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="instant">instant — آنی</option>
                <option value="queued">queued — صف انتظار</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">زمان تخمینی (ثانیه)</label>
              <input type="number" name="estimated_time" value="{{ old('estimated_time', 30) }}" min="1" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5">
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">واترمارک</div>
                <div class="text-[11px] text-text3">watermark_enabled</div>
              </div>
              <input type="checkbox" name="watermark_enabled" value="1" @checked(old('watermark_enabled', true)) class="accent-green w-4 h-4">
            </label>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">موقعیت واترمارک</label>
              <select name="watermark_position" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="corner">corner — گوشه</option>
                <option value="center">center — وسط</option>
                <option value="none">none — بدون</option>
              </select>
            </div>
          </div>
        </div>

        {{-- ══════════ قیمت‌گذاری ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-coins text-accent"></i> قیمت‌گذاری (Pricing)
          </div>

          <div class="grid grid-cols-3 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">مدل قیمت‌گذاری <span class="text-red">*</span></label>
              <select name="pricing_model" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="per_credit">per_credit — کردیتی</option>
                <option value="free">free — رایگان</option>
                <option value="subscription_only">subscription_only — اشتراک</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">هزینه (کردیت)</label>
              <input type="number" name="credit_cost" value="{{ old('credit_cost', 5) }}" min="1" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">سطح قیمتی</label>
              <select name="price_tier" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="basic">basic — پایه</option>
                <option value="standard" selected>standard — استاندارد</option>
                <option value="premium">premium — پریمیوم</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">درصد تخفیف <span class="text-[10px] text-text3 font-normal">۰ = بدون تخفیف</span></label>
              <input type="number" name="discount_percent" value="{{ old('discount_percent', 0) }}" min="0" max="100" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
            </div>
            <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer">
              <div>
                <div class="text-[12.5px] font-semibold text-text2">رایگان (is_free)</div>
                <div class="text-[11px] text-text3">بدون نیاز به کردیت</div>
              </div>
              <input type="checkbox" name="is_free" value="1" @checked(old('is_free')) class="accent-green w-4 h-4">
            </label>
          </div>
        </div>

        {{-- ══════════ تنظیمات نمایش ══════════ --}}
        <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5.5">
          <div class="text-[13px] font-bold text-white mb-4.5 pb-3 border-b border-b1 flex items-center gap-2">
            <i class="fa-solid fa-palette text-accent"></i> تنظیمات نمایش (Display Config)
          </div>

          <div class="grid grid-cols-3 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">حالت نمایش</label>
              <select name="display_mode" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="card" selected>card — کارت</option>
                <option value="featured">featured — ویژه</option>
                <option value="minimal">minimal — ساده</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">شکل کارت</label>
              <select name="card_shape" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="portrait" selected>portrait — عمودی</option>
                <option value="landscape">landscape — افقی</option>
                <option value="square">square — مربع</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">چیدمان گالری</label>
              <select name="gallery_layout" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="grid" selected>grid — شبکه</option>
                <option value="masonry">masonry — آبشاری</option>
                <option value="carousel">carousel — اسلایدر</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3.5 mb-3.5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">برچسب کارت <span class="text-[10px] text-text3 font-normal">مثلاً: پرفروش، ویژه</span></label>
              <input type="text" name="card_badge" value="{{ old('card_badge') }}" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent" placeholder="مثال: پرفروش">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-text2">پلتفرم</label>
              <select name="platform" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:outline-none focus:border-accent">
                <option value="web">web</option>
                <option value="mobile">mobile</option>
                <option value="both" selected>both — هر دو</option>
              </select>
            </div>
          </div>

          <div class="mb-3.5">
            <label class="text-xs font-semibold text-text2">رنگ accent محصول</label>
            <div class="flex items-center gap-3 mt-1.5">
              <input type="color" name="accent_color" value="{{ old('accent_color', '#a07af5') }}" class="w-10 h-10 rounded-lg border border-b1 bg-s1 cursor-pointer">
              <span class="text-[12px] text-text3">رنگ کارت و دکمه‌های مرتبط با این محصول در اپلیکیشن</span>
            </div>
          </div>

          <label class="flex items-center justify-between px-3.5 py-2.5 bg-s1 border border-b1 rounded-lg cursor-pointer max-w-[340px]">
            <div>
              <div class="text-[12.5px] font-semibold text-text2">نمایش Before/After</div>
              <div class="text-[11px] text-text3">show_before_after slider</div>
            </div>
            <input type="checkbox" name="show_before_after" value="1" @checked(old('show_before_after', true)) class="accent-green w-4 h-4">
          </label>
        </div>

        {{-- ══════════ دکمه‌های پایانی ══════════ --}}
        <div class="bg-s1 border-t border-b1 px-6 py-3.5 flex items-center justify-between sticky bottom-0 z-40 rounded-2xl">
          <div class="text-[12px] text-text3">تمام فیلدهای دارای <span class="text-red">*</span> اجباری هستند</div>
          <div class="flex gap-2">
            <a href="{{ route('admin.products') }}" class="inline-flex items-center gap-2 px-5.5 h-10 rounded-[10px] text-[13px] font-bold bg-s2 text-text2 border border-b1 hover:border-b2 hover:text-white transition-colors">
              انصراف
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5.5 h-10 rounded-[10px] text-[13px] font-bold bg-green text-white hover:bg-[#08a443] transition-colors">
              <i class="fa-solid fa-check"></i> ثبت نهایی محصول
            </button>
          </div>
        </div>

      </form>

    </main>
  </div>
</div> 
@endsection