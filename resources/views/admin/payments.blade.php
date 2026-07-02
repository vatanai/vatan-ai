@extends('layouts.admin')
@section('title', 'پرداخت‌ها — AIPIX Admin')

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
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.stat-label{font-size:11px;color:var(--text2);margin-bottom:5px;}
.stat-val{font-size:22px;font-weight:800;line-height:1;}
.stat-sub{font-size:10px;color:var(--text3);margin-top:3px;}

.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-search{flex:1;min-width:180px;position:relative;}
.filter-search input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 32px 7px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;}
.filter-search input:focus{border-color:var(--accent);}
.filter-search .icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px;}
.filter-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;cursor:pointer;}
.date-input{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;direction:ltr;}

.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.p-table{width:100%;border-collapse:collapse;}
.p-table th{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:10px 14px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.p-table td{padding:12px 14px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.p-table tr:last-child td{border-bottom:none;}
.p-table tr:hover td{background:rgba(255,255,255,.012);}

.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}

.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;margin-left:3px;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}

.pay-method{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;}
.pay-icon{width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;}

/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:480px;max-width:calc(100vw-32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.detail-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--b1);font-size:13px;}
.detail-row:last-child{border-bottom:none;}
.detail-key{color:var(--text3);}
.detail-val{font-weight:600;color:var(--text);}
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
      <a href="/admin/users" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-users"></i></div><div class="snav-label">کاربران</div></a>
      <a href="/admin/payments" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-credit-card"></i></div><div class="snav-label">پرداخت‌ها</div></a>
      <div class="snav-section">بازاریابی</div>
      <a href="/admin/bloggers" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div></a>
      <div class="snav-section">آنالیز</div>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="font-size:14px;font-weight:700;">پرداخت‌ها و تراکنش‌ها</div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline"><i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i> خروجی Excel</button>
    </header>

    <main class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">درآمد این ماه</div>
          <div class="stat-val" style="color:var(--green);">۱.۴۴B</div>
          <div class="stat-sub">↑ ۲۳٪ نسبت به ماه قبل</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">تراکنش‌های موفق</div>
          <div class="stat-val" style="color:var(--text);">۲,۱۸۴</div>
          <div class="stat-sub">نرخ موفقیت: ۹۷.۲٪</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">معلق / در انتظار</div>
          <div class="stat-val" style="color:var(--orange);">۶۲</div>
          <div class="stat-sub">مجموع: ۳۱M</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">ناموفق / برگشتی</div>
          <div class="stat-val" style="color:var(--red);">۱۸</div>
          <div class="stat-sub">۸.۸M برگشتی</div>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <div class="filter-search">
          <input type="text" placeholder="شناسه تراکنش یا نام کاربر...">
          <i class="fa-solid fa-magnifying-glass icon"></i>
        </div>
        <select class="filter-select" id="pay-status">
          <option>همه وضعیت‌ها</option>
          <option>موفق</option>
          <option>معلق</option>
          <option>ناموفق</option>
          <option>برگشتی</option>
        </select>
        <select class="filter-select" id="pay-type">
          <option>همه انواع</option>
          <option>خرید کردیت</option>
          <option>اشتراک</option>
          <option>برگشت وجه</option>
        </select>
        <input type="date" class="date-input" placeholder="از تاریخ">
        <input type="date" class="date-input" placeholder="تا تاریخ">
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table class="p-table">
          <thead>
            <tr>
              <th>شناسه</th>
              <th>کاربر</th>
              <th>نوع</th>
              <th>مبلغ</th>
              <th>کردیت</th>
              <th>روش پرداخت</th>
              <th>وضعیت</th>
              <th>تاریخ</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۸۱</td>
              <td style="font-weight:600;color:var(--text);">سارا احمدی</td>
              <td><span class="badge badge-purple">خرید کردیت</span></td>
              <td style="color:var(--green);font-weight:700;">۵۰۰,۰۰۰ ت</td>
              <td><span style="color:var(--accent);font-weight:700;font-size:12px;"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> ۱۰</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(11,191,83,.1);color:var(--green);">Z</div>
                  زرین‌پال
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-check" style="font-size:8px;"></i>&nbsp;موفق</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۷</td>
              <td><button class="action-btn" onclick="openDetail('TXN-2481')"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۸۰</td>
              <td style="font-weight:600;color:var(--text);">آرزو شریفی</td>
              <td><span class="badge badge-blue">اشتراک ماهانه</span></td>
              <td style="color:var(--green);font-weight:700;">۲,۰۰۰,۰۰۰ ت</td>
              <td><span style="color:var(--accent);font-weight:700;font-size:12px;"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> ۵۰</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(245,146,58,.1);color:var(--orange);">I</div>
                  ایدی‌پی
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-check" style="font-size:8px;"></i>&nbsp;موفق</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۷</td>
              <td><button class="action-btn" onclick="openDetail('TXN-2480')"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۷۸</td>
              <td style="font-weight:600;color:var(--text);">مینا کریمی</td>
              <td><span class="badge badge-purple">خرید کردیت</span></td>
              <td style="color:var(--green);font-weight:700;">۱,۰۰۰,۰۰۰ ت</td>
              <td><span style="color:var(--accent);font-weight:700;font-size:12px;"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> ۲۵</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(11,191,83,.1);color:var(--green);">Z</div>
                  زرین‌پال
                </div>
              </td>
              <td><span class="badge badge-orange"><i class="fa-solid fa-clock" style="font-size:8px;"></i>&nbsp;معلق</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۷</td>
              <td><button class="action-btn" onclick="openDetail('TXN-2478')"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۷۶</td>
              <td style="font-weight:600;color:var(--text);">علی رضایی</td>
              <td><span class="badge badge-purple">خرید کردیت</span></td>
              <td style="color:var(--text3);font-weight:700;">۲۵۰,۰۰۰ ت</td>
              <td><span style="color:var(--text3);font-weight:700;font-size:12px;">—</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(240,92,92,.1);color:var(--red);">!</div>
                  زرین‌پال
                </div>
              </td>
              <td><span class="badge badge-red"><i class="fa-solid fa-xmark" style="font-size:8px;"></i>&nbsp;ناموفق</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۶</td>
              <td><button class="action-btn" onclick="openDetail('TXN-2476')"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۷۴</td>
              <td style="font-weight:600;color:var(--text);">زهرا حسینی</td>
              <td><span class="badge badge-blue">اشتراک ماهانه</span></td>
              <td style="color:var(--green);font-weight:700;">۲,۰۰۰,۰۰۰ ت</td>
              <td><span style="color:var(--accent);font-weight:700;font-size:12px;"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> ۵۰</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(160,122,245,.1);color:var(--accent);">N</div>
                  نکست‌پی
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-check" style="font-size:8px;"></i>&nbsp;موفق</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۶</td>
              <td><button class="action-btn" onclick="openDetail('TXN-2474')"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
            <tr>
              <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">TXN-۲۴۶۹</td>
              <td style="font-weight:600;color:var(--text);">رضا موسوی</td>
              <td><span class="badge badge-red">برگشت وجه</span></td>
              <td style="color:var(--red);font-weight:700;">−۵۰۰,۰۰۰ ت</td>
              <td><span style="color:var(--red);font-weight:700;font-size:12px;">−۱۰</span></td>
              <td>
                <div class="pay-method">
                  <div class="pay-icon" style="background:rgba(11,191,83,.1);color:var(--green);">Z</div>
                  زرین‌پال
                </div>
              </td>
              <td><span class="badge badge-green">برگشتی</span></td>
              <td style="font-size:11px;">۱۴۰۵/۰۳/۲۵</td>
              <td><button class="action-btn"><i class="fa-solid fa-eye"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;">
        <div style="font-size:12px;color:var(--text3);">نمایش ۱–۶ از ۲,۲۶۴</div>
        <div style="display:flex;gap:4px;">
          <button class="action-btn" style="width:32px;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></button>
          <button class="action-btn" style="width:32px;background:var(--accent);color:#fff;border-color:var(--accent);">۱</button>
          <button class="action-btn" style="width:32px;">۲</button>
          <button class="action-btn" style="width:32px;">۳</button>
          <span style="display:flex;align-items:center;padding:0 4px;font-size:12px;color:var(--text3);">...</span>
          <button class="action-btn" style="width:32px;">۲۲۶</button>
          <button class="action-btn" style="width:32px;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></button>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal-bg" id="txn-modal" onclick="if(event.target===this)closeTxn()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-receipt" style="color:var(--accent);margin-left:8px;"></i>جزئیات تراکنش</span>
      <button style="background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;" onclick="closeTxn()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="txn-detail-content"></div>
  </div>
