{{-- ══ AI PROMPTS PAGE ══ --}}
<div id="ai-prompts-page" style="display:none;">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-3">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">مدیریت پرامپت‌ها</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">پرامپت‌های ذخیره‌شده در دیتابیس — نسخه‌بندی و تست</div>
    </div>
    <button onclick="openAiPromptEditor()"
      class="flex items-center gap-2 bg-green text-white text-[12px] font-bold py-[8px] px-4 rounded-lg hover:opacity-90 transition-opacity duration-150">
      <i class="fa-solid fa-plus text-[11px]"></i> پرامپت جدید
    </button>
  </div>

  {{-- Filters + Search --}}
  <div class="flex items-center gap-3 mb-5 flex-wrap">
    <div class="flex items-center gap-[6px] bg-s2 border border-b1 rounded-lg px-3 h-[36px] w-[240px] focus-within:border-b2 transition-colors duration-150">
      <i class="fa-solid fa-magnifying-glass text-watan-text3 text-xs"></i>
      <input type="text" id="ai-prompt-search" placeholder="جستجو در پرامپت‌ها..."
        oninput="searchAiPrompts(this.value)"
        class="bg-transparent border-0 outline-none text-[12px] text-watan-text flex-1 placeholder:text-watan-text3">
    </div>
    <select onchange="filterAiPromptsByType(this.value)"
      class="bg-s2 border border-b1 rounded-lg px-3 h-[36px] text-[12px] text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150">
      <option value="all">همه انواع</option>
      <option value="system">System</option>
      <option value="user">User</option>
      <option value="negative">Negative</option>
      <option value="enhancement">Enhancement</option>
    </select>
    <select onchange="filterAiPromptsByProduct(this.value)"
      class="bg-s2 border border-b1 rounded-lg px-3 h-[36px] text-[12px] text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150">
      <option value="all">همه محصولات</option>
      <option>عکس رستوران</option>
      <option>پس‌زمینه غذا</option>
      <option>ویترین کافه</option>
      <option>منو دیجیتال</option>
    </select>
  </div>

  {{-- Prompts Table --}}
  <div class="bg-s1 border border-b1 rounded-[10px] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full" id="ai-prompts-table">
        <thead>
          <tr class="border-b border-b1">
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">نام پرامپت</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">نوع</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px] max-[768px]:hidden">محصول</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px] max-[900px]:hidden">استفاده ۷روزه</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px] max-[900px]:hidden">آخرین ویرایش</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">وضعیت</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">عملیات</th>
          </tr>
        </thead>
        <tbody id="ai-prompts-tbody">

          <tr class="prompt-row border-b border-b1 hover:bg-s2 transition-colors duration-150" data-name="restaurant-system" data-type="system" data-product="عکس رستوران">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-watan-text">restaurant-system</div>
              <div class="text-[10px] text-watan-text3 mt-[2px] font-mono truncate max-w-[200px]">You are a professional food photographer...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2]">System</span></td>
            <td class="py-[11px] px-4 text-[12px] text-watan-text2 max-[768px]:hidden">عکس رستوران</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-watan-text max-[900px]:hidden">۴۸۲</td>
            <td class="py-[11px] px-4 text-[11px] text-watan-text3 max-[900px]:hidden">۱۴۰۵/۰۴/۰۲</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt('restaurant-system','You are a professional food photographer for restaurants. Create high-quality, appetizing images...')" class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-accent/[0.4] hover:text-accent transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.5] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-b1 hover:bg-s2 transition-colors duration-150" data-name="restaurant-negative" data-type="negative" data-product="عکس رستوران">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-watan-text">restaurant-negative</div>
              <div class="text-[10px] text-watan-text3 mt-[2px] font-mono truncate max-w-[200px]">blurry, low quality, dark, unappealing...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-red/[0.08] text-red border border-red/[0.2]">Negative</span></td>
            <td class="py-[11px] px-4 text-[12px] text-watan-text2 max-[768px]:hidden">عکس رستوران</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-watan-text max-[900px]:hidden">۴۸۲</td>
            <td class="py-[11px] px-4 text-[11px] text-watan-text3 max-[900px]:hidden">۱۴۰۵/۰۳/۲۸</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt('restaurant-negative','blurry, low quality, dark, unappealing food, dirty plates...')" class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-accent/[0.4] hover:text-accent transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.5] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-b1 hover:bg-s2 transition-colors duration-150" data-name="background-enhancer" data-type="enhancement" data-product="پس‌زمینه غذا">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-watan-text">background-enhancer</div>
              <div class="text-[10px] text-watan-text3 mt-[2px] font-mono truncate max-w-[200px]">Replace background with elegant dining environment...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-orange/[0.08] text-orange border border-orange/[0.2]">Enhancement</span></td>
            <td class="py-[11px] px-4 text-[12px] text-watan-text2 max-[768px]:hidden">پس‌زمینه غذا</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-watan-text max-[900px]:hidden">۲۱۷</td>
            <td class="py-[11px] px-4 text-[11px] text-watan-text3 max-[900px]:hidden">۱۴۰۵/۰۴/۰۵</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt('background-enhancer','Replace background with elegant dining environment, warm lighting...')" class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-accent/[0.4] hover:text-accent transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.5] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row border-b border-b1 hover:bg-s2 transition-colors duration-150" data-name="cafe-window-display" data-type="user" data-product="ویترین کافه">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-watan-text">cafe-window-display</div>
              <div class="text-[10px] text-watan-text3 mt-[2px] font-mono truncate max-w-[200px]">A cozy cafe window display with artisanal products...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">User</span></td>
            <td class="py-[11px] px-4 text-[12px] text-watan-text2 max-[768px]:hidden">ویترین کافه</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-watan-text max-[900px]:hidden">۹۸</td>
            <td class="py-[11px] px-4 text-[11px] text-watan-text3 max-[900px]:hidden">۱۴۰۵/۰۴/۰۱</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">فعال</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt('cafe-window-display','A cozy cafe window display with artisanal products, natural light...')" class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-accent/[0.4] hover:text-accent transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.5] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

          <tr class="prompt-row hover:bg-s2 transition-colors duration-150" data-name="menu-digital-draft" data-type="user" data-product="منو دیجیتال">
            <td class="py-[11px] px-4">
              <div class="text-[12px] font-bold text-watan-text">menu-digital-draft</div>
              <div class="text-[10px] text-watan-text3 mt-[2px] font-mono truncate max-w-[200px]">Professional digital menu layout with Persian typography...</div>
            </td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">User</span></td>
            <td class="py-[11px] px-4 text-[12px] text-watan-text2 max-[768px]:hidden">منو دیجیتال</td>
            <td class="py-[11px] px-4 text-[12px] font-bold text-watan-text max-[900px]:hidden">۱۴۳</td>
            <td class="py-[11px] px-4 text-[11px] text-watan-text3 max-[900px]:hidden">۱۴۰۵/۰۳/۱۵</td>
            <td class="py-[11px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-orange/[0.08] text-orange border border-orange/[0.2]">پیش‌نویس</span></td>
            <td class="py-[11px] px-4">
              <div class="flex items-center gap-[6px]">
                <button onclick="editAiPrompt('menu-digital-draft','Professional digital menu layout with Persian typography and RTL support...')" class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="ویرایش"><i class="fa-solid fa-pen"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-accent/[0.4] hover:text-accent transition-all duration-150" title="تست"><i class="fa-solid fa-play"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-watan-text3 hover:border-b2 hover:text-watan-text transition-all duration-150" title="تاریخچه نسخه"><i class="fa-solid fa-clock-rotate-left"></i></button>
                <button class="w-[28px] h-[28px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-[11px] text-red/[0.5] hover:bg-red/[0.08] hover:border-red/[0.3] hover:text-red transition-all duration-150" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
            </td>
          </tr>

        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Prompt Editor Panel --}}
