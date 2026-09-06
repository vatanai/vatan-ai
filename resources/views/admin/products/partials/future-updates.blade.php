{{-- قابلیت‌های خارج‌شده از مسیر اصلی ثبت محصول؛ هر مورد یک بخش مستقل برای توسعه آینده است. --}}
<section class="bg-[var(--s2)] border border-dashed border-[var(--b2)] rounded-xl p-5" id="product-future-updates">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
      <i class="fa-solid fa-clock-rotate-left text-[var(--accent)]"></i>
      آپدیت‌های آینده
    </div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">این قابلیت‌ها فعلاً بخشی از مسیر ثبت محصول نیستند و بعداً جداگانه تکمیل می‌شوند.</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="future-updates-content">
    <article class="future-update-card">
      <span class="future-update-icon"><i class="fa-solid fa-flask"></i></span>
      <div><strong>آزمایشگاه محصول</strong><small>تجربه فرم کاربر، اجرای آزمایشی و مقایسه مدل‌ها</small></div>
      <span class="future-update-badge">بزودی</span>
    </article>
    <article class="future-update-card">
      <span class="future-update-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
      <div><strong>تست ثبت محصول</strong><small>ساخت هوشمند ویژگی‌ها و سندباکس ثبت</small></div>
      <span class="future-update-badge">بزودی</span>
    </article>
    <article class="future-update-card">
      <span class="future-update-icon"><i class="fa-solid fa-images"></i></span>
      <div><strong>پیش‌نمایش واقعی کارت و گالری</strong><small>نمایش زنده تصاویر واقعی محصول، حالت کارت، چیدمان گالری و برچسب روی تصویر</small></div>
      <span class="future-update-badge">بزودی</span>
    </article>
  </div>
</section>

<style>
.future-update-card{display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--b1);border-radius:11px;background:var(--s1)}
.future-update-card>div{display:flex;flex:1;min-width:0;flex-direction:column;gap:3px}
.future-update-card strong{font-size:11px;color:var(--text2)}
.future-update-card small{font-size:9.5px;line-height:1.7;color:var(--text3)}
.future-update-icon{width:34px;height:34px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;border-radius:9px;background:color-mix(in srgb,var(--accent) 10%,transparent);color:var(--accent)}
.future-update-badge{font-size:9px;font-weight:700;padding:3px 7px;border:1px solid var(--b2);border-radius:7px;color:var(--text3);white-space:nowrap}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var target = document.getElementById('future-updates-content');
  if (!target) return;
  document.querySelectorAll('[data-future-update]').forEach(function (item) {
    var card = document.createElement('article');
    card.className = 'future-update-card md:col-span-2';
    card.innerHTML = '<span class="future-update-icon"><i class="fa-solid fa-layer-group"></i></span><div><strong></strong><small>قابلیت مستقل برای توسعه در نسخه‌های بعدی</small></div><span class="future-update-badge">بزودی</span>';
    card.querySelector('strong').textContent = item.dataset.futureUpdate;
    item.remove();
    target.appendChild(card);
  });
});
</script>
