{{--
    PARTIAL: create/options.blade.php
    بخش چهارم — فیلدهای داینامیک محصول
    متغیر $fields آرایه‌ای از فیلدهاست:
    [
      ['key'=>'country','type'=>'dropdown','label'=>'کشور مقصد','required'=>true,'options'=>['ایران','ترکیه']],
      ['key'=>'age',    'type'=>'number',  'label'=>'سن',        'required'=>false,'min'=>18,'max'=>80],
      ['key'=>'hair',   'type'=>'radio',   'label'=>'رنگ مو',    'required'=>true,'options'=>['مشکی','قهوه‌ای','بلوند']],
      ['key'=>'bg',     'type'=>'toggle',  'label'=>'پس‌زمینه سفید','required'=>false],
      ...
    ]
--}}

@php
$fields = $fields ?? [
  [
    'key'      => 'style',
    'type'     => 'radio',
    'label'    => 'سبک تصویر',
    'required' => true,
    'options'  => ['رسمی', 'نیمه‌رسمی', 'خلاقانه'],
  ],
  [
    'key'      => 'bg_color',
    'type'     => 'dropdown',
    'label'    => 'رنگ پس‌زمینه',
    'required' => false,
    'options'  => ['سفید', 'خاکستری روشن', 'آبی ملایم', 'بدون پس‌زمینه'],
  ],
  [
    'key'      => 'gender',
    'type'     => 'radio',
    'label'    => 'جنسیت',
    'required' => true,
    'options'  => ['مرد', 'زن'],
  ],
  [
    'key'      => 'bio',
    'type'     => 'textarea',
    'label'    => 'توضیح اختیاری',
    'required' => false,
    'placeholder' => 'مثلاً: عکس برای لینکدین — ظاهر رسمی',
  ],
];
@endphp

