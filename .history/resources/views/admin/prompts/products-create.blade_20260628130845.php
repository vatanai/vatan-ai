@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — پنل مدیریت')

@section('content')
<div class="flex min-h-screen bg-bg text-white" dir="rtl">

  @include('admin.partials.sidebar')

  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    <header class="sticky top-0 z-50 bg-s1 border-b border-b1 px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-text2">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <a href="{{ route('admin.products') }}" class="hover:text-white">محصولات</a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <span class="text-white font-semibold">ثبت محصول مرحله‌ای</span>
      </div>
    </header>

    <main class="px-6 py-6 flex-1 max-w-3xl mx-auto w-full">

      <div class="bg-s2 border border-b1 rounded-2xl p-4 mb-6 flex justify-between items-center relative overflow-hidden">
        <div class="absolute top-1/2 right-12 left-12 h-0.5 bg-b1 -translate-y-1/2 z-0"></div>
        <div id="wizard-bar" class="absolute top-1/2 right-12 h-0.5 bg-accent -translate-y-1/2 z-0 transition-all duration-300" style="width: 0%;"></div>

        <div class="step-item active flex flex-col items-center gap-1.5 z-10 relative" data-step="1">
          <div class="w-8 h-8 rounded-full bg-accent text-white text-xs font-bold flex items-center justify-center step-circle">۱</div>
          <span class="text-[11px] font-bold text-white">اطلاعات و هویت</span>
        </div>
        <div class="step-item flex flex-col items-center gap-1.5 z-10 relative" data-step="2">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-text3 text-xs font-bold flex items-center justify-center step-circle">۲</div>
          <span class="text-[11px] font-bold text-text3">رسانه و دمو</span>
        </div>
        <div class="step-item flex flex-col items-center gap-1.5 z-10 relative" data-step="3">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-text3 text-xs font-bold flex items-center justify-center step-circle">۳</div>
          <span class="text-[11px] font-bold text-text3">تنظیمات هوش مصنوعی</span>
        </div>
        <div class="step-item flex flex-col items-center gap-1.5 z-10 relative" data-step="4">
          <div class="w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-text3 text-xs font-bold flex items-center justify-center step-circle">۴</div>
          <span class="text-[11px] font-bold text-text3">اعتبار و قیمت</span>
        </div>
      </div>

      <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="wizard-form">
        @csrf

        {{-- ══════════════════ گام ۱: هویت محصول ══════════════════ --}}
        <div class="wizard-pane space-y-4" data-step="1">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5 space-y-4">
            <div class="text-[13px] font-bold text-white pb-2 border-b border-b1"><i class="fa-solid fa-pen text-accent ml-1"></i> نام و دسته‌بندی محصول</div>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">نام محصول (فارسی) <span class="text-red">*</span></label>
                <input type="text" name="name_fa" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">نام محصول (انگلیسی) <span class="text-red">*</span></label>
                <input type="text" name="name_en" dir="ltr" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">آدرس پیوند (Slug) <span class="text-red">*</span></label>
              <input type="text" name="slug" dir="ltr" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none" placeholder="example-product">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">دسته‌بندی اصلی <span class="text-red">*</span></label>
                <select name="category" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
                  @foreach($categories as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                  @endforeach
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">وضعیت انتشار</label>
                <select name="status" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
                  <option value="active">فعال و نمایان</option>
                  <option value="draft">پیش‌نویس اولیه</option>
                </select>
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">توضیح کوتاه محصول <span class="text-red">*</span></label>
              <textarea name="description_fa" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white min-h-[70px] focus:border-accent focus:outline-none" placeholder="توضیحاتی جهت نمایش به کاربر..."></textarea>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">تگ‌ها <span class="text-[10px] text-text3">(جداکننده با کاما انگلیسی ,)</span></label>
              <input type="text" name="tags" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none" placeholder="آواتار, تصویر واقعی, عکاسی">
            </div>
          </div>
        </div>

        {{-- ══════════════════ گام ۲: رسانه و دمو ══════════════════ --}}
        <div class="wizard-pane space-y-4 hidden" data-step="2">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5 space-y-4">
            <div class="text-[13px] font-bold text-white pb-2 border-b border-b1"><i class="fa-solid fa-image text-accent ml-1"></i> تصاویر و بخش دمو</div>
            
            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">کاور اصلی تصویر محصول (Thumbnail) <span class="text-red">*</span></label>
              <input type="file" name="thumbnail" accept="image/*" required class="bg-s1 border border-b1 rounded-lg px-3 py-2 text-[13px] text-text2">
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">گالری تصاویر نمونه کار خروجی پلتفرم</label>
              <input type="file" name="sample_images[]" accept="image/*" multiple class="bg-s1 border border-b1 rounded-lg px-3 py-2 text-[13px] text-text2">
            </div>

            <div class="pt-2">
              <label class="flex items-center justify-between px-4 py-3 bg-s1 border border-b1 rounded-xl cursor-pointer">
                <div>
                  <div class="text-[12.5px] font-semibold text-white">نمایش اسلایدر قبل / بعد (Before & After)</div>
                  <div class="text-[10px] text-text3">نمایش تفاوت عکس خام و خروجی هوش مصنوعی به کاربر</div>
                </div>
                <input type="checkbox" name="show_before_after" value="1" checked class="accent-green w-4 h-4">
              </label>
            </div>
          </div>
        </div>

        {{-- ══════════════════ گام ۳: موتور هوش مصنوعی ══════════════════ --}}
        <div class="wizard-pane space-y-4 hidden" data-step="3">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5 space-y-4">
            <div class="text-[13px] font-bold text-white pb-2 border-b border-b1"><i class="fa-solid fa-gears text-accent ml-1"></i> هسته پردازش اختصاصی مدل</div>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">مدل AI هسته پلتفرم <span class="text-red">*</span></label>
                <select name="primary_model" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
                  @foreach($aiModels as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                  @endforeach
                </select>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs text-text2">نوع عکس ورودی مجاز <span class="text-red">*</span></label>
                <select name="media_type" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent focus:outline-none">
                  <option value="photo">تصویر پرتره چهره شخص</option>
                  <option value="product">تصویر کالا و اجسام</option>
                  <option value="all">آزاد (هر تصویری)</option>
                </select>
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">متن روی دکمه فرانت کاربری <span class="text-red">*</span></label>
              <input type="text" name="button_text" value="ساخت تصویر" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent">
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">ساختار تخصصی و قالب پرامپت (Prompt Template) <span class="text-red">*</span></label>
              <textarea name="prompt_template" dir="ltr" required class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white font-mono min-h-[80px] text-left focus:border-accent focus:outline-none" placeholder="An elegant portrait of {name}, high quality..."></textarea>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs text-text2">کلمات منفی فیلتر پرامپت (Negative Prompt)</label>
              <textarea name="negative_prompt" dir="ltr" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[12px] text-white font-mono min-h-[50px] text-left focus:border-accent focus:outline-none" placeholder="blurry, low quality, deformed"></textarea>
            </div>
          </div>
        </div>

        {{-- ══════════════════ گام ۴: قیمت‌گذاری و اعتبارسنجی ══════════════════ --}}
        <div class="wizard-pane space-y-4 hidden" data-step="4">
          <div class="bg-s2 border border-b1 rounded-2xl px-6 py-5 space-y-4">
            <div class="text-[13px] font-bold text-white pb-2 border-b border-b1"><i class="fa-solid fa-coins text-accent ml-1"></i> تنظیمات اعتبار مصرفی (توکنی فعلی)</div>
            
            <div class="bg-s1/40 p-4 border border-b1 rounded-xl grid grid-cols-2 gap-4 items-center">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-white">تعداد توکن مصرفی کاربر <span class="text-red">*</span></label>
                <input type="number" name="credit_cost" id="credit_input" value="1" min="0" required class="bg-s2 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-white focus:border-accent">
              </div>
              <div class="pt-4">
                <label class="flex items-center justify-between px-3.5 py-2.5 bg-s2 border border-b1 rounded-lg cursor-pointer">
                  <div class="text-xs text-text2">محصول کاملاً رایگان باشد</div>
                  <input type="checkbox" name="is_free" id="free_check" value="1" class="accent-green w-4 h-4">
                </label>
              </div>
            </div>

            {{-- فیلدهای درخواستی آقا محسن (صرفاً نمایش بصری در UI بدون اعمال منطقی) --}}
            <div class="pt-4 border-t border-dashed border-b1 opacity-60">
              <div class="text-[11.5px] font-bold text-accent mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-[10px]"></i> تنظیمات اشتراکی و پولی (بخش غیرفعال مصلحتی پنل)
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs text-text3">نیاز به اشتراک خاص (بزودی)</label>
                  <select disabled class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-text3 cursor-not-allowed">
                    <option value="">نیاز ندارد (بدون محدودیت اشتراک فعلی)</option>
                    <option value="vip">فقط کاربران دارای اشتراک VIP</option>
                  </select>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs text-text3">قیمت خرید تکی مستقیم - تومان (بزودی)</label>
                  <input type="text" disabled value="غیرفعال" class="bg-s1 border border-b1 rounded-lg px-3 py-2.5 text-[13px] text-text3 text-center cursor-not-allowed">
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- ناوبری گام‌های فرم --}}
        <div class="bg-s1 border-t border-b1 px-6 py-4 flex items-center justify-between mt-6 rounded-2xl">
          <div class="text-xs text-text3">سیستم ثبت مرحله‌ای پلتفرم هوش مصنوعی</div>
          <div class="flex gap-2">
            <button type="button" id="prev-btn" class="hidden px-5 h-10 rounded-lg text-xs font-bold bg-s2 text-text2 border border-b1 hover:text-white transition-colors">مرحله قبل</button>
            <button type="button" id="next-btn" class="px-5 h-10 rounded-lg text-xs font-bold bg-green text-white hover:bg-opacity-90 transition-colors">مرحله بعد</button>
            <button type="submit" id="submit-btn" class="hidden px-5 h-10 rounded-lg text-xs font-bold bg-accent text-white hover:bg-opacity-90 transition-colors">ثبت نهایی محصول</button>
          </div>
        </div>

      </form>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let current = 1;
    const total = 4;

    const steps = document.querySelectorAll('.wizard-pane');
    const stepItems = document.querySelectorAll('.step-item');
    const bar = document.getElementById('wizard-bar');
    const btnNext = document.getElementById('next-btn');
    const btnPrev = document.getElementById('prev-btn');
    const btnSubmit = document.getElementById('submit-btn');

    function updateView() {
        steps.forEach(s => s.classList.add('hidden'));
        document.querySelector(`.wizard-pane[data-step="${current}"]`).classList.remove('hidden');

        stepItems.forEach(item => {
            const stepNum = parseInt(item.dataset.step);
            const circle = item.querySelector('.step-circle');
            if(stepNum === current) {
                circle.className = "w-8 h-8 rounded-full bg-accent text-white text-xs font-bold flex items-center justify-center step-circle";
                item.querySelector('span').className = "text-[11px] font-bold text-white";
            } else if(stepNum < current) {
                circle.className = "w-8 h-8 rounded-full bg-green text-white text-xs font-bold flex items-center justify-center step-circle";
                item.querySelector('span').className = "text-[11px] font-bold text-text2";
            } else {
                circle.className = "w-8 h-8 rounded-full bg-s1 border-2 border-b1 text-text3 text-xs font-bold flex items-center justify-center step-circle";
                item.querySelector('span').className = "text-[11px] font-bold text-text3";
            }
        });

        bar.style.width = ((current - 1) / (total - 1)) * 100 + '%';

        btnPrev.classList.toggle('hidden', current === 1);
        if(current === total) {
            btnNext.classList.add('hidden');
            btnSubmit.classList.remove('hidden');
        } else {
            btnNext.classList.remove('hidden');
            btnSubmit.classList.add('hidden');
        }
    }

    btnNext.addEventListener('click', function() {
        const requiredFields = steps[current - 1].querySelectorAll('[required]');
        let valid = true;
        requiredFields.forEach(f => { if(!f.checkValidity()) { f.reportValidity(); valid = false; } });
        
        if(valid && current < total) { current++; updateView(); }
    });

    btnPrev.addEventListener('click', function() { if(current > 1) { current--; updateView(); } });

    // منطق سوئیچ پویای کاملاً رایگان
    const freeCheck = document.getElementById('free_check');
    const creditInput = document.getElementById('credit_input');
    freeCheck.addEventListener('change', function() {
        if(this.checked) {
            creditInput.value = 0;
            creditInput.disabled = true;
        } else {
            creditInput.value = 1;
            creditInput.disabled = false;
        }
    });
});
</script>
@endsection