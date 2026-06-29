{{--
    PARTIAL: create/upload.blade.php
    بخش سوم — آپلود تصویر
    متغیرها:
      $uploadSlots = [
        ['key' => 'front',   'label' => 'عکس روبرو',    'hint' => 'صورت واضح'],
        ['key' => 'profile', 'label' => 'عکس نیم‌رخ',   'hint' => 'اختیاری'],
      ]
--}}

@php
  $uploadSlots = $uploadSlots ?? [
    ['key' => 'front',   'label' => 'عکس روبرو',    'hint' => 'صورت کامل و واضح'],
    ['key' => 'profile', 'label' => 'عکس نیم‌رخ',   'hint' => 'از پهلو یا سه‌رخ'],
  ];
@endphp

<section class="cp-upload" dir="rtl">
  <div class="cp-upload__inner">

    <div class="cp-section-label">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      بارگذاری تصاویر
      <span class="cp-section-label__required">ضروری</span>
    </div>

    {{-- grid کارت‌های آپلود --}}
    <div class="cp-upload__grid cp-upload__grid--{{ count($uploadSlots) }}">

      @foreach($uploadSlots as $slot)
      <div
        class="cp-upload-card"
        id="upload-card-{{ $slot['key'] }}"
        data-slot="{{ $slot['key'] }}"
        ondragover="cpUploadDragOver(event, this)"
        ondragleave="cpUploadDragLeave(event, this)"
        ondrop="cpUploadDrop(event, this)"
        onclick="document.getElementById('file-{{ $slot['key'] }}').click()"
        role="button"
        tabindex="0"
        aria-label="بارگذاری {{ $slot['label'] }}"
        onkeydown="if(event.key==='Enter'||event.key===' ')this.click()"
      >
        {{-- پیش‌نمایش (پنهان تا انتخاب شود) --}}
        <div class="cp-upload-card__preview" id="preview-{{ $slot['key'] }}" style="display:none;">
          <img id="preview-img-{{ $slot['key'] }}" src="" alt="پیش‌نمایش" class="cp-upload-card__preview-img">
          <div class="cp-upload-card__preview-overlay">
            <button
              type="button"
              class="cp-upload-card__del"
              onclick="cpUploadRemove(event, '{{ $slot['key'] }}')"
              aria-label="حذف تصویر"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              </svg>
            </button>
            <button
              type="button"
              class="cp-upload-card__replace"
              onclick="cpUploadReplace(event, '{{ $slot['key'] }}')"
              aria-label="تعویض تصویر"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              تعویض
            </button>
          </div>
        </div>

        {{-- حالت خالی --}}
        <div class="cp-upload-card__empty" id="empty-{{ $slot['key'] }}">
          <div class="cp-upload-card__icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="1.6"/>
              <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
              <path d="M3 15L8 10L12 14L16 11L21 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 20v-4M10 18h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </div>
          <p class="cp-upload-card__label">{{ $slot['label'] }}</p>
          <p class="cp-upload-card__hint">{{ $slot['hint'] }}</p>
          <div class="cp-upload-card__btn">انتخاب تصویر</div>
        </div>

        {{-- loading --}}
        <div class="cp-upload-card__loading" id="loading-{{ $slot['key'] }}" style="display:none;">
          <div class="cp-upload-spinner"></div>
          <span>در حال بارگذاری...</span>
        </div>

        {{-- input مخفی --}}
        <input
          type="file"
          id="file-{{ $slot['key'] }}"
          name="uploads[{{ $slot['key'] }}]"
          accept="image/jpeg,image/png,image/webp"
          style="display:none;"
          onchange="cpUploadSelect(event, '{{ $slot['key'] }}')"
        >
      </div>
      @endforeach

    </div>

    {{-- پیام خطا --}}
    <div class="cp-upload__error" id="upload-error" style="display:none;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#f05c5c" stroke-width="1.8"/><path d="M12 8v5M12 15.5v.5" stroke="#f05c5c" stroke-width="1.8" stroke-linecap="round"/></svg>
      <span id="upload-error-text">لطفاً همه تصاویر را بارگذاری کنید.</span>
    </div>

  </div>
</section>

<style>
/* ═══════════════════════════════
   SECTION LABEL (shared)
═══════════════════════════════ */
.cp-section-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text, #fff);
  font-family: 'YekanBakh', sans-serif;
  margin-bottom: 12px;
}

.cp-section-label svg {
  color: rgba(255,255,255,0.5);
  flex-shrink: 0;
}

