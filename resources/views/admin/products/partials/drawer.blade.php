{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت: Drawer پیش‌نمایش محصول (سمت راست) + اسکریپت تعاملی صفحه لیست محصولات
  به‌جای رفتن به صفحه‌ی جدید، با کلیک روی محصول این پنل باز می‌شود.
  فیلدهای «آخرین اجرا / تعداد اجرا / تعداد کاربران» داده‌ی واقعی ندارند و
  با بج «نیاز به بررسی برنامه» مشخص شده‌اند.
  ══════════════════════════════════════════════════════════════════
--}}

<div class="drawer-overlay" id="drawer-overlay" onclick="closeDrawer()"></div>
<div class="drawer-panel" id="drawer-panel">

  <div class="drawer-section" style="position:sticky;top:0;background:var(--card-bg);z-index:5;display:flex;align-items:center;justify-content:space-between;">
    <div class="flex items-center gap-3">
      <div class="table-thumb" id="dw-thumb" style="width:46px;height:46px;">
        <i class="fa-solid fa-image"></i>
      </div>
      <div>
        <div id="dw-name" class="text-[14px] font-bold" style="color:var(--text-h);">—</div>
        <div id="dw-slug" class="text-[10.5px] font-mono" style="color:var(--text-soft);" dir="ltr">—</div>
      </div>
    </div>
    <button onclick="closeDrawer()" class="icon-action-btn"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="drawer-section">
    <img id="dw-cover" src="" alt="" style="width:100%;height:150px;object-fit:cover;border-radius:12px;border:1px solid var(--border);display:none;">
    <div class="flex items-center gap-2 flex-wrap mt-3">
      <span id="dw-status" class="badge-pro"></span>
      <span id="dw-category" class="badge-pro badge-primary"></span>
      <span id="dw-subcategory" class="badge-pro badge-neutral"></span>
    </div>
  </div>

  <div class="drawer-section grid grid-cols-2 gap-3">
    <div>
      <div class="drawer-label">مدل هوش مصنوعی</div>
      <div class="drawer-value" id="dw-model" style="font-family:monospace;">—</div>
    </div>
    <div>
      <div class="drawer-label">هزینه (Cost)</div>
      <div class="drawer-value" id="dw-cost">—</div>
    </div>
    <div>
      <div class="drawer-label">تاریخ ایجاد</div>
      <div class="drawer-value" id="dw-created">—</div>
    </div>
    <div>
      <div class="drawer-label">تاریخ ویرایش</div>
      <div class="drawer-value" id="dw-updated">—</div>
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-2">تگ‌ها</div>
    <div class="flex items-center gap-1.5 flex-wrap" id="dw-tags"><span style="color:var(--text-soft);font-size:12px;">—</span></div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-2">Prompt Template</div>
    <div id="dw-prompt" class="text-[11.5px] leading-relaxed" style="background:var(--input-bg);border:1px solid var(--border);border-radius:10px;padding:10px 12px;font-family:monospace;color:var(--text-main);" dir="ltr">—</div>
  </div>

  <div class="drawer-section">
    <div class="flex items-center gap-1.5 mb-2">
      <div class="drawer-label" style="margin-bottom:0;">آمار استفاده</div>
      <span class="pending-badge" style="position:static;">نیاز به بررسی برنامه</span>
    </div>
    <div class="grid grid-cols-3 gap-2 text-center">
      <div style="background:var(--input-bg);border:1px solid var(--border);border-radius:10px;padding:10px 6px;">
        <div class="text-[15px] font-extrabold" style="color:var(--text-soft);">—</div>
        <div class="text-[10px] mt-1" style="color:var(--text-soft);">تعداد اجرا</div>
      </div>
      <div style="background:var(--input-bg);border:1px solid var(--border);border-radius:10px;padding:10px 6px;">
        <div class="text-[15px] font-extrabold" style="color:var(--text-soft);">—</div>
        <div class="text-[10px] mt-1" style="color:var(--text-soft);">تعداد کاربران</div>
      </div>
      <div style="background:var(--input-bg);border:1px solid var(--border);border-radius:10px;padding:10px 6px;">
        <div class="text-[15px] font-extrabold" style="color:var(--text-soft);">—</div>
        <div class="text-[10px] mt-1" style="color:var(--text-soft);">آخرین اجرا</div>
      </div>
    </div>
  </div>

  <div class="drawer-section" style="position:sticky;bottom:0;background:var(--card-bg);display:flex;gap:8px;">
    <a id="dw-edit-link" href="#" class="btn-pro btn-pro-primary" style="flex:1;justify-content:center;">
      <i class="fa-solid fa-pen text-[11px]"></i> ویرایش محصول
    </a>
    <button onclick="closeDrawer()" class="btn-pro btn-pro-ghost">بستن</button>
  </div>

