@extends('layouts.admin')
@section('title', 'مدیریت کاربران — AIPIX Admin')

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
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;border:none;}

.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;}
.stat-label{font-size:10.5px;color:var(--text2);margin-bottom:5px;}
.stat-val{font-size:20px;font-weight:800;line-height:1;}
.stat-sub{font-size:9.5px;color:var(--text3);margin-top:3px;}

.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-search{flex:1;min-width:180px;position:relative;}
.filter-search input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 32px 7px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;}
.filter-search input:focus{border-color:var(--accent);}
.filter-search .icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px;}
.filter-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;cursor:pointer;}

.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.u-table{width:100%;border-collapse:collapse;}
.u-table th{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:10px 14px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.u-table td{padding:12px 14px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.u-table tr:last-child td{border-bottom:none;}
.u-table tr:hover td{background:rgba(255,255,255,.012);}

.user-cell{display:flex;align-items:center;gap:10px;}
.user-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;}
.user-name{font-size:13px;font-weight:700;color:var(--text);}
.user-phone{font-size:10.5px;color:var(--text3);font-family:monospace;direction:ltr;text-align:right;}

.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}

.credit-pill{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:var(--accent);}
.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;margin-left:3px;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}

/* User detail drawer */
.drawer-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;}
.drawer-bg.open{display:block;}
.drawer{position:fixed;top:0;left:0;bottom:0;width:400px;background:var(--s2);border-right:1px solid var(--b1);z-index:201;overflow-y:auto;transform:translateX(-100%);transition:transform .25s ease;}
.drawer.open{transform:translateX(0);}
.drawer-header{padding:20px;border-bottom:1px solid var(--b1);display:flex;align-items:center;gap:12px;}
.drawer-section{padding:16px 20px;border-bottom:1px solid var(--b1);}
.drawer-section-title{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--text3);margin-bottom:12px;}
.drow-row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(34,34,48,.5);}
.drow-row:last-child{border-bottom:none;}
.drow-key{font-size:11.5px;color:var(--text3);}
.drow-val{font-size:12px;font-weight:600;color:var(--text);}

