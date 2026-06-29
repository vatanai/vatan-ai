@extends('layouts.admin')
@section('title', 'مدیریت محصولات هوش مصنوعی — وطن استودیو')

@section('content')
<div class="flex min-h-screen bg-bg text-white" dir="rtl">

  {{-- نوار کناری پنل --}}
  @include('admin.partials.sidebar')

  {{-- محتوای اصلی سمت چپ --}}
  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    {{-- هدر بالای صفحه لیست --}}
    <header class="sticky top-0 z-50 bg-s1 border-b border-b1 px-6 h-14 flex items-center justify-between">
      <div class="flex items-center gap-1.5 text-xs text-text2">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <span class="text-white font-semibold">لیست محصولات هوش مصنوعی</span>
      </div>
      
      {{-- دکمه انتقال به فرم ساخت مرحله‌ای محصول --}}
      <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl text-xs font-bold bg-accent text-white hover:bg-opacity-90 transition-all shadow-sm shadow-accent/10">
        <i class="fa-solid fa-plus text-[10px]"></i> ثبت محصول جدید
      </a>
    </header>

    {{-- بخش اصلی محتوای جدول --}}
    <main class="p-6 flex-1 w-full max-w-6xl mx-auto">

      {{-- باکس و جدول هوشمند لیست محصولات --}}
      <div class="bg-s2 border border-b1 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-b1 flex items-center justify-between bg-s1/30">
          <div class="text-sm font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-accent"></i> محصولات ثبت شده در سیستم
          </div>
          <div class="text-xs text-text3 bg-s1 border border-b1 px-2.5 py-1 rounded-lg">تعداد کل: {{ $products->total() }} مورد</div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-right border-collapse">
            <thead>
              <tr class="border-b border-b1 bg-s1/60 text-text2 text-[11px] font-bold uppercase tracking-wider">
                <th class="px-6 py-3.5 w-20">کاور دمو</th>
                <th class="px-6 py-3.5">نام و شناسنامه محصول</th>
                <th class="px-6 py-3.5">دسته‌بندی اصلی</th>
                <th class="px-6 py-3.5">هسته موتور AI</th>
                <th class="px-6 py-3.5 text-center">هزینه (توکن)</th>
                <th class="px-6 py-3.5 text-center">وضعیت</th>
                <th class="px-6 py-3.5 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-b1 text-[12.5px]">
              @forelse($products as $product)
                <tr class="hover:bg-s1/20 transition-colors duration-150">
                  
                  {{-- تصویر بندانگشتی محصول (Thumbnail) --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    @if($product->thumbnail_path)
                      <img src="{{ asset('storage/' . $product->thumbnail_path) }}" alt="{{ $product->name_fa }}" class="w-11 h-11 rounded-xl object-cover border border-b1 bg-s1">
                    @else
                      <div class="w-11 h-11 rounded-xl bg-s1 border border-b1 flex items-center justify-center text-text3 text-[9px] text-center font-medium leading-tight">بدون کاور</div>
                    @endif
                  </td>
                  
                  {{-- نام فارسی و انگلیسی محصول --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-white text-[13px]">{{ $product->name_fa }}</div>
                    <div class="text-[10.5px] text-text3 font-mono mt-0.5" dir="ltr">{{ $product->name_en }}</div>
                  </td>
                  
                  {{-- دسته‌بندی محصول --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-lg bg-s1 border border-b1 text-text2 text-[11px] font-medium">
                      {{ $product->category }}
                    </span>
                  </td>
                  
                  {{-- مدل هوش مصنوعی اصلی --}}
                  <td class="px-6 py-4 whitespace-nowrap font-mono text-[11px] text-accent" dir="ltr">
                    {{ $product->primary_model }}
                  </td>
                  
                  {{-- تعداد توکن مصرفی یا وضعیت رایگان --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center font-bold">
                    @if($product->is_free)
                      <span class="text-green text-[11px] bg-green/10 px-2.5 py-0.5 rounded-full border border-green/20">رایگان</span>
                    @else
                      <span class="text-amber-400 font-mono text-[13px]">{{ $product->credit_cost }}</span> 
                      <span class="text-[10px] text-text3 font-normal mr-0.5">توکن</span>
                    @endif
                  </td>
                  
                  {{-- وضعیت فعال / پیش‌نویس --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    @if($product->status === 'active')
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-green bg-green/10 px-2.5 py-0.5 rounded-full border border-green/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-green animate-pulse"></span> فعال
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-text3 bg-s1 px-2.5 py-0.5 rounded-full border border-b1">
                        <span class="w-1.5 h-1.5 rounded-full bg-text3"></span> پیش‌نویس
                      </span>
                    @endif
                  </td>
                  
                  {{-- ابزارهای مدیریت رکورد --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                      {{-- دکمه ویرایش محصول --}}
                      <a href="{{ route('admin.products.edit', $product) }}" class="w-8 h-8 rounded-lg bg-s1 hover:bg-b1 text-text2 hover:text-white flex items-center justify-center border border-b1 transition-colors" title="ویرایش محصول">
                        <i class="fa-solid fa-pen-to-square text-[11.5px]"></i>
                      </a>
                      
                      {{-- فرم حذف امن محصول --}}
                      <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('آیا از حذف دائمی این محصول هوش مصنوعی مطمئن هستید؟')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 rounded-lg bg-s1 hover:bg-red/20 text-text2 hover:text-red flex items-center justify-center border border-b1 transition-colors" title="حذف محصول">
                          <i class="fa-solid fa-trash-can text-[11.5px]"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                {{-- وضعیت خالی بودن دیتابیس محصولات --}}
                <tr>
                  <td colspan="7" class="px-6 py-14 text-center text-text3">
                    <div class="flex flex-col items-center justify-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-s1 border border-b1 flex items-center justify-center text-text3">
                        <i class="fa-solid fa-folder-open text-xl"></i>
                      </div>
                      <div class="text-xs font-semibold text-text2">هیچ محصول هوش مصنوعی یافت نشد!</div>
                      <p class="text-[11px] text-text3 max-w-[280px] mx-auto leading-relaxed">در حال حاضر دیتابیسی از ابزارها ندارید. جهت تست و راه‌اندازی، اولین محصول خود را ایجاد کنید.</p>
                      <a href="{{ route('admin.products.create') }}" class="mt-2 inline-flex items-center gap-1 text-xs text-accent font-bold hover:underline">
                        ساخت اولین محصول پلتفرم <i class="fa-solid fa-arrow-left text-[10px]"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- پجینیشن آدرس‌دهی لاراول --}}
        @if($products->hasPages())
          <div class="px-6 py-4 border-t border-b1 bg-s1/10 flex justify-center" dir="ltr">
            {{ $products->links() }}
          </div>
        @endif

      </div>
    </main>
  </div>
</div>

{{-- ══════════════════ مودال هوشمند و اختصاصی موفقیت ══════════════════ --}}
@if(session('success'))
  <div id="watan-success-modal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-100" onclick="closeWatanModal(event)">
    
    {{-- بدنه اصلی مودال --}}
    <div class="bg-s2 border border-b1 w-full max-w-md rounded-2xl p-6 shadow-2xl shadow-black/90 transform scale-100 transition-transform duration-300 relative overflow-hidden" onclick="event.stopPropagation()">
      
      {{-- پترن نوری پشت آیکون --}}
      <div class="absolute -top-12 -left-12 w-32 h-32 bg-green/10 rounded-full blur-3xl pointer-events-none"></div>
      
      {{-- دکمه ضربدر گوشه بالا --}}
      <button onclick="hideWatanModal()" class="absolute top-4 left-4 w-7 h-7 flex items-center justify-center rounded-lg bg-s1 border border-b1 text-text3 hover:text-white transition-colors">
        <i class="fa-solid fa-xmark text-xs"></i>
      </button>

      {{-- آیکون متحرک تایید سبز رنگ --}}
      <div class="flex flex-col items-center text-center mt-2">
        <div class="w-16 h-16 rounded-full bg-green/10 border border-green/20 flex items-center justify-center text-green mb-4 shadow-[0_0_20px_rgba(11,191,83,0.15)] animate-pulse">
          <i class="fa-solid fa-circle-check text-3xl"></i>
        </div>

        {{-- تیتر مودال --}}
        <h3 class="text-sm font-extrabold text-white mb-2 tracking-tight">عملیات موفقیت‌آمیز</h3>
        
        {{-- متن داینامیک ارسالی از Session لاراول --}}
        <p class="text-[12px] text-text2 leading-relaxed max-w-[320px] bg-s1/40 border border-b1/60 p-3 rounded-xl w-full mt-2 font-medium">
          {{ session('success') }}
        </p>

        {{-- دکمه خروج و تایید نهایی --}}
        <button onclick="hideWatanModal()" class="mt-6 w-full h-10 rounded-xl bg-accent hover:bg-opacity-90 text-white text-xs font-bold transition-all shadow-md shadow-accent/10 flex items-center justify-center gap-1.5">
          متوجه شدم <i class="fa-solid fa-check text-[10px]"></i>
        </button>
      </div>

    </div>
  </div>

  {{-- اسکریپت سبک کنترل و بستن مودال --}}
  <script>
    function hideWatanModal() {
      const modal = document.getElementById('watan-success-modal');
      if(modal) {
        modal.classList.add('opacity-0');
        setTimeout(() => {
          modal.remove();
        }, 250);
      }
    }

    function closeWatanModal(event) {
      // بستن مودال در صورت کلیک روی فضای تاریک بیرون
      hideWatanModal();
    }
    
    // بستن خودکار مودال با زدن کلید ESC کیبورد
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') hideWatanModal();
    });
  </script>
@endif
@endsection