<div id="ai-prompt-editor" class="fixed top-0 left-0 bottom-0 z-[150] flex flex-col" style="width:480px;max-width:100vw;background:var(--s1);border-right:1px solid var(--b1);box-shadow:-24px 0 48px rgba(0,0,0,0.4);transform:translateX(-100%);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);">
  <div class="flex items-center justify-between p-5 border-b border-b1 flex-shrink-0">
    <div>
      <div class="text-[14px] font-extrabold text-watan-text" id="prompt-editor-title">پرامپت جدید</div>
      <div class="text-[11px] text-watan-text3 mt-[2px]" id="prompt-editor-subtitle">ویرایش محتوا و تنظیمات</div>
    </div>
    <button onclick="closeAiPromptEditor()" class="w-[30px] h-[30px] rounded-lg bg-s2 border border-b1 flex items-center justify-center text-sm text-watan-text3 hover:text-watan-text hover:border-b2 transition-all duration-150">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="flex-1 overflow-y-auto p-5 space-y-4" style="scrollbar-width:none">
    <div>
      <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">نام پرامپت</label>
      <input type="text" id="prompt-editor-name" class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text placeholder:text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150" placeholder="مثال: restaurant-system-v2">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">نوع</label>
        <select class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text focus:border-b2 focus:outline-none transition-colors duration-150">
          <option>System</option>
          <option>User</option>
          <option>Negative</option>
          <option>Enhancement</option>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">محصول</label>
        <select class="w-full bg-s2 border border-b1 rounded-lg px-3 h-[38px] text-[12px] text-watan-text focus:border-b2 focus:outline-none transition-colors duration-150">
          <option>همه</option>
          <option>عکس رستوران</option>
          <option>پس‌زمینه غذا</option>
          <option>ویترین کافه</option>
          <option>منو دیجیتال</option>
        </select>
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">متن پرامپت</label>
      <textarea id="prompt-editor-content" rows="10" class="w-full bg-s2 border border-b1 rounded-lg px-3 py-2 text-[12px] text-watan-text placeholder:text-watan-text3 focus:border-b2 focus:outline-none transition-colors duration-150 resize-none font-mono leading-relaxed" placeholder="متن پرامپت را اینجا وارد کنید..."></textarea>
      <div class="flex items-center justify-between mt-[6px]">
        <span class="text-[10px] text-watan-text3" id="prompt-char-count">۰ کاراکتر</span>
        <span class="text-[10px] text-watan-text3">≈ <span id="prompt-token-count">۰</span> توکن</span>
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">متغیرهای پویا</label>
      <div class="bg-s2 border border-b1 rounded-lg p-3 flex flex-wrap gap-2">