html.light .cp-section-label svg {
  color: rgba(0,0,0,0.35);
}

.cp-section-label__required {
  margin-right: auto;
  font-size: 10px;
  font-weight: 500;
  color: #f05c5c;
  background: rgba(240,92,92,0.1);
  border: 1px solid rgba(240,92,92,0.2);
  border-radius: 6px;
  padding: 2px 7px;
}

/* ═══════════════════════════════
   CP-UPLOAD
═══════════════════════════════ */
.cp-upload {
  padding: 14px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-upload__inner {
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
}

/* grid */
.cp-upload__grid {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr;
}

.cp-upload__grid--2 { grid-template-columns: 1fr 1fr; }
.cp-upload__grid--3 { grid-template-columns: 1fr 1fr 1fr; }
.cp-upload__grid--4 { grid-template-columns: 1fr 1fr; }
.cp-upload__grid--5,
.cp-upload__grid--6 { grid-template-columns: 1fr 1fr 1fr; }

/* کارت آپلود */
.cp-upload-card {
  position: relative;
  aspect-ratio: 1 / 1.1;
  border-radius: 14px;
  border: 1.5px dashed rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.03);
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.2s, background 0.2s, transform 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  -webkit-tap-highlight-color: transparent;
  outline: none;
}

.cp-upload-card:focus-visible {
  border-color: #a07af5;
  box-shadow: 0 0 0 2px rgba(160,122,245,0.3);
}

.cp-upload-card:hover {
  border-color: rgba(255,255,255,0.35);
  background: rgba(255,255,255,0.05);
}

.cp-upload-card.is-dragover {
  border-color: #a07af5;
  background: rgba(160,122,245,0.08);
  transform: scale(1.02);
}

.cp-upload-card.is-filled {
  border-color: rgba(11,191,83,0.4);
  border-style: solid;
}

html.light .cp-upload-card {
  border-color: rgba(0,0,0,0.12);
  background: rgba(0,0,0,0.02);
}

html.light .cp-upload-card:hover {
  border-color: rgba(0,0,0,0.25);
  background: rgba(0,0,0,0.04);
}

/* حالت خالی */
.cp-upload-card__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 8px;
  text-align: center;
  width: 100%;
  height: 100%;
}

.cp-upload-card__icon {
  color: rgba(255,255,255,0.25);
  margin-bottom: 2px;
}

html.light .cp-upload-card__icon {
  color: rgba(0,0,0,0.2);
}

.cp-upload-card__label {
  font-size: 12px;
  font-weight: 700;
  color: rgba(255,255,255,0.75);
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-upload-card__label {
  color: rgba(0,0,0,0.65);
}

.cp-upload-card__hint {
  font-size: 10px;
  font-weight: 400;
  color: rgba(255,255,255,0.35);
  font-family: 'YekanBakh', sans-serif;
  line-height: 1.5;
}

html.light .cp-upload-card__hint {
  color: rgba(0,0,0,0.35);
}

.cp-upload-card__btn {
  margin-top: 6px;
  font-size: 10px;
  font-weight: 600;
  color: #a07af5;
  background: rgba(160,122,245,0.1);
  border: 1px solid rgba(160,122,245,0.2);
  border-radius: 8px;
  padding: 4px 10px;
  font-family: 'YekanBakh', sans-serif;
}

/* پیش‌نمایش */
.cp-upload-card__preview {
  position: absolute;
  inset: 0;
}

.cp-upload-card__preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.cp-upload-card__preview-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%);
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding: 8px;
  opacity: 0;
  transition: opacity 0.2s;
}

.cp-upload-card:hover .cp-upload-card__preview-overlay,
.cp-upload-card:focus-within .cp-upload-card__preview-overlay {
  opacity: 1;
}

.cp-upload-card__del {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(240,92,92,0.85);
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
}

.cp-upload-card__replace {
  display: flex;
  align-items: center;
  gap: 4px;
  height: 30px;
  border-radius: 8px;
  background: rgba(255,255,255,0.15);
  border: none;
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  font-family: 'YekanBakh', sans-serif;
  padding: 0 10px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

/* loading */
.cp-upload-card__loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(0,0,0,0.5);
  font-size: 11px;
  color: rgba(255,255,255,0.7);
  font-family: 'YekanBakh', sans-serif;
}

.cp-upload-spinner {
  width: 22px;
  height: 22px;
  border: 2.5px solid rgba(255,255,255,0.15);
  border-top-color: #a07af5;
  border-radius: 50%;
  animation: cpSpin 0.7s linear infinite;
}

