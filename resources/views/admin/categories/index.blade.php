@extends('layouts.admin')
@section('title', 'مدیریت دسته‌بندی‌ها — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[var(--bg)] text-[var(--text)]" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px]" id="content">

      <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
          <div class="text-xl font-extrabold tracking-tight mb-1">مدیریت دسته‌بندی‌ها</div>
          <div class="text-xs text-gray-500">لیست، ویرایش و مدیریت تمامی دسته‌بندی‌های محصولات موجود در سیستم</div>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[var(--accent)] text-[var(--primary)] shadow-lg shadow-[var(--accent)]/10 hover:bg-[var(--accent-hover)] transition-all no-underline cursor-pointer">
          <i class="fa-solid fa-plus text-[11px]"></i> افزودن دسته‌بندی جدید
        </a>
      </div>

      <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl overflow-hidden">
        <div class="p-4 border-b border-[var(--b1)] text-xs font-bold text-[var(--text3)] flex items-center gap-2">
          <i class="fa-solid fa-list text-[var(--accent)]"></i> دسته‌بندی‌های ثبت شده
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-right border-collapse text-xs">
            <thead>
              <tr class="bg-[var(--s1)] border-b border-[var(--b1)] text-[var(--text2)]">
                <th class="p-4 font-semibold w-20">تصویر</th>
                <th class="p-4 font-semibold">نام دسته‌بندی</th>
                <th class="p-4 font-semibold">اسلاگ (Slug)</th>
                <th class="p-4 font-semibold w-32 text-center">تعداد محصولات</th>
                <th class="p-4 font-semibold w-36 text-left pl-6">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--b1)]">
              @forelse($categories as $category)
                <tr class="hover:bg-[var(--s1)]/40 transition-colors">
                  <td class="p-4">
                    @if($category->image)
                      <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-10 h-10 object-cover rounded-lg border border-[var(--b1)]">
                    @else
                      <div class="w-10 h-10 rounded-lg bg-[var(--s1)] border border-[var(--b1)] flex items-center justify-center text-[10px] text-[var(--text3)]">
                        <i class="fa-regular fa-image text-sm"></i>
                      </div>
                    @endif
                  </td>
                  
                  <td class="p-4 font-bold text-[var(--text)] text-sm">
                    {{ $category->name }}
                  </td>
                  
                  <td class="p-4 font-mono text-gray-500 ltr text-right">
                    {{ $category->slug }}
                  </td>
                  
                  <td class="p-4 text-center">
                    <span class="inline-flex items-center justify-center px-2 py-1 rounded-md bg-[var(--b1)] text-[var(--text2)] font-semibold min-w-[40px]">
                      {{ $category->products_count }}
                    </span>
                  </td>
                  
                  <td class="p-4 text-left pl-6">
                    <div class="inline-flex items-center gap-2">
                      <a href="{{ route('admin.categories.edit', $category->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[var(--b1)] border border-[var(--b2)] text-[var(--text2)] hover:text-[var(--text)] transition-colors" title="ویرایش">
                        <i class="fa-regular fa-pen-to-square text-[13px]"></i>
                      </a>
                      
                      <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟ محصولات وابسته به این دسته‌بندی حذف نخواهند شد و بدون دسته‌بندی باقی می‌مانند.');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[var(--red)]/10 border border-[var(--red)]/30 text-[var(--red)] hover:bg-[var(--red)]/20 transition-all cursor-pointer" title="حذف دسته‌بندی">
                          <i class="fa-regular fa-trash-can text-[13px]"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="p-8 text-center text-[var(--text3)]">
                    <div class="flex flex-col items-center justify-center gap-2">
                      <i class="fa-regular fa-folder-open text-2xl"></i>
                      <span>هیچ دسته‌بندی‌ای در سیستم ثبت نشده است.</span>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

@if(session('success'))
<div id="success-toast" class="fixed top-6 left-6 z-50 transform translate-x-[-120%] opacity-0 transition-all duration-500 ease-out flex items-center gap-3 bg-[var(--s2)] border border-[var(--text3)]/40 shadow-2xl shadow-[var(--bg)] p-4 rounded-xl min-w-[320px] max-w-sm text-right" dir="rtl">
    <div class="w-9 h-9 rounded-lg bg-[var(--text3)]/10 flex items-center justify-center text-[var(--text2)] shrink-0">
        <i class="fa-solid fa-circle-check text-base"></i>
    </div>
    <div class="flex-1">
        <div class="text-xs font-bold text-[var(--text)] mb-0.5">عملیات موفقیت‌آمیز</div>
        <div class="text-[11px] text-[var(--text3)] leading-relaxed">{{ session('success') }}</div>
    </div>
    <button onclick="closeToast('success-toast')" class="text-gray-500 hover:text-[var(--text)] transition-colors p-1 cursor-pointer">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('success-toast');
        if (toast) {
            setTimeout(() => {
                toast.classList.remove('translate-x-[-120%]', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 100);

            setTimeout(() => {
                closeToast('success-toast');
            }, 5000);
        }
    });
</script>
@endif

<script>
    function closeToast(id) {
        const toast = document.getElementById(id);
        if (toast) {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-[-120%]', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }
    }
</script>
@endsection