{{--
    PARTIAL: create/result-modal.blade.php
    بخش هفتم — مودال نمایش نتیجه
--}}

<div id="cp-result-modal" class="cp-modal" role="dialog" aria-modal="true" aria-label="نتیجه ساخت" style="display:none;" dir="rtl">
  <div class="cp-modal__backdrop" onclick="cpModalClose()"></div>

  <div class="cp-modal__sheet">

    {{-- هندل کشیدن (موبایل) --}}
    <div class="cp-modal__drag-handle"></div>

    {{-- مراحل پیشرفت --}}
    <div class="cp-modal__progress" id="cp-progress">
      <div class="cp-progress__steps">
        <div class="cp-progress__step is-active" id="step-uploading">
          <div class="cp-progress__dot"></div>
          <span>آپلود تصاویر</span>
        </div>
        <div class="cp-progress__line"></div>
        <div class="cp-progress__step" id="step-preparing">
          <div class="cp-progress__dot"></div>
          <span>آماده‌سازی</span>
        </div>
        <div class="cp-progress__line"></div>
        <div class="cp-progress__step" id="step-generating">
          <div class="cp-progress__dot"></div>
          <span>ساخت تصویر</span>
        </div>
        <div class="cp-progress__line"></div>
        <div class="cp-progress__step" id="step-finalizing">
          <div class="cp-progress__dot"></div>
          <span>نهایی‌سازی</span>
        </div>
      </div>
      <div class="cp-progress__bar-wrap">
        <div class="cp-progress__bar" id="cp-progress-bar"></div>
      </div>
      <p class="cp-progress__msg" id="cp-progress-msg">در حال آپلود تصاویر...</p>
    </div>

    {{-- نتیجه --}}
    <div class="cp-modal__result" id="cp-result" style="display:none;">

      {{-- تصویر --}}
      <div class="cp-modal__img-wrap">
        <img id="cp-result-img" src="" alt="نتیجه" class="cp-modal__img">
        <button class="cp-modal__zoom-btn" onclick="cpImgZoom()" aria-label="بزرگ‌نمایی">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>

      {{-- اطلاعات --}}
      <div class="cp-modal__info">
        <div class="cp-modal__token-left" id="cp-token-left"></div>
      </div>

      {{-- اکشن‌ها --}}
      <div class="cp-modal__actions">
        <a id="cp-download-btn" href="#" download="vatan-ai-result.jpg" class="cp-modal__btn cp-modal__btn--primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          دانلود
        </a>
        <button type="button" class="cp-modal__btn cp-modal__btn--secondary" onclick="cpModalClose();cpRegenerate()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          دوباره بساز
        </button>
        <button type="button" class="cp-modal__btn cp-modal__btn--ghost" onclick="cpModalClose()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          داشبورد
        </button>
      </div>

    </div>

    {{-- خطا --}}
    <div class="cp-modal__error" id="cp-error" style="display:none;">
      <div class="cp-modal__error-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#f05c5c" stroke-width="1.8"/><path d="M12 8v5M12 15.5v.5" stroke="#f05c5c" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <p class="cp-modal__error-title">مشکلی پیش آمد</p>
      <p class="cp-modal__error-msg" id="cp-error-msg">تصویر پردازش نشد. لطفاً دوباره تلاش کنید.</p>
      <button type="button" class="cp-modal__btn cp-modal__btn--secondary" onclick="cpModalClose()">
        بستن و تلاش مجدد
      </button>
    </div>

  </div>
</div>

{{-- بزرگ‌نمایی --}}
<div id="cp-zoom-overlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.92);display:none;align-items:center;justify-content:center;" onclick="this.style.display='none'">
  <img id="cp-zoom-img" src="" alt="بزرگ" style="max-width:95vw;max-height:95vh;border-radius:12px;object-fit:contain;">
</div>

<style>
/* ═══════════════════════════════
   CP-MODAL
═══════════════════════════════ */
.cp-modal {
  position: fixed;
  inset: 0;
  z-index: 900;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

@media (min-width: 640px) {
  .cp-modal { align-items: center; }
}

.cp-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.65);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  animation: cpFadeIn 0.22s ease;
}

