{{-- پارشیال: مودال نمایش نتیجه + مراحل پیشرفت --}}

<div id="cp-modal" style="display:none;position:fixed;inset:0;z-index:500;background:rgba(0,0,0,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);align-items:center;justify-content:center;padding:20px;" dir="rtl">

  <div style="background:#111116;border:1px solid #222230;border-radius:20px;width:100%;max-width:420px;overflow:hidden;">

    {{-- Progress (قبل از نتیجه) --}}
    <div id="cp-progress-view" style="padding:28px 24px;text-align:center;">
      <div style="width:56px;height:56px;background:rgba(160,122,245,0.1);border:2px solid rgba(160,122,245,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="fa-solid fa-wand-magic-sparkles fa-spin" style="color:#a07af5;font-size:20px;"></i>
      </div>
      <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:8px;" id="cp-step-title">در حال پردازش...</div>
      <div style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:20px;" id="cp-step-desc">لطفاً صبر کنید</div>

      {{-- نوار پیشرفت --}}
      <div style="background:rgba(255,255,255,0.08);border-radius:99px;height:6px;overflow:hidden;">
        <div id="cp-progress-bar" style="height:100%;background:#a07af5;border-radius:99px;transition:width 1.5s ease;width:0%;"></div>
      </div>
    </div>

    {{-- نمایش نتیجه --}}
    <div id="cp-result-view" style="display:none;">
      <div style="position:relative;">
        <img id="cp-result-img" src="" alt="نتیجه" style="width:100%;display:block;max-height:400px;object-fit:cover;">
        <div style="position:absolute;top:10px;right:10px;background:rgba(11,191,83,0.9);border-radius:8px;padding:4px 10px;font-size:11px;font-weight:700;color:#fff;display:flex;align-items:center;gap:4px;">
          <i class="fa-solid fa-check"></i> تصویر آماده شد
        </div>
      </div>

      <div style="padding:16px 20px;display:flex;gap:10px;">
        <a id="cp-result-download" href="#" download="result.png"
          style="flex:1;height:44px;background:#a07af5;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:13px;font-weight:700;color:#fff;text-decoration:none;">
          <i class="fa-solid fa-download"></i> دانلود
        </a>
        <button onclick="cpModalClose()"
          style="flex:1;height:44px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);border-radius:12px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;">
          بستن
        </button>
      </div>
    </div>

    {{-- خطا --}}
    <div id="cp-error-view" style="display:none;padding:28px 24px;text-align:center;">
      <div style="width:48px;height:48px;background:rgba(240,92,92,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#f05c5c;font-size:18px;"></i>
      </div>
      <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:8px;">مشکلی پیش آمد</div>
      <div id="cp-error-text" style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:20px;line-height:1.6;"></div>
      <button onclick="cpModalClose()"
        style="padding:10px 24px;background:#f05c5c;border:none;border-radius:10px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;">
        بستن
      </button>
    </div>

  </div>
</div>

<script>
var _cpSteps = [
  { title: 'آپلود تصویر...', desc: 'در حال ارسال عکس به سرور', pct: 15 },
  { title: 'در حال تحلیل عکس...', desc: 'هوش مصنوعی عکس شما را بررسی می‌کند', pct: 35 },
  { title: 'در حال اعمال سبک...', desc: 'ویرایش طبق دستورالعمل انجام می‌شود', pct: 65 },
  { title: 'در حال نهایی‌سازی...', desc: 'تصویر در حال آماده شدن است', pct: 90 },
];

function cpModalOpen() {
  var m = document.getElementById('cp-modal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  document.getElementById('cp-progress-view').style.display = 'block';
  document.getElementById('cp-result-view').style.display = 'none';
  document.getElementById('cp-error-view').style.display = 'none';
}

function cpModalClose() {
  document.getElementById('cp-modal').style.display = 'none';
  document.body.style.overflow = '';
}

function cpSetStep(idx) {
  var step = _cpSteps[idx] || _cpSteps[_cpSteps.length - 1];
  document.getElementById('cp-step-title').textContent = step.title;
  document.getElementById('cp-step-desc').textContent = step.desc;
  document.getElementById('cp-progress-bar').style.width = step.pct + '%';
}

function cpShowResult(imageUrl) {
  document.getElementById('cp-progress-view').style.display = 'none';
  document.getElementById('cp-error-view').style.display = 'none';
  var rv = document.getElementById('cp-result-view');
  rv.style.display = 'block';
  document.getElementById('cp-result-img').src = imageUrl;
  document.getElementById('cp-result-download').href = imageUrl;
}

function cpShowError(msg) {
  document.getElementById('cp-progress-view').style.display = 'none';
  document.getElementById('cp-result-view').style.display = 'none';
  var ev = document.getElementById('cp-error-view');
  ev.style.display = 'block';
  document.getElementById('cp-error-text').textContent = msg || 'خطای ناشناخته رخ داد.';
}
</script>