/* credit modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:300;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:400px;max-width:calc(100vw - 32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);}
.form-input,.form-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;width:100%;}
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
      <div><div style="font-size:12px;font-weight:700;">محسن رضایی</div><div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div></div>
    </div>
    <nav style="flex:1;padding:8px 0;">
      <a href="/admin/dashboard" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div><div class="snav-label">مرکز فرماندهی</div></a>
      <div class="snav-section">مدیریت محصولات</div>
      <a href="/admin/products" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-box-open"></i></div><div class="snav-label">محصولات</div></a>
      <a href="/admin/orders" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div></a>
      <div class="snav-section">کاربران</div>
      <a href="/admin/users" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-users"></i></div><div class="snav-label">کاربران</div></a>
      <a href="/admin/payments" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-credit-card"></i></div><div class="snav-label">پرداخت‌ها</div></a>
      <div class="snav-section">بازاریابی</div>
      <a href="/admin/bloggers" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div></a>
      <div class="snav-section">آنالیز</div>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="font-size:14px;font-weight:700;">مدیریت کاربران</div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline"><i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i> خروجی CSV</button>
    </header>

    <main class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">کل کاربران</div>
          <div class="stat-val" style="color:var(--text);">۱۲,۴۸۱</div>
          <div class="stat-sub">+۲۴۱ این هفته</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">فعال (۳۰ روز)</div>
          <div class="stat-val" style="color:var(--green);">۳,۸۶۲</div>
          <div class="stat-sub">۳۱٪ نرخ فعالیت</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">پرمیوم</div>
          <div class="stat-val" style="color:var(--accent);">۸۴۷</div>
          <div class="stat-sub">۶.۸٪ از کل</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">مسدودشده</div>
          <div class="stat-val" style="color:var(--red);">۱۲</div>
          <div class="stat-sub">نیاز به بررسی</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">مجموع کردیت</div>
          <div class="stat-val" style="color:var(--orange);">۴۸K</div>
          <div class="stat-sub">در حساب کاربران</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <div class="filter-search">
          <input type="text" id="search-input" placeholder="جستجو با نام، شماره یا ایمیل..." oninput="filterUsers()">
          <i class="fa-solid fa-magnifying-glass icon"></i>
        </div>
        <select class="filter-select" id="status-filter" onchange="filterUsers()">
          <option value="">همه وضعیت‌ها</option>
          <option value="فعال">فعال</option>
          <option value="غیرفعال">غیرفعال</option>
          <option value="مسدود">مسدود</option>
        </select>
        <select class="filter-select" id="plan-filter" onchange="filterUsers()">
          <option value="">همه پلن‌ها</option>
          <option value="رایگان">رایگان</option>
          <option value="پرمیوم">پرمیوم</option>
        </select>
        <select class="filter-select" id="sort-filter" onchange="filterUsers()">
          <option value="orders">مرتب: سفارشات</option>
          <option value="credits">مرتب: کردیت</option>
          <option value="joined">مرتب: تاریخ عضویت</option>
        </select>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table class="u-table" id="users-table">
          <thead>
            <tr>
              <th>#</th>
              <th>کاربر</th>
              <th>وضعیت</th>
              <th>پلن</th>
              <th>کردیت</th>
              <th>سفارشات</th>
              <th>درآمد</th>
              <th>رفرال</th>
              <th>عضویت</th>
              <th style="width:90px;">عملیات</th>
            </tr>
          </thead>
          <tbody id="users-body">
            <!-- JS rendered -->
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;">
        <div style="font-size:12px;color:var(--text3);" id="pagination-info">نمایش ۱–۱۰ از ۱۲,۴۸۱</div>
        <div style="display:flex;gap:4px;">
          <button class="action-btn" style="width:32px;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></button>
          <button class="action-btn" style="width:32px;background:var(--accent);color:#fff;border-color:var(--accent);">۱</button>
          <button class="action-btn" style="width:32px;">۲</button>
          <button class="action-btn" style="width:32px;">۳</button>
          <span style="display:flex;align-items:center;padding:0 4px;font-size:12px;color:var(--text3);">...</span>
          <button class="action-btn" style="width:32px;">۱,۲۴۸</button>
          <button class="action-btn" style="width:32px;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></button>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- User Drawer -->
<div class="drawer-bg" id="drawer-bg" onclick="closeDrawer()"></div>
<div class="drawer" id="user-drawer">
  <div class="drawer-header">
    <div id="drawer-avatar" style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0;"></div>
    <div style="flex:1;">
      <div id="drawer-name" style="font-size:15px;font-weight:800;"></div>
      <div id="drawer-phone" style="font-size:11px;color:var(--text3);font-family:monospace;direction:ltr;text-align:right;"></div>
    </div>
    <button style="background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="drawer-section">
    <div class="drawer-section-title">اطلاعات اکانت</div>
    <div class="drow-row"><div class="drow-key">وضعیت</div><div id="dr-status" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">پلن</div><div id="dr-plan" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">کردیت فعلی</div><div id="dr-credits" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">تاریخ عضویت</div><div id="dr-joined" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">آخرین فعالیت</div><div id="dr-last" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">رفرال از</div><div id="dr-ref" class="drow-val"></div></div>
  </div>
  <div class="drawer-section">
    <div class="drawer-section-title">آمار</div>
    <div class="drow-row"><div class="drow-key">کل سفارشات</div><div id="dr-orders" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">سفارشات موفق</div><div id="dr-success" class="drow-val"></div></div>
    <div class="drow-row"><div class="drow-key">کل خرید کردیت</div><div id="dr-spend" class="drow-val"></div></div>
  </div>
  <div class="drawer-section" style="border-bottom:none;">
    <div class="drawer-section-title">عملیات</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <button onclick="openCreditModal()" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(160,122,245,.08);border:1px solid rgba(160,122,245,.2);border-radius:8px;cursor:pointer;color:var(--accent);font-size:12px;font-weight:600;font-family:'Vazirmatn',sans-serif;"><i class="fa-solid fa-coins"></i> مدیریت کردیت</button>
      <button style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(245,146,58,.06);border:1px solid rgba(245,146,58,.2);border-radius:8px;cursor:pointer;color:var(--orange);font-size:12px;font-weight:600;font-family:'Vazirmatn',sans-serif;"><i class="fa-solid fa-ban"></i> مسدودسازی</button>
      <button style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;cursor:pointer;color:var(--text2);font-size:12px;font-weight:600;font-family:'Vazirmatn',sans-serif;"><i class="fa-solid fa-clock-rotate-left"></i> تاریخچه سفارشات</button>
    </div>
  </div>
</div>

<!-- Credit Modal -->
<div class="modal-bg" id="credit-modal" onclick="if(event.target===this)closeCreditModal()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-coins" style="color:var(--accent);margin-left:8px;"></i>مدیریت کردیت</span>
      <button class="modal-close" onclick="closeCreditModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:12px;color:var(--text3);">کردیت فعلی</span>
      <span id="modal-current-credit" style="font-size:16px;font-weight:800;color:var(--accent);">—</span>
    </div>
    <div class="form-group">
      <label class="form-label">عملیات</label>
      <select class="form-select" id="credit-op"><option value="add">افزودن کردیت</option><option value="sub">کاهش کردیت</option><option value="set">تنظیم مقدار ثابت</option></select>
    </div>
    <div class="form-group">
      <label class="form-label">مقدار</label>
      <input type="number" class="form-input" id="credit-amount" placeholder="مثال: ۱۰" min="1">
    </div>
    <div class="form-group">
      <label class="form-label">دلیل (اختیاری)</label>
      <input type="text" class="form-input" placeholder="مثال: جبران خطای سیستم">
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
      <button class="hdr-btn hdr-btn-outline" onclick="closeCreditModal()">انصراف</button>
      <button class="hdr-btn hdr-btn-primary" onclick="saveCreditChange()">ثبت تغییر</button>
    </div>
  </div>
</div>

@endsection
@section('scripts')
<script>
const USERS = [
  {id:1,name:'سارا احمدی',phone:'09121234567',status:'فعال',plan:'پرمیوم',credits:42,orders:87,revenue:'۴۳۵K',ref:'@tech_ali',joined:'۱۴۰۴/۰۸/۱۲',last:'دیروز',success:84,spend:'۴.۲M',color:'#ec4899',letter:'س'},
  {id:2,name:'علی رضایی',phone:'09351112233',status:'فعال',plan:'رایگان',credits:8,orders:23,revenue:'۱۱۵K',ref:'ارگانیک',joined:'۱۴۰۴/۱۰/۰۵',last:'امروز',success:22,spend:'۱.۱M',color:'#a07af5',letter:'ع'},
  {id:3,name:'مینا کریمی',phone:'09211009988',status:'فعال',plan:'پرمیوم',credits:120,orders:142,revenue:'۷۱۰K',ref:'@design_mina',joined:'۱۴۰۴/۰۲/۱۸',last:'۲ روز پیش',success:138,spend:'۷.۱M',color:'#3b82f6',letter:'م'},
  {id:4,name:'رضا موسوی',phone:'09301234567',status:'غیرفعال',plan:'رایگان',credits:2,orders:5,revenue:'۲۵K',ref:'ارگانیک',joined:'۱۴۰۴/۱۱/۰۱',last:'۱۵ روز پیش',success:4,spend:'۲۵K',color:'#0BBF53',letter:'ر'},
  {id:5,name:'زهرا حسینی',phone:'09125551234',status:'فعال',plan:'پرمیوم',credits:75,orders:210,revenue:'۱.۰۵M',ref:'@art_roya',joined:'۱۴۰۳/۱۲/۰۵',last:'امروز',success:202,spend:'۱۰.۵M',color:'#f5923a',letter:'ز'},
  {id:6,name:'نیلوفر کریمی',phone:'09178889900',status:'فعال',plan:'رایگان',credits:15,orders:31,revenue:'۱۵۵K',ref:'ارگانیک',joined:'۱۴۰۴/۰۶/۲۰',last:'۵ روز پیش',success:29,spend:'۱.۵M',color:'#a07af5',letter:'ن'},
  {id:7,name:'فرید طاهری',phone:'09033334455',status:'مسدود',plan:'رایگان',credits:0,orders:2,revenue:'۱۰K',ref:'ارگانیک',joined:'۱۴۰۴/۱۱/۱۵',last:'۳۰ روز پیش',success:1,spend:'۱۰K',color:'#f05c5c',letter:'ف'},
  {id:8,name:'آرزو شریفی',phone:'09366665544',status:'فعال',plan:'پرمیوم',credits:200,orders:314,revenue:'۱.۵۷M',ref:'@startup_sara',joined:'۱۴۰۳/۰۹/۱۰',last:'امروز',success:308,spend:'۱۵.۷M',color:'#ec4899',letter:'آ'},
];

const STATUS_BADGE = {
  'فعال':'<span class="badge badge-green">فعال</span>',
  'غیرفعال':'<span class="badge badge-gray">غیرفعال</span>',
  'مسدود':'<span class="badge badge-red">مسدود</span>'
};
const PLAN_BADGE = {
  'پرمیوم':'<span class="badge badge-purple"><i class="fa-solid fa-crown" style="font-size:8px;"></i>&nbsp;پرمیوم</span>',
  'رایگان':'<span class="badge badge-gray">رایگان</span>'
};

let currentUser = null;

function renderUsers(list) {
  const tbody = document.getElementById('users-body');
  tbody.innerHTML = list.map((u,i)=>`
    <tr>
      <td style="font-size:11px;color:var(--text3);">${i+1}</td>
      <td><div class="user-cell">
        <div class="user-avatar" style="background:${u.color}22;color:${u.color};">${u.letter}</div>
        <div><div class="user-name">${u.name}</div><div class="user-phone">${u.phone}</div></div>
      </div></td>
      <td>${STATUS_BADGE[u.status]}</td>
      <td>${PLAN_BADGE[u.plan]}</td>
      <td><span class="credit-pill"><i class="fa-solid fa-bolt" style="font-size:9px;"></i>${u.credits}</span></td>
      <td style="font-weight:600;color:var(--text);">${u.orders}</td>
      <td style="color:var(--green);font-weight:600;">${u.revenue}</td>
      <td style="font-size:11px;color:${u.ref==='ارگانیک'?'var(--text3)':'var(--accent)'};">${u.ref}</td>
      <td style="font-size:11px;color:var(--text3);">${u.joined}</td>
      <td>
        <button class="action-btn" title="جزئیات" onclick="openDrawer(${u.id})"><i class="fa-solid fa-eye"></i></button>
        <button class="action-btn" title="کردیت" onclick="openDrawer(${u.id});setTimeout(openCreditModal,50)"><i class="fa-solid fa-coins"></i></button>
      </td>
    </tr>
  `).join('');
}

function filterUsers() {
  const q = document.getElementById('search-input').value.toLowerCase();
  const st = document.getElementById('status-filter').value;
  const pl = document.getElementById('plan-filter').value;
  const filtered = USERS.filter(u=>
    (!q || u.name.includes(q) || u.phone.includes(q)) &&
    (!st || u.status === st) &&
    (!pl || u.plan === pl)
  );
  renderUsers(filtered);
}

function openDrawer(id) {
  const u = USERS.find(x=>x.id===id);
  if (!u) return;
  currentUser = u;
  document.getElementById('drawer-avatar').style.background = u.color+'22';
  document.getElementById('drawer-avatar').style.color = u.color;
  document.getElementById('drawer-avatar').textContent = u.letter;
  document.getElementById('drawer-name').textContent = u.name;
  document.getElementById('drawer-phone').textContent = u.phone;
  document.getElementById('dr-status').innerHTML = STATUS_BADGE[u.status];
  document.getElementById('dr-plan').innerHTML = PLAN_BADGE[u.plan];
  document.getElementById('dr-credits').innerHTML = `<span style="color:var(--accent);font-weight:700;">${u.credits} کردیت</span>`;
  document.getElementById('dr-joined').textContent = u.joined;
  document.getElementById('dr-last').textContent = u.last;
  document.getElementById('dr-ref').textContent = u.ref;
  document.getElementById('dr-orders').textContent = u.orders;
  document.getElementById('dr-success').textContent = u.success + ' موفق';
  document.getElementById('dr-spend').textContent = u.spend;
  document.getElementById('drawer-bg').classList.add('open');
  document.getElementById('user-drawer').classList.add('open');
}

function closeDrawer() {
  document.getElementById('drawer-bg').classList.remove('open');
  document.getElementById('user-drawer').classList.remove('open');
}

function openCreditModal() {
  if (!currentUser) return;
  document.getElementById('modal-current-credit').textContent = currentUser.credits + ' کردیت';
  document.getElementById('credit-modal').classList.add('open');
}
function closeCreditModal() { document.getElementById('credit-modal').classList.remove('open'); }
function saveCreditChange() {
  const op = document.getElementById('credit-op').value;
  const amt = parseInt(document.getElementById('credit-amount').value)||0;
  if (!amt) return;
  if (op==='add') currentUser.credits += amt;
  else if (op==='sub') currentUser.credits = Math.max(0, currentUser.credits - amt);
  else currentUser.credits = amt;
  closeCreditModal();
  openDrawer(currentUser.id); // re-render drawer
  filterUsers();
}

renderUsers(USERS);
</script>
@endsection
