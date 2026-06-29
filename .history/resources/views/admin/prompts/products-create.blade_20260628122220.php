@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — AIPIX Admin')

@push('styles')
<style>
:root {
  --bg:#0c0c10; --s1:#111116; --s2:#16161c;
  --b1:#222230; --b2:#2e2e3e;
  --text:#ffffff; --text2:#a8c4a8; --text3:#4d7a56;
  --green:#0BBF53; --accent:#a07af5; --red:#f05c5c; --orange:#f5923a;
}
*{box-sizing:border-box;}
body{font-family:'IRANSansXFaNum',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}

/* ── LAYOUT ── */
.admin-wrap{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;right:0;bottom:0;width:256px;background:var(--s1);border-left:1px solid var(--b1);display:flex;flex-direction:column;overflow-y:auto;z-index:100;scrollbar-width:thin;scrollbar-color:var(--b2) transparent;}
.admin-main{margin-right:256px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.admin-header{position:sticky;top:0;z-index:50;background:var(--s1);border-bottom:1px solid var(--b1);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;}
.admin-content{padding:24px;flex:1;}

/* ── SIDEBAR ── */
.snav-section{font-size:9px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--text3);padding:12px 16px 4px;}
.snav-item{display:flex;align-items:center;gap:10px;padding:0 8px;margin:1px 6px;border-radius:8px;cursor:pointer;transition:background .15s;height:38px;text-decoration:none;}
.snav-item:hover{background:var(--s2);}
.snav-item.active{background:rgba(160,122,245,.12);}
.snav-icon{width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text2);flex-shrink:0;}
.snav-item.active .snav-icon{color:var(--accent);}
.snav-label{flex:1;font-size:12.5px;font-weight:600;color:var(--text2);}
.snav-item.active .snav-label{color:var(--text);}
.snav-sub-item{display:flex;align-items:center;gap:8px;padding:6px 10px;margin:1px 6px 1px 30px;border-radius:6px;cursor:pointer;transition:background .15s;text-decoration:none;}
.snav-sub-item:hover{background:var(--s2);}
.snav-sub-item.active{background:rgba(160,122,245,.1);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;transition:background .15s;}
.snav-sub-item.active .snav-dot,.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}

/* ── HEADER ── */
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;transition:color .15s;}
.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}

/* ══ STEPPER ══ */
.stepper{display:flex;align-items:center;gap:0;margin-bottom:28px;background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:6px;}
.step-item{flex:1;display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;cursor:pointer;transition:all .2s;border:1px solid transparent;}
.step-item.active{background:rgba(160,122,245,.12);border-color:rgba(160,122,245,.2);}
.step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;border:2px solid var(--b2);color:var(--text3);transition:all .2s;}
.step-item.active .step-num{border-color:var(--accent);background:rgba(160,122,245,.15);color:var(--accent);}
.step-item.done .step-num{border-color:var(--green);background:rgba(11,191,83,.12);color:var(--green);}
.step-info{flex:1;}
.step-label{font-size:11px;color:var(--text3);margin-bottom:1px;}
.step-item.active .step-label{color:var(--accent);}
.step-title{font-size:13px;font-weight:700;color:var(--text2);}
.step-item.active .step-title{color:var(--text);}
.step-divider{width:24px;flex-shrink:0;height:1px;background:var(--b1);}

/* ══ FORM ══ */
.form-section{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:22px 24px;margin-bottom:16px;}
.form-section-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:18px;display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid var(--b1);}
.form-section-title i{color:var(--accent);}
.form-row{display:grid;gap:14px;margin-bottom:14px;}
.form-row-2{grid-template-columns:1fr 1fr;}
.form-row-3{grid-template-columns:1fr 1fr 1fr;}
.form-row-1{grid-template-columns:1fr;}
.form-group{display:flex;flex-direction:column;gap:5px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);}
.form-label .req{color:var(--red);margin-right:2px;}
.form-label .hint{font-size:10px;font-weight:400;color:var(--text3);margin-right:4px;}
.form-input,.form-select,.form-textarea{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'IRANSansXFaNum',sans-serif;outline:none;direction:rtl;transition:border-color .15s;width:100%;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);}
.form-textarea{resize:vertical;min-height:90px;line-height:1.6;}
.form-input::placeholder,.form-textarea::placeholder{color:var(--text3);}
.form-input-en{direction:ltr;text-align:left;}

