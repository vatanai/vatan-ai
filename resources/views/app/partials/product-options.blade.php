{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: تنظیمات داینامیک محصول (Variables) + دکمه بزرگ «شروع ساخت»
     تمام فیلدها کاملاً از input_schema محصول (دیتابیس) ساخته می‌شوند — بدون Hard Code.
     انواع پشتیبانی‌شده: select, radio, checkbox, text, textarea, number, switch, color, image_upload/file_upload
     اگر select/radio گزینه‌ای نداشته باشند (هنوز در ادمین تنظیم نشده)، به‌صورت ایمن به ورودی متنی ساده می‌افتند.
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__fields = is_array($product->input_schema) ? $product->input_schema : [];
@endphp

<div class="w-full flex flex-col gap-4 mt-1" id="pdpOptions">

  @if(count($__fields))
    {{-- اگر hideFields پاس داده شود، باکس تنظیمات از دید مخفی می‌شود ولی فیلدها در DOM می‌مانند تا مقادیر پیش‌فرض به بک‌اند برسند --}}
    <div class="flex flex-col gap-4 p-5 sm:p-6 rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)]" @if($hideFields ?? false) style="display:none" @endif>
      <h3 class="text-[11px] font-bold text-[var(--text-secondary)] uppercase tracking-widest">تنظیمات محصول</h3>

      @foreach($__fields as $__field)
        @php
          $__fid       = $__field['field_id'] ?? null;
          $__flabel    = $__field['label_fa'] ?? $__fid;
          $__ftype     = $__field['type'] ?? 'text';
          $__frequired = (string) ($__field['required'] ?? '0') === '1';
          $__fph       = $__field['placeholder'] ?? '';
          $__fhelp     = $__field['description'] ?? $__field['help_text'] ?? '';
          $__fmin      = $__field['min'] ?? null;
          $__fmax      = $__field['max'] ?? null;
          $__rawOptions = $__field['options'] ?? [];
          $__optionsArr = is_array($__rawOptions) ? $__rawOptions : explode(',', (string) $__rawOptions);
          $__foptions  = collect($__optionsArr)->map(fn ($o) => trim((string) (is_array($o) ? ($o['label'] ?? $o['value'] ?? '') : $o)))->filter()->values();
        @endphp

        @if($__fid)
        <div class="flex flex-col gap-1.5 pdp-field" data-field-id="{{ $__fid }}" data-field-type="{{ $__ftype }}" data-required="{{ $__frequired ? '1' : '0' }}">

          @unless($__ftype === 'switch' || ($__ftype === 'checkbox' && !$__foptions->count()))
            <label class="text-xs font-bold text-[var(--text-primary)]">
              {{ $__flabel }}
              @if($__frequired)<span class="text-[var(--red)]">*</span>@endif
            </label>
          @endunless

          @if($__fhelp)
            <p class="text-[10.5px] text-[var(--text-secondary)] -mt-1">{{ $__fhelp }}</p>
          @endif

          @if($__ftype === 'textarea')
            <textarea class="pdp-field-input min-h-[90px] px-3 py-2 rounded-xl bg-[var(--bg-page)] border border-[var(--border-subtle)] text-sm text-[var(--text-primary)] outline-none focus:border-[var(--green)] transition-colors" placeholder="{{ $__fph }}"></textarea>

          @elseif($__ftype === 'number')
            <input type="number" class="pdp-field-input h-10 px-3 rounded-xl bg-[var(--bg-page)] border border-[var(--border-subtle)] text-sm text-[var(--text-primary)] outline-none focus:border-[var(--green)] transition-colors"
                   @if(is_numeric($__fmin)) min="{{ $__fmin }}" @endif
                   @if(is_numeric($__fmax)) max="{{ $__fmax }}" @endif
                   placeholder="{{ $__fph }}">

          @elseif($__ftype === 'select' && $__foptions->count())
            <select class="pdp-field-input h-10 px-3 rounded-xl bg-[var(--bg-page)] border border-[var(--border-subtle)] text-sm text-[var(--text-primary)] outline-none focus:border-[var(--green)] transition-colors">
              <option value="" disabled selected>انتخاب کنید...</option>
              @foreach($__foptions as $__opt)
                <option value="{{ $__opt }}">{{ $__opt }}</option>
              @endforeach
            </select>

          @elseif($__ftype === 'radio' && $__foptions->count())
            <div class="flex flex-wrap gap-2">
              @foreach($__foptions as $__i => $__opt)
                <label class="cursor-pointer">
                  <input type="radio" name="pdp_radio_{{ $__fid }}" value="{{ $__opt }}" class="sr-only peer" {{ $__i === 0 ? 'checked' : '' }}>
                  <span class="inline-flex items-center px-3 h-9 rounded-full border border-[var(--border-subtle)] bg-[var(--bg-page)] text-[12px] font-bold text-[var(--text-secondary)] peer-checked:border-[var(--green)] peer-checked:text-[var(--text-primary)] transition-colors">{{ $__opt }}</span>
                </label>
              @endforeach
            </div>

          @elseif($__ftype === 'checkbox' && $__foptions->count())
            <div class="flex flex-wrap gap-2">
              @foreach($__foptions as $__opt)
                <label class="cursor-pointer">
                  <input type="checkbox" value="{{ $__opt }}" class="sr-only peer pdp-checkbox-multi">
                  <span class="inline-flex items-center px-3 h-9 rounded-full border border-[var(--border-subtle)] bg-[var(--bg-page)] text-[12px] font-bold text-[var(--text-secondary)] peer-checked:border-[var(--green)] peer-checked:text-[var(--text-primary)] transition-colors">{{ $__opt }}</span>
                </label>
              @endforeach
            </div>

          @elseif($__ftype === 'checkbox')
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" class="pdp-field-input w-4 h-4 accent-[var(--green)]">
              <span class="text-xs font-bold text-[var(--text-primary)]">{{ $__flabel }} @if($__frequired)<span class="text-[var(--red)]">*</span>@endif</span>
            </label>

          @elseif($__ftype === 'switch')
            <label class="flex items-center justify-between gap-3 cursor-pointer">
              <span class="text-xs font-bold text-[var(--text-primary)]">{{ $__flabel }} @if($__frequired)<span class="text-[var(--red)]">*</span>@endif</span>
              <span class="pdp-switch relative inline-block w-11 h-6 shrink-0">
                <input type="checkbox" class="pdp-field-input peer sr-only">
                <span class="absolute inset-0 rounded-full bg-[var(--border-subtle)] peer-checked:bg-[var(--green)] transition-colors pointer-events-none"></span>
                <span class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:-translate-x-5 pointer-events-none"></span>
              </span>
            </label>

          @elseif($__ftype === 'color')
            <input type="color" class="pdp-field-input w-14 h-10 rounded-xl border border-[var(--border-subtle)] bg-[var(--bg-page)] p-1" value="#16594f">

          @elseif(in_array($__ftype, ['image_upload', 'file_upload']))
            <div class="pdp-upload-box cursor-pointer rounded-xl border border-dashed border-[var(--border-subtle)] bg-[var(--bg-page)] p-3.5 flex items-center gap-3 hover:border-[var(--green)] transition-colors"
                 onclick="this.querySelector('input[type=file]').click()" data-upload-key="uploads[{{ $__fid }}]">
              <div class="w-9 h-9 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-subtle)] flex items-center justify-center text-[var(--text-secondary)] shrink-0">
                <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="pdp-upload-name text-[11px] font-bold text-[var(--text-primary)] truncate">{{ $__fph ?: 'انتخاب فایل...' }}</p>
              </div>
              <input type="file" class="pdp-field-input hidden" accept="{{ $__ftype === 'image_upload' ? 'image/*' : '' }}"
                     onchange="this.closest('.pdp-upload-box').querySelector('.pdp-upload-name').textContent = (this.files[0] ? this.files[0].name : '{{ $__fph ?: 'انتخاب فایل...' }}')">
            </div>

          @else
            {{-- fallback ایمن: text (شامل select/radio بدون گزینه تعریف‌شده) --}}
            <input type="text" class="pdp-field-input h-10 px-3 rounded-xl bg-[var(--bg-page)] border border-[var(--border-subtle)] text-sm text-[var(--text-primary)] outline-none focus:border-[var(--green)] transition-colors" placeholder="{{ $__fph }}">
          @endif

        </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- دکمه بزرگ «شروع ساخت» — همیشه واضح و قابل مشاهده --}}
  @php
    $__buildUrl = route('app.create', ['product' => $product->route_slug]);
    $__buildTarget = auth()->check()
      ? $__buildUrl
      : route('login', ['redirect' => $__buildUrl]);
  @endphp
  <div class="pt-1">
    <button type="button" onclick="window.location.href={{ Js::from($__buildTarget) }}" class="vatan-gen-btn" aria-label="شروع ساخت">
      <span class="text_button">{{ $genButtonLabel ?? 'شروع ساخت' }}</span>
      <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
    </button>
  </div>

</div>

<style>
/* دکمه «شروع ساخت» — هم‌ظاهر با دکمه نهایی صفحه ساخت محصول */
.vatan-gen-btn {
  width:100%;height:48px;display:flex;align-items:center;justify-content:center;gap:8px;
  padding:0 16px;border:0;border-radius:11px;background:var(--green);color:var(--bg-page);
  box-shadow:0 8px 25px color-mix(in srgb,var(--green) 14%,transparent);
  cursor:pointer;direction:ltr;transition:filter .18s ease,transform .14s ease;
  font-family: 'YekanBakh', sans-serif;
}
.vatan-gen-btn:hover { filter:brightness(1.04); }
.vatan-gen-btn:active { transform:scale(.99); }
.vatan-gen-btn .text_button {
  font-size:12px;font-weight:900;color:inherit;direction:rtl;
}
.vatan-gen-btn>i { font-size:12px; }
</style>