</div>

<script>
  /* داده‌ی محصولات صفحه‌ی جاری، برای پر کردن Drawer پیش‌نمایش بدون رفت‌وبرگشت به سرور */
  const productsData = {
    @foreach(($products ?? []) as $product)
    {{ $product->id }}: {
      name: @json($product->name_fa),
      slug: @json($product->slug),
      thumbnail: @json($product->thumbnail ? asset('storage/'.$product->thumbnail) : null),
      cover: @json($product->cover ? asset('storage/'.$product->cover) : null),
      status: @json($product->status),
      category: @json($product->category),
      subcategory: @json($product->subcategory),
      primaryModel: @json($product->primary_model ?? '—'),
      pricingModel: @json($product->pricing_model),
      creditCost: @json($product->credit_cost),
      tags: @json($product->tags ?? []),
      promptTemplate: @json($product->prompt_template ?? '—'),
      createdAt: @json($product->created_at?->format('Y/m/d H:i')),
      updatedAt: @json($product->updated_at?->format('Y/m/d H:i')),
      editUrl: @json(route('admin.products.create', $product->id)),
    },
    @endforeach
  };

  const dwStatusMap = {
    active:   { label: 'فعال',      cls: 'badge-success' },
    draft:    { label: 'پیش‌نویس',  cls: 'badge-warning' },
    inactive: { label: 'غیرفعال',   cls: 'badge-danger' },
  };

  function openDrawer(id) {
    const p = productsData[id];
    if (!p) return;

    document.getElementById('dw-name').textContent = p.name;
    document.getElementById('dw-slug').textContent = p.slug;

    const thumbEl = document.getElementById('dw-thumb');
    thumbEl.innerHTML = p.thumbnail ? `<img src="${p.thumbnail}">` : `<i class="fa-solid fa-image"></i>`;

    const coverEl = document.getElementById('dw-cover');
    if (p.cover) { coverEl.src = p.cover; coverEl.style.display = ''; } else { coverEl.style.display = 'none'; }

    const st = dwStatusMap[p.status] || dwStatusMap.draft;
    const statusEl = document.getElementById('dw-status');
    statusEl.className = 'badge-pro ' + st.cls;
    statusEl.innerHTML = `<i class="fa-solid fa-circle"></i> ${st.label}`;

    document.getElementById('dw-category').textContent = p.category || '—';
    const subEl = document.getElementById('dw-subcategory');
    if (p.subcategory) { subEl.textContent = p.subcategory; subEl.style.display = ''; } else { subEl.style.display = 'none'; }

    document.getElementById('dw-model').textContent = p.primaryModel;
    document.getElementById('dw-cost').textContent = p.pricingModel === 'free' ? 'رایگان' : `${p.creditCost ?? 0} کردیت`;
    document.getElementById('dw-created').textContent = p.createdAt || '—';
    document.getElementById('dw-updated').textContent = p.updatedAt || '—';

    const tagsEl = document.getElementById('dw-tags');
    tagsEl.innerHTML = (p.tags && p.tags.length)
      ? p.tags.map(t => `<span class="badge-pro badge-neutral" style="padding:3px 8px;font-size:10px;">${t}</span>`).join('')
      : '<span style="color:var(--text-soft);font-size:12px;">بدون تگ</span>';

    document.getElementById('dw-prompt').textContent = p.promptTemplate;
    document.getElementById('dw-edit-link').href = p.editUrl;

    document.getElementById('drawer-overlay').classList.add('open');
    document.getElementById('drawer-panel').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    document.getElementById('drawer-overlay').classList.remove('open');
    document.getElementById('drawer-panel').classList.remove('open');
    document.body.style.overflow = '';
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeDrawer(); closeAllDropdowns(); }
  });

  /* ─── دراپ‌داون اکشن‌های هر ردیف ─── */
  function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-pro-menu.open').forEach(m => m.classList.remove('open'));
  }
  function toggleRowDropdown(event, id) {
    event.stopPropagation();
    const menu = document.getElementById('row-dropdown-' + id);
    const wasOpen = menu.classList.contains('open');
    closeAllDropdowns();
    if (!wasOpen) {
      // چون جدول داخل یک والد overflow-x-auto است، موقعیت دراپ‌داون با
      // position:fixed و مختصات دقیق دکمه محاسبه می‌شود تا کلیپ نشود.
      const btn = event.currentTarget;
      const rect = btn.getBoundingClientRect();
      menu.classList.add('open');
      const menuHeight = menu.offsetHeight;
      const spaceBelow = window.innerHeight - rect.bottom;
      const top = spaceBelow < menuHeight + 12 ? rect.top - menuHeight - 6 : rect.bottom + 6;
      menu.style.top = Math.max(8, top) + 'px';
      menu.style.left = Math.max(8, rect.left - 165 + rect.width) + 'px';
    }
  }
  document.addEventListener('click', closeAllDropdowns);
  window.addEventListener('scroll', closeAllDropdowns, true);

  /* ─── تغییر سریع وضعیت (فعال ⇄ غیرفعال) بدون ورود به صفحه ویرایش ─── */
  function quickToggleStatus(id, badgeEl) {
    if (!confirm('وضعیت این محصول تغییر کند؟')) return;
    fetch(`/admin/products/${id}/toggle-status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
    })
      .then(r => r.json())
      .then(() => window.location.reload())
      .catch(() => alert('خطا در تغییر وضعیت. دوباره تلاش کنید.'));
  }

  /* ─── انتخاب چندگانه ردیف‌ها + نوار عملیات گروهی ─── */
  function toggleSelectAll(cb) {
    document.querySelectorAll('.bulk-check').forEach(c => c.checked = cb.checked);
    onRowCheck();
  }
  function onRowCheck() {
    const checked = document.querySelectorAll('.bulk-check:checked');
    const toolbar = document.getElementById('bulk-toolbar');
    document.getElementById('bulk-count').textContent = checked.length;
    toolbar.style.display = checked.length > 0 ? 'flex' : 'none';
  }
  function submitBulk(action) {
    const checked = [...document.querySelectorAll('.bulk-check:checked')].map(c => c.value);
    if (!checked.length) return;
    if (action === 'delete' && !confirm(`${checked.length} محصول انتخاب‌شده حذف شود؟`)) return;

    const form = document.getElementById('bulk-action-form');
    form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
    checked.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
      form.appendChild(input);
    });
    document.getElementById('bulk-action-input').value = action;
    form.submit();
  }
  function submitBulkCategory(category) {
    if (!category) return;
    const checked = [...document.querySelectorAll('.bulk-check:checked')];
    if (!checked.length) return;
    const form = document.getElementById('bulk-action-form');
    form.querySelectorAll('input[name="ids[]"], input[name="category"]').forEach(el => el.remove());
    checked.forEach(c => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = c.value;
      form.appendChild(input);
    });
    const catInput = document.createElement('input');
    catInput.type = 'hidden'; catInput.name = 'category'; catInput.value = category;
    form.appendChild(catInput);
    document.getElementById('bulk-action-input').value = 'change_category';
    form.submit();
  }
</script>
