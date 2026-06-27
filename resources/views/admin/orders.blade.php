@extends('layouts.admin')
@section('title', 'مدیریت سفارشات — AIPIX Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#fff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
*{box-sizing:border-box;}
body{font-family:'IRANSansXFaNum',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}
.admin-wrap{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;right:0;bottom:0;width:256px;background:var(--s1);border-left:1px solid var(--b1);display:flex;flex-direction:column;overflow-y:auto;z-index:100;scrollbar-width:thin;scrollbar-color:var(--b2) transparent;}
.admin-main{margin-right:256px;flex:1;display:flex;flex-direction:column;}
.admin-header{position:sticky;top:0;z-index:50;background:var(--s1);border-bottom:1px solid var(--b1);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;}
.admin-content{padding:24px;flex:1;}
.snav-section{font-size:9px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--text3);padding:12px 16px 4px;}
.snav-item{display:flex;align-items:center;gap:10px;padding:0 8px;margin:1px 6px;border-radius:8px;cursor:pointer;transition:background .15s;height:38px;text-decoration:none;}
.snav-item:hover{background:var(--s2);} .snav-item.active{background:rgba(160,122,245,.12);}
.snav-icon{width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text2);flex-shrink:0;}
.snav-item.active .snav-icon{color:var(--accent);}
.snav-label{flex:1;font-size:12.5px;font-weight:600;color:var(--text2);}
.snav-item.active .snav-label{color:var(--text);}
.snav-sub-item{display:flex;align-items:center;gap:8px;padding:6px 10px;margin:1px 6px 1px 30px;border-radius:6px;cursor:pointer;transition:background .15s;text-decoration:none;}
.snav-sub-item:hover{background:var(--s2);} .snav-sub-item.active{background:rgba(160,122,245,.1);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item.active .snav-dot,.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;} .breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}

/* stats */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:15px 16px;}
.stat-label{font-size:11px;color:var(--text2);margin-bottom:5px;}
.stat-val{font-size:22px;font-weight:800;line-height:1;}
.stat-sub{font-size:10px;color:var(--text3);margin-top:3px;display:flex;align-items:center;gap:3px;}
.stat-sub.up{color:var(--green);}

/* filter */
.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:13px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.filter-search{flex:1;min-width:180px;position:relative;}
.filter-search input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 34px 7px 12px;font-size:13px;color:var(--text);font-family:'IRANSansXFaNum',sans-serif;outline:none;direction:rtl;}
.filter-search input:focus{border-color:var(--accent);}
.filter-search .icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px;}
.filter-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'IRANSansXFaNum',sans-serif;outline:none;cursor:pointer;direction:rtl;min-width:120px;}
.filter-select:focus{border-color:var(--accent);}

/* table */
.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.orders-table{width:100%;border-collapse:collapse;}
.orders-table th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:11px 14px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);white-space:nowrap;}
.orders-table td{padding:12px 14px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.orders-table tr:last-child td{border-bottom:none;}
.orders-table tr:hover td{background:rgba(255,255,255,.015);}
.order-id{font-size:11px;font-weight:700;color:var(--text3);font-family:monospace;}
.user-cell{display:flex;align-items:center;gap:8px;}
.user-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;}
.user-name{font-size:12.5px;font-weight:600;color:var(--text);}
.user-email{font-size:10.5px;color:var(--text3);}
.product-cell{display:flex;align-items:center;gap:8px;}
.product-icon-sm{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;white-space:nowrap;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}
.credit-pill{display:inline-flex;align-items:center;gap:3px;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.15);border-radius:99px;padding:2px 8px;font-size:11px;font-weight:600;}
.referral-pill{display:inline-flex;align-items:center;gap:4px;background:rgba(11,191,83,.06);color:var(--green);border:1px solid rgba(11,191,83,.15);border-radius:7px;padding:2px 7px;font-size:10px;font-weight:600;}
.time-val{font-size:11px;color:var(--text2);}
.time-ms{font-size:10px;color:var(--text3);}
.action-btns{display:flex;gap:5px;}
.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}

/* pagination */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-top:1px solid var(--b1);}
.page-info{font-size:12px;color:var(--text3);}
.page-btns{display:flex;gap:4px;}
.page-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;font-weight:600;transition:all .15s;}
.page-btn:hover{border-color:var(--b2);color:var(--text);}
.page-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}

