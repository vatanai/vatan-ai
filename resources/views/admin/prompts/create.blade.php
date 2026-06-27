{{-- resources/views/admin/prompts/create.blade.php --}}
@extends('admin.layouts.admin')

@section('title', 'افزودن پرامپت جدید | وطن استودیو')

@section('content')
<div id="create-prompt-page" class="max-w-4xl mx-auto relative">

    {{-- هدر صفحه و دکمه بازگشت --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('admin.dashboard') }}" class="w-9 h-9 bg-white/5 border border-white/10 text-[#8a91ad] hover:text-white rounded-xl flex items-center justify-center transition-colors text-xs">
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <div>
            <h1 class="text-xl font-black text-white">افزودن پرامپت (سبک) جدید</h1>
            <p class="text-xs text-[#5a6184] mt-1">یک سبک تصویرسازی اختصاصی برای پردازش توسط هوش مصنوعی تعریف کنید.</p>
        </div>
    </div>

    {{-- نمایش خطاهای ولیدیشن در صورت وجود --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400 space-y-1">
            <div class="font-bold flex items-center gap-1.5 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i> لطفاً خطاهای زیر را برطرف کنید:
            </div>
            <ul class="list-disc list-inside mr-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- فرم اصلی ارسال داده‌ها به سمت کنترلر لاراول (اصلاح شده) --}}
    <form action="{{ route('admin.prompts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- ستون راست: اطلاعات متنی و فیلدها (۲ بخش از ۳ بخش عرض صفحه) --}}
            <div class="md:col-span-2 space-y-5">
                
                {{-- باکس اصلی ورودی‌ها --}}
                <div class="bg-[#121217] border border-white/5 rounded-2xl p-5 space-y-5">
                    
                    {{-- نام سبک پرامپت --}}
                    <div>
                        <label for="prompt_name" class="block text-xs font-bold text-[#8a91ad] mb-2">نام سبک / مدل <span class="text-red-400">*</span></label>
                        <input type="text" id="prompt_name" name="name" required value="{{ old('name') }}" placeholder="مثال: آبرنگ رویایی، پاپ آرت، انیمه سه بعدی" 
                            class="w-full h-11 px-4 bg-white/[0.02] border border-white/5 rounded-xl text-xs text-white placeholder-[#454c6c] focus:outline-none focus:border-[#0BBF53] focus:ring-4 focus:ring-[#0BBF53]/10 transition-all">
                    </div>

                    {{-- متن اصلی پرامپت هوش مصنوعی (Prompt) --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="prompt_text" class="block text-xs font-bold text-[#8a91ad]">پرامپت مهندسی شده (AI Prompt) <span class="text-red-400">*</span></label>
                            <span class="text-[10px] text-[#454c6c] font-medium">جهت چپ به راست</span>
                        </div>
                        <textarea id="prompt_text" name="prompt" rows="5" required placeholder="Masterpiece, ultra detailed, oil painting style, vibrant colors, highly detailed face..." 
                            class="w-full p-4 bg-white/[0.02] border border-white/5 rounded-xl text-xs text-white placeholder-[#454c6c] focus:outline-none focus:border-[#0BBF53] focus:ring-4 focus:ring-[#0BBF53]/10 transition-all text-left" style="direction: ltr;">{{ old('prompt') }}</textarea>
                        <p class="text-[10px] text-[#5a6184] mt-1.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info text-[#0BBF53]"></i> 
                            این متن به صورت خودکار به عکس آپلود شده توسط کاربر الصاق و به سرور هوش مصنوعی ارسال خواهد شد.
                        </p>
                    </div>

                    {{-- توضیحات معرفی برای کاربر --}}
                    <div>
                        <label for="prompt_description" class="block text-xs font-bold text-[#8a91ad] mb-2">توضیحات کوتاه (نمایش به کاربران)</label>
                        <textarea id="prompt_description" name="description" rows="3" placeholder="توضیح مختصری درباره خروجی این سبک بنویسید تا کاربر در اپلیکیشن ببیند..." 
                            class="w-full p-4 bg-white/[0.02] border border-white/5 rounded-xl text-xs text-white placeholder-[#454c6c] focus:outline-none focus:border-[#0BBF53] focus:ring-4 focus:ring-[#0BBF53]/10 transition-all">{{ old('description') }}</textarea>
                    </div>

                </div>

                {{-- باکس سوییچ تغییر وضعیت انتشار (فعال/غیرفعال) --}}
                <div class="bg-[#121217] border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-white">وضعیت انتشار آنی</div>
                        <div class="text-[10px] text-[#5a6184] mt-0.5">در صورت فعال بودن، کاربران بلافاصله این سبک را در سایت خواهند دید.</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-white/5 border border-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-[#454c6c] peer-checked:after:bg-[#0BBF53] after:border-transparent after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0BBF53]/10 peer-checked:border-[#0BBF53]/20"></div>
                    </label>
                </div>

            </div>

            {{-- ستون چپ: آپلود تصویر نمونه و دکمه‌های عملیاتی (۱ بخش از ۳ بخش عرض صفحه) --}}
            <div class="space-y-5">
                
                {{-- باکس درگ اند دراپ آپلود تصویر نمونه کاور --}}
                <div class="bg-[#121217] border border-white/5 rounded-2xl p-5">
                    <label class="block text-xs font-bold text-[#8a91ad] mb-3">تصویر پایه / پیش‌فرض (کاور) <span class="text-red-400">*</span></label>
                    
                    {{-- منطقه آپلود پیش‌نمایش هوشمند --}}
                    <div id="drop-zone" class="relative border-2 border-dashed border-white/5 hover:border-[#0BBF53]/40 bg-white/[0.01] hover:bg-[#0BBF53]/[0.02] transition-all rounded-xl p-5 flex flex-col items-center justify-center text-center cursor-pointer group">
                        <input type="file" id="prompt_image" name="image" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*" onchange="previewImage(this)">
                        
                        {{-- حالت اول: هیچ عکسی انتخاب نشده است --}}
                        <div id="upload-placeholder" class="space-y-3 py-4">
                            <div class="w-12 h-12 rounded-xl bg-white/5 text-[#454c6c] group-hover:text-[#0BBF53] group-hover:bg-[#0BBF53]/10 flex items-center justify-center text-lg mx-auto transition-all">
                                <i class="fa-solid fa-image-portrait"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-white">انتخاب تصویر کاور</span>
                                <span class="block text-[10px] text-[#454c6c] mt-1">یا فایل را به اینجا بکشید</span>
                            </div>
                            <span class="inline-block text-[9px] px-2 py-0.5 bg-white/5 rounded text-[#8a91ad]">فرمت JPG یا PNG</span>
                        </div>

                        {{-- حالت دوم: نمایش آنی پیش‌نمایش تصویر انتخابی بدون رفرش --}}
                        <div id="upload-preview" class="hidden w-full space-y-3">
                            <img id="preview-img" src="#" alt="Preview" class="w-full h-48 object-cover rounded-lg border border-white/10">
                            <button type="button" onclick="removeImage(event)" class="text-[10px] text-red-400 hover:underline flex items-center gap-1 justify-center mx-auto">
                                <i class="fa-solid fa-trash-can"></i> حذف و تغییر تصویر
                            </button>
                        </div>
                    </div>

                </div>

                {{-- دکمه‌های ثبت نهایی یا انصراف --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full h-11 bg-[#0BBF53] hover:bg-[#09a346] text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-2 shadow-[0_4px_20px_rgba(11,191,83,0.15)] cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>ثبت و پیکربندی پرامپت</span>
                    </button>
                    
                    <a href="{{ route('admin.dashboard') }}" class="w-full h-11 bg-white/5 hover:bg-white/10 border border-white/5 text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center">
                        انصراف و بازگشت
                    </a>
                </div>

            </div>

        </div>

    </form>

    {{-- مودال هوشمند نمایش موفقیت آمیز عملیات --}}
    @if(session('success'))
        <div id="success-modal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300">
            <div class="bg-[#121217] border border-white/10 w-full max-w-sm rounded-2xl p-6 shadow-[0_20px_50px_rgba(0,0,0,0.5)] text-center transform scale-95 transition-transform duration-300 opacity-0" id="modal-card">
                
                {{-- آیکون تایید متحرک --}}
                <div class="w-16 h-16 bg-[#0BBF53]/10 border border-[#0BBF53]/20 rounded-full flex items-center justify-center mx-auto mb-4 text-[#0BBF53] shadow-[0_0_20px_rgba(11,191,83,0.1)]">
                    <i class="fa-solid fa-circle-check text-2xl animate-bounce"></i>
                </div>

                {{-- متن پیام --}}
                <h3 class="text-sm font-black text-white mb-1">عملیات موفقیت‌آمیز</h3>
                <p class="text-xs text-[#8a91ad] leading-relaxed mb-6">{{ session('success') }}</p>

                {{-- دکمه تایید و بستن --}}
                <button onclick="closeSuccessModal()" class="w-full h-10 bg-[#0BBF53] hover:bg-[#09a346] text-white rounded-xl text-xs font-bold transition-all shadow-[0_4px_12px_rgba(11,191,83,0.2)] cursor-pointer">
                    فهمیدم
                </button>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // پردازش و نمایش فوری پیش‌نمایش عکس لود شده لوکال
    function previewImage(input) {
        const placeholder = document.getElementById('upload-placeholder');
        const preview = document.getElementById('upload-preview');
        const previewImg = document.getElementById('preview-img');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                placeholder.classList.add('hidden');
                preview.classList.remove('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // لغو عکس انتخاب شده و بازگرداندن باکس آپلود به حالت اولیه
    function removeImage(e) {
        e.preventDefault();
        const input = document.getElementById('prompt_image');
        const placeholder = document.getElementById('upload-placeholder');
        const preview = document.getElementById('upload-preview');
        
        input.value = ''; 
        placeholder.classList.remove('hidden');
        preview.classList.add('hidden');
    }

    // مدیریت انیمیشن و بستن مودال موفقیت
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('success-modal');
        const card = document.getElementById('modal-card');
        
        if(modal && card) {
            setTimeout(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 50);
        }
    });

    function closeSuccessModal() {
        const modal = document.getElementById('success-modal');
        const card = document.getElementById('modal-card');
        
        if(modal && card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }
</script>
@endpush