<<<<<<< Updated upstream
        <span onclick="insertPromptVar('@{{product_name}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">{{'{{'}}product_name{{'}}'}}</span>
        <span onclick="insertPromptVar('@{{cuisine_type}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">{{'{{'}}cuisine_type{{'}}'}}</span>
        <span onclick="insertPromptVar('@{{style}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">{{'{{'}}style{{'}}'}}</span>
        <span onclick="insertPromptVar('@{{aspect_ratio}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">{{'{{'}}aspect_ratio{{'}}'}}</span>
=======
     <span onclick="insertPromptVar('@{{product_name}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">@{{product_name}}</span>
<span onclick="insertPromptVar('@{{cuisine_type}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">@{{cuisine_type}}</span>
<span onclick="insertPromptVar('@{{style}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">@{{style}}</span>
<span onclick="insertPromptVar('@{{aspect_ratio}}')" class="text-[10px] font-mono font-bold py-[4px] px-[8px] rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2] cursor-pointer hover:bg-accent/[0.15] transition-colors duration-150">@{{aspect_ratio}}</span>
>>>>>>> Stashed changes
      </div>
    </div>
    <div>
      <label class="text-[11px] font-bold text-watan-text3 block mb-[6px]">وضعیت</label>
      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="active" checked class="accent-green"> <span class="text-[12px] text-watan-text">فعال</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="draft" class="accent-orange"> <span class="text-[12px] text-watan-text">پیش‌نویس</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="prompt-status" value="inactive" class="accent-red"> <span class="text-[12px] text-watan-text">غیرفعال</span>
        </label>
      </div>
    </div>
  </div>

  <div class="flex items-center gap-3 p-5 border-t border-b1 flex-shrink-0">
    <button onclick="closeAiPromptEditor()" class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-s2 border border-b1 text-watan-text2 hover:border-b2 hover:text-watan-text transition-colors duration-150">
      ...بستن
    </button>
    <button class="text-[12px] font-bold py-[9px] px-4 rounded-lg border border-green/[0.4] text-green hover:bg-green/[0.08] transition-colors duration-150">
      <i class="fa-solid fa-play text-[10px] ml-1"></i> تست
    </button>
    <button class="flex-1 text-[12px] font-bold py-[9px] rounded-lg bg-green text-white hover:opacity-90 transition-opacity duration-150">
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
  document.getElementById('prompt-editor-content').value = '';
  updatePromptCounts();
}

function editAiPrompt(name, content) {
  document.getElementById('ai-prompt-editor').style.transform = 'translateX(0)';
  document.getElementById('ai-prompt-overlay').classList.remove('hidden');
  document.getElementById('prompt-editor-title').textContent = 'ویرایش پرامپت';
  document.getElementById('prompt-editor-subtitle').textContent = name;
  document.getElementById('prompt-editor-name').value = name;
  document.getElementById('prompt-editor-content').value = content;
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
  ta.value = ta.value.substring(0, start) + v + ta.value.substring(end);
  ta.selectionStart = ta.selectionEnd = start + v.length;
  ta.focus();
  updatePromptCounts();
}

function searchAiPrompts(query) {
  const q = query.toLowerCase();
  document.querySelectorAll('#ai-prompts-tbody .prompt-row').forEach(row => {
    const name = (row.dataset.name || '').toLowerCase();
    const product = (row.dataset.product || '').toLowerCase();
    row.style.display = (!q || name.includes(q) || product.includes(q)) ? '' : 'none';
  });
}

function filterAiPromptsByType(type) {
  document.querySelectorAll('#ai-prompts-tbody .prompt-row').forEach(row => {
    row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
  });
}

function filterAiPromptsByProduct(product) {
  document.querySelectorAll('#ai-prompts-tbody .prompt-row').forEach(row => {
    row.style.display = (product === 'all' || row.dataset.product === product) ? '' : 'none';
  });
}
</script>