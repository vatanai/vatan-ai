    <!-- ══ PRODUCTS LIST PAGE ══ -->
    <div id="products-list-page" class="hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-extrabold tracking-[-0.4px]" style="color:var(--text);">لیست محصولات</h1>
          <div class="text-xs mt-[2px]" style="color:var(--text3);">مدیریت و نظارت بر محصولات AI</div>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="setPLView('grid')" id="pl-grid-btn" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--accent);background:var(--accent);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-solid fa-grip"></i></button>
          <button onclick="setPLView('table')" id="pl-table-btn" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-solid fa-list"></i></button>
          <button onclick="setActiveSub(null,'محصولات','ثبت محصول جدید','products-create-page')" style="display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:32px;border-radius:8px;font-size:12px;font-weight:700;background:var(--accent);color:#fff;border:none;cursor:pointer;font-family:inherit;">
            <i class="fa-solid fa-plus text-[10px]"></i> محصول جدید
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(11,191,83,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid fa-circle-check" style="color:var(--green);font-size:14px;"></i></div>
          <div><div style="font-size:10px;color:var(--text3);">فعال</div><div style="font-size:18px;font-weight:900;color:var(--green);">۱۸</div></div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(160,122,245,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid fa-box" style="color:var(--accent);font-size:14px;"></i></div>
          <div><div style="font-size:10px;color:var(--text3);">کل محصولات</div><div style="font-size:18px;font-weight:900;color:var(--text);">۲۴</div></div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(245,146,58,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid fa-layer-group" style="color:var(--orange);font-size:14px;"></i></div>
          <div><div style="font-size:10px;color:var(--text3);">دسته‌بندی</div><div style="font-size:18px;font-weight:900;color:var(--text);">۷</div></div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid fa-microchip" style="color:#3b82f6;font-size:14px;"></i></div>
          <div><div style="font-size:10px;color:var(--text3);">مدل AI</div><div style="font-size:18px;font-weight:900;color:var(--text);">۴</div></div>
        </div>
      </div>

      <!-- Filters -->
      <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;display:flex;align-items:center;gap:8px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;height:36px;">
          <i class="fa-solid fa-search" style="color:var(--text3);font-size:11px;"></i>
          <input id="pl-search" oninput="filterProducts()" placeholder="جستجوی محصول..." style="flex:1;background:none;border:none;outline:none;font-size:12px;color:var(--text);font-family:inherit;" />
        </div>
        <select id="pl-cat" onchange="filterProducts()" style="height:36px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 10px;font-size:12px;color:var(--text2);font-family:inherit;outline:none;cursor:pointer;">
          <option value="">همه دسته‌ها</option>
          <option>افراد</option><option>سرگرمی</option><option>کسب‌وکار</option><option>رویدادها</option><option>حیوانات</option>
        </select>
        <select id="pl-status" onchange="filterProducts()" style="height:36px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 10px;font-size:12px;color:var(--text2);font-family:inherit;outline:none;cursor:pointer;">
          <option value="">همه وضعیت‌ها</option>
          <option>فعال</option><option>غیرفعال</option><option>پیش‌نویس</option>
        </select>
        <select id="pl-model" onchange="filterProducts()" style="height:36px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 10px;font-size:12px;color:var(--text2);font-family:inherit;outline:none;cursor:pointer;">
          <option value="">همه مدل‌ها</option>
          <option>flux-1.1-pro</option><option>flux-kontext</option><option>sd-3.5-large</option><option>ideogram/v3</option>
        </select>
        <button onclick="document.getElementById('pl-search').value='';document.getElementById('pl-cat').value='';document.getElementById('pl-status').value='';document.getElementById('pl-model').value='';filterProducts();" style="height:36px;padding:0 12px;border-radius:8px;border:1px solid var(--b1);background:none;color:var(--text3);font-size:11px;cursor:pointer;font-family:inherit;">پاک‌کردن</button>
      </div>

      <!-- Grid View -->
      <div id="pl-grid-view">
        <div id="pl-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;"></div>
      </div>

      <!-- Table View -->
      <div id="pl-table-view" style="display:none;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:2px solid var(--b1);">
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">محصول</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">دسته</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">مدل AI</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">وضعیت</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">سفارشات</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">درآمد</th>
                <th style="padding:12px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;text-align:right;">موفقیت AI</th>
                <th style="padding:12px 16px;"></th>
              </tr>
            </thead>
            <tbody id="pl-table-body"></tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:18px;padding:0 2px;">
        <div style="font-size:12px;color:var(--text3);" id="pl-count-label">نمایش ۱–۱۲ از ۲۴ محصول</div>
        <div style="display:flex;gap:4px;">
          <button style="width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s2);color:var(--text2);cursor:pointer;font-size:11px;"><i class="fa-solid fa-chevron-right"></i></button>
          <button style="width:30px;height:30px;border-radius:7px;border:1px solid var(--accent);background:var(--accent);color:#fff;cursor:pointer;font-size:11px;font-weight:700;">۱</button>
          <button style="width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s2);color:var(--text2);cursor:pointer;font-size:11px;">۲</button>
          <button style="width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s2);color:var(--text2);cursor:pointer;font-size:11px;"><i class="fa-solid fa-chevron-left"></i></button>
        </div>
      </div>

      <!-- Toast notification -->
      <div id="pl-toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--s2);border:1px solid var(--green);border-radius:10px;padding:10px 18px;font-size:12px;font-weight:600;color:var(--green);z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2);align-items:center;gap:8px;">
        <i class="fa-solid fa-check-circle"></i> <span id="pl-toast-msg"></span>
      </div>

    </div>
    <!-- END PRODUCTS LIST PAGE -->
