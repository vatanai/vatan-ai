@extends('layouts.admin')
@section('title', 'ثبت پلن جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    {{-- هدر صفحه --}}
    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="#" class="text-[#a8c4a8] hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">افزودن پلن فروشگاهی جدید</span>
      </div>
    </header>

    {{-- بدنه فرم --}}
    <main class="flex-1 p-6 max-w-3xl w-full mx-auto">
      <div class="bg-[#111116] border border-[#222230] rounded-xl p-6 shadow-xl">
        <h2 class="text-base font-bold text-white mb-6 flex items-center gap-2 border-b border-[#222230] pb-3">
          <i class="fa-solid fa-gem text-[#a07af5]"></i>
          مشخصات پلن شارژ توکن
        </h2>

        {{-- نمایش ارورهای ولیدیشن در صورت وجود --}}
        @if ($errors->any())
          <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-lg text-xs">
            <ul class="list-disc list-inside space-y-1">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('admin.plans.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
          @csrf

          {{-- ردیف اول: نام پلن --}}
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">نام پلن <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] transition-colors"
                   placeholder="مثال: پلن برنزی (اقتصادی)">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- فیلد قیمت با کاما و پسوند تومان --}}
            <div class="flex flex-col gap-1.5 relative">
              <label class="text-xs font-semibold text-gray-400">قیمت پلن <span class="text-rose-500">*</span></label>
              <div class="relative flex items-center">
                <input type="text" id="price_input" name="price" value="{{ old('price') }}" required
                       class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 pl-14 text-xs text-white outline-none focus:border-[#a07af5] transition-colors w-full font-mono text-left ltr"
                       placeholder="150,000" oninput="formatPriceWithCommas(this)">
                <span class="absolute right-3 text-[11px] text-gray-500 pointer-events-none">تومان</span>
              </div>
            </div>

            {{-- فیلد تعداد توکن‌ها --}}
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-400">تعداد توکن‌های پلن <span class="text-rose-500">*</span></label>
              <input type="number" name="tokens" value="{{ old('tokens') }}" required min="1"
                     class="bg-[#16161c] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] transition-colors font-mono text-left ltr"
                     placeholder="500">
            </div>
          </div>

          {{-- بخش آپلود تصویر پلن --}}
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-400">تصویر کاور / آیکون پلن <span class="text-rose-500">*</span></label>
            <div class="border border-dashed border-[#333345] bg-[#16161c] hover:bg-[#1b1b24] rounded-lg p-4 transition-colors relative cursor-pointer group flex flex-col items-center justify-center text-center">
              <input type="file" name="image" accept="image/*" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewImageFile(this)">
              <i class="fa-regular fa-image text-2xl text-gray-500 group-hover:text-[#a07af5] mb-2 transition-colors"></i>
              <span id="file-chosen" class="text-xs text-gray-400">کلیک کنید یا فایل تصویر را به اینجا بکشید</span>
              <span class="text-[10px] text-gray-600 mt-1">فرمت‌های مجاز: PNG, JPG, WEBP (حداکثر ۵ مگابایت)</span>
              {{-- باکس پیش‌نمایش تصویر قبل آپلود --}}
              <img id="img-preview" class="hidden mt-3 max-h-32 rounded-lg border border-[#222230] object-contain">
            </div>
          </div>

          {{-- وضعیت فعال بودن پلن --}}
          <div class="flex items-center gap-2 pt-2">
            <input type="checkbox" name="is_active" id="is_active" checked value="1" class="accent-[#a07af5] w-4 h-4 cursor-pointer">
            <label Security for="is_active" class="text-xs font-semibold text-gray-300 cursor-pointer select-none">این پلن بلافاصله در سایت فعال و قابل خرید باشد.</label>
          </div>

          {{-- دکمه‌های عملیاتی انتهای فرم --}}
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#222230] mt-6">
            <button type="submit" class="px-5 h-9 rounded-lg text-xs font-semibold bg-[#a07af5] text-[#0c0c10] hover:bg-[#8f68e0] transition-colors flex items-center gap-1.5">
              <i class="fa-solid fa-check text-[11px]"></i>
              ذخیره و ثبت پلن
            </button>
          </div>

        </form>
      </div>
    </main>

  </div>
</div>
@endsection

@push('scripts')
<script>
  // تابع تفکیک ۳ رقم ۳ رقم مبالغ با کاما هنگام تایپ کاربر
  function formatPriceWithCommas(input) {
      // حذف هر کاراکتری به جز اعداد
      let value = input.value.replace(/\D/g, '');
      // فرمت دهی ۳ رقم ۳ رقم با کاما
      input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  // نمایش نام فایل انتخاب شده و پیش نمایش زنده تصویر روی مروگر کاربر
  function previewImageFile(input) {
      const fileChosen = document.getElementById('file-chosen');
      const imgPreview = document.getElementById('img-preview');
      
      if (input.files && input.files[0]) {
          const file = input.files[0];
          fileChosen.textContent = 'فایل انتخاب شده: ' + file.name;
          
          const reader = new FileReader();
          reader.onload = function(e) {
              imgPreview.src = e.target.result;
              imgPreview.classList.remove('hidden');
          }
          reader.readAsDataURL(file);
      } else {
          fileChosen.textContent = 'کلیک کنید یا فایل تصویر را به اینجا بکشید';
          imgPreview.classList.add('hidden');
      }
  }
</script>
@endpush