</div>

@endsection
@section('scripts')
<script>
const TXN_DATA = {
  'TXN-2481':{id:'TXN-2481',user:'سارا احمدی',type:'خرید کردیت',amount:'۵۰۰,۰۰۰ تومان',credits:'+۱۰',gateway:'زرین‌پال',ref:'ZP-98765',status:'موفق',date:'۱۴۰۵/۰۳/۲۷ — ۱۴:۳۲'},
  'TXN-2480':{id:'TXN-2480',user:'آرزو شریفی',type:'اشتراک ماهانه',amount:'۲,۰۰۰,۰۰۰ تومان',credits:'+۵۰',gateway:'ایدی‌پی',ref:'IDP-11223',status:'موفق',date:'۱۴۰۵/۰۳/۲۷ — ۱۱:۱۵'},
  'TXN-2478':{id:'TXN-2478',user:'مینا کریمی',type:'خرید کردیت',amount:'۱,۰۰۰,۰۰۰ تومان',credits:'+۲۵',gateway:'زرین‌پال',ref:'ZP-در انتظار',status:'معلق',date:'۱۴۰۵/۰۳/۲۷ — ۰۹:۴۸'},
  'TXN-2476':{id:'TXN-2476',user:'علی رضایی',type:'خرید کردیت',amount:'۲۵۰,۰۰۰ تومان',credits:'—',gateway:'زرین‌پال',ref:'ZP-خطا',status:'ناموفق',date:'۱۴۰۵/۰۳/۲۶ — ۱۸:۵۳'},
  'TXN-2474':{id:'TXN-2474',user:'زهرا حسینی',type:'اشتراک ماهانه',amount:'۲,۰۰۰,۰۰۰ تومان',credits:'+۵۰',gateway:'نکست‌پی',ref:'NP-55678',status:'موفق',date:'۱۴۰۵/۰۳/۲۶ — ۱۵:۲۲'},
};

