@extends('layouts.admin')
@section('title', 'ثبت دسته‌بندی جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[var(--bg)] text-[var(--text)]" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px]" id="content">

      <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
          <div class="text-xl font-extrabold tracking-tight mb-1">ثبت دسته‌بندی جدید</div>
          <div class="text-xs text-gray-500">ایجاد دسته‌بندی‌های جدید برای اتصال و ساختاربندی محصولات فروشگاه</div>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] transition-all hover:text-[var(--text)] no-underline">
          <i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت به لیست
        </a>
      </div>

      <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          
          <div class="lg:col-span-2 space-y-4">
            <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
              <div class="text-xs font-bold text-[var(--text)] mb-4 flex items-center gap-2 pb-3 border-b border-[var(--b1)]">
                <i class="fa-solid fa-folder-plus text-[var(--accent)]"></i> مشخصات و اطلاعات هویتی دسته‌بندی
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[var(--text2)]">نام دسته‌بندی <span class="text-[var(--red)] mr-0.5">*</span></label>
                  <input type="text" name="name" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none focus:border-[var(--accent)] w-full" value="{{ old('name') }}" placeholder="مثلا: مدل‌های متنی یا ابزارهای گرافیکی" required>
                </div>
                
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[var(--text2)]">اسلاگ (Slug - اختیاری)</label>
                  <input type="text" name="slug" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none focus:border-[var(--accent)] w-full text-right" value="{{ old('slug') }}" placeholder="در صورت خالی بودن خودکار تولید می‌شود">
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[var(--text2)]">سرشاخه (والد)</label>
                  <select name="parent_id" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] outline-none focus:border-[var(--accent)] w-full">
                    <option value="">— بدون والد (این خودش سرشاخه است) —</option>
                    @foreach(\App\Models\Category::orderBy('name')->get() as $pc)
                      <option value="{{ $pc->id }}" {{ (string) old('parent_id') === (string) $pc->id ? 'selected' : '' }}>{{ $pc->name_fa ?: $pc->name }}</option>
                    @endforeach
                  </select>
                  <div class="text-[10px] text-[var(--text3)]">اگر این دسته زیرشاخه است، سرشاخه‌اش را انتخاب کنید.</div>
                </div>

              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
              <div class="text-xs font-bold text-[var(--text)] mb-3 flex items-center gap-2 pb-2 border-b border-[var(--b1)]">
                <i class="fa-regular fa-image text-[var(--accent)]"></i> تصویر یا لوگوی دسته‌بندی
              </div>
              
              <div class="flex flex-col items-center justify-center border border-dashed border-[var(--b2)] bg-[var(--s1)] rounded-xl p-4 transition hover:border-[var(--accent)] relative group min-h-[140px]">
                <img id="image-preview" class="hidden w-16 h-16 object-cover rounded-lg mb-2 shadow" src="" alt="Preview">
                <div id="upload-placeholder" class="flex flex-col items-center justify-center text-center cursor-pointer">
                  <i class="fa-solid fa-cloud-arrow-up text-lg text-gray-500 mb-1.5 group-hover:text-[var(--accent)] transition-colors"></i>
                  <span class="text-[11px] text-[var(--text2)] font-medium">انتخاب تصویر برای دسته‌بندی</span>
                  <span class="text-[9px] text-[var(--text3)] mt-0.5">فرمت‌های مجاز: jpeg, png, jpg, webp (حداکثر ۲ مگابایت)</span>
                </div>
                <input type="file" name="image" id="category_image" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" onchange="previewImage(this)">
              </div>
            </div>
          </div>

        </div>

        <div class="fixed bottom-0 right-0 left-0 bg-[var(--s1)]/80 backdrop-blur border-t border-[var(--b1)] px-6 h-16 flex items-center justify-end gap-3 z-40 md:mr-[294px]">
          <button type="submit" class="px-5 h-[38px] rounded-lg text-xs font-bold bg-[var(--accent)] text-[var(--primary)] shadow-lg shadow-[var(--accent)]/10 hover:bg-[var(--accent-hover)] transition-all flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-floppy-disk text-[13px]"></i>
            ذخیره و ثبت دسته‌بندی
          </button>
        </div>
      </form>

    </div>
  </main>
</div>

@if($errors->any())
<div id="alert-toast" class="fixed top-6 left-6 z-50 transform translate-x-[-120%] opacity-0 transition-all duration-500 ease-out flex items-start gap-3 bg-[var(--s2)] border border-[var(--red)]/40 shadow-2xl shadow-[var(--bg)] p-4 rounded-xl min-w-[340px] max-w-sm text-right" dir="rtl">
    <div class="w-9 h-9 rounded-lg bg-[var(--red)]/10 flex items-center justify-center text-[var(--red)] shrink-0 mt-0.5">
        <i class="fa-solid fa-circle-exclamation text-base"></i>
    </div>
    <div class="flex-1">
        <div class="text-xs font-bold text-[var(--text)] mb-1.5">خطا در ثبت اطلاعات</div>
        <ul class="text-[11px] text-[var(--text3)] list-disc pr-4 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    <button onclick="closeToast('alert-toast')" class="text-gray-500 hover:text-[var(--text)] transition-colors p-1 cursor-pointer">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('alert-toast');
        if (toast) {
            setTimeout(() => {
                toast.classList.remove('translate-x-[-120%]', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 100);
        }
    });
</script>
@endif

<script>
  function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('opacity-40');
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

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