@keyframes cpFadeIn { from { opacity:0; } to { opacity:1; } }

.cp-modal__sheet {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 480px;
  background: #111116;
  border-radius: 24px 24px 0 0;
  padding: 12px 16px calc(env(safe-area-inset-bottom,0px) + 24px);
  max-height: 92dvh;
  overflow-y: auto;
  animation: cpSlideUp 0.3s cubic-bezier(0.22,1,0.36,1);
}

@media (min-width: 640px) {
  .cp-modal__sheet {
    border-radius: 20px;
    padding: 24px;
    max-height: 85dvh;
  }
}

@keyframes cpSlideUp {
  from { transform: translateY(60px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

html.light .cp-modal__sheet {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.08);
}

.cp-modal__drag-handle {
  width: 36px;
  height: 4px;
  border-radius: 2px;
  background: rgba(255,255,255,0.2);
  margin: 0 auto 18px;
}

html.light .cp-modal__drag-handle { background: rgba(0,0,0,0.12); }

/* مراحل پیشرفت */
.cp-modal__progress {
  padding: 12px 0 8px;
}

.cp-progress__steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0;
  margin-bottom: 16px;
}

.cp-progress__step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 0 0 auto;
}

.cp-progress__step span {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255,255,255,0.3);
  font-family: 'YekanBakh', sans-serif;
  white-space: nowrap;
}

.cp-progress__step.is-active span,
.cp-progress__step.is-done span  { color: rgba(255,255,255,0.85); }

.cp-progress__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(255,255,255,0.12);
  transition: background 0.3s;
  flex-shrink: 0;
}

.cp-progress__step.is-active .cp-progress__dot {
  background: #a07af5;
  box-shadow: 0 0 0 4px rgba(160,122,245,0.2);
  animation: cpPulse 1.2s infinite;
}

.cp-progress__step.is-done .cp-progress__dot {
  background: #0BBF53;
}

@keyframes cpPulse {
  0%,100% { box-shadow: 0 0 0 4px rgba(160,122,245,0.2); }
  50%      { box-shadow: 0 0 0 6px rgba(160,122,245,0.1); }
}

.cp-progress__line {
  flex: 1;
  height: 2px;
  background: rgba(255,255,255,0.08);
  margin: 0 4px;
  margin-bottom: 16px;
  transition: background 0.4s;
}