function openDetail(id) {
  const d = TXN_DATA[id]; if(!d) return;
  const sc = {'موفق':'badge-green','معلق':'badge-orange','ناموفق':'badge-red'}[d.status]||'badge-gray';
  document.getElementById('txn-detail-content').innerHTML=`
    <div class="detail-row"><div class="detail-key">شناسه</div><div class="detail-val" style="font-family:monospace;">${d.id}</div></div>
    <div class="detail-row"><div class="detail-key">کاربر</div><div class="detail-val">${d.user}</div></div>
    <div class="detail-row"><div class="detail-key">نوع</div><div class="detail-val">${d.type}</div></div>
    <div class="detail-row"><div class="detail-key">مبلغ</div><div class="detail-val" style="color:var(--green);">${d.amount}</div></div>
    <div class="detail-row"><div class="detail-key">کردیت</div><div class="detail-val" style="color:var(--accent);">${d.credits}</div></div>
    <div class="detail-row"><div class="detail-key">درگاه</div><div class="detail-val">${d.gateway}</div></div>
    <div class="detail-row"><div class="detail-key">شماره پیگیری</div><div class="detail-val" style="font-family:monospace;font-size:11.5px;">${d.ref}</div></div>
    <div class="detail-row"><div class="detail-key">وضعیت</div><div class="detail-val"><span class="badge ${sc}">${d.status}</span></div></div>
    <div class="detail-row"><div class="detail-key">تاریخ</div><div class="detail-val">${d.date}</div></div>
  `;
  document.getElementById('txn-modal').classList.add('open');
}
function closeTxn(){document.getElementById('txn-modal').classList.remove('open');}
</script>
@endsection
