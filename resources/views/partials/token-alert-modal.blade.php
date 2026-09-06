{{-- resources/views/partials/token-alert-modal.blade.php --}}
@auth
<div id="globalTokenModal" class="fixed inset-0 z-[9999] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/85 backdrop-blur-md p-4" dir="rtl">
    <div id="globalTokenModalContent" class="bg-[#121218] border border-white/10 w-full max-w-md rounded-[24px] overflow-hidden scale-95 transition-transform duration-300 shadow-2xl relative p-6 text-center flex flex-col items-center gap-4">
        
        {{-- دکمه بستن مودال --}}
        <button type="button" onclick="closeGlobalTokenModal()" class="absolute top-4 left-4 w-7 h-7 flex items-center justify-center rounded-full bg-white/[0.03] text-gray-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>

        {{-- آیکون متحرک و افکت نئون سکه/توکن --}}
        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.15)] animate-pulse mb-2">
            <i class="fa-solid fa-coins text-2xl"></i>
        </div>

        {{-- متن‌های راهنما --}}
        <h3 class="text-[15px] font-black text-gray-100">موجودی اعتبار شما کافی نیست!</h3>
        <p class="text-[11.5px] text-gray-400 leading-relaxed px-2">
            هزینه استفاده از این ابزار هوش مصنوعی بیشتر از توکن‌های باقی‌مانده شماست. برای دسترسی به کارگاه ساخت و پردازش رندر، نیاز به شارژ حساب خود دارید.
        </p>

        {{-- آمار وضعیت فعلی --}}
        <div class="w-full bg-white/[0.02] border border-white/[0.04] rounded-xl py-2.5 px-4 flex items-center justify-between text-xs my-1">
            <span class="text-gray-500 font-bold">وضعیت حساب:</span>
            <span class="text-red-400 font-black bg-red-500/10 px-2 py-0.5 rounded-md">نیازمند شارژ</span>
        </div>

        {{-- دکمه‌های عملیاتی --}}
        <div class="w-full grid grid-cols-1 gap-2 mt-2">
            <a href="/app/pricing" class="w-full h-11 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-500/10 active:scale-[0.99] no-underline">
                <i class="fa-solid fa-basket-shopping text-xs"></i>
                خرید بسته و افزایش آنی توکن
            </a>
            <button type="button" onclick="closeGlobalTokenModal()" class="w-full h-10 bg-white/[0.03] hover:bg-white/10 text-gray-400 hover:text-white font-bold text-[11px] rounded-xl transition-colors cursor-pointer">
                بعداً تهیه می‌کنم (بازگشت)
            </button>
        </div>

    </div>
</div>

<script>
// تابع باز کردن مودال به همراه انیمیشن نرم
function openGlobalTokenModal() {
    var modal = document.getElementById('globalTokenModal');
    var content = document.getElementById('globalTokenModalContent');
    if(!modal || !content) return;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(function() {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
    }, 25);
}

// تابع بستن مودال
function closeGlobalTokenModal() {
    var modal = document.getElementById('globalTokenModal');
    var content = document.getElementById('globalTokenModalContent');
    if(!modal || !content) return;

    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(function() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

// ثبت در آبجکت window جهت فراخوانی هوشمند در صفحات ابزارها
window.showTokenShortageModal = openGlobalTokenModal;
</script>
@endauth