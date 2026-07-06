@extends('layouts.admin')
@section('title', 'ثبت پلن پیشرفته جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content flex-1 p-6 max-w-5xl w-full mx-auto overflow-y-auto max-[768px]:p-[18px]" id="content">
      <div class="mb-6">
        <h2 class="text-base font-bold text-white flex items-center gap-2 border-b border-[#222230] pb-3">
          <i class="fa-solid fa-gem text-[#a07af5]"></i>
          پیکربندی و انتشار پلن اشتراکی و شارژ توکن جدید
        </h2>
      </div>

      {{-- نمایش خطاهای ولیدیشن --}}
      @if ($errors->any())
        <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-lg text-xs">
          <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('admin.plans.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        <div class="lg:col-span-2 space-y-5">
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-6 shadow-xl space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">نام پلن <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] transition-colors"
                       placeholder="مثال: پلن الماس (پیشرفته)">
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">اسلاگ آدرس انگلیسی (Slug) <span class="text-rose-500">*</span></label>
                <input type="text" name="slug" value="{{ old('slug') }}" required
                       class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] font-mono ltr text-left"
                       placeholder="مثال: diamond-plan">
                <p class="text-[10px] text-gray-500">کد یکتای هوشمند و کوتاه سیستم، خودکار بر اساس این فیلد ساخته می‌شود.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">قیمت پلن <span class="text-rose-500">*</span></label>
                <div class="relative flex items-center">
                  <input type="text" name="price" value="{{ old('price') }}" required
                         class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 pl-14 text-xs text-white outline-none focus:border-[#a07af5] w-full font-mono text-left ltr"
                         placeholder="450,000" oninput="formatPriceWithCommas(this)">
                  <span class="absolute right-3 text-[11px] text-gray-500 pointer-events-none">تومان</span>
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">تعداد توکن‌ها <span class="text-rose-500">*</span></label>
                <input type="number" name="tokens" value="{{ old('tokens') }}" required min="1"
                       class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] font-mono text-left ltr"
                       placeholder="1500">
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">توضیح کوتاه روی کارت</label>
              <input type="text" name="short_description" value="{{ old('short_description') }}"
                     class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5]"
                     placeholder="توضیح سریع و خلاصه برای نمایش روی کارت اصلی پلن...">
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">توضیحات تکمیلی و کامل</label>
              <textarea name="description" rows="4" class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] resize-none" placeholder="شرح کامل جزئیات پکیج اشتراکی...">{{ old('description') }}</textarea>
            </div>
          </div>

          <div class="bg-[#111116] border border-[#222230] rounded-xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-[#222230] pb-2 mb-2">
              <h3 class="text-xs font-bold text-[#a07af5]">سطرهای امکانات و ویژگی‌های پلن</h3>
              <button type="button" onclick="addNewFeatureLine()" class="px-3 py-1 bg-[#a07af5]/10 border border-[#a07af5]/20 text-[#a07af5] text-[11px] rounded-md hover:bg-[#a07af5]/20 transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-plus text-[9px]"></i> سطر جدید
              </button>
            </div>

            <div id="features-container" class="space-y-2.5">
              <div class="flex items-center gap-2 feature-row">
                <input type="text" name="features[]" required class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] flex-1" placeholder="مثال: اولویت پردازش هوشمند در صف تولید تصویر">
                <button type="button" onclick="removeFeatureLine(this)" class="w-9 h-9 border border-[#222230] text-gray-500 hover:text-rose-400 hover:border-rose-500/20 bg-[#16161c] rounded-lg transition-colors flex items-center justify-center text-xs"><i class="fa-solid fa-trash"></i></button>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-5">
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-6 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-[#a07af5] border-b border-[#222230] pb-2 mb-2">ظاهر و استایل رسانه‌ای</h3>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">تصویر کاور اصلی <span class="text-rose-500">*</span></label>
              <div class="border border-dashed border-[#333345] bg-[#16161c] hover:bg-[#1b1b24] rounded-lg p-4 transition-colors relative cursor-pointer group flex flex-col items-center justify-center text-center">
                <input type="file" name="image" accept="image/*" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewImageFile(this)">
                <i class="fa-regular fa-image text-2xl text-gray-500 group-hover:text-[#a07af5] mb-2 transition-colors"></i>
                <span id="file-chosen" class="text-xs text-gray-400">انتخاب تصویر کاور پلن</span>
                <img id="img-preview" class="hidden mt-3 max-h-32 rounded-lg border border-[#222230] object-contain">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">آیکون پلن</label>
                <input type="text" name="icon" value="fa-solid fa-gem" class="bg-[#16161c] border border-[#222230] rounded-lg p-2 text-xs text-white outline-none focus:border-[#a07af5] font-mono ltr text-left">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">رنگ اختصاصی کارت</label>
                <div class="flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-lg p-1.5">
                  <input type="color" name="card_color" value="#a07af5" class="bg-none border-none w-7 h-7 cursor-pointer rounded-md">
                  <span class="text-[11px] text-gray-400 font-mono">پایه استایل</span>
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">متن بج برجسته (Badge)</label>
              <input type="text" name="badge_text" value="{{ old('badge_text') }}" class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5]" placeholder="مانند: پرفروش، ۲۰٪ هدیه">
            </div>
          </div>

          <div class="bg-[#111116] border border-[#222230] rounded-xl p-6 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-[#a07af5] border-b border-[#222230] pb-2 mb-2">تنظیمات انتشار و مدیریت</h3>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">انتخاب برچسب‌ها (Tags)</label>
              <div class="grid grid-cols-2 gap-2 bg-[#16161c] border border-[#222230] rounded-lg p-3">
                <label class="flex items-center gap-1.5 text-xs text-gray-300 cursor-pointer"><input type="checkbox" name="tags[]" value="جدید" class="accent-[#a07af5]"> جدید</label>
                <label class="flex items-center gap-1.5 text-xs text-gray-300 cursor-pointer"><input type="checkbox" name="tags[]" value="پرفروش" class="accent-[#a07af5]"> پرفروش</label>
                <label class="flex items-center gap-1.5 text-xs text-gray-300 cursor-pointer"><input type="checkbox" name="tags[]" value="اقتصادی" class="accent-[#a07af5]"> اقتصادی</label>
                <label class="flex items-center gap-1.5 text-xs text-gray-300 cursor-pointer"><input type="checkbox" name="tags[]" value="محبوب" class="accent-[#a07af5]"> محبوب</label>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">ترتیب چیدمان نمایش</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" required min="0" class="bg-[#16161c] border border-[#222230] rounded-lg p-2 text-xs text-white outline-none focus:border-[#a07af5] font-mono ltr text-left">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400">وضعیت پیش‌فرض</label>
                <select name="status" class="bg-[#16161c] border border-[#222230] rounded-lg p-2 text-xs text-gray-300 outline-none cursor-pointer focus:border-[#a07af5]">
                  <option value="active" selected>فعال (Active)</option>
                  <option value="draft">پیش‌نویس (Draft)</option>
                  <option value="inactive">غیرفعال (Inactive)</option>
                </select>
              </div>
            </div>

            <div class="pt-4 border-t border-[#222230] flex justify-end">
              <button type="submit" class="px-5 h-9 rounded-lg text-xs font-semibold bg-[#a07af5] text-[#0c0c10] hover:bg-[#8f68e0] transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-check text-[11px]"></i>
                ذخیره نهایی پلن جدید
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  // تابع فرمت‌کننده با حفظ موقعیت مکان‌نما
  function formatPriceWithCommas(input) {
      // ذخیره موقعیت مکان‌نما
      const cursorPos = input.selectionStart;
      // حذف همه کاراکترهای غیرعددی
      let raw = input.value.replace(/\D/g, '');
      // اگر خالی بود، مقدار را خالی کن
      if (raw === '') {
          input.value = '';
          return;
      }
      // تبدیل به عدد با کاما
      let formatted = raw.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      input.value = formatted;

      // تنظیم مجدد مکان‌نما (با توجه به تعداد کاماهای اضافه شده)
      let newCursorPos = cursorPos;
      // اگر کاربر در حال تایپ در وسط متن باشد، موقعیت را تنظیم می‌کنیم
      // اما برای سادگی، مکان‌نما را به انتهای مقدار منتقل می‌کنیم (که راحت‌تر است)
      // اگر می‌خواهید مکان‌نما حفظ شود، می‌توانید محاسبه کنید، اما معمولاً کاربر در انتها تایپ می‌کند.
      input.setSelectionRange(input.value.length, input.value.length);
  }

  // تابع حذف کاما برای ارسال به سرور
  function stripCommas(value) {
      return value.replace(/,/g, '');
  }

  // سایر توابع قبلی (پیش‌نمایش تصویر، افزودن/حذف ویژگی) بدون تغییر
  function previewImageFile(input) {
      const fileChosen = document.getElementById('file-chosen');
      const imgPreview = document.getElementById('img-preview');
      if (input.files && input.files[0]) {
          fileChosen.textContent = 'فایل انتخاب شد: ' + input.files[0].name;
          const reader = new FileReader();
          reader.onload = function(e) {
              imgPreview.src = e.target.result;
              imgPreview.classList.remove('hidden');
          }
          reader.readAsDataURL(input.files[0]);
      }
  }

  function addNewFeatureLine() {
      const container = document.getElementById('features-container');
      const newRow = document.createElement('div');
      newRow.className = 'flex items-center gap-2 feature-row';
      newRow.innerHTML = `
          <input type="text" name="features[]" required class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] flex-1" placeholder="ویژگی و امکان جدید پلن...">
          <button type="button" onclick="removeFeatureLine(this)" class="w-9 h-9 border border-[#222230] text-gray-500 hover:text-rose-400 hover:border-rose-500/20 bg-[#16161c] rounded-lg transition-colors flex items-center justify-center text-xs"><i class="fa-solid fa-trash"></i></button>
      `;
      container.appendChild(newRow);
  }

  function removeFeatureLine(btn) {
      const container = document.getElementById('features-container');
      if (container.getElementsByClassName('feature-row').length > 1) {
          btn.closest('.feature-row').remove();
      } else {
          alert('پلن باید حداقل شامل یک ویژگی باشد.');
      }
  }

  // ====== اتصال رویدادها پس از بارگذاری کامل DOM ======
  document.addEventListener('DOMContentLoaded', function() {
      const priceInput = document.querySelector('input[name="price"]');
      if (!priceInput) return;

      // ۱. فرمت مقدار اولیه (اگر از قبل مقداری داشته باشد)
      if (priceInput.value) {
          formatPriceWithCommas(priceInput);
      }

      // ۲. اتصال رویداد input برای فرمت‌کردن زنده هنگام تایپ
      priceInput.addEventListener('input', function() {
          formatPriceWithCommas(this);
      });

      // ۳. هنگام ارسال فرم، کاماها را حذف کن
      const form = priceInput.closest('form');
      if (form) {
          form.addEventListener('submit', function() {
              priceInput.value = stripCommas(priceInput.value);
          });
      }
  });
</script>
@endpush