@extends('layouts.admin')
@section('title', 'مدیریت بلاگرها — AIPIX Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#fff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
*{box-sizing:border-box;}
body{font-family:'Vazirmatn',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}
.admin-wrap{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;right:0;bottom:0;width:256px;background:var(--s1);border-left:1px solid var(--b1);display:flex;flex-direction:column;overflow-y:auto;z-index:100;scrollbar-width:none;}
.admin-sidebar::-webkit-scrollbar{display:none;}
.admin-main{margin-right:256px;flex:1;display:flex;flex-direction:column;}
.admin-header{position:sticky;top:0;z-index:50;background:var(--s1);border-bottom:1px solid var(--b1);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;}
.admin-content{padding:24px;flex:1;}
.snav-section{font-size:9px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--text3);padding:12px 16px 4px;}
.snav-item{display:flex;align-items:center;gap:10px;padding:0 8px;margin:1px 6px;border-radius:8px;cursor:pointer;transition:background .15s;height:38px;text-decoration:none;}
.snav-item:hover{background:var(--s2);}.snav-item.active{background:rgba(160,122,245,.12);}
.snav-icon{width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text2);flex-shrink:0;}
.snav-item.active .snav-icon{color:var(--accent);}
.snav-label{flex:1;font-size:12.5px;font-weight:600;color:var(--text2);}
.snav-item.active .snav-label{color:var(--text);}
.snav-sub-item{display:flex;align-items:center;gap:8px;padding:6px 10px;margin:1px 6px 1px 30px;border-radius:6px;text-decoration:none;transition:background .15s;}
.snav-sub-item:hover{background:var(--s2);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;}.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;border:none;}
.hdr-btn-primary:hover{opacity:.9;}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.stat-label{font-size:11px;color:var(--text2);margin-bottom:5px;}
.stat-val{font-size:22px;font-weight:800;line-height:1;}
.stat-sub{font-size:10px;color:var(--text3);margin-top:3px;}

.tabs{display:flex;background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:3px;gap:2px;margin-bottom:16px;}
.tab-btn{flex:1;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--text2);font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.tab-btn.active{background:var(--b2);color:var(--text);}
.tab-content{display:none;}.tab-content.active{display:block;}

.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-search{flex:1;min-width:160px;position:relative;}
.filter-search input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 32px 7px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;}
.filter-search input:focus{border-color:var(--accent);}
.filter-search .icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px;}
.filter-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;cursor:pointer;direction:rtl;}

.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.bl-table{width:100%;border-collapse:collapse;}
.bl-table th{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:11px 14px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.bl-table td{padding:13px 14px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.bl-table tr:last-child td{border-bottom:none;}
.bl-table tr:hover td{background:rgba(255,255,255,.012);}

.blogger-cell{display:flex;align-items:center;gap:10px;}
.blogger-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;}
.blogger-name{font-size:13px;font-weight:700;color:var(--text);}
.blogger-handle{font-size:10.5px;color:var(--accent);}
.link-code{font-size:11px;font-family:monospace;background:var(--bg);border:1px solid var(--b1);border-radius:6px;padding:2px 8px;color:var(--text2);cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:border-color .15s;}
.link-code:hover{border-color:var(--b2);}
.mini-bar{height:5px;border-radius:99px;background:var(--b1);overflow:hidden;width:70px;display:inline-block;vertical-align:middle;}
.mini-fill{height:100%;border-radius:99px;background:var(--accent);}
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}
.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;margin-left:3px;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}
.payout-status{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:600;}

/* Requests tab */
.request-card{background:var(--s1);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;margin-bottom:10px;display:flex;align-items:center;gap:16px;}
.req-info{flex:1;}
.req-name{font-size:13px;font-weight:700;color:var(--text);margin-bottom:3px;}
.req-meta{font-size:11px;color:var(--text3);}
.req-actions{display:flex;gap:8px;}
.req-btn{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;}
.req-btn-accept{background:rgba(11,191,83,.12);color:var(--green);border:1px solid rgba(11,191,83,.25);}
.req-btn-reject{background:rgba(240,92,92,.08);color:var(--red);border:1px solid rgba(240,92,92,.2);}

/* modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:520px;max-width:calc(100vw - 32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);}
.form-input,.form-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;width:100%;}
.form-input:focus,.form-select:focus{border-color:var(--accent);}
</style>
@endpush

@section('content')
<div class="admin-wrap">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:rgba(11,191,83,.08);border:1px solid rgba(11,191,83,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <img src="/assets/img/iconvatanai.svg" alt="Vatan AI" style="width:22px;height:22px;">
      </div>
      <div><div style="font-size:14px;font-weight:800;">وطن استودیو</div><div style="font-size:9px;color:var(--text3);letter-spacing:2.5px;text-transform:uppercase;">Admin Panel</div></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--b1);">
      <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">م</div>
      <div style="flex:1;"><div style="font-size:12px;font-weight:700;">محسن رضایی</div><div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div></div>
    </div>
    <nav style="flex:1;padding:8px 0;">
      <a href="/admin/dashboard" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div><div class="snav-label">مرکز فرماندهی</div></a>
      <div class="snav-section">مدیریت محصولات</div>
      <a href="/admin/products" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-box-open"></i></div><div class="snav-label">محصولات</div></a>
      <a href="/admin/products/dashboard" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">داشبورد محصولات</div></a>
      <a href="/admin/products/create" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">ثبت محصول جدید</div></a>
      <a href="/admin/products/categories" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div></a>
      <a href="/admin/products/pricing" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div></a>
      <a href="/admin/orders" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div></a>
      <div class="snav-section">بازاریابی</div>
      <a href="/admin/bloggers" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div><span style="font-size:9px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);padding:1px 6px;border-radius:6px;">۳ درخواست</span></a>
      <div class="snav-section">آنالیز</div>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div class="breadcrumb">
        <a href="/admin/dashboard"><i class="fa-solid fa-house" style="font-size:11px;"></i></a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="current">بلاگرها</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline"><i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i> خروجی CSV</button>
      <button class="hdr-btn hdr-btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus" style="font-size:11px;"></i> افزودن بلاگر</button>
    </header>

    <main class="admin-content">
      <div style="margin-bottom:20px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">مدیریت بلاگرها</div>
        <div style="font-size:13px;color:var(--text3);">سیستم رفرال، لینک‌های اختصاصی، کارمزد و تسویه</div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">بلاگرهای فعال</div>
          <div class="stat-val" style="color:var(--green);">۲۴</div>
          <div class="stat-sub">۳ درخواست جدید</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">رفرال این ماه</div>
          <div class="stat-val" style="color:var(--accent);">۶۴۸</div>
          <div class="stat-sub">۲۶٪ از کل سفارشات</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">درآمد از رفرال</div>
          <div class="stat-val" style="color:var(--text);">۳۷۴M</div>
          <div class="stat-sub">کارمزد پرداختی: ۵۶M</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">در انتظار تسویه</div>
          <div class="stat-val" style="color:var(--orange);">۲۸.۴M</div>
          <div class="stat-sub">۵ بلاگر — این هفته</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab(this,'tab-active')">بلاگرهای فعال <span style="font-size:10px;margin-right:4px;background:var(--b1);padding:1px 7px;border-radius:99px;">۲۴</span></button>
        <button class="tab-btn" onclick="switchTab(this,'tab-requests')">درخواست‌های جدید <span style="font-size:10px;margin-right:4px;background:rgba(245,146,58,.15);color:var(--orange);padding:1px 7px;border-radius:99px;">۳</span></button>
        <button class="tab-btn" onclick="switchTab(this,'tab-payments')">تسویه حساب</button>
      </div>

      <!-- Tab: Active Bloggers -->
      <div class="tab-content active" id="tab-active">
        <div class="filter-bar">
          <div class="filter-search">
            <input type="text" placeholder="جستجو با نام یا هندل...">
            <i class="fa-solid fa-magnifying-glass icon"></i>
          </div>
          <select class="filter-select"><option>همه وضعیت‌ها</option><option>فعال</option><option>کند</option><option>غیرفعال</option></select>
          <select class="filter-select"><option>مرتب‌سازی: رفرال</option><option>درآمد</option><option>کارمزد</option></select>
        </div>
        <div class="table-wrap">
          <table class="bl-table">
            <thead>
              <tr>
                <th>بلاگر</th>
                <th>لینک رفرال</th>
                <th>رفرال ماه</th>
                <th>درآمد</th>
                <th>کارمزد %</th>
                <th>در انتظار</th>
                <th>وضعیت</th>
                <th style="width:80px;">عملیات</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(160,122,245,.15);color:var(--accent);">ع</div><div><div class="blogger-name">علی محمدی</div><div class="blogger-handle">@tech_ali</div></div></div></td>
                <td><span class="link-code" onclick="copyLink('vatan.ai/r/tech-ali')"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/tech-ali</span></td>
                <td><span style="font-weight:700;color:var(--text);">۲۱۴</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:100%;"></div></div></td>
                <td style="color:var(--green);font-weight:600;">۱۰۷M</td>
                <td><span class="badge badge-purple">۱۵٪</span></td>
                <td style="color:var(--orange);font-weight:600;">۸.۲M</td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn" title="جزئیات"><i class="fa-solid fa-eye"></i></button><button class="action-btn" title="ویرایش"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(236,72,153,.12);color:#ec4899;">م</div><div><div class="blogger-name">مینا کریمی</div><div class="blogger-handle">@design_mina</div></div></div></td>
                <td><span class="link-code" onclick="copyLink('vatan.ai/r/design-mina')"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/design-mina</span></td>
                <td><span style="font-weight:700;color:var(--text);">۱۸۷</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:87%;background:#ec4899;"></div></div></td>
                <td style="color:var(--green);font-weight:600;">۱۸۷M</td>
                <td><span class="badge badge-purple">۲۰٪</span></td>
                <td style="color:var(--orange);font-weight:600;">۱۲.۴M</td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button><button class="action-btn"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(11,191,83,.12);color:var(--green);">ر</div><div><div class="blogger-name">رویا حسینی</div><div class="blogger-handle">@art_roya</div></div></div></td>
                <td><span class="link-code"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/art-roya</span></td>
                <td><span style="font-weight:700;color:var(--text);">۱۲۳</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:57%;background:var(--green);"></div></div></td>
                <td style="color:var(--green);font-weight:600;">۳۷M</td>
                <td><span class="badge badge-purple">۱۵٪</span></td>
                <td style="color:var(--orange);font-weight:600;">۳.۶M</td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button><button class="action-btn"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(59,130,246,.12);color:#3b82f6;">س</div><div><div class="blogger-name">سارا رضایی</div><div class="blogger-handle">@startup_sara</div></div></div></td>
                <td><span class="link-code"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/startup-sara</span></td>
                <td><span style="font-weight:700;color:var(--text);">۸۶</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:40%;background:#3b82f6;"></div></div></td>
                <td style="color:var(--green);font-weight:600;">۶۸M</td>
                <td><span class="badge badge-blue">۱۰٪</span></td>
                <td style="color:var(--text3);font-weight:600;">—</td>
                <td><span class="badge badge-green">فعال</span></td>
                <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button><button class="action-btn"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(245,146,58,.12);color:var(--orange);">ن</div><div><div class="blogger-name">نیما احمدی</div><div class="blogger-handle">@photo_nima</div></div></div></td>
                <td><span class="link-code"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/photo-nima</span></td>
                <td><span style="font-weight:700;color:var(--text);">۵۴</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:25%;background:var(--orange);"></div></div></td>
                <td style="color:var(--green);font-weight:600;">۲۷M</td>
                <td><span class="badge badge-purple">۱۵٪</span></td>
                <td style="color:var(--orange);font-weight:600;">۲.۱M</td>
                <td><span class="badge badge-orange">کند</span></td>
                <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button><button class="action-btn"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(240,92,92,.12);color:var(--red);">ف</div><div><div class="blogger-name">فریده نوری</div><div class="blogger-handle">@fashion_farida</div></div></div></td>
                <td><span class="link-code"><i class="fa-solid fa-link" style="font-size:9px;"></i> vatan.ai/r/farida</span></td>
                <td><span style="font-weight:700;color:var(--text);">۲۸</span> <div class="mini-bar" style="margin-right:6px;"><div class="mini-fill" style="width:13%;background:var(--red);"></div></div></td>
                <td style="color:var(--text3);font-weight:600;">۸M</td>
                <td><span class="badge badge-gray">۱۰٪</span></td>
                <td style="color:var(--text3);">—</td>
                <td><span class="badge badge-red">غیرفعال</span></td>
                <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button><button class="action-btn"><i class="fa-solid fa-pen"></i></button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Requests -->
      <div class="tab-content" id="tab-requests">
        <div class="request-card">
          <div class="blogger-avatar" style="background:rgba(160,122,245,.15);color:var(--accent);width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0;">آ</div>
          <div class="req-info">
            <div class="req-name">آرش کمالی — <span style="color:var(--accent);">@arash_ai</span></div>
            <div class="req-meta">اینستاگرام · ۴۸K فالوور · حوزه: تکنولوژی و AI · درخواست کارمزد: ۱۵٪</div>
          </div>
          <div class="req-actions">
            <button class="req-btn req-btn-accept" onclick="acceptRequest(this)"><i class="fa-solid fa-check" style="font-size:10px;"></i> تأیید</button>
            <button class="req-btn req-btn-reject" onclick="rejectRequest(this)"><i class="fa-solid fa-xmark" style="font-size:10px;"></i> رد</button>
          </div>
        </div>
        <div class="request-card">
          <div class="blogger-avatar" style="background:rgba(11,191,83,.12);color:var(--green);width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0;">ش</div>
          <div class="req-info">
            <div class="req-name">شیوا رستمی — <span style="color:var(--green);">@shiva_photo</span></div>
            <div class="req-meta">یوتوب · ۱۲۰K سابسکرایبر · حوزه: عکاسی و پرتره · درخواست کارمزد: ۲۰٪</div>
          </div>
          <div class="req-actions">
            <button class="req-btn req-btn-accept" onclick="acceptRequest(this)"><i class="fa-solid fa-check" style="font-size:10px;"></i> تأیید</button>
            <button class="req-btn req-btn-reject" onclick="rejectRequest(this)"><i class="fa-solid fa-xmark" style="font-size:10px;"></i> رد</button>
          </div>
        </div>
        <div class="request-card">
          <div class="blogger-avatar" style="background:rgba(59,130,246,.12);color:#3b82f6;width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0;">ک</div>
          <div class="req-info">
            <div class="req-name">کامران صادقی — <span style="color:#3b82f6;">@kamran_design</span></div>
            <div class="req-meta">تلگرام · ۸۵K عضو · حوزه: طراحی و برندینگ · درخواست کارمزد: ۱۰٪</div>
          </div>
          <div class="req-actions">
            <button class="req-btn req-btn-accept" onclick="acceptRequest(this)"><i class="fa-solid fa-check" style="font-size:10px;"></i> تأیید</button>
            <button class="req-btn req-btn-reject" onclick="rejectRequest(this)"><i class="fa-solid fa-xmark" style="font-size:10px;"></i> رد</button>
          </div>
        </div>
      </div>

      <!-- Tab: Payments -->
      <div class="tab-content" id="tab-payments">
        <div class="table-wrap">
          <table class="bl-table">
            <thead>
              <tr><th>بلاگر</th><th>دوره</th><th>رفرال</th><th>درآمد رفرال</th><th>کارمزد</th><th>وضعیت</th><th>عملیات</th></tr>
            </thead>
            <tbody>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(236,72,153,.12);color:#ec4899;width:28px;height:28px;font-size:11px;">م</div><span style="font-weight:600;color:var(--text);">@design_mina</span></div></td>
                <td style="font-size:11.5px;">خرداد ۱۴۰۵</td>
                <td>۱۸۷</td>
                <td style="color:var(--green);font-weight:600;">۱۸۷M</td>
                <td style="color:var(--orange);font-weight:700;">۱۲.۴M</td>
                <td><span class="badge badge-orange">در انتظار</span></td>
                <td><button class="hdr-btn hdr-btn-primary" style="height:28px;font-size:11px;padding:0 10px;">پرداخت</button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(160,122,245,.15);color:var(--accent);width:28px;height:28px;font-size:11px;">ع</div><span style="font-weight:600;color:var(--text);">@tech_ali</span></div></td>
                <td style="font-size:11.5px;">خرداد ۱۴۰۵</td>
                <td>۲۱۴</td>
                <td style="color:var(--green);font-weight:600;">۱۰۷M</td>
                <td style="color:var(--orange);font-weight:700;">۸.۲M</td>
                <td><span class="badge badge-orange">در انتظار</span></td>
                <td><button class="hdr-btn hdr-btn-primary" style="height:28px;font-size:11px;padding:0 10px;">پرداخت</button></td>
              </tr>
              <tr>
                <td><div class="blogger-cell"><div class="blogger-avatar" style="background:rgba(11,191,83,.12);color:var(--green);width:28px;height:28px;font-size:11px;">ر</div><span style="font-weight:600;color:var(--text);">@art_roya</span></div></td>
                <td style="font-size:11.5px;">اردیبهشت ۱۴۰۵</td>
                <td>۹۸</td>
                <td style="color:var(--green);font-weight:600;">۲۹.۴M</td>
                <td style="color:var(--text2);font-weight:700;">۲.۹M</td>
                <td><span class="badge badge-green">پرداخت شده</span></td>
                <td><button class="action-btn" title="رسید"><i class="fa-solid fa-file-invoice"></i></button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Add Blogger Modal -->
<div class="modal-bg" id="add-modal" onclick="if(event.target===this)closeAddModal()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-bullhorn" style="color:var(--accent);margin-left:8px;"></i>افزودن بلاگر جدید</span>
      <button class="modal-close" onclick="closeAddModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="form-group"><label class="form-label">نام کامل</label><input type="text" class="form-input" placeholder="مثال: علی محمدی"></div>
    <div class="form-group"><label class="form-label">هندل / نام کانال</label><input type="text" class="form-input form-input-ltr" placeholder="@username" style="direction:ltr;text-align:left;"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="form-group"><label class="form-label">پلتفرم</label><select class="form-select"><option>اینستاگرام</option><option>یوتوب</option><option>تلگرام</option><option>توییتر</option></select></div>
      <div class="form-group"><label class="form-label">کارمزد (%)</label><input type="number" class="form-input" value="15" min="5" max="30"></div>
    </div>
    <div class="form-group"><label class="form-label">ایمیل</label><input type="email" class="form-input" placeholder="email@example.com" style="direction:ltr;text-align:left;"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
      <button class="hdr-btn hdr-btn-outline" onclick="closeAddModal()">انصراف</button>
      <button class="hdr-btn hdr-btn-primary" onclick="saveNewBlogger()">ثبت بلاگر</button>
    </div>
  </div>
</div>

@endsection
@section('scripts')
<script>
function switchTab(btn, id) {
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById(id).classList.add('active');
}
function openAddModal(){document.getElementById('add-modal').classList.add('open');}
function closeAddModal(){document.getElementById('add-modal').classList.remove('open');}
function saveNewBlogger(){closeAddModal();alert('بلاگر با موفقیت اضافه شد');}
function copyLink(url){navigator.clipboard.writeText('https://'+url).then(()=>alert('لینک کپی شد: '+url));}
function acceptRequest(btn){btn.closest('.request-card').style.opacity='.5';btn.textContent='✓ تأیید شد';}
function rejectRequest(btn){btn.closest('.request-card').style.opacity='.5';btn.textContent='✗ رد شد';}
</script>
@endsection
