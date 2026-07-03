{{-- پارشیال: گام پنجم — بازبینی نهایی --}}
{{-- جدا شده از step-output.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای (طبق درخواست کاربر).
     یک Step مستقل و اختصاصی برای مرور نهایی قبل از ثبت — الگوی رایج در ویزاردهای SaaS مدرن
     (مثل مراحل پایانی Checkout در Stripe). هیچ فیلد جدیدی اینجا نیست، فقط خواندن مقادیر فرم. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-clipboard-check text-[var(--accent)]"></i> خلاصه نهایی</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">پیش از ثبت، اطلاعات محصول را مرور کنید</div>
    </div>
    <span id="summary-status-badge" class="text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30">Incomplete</span>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5" id="final-summary-grid">
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">نام محصول</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-name">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">دسته</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-category">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">مدل AI</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-model">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">قیمت</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-price">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">نوع خروجی</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-media">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">وضعیت</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-status">پیش‌نویس</span>
    </div>
  </div>

  <div class="text-[10.5px] text-[var(--text3)] mt-3 leading-relaxed">
    اگر موردی نیاز به اصلاح دارد، از Stepper بالا به همان مرحله برگردید — بازگشت همیشه آزاد است و اطلاعات از دست نمی‌رود.
  </div>

  <button type="button" class="inline-flex items-center gap-2 px-4 h-9 rounded-lg text-xs font-bold bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-all mt-4" onclick="alert('پیش‌نمایش محصول — فقط UI، در فاز بعد به صفحه واقعی محصول متصل می‌شود.')">
    <i class="fa-solid fa-eye"></i> NEW پیش‌نمایش محصول {!! $newBadge !!}
  </button>
</div>

<script>
/* ══════ خلاصه نهایی زنده (فقط خواندن مقادیر واقعی فرم از تمام مراحل، بدون فیلد جدید) ══════ */
function refreshFinalSummary() {
  const nameFa = document.querySelector('[name="name_fa"]')?.value.trim();
  const catSel = document.querySelector('[name="category"]');
  const modelSel = document.getElementById('primary-model-select');
  const priceSel = document.querySelector('[name="pricing_model"]:checked');
  const mediaSel = document.querySelector('[name="media_type"]:checked');
  const statusInput = document.getElementById('product-status');

  const catText = catSel && catSel.value ? catSel.options[catSel.selectedIndex].textContent : null;
  const modelText = modelSel && modelSel.value ? modelSel.options[modelSel.selectedIndex].textContent : null;
  const priceText = priceSel ? ({free:'رایگان', per_credit:'کردیتی', subscription:'اشتراکی'}[priceSel.value]) : null;
  const mediaText = mediaSel ? ({photo:'عکس', video:'ویدیو', both:'هر دو'}[mediaSel.value]) : null;

  const fields = { 'sum-name': nameFa, 'sum-category': catText, 'sum-model': modelText, 'sum-price': priceText, 'sum-media': mediaText };
  let complete = true;
  Object.keys(fields).forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (fields[id]) { el.textContent = fields[id]; el.classList.remove('text-[var(--text3)]'); }
    else { el.textContent = 'تکمیل‌نشده'; el.classList.add('text-[var(--text3)]'); complete = false; }
  });

  const statusEl = document.getElementById('sum-status');
  if (statusEl && statusInput) statusEl.textContent = statusInput.value === 'active' ? 'ثبت نهایی' : 'پیش‌نویس';

  const badge = document.getElementById('summary-status-badge');
  if (complete) {
    badge.textContent = 'Ready';
    badge.className = 'text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--green)]/15 text-[var(--green)] border border-[var(--green)]/30';
  } else {
    badge.textContent = 'Incomplete';
    badge.className = 'text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  refreshFinalSummary();
  // به‌روزرسانی زنده خلاصه با تغییر فیلدهای کلیدی در سایر مراحل
  document.querySelector('[name="name_fa"]')?.addEventListener('input', refreshFinalSummary);
  document.querySelector('[name="category"]')?.addEventListener('change', refreshFinalSummary);
  document.getElementById('primary-model-select')?.addEventListener('change', refreshFinalSummary);
  document.querySelectorAll('[name="media_type"]').forEach(r => r.addEventListener('change', refreshFinalSummary));
  document.querySelectorAll('[name="pricing_model"]').forEach(r => r.addEventListener('change', refreshFinalSummary));
});
</script>
