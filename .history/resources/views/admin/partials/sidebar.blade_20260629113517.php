<!--
  نیاز به این رنگ‌ها در tailwind.config.js (یا استفاده مستقیم از arbitrary values که همینجا استفاده شده):
  bg:    #0c0c10
  s1:    #111116
  s2:    #16161c
  b1:    #222230
  b2:    #2e2e3e
  text2: #a8c4a8
  text3: #4d7a56
  green: #0BBF53
  accent:#a07af5
  orange:#f5923a
-->

<aside class="fixed top-0 right-0 bottom-0 w-64 bg-[#111116] border-l border-[#222230] flex flex-col overflow-y-auto z-[100] font-[IRANSansXFaNum] direction-rtl text-white" dir="rtl">

  <!-- لوگو -->
  <div class="flex items-center gap-2.5 px-4 py-[18px] border-b border-[#222230] flex-shrink-0">
    <div class="w-9 h-9 rounded-[10px] bg-[#0BBF53] flex items-center justify-center text-[17px] font-black text-white shadow-[0_0_16px_rgba(11,191,83,.3)]">و</div>
    <div>
      <div class="text-sm font-extrabold">وطن استودیو</div>
      <div class="text-[9px] text-[#4d7a56] tracking-[2.5px] uppercase">Admin Panel</div>
    </div>
  </div>

  <!-- پروفایل کاربر -->
  <div class="flex items-center gap-2.5 px-3.5 py-2.5 border-b border-[#222230]">
    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#a07af5] to-[#6a4dcc] flex items-center justify-center text-[13px] font-bold flex-shrink-0">م</div>
    <div class="flex-1">
      <div class="text-xs font-bold">محسن رضایی</div>
      <div class="text-[9px] font-bold px-1.5 py-px rounded bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/25 inline-block mt-0.5">مدیر کل</div>
    </div>
    <div class="w-[7px] h-[7px] rounded-full bg-[#0BBF53] shadow-[0_0_6px_#0BBF53] flex-shrink-0"></div>
  </div>

  <!-- ناوبری -->
  <nav class="flex-1 py-2">

    <a href="/admin/dashboard"
       class="flex items-center gap-2.5 px-2 mx-1.5 mb-1 rounded-lg h-[38px] no-underline hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-bolt-lightning"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">مرکز فرماندهی</div>
    </a>

    <div class="text-[9px] font-bold tracking-[2.5px] uppercase text-[#4d7a56] px-4 pt-3 pb-1">مدیریت محصولات</div>

    <!-- آیتم فعال با زیرمنو -->
    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer bg-[#a07af5]/[0.12]">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a07af5] flex-shrink-0">
        <i class="fa-solid fa-box-open"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-white">محصولات</div>
      <i class="fa-solid fa-chevron-down text-[9px] text-[#4d7a56]"></i>
    </div>

    <div class="py-0.5 pb-1">

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">داشبورد محصولات</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در حال طراحی</span>
      </div>

      <a href="/admin/products"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">لیست محصولات</div>
      </a>

      <a href="/admin/products/create"
         class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md no-underline bg-[#a07af5]/10 group">
        <div class="w-1 h-1 rounded-full bg-[#a07af5] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-semibold text-white">ثبت محصول جدید</div>
      </a>

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">دسته‌بندی‌ها</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در حال طراحی</span>
      </div>

      <div class="flex items-center gap-2 px-2.5 py-1.5 mx-1.5 ml-[30px] rounded-md cursor-pointer hover:bg-[#16161c] transition-colors">
        <div class="w-1 h-1 rounded-full bg-[#2e2e3e] flex-shrink-0"></div>
        <div class="flex-1 text-[11.5px] font-medium text-[#a8c4a8]">قیمت‌گذاری</div>
        <span class="text-[9px] px-1.5 py-px rounded bg-[#f5923a]/[0.08] text-[#f5923a] border border-[#f5923a]/20">در حال طراحی</span>
      </div>

    </div>

    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">سفارشات</div>
      <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25">در حال طراحی</span>
    </div>

    <div class="text-[9px] font-bold tracking-[2.5px] uppercase text-[#4d7a56] px-4 pt-3 pb-1">هوش مصنوعی</div>

    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-microchip"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">مدیریت مدل‌ها</div>
      <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25">در حال طراحی</span>
    </div>

    <div class="h-px bg-[#222230] mx-3 my-2"></div>

    <div class="flex items-center gap-2.5 px-2 mx-1.5 rounded-lg h-[38px] cursor-pointer hover:bg-[#16161c] transition-colors">
      <div class="w-[30px] h-[30px] flex items-center justify-center text-[13px] text-[#a8c4a8] flex-shrink-0">
        <i class="fa-solid fa-gear"></i>
      </div>
      <div class="flex-1 text-[12.5px] font-semibold text-[#a8c4a8]">تنظیمات</div>
    </div>

  </nav>
</aside>