/* detail modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:flex-start;justify-content:center;padding-top:60px;overflow-y:auto;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:580px;max-width:calc(100vw - 32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.modal-close:hover{color:var(--text);}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--b1);}
.detail-row:last-child{border-bottom:none;}
.detail-key{font-size:12px;color:var(--text3);}
.detail-val{font-size:13px;font-weight:600;color:var(--text);}
.output-thumb{width:80px;height:80px;border-radius:10px;background:linear-gradient(135deg,rgba(160,122,245,.2),rgba(160,122,245,.05));display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--accent);flex-shrink:0;}
.json-block{background:var(--bg);border:1px solid var(--b1);border-radius:8px;padding:12px;font-family:monospace;font-size:11px;color:var(--text2);overflow-x:auto;line-height:1.6;white-space:pre;}
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
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="snav-label">محصولات</div>
        <i class="fa-solid fa-chevron-left" style="font-size:9px;color:var(--text3);"></i>
      </div>
      <div class="snav-item active">
        <div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="snav-label">سفارشات</div>
      </div>
      <div class="snav-section">هوش مصنوعی</div>
      <div class="snav-item"><div class="snav-icon"><i class="fa-solid fa-microchip"></i></div><div class="snav-label">مدیریت مدل‌ها</div><span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span></div>
      <div class="snav-section">آنالیز</div>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
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
        <span class="current">سفارشات</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline">
        <i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i> خروجی CSV
      </button>
    </header>

    <main class="admin-content">
      <div style="margin-bottom:20px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">مدیریت سفارشات</div>
        <div style="font-size:13px;color:var(--text3);">تمام سفارشات AI — وضعیت پردازش، رفرال بلاگرها، و جزئیات خروجی</div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">کل سفارشات</div>
          <div class="stat-val" style="color:var(--text);">۲,۴۸۱</div>
          <div class="stat-sub up"><i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> این ماه</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">موفق</div>
          <div class="stat-val" style="color:var(--green);">۲,۳۹۴</div>
          <div class="stat-sub up">۹۶.۵٪ نرخ موفقیت</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">در حال پردازش</div>
          <div class="stat-val" style="color:var(--orange);">۱۲</div>
          <div class="stat-sub">الان در صف</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">ناموفق</div>
          <div class="stat-val" style="color:var(--red);">۷۵</div>
          <div class="stat-sub">۳.۵٪ نرخ خطا</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">از طریق بلاگر</div>
          <div class="stat-val" style="color:var(--accent);">۶۴۸</div>
          <div class="stat-sub">۲۶٪ رفرال</div>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <div class="filter-search">
          <input type="text" placeholder="جستجو با شناسه، نام کاربر، یا محصول..." oninput="filterOrders()">
          <i class="fa-solid fa-magnifying-glass icon"></i>
        </div>
        <select class="filter-select" onchange="filterOrders()">
          <option value="">همه وضعیت‌ها</option>
          <option>completed</option>
          <option>processing</option>
          <option>failed</option>
          <option>pending</option>
        </select>
        <select class="filter-select" onchange="filterOrders()">
          <option value="">همه محصولات</option>
          <option>عکس لینکدین</option>
          <option>کارت تولد</option>
          <option>آواتار دیجیتال</option>
          <option>بنر کسب‌وکار</option>
        </select>
        <select class="filter-select">
          <option value="">همه منابع</option>
          <option>مستقیم</option>
          <option>بلاگر — رفرال</option>
        </select>
        <select class="filter-select">
          <option>امروز</option>
          <option>۷ روز</option>
          <option>۳۰ روز</option>
          <option>همه زمان‌ها</option>
        </select>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table class="orders-table">
          <thead>
            <tr>
              <th>شناسه</th>
              <th>کاربر</th>
              <th>محصول</th>
              <th>وضعیت</th>
              <th>مدل AI</th>
              <th>زمان</th>
              <th>کردیت</th>
              <th>منبع / بلاگر</th>
              <th style="width:70px;">جزئیات</th>
            </tr>
          </thead>
          <tbody>

            <tr>
              <td><div class="order-id">#ORD-۲۴۸۱</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(160,122,245,.15);color:var(--accent);">س</div><div><div class="user-name">سارا احمدی</div><div class="user-email">sara@email.com</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(160,122,245,.12);color:var(--accent);"><i class="fa-solid fa-user-tie"></i></div><span>عکس لینکدین</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-1.1-pro</span></td>
              <td><div class="time-val">۱۸ ثانیه</div><div class="time-ms">۱۸,۲۴۰ms</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۵</span></td>
              <td><span class="referral-pill"><i class="fa-solid fa-link" style="font-size:9px;"></i> @tech_ali</span></td>
              <td><button class="action-btn" onclick="openDetail(1)" title="جزئیات"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۸۰</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(11,191,83,.12);color:var(--green);">م</div><div><div class="user-name">محمد رضوی</div><div class="user-email">m.razavi@mail.ir</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(240,92,92,.12);color:#f05c5c;"><i class="fa-solid fa-cake-candles"></i></div><span>کارت تولد</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-1.1-pro</span></td>
              <td><div class="time-val">۱۲ ثانیه</div><div class="time-ms">۱۲,۱۰۵ms</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۳</span></td>
              <td><span style="font-size:11px;color:var(--text3);">مستقیم</span></td>
              <td><button class="action-btn" onclick="openDetail(2)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۹</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(59,130,246,.12);color:#3b82f6;">ن</div><div><div class="user-name">نیلوفر کریمی</div><div class="user-email">niloo.k@gmail.com</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-robot"></i></div><span>آواتار دیجیتال</span></div></td>
              <td><span class="badge badge-orange"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;در پردازش</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-kontext-pro</span></td>
              <td><div class="time-val">—</div><div class="time-ms" style="color:var(--orange);">در صف...</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۱۰</span></td>
              <td><span class="referral-pill"><i class="fa-solid fa-link" style="font-size:9px;"></i> @design_mina</span></td>
              <td><button class="action-btn" onclick="openDetail(3)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۸</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(245,146,58,.12);color:var(--orange);">ع</div><div><div class="user-name">علیرضا موسوی</div><div class="user-email">alireza.m@co.ir</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-briefcase"></i></div><span>بنر کسب‌وکار</span></div></td>
              <td><span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;ناموفق</span></td>
              <td><span style="font-size:10.5px;color:var(--red);font-family:monospace;">timeout</span></td>
              <td><div class="time-val">۶۰ ثانیه</div><div class="time-ms" style="color:var(--red);">timeout</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۸</span></td>
              <td><span style="font-size:11px;color:var(--text3);">مستقیم</span></td>
              <td><button class="action-btn" onclick="openDetail(4)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۷</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(160,122,245,.12);color:var(--accent);">ز</div><div><div class="user-name">زهرا حسینی</div><div class="user-email">zahra.h@email.com</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(160,122,245,.12);color:var(--accent);"><i class="fa-solid fa-user-tie"></i></div><span>عکس لینکدین</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-1.1-pro</span></td>
              <td><div class="time-val">۲۲ ثانیه</div><div class="time-ms">۲۲,۸۱۰ms</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۵</span></td>
              <td><span class="referral-pill"><i class="fa-solid fa-link" style="font-size:9px;"></i> @tech_ali</span></td>
              <td><button class="action-btn" onclick="openDetail(5)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۶</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(11,191,83,.12);color:var(--green);">ر</div><div><div class="user-name">رضا تهرانی</div><div class="user-email">r.tehrani@co.com</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(11,191,83,.12);color:var(--green);"><i class="fa-solid fa-house-user"></i></div><span>عکس خانوادگی</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text3);font-family:monospace;">sd-3.5 <span style="color:var(--orange);">(fallback)</span></span></td>
              <td><div class="time-val">۳۵ ثانیه</div><div class="time-ms">۳۵,۴۱۲ms</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۶</span></td>
              <td><span style="font-size:11px;color:var(--text3);">مستقیم</span></td>
              <td><button class="action-btn" onclick="openDetail(6)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۵</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(236,72,153,.12);color:#ec4899;">ف</div><div><div class="user-name">فاطمه نوری</div><div class="user-email">f.nouri@mail.ir</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(160,122,245,.12);color:var(--accent);"><i class="fa-solid fa-seedling"></i></div><span>کارت نوروز</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-1.1-pro</span></td>
              <td><div class="time-val">۹ ثانیه</div><div class="time-ms">۹,۳۴۰ms</div></td>
              <td><span class="badge" style="background:rgba(11,191,83,.06);color:var(--green);border:1px solid rgba(11,191,83,.15);font-size:10px;">رایگان</span></td>
              <td><span class="referral-pill"><i class="fa-solid fa-link" style="font-size:9px;"></i> @art_roya</span></td>
              <td><button class="action-btn" onclick="openDetail(7)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

            <tr>
              <td><div class="order-id">#ORD-۲۴۷۴</div></td>
              <td><div class="user-cell"><div class="user-avatar" style="background:rgba(234,179,8,.12);color:#eab308;">ا</div><div><div class="user-name">امیر شریفی</div><div class="user-email">a.sharifi@biz.ir</div></div></div></td>
              <td><div class="product-cell"><div class="product-icon-sm" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-robot"></i></div><span>آواتار دیجیتال</span></div></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;موفق</span></td>
              <td><span style="font-size:10.5px;color:var(--text2);font-family:monospace;">flux-kontext-pro</span></td>
              <td><div class="time-val">۲۸ ثانیه</div><div class="time-ms">۲۸,۰۵۵ms</div></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:9px;"></i> ۱۰</span></td>
              <td><span style="font-size:11px;color:var(--text3);">مستقیم</span></td>
              <td><button class="action-btn" onclick="openDetail(8)"><i class="fa-solid fa-eye"></i></button></td>
            </tr>

          </tbody>
        </table>
        <div class="pagination">
          <div class="page-info">نمایش ۱–۸ از ۲,۴۸۱ سفارش</div>
          <div class="page-btns">
            <div class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></div>
            <div class="page-btn active">۱</div>
            <div class="page-btn">۲</div>
            <div class="page-btn">۳</div>
            <div class="page-btn" style="width:auto;padding:0 8px;font-size:11px;">...</div>
            <div class="page-btn">۳۱۱</div>
            <div class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ORDER DETAIL MODAL -->
<div class="modal-bg" id="detail-modal" onclick="if(event.target===this)closeDetail()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-receipt" style="color:var(--accent);margin-left:8px;"></i>جزئیات سفارش <span id="modal-order-id" style="color:var(--accent);"></span></span>
      <button class="modal-close" onclick="closeDetail()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div style="display:flex;gap:14px;margin-bottom:18px;align-items:flex-start;">
      <div class="output-thumb" id="modal-thumb"><i class="fa-solid fa-image"></i></div>
      <div style="flex:1;">
        <div style="font-size:14px;font-weight:800;margin-bottom:4px;" id="modal-product"></div>
        <div style="font-size:12px;color:var(--text3);margin-bottom:8px;" id="modal-user"></div>
        <div id="modal-status-badge"></div>
      </div>
    </div>

    <div id="modal-details">
      <div class="detail-row"><div class="detail-key">مدل AI مورد استفاده</div><div class="detail-val" style="font-family:monospace;font-size:12px;" id="modal-model">—</div></div>
      <div class="detail-row"><div class="detail-key">زمان پردازش</div><div class="detail-val" id="modal-time">—</div></div>
      <div class="detail-row"><div class="detail-key">کردیت مصرفی</div><div class="detail-val" id="modal-credits">—</div></div>
      <div class="detail-row"><div class="detail-key">Fallback استفاده شد؟</div><div class="detail-val" id="modal-fallback">—</div></div>
      <div class="detail-row"><div class="detail-key">منبع / بلاگر</div><div class="detail-val" id="modal-referral">—</div></div>
      <div class="detail-row"><div class="detail-key">تاریخ ثبت</div><div class="detail-val" id="modal-date">—</div></div>
    </div>

    <div style="margin-top:14px;">
      <div style="font-size:11px;font-weight:700;color:var(--text3);letter-spacing:.5px;margin-bottom:6px;">ورودی کاربر (inputs)</div>
      <div class="json-block" id="modal-inputs">—</div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
const orderData = {
  1:{id:'#ORD-۲۴۸۱',product:'عکس حرفه‌ای لینکدین',user:'سارا احمدی — sara@email.com',status:'completed',model:'black-forest-labs/flux-1.1-pro',time:'۱۸,۲۴۰ms',credits:'۵',fallback:'خیر',referral:'@tech_ali',date:'۱۴۰۵/۰۳/۲۷ — ۱۴:۲۳',inputs:'{\n  "user_photo": ["https://cdn.../photo1.jpg"],\n  "name": "سارا احمدی",\n  "clothing_style": "formal",\n  "background": "office"\n}'},
  2:{id:'#ORD-۲۴۸۰',product:'کارت تبریک تولد',user:'محمد رضوی — m.razavi@mail.ir',status:'completed',model:'black-forest-labs/flux-1.1-pro',time:'۱۲,۱۰۵ms',credits:'۳',fallback:'خیر',referral:'مستقیم',date:'۱۴۰۵/۰۳/۲۷ — ۱۴:۱۸',inputs:'{\n  "user_photo": ["https://cdn.../photo.jpg"],\n  "name": "محمد",\n  "birth_date": "1370/05/15"\n}'},
  3:{id:'#ORD-۲۴۷۹',product:'آواتار دیجیتال',user:'نیلوفر کریمی — niloo.k@gmail.com',status:'processing',model:'در حال پردازش...',time:'—',credits:'۱۰',fallback:'—',referral:'@design_mina',date:'۱۴۰۵/۰۳/۲۷ — ۱۴:۱۵',inputs:'{\n  "user_photo": ["https://cdn.../img.png"],\n  "style": "realistic_3d"\n}'},
  4:{id:'#ORD-۲۴۷۸',product:'بنر کسب‌وکار',user:'علیرضا موسوی — alireza.m@co.ir',status:'failed',model:'timeout — هیچ مدلی پاسخ نداد',time:'۶۰,۰۰۰ms',credits:'۸',fallback:'بله (SD 3.5 هم timeout شد)',referral:'مستقیم',date:'۱۴۰۵/۰۳/۲۷ — ۱۴:۰۵',inputs:'{\n  "business_name": "آروین دیجیتال",\n  "slogan": "نوآوری در دیجیتال",\n  "color_theme": "#1e40af"\n}'},
  5:{id:'#ORD-۲۴۷۷',product:'عکس حرفه‌ای لینکدین',user:'زهرا حسینی — zahra.h@email.com',status:'completed',model:'black-forest-labs/flux-1.1-pro',time:'۲۲,۸۱۰ms',credits:'۵',fallback:'خیر',referral:'@tech_ali',date:'۱۴۰۵/۰۳/۲۷ — ۱۳:۵۵',inputs:'{\n  "user_photo": ["https://cdn.../zahra.jpg"],\n  "name": "زهرا حسینی",\n  "clothing_style": "business_casual"\n}'},
  6:{id:'#ORD-۲۴۷۶',product:'عکس خانوادگی',user:'رضا تهرانی — r.tehrani@co.com',status:'completed',model:'stability-ai/stable-diffusion-3.5 (fallback)',time:'۳۵,۴۱۲ms',credits:'۶',fallback:'بله — primary timeout شد',referral:'مستقیم',date:'۱۴۰۵/۰۳/۲۷ — ۱۳:۴۰',inputs:'{\n  "family_photo": ["https://cdn.../fam.jpg"],\n  "members": 4\n}'},
  7:{id:'#ORD-۲۴۷۵',product:'کارت نوروز',user:'فاطمه نوری — f.nouri@mail.ir',status:'completed',model:'black-forest-labs/flux-1.1-pro',time:'۹,۳۴۰ms',credits:'رایگان',fallback:'خیر',referral:'@art_roya',date:'۱۴۰۵/۰۳/۲۷ — ۱۳:۲۲',inputs:'{\n  "user_photo": ["https://cdn.../fatemeh.jpg"],\n  "name": "فاطمه"\n}'},
  8:{id:'#ORD-۲۴۷۴',product:'آواتار دیجیتال',user:'امیر شریفی — a.sharifi@biz.ir',status:'completed',model:'black-forest-labs/flux-kontext-pro',time:'۲۸,۰۵۵ms',credits:'۱۰',fallback:'خیر',referral:'مستقیم',date:'۱۴۰۵/۰۳/۲۷ — ۱۳:۱۰',inputs:'{\n  "user_photo": ["https://cdn.../amir.jpg"],\n  "style": "anime"\n}'},
};

function openDetail(n){
  const d=orderData[n];
  document.getElementById('modal-order-id').textContent=d.id;
  document.getElementById('modal-product').textContent=d.product;
  document.getElementById('modal-user').textContent=d.user;
  const statusMap={completed:'<span class="badge badge-green"><i class="fa-solid fa-check"></i>&nbsp;موفق</span>',processing:'<span class="badge badge-orange">در پردازش</span>',failed:'<span class="badge badge-red">ناموفق</span>'};
  document.getElementById('modal-status-badge').innerHTML=statusMap[d.status]||'';
  document.getElementById('modal-model').textContent=d.model;
  document.getElementById('modal-time').textContent=d.time;
  document.getElementById('modal-credits').textContent=d.credits+' کردیت';
  document.getElementById('modal-fallback').textContent=d.fallback;
  document.getElementById('modal-referral').innerHTML=d.referral.startsWith('@')?'<span class="referral-pill"><i class="fa-solid fa-link" style="font-size:9px;"></i> '+d.referral+'</span>':d.referral;
  document.getElementById('modal-date').textContent=d.date;
  document.getElementById('modal-inputs').textContent=d.inputs;
  document.getElementById('detail-modal').classList.add('open');
}
function closeDetail(){document.getElementById('detail-modal').classList.remove('open');}
function filterOrders(){}
</script>
@endsection
