{{-- پارشیال: گام دوم — معماری کیفیت، پرامپت و تنظیمات حفظ هویت --}}
{{-- بعد از تبدیل ویزارد به ۵ مرحله، این پارشیال معماری کیفیت، تنظیمات پرامپت و حفظ هویت را دارد؛
     Card متغیرها و فیلدهای ورودی به step-3.blade.php منتقل شد (گام سوم جدید).
     این بخش نیاز به متغیر $aiModels دارد که از کنترلر پاس داده می‌شود.
     تمام name های ورودی و منطق موجود (از جمله فراخوانی واقعی تست پرامپت به Backend) دقیقاً حفظ شده‌اند. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

@include('admin.products.partials.model-quality-architecture-preview')

{{-- ═══════════════════ Card ۲ — تنظیمات پرامپت ═══════════════════ --}}
<section class="step2-disclosure">
  <div class="step2-disclosure__header">
    <div class="step2-disclosure__heading">
      <span class="step2-disclosure__icon"><i class="fa-solid fa-terminal"></i></span>
      <div><h3>تنظیمات پرامپت</h3><p>مهم‌ترین بخش پیکربندی محصول</p></div>
    </div>
    <div class="step2-disclosure__actions">
      <button type="button" class="step2-disclosure__toggle" data-prompt-toggle aria-expanded="true" aria-controls="prompt-configuration">
        <span data-prompt-toggle-label>بستن تنظیمات پرامپت</span>
        <span class="step2-disclosure__switch"><span></span></span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
    </div>
  </div>
  <div id="prompt-configuration" class="step2-disclosure__drawer">

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
        <span id="prompt-token-estimate">~0 اعتبار (تخمینی)</span>
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
          <div class="text-[9px] text-[var(--text3)] flex items-center gap-1 flex-wrap">مصرف اعتبار {!! $newBadge !!}</div>
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
</section>

{{-- حفظ هویت در کنار پرامپت و قبل از ورودی‌های محصول تنظیم می‌شود. --}}
@include('admin.products.partials.identity-settings', ['aiModels' => $aiModels, 'product' => $product, 'duplicateFrom' => $duplicateFrom])

{{-- ═══════════════════ آزمایشگاه مدل‌های هوش مصنوعی — فقط UI ═══════════════════ --}}
@include('admin.products.partials.ai-model-lab', ['aiModels' => $aiModels, 'exchange' => $exchange ?? [], 'labTested' => $labTested ?? false, 'product' => $product ?? null])

<script>
(() => {
  const connectDisclosure = ({ toggle, drawer, input, label, onLabel, offLabel, afterChange }) => {
    if (!toggle || !drawer) return;
    const setState = (open) => {
      drawer.classList.toggle('hidden', !open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (input) input.value = open ? '1' : '0';
      if (label) label.textContent = open ? onLabel : offLabel;
      afterChange?.(open);
    };
    toggle.addEventListener('click', () => setState(toggle.getAttribute('aria-expanded') !== 'true'));
  };

  connectDisclosure({
    toggle: document.querySelector('[data-prompt-toggle]'),
    drawer: document.getElementById('prompt-configuration'),
    label: document.querySelector('[data-prompt-toggle-label]'),
    onLabel: 'بستن تنظیمات پرامپت',
    offLabel: 'باز کردن تنظیمات پرامپت',
  });
})();
</script>

@include('admin.products.partials.step-2-scripts')
@include('admin.products.partials.step-2-styles')
