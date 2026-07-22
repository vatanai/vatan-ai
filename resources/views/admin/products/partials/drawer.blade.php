{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت: پاپ‌آپ جزئیات محصول (وسطِ کانتینر محتوای داشبورد)
  + اسکریپت‌های تعاملی صفحه لیست محصولات (دراپ‌داون، عملیات گروهی و…)
  ──────────────────────────────────────────────────────────────────
  جایگزین Drawer سمت راست قبلی: با کلیک روی هر محصول، یک پاپ‌آپ دقیقاً
  وسط ناحیه‌ی محتوا (سمت چپ سایدبار) باز می‌شود، پس‌زمینه‌ی همان ناحیه
  تار/کم‌نور می‌شود و «تمام» اطلاعات محصول برای مدیر نمایش داده می‌شود:
  مشخصات پایه، قیمت‌گذاری، هوش مصنوعی، خروجی، پرامپت، توضیحات، سئو،
  نشان‌ها/تگ‌ها، تاریخ‌های شمسی ثبت/ویرایش و آمار واقعی استفاده
  (تعداد اجرا، کاربران یکتا، آخرین اجرا — از جدول generations).
  استایل‌ها در design-tokens.css با پیشوند product-modal / pm- تعریف شده‌اند.
  ══════════════════════════════════════════════════════════════════
--}}

