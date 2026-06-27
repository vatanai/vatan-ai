@extends('layouts.admin')
@section('title', 'قیمت‌گذاری محصولات — AIPIX Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#ffffff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
*{box-sizing:border-box;}
body{font-family:'IRANSansXFaNum',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}
.admin-wrap{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;right:0;bottom:0;width:256px;background:var(--s1);border-left:1px solid var(--b1);display:flex;flex-direction:column;overflow-y:auto;z-index:100;scrollbar-width:thin;scrollbar-color:var(--b2) transparent;}
.admin-main{margin-right:256px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.admin-header{position:sticky;top:0;z-index:50;background:var(--s1);border-bottom:1px solid var(--b1);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;}
.admin-content{padding:24px;flex:1;}
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
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item.active .snav-dot,.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;}
.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-primary{background:var(--accent);color:#fff;}
.hdr-btn-primary:hover{background:#8f68e0;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.stat-label{font-size:12px;color:var(--text2);margin-bottom:6px;}
.stat-val{font-size:26px;font-weight:700;line-height:1;}
.stat-sub{font-size:11px;color:var(--text3);margin-top:4px;}
.stat-sub.up{color:var(--green);}

/* ── TABS ── */
.tab-bar{display:flex;gap:4px;background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:5px;margin-bottom:20px;}
.tab-btn{flex:1;padding:8px 14px;border-radius:8px;border:none;background:none;font-family:'IRANSansXFaNum',sans-serif;font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer;transition:all .15s;}
.tab-btn.active{background:var(--s1);color:var(--text);border:1px solid var(--b1);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── CARD ── */
.card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;margin-bottom:16px;overflow:hidden;}
.card-head{padding:16px 20px;border-bottom:1px solid var(--b1);display:flex;align-items:center;gap:10px;}
.card-title{font-size:13px;font-weight:700;color:var(--text);flex:1;}
.card-title i{color:var(--accent);margin-left:6px;}
.card-body{padding:20px;}

/* ── PRICING TABLE ── */
.pricing-table{width:100%;border-collapse:collapse;}
.pricing-table th{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);white-space:nowrap;}
.pricing-table td{padding:13px 16px;border-bottom:1px solid var(--b1);font-size:13px;color:var(--text2);vertical-align:middle;}
.pricing-table tr:last-child td{border-bottom:none;}
.pricing-table tr:hover td{background:rgba(255,255,255,.02);}
.product-cell{display:flex;align-items:center;gap:10px;}
.product-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
.product-name{font-size:13px;font-weight:600;color:var(--text);}
.product-cat{font-size:10.5px;color:var(--text3);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.credit-val{font-size:16px;font-weight:800;color:var(--text);}
.credit-label{font-size:10px;color:var(--text3);}
.inline-input{background:var(--s1);border:1px solid var(--b1);border-radius:7px;padding:6px 10px;font-size:13px;font-weight:700;color:var(--text);font-family:'IRANSansXFaNum',sans-serif;outline:none;width:72px;text-align:center;transition:border-color .15s;}
.inline-input:focus{border-color:var(--accent);}
.action-btns{display:flex;gap:6px;}
.action-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;transition:all .15s;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}
.action-btn.save-btn:hover{border-color:var(--green);color:var(--green);}

/* ── DISCOUNT SECTION ── */
.discount-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.discount-card{background:var(--s1);border:1px solid var(--b1);border-radius:12px;padding:18px;}
.discount-card-title{font-size:12px;font-weight:700;color:var(--text2);margin-bottom:14px;display:flex;align-items:center;gap:6px;}
.discount-card-title i{color:var(--orange);}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:12px;}
.form-label{font-size:11.5px;font-weight:600;color:var(--text2);}
.form-input,.form-select{background:var(--s2);border:1px solid var(--b1);border-radius:8px;padding:8px 12px;font-size:13px;color:var(--text);font-family:'IRANSansXFaNum',sans-serif;outline:none;direction:rtl;transition:border-color .15s;width:100%;}
.form-input:focus,.form-select:focus{border-color:var(--orange);}
.form-input::placeholder{color:var(--text3);}
.btn-orange{background:var(--orange);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-family:'IRANSansXFaNum',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:background .15s;}
.btn-orange:hover{background:#d97f2e;}

/* active discounts list */
.discount-row{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--s2);border:1px solid var(--b1);border-radius:10px;margin-bottom:8px;}
.discount-pct{font-size:22px;font-weight:900;color:var(--orange);min-width:44px;}
.discount-info{flex:1;}
.discount-name{font-size:12.5px;font-weight:700;color:var(--text);}
.discount-meta{font-size:11px;color:var(--text3);margin-top:2px;}
.discount-expire{font-size:11px;padding:2px 8px;border-radius:6px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.discount-del{background:none;border:none;cursor:pointer;color:var(--text3);font-size:13px;transition:color .15s;padding:4px;}
.discount-del:hover{color:var(--red);}

/* ── TIERS SECTION ── */
.tiers-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.tier-card{border-radius:12px;padding:20px;border:1px solid var(--b1);position:relative;overflow:hidden;}
.tier-card.basic{background:linear-gradient(135deg,rgba(78,78,108,.15),var(--s2));}
.tier-card.standard{background:linear-gradient(135deg,rgba(160,122,245,.12),var(--s2));border-color:rgba(160,122,245,.25);}
.tier-card.premium{background:linear-gradient(135deg,rgba(234,179,8,.1),var(--s2));border-color:rgba(234,179,8,.2);}
.tier-name{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;}
.tier-card.basic .tier-name{color:var(--text3);}
.tier-card.standard .tier-name{color:var(--accent);}
.tier-card.premium .tier-name{color:#eab308;}
.tier-price{font-size:28px;font-weight:900;margin-bottom:4px;}
.tier-price-sub{font-size:11px;color:var(--text3);margin-bottom:14px;}
.tier-feature{font-size:12px;color:var(--text2);padding:5px 0;border-bottom:1px solid var(--b1);display:flex;align-items:center;gap:6px;}
.tier-feature:last-child{border-bottom:none;}
.tier-feature i{font-size:10px;}
.tier-card.basic .tier-feature i{color:var(--text3);}
.tier-card.standard .tier-feature i{color:var(--accent);}
.tier-card.premium .tier-feature i{color:#eab308;}
.tier-edit-btn{margin-top:14px;width:100%;padding:7px;border-radius:8px;border:1px solid var(--b1);background:rgba(255,255,255,.03);font-family:'IRANSansXFaNum',sans-serif;font-size:12px;font-weight:600;color:var(--text2);cursor:pointer;transition:all .15s;}
.tier-edit-btn:hover{border-color:var(--b2);color:var(--text);}

/* ── MODAL ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:460px;max-width:calc(100vw - 32px);padding:24px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.modal-close:hover{color:var(--text);}
.btn-full{width:100%;padding:10px;border-radius:9px;border:none;font-family:'IRANSansXFaNum',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;margin-top:4px;}
.btn-primary{background:var(--accent);color:#fff;}
.btn-primary:hover{background:#8f68e0;}
</style>
@endpush

@section('content')
<div class="admin-wrap">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900;color:#fff;box-shadow:0 0 16px rgba(11,191,83,.3);">و</div>
      <div><div style="font-size:14px;font-weight:800;">وطن استودیو</div><div style="font-size:9px;color:var(--text3);letter-spacing:2.5px;text-transform:uppercase;">Admin Panel</div></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--b1);">
      <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">م</div>
      <div style="flex:1;"><div style="font-size:12px;font-weight:700;">محسن رضایی</div><div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div></div>
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
        <div class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">داشبورد محصولات</div><span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span></div>
        <a href="/admin/products" class="snav-sub-item" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">لیست محصولات</div></a>
        <a href="/admin/products/create" class="snav-sub-item" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">ثبت محصول جدید</div></a>
        <a href="/admin/products/categories" class="snav-sub-item" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div></a>
        <a href="/admin/products/pricing" class="snav-sub-item active" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div></a>
      </div>
      <div class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div><span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span></div>
      <div class="snav-section">هوش مصنوعی</div>
      <div class="snav-item"><div class="snav-icon"><i class="fa-solid fa-microchip"></i></div><div class="snav-label">مدیریت مدل‌ها</div><span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span></div>
      <div style="height:1px;background:var(--b1);margin:8px 12px;"></div>
      <div class="snav-item"><div class="snav-icon"><i class="fa-solid fa-gear"></i></div><div class="snav-label">تنظیمات</div></div>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div class="breadcrumb">
        <a href="/admin/dashboard"><i class="fa-solid fa-house" style="font-size:11px;"></i></a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="/admin/products">محصولات</a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="current">قیمت‌گذاری</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline" onclick="openDiscountModal()">
        <i class="fa-solid fa-tag" style="font-size:11px;"></i> تخفیف جدید
      </button>
      <button class="hdr-btn hdr-btn-primary" onclick="saveAll()">
        <i class="fa-solid fa-floppy-disk" style="font-size:11px;"></i> ذخیره همه
      </button>
    </header>

    <main class="admin-content">
      <div style="margin-bottom:22px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">قیمت‌گذاری و تخفیفات</div>
        <div style="font-size:13px;color:var(--text3);">مدیریت قیمت کردیتی محصولات، تخفیفات فعال، و سطوح اشتراک</div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">میانگین قیمت</div>
          <div class="stat-val" style="color:var(--accent);">۵.۶</div>
          <div class="stat-sub">کردیت / محصول</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">تخفیفات فعال</div>
          <div class="stat-val" style="color:var(--orange);">۳</div>
          <div class="stat-sub">روی ۴ محصول</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">درآمد این ماه</div>
          <div class="stat-val" style="color:var(--green);">۴۸.۲M</div>
          <div class="stat-sub up"><i class="fa-solid fa-arrow-up" style="font-size:10px;"></i> ۱۲٪ نسبت به ماه قبل</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">محبوب‌ترین پلن</div>
          <div class="stat-val" style="color:var(--text);font-size:20px;margin-top:3px;">Standard</div>
          <div class="stat-sub">۶۸٪ کاربران</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('prices',this)"><i class="fa-solid fa-coins"></i> قیمت محصولات</button>
        <button class="tab-btn" onclick="switchTab('discounts',this)"><i class="fa-solid fa-tag"></i> تخفیفات</button>
        <button class="tab-btn" onclick="switchTab('tiers',this)"><i class="fa-solid fa-layer-group"></i> سطوح اشتراک</button>
      </div>

      <!-- ══ TAB: PRICES ══ -->
      <div class="tab-panel active" id="tab-prices">
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-coins"></i>قیمت کردیتی محصولات</div>
            <div style="font-size:12px;color:var(--text3);">برای ویرایش روی قیمت کلیک کنید</div>
          </div>
          <table class="pricing-table">
            <thead>
              <tr>
                <th>محصول</th>
                <th>مدل قیمت</th>
                <th>قیمت (کردیت)</th>
                <th>سطح</th>
                <th>تخفیف</th>
                <th>وضعیت</th>
                <th style="width:80px;">عملیات</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(160,122,245,.12);color:#a07af5;"><i class="fa-solid fa-user-tie"></i></div><div><div class="product-name">عکس حرفه‌ای لینکدین</div><div class="product-cat">PEOPLE</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="5" min="1"></td>
                <td><span class="badge badge-gray">standard</span></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn" title="ذخیره"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-briefcase"></i></div><div><div class="product-name">طراحی بنر کسب‌وکار</div><div class="product-cat">BUSINESS</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="8" min="1"></td>
                <td><span class="badge" style="background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);">premium</span></td>
                <td><span class="badge badge-orange">۱۵٪</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(240,92,92,.12);color:#f05c5c;"><i class="fa-solid fa-cake-candles"></i></div><div><div class="product-name">کارت تبریک تولد</div><div class="product-cat">EVENTS</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="3" min="1"></td>
                <td><span class="badge badge-gray">basic</span></td>
                <td><span class="badge badge-orange">۲۰٪</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(245,146,58,.12);color:#f5923a;"><i class="fa-solid fa-star"></i></div><div><div class="product-name">آواتار انیمه</div><div class="product-cat">ENTERTAINMENT</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="5" min="1"></td>
                <td><span class="badge badge-gray">standard</span></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-orange">پیش‌نویس</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(11,191,83,.12);color:#0BBF53;"><i class="fa-solid fa-house-user"></i></div><div><div class="product-name">عکس خانوادگی</div><div class="product-cat">FAMILY</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="6" min="1"></td>
                <td><span class="badge badge-gray">standard</span></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(160,122,245,.12);color:#a07af5;"><i class="fa-solid fa-seedling"></i></div><div><div class="product-name">کارت مبارکباد نوروز</div><div class="product-cat">EVENTS</div></div></div></td>
                <td><span class="badge badge-green">free</span></td>
                <td><span style="color:var(--text3);font-size:12px;">رایگان</span></td>
                <td><span class="badge badge-gray">basic</span></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-robot"></i></div><div><div class="product-name">آواتار دیجیتال</div><div class="product-cat">AVATARS</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="10" min="1"></td>
                <td><span class="badge" style="background:rgba(234,179,8,.08);color:#eab308;border:1px solid rgba(234,179,8,.2);">premium</span></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
              <tr>
                <td><div class="product-cell"><div class="product-icon" style="background:var(--b1);color:var(--text3);"><i class="fa-solid fa-graduation-cap"></i></div><div><div class="product-name">کارت فارغ‌التحصیلی</div><div class="product-cat">EVENTS</div></div></div></td>
                <td><span class="badge badge-purple">per_credit</span></td>
                <td><input class="inline-input" type="number" value="4" min="1"></td>
                <td><span class="badge badge-gray">standard</span></td>
                <td><span class="badge badge-orange">۱۰٪</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><div class="action-btns"><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ TAB: DISCOUNTS ══ -->
      <div class="tab-panel" id="tab-discounts">
        <div class="discount-grid">
          <!-- Active discounts -->
          <div>
            <div class="card-head" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px 14px 0 0;"><div class="card-title"><i class="fa-solid fa-tag"></i>تخفیفات فعال</div></div>
            <div style="background:var(--s2);border:1px solid var(--b1);border-top:none;border-radius:0 0 14px 14px;padding:16px;">
              <div class="discount-row">
                <div class="discount-pct">۲۰٪</div>
                <div class="discount-info">
                  <div class="discount-name">تخفیف تولد</div>
                  <div class="discount-meta">کارت تبریک تولد — کد: BDAY20</div>
                </div>
                <span class="discount-expire">۱۴۰۵/۰۶/۳۱</span>
                <button class="discount-del" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="discount-row">
                <div class="discount-pct">۱۵٪</div>
                <div class="discount-info">
                  <div class="discount-name">تخفیف کسب‌وکار</div>
                  <div class="discount-meta">بنر کسب‌وکار — کد: BIZ15</div>
                </div>
                <span class="discount-expire">۱۴۰۵/۰۵/۱۵</span>
                <button class="discount-del" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="discount-row">
                <div class="discount-pct">۱۰٪</div>
                <div class="discount-info">
                  <div class="discount-name">تخفیف فارغ‌التحصیلی</div>
                  <div class="discount-meta">کارت فارغ‌التحصیلی — کد: GRAD10</div>
                </div>
                <span class="discount-expire">۱۴۰۵/۰۶/۱۵</span>
                <button class="discount-del" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div style="text-align:center;padding:12px;color:var(--text3);font-size:12px;">۳ تخفیف فعال — ۰ منقضی‌شده</div>
            </div>
          </div>

          <!-- Add discount form -->
          <div class="discount-card">
            <div class="discount-card-title"><i class="fa-solid fa-plus-circle"></i>تعریف تخفیف جدید</div>
            <div class="form-group">
              <label class="form-label">نام تخفیف</label>
              <input type="text" class="form-input" placeholder="مثال: تخفیف عید نوروز">
            </div>
            <div class="form-group">
              <label class="form-label">کد تخفیف</label>
              <input type="text" class="form-input" style="direction:ltr;text-align:left;" placeholder="NOWRUZ1404">
            </div>
            <div class="form-group">
              <label class="form-label">درصد تخفیف</label>
              <input type="number" class="form-input" placeholder="۰–۱۰۰" min="1" max="100">
            </div>
            <div class="form-group">
              <label class="form-label">محصول مربوطه</label>
              <select class="form-select">
                <option>همه محصولات</option>
                <option>عکس حرفه‌ای لینکدین</option>
                <option>طراحی بنر کسب‌وکار</option>
                <option>کارت تبریک تولد</option>
                <option>آواتار دیجیتال</option>
                <option>کارت فارغ‌التحصیلی</option>
              </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;" class="form-group">
              <div>
                <label class="form-label">تاریخ شروع</label>
                <input type="text" class="form-input" placeholder="۱۴۰۵/۰۱/۰۱">
              </div>
              <div>
                <label class="form-label">تاریخ پایان</label>
                <input type="text" class="form-input" placeholder="۱۴۰۵/۰۶/۳۱">
              </div>
            </div>
            <button class="btn-orange" style="width:100%;margin-top:4px;" onclick="addDiscount()">
              <i class="fa-solid fa-plus"></i> ثبت تخفیف
            </button>
          </div>
        </div>
      </div>

      <!-- ══ TAB: TIERS ══ -->
      <div class="tab-panel" id="tab-tiers">
        <div class="tiers-grid">
          <!-- Basic -->
          <div class="tier-card basic">
            <div class="tier-name">Basic</div>
            <div class="tier-price" style="color:var(--text2);">رایگان</div>
            <div class="tier-price-sub">برای همه کاربران</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> دسترسی به محصولات Basic</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> ۵ کردیت رایگان ماهانه</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> واترمارک روی خروجی</div>
            <div class="tier-feature"><i class="fa-regular fa-xmark"></i> بدون Fallback AI</div>
            <div class="tier-feature"><i class="fa-regular fa-xmark"></i> بدون ذخیره‌سازی</div>
            <button class="tier-edit-btn" onclick="openTierModal('Basic')"><i class="fa-solid fa-pen" style="font-size:11px;"></i> ویرایش پلن</button>
          </div>
          <!-- Standard -->
          <div class="tier-card standard">
            <div class="tier-name">Standard</div>
            <div class="tier-price" style="color:var(--accent);">۱۹۹K</div>
            <div class="tier-price-sub">تومان / ماه</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> دسترسی به همه محصولات</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> ۵۰ کردیت ماهانه</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> بدون واترمارک</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> Fallback AI فعال</div>
            <div class="tier-feature"><i class="fa-regular fa-xmark"></i> بدون API دسترسی</div>
            <button class="tier-edit-btn" onclick="openTierModal('Standard')"><i class="fa-solid fa-pen" style="font-size:11px;"></i> ویرایش پلن</button>
          </div>
          <!-- Premium -->
          <div class="tier-card premium">
            <div class="tier-name">Premium</div>
            <div class="tier-price" style="color:#eab308;">۴۹۹K</div>
            <div class="tier-price-sub">تومان / ماه</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> همه محصولات + Early Access</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> ۲۰۰ کردیت ماهانه</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> بدون واترمارک</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> Fallback AI + اولویت صف</div>
            <div class="tier-feature"><i class="fa-solid fa-check"></i> دسترسی API</div>
            <button class="tier-edit-btn" onclick="openTierModal('Premium')"><i class="fa-solid fa-pen" style="font-size:11px;"></i> ویرایش پلن</button>
          </div>
        </div>

        <!-- credit packages -->
        <div class="card" style="margin-top:20px;">
          <div class="card-head"><div class="card-title"><i class="fa-solid fa-wallet"></i>بسته‌های کردیت (خرید مستقل)</div></div>
          <table class="pricing-table">
            <thead>
              <tr><th>بسته</th><th>تعداد کردیت</th><th>قیمت (تومان)</th><th>بونوس</th><th>وضعیت</th><th>عملیات</th></tr>
            </thead>
            <tbody>
              <tr>
                <td style="font-weight:700;color:var(--text);">بسته کوچک</td>
                <td><input class="inline-input" type="number" value="20"></td>
                <td><input class="inline-input" style="width:90px;" type="text" value="۴۹,۰۰۰"></td>
                <td><span style="color:var(--text3);">—</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></td>
              </tr>
              <tr>
                <td style="font-weight:700;color:var(--text);">بسته متوسط</td>
                <td><input class="inline-input" type="number" value="60"></td>
                <td><input class="inline-input" style="width:90px;" type="text" value="۱۲۹,۰۰۰"></td>
                <td><span class="badge badge-orange">+۱۰ رایگان</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></td>
              </tr>
              <tr>
                <td style="font-weight:700;color:var(--text);">بسته بزرگ</td>
                <td><input class="inline-input" type="number" value="150"></td>
                <td><input class="inline-input" style="width:90px;" type="text" value="۲۷۹,۰۰۰"></td>
                <td><span class="badge badge-orange">+۳۰ رایگان</span></td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn save-btn"><i class="fa-solid fa-check"></i></button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- DISCOUNT MODAL -->
<div class="modal-bg" id="discount-modal" onclick="if(event.target===this)closeDiscountModal()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-tag" style="color:var(--orange);margin-left:8px;"></i>تخفیف جدید</span>
      <button class="modal-close" onclick="closeDiscountModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="form-group"><label class="form-label">نام تخفیف</label><input type="text" class="form-input" placeholder="مثال: جشنواره تابستان"></div>
    <div class="form-group"><label class="form-label">کد تخفیف</label><input type="text" class="form-input" style="direction:ltr;text-align:left;" placeholder="SUMMER25"></div>
    <div class="form-group"><label class="form-label">درصد تخفیف</label><input type="number" class="form-input" placeholder="۱۰" min="1" max="100"></div>
    <button class="btn-full btn-primary" onclick="closeDiscountModal()"><i class="fa-solid fa-check"></i> ثبت</button>
  </div>
</div>

<!-- TIER MODAL -->
<div class="modal-bg" id="tier-modal" onclick="if(event.target===this)closeTierModal()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-layer-group" style="color:var(--accent);margin-left:8px;"></i>ویرایش پلن: <span id="tier-modal-name"></span></span>
      <button class="modal-close" onclick="closeTierModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="form-group"><label class="form-label">قیمت ماهانه (تومان)</label><input type="text" class="form-input" id="tier-price-input" placeholder="۱۹۹,۰۰۰"></div>
    <div class="form-group"><label class="form-label">تعداد کردیت ماهانه</label><input type="number" class="form-input" placeholder="۵۰"></div>
    <button class="btn-full btn-primary" onclick="closeTierModal()"><i class="fa-solid fa-check"></i> ذخیره</button>
  </div>
</div>

@endsection

@section('scripts')
<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}
function openDiscountModal(){document.getElementById('discount-modal').classList.add('open');}
function closeDiscountModal(){document.getElementById('discount-modal').classList.remove('open');}
function openTierModal(name){document.getElementById('tier-modal-name').textContent=name;document.getElementById('tier-modal').classList.add('open');}
function closeTierModal(){document.getElementById('tier-modal').classList.remove('open');}
function addDiscount(){}
function saveAll(){
  const btn=event.currentTarget;const orig=btn.innerHTML;
  btn.innerHTML='<i class="fa-solid fa-check"></i> ذخیره شد';
  setTimeout(()=>{btn.innerHTML=orig;},2000);
}
</script>
@endsection