.cp-progress__line.is-done { background: #0BBF53; }

html.light .cp-progress__step span      { color: rgba(0,0,0,0.3); }
html.light .cp-progress__step.is-active span,
html.light .cp-progress__step.is-done span { color: rgba(0,0,0,0.8); }
html.light .cp-progress__dot             { background: rgba(0,0,0,0.1); }
html.light .cp-progress__line            { background: rgba(0,0,0,0.07); }

/* نوار پیشرفت */
.cp-progress__bar-wrap {
  height: 4px;
  background: rgba(255,255,255,0.08);
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 12px;
}

.cp-progress__bar {
  height: 100%;
  width: 0%;
  border-radius: 2px;
  background: linear-gradient(90deg, #a07af5, #0BBF53);
  transition: width 0.6s ease;
}

html.light .cp-progress__bar-wrap { background: rgba(0,0,0,0.07); }

.cp-progress__msg {
  font-size: 12px;
  font-weight: 500;
  color: rgba(255,255,255,0.5);
  font-family: 'YekanBakh', sans-serif;
  text-align: center;
}

html.light .cp-progress__msg { color: rgba(0,0,0,0.4); }

/* نتیجه */
.cp-modal__result { display: flex; flex-direction: column; gap: 14px; }

.cp-modal__img-wrap {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  background: rgba(255,255,255,0.04);
  aspect-ratio: 1 / 1;
}

.cp-modal__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.cp-modal__zoom-btn {
  position: absolute;
  top: 10px;
  left: 10px;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: rgba(0,0,0,0.45);
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  backdrop-filter: blur(8px);
}

.cp-modal__info {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.cp-modal__token-left {
  font-size: 11px;
  font-weight: 500;
  color: rgba(255,255,255,0.4);
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-modal__token-left { color: rgba(0,0,0,0.4); }

/* اکشن‌ها */
.cp-modal__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.cp-modal__btn {
  width: 100%;
  height: 48px;
  border-radius: 12px;
  border: none;
  font-size: 14px;
  font-weight: 700;
  font-family: 'YekanBakh', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  transition: opacity 0.18s, transform 0.14s;
  -webkit-tap-highlight-color: transparent;
}

.cp-modal__btn:active { transform: scale(0.98); }

.cp-modal__btn--primary {
  background: #0BBF53;
  color: #fff;
}

.cp-modal__btn--primary:hover { opacity: 0.88; }

.cp-modal__btn--secondary {
  background: rgba(160,122,245,0.12);
  border: 1.5px solid rgba(160,122,245,0.25);
  color: #a07af5;
}

.cp-modal__btn--secondary:hover { background: rgba(160,122,245,0.18); }

.cp-modal__btn--ghost {
  background: rgba(255,255,255,0.05);
  border: 1.5px solid rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.6);
}

html.light .cp-modal__btn--ghost {
  background: rgba(0,0,0,0.04);
  border-color: rgba(0,0,0,0.08);
  color: rgba(0,0,0,0.5);
}

/* خطا */
.cp-modal__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 24px 0;
  text-align: center;
}

.cp-modal__error-icon { opacity: 0.85; }

.cp-modal__error-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text, #fff);
  font-family: 'YekanBakh', sans-serif;
}

.cp-modal__error-msg {
  font-size: 13px;
  color: rgba(255,255,255,0.5);
  font-family: 'YekanBakh', sans-serif;
  line-height: 1.7;
}

html.light .cp-modal__error-msg { color: rgba(0,0,0,0.45); }
</style>

<script>
/* ── Modal control ── */
function cpModalOpen() {
  var modal = document.getElementById('cp-result-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function cpModalClose() {
  var modal = document.getElementById('cp-result-modal');
  modal.style.display = 'none';
  document.body.style.overflow = '';
}

function cpImgZoom() {
  var src = document.getElementById('cp-result-img').src;
  var ov  = document.getElementById('cp-zoom-overlay');
  document.getElementById('cp-zoom-img').src = src;
  ov.style.display = 'flex';
}

/* مراحل پیشرفت */
var CP_STEPS = ['uploading','preparing','generating','finalizing'];
var CP_MSGS  = [
  'در حال آپلود تصاویر...',
  'در حال آماده‌سازی...',
  'در حال ساخت تصویر...',
  'در حال نهایی‌سازی...',
];
var CP_PROGRESS = [15, 40, 75, 95];

function cpSetStep(index) {
  CP_STEPS.forEach(function(s, i) {
    var el   = document.getElementById('step-' + s);
    var line = el ? el.nextElementSibling : null;
    if (!el) return;
    el.classList.remove('is-active','is-done');
    if (i < index)  el.classList.add('is-done');
    if (i === index) el.classList.add('is-active');
    if (line && line.classList.contains('cp-progress__line')) {
      i < index ? line.classList.add('is-done') : line.classList.remove('is-done');
    }
  });
  document.getElementById('cp-progress-bar').style.width = CP_PROGRESS[index] + '%';
  document.getElementById('cp-progress-msg').textContent  = CP_MSGS[index];
}

function cpShowResult(imageUrl, tokensLeft) {
  document.getElementById('cp-progress').style.display = 'none';
  document.getElementById('cp-error').style.display    = 'none';
  document.getElementById('cp-result').style.display   = 'flex';
  document.getElementById('cp-result-img').src         = imageUrl;
  document.getElementById('cp-download-btn').href      = imageUrl;
  document.getElementById('cp-progress-bar').style.width = '100%';
  if (tokensLeft !== undefined) {
    document.getElementById('cp-token-left').textContent = 'موجودی: ' + tokensLeft + ' توکن';
  }
}

function cpShowError(msg) {
  document.getElementById('cp-progress').style.display = 'none';
  document.getElementById('cp-result').style.display   = 'none';
  document.getElementById('cp-error').style.display    = 'flex';
  if (msg) document.getElementById('cp-error-msg').textContent = msg;
}

function cpRegenerate() {
  document.getElementById('cp-submit-btn').click();
}

/* بستن با Escape */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') cpModalClose();
});
</script>
