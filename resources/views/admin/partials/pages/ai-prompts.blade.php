{{-- ══ AI PROMPTS PAGE ══ --}}
<div id="ai-prompts-page" style="display:none;">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-3">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">مدیریت پرامپت‌ها</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">پرامپت‌های ذخیره‌شده در دیتابیس — نسخه‌ب بندی و تست</div>
    </div>
    <button onclick="openAiPromptEditor()"
      class="flex items-center gap-2 bg-[#0BBF53] text-white text-[12px] font-bold py-[8px] px-4 rounded-lg hover:opacity-90 transition-opacity duration-150">
      <i class="fa-solid fa-plus text-[11px]"></i> پرامپت جدید
    </button>
  </div>

  {{-- Filters + Search --}}
  <div class="flex items-center gap-3 mb-5 flex-wrap">
    <div class="flex items-center gap-[6px] bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[36px] w-[240px] focus-within:border-[#2e2e3e] transition-colors duration-150">
      <i class="fa-solid fa-magnifying-glass text-watan-text3 text-xs"></i>
      <input type="text" id="ai-prompt-search" placeholder="جستجو در پرامپت‌ها..."
        oninput="applyAiPromptFilters()"
        class="bg-transparent border-0 outline-none text-[12px] text-white flex-1 placeholder:text-watan-text3">
    </div>
    <select id="filter-prompt-type" onchange="applyAiPromptFilters()"
      class="bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[36px] text-[12px] text-[#4d7a56] focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150">
      <option value="all">همه انواع</option>
      <option value="system">System</option>
      <option value="user">User</option>
      <option value="negative">Negative</option>
      <option value="enhancement">Enhancement</option>
    </select>
    <select id="filter-prompt-product" onchange="applyAiPromptFilters()"
      class="bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[36px] text-[12px] text-[#4d7a56] focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150">
      <option value="all">همه محصولات</option>
      <option value="عکس رستوران">عکس رستوران</option>
      <option value="پس‌زمینه غذا">پس‌زمینه غذا</option>
      <option value="ویترین کافه">ویترین کافه</option>
      <option value="منو دیجیتال">منو دیجیتال</option>
    </select>
  </div>

  {{-- Prompts Table --}}
  <div class="bg-[#111116] border border-[#222230] rounded-[10px] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full" id="ai-prompts-table">
        <thead>
          <tr class="border-b border-[#222230]">
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px]">نام پرامپت</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px]">نوع</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px] max-[768px]:hidden">محصول</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px] max-[900px]:hidden">استفاده ۷روزه</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px] max-[900px]:hidden">آخرین ویرایش</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px]">وضعیت</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-[#4d7a56] uppercase tracking-[1.5px]">عملیات</th>
          </tr>
        </thead>
        <tbody id="ai-prompts-tbody">

          <tr class="prompt-row border-b border-[#222230] hover:bg-[#16161c] transition-colors duration-150" data-name="restaurant-system" data-type="system" data-product="عکس رستوران" data-status="active">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-white">restaurant-system</div>
              <div class="text-[10px] text-[#4d7a56] mt-[2px] font-mono truncate max-w-[200px]">You are a professional food photographer...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/[0.2]">System</span></td>
            <td class="py-[11px] px-4 text-[12px] text-[#a8c4a8] max-[768px]:hidden">عکس رستوران</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-white max-[900px]:hidden">۴۸۲</td>
            <td class="py-[11px] px-4 text-[11px] text-[#4d7a56] max-[900px]:hidden">۱۴۰۵/۰۴/۰۲</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt(this, 'restaurant-system','You are a professional food photographer for restaurants. Create high-quality, appetizing images...')" class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#a07af5]/[0.4] hover:text-[#a07af5] transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-red-500/[0.5] hover:bg-red-500/[0.08] hover:border-red-500/[0.3] hover:text-red-500 transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-[#222230] hover:bg-[#16161c] transition-colors duration-150" data-name="restaurant-negative" data-type="negative" data-product="عکس رستوران" data-status="active">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-white">restaurant-negative</div>
              <div class="text-[10px] text-[#4d7a56] mt-[2px] font-mono truncate max-w-[200px]">blurry, low quality, dark, unappealing...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-red-500/[0.08] text-red-500 border border-red-500/[0.2]">Negative</span></td>
            <td class="py-[11px] px-4 text-[12px] text-[#a8c4a8] max-[768px]:hidden">عکس رستوران</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-white max-[900px]:hidden">۴۸۲</td>
            <td class="py-[11px] px-4 text-[11px] text-[#4d7a56] max-[900px]:hidden">۱۴۰۵/۰۳/۲۸</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt(this, 'restaurant-negative','blurry, low quality, dark, unappealing food, dirty plates...')" class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#a07af5]/[0.4] hover:text-[#a07af5] transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-red-500/[0.5] hover:bg-red-500/[0.08] hover:border-red-500/[0.3] hover:text-red-500 transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-[#222230] hover:bg-[#16161c] transition-colors duration-150" data-name="background-enhancer" data-type="enhancement" data-product="پس‌زمینه غذا" data-status="active">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-white">background-enhancer</div>
              <div class="text-[10px] text-[#4d7a56] mt-[2px] font-mono truncate max-w-[200px]">Replace background with elegant dining environment...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/[0.2]">Enhancement</span></td>
            <td class="py-[11px] px-4 text-[12px] text-[#a8c4a8] max-[768px]:hidden">پس‌زمینه غذا</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-white max-[900px]:hidden">۲۱۷</td>
            <td class="py-[11px] px-4 text-[11px] text-[#4d7a56] max-[900px]:hidden">۱۴۰۵/۰۴/۰۵</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt(this, 'background-enhancer','Replace background with elegant dining environment, warm lighting...')" class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#a07af5]/[0.4] hover:text-[#a07af5] transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-red-500/[0.5] hover:bg-red-500/[0.08] hover:border-red-500/[0.3] hover:text-red-500 transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-[#222230] hover:bg-[#16161c] transition-colors duration-150" data-name="cafe-window-display" data-type="user" data-product="ویترین کافه" data-status="active">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-white">cafe-window-display</div>
              <div class="text-[10px] text-[#4d7a56] mt-[2px] font-mono truncate max-w-[200px]">A cozy cafe window display with artisanal products...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">User</span></td>
            <td class="py-[11px] px-4 text-[12px] text-[#a8c4a8] max-[768px]:hidden">ویترین کافه</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-white max-[900px]:hidden">۹۸</td>
            <td class="py-[11px] px-4 text-[11px] text-[#4d7a56] max-[900px]:hidden">۱۴۰۵/۰۴/۰۱</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt(this, 'cafe-window-display','A cozy cafe window display with artisanal products, natural light...')" class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#a07af5]/[0.4] hover:text-[#a07af5] transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-red-500/[0.5] hover:bg-red-500/[0.08] hover:border-red-500/[0.3] hover:text-red-500 transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row hover:bg-[#16161c] transition-colors duration-150" data-name="menu-digital-draft" data-type="user" data-product="منو دیجیتال" data-status="draft">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-white">menu-digital-draft</div>
              <div class="text-[10px] text-[#4d7a56] mt-[2px] font-mono truncate max-w-[200px]">Professional digital menu layout with Persian typography...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#0BBF53]/[0.08] text-[#0BBF53] border border-[#0BBF53]/[0.2]">User</span></td>
            <td class="py-[11px] px-4 text-[12px] text-[#a8c4a8] max-[768px]:hidden">منو دیجیتال</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-white max-[900px]:hidden">۱۴۳</td>
            <td class="py-[11px] px-4 text-[11px] text-[#4d7a56] max-[900px]:hidden">۱۴۰۵/۰۳/۱۵</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/[0.2]">پیش‌نویس</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt(this, 'menu-digital-draft','Professional digital menu layout with Persian typography and RTL support...')" class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#a07af5]/[0.4] hover:text-[#a07af5] transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-[#4d7a56] hover:border-[#2e2e3e] hover:text-white transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-[11px] text-red-500/[0.5] hover:bg-red-500/[0.08] hover:border-red-500/[0.3] hover:text-red-500 transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Prompt Editor Panel --}}
<div id="ai-prompt-editor" class="fixed top-0 left-0 bottom-0 z-[150] flex flex-col" style="width:480px;max-width:100vw;background:#111116;border-right:1px solid #222230;box-shadow:24px 0 48px rgba(0,0,0,0.4);transform:translateX(-100%);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);">
  <div class="flex items-center justify-between p-5 border-b border-[#222230] flex-shrink-0">
    <div>
      <div class="text-[14px] font-extrabold text-white" id="prompt-editor-title">پرامپت جدید</div>
      <div class="text-[11px] text-[#4d7a56] mt-[2px]" id="prompt-editor-subtitle">ویرایش محتوا و تنظیمات</div>
    </div>
    <button onclick="closeAiPromptEditor()" class="w-[30px] h-[30px] rounded-lg bg-[#16161c] border border-[#222230] flex items-center justify-center text-sm text-[#4d7a56] hover:text-white hover:border-[#2e2e3e] transition-all duration-150">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="flex-1 overflow-y-auto p-5 space-y-4" style="scrollbar-width:none">
    <div>
      <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">نام پرامپت</label>
      <input type="text" id="prompt-editor-name" class="w-full bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[38px] text-[12px] text-white placeholder:text-[#4d7a56] focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150" placeholder="مثال: restaurant-system-v2">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">نوع</label>
        <select id="prompt-editor-type" class="w-full bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[38px] text-[12px] text-white focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150">
          <option value="system">System</option>
          <option value="user">User</option>
          <option value="negative">Negative</option>
          <option value="enhancement">Enhancement</option>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">محصول</label>
        <select id="prompt-editor-product" class="w-full bg-[#16161c] border border-[#222230] rounded-lg px-3 h-[38px] text-[12px] text-white focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150">
          <option value="همه">همه</option>
          <option value="عکس رستوران">عکس رستوران</option>
          <option value="پس‌زمینه غذا">پس‌زمینه غذا</option>
          <option value="ویترین کافه">ویترین کافه</option>
          <option value="منو دیجیتال">منو دیجیتال</option>
        </select>
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">متن پرامپت</label>
      <textarea id="prompt-editor-content" rows="10" class="w-full bg-[#16161c] border border-[#222230] rounded-lg px-3 py-2 text-[12px] text-white placeholder:text-[#4d7a56] focus:border-[#2e2e3e] focus:outline-none transition-colors duration-150 resize-none font-mono leading-relaxed" placeholder="متن پرامپت را اینجا وارد کنید..."></textarea>
      <div class="flex items-center justify-between mt-[6px]">
        <span class="text-[10px] text-[#4d7a56]" id="prompt-char-count">۰ کاراکتر</span>
        <span class="text-[10px] text-[#4d7a56]">≈ <span id="prompt-token-count">۰</span> توکن</span>
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">متغیرهای پویا</label>
      <div class="bg-[#16161c] border border-[#222230] rounded-lg p-3 flex flex-wrap gap-2" dir="ltr">
        <span onclick="insertPromptVar('@{{product_name}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/[0.2] cursor-pointer hover:bg-[#a07af5]/[0.15] transition-colors duration-150">@{{product_name}}</span>
        <span onclick="insertPromptVar('@{{cuisine_type}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/[0.2] cursor-pointer hover:bg-[#a07af5]/[0.15] transition-colors duration-150">@{{cuisine_type}}</span>
        <span onclick="insertPromptVar('@{{style}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/[0.2] cursor-pointer hover:bg-[#a07af5]/[0.15] transition-colors duration-150">@{{style}}</span>
        <span onclick="insertPromptVar('@{{aspect_ratio}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-[#a07af5]/[0.08] text-[#a07af5] border border-[#a07af5]/[0.2] cursor-pointer hover:bg-[#a07af5]/[0.15] transition-colors duration-150">@{{aspect_ratio}}</span>
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-[#4d7a56] block mb-[6px]">وضعیت</label>
      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="active" id="status-active" checked class="accent-[#0BBF53]"> <span class="text-[12px] text-white">فعال</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="draft" id="status-draft" class="accent-[#f5923a]"> <span class="text-[12px] text-white">پیش‌نویس</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="inactive" id="status-inactive" class="accent-red-500"> <span class="text-[12px] text-white">غیرفعال</span>
        </label>
      </div>
    </div>
  </div>

  <div class="flex items-center gap-3 p-5 border-t border-[#222230] flex-shrink-0">
    <button onclick="closeAiPromptEditor()" class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-[#16161c] border border-[#222230] text-[#a8c4a8] hover:border-[#2e2e3e] hover:text-white transition-colors duration-150">
      بستن
    </button>
    <button class="text-[12px] font-bold py-[9px] px-4 rounded-lg border border-[#0BBF53]/[0.4] text-[#0BBF53] hover:bg-[#0BBF53]/[0.08] transition-colors duration-150">
      <i class="fa-solid fa-play text-[10px] ml-1"></i> تست
    </button>
    <button class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-[#0BBF53] text-white hover:opacity-90 transition-opacity duration-150">
      <i class="fa-solid fa-floppy-disk text-[10px] ml-1"></i> ذخیره
    </button>
  </div>
</div>

<div id="ai-prompt-overlay" class="fixed inset-0 z-[140] hidden" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);" onclick="closeAiPromptEditor()"></div>

<script>
function openAiPromptEditor() {
  document.getElementById('ai-prompt-editor').style.transform = 'translateX(0)';
  document.getElementById('ai-prompt-overlay').classList.remove('hidden');
  document.getElementById('prompt-editor-title').textContent = 'پرامپت جدید';
  document.getElementById('prompt-editor-subtitle').textContent = 'ایجاد پرامپت جدید';
  document.getElementById('prompt-editor-name').value = '';
  document.getElementById('prompt-editor-name').disabled = false;
  document.getElementById('prompt-editor-content').value = '';
  document.getElementById('prompt-editor-type').value = 'system';
  document.getElementById('prompt-editor-product').value = 'همه';
  document.getElementById('status-active').checked = true;
  updatePromptCounts();
}

function editAiPrompt(btn, name, content) {
  const row = btn.closest('.prompt-row');
  const type = row.dataset.type;
  const product = row.dataset.product;
  const status = row.dataset.status;

  document.getElementById('ai-prompt-editor').style.transform = 'translateX(0)';
  document.getElementById('ai-prompt-overlay').classList.remove('hidden');
  document.getElementById('prompt-editor-title').textContent = 'ویرایش پرامپت';
  document.getElementById('prompt-editor-subtitle').textContent = name;
  document.getElementById('prompt-editor-name').value = name;
  document.getElementById('prompt-editor-name').disabled = true;
  document.getElementById('prompt-editor-content').value = content;
  
  document.getElementById('prompt-editor-type').value = type;
  document.getElementById('prompt-editor-product').value = product;
  
  if(status === 'active') document.getElementById('status-active').checked = true;
  else if(status === 'draft') document.getElementById('status-draft').checked = true;
  else if(status === 'inactive') document.getElementById('status-inactive').checked = true;

  updatePromptCounts();
}

function closeAiPromptEditor() {
  document.getElementById('ai-prompt-editor').style.transform = 'translateX(-100%)';
  document.getElementById('ai-prompt-overlay').classList.add('hidden');
}

function updatePromptCounts() {
  const ta = document.getElementById('prompt-editor-content');
  if (!ta) return;
  const chars = ta.value.length;
  const tokens = Math.ceil(chars / 4);
  const cc = document.getElementById('prompt-char-count');
  const tc = document.getElementById('prompt-token-count');
  if (cc) cc.textContent = chars.toLocaleString('fa-IR') + ' کاراکتر';
  if (tc) tc.textContent = tokens.toLocaleString('fa-IR');
}

document.addEventListener('input', function(e) {
  if (e.target && e.target.id === 'prompt-editor-content') updatePromptCounts();
});

function insertPromptVar(v) {
  const ta = document.getElementById('prompt-editor-content');
  if (!ta) return;
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  // حذف علامت @ قبل از تزریق به تماشاگه متن
  const cleanVar = v.startsWith('@') ? v.substring(1) : v;
  ta.value = ta.value.substring(0, start) + cleanVar + ta.value.substring(end);
  ta.selectionStart = ta.selectionEnd = start + cleanVar.length;
  ta.focus();
  updatePromptCounts();
}

function applyAiPromptFilters() {
  const searchQuery = document.getElementById('ai-prompt-search').value.toLowerCase();
  const selectedType = document.getElementById('filter-prompt-type').value;
  const selectedProduct = document.getElementById('filter-prompt-product').value;

  document.querySelectorAll('#ai-prompts-tbody .prompt-row').forEach(row => {
    const name = (row.dataset.name || '').toLowerCase();
    const product = (row.dataset.product || '').toLowerCase();
    const type = row.dataset.type;

    const matchesSearch = !searchQuery || name.includes(searchQuery) || product.includes(searchQuery);
    const matchesType = selectedType === 'all' || type === selectedType;
    const matchesProduct = selectedProduct === 'all' || row.dataset.product === selectedProduct;

    if (matchesSearch && matchesType && matchesProduct) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>