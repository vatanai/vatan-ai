{{-- ══ AI MODELS PAGE ══ --}}
<div id="ai-models-page" style="display:none;">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-3">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">مدیریت مدل‌ها</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">پیکربندی مدل‌های AI، زنجیره fallback و نسخه‌بندی</div>
    </div>
    <button onclick="openAiModelModal()"
      class="flex items-center gap-2 bg-green text-white text-[12px] font-bold py-[8px] px-4 rounded-lg hover:opacity-90 transition-opacity duration-150">
      <i class="fa-solid fa-plus text-[11px]"></i> افزودن مدل
    </button>
  </div>

  {{-- Filter Tabs --}}
  <div class="flex items-center gap-[6px] mb-5 flex-wrap">
    <button onclick="filterAiModels('all', this)"
      class="ai-model-filter active text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s2 text-watan-text transition-all duration-150 hover:border-b2">
      همه <span class="text-watan-text3 mr-1">۶</span>
    </button>
    <button onclick="filterAiModels('fal', this)"
      class="ai-model-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">
      fal.ai <span class="text-watan-text3 mr-1">۴</span>
    </button>
    <button onclick="filterAiModels('replicate', this)"
      class="ai-model-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">
      Replicate <span class="text-watan-text3 mr-1">۲</span>
    </button>
    <button onclick="filterAiModels('active', this)"
      class="ai-model-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">
      <span class="w-[6px] h-[6px] rounded-full bg-green inline-block ml-1"></span> فعال <span class="text-watan-text3 mr-1">۵</span>
    </button>
    <button onclick="filterAiModels('inactive', this)"
      class="ai-model-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">
      <span class="w-[6px] h-[6px] rounded-full bg-red inline-block ml-1"></span> غیرفعال <span class="text-watan-text3 mr-1">۱</span>
    </button>
  </div>

  {{-- Fallback Chain Banner --}}
  <div class="bg-s1 border border-b1 rounded-[10px] p-4 mb-5 flex items-center gap-4 flex-wrap">
    <div class="text-[11px] font-bold text-watan-text3 flex-shrink-0">زنجیره Fallback:</div>
    <div class="flex items-center gap-2 flex-wrap">
      <div class="flex items-center gap-[6px] bg-green/[0.08] border border-green/[0.2] text-green text-[11px] font-bold py-[5px] px-3 rounded-lg">
        <i class="fa-solid fa-1 text-[9px]"></i> fal.ai — flux-pro
      </div>
      <i class="fa-solid fa-arrow-left text-[10px] text-watan-text3"></i>
      <div class="flex items-center gap-[6px] bg-accent/[0.08] border border-accent/[0.2] text-accent text-[11px] font-bold py-[5px] px-3 rounded-lg">
        <i class="fa-solid fa-2 text-[9px]"></i> Replicate — sdxl-turbo
      </div>
      <i class="fa-solid fa-arrow-left text-[10px] text-watan-text3"></i>
      <div class="flex items-center gap-[6px] bg-orange/[0.08] border border-orange/[0.2] text-orange text-[11px] font-bold py-[5px] px-3 rounded-lg">
        <i class="fa-solid fa-3 text-[9px]"></i> fal.ai — flux-schnell
      </div>
    </div>
    <button class="mr-auto text-[11px] font-semibold text-watan-text3 hover:text-watan-text transition-colors duration-150 flex-shrink-0">
      <i class="fa-solid fa-pen text-[10px]"></i> ویرایش زنجیره
    </button>
  </div>

  {{-- Model Cards Grid --}}
  <div class="grid grid-cols-2 gap-4 max-[900px]:grid-cols-1" id="ai-models-grid">

    {{-- Model Card: flux-pro --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="fal" data-status="active">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-green/[0.08] border border-green/[0.2] text-green flex-shrink-0">
            <i class="fa-solid fa-microchip"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text">flux-pro</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span>
              <span class="text-[9px] text-watan-text3 font-mono">v1.1</span>
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-orange/[0.08] text-orange border border-orange/[0.2]">Primary</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" checked class="sr-only peer" onchange="toggleAiModel(this,'flux-pro')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-watan-text">۷.۲s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.014</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-green">۹۸.۱٪</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    {{-- Model Card: flux-schnell --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="fal" data-status="active">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-green/[0.08] border border-green/[0.2] text-green flex-shrink-0">
            <i class="fa-solid fa-bolt"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text">flux-schnell</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span>
              <span class="text-[9px] text-watan-text3 font-mono">v1.0</span>
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.08] text-green border border-green/[0.2]">Fallback-2</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" checked class="sr-only peer" onchange="toggleAiModel(this,'flux-schnell')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-green">۳.۸s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.006</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-green">۹۹.۲٪</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    {{-- Model Card: flux-dev --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="fal" data-status="active">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-green/[0.08] border border-green/[0.2] text-green flex-shrink-0">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text">flux-dev</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span>
              <span class="text-[9px] text-watan-text3 font-mono">v1.0</span>
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-accent/[0.08] text-accent border border-accent/[0.2]">آزمایشی</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" checked class="sr-only peer" onchange="toggleAiModel(this,'flux-dev')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-orange">۱۴.۶s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.025</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-green">۹۶.۸٪</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    {{-- Model Card: SDXL-Lightning --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="fal" data-status="inactive">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-red/[0.08] border border-red/[0.2] text-red/[0.7] flex-shrink-0">
            <i class="fa-solid fa-microchip"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text/[0.5]">sdxl-lightning</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.05] text-green/[0.5] border border-green/[0.1]">fal.ai</span>
              <span class="text-[9px] text-watan-text3/[0.5] font-mono">v2.0</span>
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-red/[0.08] text-red border border-red/[0.2]">غیرفعال</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" class="sr-only peer" onchange="toggleAiModel(this,'sdxl-lightning')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4 opacity-50">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-watan-text">۵.۱s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.009</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-watan-text">—</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    {{-- Model Card: sdxl-turbo --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="replicate" data-status="active">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-accent/[0.08] border border-accent/[0.2] text-accent flex-shrink-0">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text">sdxl-turbo</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-accent/[0.08] text-accent border border-accent/[0.2]">Replicate</span>
              <span class="text-[9px] text-watan-text3 font-mono">r2aa4d69</span>
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-orange/[0.08] text-orange border border-orange/[0.2]">Fallback-1</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" checked class="sr-only peer" onchange="toggleAiModel(this,'sdxl-turbo')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-orange">۱۱.۴s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.021</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-green">۹۵.۳٪</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    {{-- Model Card: stable-diffusion (replicate) --}}
    <div class="model-card bg-s1 border border-b1 rounded-[10px] p-5 transition-colors duration-200 hover:border-b2" data-provider="replicate" data-status="active">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm bg-accent/[0.08] border border-accent/[0.2] text-accent flex-shrink-0">
            <i class="fa-solid fa-palette"></i>
          </div>
          <div>
            <div class="text-[13px] font-extrabold text-watan-text">stable-diffusion-xl</div>
            <div class="flex items-center gap-2 mt-[3px]">
              <span class="text-[9px] font-bold py-px px-[5px] rounded bg-accent/[0.08] text-accent border border-accent/[0.2]">Replicate</span>
              <span class="text-[9px] text-watan-text3 font-mono">a00d0b7d</span>
            </div>
          </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" checked class="sr-only peer" onchange="toggleAiModel(this,'stable-diffusion-xl')">
          <div class="w-9 h-5 rounded-full border border-b1 bg-s2 peer-checked:bg-green/[0.15] peer-checked:border-green/[0.4] after:content-[''] after:absolute after:top-[3px] after:right-[3px] after:w-[14px] after:h-[14px] after:rounded-full after:bg-watan-text3 after:transition-all peer-checked:after:right-auto peer-checked:after:left-[3px] peer-checked:after:bg-green transition-colors duration-200"></div>
        </label>
      </div>
      <div class="grid grid-cols-3 gap-[6px] mb-4">
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">تأخیر p50</div>
          <div class="text-[14px] font-extrabold text-orange">۱۶.۲s</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">هزینه/تصویر</div>
          <div class="text-[14px] font-extrabold text-watan-text">$0.018</div>
        </div>
        <div class="bg-s2 rounded-lg p-[10px] border border-b1">
          <div class="text-[11px] text-watan-text3 mb-[4px]">موفقیت ۷روزه</div>
          <div class="text-[14px] font-extrabold text-green">۹۷.۱٪</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-pen text-[10px] ml-1"></i> ویرایش
        </button>
        <button class="flex-1 text-[11px] font-semibold py-[7px] px-3 rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
          <i class="fa-solid fa-clock-rotate-left text-[10px] ml-1"></i> تاریخچه نسخه
        </button>
        <button class="w-[34px] h-[34px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.6] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

  </div>

</div>

{{-- Add Model Modal --}}
<div id="ai-model-modal" class="fixed inset-0 z-[200] flex items-center justify-center hidden" style="background:rgba(0,0,0,0.6);backdrop-filter:blur(4px)">
  <div class="bg-s1 border border-b1 rounded-[14px] w-[520px] max-w-[calc(100vw-32px)] shadow-[0_24px_64px_rgba(0,0,0,0.5)]">
    <div class="flex items-center justify-between p-5 border-b border-b1">
      <div class="text-[14px] font-extrabold text-watan-text">افزودن مدل جدید</div>
      <button onclick="closeAiModelModal()" class="w-[30px] h-[30px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-sm text-watan-text3 hover:text-watan-text hover:border-b2 transition-all duration-150">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">پروایدر</label>
          <select class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text focus:border-b2 focus:outline-none transition-colors duration-150">
            <option>fal.ai</option>
            <option>Replicate</option>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">نام مدل</label>
          <input type="text" placeholder="مثال: flux-pro-v1.1" class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text placeholder:text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">نسخه / Hash</label>
          <input type="text" placeholder="version hash یا شماره نسخه" class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text placeholder:text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150">
        </div>
        <div>
          <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">نقش</label>
          <select class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text focus:border-b2 focus:outline-none transition-colors duration-150">
            <option>Primary</option>
            <option>Fallback-1</option>
            <option>Fallback-2</option>
            <option>آزمایشی</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">پارامترهای پیش‌فرض (JSON)</label>
        <textarea rows="3" placeholder='{"num_inference_steps": 28, "guidance_scale": 3.5}' class="w-full bg-s2 border border-b1 rounded-lg px-3 py-2 text-[12px] text-watan-text placeholder:text-watan-text3 font-mono focus:border-b2 focus:outline-none transition-colors duration-150 resize-none"></textarea>
      </div>
    </div>
    <div class="flex items-center gap-3 p-5 border-t border-b1">
      <button onclick="closeAiModelModal()" class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
        بستن
      </button>
      <button class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-green text-white hover:opacity-90 transition-opacity duration-150">
        <i class="fa-solid fa-plus ml-2 text-[10px]"></i> افزودن مدل
      </button>
    </div>
  </div>
</div>

<script>
function filterAiModels(filter, el) {
  document.querySelectorAll('.ai-model-filter').forEach(btn => {
    btn.classList.remove('active');
    btn.style.cssText = '';
  });
  el.classList.add('active');

  document.querySelectorAll('#ai-models-grid .model-card').forEach(card => {
    let show = false;
    if (filter === 'all')      show = true;
    else if (filter === 'fal') show = card.dataset.provider === 'fal';
    else if (filter === 'replicate') show = card.dataset.provider === 'replicate';
    else if (filter === 'active')   show = card.dataset.status === 'active';
    else if (filter === 'inactive') show = card.dataset.status === 'inactive';
    card.style.display = show ? '' : 'none';
  });
}

function toggleAiModel(checkbox, name) {
  const card = checkbox.closest('.model-card');
  if (checkbox.checked) {
    card.dataset.status = 'active';
    card.style.opacity = '';
  } else {
    card.dataset.status = 'inactive';
  }
}

function openAiModelModal()  { document.getElementById('ai-model-modal').classList.remove('hidden'); }
function closeAiModelModal() { document.getElementById('ai-model-modal').classList.add('hidden'); }

document.addEventListener('click', function(e) {
  const modal = document.getElementById('ai-model-modal');
  if (modal && e.target === modal) closeAiModelModal();
});
</script>
