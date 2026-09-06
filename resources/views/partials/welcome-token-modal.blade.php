{{-- resources/views/partials/welcome-token-modal.blade.php --}}
@auth
@if(session()->has('welcome_tokens'))
<div id="welcomeTokenModal" class="fixed inset-0 z-[9999] flex opacity-0 transition-opacity duration-500 items-center justify-center bg-black/90 backdrop-blur-lg p-4" dir="rtl">
    <div id="welcomeTokenModalContent" class="bg-gradient-to-b from-[#16161f] to-[#111116] border border-emerald-500/20 w-full max-w-md rounded-[28px] overflow-hidden scale-95 transition-transform duration-500 shadow-[0_0_50px_rgba(16,185,129,0.1)] relative p-8 text-center flex flex-col items-center gap-5">
        
        {{-- دکمه بستن مودال --}}
        <button type="button" onclick="closeWelcomeTokenModal()" class="absolute top-5 left-5 w-7 h-7 flex items-center justify-center rounded-full bg-white/[0.03] text-gray-500 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>

        {{-- افکت و آیکون متحرک تبریک و هدیه --}}
        <div class="relative mb-2">
            <div class="absolute inset-0 bg-emerald-500/20 blur-xl rounded-full scale-150 animate-pulse"></div>
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 border border-emerald-400/30 flex items-center justify-center text-black shadow-lg relative z-10 animate-bounce">
                <i class="fa-solid fa-gift text-3xl"></i>
            </div>
            <span class="absolute -top-2 -right-2 text-xl animate-ping">✨</span>
            <span class="absolute -bottom-2 -left-2 text-lg animate-pulse">🎉</span>
        </div>

        {{-- متن‌های تبریک --}}
        <div class="space-y-1.5">
            <h3 class="text-[17px] font-black bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">
                ثبت‌نام شما با موفقیت انجام شد!
            </h3>
            <p class="text-[13px] font-bold text-gray-200">به جامعه هوش مصنوعی وطن AI خوش آمدید</p>
        </div>

        <p class="text-[11.5px] text-gray-400 leading-relaxed px-2">
            به پاس همراهی و انتخاب شما، پکیج هدیه اولیه با موفقیت روی حساب کاربری شما فعال و شارژ گردید. هم‌اکنون می‌توانید از تمامی ابزارهای خلاقانه کارگاه استفاده کنید.
        </p>

        {{-- نمایش توکن‌های هدیه تخصیص داده شده --}}
        <div class="w-full bg-emerald-500/[0.03] border border-emerald-500/10 rounded-2xl py-3.5 px-5 flex items-center justify-between my-1 group hover:border-emerald-500/20 transition-all">
            <span class="text-gray-400 text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500 text-[10px]"></i>
                اعتبار هدیه خوش‌آمدگویی:
            </span>
            <span class="text-emerald-400 font-black text-sm tracking-wide bg-emerald-500/10 px-3 py-1 rounded-xl">
               + {{ number_format(session('welcome_tokens')) }} توکن
            </span>
        </div>

        {{-- دکمه ورود به پنل یا شروع کار --}}
        <div class="w-full mt-2">
            <button type="button" onclick="closeWelcomeTokenModal()" class="w-full h-12 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-black font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-emerald-500/10 active:scale-[0.99] cursor-pointer">
                <i class="fa-solid fa-rocket text-xs"></i>
                شروع خلق تصاویر با هوش مصنوعی
            </button>
        </div>

    </div>
</div>

<script>
// باز شدن خودکار به محض لود کامل صفحه (اگر سشن وجود داشت)
document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById('welcomeTokenModal');
    var content = document.getElementById('welcomeTokenModalContent');
    if(!modal || !content) return;
    
    // اعمال انیمیشن ورود نرم پس از ۵۰۰ میلی‌ثانیه
    setTimeout(function() {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
    }, 500);
});

// تابع بستن مودال تبریک
function closeWelcomeTokenModal() {
    var modal = document.getElementById('welcomeTokenModal');
    var content = document.getElementById('welcomeTokenModalContent');
    if(!modal || !content) return;

    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(function() {
        modal.remove(); // المان را کلاً از دام حذف میکنیم تا دیگر تکرار نشود
    }, 500);
}
</script>
@endif
@endauth