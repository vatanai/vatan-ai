{{--
    PARTIAL: create/output-settings.blade.php
    بخش پنجم — تنظیمات خروجی (بدون expose پارامترهای AI)
    متغیرها:
      $outputSettings = [
        'aspect_ratios' => ['1:1','4:3','3:4','16:9'],
        'qualities'     => ['معمولی','بالا','خیلی بالا'],
        'show_format'   => false,
      ]
--}}

@php
$outputSettings = $outputSettings ?? [
  'aspect_ratios' => ['1:1', '4:3', '3:4', '16:9'],
  'qualities'     => ['معمولی', 'بالا', 'خیلی بالا'],
  'show_format'   => false,
];

$ratios    = $outputSettings['aspect_ratios'] ?? ['1:1','4:3','3:4'];
$qualities = $outputSettings['qualities']     ?? ['معمولی','بالا','خیلی بالا'];
$showFmt   = $outputSettings['show_format']   ?? false;

$ratioIcons = [
  '1:1'  => ['w'=>20,'h'=>20],
  '4:3'  => ['w'=>24,'h'=>18],
  '3:4'  => ['w'=>18,'h'=>24],
  '16:9' => ['w'=>28,'h'=>16],
  '9:16' => ['w'=>16,'h'=>28],
];
@endphp

<section class="cp-output" dir="rtl">
  <div class="cp-output__inner">

    <div class="cp-section-label">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.8"/>
        <path d="M3 9h18M9 21V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
      تنظیمات خروجی
    </div>

    <div class="cp-output__rows">

      {{-- نسبت تصویر --}}
      @if(count($ratios) > 0)
      <div class="cp-output__row">
        <p class="cp-output__row-label">نسبت تصویر</p>
        <div class="cp-ratio-group" role="radiogroup" aria-label="نسبت تصویر">
          @foreach($ratios as $i => $ratio)
          @php $dims = $ratioIcons[$ratio] ?? ['w'=>20,'h'=>20]; @endphp
          <label class="cp-ratio-item">
            <input
              type="radio"
              name="output[aspect_ratio]"
              value="{{ $ratio }}"
              class="cp-ratio-input"
              {{ $i === 0 ? 'checked' : '' }}
            >
            <span class="cp-ratio-box">
              <span class="cp-ratio-icon">
                <svg width="{{ $dims['w'] }}" height="{{ $dims['h'] }}" viewBox="0 0 {{ $dims['w'] }} {{ $dims['h'] }}" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="1" y="1" width="{{ $dims['w']-2 }}" height="{{ $dims['h']-2 }}" rx="2" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </span>
              <span class="cp-ratio-label">{{ $ratio }}</span>
            </span>
          </label>
          @endforeach
        </div>
      </div>
      @endif

      {{-- کیفیت --}}
      @if(count($qualities) > 0)
      <div class="cp-output__row">
        <p class="cp-output__row-label">کیفیت خروجی</p>
        <div class="cp-quality-group" role="radiogroup" aria-label="کیفیت">
          @foreach($qualities as $i => $q)
          <label class="cp-quality-item">
            <input
              type="radio"
              name="output[quality]"
              value="{{ $q }}"
              class="cp-quality-input"
              {{ $i === 1 ? 'checked' : '' }}
            >
            <span class="cp-quality-box">{{ $q }}</span>
          </label>
          @endforeach
        </div>
      </div>
      @endif

      {{-- فرمت خروجی (اختیاری) --}}
      @if($showFmt)
      <div class="cp-output__row">
        <p class="cp-output__row-label">فرمت فایل</p>
        <div class="cp-radio-group">
          @foreach(['JPG','PNG','WEBP'] as $fmt)
          <label class="cp-radio-item">
            <input type="radio" name="output[format]" value="{{ $fmt }}" class="cp-radio-input" {{ $fmt==='JPG'?'checked':'' }}>
            <span class="cp-radio-box">{{ $fmt }}</span>
          </label>
          @endforeach
        </div>
      </div>
      @endif

    </div>

  </div>
</section>

<style>
/* ═══════════════════════════════
   CP-OUTPUT
═══════════════════════════════ */
.cp-output {
  padding: 14px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-output__inner {
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
}

.cp-output__rows {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.cp-output__row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cp-output__row-label {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,0.5);
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-output__row-label { color: rgba(0,0,0,0.4); }

/* نسبت تصویر */
.cp-ratio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cp-ratio-item { cursor: pointer; }
.cp-ratio-input { display: none; }

.cp-ratio-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1.5px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  transition: all 0.18s;
  min-width: 58px;
}

.cp-ratio-input:checked + .cp-ratio-box {
  border-color: #a07af5;
  background: rgba(160,122,245,0.1);
}

.cp-ratio-icon {
  color: rgba(255,255,255,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  height: 28px;
}

.cp-ratio-input:checked + .cp-ratio-box .cp-ratio-icon {
  color: #a07af5;
}

.cp-ratio-label {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255,255,255,0.55);
  font-family: 'YekanBakh', sans-serif;
}

.cp-ratio-input:checked + .cp-ratio-box .cp-ratio-label {
  color: #a07af5;
}

html.light .cp-ratio-box {
  border-color: rgba(0,0,0,0.08);
  background: rgba(0,0,0,0.03);
}
html.light .cp-ratio-input:checked + .cp-ratio-box {
  border-color: #a07af5;
  background: rgba(160,122,245,0.07);
}
html.light .cp-ratio-icon { color: rgba(0,0,0,0.3); }
html.light .cp-ratio-label { color: rgba(0,0,0,0.45); }

/* کیفیت */
.cp-quality-group {
  display: flex;
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
}

.cp-quality-item {
  flex: 1;
  cursor: pointer;
}

.cp-quality-input { display: none; }

.cp-quality-box {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 4px;
  font-size: 12px;
  font-weight: 500;
  color: rgba(255,255,255,0.45);
  font-family: 'YekanBakh', sans-serif;
  transition: all 0.18s;
  text-align: center;
  border-right: 1px solid rgba(255,255,255,0.08);
}

.cp-quality-item:last-child .cp-quality-box {
  border-right: none;
}

.cp-quality-input:checked + .cp-quality-box {
  background: rgba(160,122,245,0.15);
  color: #a07af5;
  font-weight: 700;
}

html.light .cp-quality-group {
  border-color: rgba(0,0,0,0.08);
  background: rgba(0,0,0,0.02);
}
html.light .cp-quality-box {
  color: rgba(0,0,0,0.4);
  border-right-color: rgba(0,0,0,0.06);
}
html.light .cp-quality-input:checked + .cp-quality-box {
  background: rgba(160,122,245,0.08);
  color: #7c56d4;
}

@media (min-width: 640px) {
  .cp-output { padding: 14px 24px 0; }
}

@media (min-width: 1024px) {
  .cp-output__inner {
    padding: 18px;
    border-radius: 14px;
  }

  /* نسبت‌ها و کیفیت کنار هم در یک ردیف */
  .cp-output__rows {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
  }

  .cp-output__row-label { font-size: 11.5px; }

  .cp-ratio-box {
    padding: 8px 10px;
    min-width: 52px;
    border-radius: 10px;
  }

  .cp-quality-box { padding: 9px 4px; font-size: 12px; }
}
</style>