@if(count($fields) > 0)
<section class="cp-options" dir="rtl">
  <div class="cp-options__inner">

    <div class="cp-section-label">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M15.5 3.5a2 2 0 1 1 2.83 2.83L10 14.5 6 15.5l1-4 8.5-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      اطلاعات تکمیلی
    </div>

    <div class="cp-options__fields">
      @foreach($fields as $field)
        <div class="cp-field" data-key="{{ $field['key'] }}">

          {{-- label --}}
          <label class="cp-field__label" for="field-{{ $field['key'] }}">
            {{ $field['label'] }}
            @if(!empty($field['required']))
              <span class="cp-field__req">*</span>
            @endif
          </label>

          {{-- text --}}
          @if($field['type'] === 'text')
            <input
              type="text"
              id="field-{{ $field['key'] }}"
              name="fields[{{ $field['key'] }}]"
              class="cp-input"
              placeholder="{{ $field['placeholder'] ?? '' }}"
              {{ !empty($field['required']) ? 'required' : '' }}
            >

          {{-- textarea --}}
          @elseif($field['type'] === 'textarea')
            <textarea
              id="field-{{ $field['key'] }}"
              name="fields[{{ $field['key'] }}]"
              class="cp-input cp-input--textarea"
              placeholder="{{ $field['placeholder'] ?? '' }}"
              rows="3"
              {{ !empty($field['required']) ? 'required' : '' }}
            ></textarea>

          {{-- number --}}
          @elseif($field['type'] === 'number')
            <input
              type="number"
              id="field-{{ $field['key'] }}"
              name="fields[{{ $field['key'] }}]"
              class="cp-input cp-input--number"
              min="{{ $field['min'] ?? 0 }}"
              max="{{ $field['max'] ?? 9999 }}"
              placeholder="{{ $field['placeholder'] ?? '' }}"
              {{ !empty($field['required']) ? 'required' : '' }}
            >

          {{-- dropdown --}}
          @elseif($field['type'] === 'dropdown')
            <div class="cp-select-wrap">
              <select
                id="field-{{ $field['key'] }}"
                name="fields[{{ $field['key'] }}]"
                class="cp-select"
                {{ !empty($field['required']) ? 'required' : '' }}
              >
                <option value="">انتخاب کنید...</option>
                @foreach($field['options'] as $opt)
                  <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
              </select>
              <svg class="cp-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>

          {{-- radio --}}
          @elseif($field['type'] === 'radio')
            <div class="cp-radio-group" role="radiogroup" aria-labelledby="label-{{ $field['key'] }}">
              @foreach($field['options'] as $i => $opt)
                <label class="cp-radio-item">
                  <input
                    type="radio"
                    name="fields[{{ $field['key'] }}]"
                    value="{{ $opt }}"
                    class="cp-radio-input"
                    {{ $i === 0 && !empty($field['required']) ? 'required' : '' }}
                  >
                  <span class="cp-radio-box">{{ $opt }}</span>
                </label>
              @endforeach
            </div>

          {{-- toggle --}}
          @elseif($field['type'] === 'toggle')
            <label class="cp-toggle-wrap">
              <input
                type="checkbox"
                id="field-{{ $field['key'] }}"
                name="fields[{{ $field['key'] }}]"
                value="1"
                class="cp-toggle-input"
              >
              <span class="cp-toggle-track">
                <span class="cp-toggle-thumb"></span>
              </span>
              <span class="cp-toggle-text">{{ $field['toggle_label'] ?? 'فعال' }}</span>
            </label>

          {{-- slider --}}
          @elseif($field['type'] === 'slider')
            <div class="cp-slider-wrap">
              <input
                type="range"
                id="field-{{ $field['key'] }}"
                name="fields[{{ $field['key'] }}]"
                class="cp-slider"
                min="{{ $field['min'] ?? 0 }}"
                max="{{ $field['max'] ?? 100 }}"
                value="{{ $field['default'] ?? 50 }}"
                oninput="document.getElementById('slider-val-{{ $field['key'] }}').textContent=this.value"
              >
              <span class="cp-slider-val" id="slider-val-{{ $field['key'] }}">{{ $field['default'] ?? 50 }}</span>
            </div>

          {{-- color --}}
          @elseif($field['type'] === 'color')
            <div class="cp-color-wrap">
              <input
                type="color"
                id="field-{{ $field['key'] }}"
                name="fields[{{ $field['key'] }}]"
                class="cp-color-input"
                value="{{ $field['default'] ?? '#ffffff' }}"
              >
              <span class="cp-color-label" id="color-label-{{ $field['key'] }}">{{ $field['default'] ?? '#ffffff' }}</span>
            </div>

          @endif

          {{-- پیام خطا --}}
          <p class="cp-field__error" id="error-{{ $field['key'] }}" style="display:none;">این فیلد الزامی است.</p>

        </div>
      @endforeach
    </div>

  </div>
</section>
@endif

