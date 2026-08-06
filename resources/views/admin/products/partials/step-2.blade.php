{{-- پارشیال: گام دوم — هوش مصنوعی (پایپ‌لاین + پرامپت) --}}
{{-- بعد از تبدیل ویزارد به ۵ مرحله، این پارشیال فقط Card ۱ و ۲ (پایپ‌لاین و تنظیمات پرامپت/تست) را دارد؛
     Card متغیرها و فیلدهای ورودی به step-3.blade.php منتقل شد (گام سوم جدید).
     این بخش نیاز به متغیر $aiModels دارد که از کنترلر پاس داده می‌شود.
     تمام name های ورودی و منطق موجود (از جمله فراخوانی واقعی تست پرامپت به Backend) دقیقاً حفظ شده‌اند. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

{{-- ═══════════════════ Card ۱ — پایپ‌لاین هوش مصنوعی ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-microchip text-[var(--accent)]"></i> پایپ‌لاین هوش مصنوعی <span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:290px;">مدل اصلی اولین انتخاب برای ساخت تصویر است. اگر OpenRouter یا مدل اصلی پاسخ موفق ندهد، سیستم خودکار مدل جایگزین را امتحان می‌کند. زمان انتظار و نوع پردازش تصویر در معماری نهایی به‌صورت امن و خودکار تنظیم شده‌اند و نیازی به انتخاب مدیر ندارند.</span></span></div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">مدل اصلی اجباری است و مدل جایگزین متفاوت، هنگام خطا به‌صورت خودکار اجرا می‌شود.</div>
  </div>

  <div class="text-[11px] font-bold text-[var(--text3)] mb-2 tracking-wider uppercase flex items-center gap-1.5">مدل اصلی — اولویت یک <span class="text-[var(--red)]">*</span><span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:250px;">اولین مدل برای ساخت خروجی است. سه کارت پیشنهادی، جدیدترین خانواده مدل‌های تصویری ChatGPT را در اولویت قرار می‌دهند؛ همچنان می‌توانید هر مدل فعال دیگری را از جست‌وجو انتخاب کنید.</span></span></div>
  <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5 mb-4">
    @php
      // وضعیت روشن/خاموش providerها را از کلید مرکزی می‌خوانیم؛ همان فلگی
      // که در پنل «مدیریت مدل‌های هوش مصنوعی» توسط ادمین تنظیم می‌شود.
      // اگر provider خاموش باشد، دکمه انتخاب آن اینجا نمایش داده نمی‌شود.
      $providerStatus = \App\Support\ProviderStatus::all();
      $curPrimaryModel = old('primary_model', optional($duplicateFrom)->primary_model);
      $curSavedProvider = old('ai_provider', optional($duplicateFrom)->ai_provider);
      // برای محصول جدید OpenRouter پیش‌فرض است؛ محصول ذخیره‌شده provider خودش را نگه می‌دارد.
      $providerLabels = ['liara' => 'لیارا', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'];
      $curApiProvider  = collect($providerStatus)->filter()->keys()->first() ?: 'openrouter';
      if (in_array($curSavedProvider, array_keys($providerLabels), true)) {
        $curApiProvider = $curSavedProvider;
      }
      if ($curPrimaryModel) {
        $matchedApiModel = $aiModels->first(
          fn ($model) => $model->openrouter_model_id === $curPrimaryModel
            && (!$curSavedProvider || $model->provider === $curSavedProvider)
        );
        if ($matchedApiModel) {
          $curApiProvider = $matchedApiModel->provider ?? 'openrouter';
        }
      }
    @endphp

    {{-- تاگل انتخاب Provider — فقط providerهای روشن نمایش داده می‌شوند --}}
    <div class="flex items-center gap-2 mb-3 flex-wrap">
      <span class="text-[10px] text-[var(--text3)] font-semibold ml-1">سرویس:</span>
      @foreach($providerLabels as $providerKey => $providerLabel)
        @if($providerStatus[$providerKey] ?? false)
          <button type="button" id="lbl-api-{{ $providerKey }}" onclick="onApiProviderChange('{{ $providerKey }}')" class="api-provider-btn inline-flex items-center gap-1.5 px-3 h-7 rounded-lg text-[11px] font-bold border transition-all cursor-pointer {{ $curApiProvider === $providerKey ? 'active-provider' : '' }}">{{ $providerLabel }}</button>
        @endif
      @endforeach
      @if(!collect($providerStatus)->contains(true))
        <span class="text-[10.5px] text-[var(--red)] font-bold">
          <i class="fa-solid fa-triangle-exclamation ml-1"></i>
          هیچ سرویس هوش مصنوعی فعال نیست. لطفاً از «مدیریت مدل‌های هوش مصنوعی» یکی را روشن کنید.
        </span>
      @endif
    </div>

    {{-- Select مدل اصلی — آپشن‌ها فیلتر می‌شوند بر اساس provider --}}
    <script>var __currentApiProvider = '{{ $curApiProvider }}';</script>
    <input type="hidden" name="ai_provider" id="ai-provider-input" value="{{ $curApiProvider }}">
    <div id="recommended-openrouter-models" class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3 {{ $curApiProvider === 'openrouter' ? '' : 'hidden' }}">
      @foreach([
        ['openai/gpt-image-2', 3, 'بهترین کیفیت'],
        ['openai/gpt-image-1', 2, 'کیفیت حرفه‌ای'],
        ['openai/gpt-image-1-mini', 1, 'بهینه‌تر'],
      ] as [$recommendedId, $stars, $caption])
        @php $recommendedModel = $aiModels->first(fn($model) => $model->provider === 'openrouter' && $model->openrouter_model_id === $recommendedId); @endphp
        @if($recommendedModel)
          <button type="button" data-recommended-model="{{ $recommendedId }}" onclick="selectRecommendedModel('{{ $recommendedId }}')" class="recommended-model-card relative text-right bg-[var(--s1)] border border-[var(--b1)] rounded-lg px-2.5 py-2 hover:border-[var(--accent)] transition-colors">
            <i class="recommended-model-check fa-solid fa-circle-check absolute left-2 top-1/2 -translate-y-1/2 text-[var(--green)]" style="display:none" aria-hidden="true"></i>
            <span class="flex items-center gap-1.5 leading-none mb-1"><span class="text-[var(--warning)] text-[8.5px]">@for($star=0;$star<$stars;$star++)<i class="fa-solid fa-star"></i>@endfor</span><small class="text-[9px] text-[var(--text3)]">{{ $caption }}</small></span>
            <span class="block text-[10.5px] font-bold text-[var(--text)] pl-5">{{ $recommendedModel->name }}</span>
          </button>
        @endif
      @endforeach
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
    <select name="primary_model" id="primary-model-select" data-searchable required class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full focus:border-[var(--accent)] mb-2" onchange="onPrimaryModelChange()">
      <option value="">— انتخاب مدل اصلی —</option>
      @foreach ($aiModels as $model)
        <option value="{{ $model->openrouter_model_id }}"
                data-name="{{ $model->name }}"
                data-provider="{{ $model->provider_name }}"
                data-api-provider="{{ $model->provider ?? 'openrouter' }}"
                data-output-modality="{{ $model->output_modality }}"
                {{ $curPrimaryModel == $model->openrouter_model_id
                    && (!$curSavedProvider || $curSavedProvider === ($model->provider ?? 'openrouter'))
                    ? 'selected' : '' }}>
          {{ $model->name }} ({{ $model->provider_name }})
        </option>
      @endforeach
    </select>

    {{-- کارت اطلاعات مدل — بعد از انتخاب نمایش داده می‌شود (نام/Provider واقعی هستند) --}}
    <div id="model-info-card" class="hidden bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-3 mb-3">
      <div class="flex items-center gap-2 flex-wrap">
        <div class="text-xs font-bold text-[var(--text)]" id="model-info-name">—</div>
        <span class="text-[10px] font-mono text-[var(--accent)] bg-[var(--accent)]/10 border border-[var(--accent)]/25 rounded px-1.5 py-0.5" id="model-info-provider">—</span>
        <span class="text-[10px] text-[var(--text3)]">نوع مدل:</span>
        <span id="model-info-media" class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-image ml-1"></i>عکس</span>
        <span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-eye ml-1"></i>Vision</span>
        <span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5"><i class="fa-solid fa-font ml-1"></i>Text</span>
      </div>
    </div>
    </div>

    <input type="hidden" name="timeout" value="60">
    <input type="hidden" name="pipeline_type" value="image_editing">
  </div>

  <div class="text-[11px] font-bold text-[var(--text3)] mb-2 tracking-wider uppercase flex items-center gap-1.5">مدل جایگزین — اولویت دو <span class="text-[var(--red)]">*</span><span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:250px;">در انتخاب کارت‌های پیشنهادی، یک مدل متفاوت و یک رده پایین‌تر خودکار انتخاب می‌شود. انتخاب دستی هر دو مدل نیز امکان‌پذیر است، اما مدل دوم نباید با مدل اصلی یکسان باشد.</span></span></div>
  <p class="text-[10.5px] text-[var(--text3)] mb-2.5 leading-relaxed">
    اگر مدل اصلی پاسخ نداد، سیستم به ترتیبی که اینجا چیده‌اید سراغ مدل بعدی می‌رود. برای تغییر ترتیب، ردیف را با آیکون کنار آن بکشید.
  </p>
  @php
    $configuredFallbacks = old('fallback_models', optional($duplicateFrom)->fallback_models ?? []);
    $configuredFallbackProviders = old('fallback_providers', optional($duplicateFrom)->fallback_model_providers ?? []);
    if (empty($configuredFallbacks)) {
      $configuredFallbacks = ['openai/gpt-image-1-mini'];
      $configuredFallbackProviders = [$curApiProvider];
    }
  @endphp
  <div id="fallback-list" class="space-y-2 md:w-1/2 md:ml-auto">
    @foreach ($configuredFallbacks as $i => $fbModelId)
      <div class="fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3" id="fb-preload-{{ $i }}">
        <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
        <div class="flex md:hidden flex-col gap-0.5 shrink-0">
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
        </div>
        <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت {{ $i + 2 }}</span>
        <select name="fallback_models[]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item" data-searchable>
          @foreach ($aiModels as $model)
            <option value="{{ $model->openrouter_model_id }}"
                    data-api-provider="{{ $model->provider ?? 'openrouter' }}"
                    {{ $model->openrouter_model_id === $fbModelId && ($configuredFallbackProviders[$i] ?? null) === ($model->provider ?? 'openrouter') ? 'selected' : '' }}>{{ $model->name }} ({{ $model->provider_name }})</option>
          @endforeach
        </select>
        <input type="hidden" name="fallback_providers[]" class="fallback-provider-input" value="{{ $configuredFallbackProviders[$i] ?? $curApiProvider }}">
      </div>
    @endforeach
  </div>
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

    <div class="hidden flex flex-col gap-1.5 mt-2" data-future-update="جستجوی متغیر">
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
  <div class="hidden border-t border-dashed border-[var(--b2)] pt-3.5 mt-3" data-future-update="نسخه‌ها و تاریخچه پرامپت">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
      <div class="text-[11px] font-bold text-[var(--text2)] flex items-center gap-1.5 flex-wrap"><i class="fa-solid fa-clock-rotate-left text-[var(--accent)]"></i> نسخه‌ها و تاریخچه پرامپت {!! $newBadge !!}</div>
      <button type="button" class="text-[10.5px] px-2.5 py-1 rounded-md bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-colors" onclick="savePromptVersion()"><i class="fa-solid fa-floppy-disk ml-1"></i>ذخیره نسخه فعلی</button>
    </div>
    <div id="prompt-versions-list" class="space-y-1.5"></div>
    <div id="prompt-versions-empty" class="text-[10.5px] text-[var(--text3)] text-center py-2">هنوز نسخه‌ای ذخیره نشده است.</div>
  </div>

  {{-- ── پارامترهای واقعی کیفیت (به Backend وصل هستند) ── --}}
  <div class="hidden border-t border-[var(--b1)] pt-3.5 mt-3 grid grid-cols-1 md:grid-cols-2 gap-3.5" data-future-update="تنظیمات تکمیلی خروجی">
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

@include('admin.products.partials.step-2-scripts')
@include('admin.products.partials.step-2-styles')
