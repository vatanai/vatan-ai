@extends('layouts.admin')
@section('title', 'مدیریت محصولات هوش مصنوعی — وطن استودیو')

@section('content')
<div class="flex min-h-screen bg-bg text-white" dir="rtl">

  {{-- نوار کناری پنل مدیریت --}}
  @include('admin.partials.sidebar')

  {{-- محتوای اصلی سمت چپ --}}
  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    {{-- هدر بالای صفحه --}}
    <header class="sticky top-0 z-50 bg-s1 border-b border-b1 px-6 h-14 flex items-center justify-between">
      <div class="flex items-center gap-1.5 text-xs text-text2">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <span class="text-white font-semibold">محصولات</span>
        <i class="fa-solid fa-chevron-left text-[10px] text-text3"></i>
        <span class="text-text3 text-[11px]">لیست ابزارهای فعال پلتفرم</span>
      </div>
      
      {{-- دکمه ثبت محصول جدید متصل به فرم مرحله‌ای --}}
      <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-1.5 px-4 h-9 rounded-xl text-xs font-bold bg-accent text-white hover:bg-opacity-90 transition-all shadow-sm shadow-accent/10">
        <i class="fa-solid fa-plus text-[10px]"></i> ثبت محصول جدید
      </a>
    </header>

    {{-- باکس محتوای جدول رکوردها --}}
    <main class="p-6 flex-1 w-full max-w-6xl mx-auto">
      
      {{-- نمایش اعلان‌های موفقیت‌آمیز سیستم --}}
      @if(session('success'))
        <div class="mb-5 p-4 bg-green/10 border border-green/20 rounded-xl text-green text-xs font-bold flex items-center gap-2">
          <i class="fa-solid fa-circle-check text-[14px]"></i> 
          <span>{{ session('success') }}</span>
        </div>
      @endif

      {{-- جدول لیست هوشمند محصولات وطن AI --}}
      <div class="bg-s2 border border-b1 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-b1 flex items-center justify-between bg-s1/30">
          <div class="text-sm font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-accent"></i> ابزارها و مدل‌های پردازش تصویر
          </div>
          <div class="text-xs text-text3 bg-s1 border border-b1 px-2.5 py-1 rounded-lg">تعداد کل: {{ $products->total() }} ابزار</div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-right border-collapse">
            <thead>
              <tr class="border-b border-b1 bg-s1/60 text-text2 text-[11px] font-bold uppercase tracking-wider">
                <th class="px-6 py-3.5 w-20">کاور دمو</th>
                <th class="px-6 py-3.5">نام و شناسه ابزار</th>
                <th class="px-6 py-3.5">دسته‌بندی / زیرمجموعه</th>
                <th class="px-6 py-3.5">موتور پردازش اصلی</th>
                <th class="px-6 py-3.5 text-center">هزینه (توکن فعلی)</th>
                <th class="px-6 py-3.5 text-center">وضعیت</th>
                <th class="px-6 py-3.5 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-b1 text-[12.5px]">
              @forelse($products as $product)
                <tr class="hover:bg-s1/20 transition-colors duration-150">
                  
                  {{-- تصویر کاور بندانگشتی (Thumbnail) --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    @if($product->thumbnail_path)
                      <img src="{{ asset('storage/' . $product->thumbnail_path) }}" alt="{{ $product->name_fa }}" class="w-11 h-11 rounded-xl object-cover border border-b1 bg-s1">
                    @else
                      <div class="w-11 h-11 rounded-xl bg-s1 border border-b1 flex items-center justify-center text-text3 text-[9px] text-center font-medium leading-tight">بدون کاور</div>
                    @endif
                  </td>
                  
                  {{-- نام فارسی و انگلیسی ابزار به همراه پیوند یکتا --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-white text-[13px] flex items-center gap-1.5">
                      {{ $product->name_fa }}
                      @if($product->is_featured)
                        <span class="text-[9px] font-bold bg-accent/10 text-accent px-1.5 py-0.5 rounded border border-accent/20">ویژه</span>
                      @endif
                    </div>
                    <div class="text-[10.5px] text-text3 font-mono mt-0.5" dir="ltr">{{ $product->name_en }}</div>
                  </td>
                  
                  {{-- دسته‌بندی و زیردسته ثبت شده --}}
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-text2 text-[12px] font-medium">{{ $product->category }}</div>
                    @if($product->subcategory)
                      <div class="text-[10px] text-text3 mt-0.5">{{ $product->subcategory }}</div>
                    @endif
                  </td>
                  
                  {{-- مدل هوش مصنوعی تخصصی ست شده --}}
                  <td class="px-6 py-4 whitespace-nowrap font-mono text-[11px] text-accent/90" dir="ltr">
                    {{ Str::afterLast($product->primary_model, '/') }}
                  </td>
                  
                  {{-- کسر از اعتبار کاربر (سیستم توکنی) --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center font-bold">
                    @if($product->is_free)
                      <span class="text-green text-[11px] bg-green/10 px-2.5 py-0.5 rounded-full border border-green/20">رایگان</span>
                    @else
                      <span class="text-amber-400 font-mono text-[13.5px]">{{ $product->credit_cost }}</span> 
                      <span class="text-[10px] text-text3 font-normal mr-0.5">اعتبار</span>
                    @endif
                  </td>
                  
                  {{-- وضعیت انتشار پلتفرم --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    @if($product->status === 'active')
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-green bg-green/10 px-2.5 py-0.5 rounded-full border border-green/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-green animate-pulse"></span> فعال
                      </span>
                    @elseif($product->status === 'draft')
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-text3 bg-s1 px-2.5 py-0.5 rounded-full border border-b1">
                        <span class="w-1.5 h-1.5 rounded-full bg-text3"></span> پیش‌نویس
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-red bg-red/10 px-2.5 py-0.5 rounded-full border border-red/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-red"></span> غیرفعال
                      </span>
                    @endif
                  </td>
                  
                  {{-- عملیات‌های مدیریتی --}}
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2" dir="ltr">
                      
                      {{-- فرم حذف امن ابزار هوش مصنوعی --}}
                      <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('آیا از حذف این ابزار مطمئن هستید؟ با حذف این مورد، دسترسی کاربران به این بخش قطع خواهد شد.')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 rounded-lg bg-s1 hover:bg-red/20 text-text2 hover:text-red flex items-center justify-center border border-b1 transition-colors" title="حذف محصول">
                          <i class="fa-solid fa-trash-can text-[11px]"></i>
                        </button>
                      </form>

                      {{-- ویرایش محصول --}}
                      <a href="{{ route('admin.products.edit', $product) }}" class="w-8 h-8 rounded-lg bg-s1 hover:bg-b1 text-text2 hover:text-white flex items-center justify-center border border-b1 transition-colors" title="ویرایش">
                        <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                      </a>
                      
                    </div>
                  </td>
                </tr>
              @empty
                {{-- وضعیت عدم وجود دیتا در دیتابیس --}}
                <tr>
                  <td colspan="7" class="px-6 py-16 text-center text-text3">
                    <div class="flex flex-col items-center justify-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-s1 border border-b1 flex items-center justify-center text-text3">
                        <i class="fa-solid fa-box-open text-lg"></i>
                      </div>
                      <div class="text-xs font-semibold text-text2">هیچ محصول یا ابزار پردازشی یافت نشد.</div>
                      <p class="text-[11px] text-text3 max-w-[300px] mx-auto leading-relaxed">دیتابیس محصولات در حال حاضر خالی است. برای راه اندازی بخش فرانت سیستم کسر توکن، اولین محصول خود را ثبت کنید.</p>
                      <a href="{{ route('admin.products.create') }}" class="mt-1.5 inline-flex items-center gap-1 text-xs text-accent font-bold hover:underline">
                        ایجاد سریع اولین محصول ابزار <i class="fa-solid fa-arrow-left text-[10px]"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- پجینیشن استاندارد سیستم لاراول --}}
        @if($products->hasPages())
          <div class="px-6 py-4 border-t border-b1 bg-s1/10 flex justify-center" dir="ltr">
            {{ $products->links() }}
          </div>
        @endif

      </div>
    </main>
  </div>
</div>
@endsection