<style>
/* ═══════════════════════════════
   CP-OPTIONS
═══════════════════════════════ */
.cp-options {
  padding: 14px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-options__inner {
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
}

.cp-options__fields {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* فیلد */
.cp-field { display: flex; flex-direction: column; gap: 8px; }

.cp-field__label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  font-family: 'YekanBakh', sans-serif;
  display: flex;
  align-items: center;
  gap: 4px;
}

html.light .cp-field__label { color: rgba(0,0,0,0.75); }

.cp-field__req { color: #f05c5c; font-size: 14px; line-height: 1; }

.cp-field__error {
  font-size: 11px;
  color: #f05c5c;
  font-family: 'YekanBakh', sans-serif;
}

/* input / textarea */
.cp-input {
  width: 100%;
  background: rgba(255,255,255,0.06);
  border: 1.5px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 11px 14px;
  font-size: 13px;
  font-family: 'YekanBakh', sans-serif;
  color: var(--text, #fff);
  outline: none;
  transition: border-color 0.2s, background 0.2s;
  direction: rtl;
}

.cp-input:focus {
  border-color: #a07af5;
  background: rgba(160,122,245,0.06);
}

.cp-input--textarea { resize: none; line-height: 1.7; }
.cp-input--number   { max-width: 140px; }

html.light .cp-input {
  background: rgba(0,0,0,0.04);
  border-color: rgba(0,0,0,0.1);
  color: #000;
}

html.light .cp-input:focus {
  border-color: #a07af5;
  background: rgba(160,122,245,0.04);
}

/* select */
.cp-select-wrap {
  position: relative;
}

.cp-select {
  width: 100%;
  appearance: none;
  -webkit-appearance: none;
  background: rgba(255,255,255,0.06);
  border: 1.5px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 11px 14px;
  padding-left: 36px;
  font-size: 13px;
  font-family: 'YekanBakh', sans-serif;
  color: var(--text, #fff);
  outline: none;
  cursor: pointer;
  direction: rtl;
  transition: border-color 0.2s;
}

.cp-select:focus { border-color: #a07af5; }

.cp-select option { background: #1e1e2e; color: #fff; }

html.light .cp-select {
  background: rgba(0,0,0,0.04);
  border-color: rgba(0,0,0,0.1);
  color: #000;
}
html.light .cp-select option { background: #fff; color: #000; }

.cp-select-arrow {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.4);
  pointer-events: none;
}

html.light .cp-select-arrow { color: rgba(0,0,0,0.35); }

/* radio */
.cp-radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cp-radio-item { cursor: pointer; }

.cp-radio-input { display: none; }

.cp-radio-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  border-radius: 10px;
  border: 1.5px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.05);
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,0.6);
  font-family: 'YekanBakh', sans-serif;
  transition: all 0.18s;
  user-select: none;
}

.cp-radio-input:checked + .cp-radio-box {
  border-color: #a07af5;
  background: rgba(160,122,245,0.12);
  color: #a07af5;
  font-weight: 700;
}

html.light .cp-radio-box {
  border-color: rgba(0,0,0,0.1);
  background: rgba(0,0,0,0.03);
  color: rgba(0,0,0,0.5);
}
html.light .cp-radio-input:checked + .cp-radio-box {
  border-color: #a07af5;
  background: rgba(160,122,245,0.08);
  color: #7c56d4;
}

/* toggle */
.cp-toggle-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.cp-toggle-input { display: none; }

.cp-toggle-track {
  width: 44px;
  height: 26px;
  border-radius: 999px;
  background: rgba(255,255,255,0.12);
  position: relative;
  transition: background 0.22s;
  flex-shrink: 0;
}

.cp-toggle-input:checked ~ .cp-toggle-track {
  background: #0BBF53;
}

.cp-toggle-thumb {
  position: absolute;
  top: 3px;
  right: 3px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #fff;
  transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1);
  box-shadow: 0 1px 4px rgba(0,0,0,0.25);
}

.cp-toggle-input:checked ~ .cp-toggle-track .cp-toggle-thumb {
  transform: translateX(-18px);
}

.cp-toggle-text {
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,0.7);
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-toggle-text { color: rgba(0,0,0,0.6); }

/* slider */
.cp-slider-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.cp-slider {
  flex: 1;
  -webkit-appearance: none;
  appearance: none;
  height: 4px;
  border-radius: 2px;
  background: rgba(255,255,255,0.12);
  outline: none;
  cursor: pointer;
}

.cp-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #a07af5;
  box-shadow: 0 1px 6px rgba(160,122,245,0.5);
  cursor: pointer;
}

.cp-slider-val {
  font-size: 13px;
  font-weight: 700;
  color: #a07af5;
  font-family: 'YekanBakh', sans-serif;
  min-width: 28px;
  text-align: center;
}

/* color */
.cp-color-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cp-color-input {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1.5px solid rgba(255,255,255,0.12);
  background: none;
  cursor: pointer;
  padding: 2px;
}

.cp-color-label {
  font-size: 12px;
  color: rgba(255,255,255,0.5);
  font-family: 'YekanBakh', sans-serif;
}

@media (min-width: 640px) {
  .cp-options { padding: 14px 24px 0; }

  .cp-options__fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 24px;
  }

  .cp-field:has(.cp-input--textarea),
  .cp-field:has(.cp-radio-group),
  .cp-field:has(.cp-slider-wrap) {
    grid-column: 1 / -1;
  }
}
</style>