/* toggles */
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;}
.toggle-label{font-size:12.5px;font-weight:600;color:var(--text2);}
.toggle-desc{font-size:11px;color:var(--text3);margin-top:1px;}
.toggle{position:relative;width:36px;height:20px;flex-shrink:0;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:var(--b2);border-radius:20px;cursor:pointer;transition:.2s;}
.toggle-slider:before{content:'';position:absolute;width:14px;height:14px;right:3px;top:3px;background:var(--text3);border-radius:50%;transition:.2s;}
.toggle input:checked + .toggle-slider{background:var(--green);}
.toggle input:checked + .toggle-slider:before{right:auto;left:3px;background:#fff;}

/* tags */
.tags-input-wrap{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:6px 10px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;cursor:text;min-height:42px;transition:border-color .15s;}
.tags-input-wrap:focus-within{border-color:var(--accent);}
.tag-chip{display:inline-flex;align-items:center;gap:4px;background:rgba(160,122,245,.12);border:1px solid rgba(160,122,245,.25);border-radius:6px;padding:3px 8px;font-size:11.5px;color:var(--accent);}
.tag-chip button{background:none;border:none;cursor:pointer;color:var(--text3);padding:0;line-height:1;font-size:11px;}
.tag-chip button:hover{color:var(--red);}
.tags-input-wrap input{background:none;border:none;outline:none;font-family:'IRANSansXFaNum',sans-serif;font-size:13px;color:var(--text);flex:1;min-width:80px;direction:rtl;}

/* upload zones */
.upload-zone{border:1.5px dashed var(--b2);border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s;background:var(--s1);}
.upload-zone:hover{border-color:var(--accent);background:rgba(160,122,245,.04);}
.upload-zone i{font-size:24px;color:var(--text3);margin-bottom:8px;display:block;}
.upload-zone-label{font-size:12.5px;font-weight:600;color:var(--text2);margin-bottom:3px;}
.upload-zone-hint{font-size:11px;color:var(--text3);}
.sample-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;}
.sample-cell{aspect-ratio:1;border-radius:8px;border:1.5px dashed var(--b2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;background:var(--s1);font-size:18px;color:var(--text3);}
.sample-cell:hover{border-color:var(--accent);color:var(--accent);}

/* AI model cards */
.model-card{background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:border-color .15s;margin-bottom:8px;}
.model-card:hover{border-color:var(--b2);}
.model-badge-primary{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);white-space:nowrap;}
.model-badge-fallback{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);white-space:nowrap;}
.model-drag{color:var(--text3);cursor:grab;font-size:13px;flex-shrink:0;}
.model-remove{background:none;border:none;cursor:pointer;color:var(--text3);font-size:12px;padding:4px 8px;border-radius:6px;transition:all .15s;font-family:'IRANSansXFaNum',sans-serif;white-space:nowrap;}
.model-remove:hover{background:rgba(240,92,92,.1);color:var(--red);}

/* input schema builder */
.field-header{display:grid;grid-template-columns:24px 1fr 1fr 160px 76px 34px;gap:10px;padding:0 4px 6px;border-bottom:1px solid var(--b1);margin-bottom:8px;}
.field-col-label{font-size:10px;font-weight:700;color:var(--text3);letter-spacing:.5px;text-transform:uppercase;}
.field-row{background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:12px 14px;margin-bottom:8px;display:grid;grid-template-columns:24px 1fr 1fr 160px 76px 34px;gap:10px;align-items:center;transition:border-color .15s;}
.field-row:hover{border-color:var(--b2);}
.req-toggle{width:28px;height:28px;border-radius:6px;border:1.5px solid var(--b2);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .15s;margin:0 auto;}
.req-toggle.on{background:var(--green);border-color:var(--green);color:#fff;}
.req-toggle:not(.on){color:var(--text3);}
.field-remove{width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--text3);transition:all .15s;}
.field-remove:hover{border-color:var(--red);color:var(--red);background:rgba(240,92,92,.05);}
.add-field-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1.5px dashed var(--b2);background:none;color:var(--text3);font-size:12px;font-weight:600;cursor:pointer;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;}
.add-field-btn:hover{border-color:var(--accent);color:var(--accent);background:rgba(160,122,245,.04);}

/* prompt */
.prompt-var-chip{font-size:10.5px;background:var(--b1);border:1px solid var(--b2);border-radius:5px;padding:2px 8px;color:var(--text2);cursor:pointer;font-family:monospace;transition:all .15s;}
.prompt-var-chip:hover{border-color:var(--accent);color:var(--accent);}