<div class="product-modal-overlay" id="product-modal-overlay" onclick="if(event.target === this) closeDrawer()">
  <div class="product-modal" id="product-modal" role="dialog" aria-modal="true" aria-labelledby="pm-name" dir="rtl">

    {{-- ─── هدر چسبان ─── --}}
    <div class="pm-section" style="position:sticky;top:0;background:var(--card-bg);z-index:5;display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div class="flex items-center gap-3 min-w-0">
        <div class="table-thumb" id="pm-thumb" style="width:48px;height:48px;border-radius:12px;">
          <i class="fa-solid fa-image"></i>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <div id="pm-name" class="text-[14.5px] font-extrabold truncate" style="color:var(--text-h);">—</div>
            <span id="pm-status" class="badge-pro"></span>
          </div>
          <div class="flex items-center gap-2 flex-wrap mt-0.5">
            <span id="pm-slug" class="text-[10.5px] font-mono truncate" style="color:var(--text-soft);" dir="ltr">—</span>
            <span class="badge-pro badge-neutral" style="padding:2px 7px;font-size:9.5px;font-family:monospace;" dir="ltr">کد: <span id="pm-code">—</span></span>
          </div>
        </div>
      </div>
      <button onclick="closeDrawer()" class="icon-action-btn" title="بستن" aria-label="بستن پاپ‌آپ"><i class="fa-solid fa-xmark"></i></button>
    </div>

    {{-- ─── کاور ─── --}}
    <div class="pm-section" id="pm-cover-wrap" style="display:none;">
      <img id="pm-cover" src="" alt="" style="width:100%;height:170px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
    </div>

    {{-- ─── آمار واقعی استفاده ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-chart-simple"></i> آمار استفاده (داده واقعی)</div>
      <div class="grid grid-cols-3 gap-2 max-[640px]:grid-cols-1">
        <div class="pm-stat">
          <div class="text-[17px] font-extrabold" style="color:var(--text-h);" id="pm-runs">۰</div>
          <div class="text-[10px] mt-1" style="color:var(--text-soft);">تعداد اجرا</div>
        </div>
        <div class="pm-stat">
          <div class="text-[17px] font-extrabold" style="color:var(--text-h);" id="pm-users">۰</div>
          <div class="text-[10px] mt-1" style="color:var(--text-soft);">کاربران یکتا</div>
        </div>
        <div class="pm-stat">
          <div class="text-[13px] font-extrabold" style="color:var(--text-h);" id="pm-lastrun">—</div>
          <div class="text-[10px] mt-1" style="color:var(--text-soft);">آخرین اجرا</div>
        </div>
      </div>
    </div>

    {{-- ─── اطلاعات پایه ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-fingerprint"></i> اطلاعات پایه</div>
      <div class="pm-grid" id="pm-basic"></div>
    </div>

    {{-- ─── قیمت‌گذاری ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-coins"></i> قیمت‌گذاری</div>
      <div class="pm-grid" id="pm-pricing"></div>
    </div>

    {{-- ─── هوش مصنوعی ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-microchip"></i> هوش مصنوعی</div>
      <div class="pm-grid" id="pm-ai"></div>
      <div class="mt-3">
        <div class="pm-label">Prompt Template</div>
        <div class="pm-mono" id="pm-prompt">—</div>
      </div>
      <div class="mt-2.5" id="pm-negative-wrap" style="display:none;">
        <div class="pm-label">Negative Prompt</div>
        <div class="pm-mono" id="pm-negative"></div>
      </div>
    </div>

    {{-- ─── خروجی و رسانه ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-image"></i> خروجی و رسانه</div>
      <div class="pm-grid" id="pm-output"></div>
    </div>

    {{-- ─── توضیحات ─── --}}
    <div class="pm-section" id="pm-desc-section">
      <div class="pm-title"><i class="fa-solid fa-align-right"></i> توضیحات</div>
      <div class="pm-label">توضیح فارسی</div>
      <div class="text-[12px] leading-relaxed mb-2.5" style="color:var(--text-main);" id="pm-desc-fa">—</div>
      <div id="pm-desc-en-wrap" style="display:none;">
        <div class="pm-label">توضیح انگلیسی</div>
        <div class="text-[12px] leading-relaxed" style="color:var(--text-main);" id="pm-desc-en" dir="ltr"></div>
      </div>
    </div>

    {{-- ─── سئو ─── --}}
    <div class="pm-section" id="pm-seo-section" style="display:none;">
      <div class="pm-title"><i class="fa-solid fa-magnifying-glass-chart"></i> سئو</div>
      <div class="pm-grid" id="pm-seo"></div>
    </div>

    {{-- ─── نشان‌ها و تگ‌ها ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-tags"></i> نشان‌ها و تگ‌ها</div>
      <div class="flex items-center gap-1.5 flex-wrap mb-2" id="pm-flags"></div>
      <div class="flex items-center gap-1.5 flex-wrap" id="pm-tags"></div>
    </div>

    {{-- ─── تاریخ‌ها ─── --}}
    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-calendar-days"></i> تاریخ‌ها (شمسی)</div>
      <div class="pm-grid">
        <div>
          <div class="pm-label">تاریخ و ساعت ثبت</div>
          <div class="pm-value" id="pm-created">—</div>
        </div>
        <div>
          <div class="pm-label">تاریخ و ساعت آخرین ویرایش</div>
          <div class="pm-value" id="pm-updated">—</div>
        </div>
      </div>
    </div>

    {{-- ─── یادداشت مدیر ─── --}}
    <div class="pm-section" id="pm-note-section" style="display:none;">
      <div class="pm-title"><i class="fa-solid fa-note-sticky"></i> یادداشت داخلی مدیر</div>
      <div class="text-[12px] leading-relaxed" style="color:var(--text-main);" id="pm-note"></div>
    </div>

    {{-- ─── فوتر چسبان ─── --}}
    <div class="pm-section" style="position:sticky;bottom:0;background:var(--card-bg);display:flex;gap:8px;border-bottom:none;z-index:5;">
      <a id="pm-edit-link" href="#" class="btn-pro btn-pro-primary" style="flex:1;justify-content:center;">
        <i class="fa-solid fa-pen text-[11px]"></i> ویرایش محصول
      </a>
      <button onclick="closeDrawer()" class="btn-pro btn-pro-ghost">بستن</button>
    </div>

  </div>
</div>

<script>
  /* داده‌ی کامل محصولات صفحه‌ی جاری برای پاپ‌آپ جزئیات — بدون رفت‌وبرگشت اضافه به سرور.
     آمار اجرا (runs / uniqueUsers / lastRun) و تاریخ‌های شمسی در کنترلر محاسبه شده‌اند. */
  const productsData = {
    @foreach(($products ?? []) as $product)
    {{ $product->id }}: {
      name: @json($product->name_fa),
      nameEn: @json($product->name_en),
      slug: @json($product->slug),
      productCode: @json($product->product_code),
      thumbnail: @json($product->displayImageUrl()),
      cover: @json($product->cover ? asset('storage/'.$product->cover) : null),
      status: @json($product->status),
      category: @json($product->category),
      categories: @json($product->categories->pluck('name')->all()),
      subcategory: @json($product->subcategory),
      mediaType: @json($product->media_type),
      platform: @json($product->platform),
      displayOrder: @json($product->new_display_order),
      internalCode: @json($product->new_internal_code),
      cardLabel: @json($product->card_label),
      pricingModel: @json($product->pricing_model),
      creditCost: @json($product->credit_cost),
      discount: @json($product->discount_percentage),
      priceTier: @json($product->price_tier),
      minCredit: @json($product->new_min_credit_required),
      maxRunPerUser: @json($product->new_max_run_per_user),
      customPriceLabel: @json($product->new_price_custom_label),
      primaryModel: @json($product->primary_model),
      fallbackModels: @json(is_array($product->fallback_models) ? array_values(array_filter($product->fallback_models)) : []),
      pipelineType: @json($product->pipeline_type),
      timeout: @json($product->timeout),
      subjectType: @json($product->subject_type),
      identityPreservation: @json((bool) $product->identity_preservation),
      promptTemplate: @json($product->prompt_template),
      negativePrompt: @json($product->negative_prompt),
      outputType: @json($product->output_type),
      outputFormat: @json($product->output_format),
      outputCount: @json($product->output_count),
      resolution: @json($product->resolution),
      aspectRatio: @json($product->aspect_ratio),
      estimatedTime: @json($product->estimated_time),
      deliveryMethod: @json($product->delivery_method),
      watermarkEnabled: @json((bool) $product->watermark_enabled),
      descriptionFa: @json($product->description_fa),
      descriptionEn: @json($product->description_en),
      metaTitle: @json($product->meta_title),
      metaDescription: @json($product->meta_description),
      metaKeywords: @json($product->meta_keywords),
      tags: @json($product->tags ?? []),
      isFeatured: @json((bool) $product->is_featured),
      isNew: @json((bool) $product->is_new),
      isTrending: @json((bool) $product->is_trending),
      isPremium: @json((bool) $product->new_is_premium),
      isRecommended: @json((bool) $product->new_is_recommended),
      isBeta: @json((bool) $product->new_is_beta),
      adminNote: @json($product->new_admin_note),
      runs: {{ (int) ($product->generations_count ?? 0) }},
      uniqueUsers: {{ (int) ($product->unique_users_count ?? 0) }},
      lastRun: @json($product->last_run_at ? \App\Support\Jalali::formatNumeric(\Illuminate\Support\Carbon::parse($product->last_run_at)) : null),
      createdAt: @json(\App\Support\Jalali::formatNumeric($product->created_at)),
      updatedAt: @json(\App\Support\Jalali::formatNumeric($product->updated_at)),
      editUrl: @json(route('admin.products.create', $product->id)),
    },
    @endforeach
  };

  const dwStatusMap = {
    active:   { label: 'فعال',      cls: 'badge-success' },
    draft:    { label: 'پیش‌نویس',  cls: 'badge-warning' },
    inactive: { label: 'غیرفعال',   cls: 'badge-danger' },
  };

  const pmPricingMap = { free: 'رایگان', per_credit: 'کردیتی', subscription: 'اشتراکی' };
  const pmMediaMap   = { photo: 'عکس', video: 'ویدیو', both: 'عکس + ویدیو' };

  function pmEsc(v) {
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pmFa(n) {
    return String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  }
  function pmVal(v) {
    return (v === null || v === undefined || v === '') ? '—' : pmEsc(v);
  }
  function pmItem(label, value, mono) {
    return '<div><div class="pm-label">' + label + '</div><div class="pm-value"' + (mono ? ' style="font-family:monospace;" dir="ltr"' : '') + '>' + value + '</div></div>';
  }

  /* نام تابع openDrawer برای سازگاری با onclick های جدول حفظ شده — اما حالا پاپ‌آپ مرکزی باز می‌کند */
  function openDrawer(id) {
    const p = productsData[id];
    if (!p) return;

    // ── هدر ──
    document.getElementById('pm-name').textContent = p.name || '—';
    document.getElementById('pm-slug').textContent = (p.nameEn ? p.nameEn + ' · ' : '') + (p.slug || '');
    document.getElementById('pm-code').textContent = p.productCode || '—';
    document.getElementById('pm-thumb').innerHTML = p.thumbnail ? `<img src="${p.thumbnail}">` : `<i class="fa-solid fa-image"></i>`;

    const st = dwStatusMap[p.status] || dwStatusMap.draft;
    const statusEl = document.getElementById('pm-status');
    statusEl.className = 'badge-pro ' + st.cls;
    statusEl.innerHTML = `<i class="fa-solid fa-circle"></i> ${st.label}`;

    // ── کاور ──
    const coverWrap = document.getElementById('pm-cover-wrap');
    if (p.cover) { document.getElementById('pm-cover').src = p.cover; coverWrap.style.display = ''; }
    else { coverWrap.style.display = 'none'; }

    // ── آمار واقعی استفاده ──
    document.getElementById('pm-runs').textContent = pmFa(p.runs || 0);
    document.getElementById('pm-users').textContent = pmFa(p.uniqueUsers || 0);
    document.getElementById('pm-lastrun').textContent = p.lastRun || 'هنوز اجرا نشده';

    // ── اطلاعات پایه ──
    const cats = (p.categories && p.categories.length) ? p.categories.join('، ') : (p.category || null);
    document.getElementById('pm-basic').innerHTML =
      pmItem('کد محصول (۶ رقمی)', pmVal(p.productCode), true) +
      pmItem('دسته‌بندی‌ها', pmVal(cats)) +
      pmItem('زیردسته', pmVal(p.subcategory)) +
      pmItem('نوع رسانه', pmVal(pmMediaMap[p.mediaType] || p.mediaType)) +
      pmItem('پلتفرم نمایش', pmVal(p.platform)) +
      pmItem('ترتیب نمایش', pmVal(p.displayOrder)) +
      pmItem('کد داخلی', pmVal(p.internalCode), true) +
      pmItem('برچسب کارت', pmVal(p.cardLabel));

    // ── قیمت‌گذاری ──
    document.getElementById('pm-pricing').innerHTML =
      pmItem('مدل قیمت‌گذاری', pmVal(pmPricingMap[p.pricingModel] || p.pricingModel)) +
      pmItem('هزینه هر اجرا', p.pricingModel === 'free' ? 'رایگان' : pmVal(p.creditCost !== null ? pmFa(p.creditCost) + ' کردیت' : null)) +
      pmItem('درصد تخفیف', pmVal(p.discount !== null && p.discount !== undefined ? pmFa(p.discount) + '٪' : null)) +
      pmItem('رده قیمتی', pmVal(p.priceTier)) +
      pmItem('حداقل کردیت لازم', pmVal(p.minCredit !== null && p.minCredit !== undefined ? pmFa(p.minCredit) : null)) +
      pmItem('سقف اجرا برای هر کاربر', p.maxRunPerUser ? pmFa(p.maxRunPerUser) : 'نامحدود') +
      pmItem('برچسب قیمت سفارشی', pmVal(p.customPriceLabel));

    // ── هوش مصنوعی ──
    document.getElementById('pm-ai').innerHTML =
      pmItem('مدل اصلی', pmVal(p.primaryModel), true) +
      pmItem('مدل‌های جایگزین', (p.fallbackModels && p.fallbackModels.length) ? pmEsc(p.fallbackModels.join(' ، ')) : '—') +
      pmItem('نوع پایپ‌لاین', pmVal(p.pipelineType), true) +
      pmItem('تایم‌اوت', pmVal(p.timeout !== null ? pmFa(p.timeout) + ' ثانیه' : null)) +
      pmItem('نوع سوژه', pmVal(p.subjectType)) +
      pmItem('حفظ هویت چهره', p.identityPreservation ? 'فعال' : 'غیرفعال');
    document.getElementById('pm-prompt').textContent = p.promptTemplate || '—';
    const negWrap = document.getElementById('pm-negative-wrap');
    if (p.negativePrompt) { document.getElementById('pm-negative').textContent = p.negativePrompt; negWrap.style.display = ''; }
    else { negWrap.style.display = 'none'; }

    // ── خروجی و رسانه ──
    document.getElementById('pm-output').innerHTML =
      pmItem('نوع خروجی', pmVal(p.outputType)) +
      pmItem('فرمت خروجی', pmVal(p.outputFormat), true) +
      pmItem('تعداد خروجی هر اجرا', pmVal(p.outputCount !== null ? pmFa(p.outputCount) : null)) +
      pmItem('رزولوشن', pmVal(p.resolution), true) +
      pmItem('نسبت تصویر', pmVal(p.aspectRatio), true) +
      pmItem('زمان تخمینی', pmVal(p.estimatedTime !== null ? pmFa(p.estimatedTime) + ' ثانیه' : null)) +
      pmItem('روش تحویل', pmVal(p.deliveryMethod)) +
      pmItem('واترمارک', p.watermarkEnabled ? 'فعال' : 'غیرفعال');

    // ── توضیحات ──
    document.getElementById('pm-desc-fa').textContent = p.descriptionFa || '—';
    const descEnWrap = document.getElementById('pm-desc-en-wrap');
    if (p.descriptionEn) { document.getElementById('pm-desc-en').textContent = p.descriptionEn; descEnWrap.style.display = ''; }
    else { descEnWrap.style.display = 'none'; }

    // ── سئو (فقط اگر چیزی ثبت شده باشد) ──
    const seoSection = document.getElementById('pm-seo-section');
    if (p.metaTitle || p.metaDescription || p.metaKeywords) {
      document.getElementById('pm-seo').innerHTML =
        pmItem('Meta Title', pmVal(p.metaTitle)) +
        pmItem('Meta Keywords', pmVal(p.metaKeywords)) +
        '<div style="grid-column:1/-1;"><div class="pm-label">Meta Description</div><div class="pm-value">' + pmVal(p.metaDescription) + '</div></div>';
      seoSection.style.display = '';
    } else {
      seoSection.style.display = 'none';
    }

    // ── نشان‌ها و تگ‌ها ──
    const flags = [];
    if (p.isFeatured)    flags.push('<span class="badge-pro badge-warning"><i class="fa-solid fa-star"></i> ویژه</span>');
    if (p.isNew)         flags.push('<span class="badge-pro badge-info"><i class="fa-solid fa-sparkles"></i> جدید</span>');
    if (p.isTrending)    flags.push('<span class="badge-pro badge-danger"><i class="fa-solid fa-fire"></i> ترند</span>');
    if (p.isPremium)     flags.push('<span class="badge-pro badge-primary"><i class="fa-solid fa-crown"></i> پرمیوم</span>');
    if (p.isRecommended) flags.push('<span class="badge-pro badge-success"><i class="fa-solid fa-thumbs-up"></i> پیشنهادی</span>');
    if (p.isBeta)        flags.push('<span class="badge-pro badge-neutral"><i class="fa-solid fa-flask"></i> بتا</span>');
    document.getElementById('pm-flags').innerHTML = flags.length ? flags.join('') : '<span style="color:var(--text-soft);font-size:11.5px;">بدون نشان خاص</span>';

    document.getElementById('pm-tags').innerHTML = (p.tags && p.tags.length)
      ? p.tags.map(t => `<span class="badge-pro badge-neutral" style="padding:3px 8px;font-size:10px;">${pmEsc(t)}</span>`).join('')
      : '<span style="color:var(--text-soft);font-size:11.5px;">بدون تگ</span>';

    // ── تاریخ‌ها ──
    document.getElementById('pm-created').textContent = p.createdAt || '—';
    document.getElementById('pm-updated').textContent = p.updatedAt || '—';

    // ── یادداشت مدیر ──
    const noteSection = document.getElementById('pm-note-section');
    if (p.adminNote) { document.getElementById('pm-note').textContent = p.adminNote; noteSection.style.display = ''; }
    else { noteSection.style.display = 'none'; }

    document.getElementById('pm-edit-link').href = p.editUrl;

    const overlay = document.getElementById('product-modal-overlay');
    overlay.classList.add('open');
    document.getElementById('product-modal').scrollTop = 0;
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    document.getElementById('product-modal-overlay').classList.remove('open');
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
