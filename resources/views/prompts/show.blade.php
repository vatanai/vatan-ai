{{-- resources/views/prompts/show.blade.php --}}
@extends('layouts.app')

@section('title', $prompt->name . ' | Uniset AI')

@section('content')
<div class="h-screen w-full bg-[#0a0a0c] text-white font-vazir flex flex-col overflow-hidden relative" dir="rtl">

    {{-- بدنه اصلی --}}
    <div class="flex-1 flex overflow-hidden">
        
        {{-- بخش تصویر اصلی --}}
        <div class="flex-1 p-4 md:p-8 flex items-center justify-center relative bg-[#0a0a0c]">
            <div id="loadingStatus" class="hidden absolute inset-0 bg-black/70 backdrop-blur-md z-10 flex flex-col items-center justify-center space-y-4 transition-all">
                <div class="w-14 h-14 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin shadow-lg shadow-indigo-500/20"></div>
                <div class="text-indigo-400 text-xs font-black animate-pulse flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> هوش مصنوعی در حال خلق تصویر شماست...
                </div>
                <p class="text-[10px] text-gray-500 font-medium">این فرآیند ممکن است تا یک دقیقه زمان ببرد. لطفاً پنجره را نبندید.</p>
            </div>

            <div class="w-full h-full flex items-center justify-center p-4">
                <img id="mainDisplayImage" src="{{ asset($prompt->image) }}" alt="{{ $prompt->name }}" class="max-w-full max-h-full object-contain rounded-[20px] transition-all duration-500 shadow-2xl border border-white/5">
            </div>

            <div id="readyBadge" class="hidden absolute top-6 right-6 bg-emerald-500 text-black font-black text-[10px] px-3 py-1.5 rounded-lg shadow-lg shadow-emerald-500/20 animate-fade-in">
                <i class="fa-solid fa-check"></i> آماده شد
            </div>
        </div>

        {{-- بخش سایدبار --}}
        <div class="w-[380px] shrink-0 bg-[#121214] border-r border-white/[0.04] flex flex-col h-full overflow-y-auto custom-scrollbar">
            
            <div class="p-6 flex flex-col gap-6">
                
                {{-- هدر سایدبار --}}
                <div class="flex items-start justify-between">
                    <h1 class="text-[12px] font-bold text-gray-300 uppercase tracking-widest leading-relaxed max-w-[250px]">
                        {{ $prompt->name }}
                    </h1>
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-white transition-colors cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </a>
                </div>

                {{-- تگ‌ها و لایک --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-white/[0.03] border border-white/[0.05] rounded-full">
                        <span class="text-[11px] font-bold text-gray-300">Nano Banana PRO</span>
                        <i class="fa-regular fa-circle-question text-[10px] text-gray-500"></i>
                    </div>
                    
                    <div class="flex items-center gap-1.5">
                        <button class="w-8 h-8 bg-white/[0.03] hover:bg-white/10 rounded-full flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                            <i class="fa-solid fa-share text-[11px]"></i>
                        </button>
                        <button class="px-3 h-8 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/10 rounded-full flex items-center gap-1.5 text-rose-400 transition-colors">
                            <i class="fa-solid fa-heart text-[11px]"></i>
                            <span class="text-[11px] font-bold">7</span>
                        </button>
                    </div>
                </div>

                {{-- پروفایل سازنده --}}
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/[0.05] flex items-center justify-center text-gray-500 border border-white/[0.05]">
                        <i class="fa-regular fa-user text-[11px]"></i>
                    </div>
                    <span class="text-[11px] font-bold text-gray-500">پروفایل سازنده</span>
                </div>

                {{-- فرم اصلی ارسال دیتا همراه با اتریبیوت چک کردن وضعیت لاگین --}}
                <form action="{{ route('prompts.generate', $prompt->id) }}" method="POST" enctype="multipart/form-data" id="generationForm" data-auth="{{ Auth::check() ? 'true' : 'false' }}" class="space-y-4">
                    @csrf
                    
                    {{-- فراخوانی پارشیال باکس آپلود تصویر شما --}}
                    @include('prompts.uploader')

                    {{-- دکمه تولید مجدد نئون --}}
                    <button type="submit" id="submitBtn" class="w-full h-11 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-600/10 active:scale-[0.99] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-bolt text-[11px]"></i>
                        <span id="btnText">تولید مجدد در این سبک</span>
                    </button>
                </form>

                {{-- دکمه‌های دانلود و متحرک‌سازی --}}
                <div class="grid grid-cols-2 gap-3">
                    <a id="downloadBtn" href="#" download class="opacity-40 pointer-events-none h-10 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold text-[11px] rounded-xl flex items-center justify-center gap-2 transition-colors border border-emerald-500/10 cursor-pointer">
                        <i class="fa-solid fa-download text-[11px]"></i>
                        دانلود خروجی
                    </a>
                    <button class="h-10 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-bold text-[11px] rounded-xl flex items-center justify-center gap-2 transition-colors border border-sky-500/10 cursor-pointer">
                        <i class="fa-solid fa-wand-magic-sparkles text-[11px]"></i>
                        متحرک‌سازی
                    </button>
                </div>

                {{-- سیستم خطای داینامیک آژاکس --}}
                <div id="ajaxErrorBox" class="hidden p-2.5 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl text-[10px] font-bold flex items-center gap-2 animate-fade-in">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="ajaxErrorText">خطا</span>
                </div>

                {{-- گالری انتهای صفحه --}}
                <div class="space-y-3 mt-2">
                    <h3 class="text-[9px] font-black text-gray-500 uppercase tracking-widest">خلق شده با این پرامپت</h3>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <div class="aspect-square rounded-lg bg-[#1a1a1d] border border-white/5 overflow-hidden cursor-pointer">
                            <img src="{{ asset($prompt->image) }}" class="w-full h-full object-cover hover:scale-105 transition-transform opacity-90 hover:opacity-100">
                        </div>
                        <div class="aspect-square rounded-lg bg-[#1a1a1d] border border-white/5 overflow-hidden cursor-pointer flex items-center justify-center text-gray-600 hover:text-gray-400 hover:bg-white/[0.02] transition-all">
                            <i class="fa-solid fa-layer-group text-sm"></i>
                        </div>
                        <div class="aspect-square rounded-lg bg-[#1a1a1d] border border-white/5 overflow-hidden cursor-pointer flex items-center justify-center text-gray-600 hover:text-gray-400 hover:bg-white/[0.02] transition-all">
                            <i class="fa-solid fa-wand-magic text-sm"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- مودال اول: اخطار عدم ورود به حساب --}}
    <div id="loginModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden bg-black/60 backdrop-blur-sm transition-opacity duration-300">
        <div class="w-[340px] bg-[#121215] border border-white/[0.08] rounded-2xl overflow-hidden shadow-2xl transform transition-all p-5 space-y-4 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto text-lg animate-bounce">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-sm font-black text-white">وارد حساب خود نشده‌اید!</h4>
                <p class="text-[11px] text-gray-400">برای استفاده از سیستم تولید هوش مصنوعی ابتدا باید وارد شوید.</p>
            </div>
            
            <div class="grid grid-cols-2 gap-2.5 pt-2">
                <a href="/auth/login" class="h-9 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] rounded-xl flex items-center justify-center transition-colors">ورود به حساب</a>
                <button onclick="closeModal('loginModal')" class="h-9 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-bold text-[11px] rounded-xl transition-colors">بعداً</button>
            </div>

            <div class="w-full h-[3px] bg-white/[0.03] rounded-full overflow-hidden mt-2">
                <div id="modalProgressBar" class="h-full bg-rose-500 w-full origin-right"></div>
            </div>
        </div>
    </div>

    {{-- مودال دوم: اخطار نیاز به خرید/تمدید اشتراک --}}
    <div id="subscriptionModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden bg-black/70 backdrop-blur-sm transition-opacity duration-300">
        <div class="w-[350px] bg-[#121215] border border-white/[0.08] rounded-2xl overflow-hidden shadow-2xl p-6 space-y-4 text-center relative">
            <div class="w-12 h-12 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto text-lg">
                <i class="fa-solid fa-crown text-amber-400"></i>
            </div>
            <div class="space-y-1.5">
                <h4 class="text-sm font-black text-white">اشتراک شما فعال نیست</h4>
                <p class="text-[11px] text-gray-400 leading-relaxed">
                    سرویس پردازش تصویر موقتاً غیرفعال است. لطفاً جهت استفاده، اشتراک خود را تهیه یا تمدید کنید.
                </p>
            </div>
            
            <div class="flex flex-col gap-2 pt-2">
                <a href="/plans" class="h-10 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-black text-[11px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-500/15">
                    <i class="fa-solid fa Gem"></i>
                    خرید یا ارتقاء اشتراک
                </a>
                <button onclick="closeModal('subscriptionModal')" class="h-10 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-bold text-[11px] rounded-xl transition-colors">
                    متوجه شدم
                </button>
            </div>
        </div>
    </div>