@keyframes cpSpin { to { transform: rotate(360deg); } }

/* خطا */
.cp-upload__error {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 10px;
  font-size: 12px;
  color: #f05c5c;
  font-family: 'YekanBakh', sans-serif;
}

/* تبلت */
@media (min-width: 640px) {
  .cp-upload { padding: 14px 24px 0; }
  .cp-upload-card__label { font-size: 13px; }
  .cp-upload-card__hint  { font-size: 11px; }
}

/* دسکتاپ */
@media (min-width: 1024px) {
  .cp-upload__inner {
    padding: 18px;
    border-radius: 14px;
  }

  /* grid آپلود: حداکثر سه‌ستون در main col */
  .cp-upload__grid       { gap: 14px; }
  .cp-upload__grid--2    { grid-template-columns: 1fr 1fr; }
  .cp-upload__grid--3,
  .cp-upload__grid--5,
  .cp-upload__grid--6    { grid-template-columns: 1fr 1fr 1fr; }
  .cp-upload__grid--4    { grid-template-columns: 1fr 1fr; }

  .cp-upload-card {
    aspect-ratio: 3 / 4;
    border-radius: 12px;
    border-width: 1.5px;
  }

  .cp-upload-card__label { font-size: 12px; }
  .cp-upload-card__hint  { font-size: 10.5px; }

  .cp-upload-card__btn {
    font-size: 10px;
    padding: 4px 10px;
  }

  /* overlay همیشه نیمه‌نمایان روی دسکتاپ */
  .cp-upload-card.is-filled .cp-upload-card__preview-overlay {
    opacity: 0;
  }
  .cp-upload-card.is-filled:hover .cp-upload-card__preview-overlay {
    opacity: 1;
  }
}
</style>

<script>
/* ── Upload Logic ── */
function cpUploadSelect(event, key) {
  var file = event.target.files[0];
  if (!file) return;
  cpUploadProcess(key, file);
}

function cpUploadDragOver(event, el) {
  event.preventDefault();
  el.classList.add('is-dragover');
}

function cpUploadDragLeave(event, el) {
  if (!el.contains(event.relatedTarget)) {
    el.classList.remove('is-dragover');
  }
}

function cpUploadDrop(event, el) {
  event.preventDefault();
  el.classList.remove('is-dragover');
  var file = event.dataTransfer.files[0];
  var key  = el.dataset.slot;
  if (!file || !file.type.startsWith('image/')) {
    cpUploadShowError('فایل باید یک تصویر باشد.');
    return;
  }
  cpUploadProcess(key, file);
}

function cpUploadProcess(key, file) {
  if (file.size > 10 * 1024 * 1024) {
    cpUploadShowError('حجم تصویر باید کمتر از ۱۰ مگابایت باشد.');
    return;
  }

  var loading = document.getElementById('loading-' + key);
  var empty   = document.getElementById('empty-' + key);
  var preview = document.getElementById('preview-' + key);
  var img     = document.getElementById('preview-img-' + key);
  var card    = document.getElementById('upload-card-' + key);

  empty.style.display   = 'none';
  loading.style.display = 'flex';

  var reader = new FileReader();
  reader.onload = function(e) {
    img.src = e.target.result;
    loading.style.display = 'none';
    preview.style.display = 'block';
    card.classList.add('is-filled');
    cpUploadHideError();
    cpUploadOnChange();
  };
  reader.readAsDataURL(file);
}

function cpUploadRemove(event, key) {
  event.stopPropagation();
  var card    = document.getElementById('upload-card-' + key);
  var preview = document.getElementById('preview-' + key);
  var empty   = document.getElementById('empty-' + key);
  var input   = document.getElementById('file-' + key);

  preview.style.display = 'none';
  empty.style.display   = 'flex';
  card.classList.remove('is-filled');
  input.value = '';
  cpUploadOnChange();
}

function cpUploadReplace(event, key) {
  event.stopPropagation();
  document.getElementById('file-' + key).click();
}

function cpUploadShowError(msg) {
  var el = document.getElementById('upload-error');
  var tx = document.getElementById('upload-error-text');
  if (el && tx) { tx.textContent = msg; el.style.display = 'flex'; }
}

function cpUploadHideError() {
  var el = document.getElementById('upload-error');
  if (el) el.style.display = 'none';
}

function cpUploadOnChange() {
  /* به summary اطلاع بده */
  document.dispatchEvent(new CustomEvent('cp:upload-changed'));
}
</script>
