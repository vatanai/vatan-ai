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

    <div class="pm-section">
      <div class="pm-title"><i class="fa-solid fa-layer-group"></i> نتیجه‌ی آزمایش سه گرید</div>
      <div class="pm-grid" id="pm-lab-grades"></div>
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
      qualityCreditCosts: @json($product->qualityCreditCosts()),
      discount: @json($product->discount_percentage),
      priceTier: @json($product->price_tier),
      minCredit: @json($product->new_min_credit_required),
      maxRunPerUser: @json($product->new_max_run_per_user),
      customPriceLabel: @json($product->new_price_custom_label),
      primaryModel: @json($product->primary_model),
      fallbackModels: @json(is_array($product->fallback_models) ? array_values(array_filter($product->fallback_models)) : []),
      labGradeConfig: @json($product->lab_grade_config ?? []),
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
      editUrl: @json($product->isVideoProduct() ? route('admin.products.video.create', $product) : route('admin.products.create', $product)),
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
      pmItem('مصرف اعتبار سه سطح', p.pricingModel === 'free' ? 'رایگان' : pmVal(p.qualityCreditCosts ? 'استاندارد ' + pmFa(p.qualityCreditCosts.standard) + ' · حرفه‌ای ' + pmFa(p.qualityCreditCosts.professional) + ' · بهترین ' + pmFa(p.qualityCreditCosts.best) + ' کردیت' : null)) +
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
    const gradeLabels = {economic: 'استاندارد', standard: 'استاندارد', professional: 'حرفه‌ای', best: 'بهترین خروجی'};
    const gradeEntries = Object.entries(p.labGradeConfig || {});
    document.getElementById('pm-lab-grades').innerHTML = gradeEntries.length
      ? gradeEntries.map(([key, grade]) => pmItem(grade.label || gradeLabels[key] || key, `${pmEsc(grade.primary?.name || grade.primary?.model_id || '—')} · ${pmEsc(String(grade.primary?.score || '—'))} از ۵`)).join('')
      : pmItem('وضعیت', 'هنوز نتیجه‌ای روی محصول اعمال نشده است.');
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
  function showProductNotice(message, kind) {
    const old = document.getElementById('product-action-notice');
    if (old) old.remove();
    const notice = document.createElement('div');
    notice.id = 'product-action-notice';
    notice.className = 'admin-toast fixed left-5 bottom-5 z-[150] px-4 py-3 rounded-xl text-[12px]';
    const color = kind === 'error' ? 'var(--danger)' : (kind === 'warning' ? 'var(--warning)' : 'var(--success)');
    const icon = kind === 'error' ? 'fa-triangle-exclamation' : (kind === 'warning' ? 'fa-circle-info' : 'fa-circle-check');
    notice.style.cssText += 'background:var(--card-bg);color:' + color + ';border:1px solid ' + color + ';';
    notice.innerHTML = '<span class="admin-toast-icon"><i class="fa-solid ' + icon + '"></i></span><span>' + message + '</span>';
    document.body.appendChild(notice);
    setTimeout(function () { notice.remove(); }, 3500);
  }

  function confirmProductStatusChange() {
    return new Promise(function (resolve) {
      const overlay = document.createElement('div');
      overlay.className = 'fixed inset-0 z-[160] flex items-center justify-center p-4';
      overlay.style.background = 'color-mix(in srgb, var(--text-h) 55%, transparent)';
      overlay.innerHTML = '<div class="w-full max-w-sm rounded-2xl p-5 text-right" style="background:var(--card-bg);border:1px solid var(--border);box-shadow:var(--shadow-card);"><div class="flex items-center gap-3 mb-4"><span class="admin-toast-icon" style="background:var(--warning-l);color:var(--warning);"><i class="fa-solid fa-toggle-on"></i></span><div><div class="font-bold text-[13px]" style="color:var(--text-h);">تغییر وضعیت محصول</div><div class="text-[11px] mt-1" style="color:var(--text-soft);">وضعیت این محصول تغییر کند؟</div></div></div><div class="flex gap-2 justify-end"><button type="button" data-answer="0" class="btn-pro btn-pro-ghost">انصراف</button><button type="button" data-answer="1" class="btn-pro btn-pro-primary">تأیید تغییر</button></div></div>';
      overlay.addEventListener('click', function (event) {
        const answer = event.target.closest('[data-answer]');
        if (!answer && event.target !== overlay) return;
        const accepted = answer?.dataset.answer === '1';
        overlay.remove();
        resolve(accepted);
      });
      document.body.appendChild(overlay);
    });
  }

  async function quickToggleStatus(id, badgeEl) {
    if (!(await confirmProductStatusChange())) return;
    fetch(`/admin/products/${id}/toggle-status`, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
    })
      .then(r => r.json())
      .then(() => {
        showProductNotice('وضعیت محصول با موفقیت تغییر کرد.', 'success');
        setTimeout(() => window.location.reload(), 600);
      })
      .catch(() => showProductNotice('خطا در تغییر وضعیت. دوباره تلاش کنید.', 'error'));
  }

  /* ─── تغییر سریع مدل هوش مصنوعی؛ مستقل از فرم ثبت/ویرایش محصول ─── */
  const productAiDialogState = {
    mode: 'single',
    productId: null,
    productName: '',
    url: '',
    selectedIds: [],
    currentProvider: '',
    currentModel: '',
    saving: false,
    trigger: null,
  };

  function productAiModels() {
    return Array.isArray(window.PRODUCT_ASSIGNABLE_AI_MODELS) ? window.PRODUCT_ASSIGNABLE_AI_MODELS : [];
  }

  function openProductAiModelDialog(button) {
    closeAllDropdowns();
    productAiDialogState.mode = 'single';
    productAiDialogState.productId = String(button.dataset.productId || '');
    productAiDialogState.productName = button.dataset.productName || '';
    productAiDialogState.url = button.dataset.aiUrl || '';
    productAiDialogState.selectedIds = [];
    productAiDialogState.currentProvider = button.dataset.aiProvider || '';
    productAiDialogState.currentModel = button.dataset.aiModel || '';
    productAiDialogState.trigger = button;
    openConfiguredAiModelDialog('مدل هوش مصنوعی محصول', productAiDialogState.productName);
  }

  function openBulkAiModelDialog() {
    const ids = requireBulkSelection();
    if (!ids) return;
    productAiDialogState.mode = 'bulk';
    productAiDialogState.productId = null;
    productAiDialogState.productName = '';
    productAiDialogState.url = window.PRODUCT_BULK_AI_URL || '';
    productAiDialogState.selectedIds = ids;
    productAiDialogState.currentProvider = '';
    productAiDialogState.currentModel = '';
    productAiDialogState.trigger = null;
    openConfiguredAiModelDialog('تغییر گروهی مدل هوش مصنوعی', ids.length.toLocaleString('fa-IR') + ' محصول انتخاب شده');
  }

  /* ─── معماری کیفیت خروجیِ مشترک با گام دوم ثبت محصول ─── */
  const productQualityConfigurationState = {
    mode: 'single', url: '', ids: [], productName: '', configuration: {}, saving: false,
  };
  const productQualitySearchState = {};
  const productQualityCards = [
    { group: 'quality_models', key: 'standard', title: 'مدل استاندارد', description: 'مسیر متعادل برای ساخت روزمره', grade: 3, icon: 'fa-wand-magic-sparkles' },
    { group: 'quality_models', key: 'professional', title: 'مدل حرفه‌ای', description: 'جزئیات و پایداری بیشتر برای خروجی حرفه‌ای', grade: 2, icon: 'fa-gem' },
    { group: 'quality_models', key: 'best', title: 'مدل بهترین خروجی', description: 'بالاترین سطح کیفیت برای نتیجه‌های کلیدی', grade: 1, icon: 'fa-crown' },
  ];
  const productFreeQualityCards = [
    { group: 'free_quality_models', key: 'standard', title: 'مدل استاندارد', description: 'مسیر پیش‌فرض ساخت با اعتبار هدیه', grade: 4, icon: 'fa-gift' },
    { group: 'free_quality_models', key: 'best', title: 'حالت بهترین خروجی', description: 'مدل آماده برای فعال‌شدن دسترسی بهترین خروجی', grade: 1, icon: 'fa-star' },
  ];

  function productQualityPresets() {
    return window.PRODUCT_MODEL_QUALITY_PRESETS || {};
  }

  function cloneProductQualityValue(value) {
    return JSON.parse(JSON.stringify(value || {}));
  }

  function emptyQualityPair() {
    return { primary: { model_id: '', provider: '' }, fallback: { model_id: '', provider: '' } };
  }

  function presetQualityConfiguration(key) {
    return cloneProductQualityValue(productQualityPresets()[key]?.configuration || {});
  }

  function completeProductQualityConfiguration(configuration) {
    const presetKey = configuration?.quality_preset_key || 'preset_1';
    const preset = presetQualityConfiguration(presetKey);
    const result = {
      quality_preset_key: productQualityPresets()[presetKey] ? presetKey : 'preset_1',
      quality_architecture_enabled: configuration?.quality_architecture_enabled !== false,
      quality_models: {},
      free_quality_models: {},
    };
    productQualityCards.concat(productFreeQualityCards).forEach(function (card) {
      const saved = configuration?.[card.group]?.[card.key] || {};
      const fallback = preset?.[card.group]?.[card.key] || emptyQualityPair();
      result[card.group][card.key] = {
        primary: {
          model_id: saved.primary?.model_id || fallback.primary?.model_id || '',
          provider: saved.primary?.provider || fallback.primary?.provider || '',
        },
        fallback: {
          model_id: saved.fallback?.model_id || fallback.fallback?.model_id || '',
          provider: saved.fallback?.provider || fallback.fallback?.provider || '',
        },
      };
    });
    return result;
  }

  function productQualitySelection(card, role) {
    return productQualityConfigurationState.configuration?.[card.group]?.[card.key]?.[role] || {};
  }

  function productQualityModelsFor(card, role, provider) {
    const all = productAiModels();
    const sameGrade = all.filter(function (model) { return Number(model.gradeNumber) === Number(card.grade); });
    const candidates = role === 'primary' && sameGrade.length ? sameGrade : all;
    const primary = productQualitySelection(card, 'primary');
    const primaryProvider = primary.provider || '';
    return candidates.filter(function (model) {
      // مدل جایگزین می‌تواند از همان پرووایدر باشد؛ فقط انتخاب عین مدل اصلی
      // را می‌بندیم تا مسیر جایگزین واقعاً یک مدل متفاوت داشته باشد.
      const isSameModel = role === 'fallback'
        && primaryProvider
        && model.provider === primaryProvider
        && model.id === primary.model_id;
      return !isSameModel && (!provider || model.provider === provider);
    });
  }

  function productQualitySearchKey(card, role) {
    return card.group + ':' + card.key + ':' + role;
  }

  function productQualityProviderOptions(card, role) {
    const selection = productQualitySelection(card, role);
    const primaryProvider = productQualitySelection(card, 'primary').provider || '';
    const providers = [...new Set(productQualityModelsFor(card, role).map(function (model) { return model.provider; }))];
    return '<option value="">انتخاب پرووایدر</option>' + providers.map(function (provider) {
      const selected = provider === selection.provider ? ' selected' : '';
      const label = productAiModels().find(function (model) { return model.provider === provider; })?.providerFa || provider;
      return '<option value="' + pmEsc(provider) + '"' + selected + '>' + pmEsc(label) + '</option>';
    }).join('');
  }

  function productQualityModelOptions(card, role) {
    const selection = productQualitySelection(card, role);
    const provider = selection.provider || '';
    const models = provider ? productQualityModelsFor(card, role, provider) : [];
    const selected = productAiModels().find(function (model) {
      return model.id === selection.model_id && model.provider === selection.provider;
    });
    const items = selected && !models.some(function (model) { return model.id === selected.id && model.provider === selected.provider; })
      ? [selected].concat(models)
      : models;
    return '<option value="">' + (provider ? 'انتخاب مدل' : 'ابتدا پرووایدر را انتخاب کنید') + '</option>' + items.map(function (model) {
      const isSelected = model.id === selection.model_id && model.provider === selection.provider;
      const searchText = [model.id, model.name, model.persianName, model.provider, model.providerFa].filter(Boolean).join(' ').toLowerCase();
      return '<option value="' + pmEsc(model.id) + '" data-provider="' + pmEsc(model.provider) + '" data-search="' + pmEsc(searchText) + '"' + (isSelected ? ' selected' : '') + '>' + pmEsc(model.persianName || model.name || model.id) + ' · ' + pmEsc(model.providerFa || model.provider) + '</option>';
    }).join('');
  }

  function filterProductQualityModelSearch(input) {
    const query = String(input?.value || '').trim().toLowerCase();
    const select = input?.closest('label')?.querySelector('[data-quality-model-search-select]');
    if (!select) return;
    Array.from(select.options).forEach(function (option) {
      if (!option.value) return;
      const haystack = String(option.dataset.search || option.textContent || '').toLowerCase();
      option.hidden = Boolean(query && !haystack.includes(query));
    });
    const key = input.dataset.qualitySearchKey;
    if (key) productQualitySearchState[key] = input.value || '';
  }

  function productQualityCostText(card, role) {
    const selection = productQualitySelection(card, role);
    const model = productAiModels().find(function (item) {
      return item.id === selection.model_id && item.provider === selection.provider;
    });
    if (!model || !Number(model.usd)) return 'هزینه تقریبی هر ساخت: —';
    const toman = Number(model.toman || 0);
    return 'هزینه تقریبی هر ساخت: $' + Number(model.usd).toFixed(3) + (toman ? ' · ' + toman.toLocaleString('fa-IR') + ' تومان' : '');
  }

  function productQualityCardMarkup(card, dashed) {
    const cardClass = dashed ? 'border-dashed' : '';
    const roleMarkup = function (role, title, subtitle) {
      const selection = productQualitySelection(card, role);
      const disabled = selection.provider ? '' : ' disabled';
      const searchKey = productQualitySearchKey(card, role);
      return '<label class="block text-[10px] font-bold" style="color:var(--text-soft);">' + title + (subtitle ? ' <span class="font-normal">' + subtitle + '</span>' : '') +
        '<span class="block mt-2 text-[9px] font-normal" style="color:var(--text-soft);">پرووایدر</span><select class="input-pro w-full mt-1" style="height:36px;" data-quality-group="' + card.group + '" data-quality-key="' + card.key + '" data-quality-role="' + role + '" onchange="updateProductQualityProvider(this)">' + productQualityProviderOptions(card, role) + '</select>' +
        '<span class="block mt-2 text-[9px] font-normal" style="color:var(--text-soft);">مدل</span><input type="search" class="input-pro w-full mt-1" style="height:32px;" data-quality-model-search data-quality-search-key="' + pmEsc(searchKey) + '" value="' + pmEsc(productQualitySearchState[searchKey] || '') + '" placeholder="جستجوی نام یا شناسه مدل..." autocomplete="off" oninput="filterProductQualityModelSearch(this)"><select class="input-pro w-full mt-1" style="height:36px;" data-quality-model-search-select data-quality-group="' + card.group + '" data-quality-key="' + card.key + '" data-quality-role="' + role + '" onchange="updateProductQualitySelection(this)"' + disabled + '>' + productQualityModelOptions(card, role) + '</select><small class="block mt-1 font-normal" style="color:var(--text-soft);">' + productQualityCostText(card, role) + '</small></label>';
    };
    return '<article class="rounded-xl p-3.5 ' + cardClass + '" style="background:var(--input-bg);border:1px ' + (dashed ? 'dashed' : 'solid') + ' var(--border);">' +
      '<div class="flex items-start gap-2.5 mb-3"><span class="w-8 h-8 grid place-items-center rounded-lg shrink-0" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid ' + pmEsc(card.icon) + '"></i></span><div><b class="text-[12px]" style="color:var(--text-h);">' + pmEsc(card.title) + '</b><p class="text-[9.5px] leading-5 mt-0.5" style="color:var(--text-soft);">' + pmEsc(card.description) + '</p></div></div>' +
      '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' + roleMarkup('primary', 'مدل اصلی', '') + roleMarkup('fallback', 'مدل جایگزین', '(مسیر مستقل)') + '</div>' +
      '</article>';
  }

  function renderProductQualityConfigurationDialog() {
    const configuration = productQualityConfigurationState.configuration;
    const presetSelect = document.getElementById('product-quality-preset');
    const presets = productQualityPresets();
    if (presetSelect) {
      const entries = Object.entries(presets);
      presetSelect.disabled = !entries.length;
      presetSelect.innerHTML = entries.length ? entries.map(function ([key, preset]) {
        return '<option value="' + pmEsc(key) + '"' + (configuration.quality_preset_key === key ? ' selected' : '') + '>' + pmEsc(preset.name || key) + '</option>';
      }).join('') : '<option value="">پیش‌فرضی در دیتابیس ثبت نشده است</option>';
    }
    const enabled = configuration.quality_architecture_enabled !== false;
    document.getElementById('product-quality-architecture-content')?.classList.toggle('hidden', !enabled);
    const toggle = document.getElementById('product-quality-architecture-toggle');
    if (toggle) {
      toggle.innerHTML = '<i class="fa-solid ' + (enabled ? 'fa-toggle-on' : 'fa-toggle-off') + ' text-[12px]"></i> <span>' + (enabled ? 'روشن' : 'خاموش') + '</span>';
      toggle.style.color = enabled ? 'var(--primary)' : '';
    }
    const paid = document.getElementById('product-quality-paid-cards');
    const free = document.getElementById('product-quality-free-cards');
    if (paid) paid.innerHTML = productQualityCards.map(function (card) { return productQualityCardMarkup(card, false); }).join('');
    if (free) free.innerHTML = productFreeQualityCards.map(function (card) { return productQualityCardMarkup(card, true); }).join('');
    document.querySelectorAll('[data-quality-model-search]').forEach(function (input) {
      filterProductQualityModelSearch(input);
    });
  }

  function openProductQualityConfigurationDialog(button) {
    closeAllDropdowns();
    let configuration = {};
    try { configuration = JSON.parse(button.dataset.modelConfiguration || '{}'); } catch (_) { configuration = {}; }
    productQualityConfigurationState.mode = 'single';
    productQualityConfigurationState.url = button.dataset.qualityUrl || '';
    productQualityConfigurationState.ids = [];
    productQualityConfigurationState.productName = button.dataset.productName || '';
    productQualityConfigurationState.configuration = completeProductQualityConfiguration(configuration);
    openProductQualityConfigurationDialogWindow('معماری کیفیت خروجی مدل', productQualityConfigurationState.productName);
  }

  function openBulkQualityConfigurationDialog() {
    const ids = typeof getSelectedBulkProductIds === 'function' ? getSelectedBulkProductIds() : [];
    if (!ids.length) {
      showProductNotice('برای اعمال تنظیمات، ابتدا حداقل یک محصول را انتخاب کنید.', 'warning');
      return;
    }
    productQualityConfigurationState.mode = 'bulk';
    productQualityConfigurationState.url = window.PRODUCT_BULK_MODEL_QUALITY_URL || '';
    productQualityConfigurationState.ids = ids;
    productQualityConfigurationState.productName = '';
    const presetKeys = Object.keys(productQualityPresets());
    const defaultPresetKey = window.PRODUCT_MODEL_QUALITY_DEFAULT_KEY || presetKeys[0] || 'preset_1';
    productQualityConfigurationState.configuration = completeProductQualityConfiguration({ quality_preset_key: defaultPresetKey, quality_architecture_enabled: true });
    openProductQualityConfigurationDialogWindow('تنظیم مدل‌های هوش مصنوعی', ids.length.toLocaleString('fa-IR') + ' محصول انتخاب شده');
  }

  function openProductQualityConfigurationDialogWindow(title, subtitle) {
    const dialog = document.getElementById('product-quality-configuration-dialog');
    document.getElementById('product-quality-configuration-title').textContent = title;
    document.getElementById('product-quality-configuration-subtitle').textContent = subtitle;
    document.getElementById('product-quality-configuration-state').textContent = '';
    document.getElementById('product-quality-configuration-hint').innerHTML = productQualityConfigurationState.mode === 'bulk'
      ? '<i class="fa-solid fa-layer-group ml-1" style="color:var(--primary);"></i> تنظیمات انتخاب‌شده برای همهٔ محصولات انتخاب‌شده ذخیره می‌شود. می‌توانید ابتدا یکی از پیش‌فرض‌ها را انتخاب و سپس جزئیات آن را تغییر دهید.'
      : '<i class="fa-solid fa-circle-info ml-1" style="color:var(--primary);"></i> این همان معماری گام دوم ثبت محصول است. هر مسیر جایگزین باید با مدل اصلی متفاوت باشد.';
    document.getElementById('product-quality-configuration-submit').textContent = productQualityConfigurationState.mode === 'bulk' ? 'اعمال به محصولات منتخب' : 'ذخیره تنظیمات';
    renderProductQualityConfigurationDialog();
    if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', 'open');
  }

  function updateProductQualitySelection(select) {
    const group = select.dataset.qualityGroup;
    const key = select.dataset.qualityKey;
    const role = select.dataset.qualityRole;
    if (!group || !key || !role) return;
    productQualityConfigurationState.configuration[group][key][role] = {
      model_id: select.value || '', provider: productQualityConfigurationState.configuration[group][key][role]?.provider || '',
    };
    renderProductQualityConfigurationDialog();
  }

  function updateProductQualityProvider(select) {
    const group = select.dataset.qualityGroup;
    const key = select.dataset.qualityKey;
    const role = select.dataset.qualityRole;
    if (!group || !key || !role) return;
    const pair = productQualityConfigurationState.configuration[group][key];
    pair[role] = { model_id: '', provider: select.value || '' };
    renderProductQualityConfigurationDialog();
  }

  function applyProductQualityPreset(key) {
    const preset = presetQualityConfiguration(key);
    if (!Object.keys(preset).length) {
      showProductNotice('این پیش‌فرض در نسخه‌ی فعلی سایت موجود نیست؛ ابتدا migrationهای دیتابیس را اجرا کنید.', 'error');
      return;
    }
    productQualityConfigurationState.configuration.quality_preset_key = key;
    productQualityCards.concat(productFreeQualityCards).forEach(function (card) {
      productQualityConfigurationState.configuration[card.group][card.key] = cloneProductQualityValue(preset?.[card.group]?.[card.key] || emptyQualityPair());
    });
    renderProductQualityConfigurationDialog();
  }

  function toggleProductQualityArchitecture() {
    productQualityConfigurationState.configuration.quality_architecture_enabled = productQualityConfigurationState.configuration.quality_architecture_enabled === false;
    renderProductQualityConfigurationDialog();
  }

  function productQualityPresetPayload() {
    const configuration = productQualityConfigurationState.configuration;
    return {
      quality_models: cloneProductQualityValue(configuration.quality_models),
      free_quality_models: cloneProductQualityValue(configuration.free_quality_models),
    };
  }

  async function fixProductQualityPreset() {
    const key = productQualityConfigurationState.configuration.quality_preset_key;
    const preset = productQualityPresets()[key];
    const state = document.getElementById('product-quality-configuration-state');
    if (!preset?.url) {
      state.style.color = 'var(--danger)';
      state.textContent = 'پیش‌فرض انتخاب‌شده از سرور دریافت نشده است؛ ابتدا migrationهای دیتابیس را اجرا کنید.';
      showProductNotice(state.textContent, 'error');
      return;
    }
    state.style.color = 'var(--warning)';
    state.textContent = 'در حال ذخیره پیش‌فرض…';
    try {
      const response = await fetch(preset.url, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ configuration: productQualityPresetPayload() }),
      });
      const data = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'ذخیره پیش‌فرض انجام نشد.');
      productQualityPresets()[key].configuration = data.preset?.configuration || productQualityPresetPayload();
      state.style.color = 'var(--success)';
      state.textContent = data.message || 'تنظیمات این پیش‌فرض ذخیره شد.';
    } catch (error) {
      state.style.color = 'var(--danger)';
      state.textContent = error.message || 'ذخیره پیش‌فرض انجام نشد.';
      showProductNotice(state.textContent, 'error');
    }
  }

  async function saveProductQualityConfiguration() {
    const state = document.getElementById('product-quality-configuration-state');
    const submit = document.getElementById('product-quality-configuration-submit');
    if (productQualityConfigurationState.mode === 'bulk' && !productQualityConfigurationState.ids.length) {
      showProductNotice('هیچ محصولی برای اعمال تنظیمات انتخاب نشده است.', 'warning');
      return;
    }
    if (!productQualityConfigurationState.url || productQualityConfigurationState.saving) {
      showProductNotice('مسیر ذخیره‌سازی تنظیمات در این صفحه آماده نیست. صفحه را تازه‌سازی کنید.', 'error');
      return;
    }
    if (productQualityConfigurationState.mode === 'bulk' && !Object.keys(productQualityPresets()).length) {
      state.style.color = 'var(--danger)';
      state.textContent = 'هیچ پیش‌فرضی از سرور دریافت نشد؛ ابتدا migrationهای دیتابیس را اجرا کنید.';
      showProductNotice(state.textContent, 'error');
      return;
    }
    productQualityConfigurationState.saving = true;
    if (submit) submit.disabled = true;
    state.style.color = 'var(--warning)';
    state.textContent = productQualityConfigurationState.mode === 'bulk' ? 'در حال اعمال تنظیمات…' : 'در حال ذخیره تنظیمات…';
    const payload = { model_configuration: productQualityConfigurationState.configuration };
    if (productQualityConfigurationState.mode === 'bulk') payload.ids = productQualityConfigurationState.ids;
    try {
      const response = await fetch(productQualityConfigurationState.url, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        const firstError = Object.values(data.errors || {})[0];
        throw new Error(Array.isArray(firstError) ? firstError[0] : (data.message || 'ذخیره تنظیمات انجام نشد.'));
      }
      state.style.color = 'var(--success)';
      state.textContent = data.message || 'معماری مدل‌های هوش مصنوعی ذخیره شد.';
      showProductNotice(state.textContent, 'success');
      setTimeout(function () { window.location.reload(); }, 550);
    } catch (error) {
      state.style.color = 'var(--danger)';
      state.textContent = error.message || 'خطا در ذخیره تنظیمات مدل.';
      showProductNotice(state.textContent, 'error');
    } finally {
      productQualityConfigurationState.saving = false;
      if (submit) submit.disabled = false;
    }
  }

  function closeProductQualityConfigurationDialog() {
    const dialog = document.getElementById('product-quality-configuration-dialog');
    if (dialog?.open && typeof dialog.close === 'function') dialog.close(); else dialog?.removeAttribute('open');
  }

  /* ─── معماری چهارسطحیِ قدیمی؛ برای سازگاری مسیرهای قبلی نگه‌داری شده است ─── */
  const productTierConfigurationState = { mode: 'single', url: '', ids: [], productName: '', tiers: {}, saving: false };
  const productTierSearchState = {};
  const productTierKeys = ['free', 'economy', 'pro', 'business'];

  function productTierDefinitions() {
    return window.PRODUCT_MODEL_TIER_DEFINITIONS || {};
  }

  function productTierDefaults() {
    return window.PRODUCT_MODEL_TIER_DEFAULTS || {};
  }

  function tierClone(value) {
    return JSON.parse(JSON.stringify(value || {}));
  }

  function defaultTierConfiguration() {
    const defaults = productTierDefaults();
    return productTierKeys.reduce(function (tiers, key) {
      const item = defaults[key] || {};
      tiers[key] = {
        primary: { model_id: item.primary?.model_id || '', provider: item.primary?.provider || '' },
        fallback: { model_id: item.fallback?.model_id || '', provider: item.fallback?.provider || '' },
      };
      return tiers;
    }, {});
  }

  function completeTierConfiguration(configuration) {
    const defaults = defaultTierConfiguration();
    return productTierKeys.reduce(function (tiers, key) {
      const saved = configuration?.[key] || {};
      tiers[key] = {
        primary: {
          model_id: saved.primary?.model_id || defaults[key].primary.model_id || '',
          provider: saved.primary?.provider || defaults[key].primary.provider || '',
        },
        fallback: {
          model_id: saved.fallback?.model_id || defaults[key].fallback.model_id || '',
          provider: saved.fallback?.provider || defaults[key].fallback.provider || '',
        },
      };
      return tiers;
    }, {});
  }

  function openProductTierConfigurationDialog(button) {
    closeAllDropdowns();
    let saved = {};
    try { saved = JSON.parse(button.dataset.tierConfiguration || '{}'); } catch (_) { saved = {}; }
    productTierConfigurationState.mode = 'single';
    productTierConfigurationState.url = button.dataset.tierUrl || '';
    productTierConfigurationState.ids = [];
    productTierConfigurationState.productName = button.dataset.productName || '';
    productTierConfigurationState.tiers = completeTierConfiguration(saved);
    openTierConfigurationDialog('مدل‌های چهار سطحی محصول', productTierConfigurationState.productName);
  }

  function openBulkTierConfigurationDialog() {
    const ids = typeof requireBulkSelection === 'function' ? requireBulkSelection() : [];
    if (!ids) return;
    productTierConfigurationState.mode = 'bulk';
    productTierConfigurationState.url = window.PRODUCT_BULK_TIER_URL || '';
    productTierConfigurationState.ids = ids;
    productTierConfigurationState.productName = '';
    productTierConfigurationState.tiers = defaultTierConfiguration();
    openTierConfigurationDialog('اعمال چهار پیش‌فرض مدل', ids.length.toLocaleString('fa-IR') + ' محصول انتخاب شده');
  }

  function openTierConfigurationDialog(title, subtitle) {
    const dialog = document.getElementById('product-tier-configuration-dialog');
    document.getElementById('product-tier-configuration-title').textContent = title;
    document.getElementById('product-tier-configuration-subtitle').textContent = subtitle;
    document.getElementById('product-tier-configuration-state').textContent = '';
    document.getElementById('product-tier-configuration-hint').innerHTML = productTierConfigurationState.mode === 'bulk'
      ? '<i class="fa-solid fa-layer-group ml-1" style="color:var(--primary);"></i> برای عملیات گروهی، چهار پیش‌فرض سراسریِ هر سطح روی همهٔ محصولات انتخاب‌شده اعمال می‌شود؛ انتخاب دستی هر سطح فقط برای یک محصول انجام می‌شود.'
      : '<i class="fa-solid fa-circle-info ml-1" style="color:var(--primary);"></i> برای هر پلن، یک مدل اصلی و یک مسیر جایگزین متفاوت انتخاب کنید. دکمهٔ پیش‌فرض، انتخاب همان سطح را به تنظیم سراسری برمی‌گرداند.';
    const submit = document.getElementById('product-tier-configuration-submit');
    submit.textContent = productTierConfigurationState.mode === 'bulk' ? 'اعمال به محصولات منتخب' : 'ذخیره چهار سطح';
    renderTierConfigurationDialog();
    if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', 'open');
  }

  function tierModelsForDialog(tierKey, role) {
    const grade = Number(productTierDefinitions()[tierKey]?.grade || 0);
    const all = productAiModels();
    const matched = all.filter(function (model) { return Number(model.gradeNumber) === grade; });
    const candidates = role === 'primary' && matched.length ? matched : all;
    const primary = productTierConfigurationState.tiers?.[tierKey]?.primary || {};
    return candidates.filter(function (model) {
      const isSameModel = role === 'fallback'
        && primary.provider
        && model.provider === primary.provider
        && model.id === primary.model_id;
      return !isSameModel;
    });
  }

  function tierSearchKey(tierKey, role) {
    return tierKey + ':' + role;
  }

  function filterTierModelSearch(input) {
    const query = String(input?.value || '').trim().toLowerCase();
    const select = input?.closest('label')?.querySelector('[data-tier-model-search-select]');
    if (!select) return;
    Array.from(select.options).forEach(function (option) {
      if (!option.value) return;
      const haystack = String(option.dataset.search || option.textContent || '').toLowerCase();
      option.hidden = Boolean(query && !haystack.includes(query));
    });
    const key = input.dataset.tierSearchKey;
    if (key) productTierSearchState[key] = input.value || '';
  }

  function tierCostText(model) {
    if (!model || !Number(model.usd)) return 'هزینه تقریبی هر ساخت: —';
    const usd = Number(model.usd).toFixed(3);
    const toman = Number(model.toman || 0).toLocaleString('fa-IR');
    return 'هزینه تقریبی هر ساخت: $' + usd + (Number(model.toman) ? ' · ' + toman + ' تومان' : '');
  }

  function tierSelectedModel(tierKey, role) {
    const selection = productTierConfigurationState.tiers?.[tierKey]?.[role] || {};
    return productAiModels().find(function (model) {
      return model.id === selection.model_id && model.provider === selection.provider;
    }) || null;
  }

  function tierModelOptions(tierKey, role) {
    const selection = productTierConfigurationState.tiers?.[tierKey]?.[role] || {};
    const models = tierModelsForDialog(tierKey, role);
    const selectedIsMissing = selection.model_id && !models.some(function (model) {
      return model.id === selection.model_id && model.provider === selection.provider;
    });
    const selectedModel = selectedIsMissing ? tierSelectedModel(tierKey, role) : null;
    const rows = selectedModel ? [selectedModel].concat(models) : models;
    return '<option value="">انتخاب مدل</option>' + rows.map(function (model) {
      const selected = model.id === selection.model_id && model.provider === selection.provider ? ' selected' : '';
      const searchText = [model.id, model.name, model.persianName, model.provider, model.providerFa].filter(Boolean).join(' ').toLowerCase();
      return '<option value="' + pmEsc(model.id) + '" data-provider="' + pmEsc(model.provider) + '" data-search="' + pmEsc(searchText) + '"' + selected + '>' + pmEsc(model.persianName || model.name || model.id) + ' · ' + pmEsc(model.providerFa || model.provider) + '</option>';
    }).join('');
  }

  function renderTierConfigurationDialog() {
    const holder = document.getElementById('product-tier-configuration-content');
    if (!holder) return;
    const definitions = productTierDefinitions();
    const defaults = productTierDefaults();
    const locked = productTierConfigurationState.mode === 'bulk';
    holder.innerHTML = productTierKeys.map(function (key) {
      const meta = definitions[key] || {};
      const defaultName = defaults[key]?.name || ('پلن ' + (meta.name || key));
      const primary = tierSelectedModel(key, 'primary');
      const fallback = tierSelectedModel(key, 'fallback');
      const disabled = locked ? ' disabled' : '';
      const primarySearchKey = tierSearchKey(key, 'primary');
      const fallbackSearchKey = tierSearchKey(key, 'fallback');
      return '<article class="rounded-xl p-4" style="background:var(--input-bg);border:1px solid var(--border);">' +
        '<div class="flex items-start justify-between gap-3 mb-3"><div><b class="text-[12px]" style="color:var(--text-h);">پلن ' + pmEsc(meta.name || key) + ' <span class="font-normal" style="color:var(--text-soft);">· گرید ' + pmEsc(meta.grade || '') + '</span></b><p class="text-[10px] leading-5 mt-1" style="color:var(--text-soft);">' + pmEsc(meta.description || '') + '</p></div>' +
        (locked ? '<span class="text-[9.5px]" style="color:var(--text-soft);">پیش‌فرض سراسری</span>' : '<button type="button" class="text-[10px] font-bold" style="color:var(--primary);" onclick="applyTierDefaultInDialog(\'' + key + '\')">پیش‌فرض این سطح</button>') + '</div>' +
        '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5"><label class="block text-[10px] font-bold" style="color:var(--text-soft);">مدل اصلی<input type="search" class="input-pro w-full mt-1" style="height:32px;" data-tier-model-search data-tier-search-key="' + pmEsc(primarySearchKey) + '" value="' + pmEsc(productTierSearchState[primarySearchKey] || '') + '" placeholder="جستجوی نام یا شناسه مدل..." autocomplete="off" oninput="filterTierModelSearch(this)"><select class="input-pro w-full mt-1" style="height:38px;" data-tier-model-search-select data-tier-key="' + key + '" data-tier-role="primary" onchange="updateTierDialogSelection(this)"' + disabled + '>' + tierModelOptions(key, 'primary') + '</select><small class="block mt-1 font-normal" style="color:var(--text-soft);">' + tierCostText(primary) + '</small></label>' +
        '<label class="block text-[10px] font-bold" style="color:var(--text-soft);">مدل جایگزین <span class="font-normal">(مسیر مستقل)</span><input type="search" class="input-pro w-full mt-1" style="height:32px;" data-tier-model-search data-tier-search-key="' + pmEsc(fallbackSearchKey) + '" value="' + pmEsc(productTierSearchState[fallbackSearchKey] || '') + '" placeholder="جستجوی نام یا شناسه مدل..." autocomplete="off" oninput="filterTierModelSearch(this)"><select class="input-pro w-full mt-1" style="height:38px;" data-tier-model-search-select data-tier-key="' + key + '" data-tier-role="fallback" onchange="updateTierDialogSelection(this)"' + disabled + '>' + tierModelOptions(key, 'fallback') + '</select><small class="block mt-1 font-normal" style="color:var(--text-soft);">' + tierCostText(fallback) + '</small></label></div>' +
        '<div class="text-[9.5px] mt-3 pt-2" style="border-top:1px solid var(--border);color:var(--text-soft);">پیش‌فرض فعال: ' + pmEsc(defaultName) + '</div></article>';
    }).join('');
    holder.querySelectorAll('[data-tier-model-search]').forEach(function (input) {
      filterTierModelSearch(input);
    });
  }

  function updateTierDialogSelection(select) {
    const key = select.dataset.tierKey;
    const role = select.dataset.tierRole;
    const option = select.options[select.selectedIndex];
    if (!key || !role) return;
    productTierConfigurationState.tiers[key][role] = {
      model_id: option?.value || '',
      provider: option?.dataset.provider || '',
    };
    renderTierConfigurationDialog();
  }

  function applyTierDefaultInDialog(key) {
    const defaultValue = defaultTierConfiguration()[key];
    if (!defaultValue) return;
    productTierConfigurationState.tiers[key] = tierClone(defaultValue);
    renderTierConfigurationDialog();
  }

  function applyAllTierDefaultsInDialog() {
    productTierConfigurationState.tiers = defaultTierConfiguration();
    renderTierConfigurationDialog();
  }

  async function saveProductTierConfiguration() {
    const state = document.getElementById('product-tier-configuration-state');
    if (!productTierConfigurationState.url || productTierConfigurationState.saving) return;
    productTierConfigurationState.saving = true;
    state.style.color = 'var(--warning)';
    state.textContent = productTierConfigurationState.mode === 'bulk' ? 'در حال اعمال چهار پیش‌فرض...' : 'در حال ذخیره چهار سطح...';
    const payload = productTierConfigurationState.mode === 'bulk'
      ? { ids: productTierConfigurationState.ids, preset_key: 'global' }
      : { tiers: productTierConfigurationState.tiers };
    try {
      const response = await fetch(productTierConfigurationState.url, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        const firstError = Object.values(data.errors || {})[0];
        throw new Error(Array.isArray(firstError) ? firstError[0] : (data.message || 'ذخیره تنظیمات انجام نشد.'));
      }
      state.style.color = 'var(--success)';
      state.textContent = data.message || 'چهار سطح مدل ذخیره شد.';
      showProductNotice(state.textContent, 'success');
      setTimeout(function () { window.location.reload(); }, 550);
    } catch (error) {
      state.style.color = 'var(--danger)';
      state.textContent = error.message || 'خطا در ذخیره تنظیمات مدل.';
    } finally {
      productTierConfigurationState.saving = false;
    }
  }

  function closeProductTierConfigurationDialog() {
    const dialog = document.getElementById('product-tier-configuration-dialog');
    if (dialog?.open && typeof dialog.close === 'function') dialog.close(); else dialog?.removeAttribute('open');
  }

  function openProductTierPresetDialog(button) { openProductTierConfigurationDialog(button); }
  function openBulkTierPresetDialog() { openBulkTierConfigurationDialog(); }

  function openConfiguredAiModelDialog(title, subtitle) {
    const dialog = document.getElementById('product-ai-model-dialog');
    const providerSelect = document.getElementById('product-ai-provider-select');
    const providers = [...new Set(productAiModels().map(function (model) { return model.provider; }))];
    document.getElementById('product-ai-dialog-title').textContent = title;
    document.getElementById('product-ai-dialog-subtitle').textContent = subtitle;
    document.getElementById('product-ai-dialog-state').textContent = 'با انتخاب مدل، تغییر به‌صورت خودکار ذخیره می‌شود.';
    providerSelect.innerHTML = [''].concat(providers).map(function (provider) {
      const labels = {openrouter: 'OpenRouter', fal: 'Fal.ai', replicate: 'Replicate'};
      return '<option value="' + provider + '">' + (provider ? (labels[provider] || provider) : 'انتخاب پرووایدر') + '</option>';
    }).join('');
    providerSelect.value = providers.includes(productAiDialogState.currentProvider)
      ? productAiDialogState.currentProvider
      : '';
    renderProductAiModelOptions();
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', 'open');
  }

  function renderProductAiModelOptions() {
    const provider = document.getElementById('product-ai-provider-select')?.value || '';
    const task = document.getElementById('product-ai-task-select')?.value || 'product_image';
    const search = (document.getElementById('product-ai-model-search')?.value || '').trim().toLowerCase();
    const modelSelect = document.getElementById('product-ai-model-select');
    const models = productAiModels().filter(function (model) {
      const providerOk = Boolean(provider) && model.provider === provider;
      const taskOk = task === 'all' || (task === 'product_image' ? model.workflow === 'product_image' : model.task === task);
      const haystack = [model.name, model.englishName, model.id, model.providerFa, model.providerEn].join(' ').toLowerCase();
      return providerOk && taskOk && (!search || haystack.includes(search));
    });
    modelSelect.disabled = !provider;
    modelSelect.innerHTML = '<option value="">' + (provider ? 'یک مدل انتخاب کنید...' : 'ابتدا پرووایدر را انتخاب کنید') + '</option>' + models.map(function (model) {
      const plan = '';
      return '<option value="' + pmEsc(model.id) + '">' + pmEsc(model.name) + plan + '</option>';
    }).join('');
    if (provider === productAiDialogState.currentProvider) modelSelect.value = productAiDialogState.currentModel;
    const holder = document.getElementById('product-ai-model-options');
    if (holder) {
      holder.innerHTML = !provider
        ? '<div class="p-4 text-center text-[10px]" style="color:var(--text-soft);">ابتدا پرووایدر را انتخاب کنید.</div>'
        : models.map(function (model) {
        const selected = model.provider === productAiDialogState.currentProvider && model.id === productAiDialogState.currentModel;
        return '<button type="button" class="product-ai-model-row' + (selected ? ' is-selected' : '') + '" data-model-id="' + pmEsc(model.id) + '" data-model-provider="' + pmEsc(model.provider) + '" onclick="selectProductAiModel(this)">' +
          '<span dir="ltr" class="font-mono">' + pmEsc(model.englishName || model.id) + '</span>' +
          '<span>' + pmEsc(model.persianName || model.name) + '</span>' +
          '<span><b>' + pmEsc(model.providerFa || model.provider) + '</b><small dir="ltr">' + pmEsc(model.providerEn || model.provider) + '</small></span>' +
          '<span>' + pmEsc(model.usage || 'ثبت نشده') + '</span>' +
          '<span class="model-quality-grade">' + pmEsc(model.grade || 'ثبت نشده') + '</span>' +
          '</button>';
        }).join('') || '<div class="p-4 text-center text-[10px]" style="color:var(--text-soft);">برای این پرووایدر مدلی وجود ندارد.</div>';
    }
  }

  function selectProductAiModel(row) {
    const providerSelect = document.getElementById('product-ai-provider-select');
    const modelSelect = document.getElementById('product-ai-model-select');
    if (!row || !providerSelect || !modelSelect) return;
    providerSelect.value = row.dataset.modelProvider || '';
    renderProductAiModelOptions();
    modelSelect.value = row.dataset.modelId || '';
    saveProductAiModelSelection();
  }

  async function saveProductAiModelSelection() {
    const provider = document.getElementById('product-ai-provider-select')?.value || '';
    const modelId = document.getElementById('product-ai-model-select')?.value || '';
    const state = document.getElementById('product-ai-dialog-state');
    if (!provider || !modelId || productAiDialogState.saving) return;

    productAiDialogState.saving = true;
    state.style.color = 'var(--warning)';
    state.textContent = 'در حال ذخیره مدل...';
    const payload = { ai_provider: provider, primary_model: modelId };
    if (productAiDialogState.mode === 'bulk') payload.ids = productAiDialogState.selectedIds;

    try {
      const response = await fetch(productAiDialogState.url, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        const firstError = Object.values(data.errors || {})[0];
        throw new Error(Array.isArray(firstError) ? firstError[0] : (data.message || 'ذخیره مدل انجام نشد.'));
      }

      if (productAiDialogState.mode === 'bulk') {
        productAiDialogState.selectedIds.forEach(function (id) {
          updateProductAiStatusBadge(id, data.model_name, data.provider);
        });
      } else {
        updateProductAiStatusBadge(productAiDialogState.productId, data.model_name, data.provider);
        if (productAiDialogState.trigger) {
          productAiDialogState.trigger.dataset.aiProvider = data.provider;
          productAiDialogState.trigger.dataset.aiModel = data.model_id;
        }
      }
      state.style.color = 'var(--success)';
      state.textContent = data.message || 'مدل با موفقیت ذخیره شد.';
      showProductNotice(data.message || 'مدل هوش مصنوعی ذخیره شد.', 'success');
      setTimeout(closeProductAiModelDialog, 650);
    } catch (error) {
      state.style.color = 'var(--danger)';
      state.textContent = error.message || 'خطا در ذخیره مدل هوش مصنوعی.';
      showProductNotice(state.textContent, 'error');
    } finally {
      productAiDialogState.saving = false;
    }
  }

  function updateProductAiStatusBadge(productId, modelName, provider) {
    const box = document.getElementById('product-ai-status-' + productId);
    if (!box) return;
    box.innerHTML = '<span class="badge-pro badge-success" dir="ltr"><i class="fa-solid fa-circle-check"></i> ' + pmEsc(modelName) + '</span>';
  }

  function closeProductAiModelDialog() {
    const dialog = document.getElementById('product-ai-model-dialog');
    if (dialog?.open && typeof dialog.close === 'function') dialog.close();
    else dialog?.removeAttribute('open');
  }

  /* ─── انتخاب چندگانه ردیف‌ها + نوار عملیات گروهی ─── */
  var bulkSelectionState = { allMatching: false };

  function getSelectedBulkProductIds() {
    if (bulkSelectionState.allMatching) {
      return Array.isArray(window.PRODUCT_MATCHING_IDS) ? window.PRODUCT_MATCHING_IDS.slice() : [];
    }
    return [...document.querySelectorAll('.bulk-check:checked')].map(function (checkbox) { return checkbox.value; });
  }

  function requireBulkSelection() {
    const ids = getSelectedBulkProductIds();
    if (!ids.length) {
      showProductNotice('برای این عملیات، ابتدا حداقل یک محصول را انتخاب کنید.', 'warning');
      return null;
    }
    return ids;
  }

  function updateBulkSelectionUi() {
    var checked = document.querySelectorAll('.bulk-check:checked');
    var selectedIds = getSelectedBulkProductIds();
    var toolbar = document.getElementById('bulk-toolbar');
    var selectAllMatchingButton = document.getElementById('bulk-select-all-matching');
    var totalMatching = Array.isArray(window.PRODUCT_MATCHING_IDS) ? window.PRODUCT_MATCHING_IDS.length : 0;
    var visibleCount = document.querySelectorAll('.bulk-check').length;

    document.getElementById('bulk-count').textContent = selectedIds.length.toLocaleString('fa-IR');
    toolbar.style.display = selectedIds.length > 0 ? 'flex' : 'none';
    if (selectAllMatchingButton) {
      selectAllMatchingButton.style.display = !bulkSelectionState.allMatching && checked.length === visibleCount && totalMatching > visibleCount ? 'inline-flex' : 'none';
      selectAllMatchingButton.textContent = 'انتخاب همه‌ی ' + totalMatching.toLocaleString('fa-IR') + ' نتیجه‌ی فیلترشده';
    }
  }

  function selectAllMatchingProducts() {
    if (!Array.isArray(window.PRODUCT_MATCHING_IDS) || !window.PRODUCT_MATCHING_IDS.length) return;
    bulkSelectionState.allMatching = true;
    document.querySelectorAll('.bulk-check').forEach(function (checkbox) { checkbox.checked = true; });
    updateBulkSelectionUi();
  }

  function toggleSelectAll(cb) {
    bulkSelectionState.allMatching = false;
    document.querySelectorAll('.bulk-check').forEach(c => c.checked = cb.checked);
    updateBulkSelectionUi();
  }
  function onRowCheck() {
    bulkSelectionState.allMatching = false;
    updateBulkSelectionUi();
  }
  function submitBulk(action) {
    const checked = requireBulkSelection();
    if (!checked) return;
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
    const checked = requireBulkSelection();
    if (!checked) return;
    const form = document.getElementById('bulk-action-form');
    form.querySelectorAll('input[name="ids[]"], input[name="category"]').forEach(el => el.remove());
    checked.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
      form.appendChild(input);
    });
    const catInput = document.createElement('input');
    catInput.type = 'hidden'; catInput.name = 'category'; catInput.value = category;
    form.appendChild(catInput);
    document.getElementById('bulk-action-input').value = 'change_category';
    form.submit();
  }
</script>