</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.12); }

    .animate-progress {
        animation: shrinkWidth 4s linear forwards;
    }
    @keyframes shrinkWidth {
        from { width: 100%; }
        to { width: 0%; }
    }
</style>

<script>
let timerTimeout;

// هندل کردن سابمیت فرم به صورت ۱۰۰٪ ناهمگام (Ajax)
document.getElementById('generationForm').addEventListener('submit', function(e) {
    e.preventDefault(); 

    // بررسی وضعیت احراز هویت کاربر قبل از هر کار
    const isAuth = this.getAttribute('data-auth') === 'true';
    if (!isAuth) {
        openLoginModal();
        return;
    }

    const form = this;
    const formData = new FormData();

    // صید فیزیکی و مطمئن اطلاعات فایل از کامپوننت پارشیال شما
    const fileInput = document.querySelector('input[name="user_image"]');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('user_image', fileInput.files[0]);
    } else {
        showAjaxError('لطفاً ابتدا تصویر پایه خود را انتخاب یا آپلود کنید.');
        return;
    }

    // صید متن تکست‌اریا کامپوننت
    const additionalPrompt = document.querySelector('textarea[name="additional_prompt"]');
    if (additionalPrompt && additionalPrompt.value.trim() !== '') {
        formData.append('additional_prompt', additionalPrompt.value);
    }

    // المان‌های واسط کاربری صفحه
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const loadingStatus = document.getElementById('loadingStatus');
    const mainDisplayImage = document.getElementById('mainDisplayImage');
    const readyBadge = document.getElementById('readyBadge');
    const errorBox = document.getElementById('ajaxErrorBox');
    const downloadBtn = document.getElementById('downloadBtn');

    // فعال‌سازی حالت لودینگ مدرن بدون ریدایرکت
    submitBtn.disabled = true;
    btnText.innerText = 'در حال ارتباط با هوش مصنوعی...';
    loadingStatus.classList.remove('hidden');
    readyBadge.classList.add('hidden');
    errorBox.classList.add('hidden');

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json' // اجبار لاراول به عدم ارسال ریدایرکت ۳۰۲
        },
        body: formData
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            // مدیریت خطای اشتراک فعال یا ولیدیشن‌ها
            if (data.message && data.message.includes('اشتراک')) {
                openSubscriptionModal();
                throw new Error('اشتراک فعال یافت نشد.');
            }
            throw new Error(data.message || 'خطایی در پردازش سرور رخ داده است.');
        }
        return data;
    })
    .then(data => {
        if(data.success && data.image_url) {
            // جایگذاری تصویر نهایی خلق شده هوش مصنوعی در قاب اصلی
            mainDisplayImage.src = data.image_url;
            readyBadge.classList.remove('hidden');
            
            // فعال‌سازی دکمه دانلود فایل فیزیکی جدید
            downloadBtn.href = data.image_url;
            downloadBtn.classList.remove('opacity-40', 'pointer-events-none');
        } else {
            showAjaxError(data.message || 'پاسخ نامعتبر سرور تصویر.');
        }
    })
    .catch(error => {
        console.error('Error Details:', error);
        if (!error.message.includes('اشتراک')) {
            showAjaxError(error.message || 'خطایی در ارسال اطلاعات یا محدودیت API رخ داد.');
        }
    })
    .finally(() => {
        // بازگرداندن وضعیت دکمه‌ها به حالت اولیه
        submitBtn.disabled = false;
        btnText.innerText = 'تولید مجدد در این سبک';
        loadingStatus.classList.add('hidden');
    });
});

function showAjaxError(message) {
    const errorBox = document.getElementById('ajaxErrorBox');
    const errorText = document.getElementById('ajaxErrorText');
    errorText.innerText = message;
    errorBox.classList.remove('hidden');
}

function openLoginModal() {
    const modal = document.getElementById('loginModal');
    const bar = document.getElementById('modalProgressBar');
    modal.classList.remove('hidden');
    bar.classList.add('animate-progress');
    
    timerTimeout = setTimeout(() => {
        closeModal('loginModal');
    }, 4000);
}

function openSubscriptionModal() {
    const modal = document.getElementById('subscriptionModal');
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    if (modalId === 'loginModal') {
        const bar = document.getElementById('modalProgressBar');
        bar.classList.remove('animate-progress');
        clearTimeout(timerTimeout);
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('uploadPlaceholder').classList.add('hidden');
            document.getElementById('imagePreviewContainer').classList.remove('hidden');
            document.getElementById('imagePreview').setAttribute('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection