@extends('layouts.admin')
@section('title', 'صف AI Jobs — AIPIX Admin')

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
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-danger{background:rgba(240,92,92,.08);color:var(--red);border:1px solid rgba(240,92,92,.2);}

.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;position:relative;overflow:hidden;}
.stat-label{font-size:10.5px;color:var(--text2);margin-bottom:5px;}
.stat-val{font-size:20px;font-weight:800;line-height:1;}
.stat-sub{font-size:9.5px;color:var(--text3);margin-top:3px;}
.pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--green);position:absolute;top:14px;left:16px;animation:pulse 2s infinite;}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(11,191,83,.4);}70%{box-shadow:0 0 0 6px rgba(11,191,83,0);}100%{box-shadow:0 0 0 0 rgba(11,191,83,0);}}

.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-search{flex:1;min-width:180px;position:relative;}
.filter-search input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 32px 7px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;}
.filter-search input:focus{border-color:var(--accent);}
.filter-search .icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px;}
.filter-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:12.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;cursor:pointer;}

.queue-layout{display:grid;grid-template-columns:1fr 280px;gap:16px;}

.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.j-table{width:100%;border-collapse:collapse;}
.j-table th{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:10px 14px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.j-table td{padding:11px 14px;border-bottom:1px solid var(--b1);font-size:12px;color:var(--text2);vertical-align:middle;}
.j-table tr:last-child td{border-bottom:none;}
.j-table tr:hover td{background:rgba(255,255,255,.012);}

.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}

.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;margin-left:3px;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}
.action-btn-danger{border-color:rgba(240,92,92,.2);color:var(--red);}
.action-btn-danger:hover{background:rgba(240,92,92,.08);}
.action-btn-up{border-color:rgba(11,191,83,.2);color:var(--green);}
.action-btn-up:hover{background:rgba(11,191,83,.08);}

/* progress bar */
.progress-wrap{height:4px;background:var(--b1);border-radius:99px;overflow:hidden;width:80px;display:inline-block;vertical-align:middle;}
.progress-fill{height:100%;border-radius:99px;transition:width .3s;}

/* sidebar cards */
.side-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px;margin-bottom:12px;}
.side-title{font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:12px;}
.model-row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(34,34,48,.5);}
.model-row:last-child{border-bottom:none;}
.model-name{font-size:11px;font-family:monospace;color:var(--text2);}
.model-queue{font-size:12px;font-weight:700;color:var(--text);}
.health-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}

/* worker status */
.worker-card{background:var(--bg);border:1px solid var(--b1);border-radius:8px;padding:10px 12px;margin-bottom:6px;display:flex;align-items:center;gap:10px;}
.worker-name{flex:1;font-size:11.5px;font-weight:600;color:var(--text);}
.worker-job{font-size:10.5px;color:var(--text3);font-family:monospace;}
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
      <a href="/admin/payments" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-credit-card"></i></div><div class="snav-label">پرداخت‌ها</div></a>
      <div class="snav-section">بازاریابی</div>
      <a href="/admin/bloggers" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div></a>
      <div class="snav-section">سیستم</div>
      <a href="/admin/jobs" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-microchip"></i></div><div class="snav-label">AI Job Queue</div></a>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="font-size:14px;font-weight:700;">AI Job Queue</div>
      <div style="display:flex;align-items:center;gap:6px;padding:4px 10px;background:rgba(11,191,83,.06);border:1px solid rgba(11,191,83,.15);border-radius:8px;">
        <div style="width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 2s infinite;"></div>
        <span style="font-size:11.5px;font-weight:600;color:var(--green);">سیستم فعال</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-danger" onclick="if(confirm('همه جاب‌های در انتظار پاک شود؟'))alert('صف پاک شد')"><i class="fa-solid fa-trash" style="font-size:11px;"></i> پاک کردن صف</button>
      <button class="hdr-btn hdr-btn-outline" onclick="location.reload()"><i class="fa-solid fa-arrows-rotate" style="font-size:11px;"></i> رفرش</button>
    </header>

    <main class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="pulse-dot"></div>
          <div class="stat-label">در حال پردازش</div>
          <div class="stat-val" style="color:var(--green);">۷</div>
          <div class="stat-sub">روی ۷ Worker</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">در انتظار</div>
          <div class="stat-val" style="color:var(--orange);">۲۳</div>
          <div class="stat-sub">میانگین انتظار: ۴۵s</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">موفق (۲۴h)</div>
          <div class="stat-val" style="color:var(--text);">۱,۸۴۲</div>
          <div class="stat-sub">نرخ: ۹۷.۱٪</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">ناموفق (۲۴h)</div>
          <div class="stat-val" style="color:var(--red);">۵۴</div>
          <div class="stat-sub">۳۸ retry شد</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">میانگین زمان</div>
          <div class="stat-val" style="color:var(--accent);">۱۸.۶s</div>
          <div class="stat-sub">P95: ۴۲s</div>
        </div>
      </div>

      <div class="queue-layout">
        <!-- Main Queue Table -->
        <div>
          <div class="filter-bar">
            <div class="filter-search">
              <input type="text" placeholder="جستجو با Job ID یا کاربر...">
              <i class="fa-solid fa-magnifying-glass icon"></i>
            </div>
            <select class="filter-select">
              <option>همه وضعیت‌ها</option>
              <option>در حال پردازش</option>
              <option>در انتظار</option>
              <option>موفق</option>
              <option>ناموفق</option>
              <option>لغو شده</option>
            </select>
            <select class="filter-select">
              <option>همه مدل‌ها</option>
              <option>flux-1.1-pro</option>
              <option>flux-kontext</option>
              <option>sd-3.5</option>
              <option>ideogram</option>
            </select>
          </div>

          <div class="table-wrap">
            <table class="j-table">
              <thead>
                <tr>
                  <th>Job ID</th>
                  <th>کاربر</th>
                  <th>محصول</th>
                  <th>مدل AI</th>
                  <th>وضعیت</th>
                  <th>زمان</th>
                  <th>Retry</th>
                  <th>اولویت</th>
                  <th style="width:90px;">عملیات</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۸۱</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">سارا احمدی</td>
                  <td style="font-size:11px;">عکس لینکدین</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--accent);">flux-1.1-pro</td>
                  <td>
                    <span class="badge badge-blue" style="gap:5px;">
                      <span class="progress-wrap" style="width:50px;"><span class="progress-fill" style="width:68%;background:var(--accent);"></span></span>
                      ۶۸٪
                    </span>
                  </td>
                  <td style="font-size:11px;color:var(--text);">۱۲.۴s</td>
                  <td style="font-size:11px;color:var(--text3);">۰</td>
                  <td><span class="badge badge-orange">بالا</span></td>
                  <td><button class="action-btn action-btn-danger" title="لغو"><i class="fa-solid fa-stop"></i></button></td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۸۰</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">مینا کریمی</td>
                  <td style="font-size:11px;">آواتار انیمه</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--accent);">flux-kontext</td>
                  <td>
                    <span class="badge badge-blue" style="gap:5px;">
                      <span class="progress-wrap" style="width:50px;"><span class="progress-fill" style="width:32%;background:var(--accent);"></span></span>
                      ۳۲٪
                    </span>
                  </td>
                  <td style="font-size:11px;color:var(--text);">۸.۱s</td>
                  <td style="font-size:11px;color:var(--text3);">۰</td>
                  <td><span class="badge badge-gray">عادی</span></td>
                  <td><button class="action-btn action-btn-danger" title="لغو"><i class="fa-solid fa-stop"></i></button></td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۷۹</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">زهرا حسینی</td>
                  <td style="font-size:11px;">پس‌زمینه حرفه‌ای</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--orange);">sd-3.5 <span style="font-size:9px;">(fallback)</span></td>
                  <td>
                    <span class="badge badge-blue" style="gap:5px;">
                      <span class="progress-wrap" style="width:50px;"><span class="progress-fill" style="width:85%;background:var(--green);"></span></span>
                      ۸۵٪
                    </span>
                  </td>
                  <td style="font-size:11px;color:var(--text);">۲۸.۷s</td>
                  <td style="font-size:11px;color:var(--orange);">۱</td>
                  <td><span class="badge badge-gray">عادی</span></td>
                  <td><button class="action-btn action-btn-danger" title="لغو"><i class="fa-solid fa-stop"></i></button></td>
                </tr>
                <tr style="opacity:.7;">
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۷۸</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">علی رضایی</td>
                  <td style="font-size:11px;">عکس کودک</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">flux-1.1-pro</td>
                  <td><span class="badge badge-orange"><i class="fa-solid fa-hourglass-half" style="font-size:8px;"></i>&nbsp;در انتظار</span></td>
                  <td style="font-size:11px;color:var(--text3);">۴۵s wait</td>
                  <td style="font-size:11px;color:var(--text3);">۰</td>
                  <td><span class="badge badge-gray">عادی</span></td>
                  <td>
                    <button class="action-btn action-btn-up" title="اولویت بالا"><i class="fa-solid fa-arrow-up"></i></button>
                    <button class="action-btn action-btn-danger" title="لغو"><i class="fa-solid fa-xmark"></i></button>
                  </td>
                </tr>
                <tr style="opacity:.7;">
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۷۷</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">آرزو شریفی</td>
                  <td style="font-size:11px;">عکس لینکدین</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">flux-1.1-pro</td>
                  <td><span class="badge badge-orange"><i class="fa-solid fa-hourglass-half" style="font-size:8px;"></i>&nbsp;در انتظار</span></td>
                  <td style="font-size:11px;color:var(--text3);">۱m ۱۲s wait</td>
                  <td style="font-size:11px;color:var(--text3);">۰</td>
                  <td><span class="badge badge-purple">VIP</span></td>
                  <td>
                    <button class="action-btn action-btn-up" title="اولویت بالا"><i class="fa-solid fa-arrow-up"></i></button>
                    <button class="action-btn action-btn-danger" title="لغو"><i class="fa-solid fa-xmark"></i></button>
                  </td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۷۵</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">فرید طاهری</td>
                  <td style="font-size:11px;">آواتار کارتونی</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">ideogram</td>
                  <td><span class="badge badge-red"><i class="fa-solid fa-triangle-exclamation" style="font-size:8px;"></i>&nbsp;ناموفق</span></td>
                  <td style="font-size:11px;color:var(--red);">timeout</td>
                  <td style="font-size:11px;color:var(--red);">۳ / ۳</td>
                  <td><span class="badge badge-gray">عادی</span></td>
                  <td><button class="action-btn" title="retry"><i class="fa-solid fa-rotate-right"></i></button></td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10px;color:var(--text3);">JOB-۰۰۴۷۳</td>
                  <td style="font-size:12px;font-weight:600;color:var(--text);">نیلوفر کریمی</td>
                  <td style="font-size:11px;">عکس لینکدین</td>
                  <td style="font-family:monospace;font-size:10px;color:var(--green);">flux-1.1-pro</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-check" style="font-size:8px;"></i>&nbsp;موفق</span></td>
                  <td style="font-size:11px;color:var(--green);">۱۶.۲s</td>
                  <td style="font-size:11px;color:var(--text3);">۰</td>
                  <td><span class="badge badge-gray">عادی</span></td>
                  <td><button class="action-btn" title="مشاهده"><i class="fa-solid fa-eye"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Sidebar: Workers + Model Health -->
        <div>
          <div class="side-card">
            <div class="side-title">Worker Instances</div>
            <div class="worker-card">
              <div class="health-dot" style="background:var(--green);"></div>
              <div><div class="worker-name">Worker #1</div><div class="worker-job">JOB-00481 — flux-pro</div></div>
            </div>
            <div class="worker-card">
              <div class="health-dot" style="background:var(--green);"></div>
              <div><div class="worker-name">Worker #2</div><div class="worker-job">JOB-00480 — kontext</div></div>
            </div>
            <div class="worker-card">
              <div class="health-dot" style="background:var(--green);"></div>
              <div><div class="worker-name">Worker #3</div><div class="worker-job">JOB-00479 — sd-3.5</div></div>
            </div>
            <div class="worker-card" style="opacity:.5;">
              <div class="health-dot" style="background:var(--text3);"></div>
              <div><div class="worker-name">Worker #4</div><div class="worker-job" style="color:var(--b2);">idle</div></div>
            </div>
            <div class="worker-card" style="opacity:.5;">
              <div class="health-dot" style="background:var(--text3);"></div>
              <div><div class="worker-name">Worker #5</div><div class="worker-job" style="color:var(--b2);">idle</div></div>
            </div>
          </div>

          <div class="side-card">
            <div class="side-title">صف به تفکیک مدل</div>
            <div class="model-row">
              <div><div class="model-name">flux-1.1-pro</div><div style="font-size:9px;color:var(--green);">online</div></div>
              <div class="model-queue">۱۴</div>
            </div>
            <div class="model-row">
              <div><div class="model-name">flux-kontext</div><div style="font-size:9px;color:var(--green);">online</div></div>
              <div class="model-queue">۶</div>
            </div>
            <div class="model-row">
              <div><div class="model-name">sd-3.5-large</div><div style="font-size:9px;color:var(--green);">online</div></div>
              <div class="model-queue">۲</div>
            </div>
            <div class="model-row">
              <div><div class="model-name">ideogram/v3</div><div style="font-size:9px;color:var(--orange);">slow</div></div>
              <div class="model-queue">۱</div>
            </div>
          </div>

          <div class="side-card">
            <div class="side-title">خطاهای اخیر</div>
            <div style="font-size:11px;color:var(--red);font-family:monospace;background:var(--bg);border:1px solid var(--b1);border-radius:6px;padding:10px;line-height:1.8;">
              <div>JOB-00475: TIMEOUT (60s)</div>
              <div>JOB-00461: MODEL_NSFW</div>
              <div>JOB-00448: API_RATE_LIMIT</div>
              <div style="color:var(--text3);">JOB-00432: TIMEOUT (60s)</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
@endsection
@section('scripts')
<script>
// Simulate live queue counter animation
let pendingCount = 23;
setInterval(() => {
  // just a visual demo — would be websocket in production
}, 5000);

document.querySelectorAll('.action-btn-danger').forEach(btn=>{
  if(btn.title==='لغو') btn.onclick = function(){
    if(confirm('این Job لغو شود؟')) {
      this.closest('tr').style.opacity='.4';
      this.closest('tr').querySelector('td:nth-child(5)').innerHTML='<span class="badge badge-gray">لغو شد</span>';
    }
  };
  if(btn.title==='retry') btn.onclick = function(){
    this.closest('tr').querySelector('td:nth-child(5)').innerHTML='<span class="badge badge-orange">retry...</span>';
  };
});
</script>
@endsection
