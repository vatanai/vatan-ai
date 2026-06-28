{{-- ══ AI LOGS PAGE ══ --}}
<div id="ai-logs-page" style="display:none;">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-3">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">لاگ‌های اجرا</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">تاریخچه کامل job‌های AI — جزئیات، خطاها و زمان‌بندی</div>
    </div>
    <div class="flex items-center gap-[6px] bg-green/[0.08] border border-green/[0.2] text-green text-[11px] font-bold py-[6px] px-3 rounded-lg">
      <div class="w-[6px] h-[6px] rounded-full bg-green animate-pulse" style="box-shadow:0 0 8px rgba(11,191,83,0.6)"></div>
      Real-time
    </div>
  </div>

  {{-- Summary Pills --}}
  <div class="flex items-center gap-3 mb-5 flex-wrap">
    <div class="flex items-center gap-[6px] bg-s1 border border-b1 rounded-lg px-3 py-[6px]">
      <span class="text-[11px] text-watan-text3">کل:</span>
      <span class="text-[12px] font-extrabold text-watan-text">۱,۲۴۷</span>
    </div>
    <div class="flex items-center gap-[6px] bg-green/[0.06] border border-green/[0.2] rounded-lg px-3 py-[6px]">
      <div class="w-[5px] h-[5px] rounded-full bg-green"></div>
      <span class="text-[11px] text-watan-text3">موفق:</span>
      <span class="text-[12px] font-extrabold text-green">۱,۲۱۳</span>
    </div>
    <div class="flex items-center gap-[6px] bg-red/[0.06] border border-red/[0.2] rounded-lg px-3 py-[6px]">
      <div class="w-[5px] h-[5px] rounded-full bg-red"></div>
      <span class="text-[11px] text-watan-text3">خطا:</span>
      <span class="text-[12px] font-extrabold text-red">۲۶</span>
    </div>
    <div class="flex items-center gap-[6px] bg-orange/[0.06] border border-orange/[0.2] rounded-lg px-3 py-[6px]">
      <div class="w-[5px] h-[5px] rounded-full bg-orange"></div>
      <span class="text-[11px] text-watan-text3">در جریان:</span>
      <span class="text-[12px] font-extrabold text-orange">۳</span>
    </div>
    <div class="flex items-center gap-[6px] bg-accent/[0.06] border border-accent/[0.2] rounded-lg px-3 py-[6px]">
      <div class="w-[5px] h-[5px] rounded-full bg-accent"></div>
      <span class="text-[11px] text-watan-text3">timeout:</span>
      <span class="text-[12px] font-extrabold text-accent">۵</span>
    </div>
  </div>

  {{-- Filters --}}
  <div class="flex items-center gap-3 mb-5 flex-wrap">
    {{-- Search --}}
    <div class="flex items-center gap-[6px] bg-s2 border border-b1 rounded-lg px-3 h-[36px] w-[200px] focus-within:border-b2 transition-colors duration-150">
      <i class="fa-solid fa-magnifying-glass text-watan-text3 text-xs"></i>
      <input type="text" placeholder="Job ID یا کاربر..." oninput="searchAiLogs(this.value)"
        class="bg-transparent border-0 outline-none text-[12px] text-watan-text flex-1 min-w-0 placeholder:text-watan-text3">
    </div>
    {{-- Status Filter --}}
    <div class="flex items-center gap-[4px]" id="ai-log-status-filters">
      <button onclick="filterAiLogs('all',this)" class="ai-log-filter active text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s2 text-watan-text transition-all duration-150 hover:border-b2">همه</button>
      <button onclick="filterAiLogs('success',this)" class="ai-log-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">موفق</button>
      <button onclick="filterAiLogs('failed',this)" class="ai-log-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">خطا</button>
      <button onclick="filterAiLogs('pending',this)" class="ai-log-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">در جریان</button>
      <button onclick="filterAiLogs('timeout',this)" class="ai-log-filter text-[11px] font-bold py-[6px] px-3 rounded-lg border border-b1 bg-s1 text-watan-text3 transition-all duration-150 hover:border-b2 hover:text-watan-text">Timeout</button>
    </div>
    {{-- Provider --}}
    <select onchange="filterAiLogsByProvider(this.value)"
      class="bg-s2 border border-b1 rounded-lg px-3 h-[36px] text-[12px] text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150 mr-auto">
      <option value="all">همه پروایدرها</option>
      <option value="fal">fal.ai</option>
      <option value="replicate">Replicate</option>
    </select>
  </div>

  {{-- Logs Table --}}
  <div class="bg-s1 border border-b1 rounded-[10px] overflow-hidden" id="ai-logs-table">

    {{-- Table Header --}}
    <div class="grid border-b border-b1 px-4 py-[10px]" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px">
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">Job ID</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">کاربر</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">محصول</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">پروایدر</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">زمان</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">هزینه</div>
      <div class="text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">وضعیت</div>
      <div></div>
    </div>

    {{-- Log Row: J5021 success --}}
    <div class="log-row-wrapper border-b border-b1" data-status="success" data-provider="fal" data-search="j5021 ali.rezaei">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5021</div>
        <div>
          <div class="text-[12px] text-watan-text">ali.rezaei</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۴:۳۲</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">عکس رستوران</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></div>
        <div class="text-[12px] font-bold text-watan-text">۷.۲s</div>
        <div class="text-[11px] text-watan-text3">$0.014</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="grid grid-cols-3 gap-3 mb-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">prompt used</div><div class="text-[11px] text-watan-text font-mono">restaurant-system</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">model</div><div class="text-[11px] text-watan-text font-mono">flux-pro v1.1</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">output size</div><div class="text-[11px] text-watan-text">۱۰۲۴×۱۰۲۴</div></div>
        </div>
        <div class="bg-s1 border border-b1 rounded-lg p-3">
          <div class="text-[10px] text-watan-text3 mb-2">Supabase URL</div>
          <div class="text-[11px] text-accent font-mono truncate">https://xyz.supabase.co/storage/v1/object/public/generations/J5021.webp</div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5020 success --}}
    <div class="log-row-wrapper border-b border-b1" data-status="success" data-provider="fal" data-search="j5020 maryam.k">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5020</div>
        <div>
          <div class="text-[12px] text-watan-text">maryam.k</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۴:۲۸</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">پس‌زمینه غذا</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></div>
        <div class="text-[12px] font-bold text-watan-text">۸.۹s</div>
        <div class="text-[11px] text-watan-text3">$0.018</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="grid grid-cols-3 gap-3 mb-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">prompt used</div><div class="text-[11px] text-watan-text font-mono">background-enhancer</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">model</div><div class="text-[11px] text-watan-text font-mono">flux-pro v1.1</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">output size</div><div class="text-[11px] text-watan-text">۱۲۸۰×۷۲۰</div></div>
        </div>
        <div class="bg-s1 border border-b1 rounded-lg p-3">
          <div class="text-[10px] text-watan-text3 mb-2">Supabase URL</div>
          <div class="text-[11px] text-accent font-mono truncate">https://xyz.supabase.co/storage/v1/object/public/generations/J5020.webp</div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5019 success replicate --}}
    <div class="log-row-wrapper border-b border-b1" data-status="success" data-provider="replicate" data-search="j5019 hassan.m">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5019</div>
        <div>
          <div class="text-[12px] text-watan-text">hassan.m</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۴:۲۱</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">ویترین کافه</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2]">Replicate</span></div>
        <div class="text-[12px] font-bold text-watan-text">۱۲.۴s</div>
        <div class="text-[11px] text-watan-text3">$0.021</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="grid grid-cols-3 gap-3 mb-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">prompt used</div><div class="text-[11px] text-watan-text font-mono">cafe-window-display</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">model</div><div class="text-[11px] text-watan-text font-mono">sdxl-turbo r2aa4d69</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">fallback used</div><div class="text-[11px] text-orange font-bold">بله — fal.ai ناموفق</div></div>
        </div>
        <div class="bg-s1 border border-b1 rounded-lg p-3">
          <div class="text-[10px] text-watan-text3 mb-2">Supabase URL</div>
          <div class="text-[11px] text-accent font-mono truncate">https://xyz.supabase.co/storage/v1/object/public/generations/J5019.webp</div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5018 FAILED --}}
    <div class="log-row-wrapper border-b border-b1" data-status="failed" data-provider="fal" data-search="j5018 sara.t">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5018</div>
        <div>
          <div class="text-[12px] text-watan-text">sara.t</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۴:۱۸</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">عکس رستوران</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></div>
        <div class="text-[12px] font-bold text-red">—</div>
        <div class="text-[11px] text-watan-text3">—</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-red/[0.08] text-red border border-red/[0.2]">خطا</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="bg-red/[0.05] border border-red/[0.2] rounded-lg p-3 mb-3">
          <div class="text-[10px] font-bold text-red mb-1 flex items-center gap-2"><i class="fa-solid fa-circle-xmark"></i> Error</div>
          <div class="text-[11px] text-watan-text font-mono">fal.ai API Error 503: Service Unavailable — model overloaded. All fallbacks exhausted.</div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">prompt used</div><div class="text-[11px] text-watan-text font-mono">restaurant-system</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">attempts</div><div class="text-[11px] text-red font-bold">۳ بار تلاش</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">refund</div><div class="text-[11px] text-green font-bold">بازگشت کردیت ✓</div></div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5017 pending --}}
    <div class="log-row-wrapper border-b border-b1" data-status="pending" data-provider="fal" data-search="j5017 reza.d">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5017</div>
        <div>
          <div class="text-[12px] text-watan-text">reza.d</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۴:۳۵</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">منو دیجیتال</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></div>
        <div class="text-[12px] font-bold text-orange">در جریان</div>
        <div class="text-[11px] text-watan-text3">—</div>
        <div>
          <span class="text-[10px] font-bold py-px px-2 rounded-md bg-orange/[0.08] text-orange border border-orange/[0.2] flex items-center gap-1">
            <div class="w-[4px] h-[4px] rounded-full bg-orange animate-pulse"></div> در جریان
          </span>
        </div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="bg-orange/[0.05] border border-orange/[0.2] rounded-lg p-3">
          <div class="text-[10px] font-bold text-orange mb-1"><i class="fa-solid fa-circle-notch fa-spin ml-1"></i> در حال پردازش...</div>
          <div class="text-[11px] text-watan-text">job در Redis queue — worker در حال پردازش با flux-pro</div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5016 timeout --}}
    <div class="log-row-wrapper border-b border-b1" data-status="timeout" data-provider="replicate" data-search="j5016 noora.s">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5016</div>
        <div>
          <div class="text-[12px] text-watan-text">noora.s</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۳:۵۱</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">ویترین کافه</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2]">Replicate</span></div>
        <div class="text-[12px] font-bold text-red">۱۲۰s+</div>
        <div class="text-[11px] text-watan-text3">—</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2]">Timeout</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden border-t border-b1 p-4 bg-s2">
        <div class="bg-accent/[0.05] border border-accent/[0.2] rounded-lg p-3 mb-3">
          <div class="text-[10px] font-bold text-accent mb-1"><i class="fa-solid fa-clock"></i> SLA Timeout</div>
          <div class="text-[11px] text-watan-text">job پس از ۱۲۰ ثانیه بدون پاسخ — SLA نقض شد. کردیت بازگشت داده شد.</div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">مدت انتظار</div><div class="text-[11px] text-red font-bold">۱۲۰.۴ ثانیه</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">refund</div><div class="text-[11px] text-green font-bold">بازگشت کردیت ✓</div></div>
        </div>
      </div>
    </div>

    {{-- Log Row: J5015 success --}}
    <div class="log-row-wrapper" data-status="success" data-provider="fal" data-search="j5015 ahmad.r">
      <div class="log-row grid items-center px-4 py-[10px] hover:bg-s2 transition-colors duration-150 cursor-pointer" style="grid-template-columns:80px 1fr 1fr 90px 60px 60px 70px 36px" onclick="toggleLogDetail(this)">
        <div class="font-mono text-[11px] text-accent font-bold">#J5015</div>
        <div>
          <div class="text-[12px] text-watan-text">ahmad.r</div>
          <div class="text-[10px] text-watan-text3">۱۴۰۵/۰۴/۰۷ — ۱۳:۴۴</div>
        </div>
        <div class="text-[12px] text-watan-text2 truncate">عکس رستوران</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></div>
        <div class="text-[12px] font-bold text-watan-text">۶.۸s</div>
        <div class="text-[11px] text-watan-text3">$0.013</div>
        <div><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></div>
        <div class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[10px] text-watan-text3 log-expand-icon"><i class="fa-solid fa-chevron-down"></i></div>
      </div>
      <div class="log-detail hidden p-4 bg-s2">
        <div class="grid grid-cols-3 gap-3">
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">prompt used</div><div class="text-[11px] text-watan-text font-mono">restaurant-system</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">model</div><div class="text-[11px] text-watan-text font-mono">flux-pro v1.1</div></div>
          <div class="bg-s1 border border-b1 rounded-lg p-3"><div class="text-[10px] text-watan-text3 mb-1">output size</div><div class="text-[11px] text-watan-text">۱۰۲۴×۱۰۲۴</div></div>
        </div>
      </div>
    </div>

  </div>{{-- #ai-logs-table --}}

  {{-- Pagination --}}
  <div class="flex items-center justify-between mt-4">
    <div class="text-[11px] text-watan-text3">نمایش ۱–۷ از ۱,۲۴۷ جاب</div>
    <div class="flex items-center gap-[4px]">
      <button class="w-[32px] h-[32px] rounded-lg bg-s1 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-colors duration-150"><i class="fa-solid fa-chevron-right"></i></button>
      <button class="w-[32px] h-[32px] rounded-lg bg-green/[0.1] border border-green/[0.3] flex items-center justify-center text-[11px] text-green font-bold">۱</button>
      <button class="w-[32px] h-[32px] rounded-lg bg-s1 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-colors duration-150">۲</button>
      <button class="w-[32px] h-[32px] rounded-lg bg-s1 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-colors duration-150">۳</button>
      <span class="text-[11px] text-watan-text3 px-1">...</span>
      <button class="w-[32px] h-[32px] rounded-lg bg-s1 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-colors duration-150">۱۷۸</button>
      <button class="w-[32px] h-[32px] rounded-lg bg-s1 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-colors duration-150"><i class="fa-solid fa-chevron-left"></i></button>
    </div>
  </div>

</div>

<script>
function toggleLogDetail(rowEl) {
  const wrapper = rowEl.closest('.log-row-wrapper');
  const detail  = wrapper.querySelector('.log-detail');
  const icon    = rowEl.querySelector('.log-expand-icon i');
  const isOpen  = !detail.classList.contains('hidden');

  detail.classList.toggle('hidden', isOpen);
  if (icon) {
    icon.style.transform = isOpen ? '' : 'rotate(180deg)';
  }
}

function filterAiLogs(status, el) {
  document.querySelectorAll('#ai-log-status-filters .ai-log-filter').forEach(btn => {
    btn.classList.remove('active');
    btn.classList.add('bg-s1', 'text-watan-text3');
    btn.classList.remove('bg-s2', 'text-watan-text');
  });
  el.classList.add('active', 'bg-s2', 'text-watan-text');
  el.classList.remove('bg-s1', 'text-watan-text3');

  document.querySelectorAll('#ai-logs-table .log-row-wrapper').forEach(row => {
    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
  });
}

function filterAiLogsByProvider(provider) {
  document.querySelectorAll('#ai-logs-table .log-row-wrapper').forEach(row => {
    row.style.display = (provider === 'all' || row.dataset.provider === provider) ? '' : 'none';
  });
}

function searchAiLogs(query) {
  const q = query.toLowerCase();
  document.querySelectorAll('#ai-logs-table .log-row-wrapper').forEach(row => {
    const s = (row.dataset.search || '').toLowerCase();
    row.style.display = (!q || s.includes(q)) ? '' : 'none';
  });
}
</script>