/* color swatches */
.color-swatches{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;}
.color-swatch{width:28px;height:28px;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:all .15s;}
.color-swatch:hover,.color-swatch.active{border-color:#fff;transform:scale(1.12);}

/* footer */
.form-footer{position:sticky;bottom:0;background:var(--s1);border-top:1px solid var(--b1);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;z-index:40;}
.btn-lg{display:inline-flex;align-items:center;gap:8px;padding:0 22px;height:40px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;}
.btn-primary{background:var(--accent);color:#fff;}
.btn-primary:hover{background:#8f68e0;}
.btn-secondary{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.btn-secondary:hover{border-color:var(--b2);color:var(--text);}
.btn-success{background:var(--green);color:#fff;}
.btn-success:hover{background:#08a443;}

/* step panels */
.step-panel{display:none;}
.step-panel.active{display:block;}
</style>
@endpush

@section('content')
<div class="admin-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside class="admin-sidebar">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900;color:#fff;box-shadow:0 0 16px rgba(11,191,83,.3);">و</div>
      <div>
        <div style="font-size:14px;font-weight:800;">وطن استودیو</div>
        <div style="font-size:9px;color:var(--text3);letter-spacing:2.5px;text-transform:uppercase;">Admin Panel</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--b1);">
      <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">م</div>
      <div style="flex:1;">
        <div style="font-size:12px;font-weight:700;">محسن رضایی</div>
        <div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div>
      </div>
      <div style="width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);flex-shrink:0;"></div>
    </div>
    <nav style="flex:1;padding:8px 0;">
      <a href="/admin/dashboard" class="snav-item" style="margin-bottom:4px;">
        <div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="snav-label">مرکز فرماندهی</div>
      </a>
      <div class="snav-section">مدیریت محصولات</div>
      <div class="snav-item active">
        <div class="snav-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="snav-label">محصولات</div>
        <i class="fa-solid fa-chevron-down" style="font-size:9px;color:var(--text3);"></i>
      </div>
      <div style="padding:2px 0 4px;">
        <div class="snav-sub-item">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">داشبورد محصولات</div>
          <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span>
        </div>
        <a href="/admin/products" class="snav-sub-item" style="text-decoration:none;">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">لیست محصولات</div>
        </a>
        <a href="/admin/products/create" class="snav-sub-item active" style="text-decoration:none;">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">ثبت محصول جدید</div>
        </a>
        <div class="snav-sub-item">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">دسته‌بندی‌ها</div>
          <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span>
        </div>
        <div class="snav-sub-item">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">قیمت‌گذاری</div>
          <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span>
        </div>
      </div>
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="snav-label">سفارشات</div>
        <span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span>
      </div>
      <div class="snav-section">هوش مصنوعی</div>
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-microchip"></i></div>
        <div class="snav-label">مدیریت مدل‌ها</div>
        <span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span>
      </div>
      <div style="height:1px;background:var(--b1);margin:8px 12px;"></div>
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-gear"></i></div>
        <div class="snav-label">تنظیمات</div>
      </div>
    </nav>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="admin-main">

    <!-- Header -->
    <header class="admin-header">
      <div class="breadcrumb">
        <a href="/admin/dashboard"><i class="fa-solid fa-house" style="font-size:11px;"></i></a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="/admin/products">محصولات</a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="current">ثبت محصول جدید</span>
      </div>
      <div style="flex:1;"></div>
      <a href="/admin/products" class="hdr-btn hdr-btn-outline">
        <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
        بازگشت به لیست
      </a>
    </header>

    <!-- Content -->
    <main class="admin-content">

      <div style="margin-bottom:22px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">ثبت محصول جدید</div>
        <div style="font-size:13px;color:var(--text3);">محصول را در ۳ مرحله تنظیم کنید — هویت، هوش مصنوعی، و خروجی</div>
      </div>

      <!-- ══ STEPPER ══ -->
      <div class="stepper">
        <div class="step-item active" id="step-tab-1" onclick="goStep(1)">
          <div class="step-num" id="step-num-1">۱</div>
          <div class="step-info">
            <div class="step-label">گام اول</div>
            <div class="step-title">هویت و رسانه</div>
          </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="step-tab-2" onclick="goStep(2)">
          <div class="step-num" id="step-num-2">۲</div>
          <div class="step-info">
            <div class="step-label">گام دوم</div>
            <div class="step-title">هوش مصنوعی</div>
          </div>
        </div>
        <div class="step-divider"></div>
        <div class="step-item" id="step-tab-3" onclick="goStep(3)">
          <div class="step-num" id="step-num-3">۳</div>
          <div class="step-info">
            <div class="step-label">گام سوم</div>
            <div class="step-title">خروجی و قیمت</div>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════
           STEP 1 — Identity + Media
      ══════════════════════════════════════ -->
      <div class="step-panel active" id="panel-1">

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-fingerprint"></i> هویت محصول</div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">نام فارسی <span class="req">*</span></label>
              <input type="text" class="form-input" placeholder="مثال: عکس حرفه‌ای لینکدین">
            </div>
            <div class="form-group">
              <label class="form-label">نام انگلیسی <span class="req">*</span></label>
              <input type="text" class="form-input form-input-en" placeholder="LinkedIn Professional Headshot" oninput="autoSlug(this)">
            </div>
          </div>

          <div class="form-row form-row-1">
            <div class="form-group">
              <label class="form-label">آدرس URL (Slug) <span class="req">*</span></label>
              <input type="text" class="form-input form-input-en" id="slug-input" placeholder="linkedin-professional-headshot">
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">توضیح فارسی</label>
              <textarea class="form-textarea" placeholder="توضیح کوتاهی از محصول برای کاربر..."></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">توضیح انگلیسی</label>
              <textarea class="form-textarea form-input-en" placeholder="Short product description for users..."></textarea>
            </div>
          </div>

          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">دسته‌بندی <span class="req">*</span></label>
              <select class="form-select" id="cat-main" onchange="updateSubcat()">
                <option value="">انتخاب کنید</option>
                <option value="PEOPLE">PEOPLE — شخصی</option>
                <option value="BUSINESS">BUSINESS — کسب‌وکار</option>
                <option value="EVENTS">EVENTS — مناسبت‌ها</option>
                <option value="FAMILY">FAMILY — خانواده</option>
                <option value="KIDS">KIDS — کودکان</option>
                <option value="PETS">PETS — حیوانات</option>
                <option value="ENTERTAINMENT">ENTERTAINMENT — سرگرمی</option>
                <option value="PRODUCTS">PRODUCTS — محصولات</option>
                <option value="AVATARS">AVATARS — آواتار</option>
                <option value="VIDEOS">VIDEOS — ویدیو</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">زیردسته</label>
              <select class="form-select" id="cat-sub">
                <option value="">ابتدا دسته انتخاب کنید</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">وضعیت <span class="req">*</span></label>
              <select class="form-select">
                <option value="draft">پیش‌نویس (draft)</option>
                <option value="active">فعال (active)</option>
                <option value="inactive">غیرفعال (inactive)</option>
              </select>
            </div>
          </div>

          <div class="form-row form-row-1">
            <div class="form-group">
              <label class="form-label">تگ‌های جستجو <span class="hint">Enter بزنید تا اضافه شود</span></label>
              <div class="tags-input-wrap" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
                <input type="text" id="tags-raw" placeholder="تگ بنویسید و Enter بزنید..." onkeydown="addTag(event)">
              </div>
            </div>
          </div>

          <div class="form-row form-row-3">
            <div class="toggle-row">
              <div>
                <div class="toggle-label">محصول ویژه</div>
                <div class="toggle-desc">نمایش در صفحه اول</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">برچسب «جدید»</div>
                <div class="toggle-desc">is_new — روی کارت نمایش</div>
              </div>
              <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">ترند</div>
                <div class="toggle-desc">is_trending</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-images"></i> رسانه نمایشی</div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">تصویر کارت (Thumbnail) <span class="req">*</span></label>
              <div class="upload-zone">
                <i class="fa-solid fa-image"></i>
                <div class="upload-zone-label">کلیک کنید یا بکشید اینجا</div>
                <div style="font-size:11px;color:var(--text3);">PNG, JPG — حداقل ۶۰۰×۶۰۰px</div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">تصویر Cover</label>
              <div class="upload-zone">
                <i class="fa-solid fa-panorama"></i>
                <div class="upload-zone-label">کلیک کنید یا بکشید اینجا</div>
                <div style="font-size:11px;color:var(--text3);">PNG, JPG — ۱۴۰۰×۶۰۰px</div>
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">نمونه خروجی‌ها <span class="hint">حداکثر ۱۰ تصویر</span></label>
            <div class="sample-grid">
              <div class="sample-cell"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-plus"></i></div>
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">نوع رسانه</label>
              <select class="form-select">
                <option>photo — عکس</option>
                <option>video — ویدیو</option>
                <option>both — هر دو</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">لینک ویدیوی پیش‌نمایش <span class="hint">اختیاری</span></label>
              <input type="text" class="form-input form-input-en" placeholder="https://...">
            </div>
          </div>
        </div>

      </div><!-- /panel-1 -->

      <!-- ══════════════════════════════════════
           STEP 2 — AI Config + Input Schema
      ══════════════════════════════════════ -->
      <div class="step-panel" id="panel-2">

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-microchip"></i> پایپ‌لاین هوش مصنوعی</div>

          <div style="font-size:11px;font-weight:700;color:var(--text3);margin-bottom:8px;letter-spacing:.5px;">مدل اصلی (Primary)</div>
          <div class="model-card" style="margin-bottom:18px;">
            <i class="fa-solid fa-grip-vertical model-drag"></i>
            <div style="flex:1;">
              <select class="form-select" style="margin-bottom:8px;">
                <option>black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
                <option>black-forest-labs/flux-kontext-pro — FLUX Kontext Pro</option>
                <option>openai/gpt-image-1 — GPT Image 1</option>
                <option>stability-ai/stable-diffusion-3.5 — SD 3.5</option>
                <option>ideogram-ai/ideogram-v2 — Ideogram V2</option>
                <option>recraft-ai/recraft-v3 — Recraft V3</option>
              </select>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="number" class="form-input" placeholder="timeout (ثانیه)" value="60">
                <select class="form-select">
                  <option>image_generation</option>
                  <option>image_editing</option>
                  <option>text_generation</option>
                </select>
              </div>
            </div>
            <span class="model-badge-primary"><i class="fa-solid fa-star" style="font-size:9px;"></i>&nbsp;Primary</span>
          </div>

          <div style="font-size:11px;font-weight:700;color:var(--text3);margin-bottom:8px;letter-spacing:.5px;">Fallback ها <span style="font-weight:400;font-size:10px;">(به ترتیب اجرا)</span></div>
          <div id="fallback-list">
            <div class="model-card" id="fb-1">
              <i class="fa-solid fa-grip-vertical model-drag"></i>
              <div style="flex:1;">
                <select class="form-select">
                  <option>stability-ai/stable-diffusion-3.5 — SD 3.5</option>
                  <option>black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
                  <option>ideogram-ai/ideogram-v2 — Ideogram V2</option>
                  <option>recraft-ai/recraft-v3 — Recraft V3</option>
                </select>
              </div>
              <span class="model-badge-fallback">Fallback ۱</span>
              <button class="model-remove" onclick="removeFallback('fb-1')"><i class="fa-solid fa-xmark"></i> حذف</button>
            </div>
          </div>
          <button class="add-field-btn" onclick="addFallback()">
            <i class="fa-solid fa-plus"></i> افزودن Fallback
          </button>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-terminal"></i> تنظیمات پرامپت</div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Prompt Template <span class="req">*</span></label>
            <textarea class="form-textarea form-input-en" id="prompt-template" style="min-height:110px;font-family:monospace;font-size:12px;" placeholder="A professional portrait of {gender} named {name}, wearing {clothing_style}, {background}, photorealistic, 8k, sharp focus"></textarea>
            <div style="margin-top:6px;">
              <div style="font-size:10px;color:var(--text3);margin-bottom:5px;">متغیرهای موجود — کلیک کنید تا درج شود:</div>
              <div style="display:flex;flex-wrap:wrap;gap:5px;" id="var-chips">
                <span class="prompt-var-chip" onclick="insertVar('{name}')">&#123;name&#125;</span>
                <span class="prompt-var-chip" onclick="insertVar('{gender}')">&#123;gender&#125;</span>
                <span class="prompt-var-chip" onclick="insertVar('{clothing_style}')">&#123;clothing_style&#125;</span>
                <span class="prompt-var-chip" onclick="insertVar('{background}')">&#123;background&#125;</span>
                <span class="prompt-var-chip" onclick="insertVar('{birth_date}')">&#123;birth_date&#125;</span>
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Negative Prompt <span class="hint">اختیاری</span></label>
            <textarea class="form-textarea form-input-en" style="min-height:60px;font-family:monospace;font-size:12px;" placeholder="blurry, low quality, distorted face, cartoon, watermark"></textarea>
          </div>

          <div class="form-row form-row-3">
            <div class="toggle-row">
              <div>
                <div class="toggle-label">نمایش پرامپت به کاربر</div>
                <div class="toggle-desc">show_prompt_to_user</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">Face Swap</div>
                <div class="toggle-desc">face_swap_enabled</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">پایپ‌لاین چند مرحله‌ای</div>
                <div class="toggle-desc">multi_step_pipeline</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-table-list"></i> سازنده فیلدهای ورودی (Input Schema)</div>

          <div class="field-header">
            <div></div>
            <div class="field-col-label">field_id</div>
            <div class="field-col-label">برچسب فارسی</div>
            <div class="field-col-label">نوع</div>
            <div class="field-col-label" style="text-align:center;">اجباری</div>
            <div></div>
          </div>

          <div id="input-fields-list">
            <div class="field-row" id="field-row-1">
              <i class="fa-solid fa-grip-vertical" style="color:var(--text3);font-size:11px;cursor:grab;"></i>
              <input type="text" class="form-input form-input-en" placeholder="field_id" value="user_photo">
              <input type="text" class="form-input" placeholder="برچسب" value="عکس شما">
              <select class="form-select"><option selected>image_upload</option><option>video_upload</option><option>text</option><option>textarea</option><option>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
              <div style="display:flex;justify-content:center;"><button class="req-toggle on" onclick="toggleReq(this)"><i class="fa-solid fa-check"></i></button></div>
              <button class="field-remove" onclick="removeField('field-row-1')"><i class="fa-solid fa-trash"></i></button>
            </div>
            <div class="field-row" id="field-row-2">
              <i class="fa-solid fa-grip-vertical" style="color:var(--text3);font-size:11px;cursor:grab;"></i>
              <input type="text" class="form-input form-input-en" placeholder="field_id" value="name">
              <input type="text" class="form-input" placeholder="برچسب" value="نام">
              <select class="form-select"><option>image_upload</option><option>video_upload</option><option selected>text</option><option>textarea</option><option>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
              <div style="display:flex;justify-content:center;"><button class="req-toggle" onclick="toggleReq(this)"><i class="fa-solid fa-minus"></i></button></div>
              <button class="field-remove" onclick="removeField('field-row-2')"><i class="fa-solid fa-trash"></i></button>
            </div>
            <div class="field-row" id="field-row-3">
              <i class="fa-solid fa-grip-vertical" style="color:var(--text3);font-size:11px;cursor:grab;"></i>
              <input type="text" class="form-input form-input-en" placeholder="field_id" value="clothing_style">
              <input type="text" class="form-input" placeholder="برچسب" value="استایل لباس">
              <select class="form-select"><option>image_upload</option><option>video_upload</option><option>text</option><option>textarea</option><option selected>select</option><option>multi_select</option><option>date</option><option>number</option><option>url</option><option>color_picker</option><option>toggle</option><option>file_upload</option></select>
              <div style="display:flex;justify-content:center;"><button class="req-toggle on" onclick="toggleReq(this)"><i class="fa-solid fa-check"></i></button></div>
              <button class="field-remove" onclick="removeField('field-row-3')"><i class="fa-solid fa-trash"></i></button>
            </div>
          </div>

          <div style="margin-top:10px;">
            <button class="add-field-btn" onclick="addInputField()">
              <i class="fa-solid fa-plus"></i> افزودن فیلد جدید
            </button>
          </div>
        </div>

      </div><!-- /panel-2 -->

      <!-- ══════════════════════════════════════
           STEP 3 — Output + Pricing + Display
      ══════════════════════════════════════ -->
      <div class="step-panel" id="panel-3">

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-file-export"></i> تنظیمات خروجی (Output Config)</div>

          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">نوع خروجی <span class="req">*</span></label>
              <select class="form-select">
                <option>image — عکس</option>
                <option>video — ویدیو</option>
                <option>image+video — هر دو</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">فرمت</label>
              <select class="form-select">
                <option>jpg</option><option>png</option><option>webp</option><option>mp4</option><option>webm</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">تعداد خروجی</label>
              <input type="number" class="form-input" value="1" min="1" max="10">
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">رزولوشن</label>
              <select class="form-select">
                <option>512×512</option><option>768×768</option><option selected>1024×1024</option><option>1080×1920</option><option>1920×1080</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">نسبت ابعاد</label>
              <select class="form-select">
                <option selected>1:1 — مربع</option><option>9:16 — عمودی</option><option>16:9 — افقی</option><option>4:5 — اینستاگرام</option>
              </select>
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">روش تحویل</label>
              <select class="form-select"><option>instant — آنی</option><option>queued — صف انتظار</option></select>
            </div>
            <div class="form-group">
              <label class="form-label">زمان تخمینی (ثانیه)</label>
              <input type="number" class="form-input" value="30">
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="toggle-row">
              <div>
                <div class="toggle-label">واترمارک</div>
                <div class="toggle-desc">watermark_enabled</div>
              </div>
              <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
            </div>
            <div class="form-group">
              <label class="form-label">موقعیت واترمارک</label>
              <select class="form-select"><option>corner — گوشه</option><option>center — وسط</option><option>none — بدون</option></select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-coins"></i> قیمت‌گذاری (Pricing)</div>

          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">مدل قیمت‌گذاری <span class="req">*</span></label>
              <select class="form-select" onchange="toggleCreditCost(this)">
                <option value="per_credit">per_credit — کردیتی</option>
                <option value="free">free — رایگان</option>
                <option value="subscription_only">subscription_only — اشتراک</option>
              </select>
            </div>
            <div class="form-group" id="credit-cost-wrap">
              <label class="form-label">هزینه (کردیت) <span class="req">*</span></label>
              <input type="number" class="form-input" value="5" min="1">
            </div>
            <div class="form-group">
              <label class="form-label">سطح قیمتی</label>
              <select class="form-select">
                <option>basic — پایه</option><option selected>standard — استاندارد</option><option>premium — پریمیوم</option>
              </select>
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">درصد تخفیف <span class="hint">۰ = بدون تخفیف</span></label>
              <input type="number" class="form-input" value="0" min="0" max="100">
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">رایگان (is_free)</div>
                <div class="toggle-desc">بدون نیاز به کردیت</div>
              </div>
              <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fa-solid fa-palette"></i> تنظیمات نمایش (Display Config)</div>

          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">حالت نمایش</label>
              <select class="form-select"><option selected>card — کارت</option><option>featured — ویژه</option><option>minimal — ساده</option></select>
            </div>
            <div class="form-group">
              <label class="form-label">شکل کارت</label>
              <select class="form-select"><option selected>portrait — عمودی</option><option>landscape — افقی</option><option>square — مربع</option></select>
            </div>
            <div class="form-group">
              <label class="form-label">چیدمان گالری</label>
              <select class="form-select"><option selected>grid — شبکه</option><option>masonry — آبشاری</option><option>carousel — اسلایدر</option></select>
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">برچسب کارت <span class="hint">مثلاً: پرفروش، ویژه</span></label>
              <input type="text" class="form-input" placeholder="مثال: پرفروش">
            </div>
            <div class="form-group">
              <label class="form-label">پلتفرم</label>
              <select class="form-select"><option>web</option><option>mobile</option><option selected>both — هر دو</option></select>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">رنگ accent محصول</label>
            <div class="color-swatches">
              <div class="color-swatch active" style="background:#a07af5;" data-color="#a07af5" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#0BBF53;" data-color="#0BBF53" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#f05c5c;" data-color="#f05c5c" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#f5923a;" data-color="#f5923a" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#3b82f6;" data-color="#3b82f6" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#ec4899;" data-color="#ec4899" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#14b8a6;" data-color="#14b8a6" onclick="pickColor(this)"></div>
              <div class="color-swatch" style="background:#eab308;" data-color="#eab308" onclick="pickColor(this)"></div>
            </div>
            <input type="text" class="form-input form-input-en" id="color-val" value="#a07af5" style="margin-top:6px;max-width:140px;">
          </div>

          <div class="toggle-row" style="max-width:340px;">
            <div>
              <div class="toggle-label">نمایش Before/After</div>
              <div class="toggle-desc">show_before_after slider</div>
            </div>
            <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
          </div>
        </div>

      </div><!-- /panel-3 -->

    </main>

    <!-- ══ STICKY FOOTER ══ -->
    <div class="form-footer">
      <button class="btn-lg btn-secondary" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> مرحله قبل
      </button>
      <div style="font-size:12px;color:var(--text3);">
        مرحله <strong style="color:var(--text);" id="step-label-num">۱</strong> از ۳
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn-lg btn-secondary" onclick="saveDraft(event)">
          <i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس
        </button>
        <button class="btn-lg btn-primary" id="btn-next" onclick="nextStep()">
          مرحله بعد <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button class="btn-lg btn-success" id="btn-submit" onclick="submitProduct()" style="display:none;">
          <i class="fa-solid fa-check"></i> ثبت نهایی محصول
        </button>
      </div>
    </div>

  </div><!-- /admin-main -->
</div>
@endsection

@section('scripts')
<script>
/* ── STEPPER ── */
let cur = 1;
const pNums = ['۱','۲','۳'];

function goStep(n) {
  for (let i = 1; i <= 3; i++) {
    document.getElementById('panel-' + i).classList.toggle('active', i === n);
    const tab = document.getElementById('step-tab-' + i);
    const num = document.getElementById('step-num-' + i);
    tab.classList.toggle('active', i === n);
    tab.classList.toggle('done', i < n);
    if (i < n) {
      num.innerHTML = '<i class="fa-solid fa-check" style="font-size:10px;"></i>';
    } else {
      num.textContent = pNums[i - 1];
    }
  }
  cur = n;
  document.getElementById('btn-prev').style.display   = n > 1 ? 'inline-flex' : 'none';
  document.getElementById('btn-next').style.display   = n < 3 ? 'inline-flex' : 'none';
  document.getElementById('btn-submit').style.display = n === 3 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = pNums[n - 1];
  window.scrollTo({top: 0, behavior: 'smooth'});
}
function nextStep() { if (cur < 3) goStep(cur + 1); }
function prevStep() { if (cur > 1) goStep(cur - 1); }

/* ── TAGS ── */
function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;
  const chip = document.createElement('span');
  chip.className = 'tag-chip';
  chip.innerHTML = v + '<button type="button" onclick="this.parentElement.remove()">×</button>';
  document.getElementById('tags-wrap').insertBefore(chip, inp);
  inp.value = '';
}

/* ── AUTO SLUG ── */
function autoSlug(el) {
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

/* ── SUBCATEGORY ── */
const subcats = {
  PEOPLE:['Professional','Fashion','Lifestyle','Fitness','Beauty'],
  BUSINESS:['Real Estate','Medical','Lawyer','Coach','Education','Entrepreneur'],
  EVENTS:['Birthday','Wedding','Graduation','Valentine','Nowruz','Yalda','Eid'],
  FAMILY:['خانواده کامل','والدین','نوزاد'],
  KIDS:['کودک','نوجوان'],
  PETS:['سگ','گربه','سایر'],
  ENTERTAINMENT:['Anime / Manga','Disney / Pixar','Superhero / Fantasy'],
  PRODUCTS:['محصول تجاری','فود','فشن'],
  AVATARS:['واقع‌گرایانه','کارتونی','سه‌بعدی'],
  VIDEOS:['Personal','Business','Social Media','Kids','AI Tools'],
};
function updateSubcat() {
  const cat = document.getElementById('cat-main').value;
  const sel = document.getElementById('cat-sub');
  sel.innerHTML = '<option value="">انتخاب کنید</option>';
  (subcats[cat] || []).forEach(s => {
    const o = document.createElement('option');
    o.textContent = s; sel.appendChild(o);
  });
}

/* ── FALLBACKS ── */
let fbIdx = 1;
const fbLabels = ['۱','۲','۳','۴','۵'];
function addFallback() {
  fbIdx++;
  const id = 'fb-' + fbIdx;
  const el = document.createElement('div');
  el.className = 'model-card'; el.id = id;
  el.innerHTML = `
    <i class="fa-solid fa-grip-vertical model-drag"></i>
    <div style="flex:1;">
      <select class="form-select">
        <option>recraft-ai/recraft-v3 — Recraft V3</option>
        <option>ideogram-ai/ideogram-v2 — Ideogram V2</option>
        <option>stability-ai/stable-diffusion-3.5 — SD 3.5</option>
        <option>black-forest-labs/flux-1.1-pro — FLUX 1.1 Pro</option>
      </select>
    </div>
    <span class="model-badge-fallback">Fallback ${fbLabels[fbIdx-1] || fbIdx}</span>
    <button class="model-remove" onclick="removeFallback('${id}')"><i class="fa-solid fa-xmark"></i> حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(el);
}
function removeFallback(id) { const el = document.getElementById(id); if(el) el.remove(); }

/* ── INPUT SCHEMA BUILDER ── */
let fieldIdx = 3;
const typeOptions = ['image_upload','video_upload','text','textarea','select','multi_select','date','number','url','color_picker','toggle','file_upload'];
function addInputField() {
  fieldIdx++;
  const id = 'field-row-' + fieldIdx;
  const opts = typeOptions.map(t => `<option>${t}</option>`).join('');
  const el = document.createElement('div');
  el.className = 'field-row'; el.id = id;
  el.innerHTML = `
    <i class="fa-solid fa-grip-vertical" style="color:var(--text3);font-size:11px;cursor:grab;"></i>
    <input type="text" class="form-input form-input-en" placeholder="field_id">
    <input type="text" class="form-input" placeholder="برچسب فارسی">
    <select class="form-select">${opts}</select>
    <div style="display:flex;justify-content:center;"><button class="req-toggle" onclick="toggleReq(this)"><i class="fa-solid fa-minus"></i></button></div>
    <button class="field-remove" onclick="removeField('${id}')"><i class="fa-solid fa-trash"></i></button>
  `;
  document.getElementById('input-fields-list').appendChild(el);
}
function removeField(id) { const el = document.getElementById(id); if(el) el.remove(); }
function toggleReq(btn) {
  btn.classList.toggle('on');
  btn.innerHTML = btn.classList.contains('on') ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-minus"></i>';
}

/* ── PROMPT VAR INSERT ── */
function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

/* ── COLOR PICKER ── */
function pickColor(el) {
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('color-val').value = el.dataset.color;
}

/* ── CREDIT COST TOGGLE ── */
function toggleCreditCost(sel) {
  const w = document.getElementById('credit-cost-wrap');
  w.style.opacity = sel.value === 'per_credit' ? '1' : '0.35';
  w.style.pointerEvents = sel.value === 'per_credit' ? '' : 'none';
}

/* ── ACTIONS ── */
function saveDraft(e) {
  const btn = e.currentTarget;
  const orig = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-check"></i> ذخیره شد';
  btn.style.color = 'var(--green)';
  setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
}
function submitProduct() {
  if (confirm('محصول به‌صورت نهایی ثبت شود؟')) {
    window.location.href = '/admin/products';
  }
}
</script> 
@endsection
