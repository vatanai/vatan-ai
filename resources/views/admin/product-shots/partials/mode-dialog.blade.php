{{-- انتخاب نوع ثبت محصول؛ فقط برای محصول جدید و وقتی ماژول پروداکتی روشن است. --}}
<div class="ps-mode-dialog" id="ps-mode-dialog" role="dialog" aria-modal="true" aria-labelledby="ps-mode-dialog-title" aria-describedby="ps-mode-dialog-desc">
  <div class="ps-mode-dialog-card">
    <a href="{{ route('admin.products') }}" class="ps-mode-dialog-close" aria-label="بستن و بازگشت به محصولات"><i class="fa-solid fa-xmark"></i></a>
    <div class="ps-mode-dialog-kicker"><i class="fa-solid fa-plus"></i> ثبت محصول جدید</div>
    <h2 id="ps-mode-dialog-title">چه نوع محصولی می‌خواهید ثبت کنید؟</h2>
    <p id="ps-mode-dialog-desc">نوع مسیر را انتخاب کنید. ثبت محصول چهره‌محور بدون هیچ تغییر با همان فرم فعلی باز می‌شود.</p>

    <div class="ps-mode-dialog-options">
      <a href="{{ route('admin.product-shots.products.create') }}" class="ps-mode-dialog-option">
        <span class="ps-mode-dialog-icon"><i class="fa-solid fa-box-open"></i></span>
        <span class="ps-mode-dialog-copy">
          <strong>ثبت محصول پروداکتی</strong>
          <small>پک چندشاتی برای کفش، پوشاک، زیبایی، طلا و دیگر محصولات فیزیکی</small>
        </span>
        <span class="ps-mode-dialog-arrow"><i class="fa-solid fa-arrow-left"></i></span>
      </a>

      <a href="{{ route('admin.products.create', ['mode' => 'portrait']) }}" class="ps-mode-dialog-option">
        <span class="ps-mode-dialog-icon"><i class="fa-solid fa-user"></i></span>
        <span class="ps-mode-dialog-copy">
          <strong>ثبت محصول چهره‌محور</strong>
          <small>همان مسیر فعلی پرتره، حفظ هویت چهره و فرم پنج‌مرحله‌ای</small>
        </span>
        <span class="ps-mode-dialog-arrow"><i class="fa-solid fa-arrow-left"></i></span>
      </a>
    </div>
  </div>
</div>
<script>
(function () {
  var dialog = document.getElementById('ps-mode-dialog');
  var page = document.getElementById('product-create-page');
  if (!dialog) return;
  if (page) page.setAttribute('inert', '');
  document.body.style.overflow = 'hidden';

  var controls = Array.prototype.slice.call(dialog.querySelectorAll('a[href], button:not([disabled])'));
  var first = controls[0];
  var last = controls[controls.length - 1];
  var closeUrl = dialog.querySelector('.ps-mode-dialog-close').href;
  window.setTimeout(function () {
    var preferred = dialog.querySelector('.ps-mode-dialog-option');
    if (preferred) preferred.focus();
  }, 0);

  dialog.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      window.location.href = closeUrl;
      return;
    }
    if (event.key !== 'Tab' || controls.length < 2) return;
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
})();
</script>
