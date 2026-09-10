{{-- resources/views/partials/token-alert-modal.blade.php --}}
@auth
<div id="globalTokenModal" class="fixed inset-0 z-[9999] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/70 backdrop-blur-md p-4" dir="rtl" role="dialog" aria-modal="true" aria-labelledby="globalTokenModalTitle">
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
        <h3 id="globalTokenModalTitle" class="text-[15px] font-black text-gray-100">برای ساخت بعدی فقط یک قدم مانده</h3>
        <p class="text-[11.5px] text-gray-400 leading-relaxed px-2">
            موجودی شما برای این ساخت کافی نیست، اما ورودی‌ها و مسیر ساختت حفظ شده است. با افزایش اعتبار، همین محصول را دوباره اجرا کن و ادامه بده.
        </p>

        {{-- آمار وضعیت فعلی --}}
        <div class="w-full bg-white/[0.02] border border-white/[0.04] rounded-xl py-2.5 px-4 flex items-center justify-between text-xs my-1">
            <span class="text-gray-500 font-bold">موجودی / هزینه ساخت:</span>
            <span class="text-red-400 font-black bg-red-500/10 px-2 py-0.5 rounded-md"><b data-token-balance>۰</b> / <b data-token-required>۰</b></span>
        </div>

        {{-- دکمه‌های عملیاتی --}}
        <div class="w-full grid grid-cols-1 gap-2 mt-2">
            <a href="{{ route('pricing.index') }}" class="w-full h-11 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-500/10 active:scale-[0.99] no-underline">
                <i class="fa-solid fa-basket-shopping text-xs"></i>
                افزایش اعتبار و ادامه ساخت
            </a>
            <button type="button" onclick="closeGlobalTokenModal()" class="w-full h-10 bg-white/[0.03] hover:bg-white/10 text-gray-400 hover:text-white font-bold text-[11px] rounded-xl transition-colors cursor-pointer">
                بعداً تهیه می‌کنم (بازگشت)
            </button>
        </div>

    </div>
</div>

<div id="globalRefundModal" class="fixed inset-0 z-[9999] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/70 backdrop-blur-md p-4" dir="rtl" role="dialog" aria-modal="true" aria-labelledby="globalRefundModalTitle">
    <div id="globalRefundModalContent" class="bg-[#121218] border border-emerald-400/20 w-full max-w-md rounded-[24px] overflow-hidden scale-95 transition-transform duration-300 shadow-2xl relative p-6 text-center flex flex-col items-center gap-4">
        <button type="button" onclick="closeCreditsReturnedModal()" class="absolute top-4 left-4 w-7 h-7 flex items-center justify-center rounded-full bg-white/[0.03] text-gray-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer" aria-label="بستن">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-400/30 flex items-center justify-center text-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.15)]">
            <i class="fa-solid fa-coins text-2xl"></i>
        </div>
        <h3 id="globalRefundModalTitle" class="text-[15px] font-black text-gray-100">اعتبارت به حسابت برگشت</h3>
        <p class="text-[11.5px] text-gray-400 leading-relaxed px-2">این تلاش به نتیجه نرسید؛ اعتبار این ساخت به‌صورت کامل به موجودی‌ات برگردانده شد و چیزی از حسابت کم نمی‌ماند.</p>
        <div class="w-full bg-emerald-500/10 border border-emerald-400/20 rounded-xl py-2.5 px-4 flex items-center justify-between text-xs">
            <span class="text-gray-400 font-bold">اعتبار برگشتی</span>
            <strong class="text-emerald-400 font-black"><span data-refunded-credits>۰</span> اعتبار</strong>
        </div>
        <button type="button" onclick="closeCreditsReturnedModal()" class="w-full h-10 bg-white/[0.03] hover:bg-white/10 text-gray-300 hover:text-white font-bold text-[11px] rounded-xl transition-colors cursor-pointer">متوجه شدم</button>
    </div>
</div>

<script>
// تابع باز کردن مودال به همراه انیمیشن نرم
function openGlobalTokenModal(details) {
    var modal = document.getElementById('globalTokenModal');
    var content = document.getElementById('globalTokenModalContent');
    if(!modal || !content) return;
    details = details || {};
    var format = function(value) { return Number(value || 0).toLocaleString('fa-IR'); };
    var balance = modal.querySelector('[data-token-balance]');
    var required = modal.querySelector('[data-token-required]');
    if (balance) balance.textContent = format(details.balance);
    if (required) required.textContent = format(details.required);
    
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

function closeCreditsReturnedModal() {
    var modal = document.getElementById('globalRefundModal');
    var content = document.getElementById('globalRefundModalContent');
    if(!modal || !content) return;
    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(function() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

function openCreditsReturnedModal(amount) {
    var modal = document.getElementById('globalRefundModal');
    var content = document.getElementById('globalRefundModalContent');
    if(!modal || !content) return;
    var amountEl = modal.querySelector('[data-refunded-credits]');
    if (amountEl) amountEl.textContent = Number(amount || 0).toLocaleString('fa-IR');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(function() {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
    }, 25);
}

function closeOpenCreditModalOnBackdrop(event) {
    if (event.target === event.currentTarget) {
        if (event.currentTarget.id === 'globalTokenModal') closeGlobalTokenModal();
        if (event.currentTarget.id === 'globalRefundModal') closeCreditsReturnedModal();
    }
}

document.getElementById('globalTokenModal')?.addEventListener('click', closeOpenCreditModalOnBackdrop);
document.getElementById('globalRefundModal')?.addEventListener('click', closeOpenCreditModalOnBackdrop);
document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    closeGlobalTokenModal();
    closeCreditsReturnedModal();
});

// ثبت در آبجکت window جهت فراخوانی هوشمند در صفحات ابزارها
window.showTokenShortageModal = openGlobalTokenModal;
window.showCreditsReturnedModal = openCreditsReturnedModal;
</script>
@endauth
