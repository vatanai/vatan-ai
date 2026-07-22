{{-- پارشیال: گام دوم — هوش مصنوعی (پایپ‌لاین + پرامپت) --}}
{{-- بعد از تبدیل ویزارد به ۵ مرحله، این پارشیال فقط Card ۱ و ۲ (پایپ‌لاین و تنظیمات پرامپت/تست) را دارد؛
     Card متغیرها و فیلدهای ورودی به step-3.blade.php منتقل شد (گام سوم جدید).
     این بخش نیاز به متغیر $aiModels دارد که از کنترلر پاس داده می‌شود.
     تمام name های ورودی و منطق موجود (از جمله فراخوانی واقعی تست پرامپت به Backend) دقیقاً حفظ شده‌اند. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

{{-- ═══════════════════ Card ۰ — نوع سوژه و حفظ هویت ═══════════════════ --}}
@php
  $curSubjectType   = old('subject_type', optional($duplicateFrom)->subject_type ?? 'generic');
  $curIdentityOn    = old('identity_preservation', optional($duplicateFrom)->identity_preservation ?? false);
  $curIdentityStr   = old('identity_strength', optional($duplicateFrom)->identity_strength ?? 80);
  $curPreserveBody  = old('preserve_body', optional($duplicateFrom)->preserve_body ?? false);
  $curMinRef        = old('min_reference_images', optional($duplicateFrom)->min_reference_images ?? 0);
  $curMaxRef        = old('max_reference_images', optional($duplicateFrom)->max_reference_images ?? 1);
@endphp
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-user-check text-[var(--accent)]"></i> نوع سوژه و حفظ هویت</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">تعیین می‌کند خروجی چقدر باید به کاربر (چهره/هیکل) شبیه باشد — مهم‌ترین عامل دقت</div>
  </div>

  {{-- نوع سوژه محصول --}}
  <div class="flex flex-col gap-1.5 mb-4">
    <label class="text-xs font-semibold text-[var(--text2)]">نوع سوژهٔ محصول</label>
    @php
      $subjectOptions = [
        'generic' => ['عمومی', 'fa-shapes'],
        'face'    => ['چهره‌محور', 'fa-face-smile'],
        'body'    => ['چهره و هیکل', 'fa-person'],
        'product' => ['محصول/شیء', 'fa-box'],
        'scene'   => ['صحنه/منظره', 'fa-image'],
      ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2.5">
      @foreach($subjectOptions as $val => $meta)
        <label class="subject-type-card flex flex-col items-center gap-1.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curSubjectType == $val ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="subject_type" value="{{ $val }}" {{ $curSubjectType == $val ? 'checked' : '' }} class="hidden" onchange="onSubjectTypeChange(this)">
          <i class="fa-solid {{ $meta[1] }} text-sm text-[var(--text2)]"></i>
          <span class="text-[11px] text-[var(--text2)] text-center">{{ $meta[0] }}</span>
        </label>
      @endforeach
    </div>
    <div class="text-[10px] text-[var(--text3)]">با انتخاب «چهره‌محور» یا «چهره و هیکل»، حفظ هویت به‌صورت خودکار روشن می‌شود.</div>
  </div>

  {{-- کلید اصلی حفظ هویت --}}
  <div class="flex items-center justify-between p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg mb-3.5">
    <div>
      <div class="text-[12.5px] font-semibold text-[var(--text2)]">فعال‌سازی حفظ هویت (Identity Preservation)</div>
      <div class="text-[11px] text-[var(--text3)] mt-0.5">خروجی باید همان شخصِ تصویر ورودی باشد — چهره و ویژگی‌ها حفظ شود</div>
    </div>
    <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
      <input type="checkbox" name="identity_preservation" value="1" id="identity-preservation-input" {{ $curIdentityOn ? 'checked' : '' }} class="sr-only peer" onchange="toggleIdentitySettings()">
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
    </label>
  </div>

  {{-- تنظیمات حفظ هویت — با روشن شدن کلید بالا نمایش داده می‌شوند --}}
  <div id="identity-settings-wrap" class="{{ $curIdentityOn ? '' : 'hidden' }} space-y-3.5">

    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center justify-between">
        <span>شدت شباهت به کاربر</span>
        <span class="text-[var(--accent)] font-mono text-[11px]" id="identity-strength-val">{{ $curIdentityStr }}%</span>
      </label>
      <input type="range" name="identity_strength" min="0" max="100" value="{{ $curIdentityStr }}" class="w-full accent-[var(--accent)]" oninput="document.getElementById('identity-strength-val').textContent = this.value + '%'">
      <div class="text-[10px] text-[var(--text3)]">هرچه بالاتر، خروجی به تصویر ورودی نزدیک‌تر و خلاقیت مدل کمتر می‌شود.</div>
    </div>

    <div class="flex items-center justify-between p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg">
      <div>
        <div class="text-[12.5px] font-semibold text-[var(--text2)]">حفظ هیکل و تناسب بدن</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">علاوه بر چهره، فرم بدن هم مشابه کاربر بماند</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="preserve_body" value="1" {{ $curPreserveBody ? 'checked' : '' }} class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">حداقل تصویر ورودی لازم</label>
        <input type="number" name="min_reference_images" min="0" max="20" value="{{ $curMinRef }}" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="0">
        <div class="text-[10px] text-[var(--text3)]">اگر بیشتر از ۰ باشد، کاربر بدون آپلود عکس نمی‌تواند اجرا کند.</div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">حداکثر تصویر ورودی مجاز</label>
        <input type="number" name="max_reference_images" min="0" max="20" value="{{ $curMaxRef }}" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="1">
      </div>
    </div>

    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">دستور اختصاصی حفظ هویت (انگلیسی — اختیاری)</label>
      <textarea name="identity_instructions" rows="3" spellcheck="false" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left font-mono leading-relaxed resize-y" placeholder="Keep the exact same face and identity of the person in the uploaded photo...">{{ old('identity_instructions', optional($duplicateFrom)->identity_instructions) }}</textarea>
      <div class="text-[10px] text-[var(--text3)]">خالی بگذارید تا دستور پیش‌فرض حرفه‌ای حفظ چهره (و هیکل در صورت فعال بودن) خودکار به پرامپت افزوده شود.</div>
    </div>
  </div>

  <script>
    function toggleIdentitySettings() {
      var on = document.getElementById('identity-preservation-input').checked;
      document.getElementById('identity-settings-wrap').classList.toggle('hidden', !on);
    }
    function onSubjectTypeChange(radio) {
      document.querySelectorAll('.subject-type-card').forEach(function (c) {
        c.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8');
      });
      if (radio.checked) radio.closest('.subject-type-card').classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');

      var idInput = document.getElementById('identity-preservation-input');
      var bodyInput = document.querySelector('input[name="preserve_body"]');
      if (radio.value === 'face' || radio.value === 'body') {
        idInput.checked = true;
        if (radio.value === 'body' && bodyInput) bodyInput.checked = true;
      }
      toggleIdentitySettings();
    }
  </script>
</div>

{{-- ═══════════════════ Card ۱ — پایپ‌لاین هوش مصنوعی ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-microchip text-[var(--accent)]"></i> پایپ‌لاین هوش مصنوعی <span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:250px;">«زمان انتظار (Timeout)» حداکثر ثانیه‌ای است که سیستم منتظر پاسخ مدل می‌ماند. «نوع پایپ‌لاین» مشخص می‌کند خروجی از صفر تولید شود، عکس کاربر ویرایش شود یا متن تولید شود.</span></span></div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">مدل اصلی و مدل‌های جایگزین در زمان اجرای محصول</div>
  </div>

  <div class="text-[11px] font-bold text-[var(--text3)] mb-2 tracking-wider uppercase">مدل اصلی — اولویت یک</div>
  <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5 mb-4">
    @php $curPrimaryModel = old('primary_model', optional($duplicateFrom)->primary_model); @endphp
    <select name="primary_model" id="primary-model-select" data-searchable class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full focus:border-[var(--accent)] mb-2" onchange="onPrimaryModelChange()">
      <option value="">— انتخاب مدل اصلی —</option>
      @foreach ($aiModels as $model)
        <option value="{{ $model->openrouter_model_id }}" data-name="{{ $model->name }}" data-provider="{{ $model->provider_name }}" {{ $curPrimaryModel == $model->openrouter_model_id ? 'selected' : '' }}>
          {{ $model->name }} ({{ $model->provider_name }})
        </option>
      @endforeach
    </select>

    {{-- کارت اطلاعات مدل — بعد از انتخاب نمایش داده می‌شود (نام/Provider واقعی هستند) --}}
    <div id="model-info-card" class="hidden bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-3 mb-3">
      <div class="flex items-center justify-between mb-2">
        <div class="text-xs font-bold text-[var(--text)]" id="model-info-name">—</div>
        <span class="text-[10px] font-mono text-[var(--accent)] bg-[var(--accent)]/10 border border-[var(--accent)]/25 rounded px-1.5 py-0.5" id="model-info-provider">—</span>
      </div>
      <div class="flex items-center gap-1.5 flex-wrap">
        <span class="text-[10px] text-[var(--text3)]">نوع مدل:</span>
        <span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-image ml-1"></i>Image</span>
        <span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-eye ml-1"></i>Vision</span>
        <span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-font ml-1"></i>Text</span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <input type="number" name="timeout" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="زمان انتظار (ثانیه)" value="{{ old('timeout', optional($duplicateFrom)->timeout ?? 60) }}">
      @php $curPipeline = old('pipeline_type', optional($duplicateFrom)->pipeline_type ?? 'image_editing'); @endphp
      <select name="pipeline_type" data-searchable class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
        <option value="image_generation" {{ $curPipeline == 'image_generation' ? 'selected' : '' }}>تولید عکس از صفر</option>
        <option value="image_editing" {{ $curPipeline == 'image_editing' ? 'selected' : '' }}>ویرایش عکس آپلودی کاربر</option>
        <option value="text_generation" {{ $curPipeline == 'text_generation' ? 'selected' : '' }}>تولید متن</option>
      </select>
    </div>
  </div>

  <div class="text-[11px] font-bold text-[var(--text3)] mb-2 tracking-wider uppercase">مدل‌های جایگزین — اولویت دو، سه و...</div>
  <p class="text-[10.5px] text-[var(--text3)] mb-2.5 leading-relaxed">
    اگر مدل اصلی پاسخ نداد، سیستم به ترتیبی که اینجا چیده‌اید سراغ مدل بعدی می‌رود. برای تغییر ترتیب، ردیف را با آیکون کنار آن بکشید.
  </p>
  <div id="fallback-list" class="space-y-2">
    @foreach ((old('fallback_models', optional($duplicateFrom)->fallback_models ?? [])) as $i => $fbModelId)
      <div class="fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3" id="fb-preload-{{ $i }}" draggable="true">
        <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
        <div class="flex md:hidden flex-col gap-0.5 shrink-0">
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
        </div>
        <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت {{ $i + 2 }}</span>
        <select name="fallback_models[]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item" data-searchable>
          @foreach ($aiModels as $model)
            <option value="{{ $model->openrouter_model_id }}" {{ $model->openrouter_model_id === $fbModelId ? 'selected' : '' }}>{{ $model->name }} ({{ $model->provider_name }})</option>
          @endforeach
        </select>
        <label class="relative w-8 h-[18px] shrink-0 block cursor-pointer" title="NEW Enable/Disable — فقط UI، برنامه‌نویسی شود">
          <input type="checkbox" class="sr-only peer" checked>
          <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
        </label>
        <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg shrink-0" onclick="this.closest('.fallback-row').remove(); renumberFallbacks();">حذف</button>
      </div>
    @endforeach
  </div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-2" onclick="addFallback()">
    <i class="fa-solid fa-plus"></i> افزودن مدل جایگزین
  </button>
</div>

{{-- ═══════════════════ Card ۲ — تنظیمات پرامپت ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-terminal text-[var(--accent)]"></i> تنظیمات پرامپت</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">مهم‌ترین بخش پیکربندی محصول</div>
  </div>

  <div class="bg-[var(--accent)]/8 border border-[var(--accent)]/25 rounded-lg p-3 mb-3.5 flex items-start gap-2.5">
    <i class="fa-solid fa-circle-info text-[var(--accent)] mt-0.5 text-xs shrink-0"></i>
    <div class="text-[11px] text-[var(--accent-soft)] leading-relaxed">
      پرامپت فقط باید به <strong>زبان انگلیسی</strong> نوشته شود؛ این متن مستقیماً برای مدل هوش مصنوعی ارسال خواهد شد.
      می‌توانید از متغیرهای سیستم مثل <code class="bg-[var(--b1)] px-1 rounded">{name}</code> استفاده کنید که در زمان اجرا با ورودی کاربر جایگزین می‌شوند.
    </div>
  </div>

  {{-- System Prompt — دستور پایه‌ای که همیشه ابتدای پرامپت نهایی قرار می‌گیرد --}}
  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)]">System Prompt — دستور سیستمی (انگلیسی، اختیاری)</label>
    <textarea name="system_prompt" rows="3" spellcheck="false" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left font-mono leading-relaxed resize-y" placeholder="You are a world-class professional photo generator...">{{ old('system_prompt', optional($duplicateFrom)->system_prompt) }}</textarea>
    <div class="text-[10px] text-[var(--text3)]">نقش/سبک کلی مدل را تعیین می‌کند و همیشه پیش از متن پرامپت اصلی ارسال می‌شود.</div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5" id="prompt-editor-card">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <label class="text-xs font-semibold text-[var(--text2)]">متن پرامپت (انگلیسی) <span class="text-[var(--red)] mr-0.5">*</span></label>
      <div class="flex items-center gap-1.5">
        <button type="button" class="text-[10.5px] px-2 py-1 rounded-md bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-colors" onclick="copyPromptText()"><i class="fa-solid fa-copy ml-1"></i>Copy Prompt</button>
        <button type="button" class="text-[10.5px] px-2 py-1 rounded-md bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-colors" onclick="clearPromptText()"><i class="fa-solid fa-eraser ml-1"></i>Clear</button>
        <button type="button" class="text-[10.5px] px-2 py-1 rounded-md bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-colors" onclick="toggleExpandEditor()" id="expand-editor-btn"><i class="fa-solid fa-expand ml-1"></i>Expand Editor</button>
      </div>
    </div>

    <div class="flex rounded-lg border border-[var(--b1)] focus-within:border-[var(--accent)] transition-colors overflow-hidden bg-[var(--s1)]" id="prompt-editor-wrap">
      <div class="ltr text-left select-none font-mono text-[11px] leading-relaxed text-[var(--text4)] bg-[var(--bg)] px-2 py-2.5 overflow-hidden whitespace-pre" id="prompt-line-numbers" style="min-width:32px;">1</div>
      <textarea name="prompt_template" id="prompt-template" rows="6" spellcheck="false"
        class="bg-transparent border-0 p-2.5 text-xs text-[var(--text)] outline-none w-full ltr text-left font-mono leading-relaxed resize-none"
        style="min-height:150px;"
        placeholder="Write your AI Prompt..."
        oninput="onPromptInput()" onscroll="syncPromptScroll()">{{ old('prompt_template', optional($duplicateFrom)->prompt_template) }}</textarea>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-2 text-[10px] text-[var(--text3)]">
      <div class="flex items-center gap-3 flex-wrap">
        <span id="prompt-char-count">0 کاراکتر</span>
        <span id="prompt-token-estimate">~0 توکن (تخمینی)</span>
        <span id="prompt-vars-detected">۰ متغیر شناسایی شد</span>
      </div>
    </div>

    <div class="flex flex-col gap-1.5 mt-2">
      <label class="text-[11px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">جستجوی متغیر</label>
      <input type="text" id="var-search-input" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] outline-none focus:border-[var(--accent)] w-full" placeholder="جستجوی متغیر..." oninput="filterVarChips(this.value)">
    </div>

    <div class="flex flex-wrap gap-1.5 mt-1" id="var-category-tabs">
      <button type="button" class="var-cat-btn text-[10.5px] px-2 py-1 rounded-md border border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]" data-cat="all" onclick="filterVarCategory('all')">همه</button>
      <button type="button" class="var-cat-btn text-[10.5px] px-2 py-1 rounded-md border border-[var(--b1)] bg-transparent text-[var(--text3)]" data-cat="user" onclick="filterVarCategory('user')">User</button>
      <button type="button" class="var-cat-btn text-[10.5px] px-2 py-1 rounded-md border border-[var(--b1)] bg-transparent text-[var(--text3)]" data-cat="product" onclick="filterVarCategory('product')">Product</button>
      <button type="button" class="var-cat-btn text-[10.5px] px-2 py-1 rounded-md border border-[var(--b1)] bg-transparent text-[var(--text3)]" data-cat="media" onclick="filterVarCategory('media')">Media</button>
      <button type="button" class="var-cat-btn text-[10.5px] px-2 py-1 rounded-md border border-[var(--b1)] bg-transparent text-[var(--text3)]" data-cat="system" onclick="filterVarCategory('system')">System</button>
    </div>

    <div class="flex flex-wrap gap-1.5 mt-1.5" id="var-chips">
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="user" onclick="insertVar('{name}')">{name}</span>
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="user" onclick="insertVar('{gender}')">{gender}</span>
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="user" onclick="insertVar('{style}')">{style}</span>
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="product" onclick="insertVar('{product_name}')">{product_name} <span class="text-[8px] text-[var(--orange)]">NEW</span></span>
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="media" onclick="insertVar('{image}')">{image} <span class="text-[8px] text-[var(--orange)]">NEW</span></span>
      <span class="var-chip text-[11px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" data-cat="system" onclick="insertVar('{today}')">{today} <span class="text-[8px] text-[var(--orange)]">NEW</span></span>
    </div>
  </div>

  {{-- نسخه‌بندی و تاریخچه پرامپت (NEW / فقط UI — بند ۳۰) --}}
  <div class="border-t border-dashed border-[var(--b2)] pt-3.5 mt-3">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
      <div class="text-[11px] font-bold text-[var(--text2)] flex items-center gap-1.5 flex-wrap"><i class="fa-solid fa-clock-rotate-left text-[var(--accent)]"></i> نسخه‌ها و تاریخچه پرامپت {!! $newBadge !!}</div>
      <button type="button" class="text-[10.5px] px-2.5 py-1 rounded-md bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-colors" onclick="savePromptVersion()"><i class="fa-solid fa-floppy-disk ml-1"></i>ذخیره نسخه فعلی</button>
    </div>
    <div id="prompt-versions-list" class="space-y-1.5"></div>
    <div id="prompt-versions-empty" class="text-[10.5px] text-[var(--text3)] text-center py-2">هنوز نسخه‌ای ذخیره نشده است.</div>
  </div>

  {{-- ── پارامترهای واقعی کیفیت (به Backend وصل هستند) ── --}}
  <div class="border-t border-[var(--b1)] pt-3.5 mt-3 grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <div class="flex flex-col gap-1.5 md:col-span-2">
      <label class="text-xs font-semibold text-[var(--text2)]">Negative Prompt — چیزهایی که نباید در خروجی باشد (انگلیسی)</label>
      <textarea name="negative_prompt" rows="2" spellcheck="false" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left font-mono leading-relaxed resize-y" placeholder="blurry, deformed face, extra fingers, low quality, watermark...">{{ old('negative_prompt', optional($duplicateFrom)->negative_prompt) }}</textarea>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">Seed (بازتولیدپذیری خروجی)</label>
      <input type="number" name="seed" value="{{ old('seed', optional($duplicateFrom)->seed) }}" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left" placeholder="خالی = تصادفی">
      <div class="text-[10px] text-[var(--text3)]">مقدار ثابت = خروجی تکرارپذیر برای پرامپت یکسان.</div>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">Provider Options (JSON پیشرفته — اختیاری)</label>
      <textarea name="provider_options" rows="2" spellcheck="false" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-[11px] text-[var(--text)] ltr text-left font-mono leading-relaxed resize-y" placeholder='{"google": {"...": "..."}}'>{{ old('provider_options', is_array(optional($duplicateFrom)->provider_options) ? json_encode($duplicateFrom->provider_options, JSON_UNESCAPED_UNICODE) : '') }}</textarea>
      <div class="text-[10px] text-[var(--text3)]">مستقیماً به provider.options اوپن‌روتر ارسال می‌شود. اگر JSON نامعتبر باشد نادیده گرفته می‌شود.</div>
    </div>
  </div>

  {{-- دکمه تست پرامپت --}}
  <div class="hidden" aria-hidden="true">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
      <div>
        <div class="text-xs font-bold text-[var(--text)]">تست پرامپت</div>
        <div class="text-[10.5px] text-[var(--text3)] mt-0.5">مستقیم از همین صفحه عکس تولید کنید تا مطمئن شوید پرامپت درست است</div>
      </div>
      <button type="button" id="btn-test-prompt"
        onclick="testPromptNow()"
        class="inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-bold bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] transition-all shadow-lg">
        <i class="fa-solid fa-play text-xs"></i>
        <span id="btn-test-text">اجرای تست</span>
      </button>
    </div>

    {{-- نمایش نتیجه تست --}}
    <div id="test-result-box" class="hidden">
      <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-start gap-3 mb-2.5">
        <img id="test-result-img" src="" alt="نتیجه تست" class="w-32 h-32 object-cover rounded-lg border border-[var(--b1)] shrink-0">
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold text-[var(--green)] mb-1 flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> تصویر با موفقیت تولید شد</div>
          <div class="text-[11px] text-[var(--text3)] mb-2">مدل استفاده‌شده: <span id="test-result-model" class="text-[var(--text2)] font-mono"></span></div>
          <a id="test-result-download" href="#" target="_blank" class="text-[11px] text-[var(--accent)] underline">مشاهده تصویر کامل</a>
        </div>
      </div>
      {{-- بند ۱۳: آمار اجرای تست — آخرین اجرا و مدت پاسخ واقعی؛ Token Usage و Estimated Cost فعلاً Placeholder --}}
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <div class="text-[9px] text-[var(--text3)]">آخرین اجرای تست</div>
          <div class="text-[11px] text-[var(--text)] mt-0.5" id="stat-last-run">—</div>
        </div>
        <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <div class="text-[9px] text-[var(--text3)]">مدت زمان پاسخ</div>
          <div class="text-[11px] text-[var(--text)] mt-0.5" id="stat-duration">—</div>
        </div>
        <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <div class="text-[9px] text-[var(--text3)] flex items-center gap-1 flex-wrap">Token Usage {!! $newBadge !!}</div>
          <div class="text-[11px] text-[var(--text3)] mt-0.5">—</div>
        </div>
        <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <div class="text-[9px] text-[var(--text3)] flex items-center gap-1 flex-wrap">Estimated Cost {!! $newBadge !!}</div>
          <div class="text-[11px] text-[var(--text3)] mt-0.5">—</div>
        </div>
      </div>
    </div>

    <div id="test-error-box" class="hidden">
      <div class="bg-[var(--red)]/10 border border-[var(--red)]/30 rounded-xl p-3 text-xs text-[var(--red-soft)]">
        <i class="fa-solid fa-triangle-exclamation ml-1"></i>
        <span id="test-error-text"></span>
      </div>
    </div>
  </div>
</div>

<script>
/* ══════ Card ۱ — کارت اطلاعات مدل اصلی ══════ */
function onPrimaryModelChange() {
  const sel = document.getElementById('primary-model-select');
  const card = document.getElementById('model-info-card');
  if (!sel || !card) return;
  
  const opt = sel.options[sel.selectedIndex];
  if (!opt || !sel.value) { card.classList.add('hidden'); return; }
  
  document.getElementById('model-info-name').textContent = opt.getAttribute('data-name') || '—';
  document.getElementById('model-info-provider').textContent = opt.getAttribute('data-provider') || '—';
  card.classList.remove('hidden');
}

/* ══════ مدیریت و چینش مدل‌های جایگزین (Fallback Models) ══════ */
function renumberFallbacks() {
  document.querySelectorAll('#fallback-list .fb-priority').forEach((el, index) => {
    el.textContent = 'اولویت ' + (index + 2);
  });
}

function wireFallbackDrag(row) {
  row.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', '');
    row.classList.add('opacity-50');
    window.draggedRow = row;
  });
  row.addEventListener('dragend', () => {
    row.classList.remove('opacity-50');
  });
  row.addEventListener('dragover', (e) => { e.preventDefault(); });
  row.addEventListener('drop', (e) => {
    e.preventDefault();
    if (window.draggedRow && window.draggedRow !== row) {
      const list = document.getElementById('fallback-list');
      const all = Array.from(list.children);
      if (all.indexOf(window.draggedRow) < all.indexOf(row)) {
        row.after(window.draggedRow);
      } else {
        row.before(window.draggedRow);
      }
      renumberFallbacks();
    }
  });
}

function moveFallbackRow(btn, dir) {
  const row = btn.closest('.fallback-row');
  if (!row) return;
  if (dir === 'up' && row.previousElementSibling) {
    row.previousElementSibling.before(row);
  } else if (dir === 'down' && row.nextElementSibling) {
    row.nextElementSibling.after(row);
  }
  renumberFallbacks();
}

function addFallback() {
  const list = document.getElementById('fallback-list');
  const selectHtml = document.getElementById('primary-model-select')?.innerHTML || '';
  const count = list.children.length;
  
  const div = document.createElement('div');
  div.className = 'fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3';
  div.draggable = true;
  div.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
    <div class="flex md:hidden flex-col gap-0.5 shrink-0">
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
    </div>
    <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت ${count + 2}</span>
    <select name="fallback_models[]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item">
      ${selectHtml}
    </select>
    <label class="relative w-8 h-[18px] shrink-0 block cursor-pointer">
      <input type="checkbox" class="sr-only peer" checked>
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
    </label>
    <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg shrink-0" onclick="this.closest('.fallback-row').remove(); renumberFallbacks();">حذف</button>
  `;
  
  // پاک کردن آپشن پیشفرض شبیه‌سازی شده از سلکتور کپی شده
  const firstOpt = div.querySelector('select option[value=""]');
  if (firstOpt) firstOpt.remove();

  list.appendChild(div);
  wireFallbackDrag(div);
}

/* ══════ تست پرامپت — فراخوانی واقعی Backend ══════ */
function testPromptNow() {
  var prompt  = document.getElementById('prompt-template').value.trim();
  var modelId = document.getElementById('primary-model-select').value;

  if (!prompt)   { alert('ابتدا پرامپت را بنویسید.'); return; }
  if (!modelId)  { alert('ابتدا مدل اصلی را انتخاب کنید.'); return; }

  document.getElementById('test-result-box').classList.add('hidden');
  document.getElementById('test-error-box').classList.add('hidden');

  var btn  = document.getElementById('btn-test-prompt');
  var text = document.getElementById('btn-test-text');
  btn.disabled = true;
  text.textContent = 'در حال تولید...';

  var csrfToken = document.querySelector('input[name="_token"]')?.value || '';
  var startedAt = Date.now();

  fetch('{{ route('admin.ai-models.test-prompt') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      prompt:   prompt,
      model_id: modelId,
    }),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';

    if (data.success) {
      document.getElementById('test-result-img').src = data.image_url;
      document.getElementById('test-result-model').textContent = data.model;
      document.getElementById('test-result-download').href = data.image_url;
      var lastRunEl = document.getElementById('stat-last-run');
      if (lastRunEl) lastRunEl.textContent = 'همین الان';
      var durEl = document.getElementById('stat-duration');
      if (durEl) durEl.textContent = Math.max(1, Math.round((Date.now() - startedAt) / 1000)) + ' ثانیه';
      document.getElementById('test-result-box').classList.remove('hidden');
    } else {
      document.getElementById('test-error-text').textContent = data.message || 'خطای ناشناخته';
      document.getElementById('test-error-box').classList.remove('hidden');
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';
    document.getElementById('test-error-text').textContent = 'خطا در ارتباط با سرور: ' + err.message;
    document.getElementById('test-error-box').classList.remove('hidden');
  });
}

/* ══════ Prompt Editor قابلیت‌های پیشرفته ══════ */
function updatePromptLineNumbers() {
  const ta = document.getElementById('prompt-template');
  const gutter = document.getElementById('prompt-line-numbers');
  if(!ta || !gutter) return;
  const lines = ta.value.split('\n').length;
  let out = '';
  for (let i = 1; i <= lines; i++) out += i + '\n';
  gutter.textContent = out;
}
function syncPromptScroll() {
  const ta = document.getElementById('prompt-template');
  const gutter = document.getElementById('prompt-line-numbers');
  if(ta && gutter) gutter.scrollTop = ta.scrollTop;
}
function autoResizePrompt() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.style.height = 'auto';
  ta.style.height = Math.max(150, ta.scrollHeight) + 'px';
}
function onPromptInput() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  updatePromptLineNumbers();
  autoResizePrompt();
  syncPromptScroll();
  document.getElementById('prompt-char-count').textContent = ta.value.length + ' کاراکتر';
  document.getElementById('prompt-token-estimate').textContent = '~' + Math.ceil(ta.value.length / 4) + ' توکن (تخمینی)';
  const matches = ta.value.match(/\{[a-zA-Z0-9_]+\}/g) || [];
  document.getElementById('prompt-vars-detected').textContent = matches.length + ' متغیر شناسایی شد';
}
function copyPromptText() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.select();
  navigator.clipboard?.writeText(ta.value).catch(() => document.execCommand('copy'));
}
function clearPromptText() {
  if (!confirm('متن پرامپت پاک شود؟')) return;
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.value = '';
  onPromptInput();
  ta.focus();
}
function toggleExpandEditor() {
  const wrap = document.getElementById('prompt-editor-card');
  const btn = document.getElementById('expand-editor-btn');
  if(!wrap || !btn) return;
  const expanded = wrap.classList.toggle('prompt-fullscreen');
  if (expanded) {
    wrap.classList.add('fixed','inset-4','z-50','bg-[var(--s2)]','p-4','rounded-xl','border','border-[var(--accent)]','shadow-2xl','overflow-y-auto');
    btn.innerHTML = '<i class="fa-solid fa-compress ml-1"></i>بستن تمام‌صفحه';
  } else {
    wrap.classList.remove('fixed','inset-4','z-50','bg-[var(--s2)]','p-4','rounded-xl','border','border-[var(--accent)]','shadow-2xl','overflow-y-auto');
    btn.innerHTML = '<i class="fa-solid fa-expand ml-1"></i>Expand Editor';
  }
}
function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  ta.value = ta.value.substring(0, start) + v + ta.value.substring(end);
  ta.selectionStart = ta.selectionEnd = start + v.length;
  onPromptInput();
  ta.focus();
}

function filterVarChips(term) {
  term = term.toLowerCase();
  document.querySelectorAll('#var-chips .var-chip').forEach(chip => {
    chip.classList.toggle('hidden', term && chip.textContent.toLowerCase().indexOf(term) === -1);
  });
}
function filterVarCategory(cat) {
  document.querySelectorAll('.var-cat-btn').forEach(btn => {
    const active = btn.dataset.cat === cat;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/10', active);
    btn.classList.toggle('text-[var(--accent)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('text-[var(--text3)]', !active);
  });
  document.querySelectorAll('#var-chips .var-chip').forEach(chip => {
    chip.classList.toggle('hidden', cat !== 'all' && chip.dataset.cat !== cat);
  });
}

/* ══════ نسخه‌بندی و تاریخچه پرامپت (بند ۳۰) — فقط UI، درون‌حافظه‌ای ══════ */
var __promptVersions = [];
function __escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function savePromptVersion() {
  var ta = document.getElementById('prompt-template');
  if (!ta || !ta.value.trim()) { alert('ابتدا پرامپت را بنویسید.'); return; }
  var now = new Date();
  var stamp = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
  __promptVersions.unshift({ time: stamp, text: ta.value });
  renderPromptVersions();
}
function renderPromptVersions() {
  var list = document.getElementById('prompt-versions-list');
  var empty = document.getElementById('prompt-versions-empty');
  if (!list) return;
  list.innerHTML = '';
  if (empty) empty.classList.toggle('hidden', __promptVersions.length > 0);
  __promptVersions.forEach(function (v, i) {
    var row = document.createElement('div');
    row.className = 'flex items-center gap-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg px-2.5 py-1.5';
    var preview = __escHtml(v.text.replace(/\s+/g, ' ').slice(0, 40));
    row.innerHTML =
      '<i class="fa-solid fa-code-branch text-[10px] text-[var(--text3)] shrink-0"></i>' +
      '<span class="text-[10px] text-[var(--text3)] font-mono shrink-0">' + v.time + '</span>' +
      '<span class="text-[11px] text-[var(--text2)] flex-1 truncate ltr text-left">' + preview + '</span>' +
      '<button type="button" class="text-[10.5px] text-[var(--accent)] shrink-0" onclick="restorePromptVersion(' + i + ')">بازگردانی</button>' +
      '<button type="button" class="text-[10.5px] text-[var(--red)] shrink-0" onclick="deletePromptVersion(' + i + ')"><i class="fa-solid fa-xmark"></i></button>';
    list.appendChild(row);
  });
}
function restorePromptVersion(i) {
  var v = __promptVersions[i]; if (!v) return;
  var ta = document.getElementById('prompt-template');
  if (ta) { ta.value = v.text; if (typeof onPromptInput === 'function') onPromptInput(); }
}
function deletePromptVersion(i) { __promptVersions.splice(i, 1); renderPromptVersions(); }

document.addEventListener('DOMContentLoaded', () => {
  onPrimaryModelChange();
  document.querySelectorAll('#fallback-list .fallback-row').forEach(row => wireFallbackDrag(row));
  onPromptInput();
});
</script>
