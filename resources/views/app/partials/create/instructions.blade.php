{{--
    PARTIAL: create/instructions.blade.php
    بخش دوم — کارت دستورالعمل (dos & don'ts)
    متغیرها:
      $instructions = [
        ['type' => 'do',   'icon' => 'face',   'text' => 'عکس واضح از صورت'],
        ['type' => 'dont', 'icon' => 'glasses','text' => 'عینک آفتابی نپوشید'],
        ...
      ]
--}}

@php
  $instructions = $instructions ?? [
    ['type' => 'do',   'icon' => 'face',    'text' => 'عکس واضح از صورت بارگذاری کنید'],
    ['type' => 'do',   'icon' => 'light',   'text' => 'نور کافی در تصویر داشته باشید'],
    ['type' => 'do',   'icon' => 'single',  'text' => 'فقط یک نفر در تصویر باشد'],
    ['type' => 'dont', 'icon' => 'glasses', 'text' => 'از عینک آفتابی استفاده نکنید'],
    ['type' => 'dont', 'icon' => 'crop',    'text' => 'صورت را برش نزنید'],
    ['type' => 'dont', 'icon' => 'blur',    'text' => 'تصویر تاری ارسال نکنید'],
  ];

  $dos   = array_filter($instructions, fn($i) => $i['type'] === 'do');
  $donts = array_filter($instructions, fn($i) => $i['type'] === 'dont');

  $iconMap = [
    'face'    => '<path d="M12 3C7.03 3 3 7.03 3 12s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 14c-2.67 0-5.02-1.32-6.47-3.35C7.14 16.25 9.45 15.5 12 15.5s4.86.75 6.47 1.15C16.02 18.68 13.67 20 12 20z" fill="currentColor"/>',
    'light'   => '<circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.8" fill="none"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    'single'  => '<circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8" fill="none"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/>',
    'glasses' => '<path d="M6 9h2a3 3 0 0 1 3 3 3 3 0 0 1 3-3h2a3 3 0 0 1 3 3 3 3 0 0 1-3 3h-2a3 3 0 0 1-3-3 3 3 0 0 1-3 3H6a3 3 0 0 1-3-3 3 3 0 0 1 3-3zM3 12h3M18 12h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/>',
    'crop'    => '<path d="M6 2v14a2 2 0 0 0 2 2h14M18 22V8a2 2 0 0 0-2-2H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/>',
    'blur'    => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" stroke-dasharray="3 3" fill="none"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8" fill="none"/>',
  ];
@endphp

<section class="cp-instructions" dir="rtl">
  <div class="cp-instructions__inner">

    {{-- عنوان --}}
    <div class="cp-instructions__header">
      <div class="cp-instructions__icon-wrap">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="9" stroke="#a07af5" stroke-width="1.8"/>
          <path d="M12 8v5M12 16v.5" stroke="#a07af5" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <span class="cp-instructions__title">قبل از شروع بخوانید</span>
    </div>

    {{-- grid dos & don'ts --}}
    <div class="cp-instructions__grid">

      {{-- بخش بایدها --}}
      <div class="cp-instructions__col">
        <p class="cp-instructions__col-label cp-instructions__col-label--do">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="#0BBF53" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          باید
        </p>
        @foreach($dos as $item)
        <div class="cp-ins-item cp-ins-item--do">
          <div class="cp-ins-item__icon">
            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              {!! $iconMap[$item['icon']] ?? $iconMap['face'] !!}
            </svg>
          </div>
          <span class="cp-ins-item__text">{{ $item['text'] }}</span>
        </div>
        @endforeach
      </div>

      {{-- divider عمودی --}}
      <div class="cp-instructions__divider"></div>

      {{-- بخش نبایدها --}}
      <div class="cp-instructions__col">
        <p class="cp-instructions__col-label cp-instructions__col-label--dont">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#f05c5c" stroke-width="2.5" stroke-linecap="round"/></svg>
          نباید
        </p>
        @foreach($donts as $item)
        <div class="cp-ins-item cp-ins-item--dont">
          <div class="cp-ins-item__icon">
            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              {!! $iconMap[$item['icon']] ?? $iconMap['blur'] !!}
            </svg>
          </div>
          <span class="cp-ins-item__text">{{ $item['text'] }}</span>
        </div>
        @endforeach
      </div>

    </div>

  </div>
</section>

<style>
/* ═══════════════════════════════
   CP-INSTRUCTIONS
═══════════════════════════════ */
.cp-instructions {
  padding: 14px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-instructions__inner {
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(160,122,245,0.15);
}

/* عنوان */
.cp-instructions__header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.cp-instructions__icon-wrap {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(160,122,245,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cp-instructions__title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text, #fff);
  font-family: 'YekanBakh', sans-serif;
}

/* grid */
.cp-instructions__grid {
  display: flex;
  gap: 0;
  align-items: flex-start;
}

.cp-instructions__col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.cp-instructions__col:first-child {
  padding-left: 14px;
}

.cp-instructions__col:last-child {
  padding-right: 14px;
}

.cp-instructions__divider {
  width: 1px;
  background: rgba(255,255,255,0.08);
  align-self: stretch;
  flex-shrink: 0;
}

html.light .cp-instructions__divider {
  background: rgba(0,0,0,0.07);
}

/* label ستون */
.cp-instructions__col-label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 700;
  font-family: 'YekanBakh', sans-serif;
  margin-bottom: 2px;
}

.cp-instructions__col-label--do   { color: #0BBF53; }
.cp-instructions__col-label--dont { color: #f05c5c; }

/* آیتم */
.cp-ins-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.cp-ins-item__icon {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cp-ins-item--do .cp-ins-item__icon {
  background: rgba(11,191,83,0.1);
  color: #0BBF53;
}

.cp-ins-item--dont .cp-ins-item__icon {
  background: rgba(240,92,92,0.1);
  color: #f05c5c;
}

.cp-ins-item__text {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255,255,255,0.7);
  line-height: 1.6;
  padding-top: 6px;
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-ins-item__text {
  color: rgba(0,0,0,0.6);
}

/* تبلت */
@media (min-width: 640px) {
  .cp-instructions { padding: 14px 24px 0; }
  .cp-ins-item__text { font-size: 13px; }
}

/* دسکتاپ */
@media (min-width: 1024px) {
  .cp-instructions__inner {
    padding: 16px;
    border-radius: 14px;
  }
  .cp-instructions__header { margin-bottom: 12px; }
  .cp-instructions__col:first-child { padding-left: 12px; }
  .cp-instructions__col:last-child  { padding-right: 12px; }
  .cp-ins-item__text { font-size: 11.5px; }
  .cp-ins-item__icon { width: 28px; height: 28px; border-radius: 8px; }
  .cp-instructions__col { gap: 8px; }
}
</style>
