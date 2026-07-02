@extends('layouts.admin')
@section('title', 'آنالیتیکس محصولات — AIPIX Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#fff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
*{box-sizing:border-box;}
body{font-family:'Vazirmatn',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}
.admin-wrap{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;right:0;bottom:0;width:256px;background:var(--s1);border-left:1px solid var(--b1);display:flex;flex-direction:column;overflow-y:auto;z-index:100;scrollbar-width:thin;scrollbar-color:var(--b2) transparent;}
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
.snav-sub-item{display:flex;align-items:center;gap:8px;padding:6px 10px;margin:1px 6px 1px 30px;border-radius:6px;cursor:pointer;transition:background .15s;text-decoration:none;}
.snav-sub-item:hover{background:var(--s2);}.snav-sub-item.active{background:rgba(160,122,245,.1);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item.active .snav-dot,.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;}.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;}
.hdr-btn-primary:hover{opacity:.9;}

/* time toggle */
.time-toggle{display:inline-flex;background:var(--s2);border:1px solid var(--b1);border-radius:9px;padding:3px;gap:2px;}
.time-btn{padding:5px 13px;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--text2);font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.time-btn.active{background:var(--b2);color:var(--text);}

/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;position:relative;overflow:hidden;transition:border-color .15s;}
.kpi-card:hover{border-color:var(--b2);}
.kpi-card::before{content:'';position:absolute;top:0;right:0;width:3px;height:100%;border-radius:0 12px 12px 0;opacity:0;transition:opacity .2s;}
.kpi-card:hover::before{opacity:1;}
.kpi-card.c-green::before{background:var(--green);}
.kpi-card.c-accent::before{background:var(--accent);}
.kpi-card.c-orange::before{background:var(--orange);}
.kpi-card.c-red::before{background:var(--red);}
.kpi-card.c-blue::before{background:#3b82f6;}
.kpi-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:24px;font-weight:800;line-height:1.1;margin-bottom:5px;}
.kpi-trend{display:flex;align-items:center;gap:5px;font-size:10.5px;}
.kpi-trend.up{color:var(--green);}.kpi-trend.down{color:var(--red);}.kpi-trend.neutral{color:var(--text3);}
.kpi-icon{position:absolute;left:14px;top:14px;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;}

/* charts row */
.charts-row{display:grid;grid-template-columns:1fr 380px;gap:16px;margin-bottom:16px;}
.chart-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 22px;}
.chart-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;}
.chart-sub{font-size:11px;color:var(--text3);}
.chart-wrap{position:relative;margin-top:14px;}

/* doughnut legend */
.doughnut-wrap{display:flex;align-items:center;gap:20px;margin-top:16px;}
.doughnut-canvas{position:relative;width:140px;height:140px;flex-shrink:0;}
.doughnut-legend{flex:1;display:flex;flex-direction:column;gap:8px;}
.legend-item{display:flex;align-items:center;gap:8px;}
.legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.legend-label{flex:1;font-size:11.5px;color:var(--text2);}
.legend-val{font-size:11.5px;font-weight:700;color:var(--text);}

/* bottom row */
.bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}

/* tables */
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:9px 12px;text-align:right;border-bottom:1px solid var(--b1);}
.data-table td{padding:10px 12px;border-bottom:1px solid rgba(34,34,48,.6);font-size:12px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
.rank-num{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;}
.r-1{background:rgba(234,179,8,.15);color:#eab308;}
.r-2{background:rgba(156,163,175,.12);color:#9ca3af;}
.r-3{background:rgba(205,127,50,.12);color:#cd7f32;}
.r-n{background:var(--b1);color:var(--text3);}
.mini-bar{height:5px;border-radius:99px;background:var(--b1);overflow:hidden;width:80px;}
.mini-bar-fill{height:100%;border-radius:99px;background:var(--accent);}
.blogger-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}
.badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:99px;font-size:10px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}

/* model usage */
.model-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--b1);}
.model-row:last-child{border-bottom:none;}
.model-name{width:160px;font-size:11.5px;color:var(--text2);font-family:monospace;}
.model-bar{flex:1;height:6px;border-radius:99px;background:var(--b1);overflow:hidden;}
.model-fill{height:100%;border-radius:99px;transition:width .6s ease;}
.model-pct{width:36px;text-align:left;font-size:11px;font-weight:700;color:var(--text);}
.model-count{width:60px;text-align:left;font-size:10px;color:var(--text3);}
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
      <a href="/admin/dashboard" class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="snav-label">مرکز فرماندهی</div>
      </a>
      <div class="snav-section">مدیریت محصولات</div>
      <a href="/admin/products" class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="snav-label">محصولات</div>
        <i class="fa-solid fa-chevron-left" style="font-size:9px;color:var(--text3);"></i>
      </a>
      <a href="/admin/products/create" class="snav-sub-item">
        <div class="snav-dot"></div><div class="snav-sub-label">ثبت محصول جدید</div>
      </a>
      <a href="/admin/products/categories" class="snav-sub-item">
        <div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div>
      </a>
      <a href="/admin/products/pricing" class="snav-sub-item">
        <div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div>
      </a>
      <a href="/admin/orders" class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="snav-label">سفارشات</div>
      </a>
      <div class="snav-section">آنالیز</div>
      <a href="/admin/analytics" class="snav-item active">
        <div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div>
        <div class="snav-label">آنالیتیکس</div>
      </a>
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
        <span class="current">آنالیتیکس</span>
      </div>
      <div style="flex:1;"></div>
      <div class="time-toggle">
        <button class="time-btn" onclick="setRange(this,'7')">۷ روز</button>
        <button class="time-btn active" onclick="setRange(this,'30')">۳۰ روز</button>
        <button class="time-btn" onclick="setRange(this,'90')">۳ ماه</button>
        <button class="time-btn" onclick="setRange(this,'365')">۱ سال</button>
      </div>
      <button class="hdr-btn hdr-btn-outline" style="margin-right:8px;">
        <i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i> گزارش PDF
      </button>
    </header>

    <main class="admin-content">
      <div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">آنالیتیکس محصولات</div>
          <div style="font-size:13px;color:var(--text3);">گزارش عملکرد — ۳۰ روز اخیر</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;background:rgba(11,191,83,.06);border:1px solid rgba(11,191,83,.2);border-radius:9px;padding:6px 12px;font-size:11px;font-weight:700;color:var(--green);">
          <div style="width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);animation:pulse 2s infinite;"></div>
          لایو
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card c-green">
          <div class="kpi-icon" style="background:rgba(11,191,83,.1);color:var(--green);"><i class="fa-solid fa-money-bill-wave"></i></div>
          <div class="kpi-label">درآمد کل</div>
          <div class="kpi-val" style="color:var(--green);">۱.۴۴B</div>
          <div class="kpi-trend up"><i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> ۱۸٪ نسبت به ماه قبل</div>
        </div>
        <div class="kpi-card c-accent">
          <div class="kpi-icon" style="background:rgba(160,122,245,.1);color:var(--accent);"><i class="fa-solid fa-bolt"></i></div>
          <div class="kpi-label">سفارشات</div>
          <div class="kpi-val" style="color:var(--accent);">۲,۴۸۱</div>
          <div class="kpi-trend up"><i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> ۱۲٪ رشد</div>
        </div>
        <div class="kpi-card c-orange">
          <div class="kpi-icon" style="background:rgba(245,146,58,.1);color:var(--orange);"><i class="fa-solid fa-clock"></i></div>
          <div class="kpi-label">میانگین زمان پردازش</div>
          <div class="kpi-val" style="color:var(--orange);">۱۸.۶s</div>
          <div class="kpi-trend down"><i class="fa-solid fa-arrow-down" style="font-size:9px;"></i> ۴٪ بهتر</div>
        </div>
        <div class="kpi-card c-red">
          <div class="kpi-icon" style="background:rgba(240,92,92,.1);color:var(--red);"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div class="kpi-label">نرخ خطا</div>
          <div class="kpi-val" style="color:var(--red);">۳.۵٪</div>
          <div class="kpi-trend neutral"><i class="fa-solid fa-minus" style="font-size:9px;"></i> بدون تغییر</div>
        </div>
        <div class="kpi-card c-blue">
          <div class="kpi-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="fa-solid fa-link"></i></div>
          <div class="kpi-label">درآمد از بلاگرها</div>
          <div class="kpi-val" style="color:#3b82f6;">۳۷۴M</div>
          <div class="kpi-trend up"><i class="fa-solid fa-arrow-up" style="font-size:9px;"></i> ۲۶٪ از کل</div>
        </div>
      </div>

      <!-- Main Chart + Model Distribution -->
      <div class="charts-row">
        <div class="chart-card">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;">
            <div>
              <div class="chart-title">سفارشات و درآمد — ۳۰ روز</div>
              <div class="chart-sub">تعداد سفارشات روزانه + درآمد (میلیون تومان)</div>
            </div>
            <div style="display:flex;gap:12px;font-size:10.5px;color:var(--text3);">
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:3px;background:var(--accent);border-radius:2px;display:inline-block;"></span> سفارشات</span>
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:3px;background:var(--green);border-radius:2px;display:inline-block;"></span> درآمد</span>
            </div>
          </div>
          <div class="chart-wrap" style="height:220px;">
            <canvas id="mainChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-title">توزیع مدل‌های AI</div>
          <div class="chart-sub">بر اساس تعداد استفاده</div>
          <div class="doughnut-wrap">
            <div class="doughnut-canvas">
              <canvas id="modelChart"></canvas>
            </div>
            <div class="doughnut-legend">
              <div class="legend-item"><div class="legend-dot" style="background:#a07af5;"></div><div class="legend-label" style="font-size:10.5px;font-family:monospace;">flux-1.1-pro</div><div class="legend-val">۶۷٪</div></div>
              <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div><div class="legend-label" style="font-size:10.5px;font-family:monospace;">flux-kontext</div><div class="legend-val">۱۸٪</div></div>
              <div class="legend-item"><div class="legend-dot" style="background:#0BBF53;"></div><div class="legend-label" style="font-size:10.5px;font-family:monospace;">sd-3.5 (fbk)</div><div class="legend-val">۱۰٪</div></div>
              <div class="legend-item"><div class="legend-dot" style="background:#f5923a;"></div><div class="legend-label" style="font-size:10.5px;font-family:monospace;">ideogram-v3</div><div class="legend-val">۵٪</div></div>
            </div>
          </div>
          <!-- model bar list -->
          <div style="margin-top:16px;border-top:1px solid var(--b1);padding-top:14px;">
            <div class="model-row">
              <div class="model-name">flux-1.1-pro</div>
              <div class="model-bar"><div class="model-fill" style="width:67%;background:var(--accent);"></div></div>
              <div class="model-pct">۶۷٪</div>
              <div class="model-count">۱,۶۶۲</div>
            </div>
            <div class="model-row">
              <div class="model-name">flux-kontext-pro</div>
              <div class="model-bar"><div class="model-fill" style="width:18%;background:#3b82f6;"></div></div>
              <div class="model-pct">۱۸٪</div>
              <div class="model-count">۴۴۷</div>
            </div>
            <div class="model-row">
              <div class="model-name">sd-3.5 (fallback)</div>
              <div class="model-bar"><div class="model-fill" style="width:10%;background:var(--green);"></div></div>
              <div class="model-pct">۱۰٪</div>
              <div class="model-count">۲۴۸</div>
            </div>
            <div class="model-row">
              <div class="model-name">ideogram-v3</div>
              <div class="model-bar"><div class="model-fill" style="width:5%;background:var(--orange);"></div></div>
              <div class="model-pct">۵٪</div>
              <div class="model-count">۱۲۴</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom: Top Products + Blogger Leaderboard -->
      <div class="bottom-row">
        <!-- Top Products -->
        <div class="chart-card" style="padding-bottom:4px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
              <div class="chart-title">برترین محصولات</div>
              <div class="chart-sub">بر اساس سفارشات ۳۰ روز</div>
            </div>
            <a href="/admin/products" style="font-size:11px;color:var(--accent);text-decoration:none;opacity:.8;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.8">مشاهده همه ←</a>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:32px;">#</th>
                <th>محصول</th>
                <th>سفارشات</th>
                <th>درآمد</th>
                <th>موفقیت</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><div class="rank-num r-1">۱</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(160,122,245,.12);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--accent);flex-shrink:0;"><i class="fa-solid fa-user-tie"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">عکس لینکدین</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۶۲۴</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:100%;background:var(--accent);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۳۱۲M</td>
                <td><span class="badge badge-green">۹۸٪</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-2">۲</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;font-size:11px;color:#3b82f6;flex-shrink:0;"><i class="fa-solid fa-robot"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">آواتار دیجیتال</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۴۸۸</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:78%;background:#3b82f6;"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۴۸۸M</td>
                <td><span class="badge badge-green">۹۵٪</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-3">۳</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(240,92,92,.12);display:flex;align-items:center;justify-content:center;font-size:11px;color:#f05c5c;flex-shrink:0;"><i class="fa-solid fa-cake-candles"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">کارت تولد</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۳۶۱</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:58%;background:var(--red);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۱۰۸M</td>
                <td><span class="badge badge-green">۹۷٪</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۴</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(59,130,246,.1);display:flex;align-items:center;justify-content:center;font-size:11px;color:#3b82f6;flex-shrink:0;"><i class="fa-solid fa-briefcase"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">بنر کسب‌وکار</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۲۸۴</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:45%;"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۲۲۷M</td>
                <td><span class="badge badge-orange">۸۹٪</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۵</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(11,191,83,.1);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--green);flex-shrink:0;"><i class="fa-solid fa-house-user"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">عکس خانوادگی</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۲۱۸</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:35%;background:var(--green);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۱۳۱M</td>
                <td><span class="badge badge-green">۹۶٪</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۶</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(160,122,245,.1);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--accent);flex-shrink:0;"><i class="fa-solid fa-seedling"></i></div><span style="font-size:12.5px;font-weight:600;color:var(--text);">کارت نوروز</span></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۱۷۲</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:27%;"></div></div></td>
                <td style="font-size:12px;color:var(--text3);font-weight:600;">رایگان</td>
                <td><span class="badge badge-green">۹۹٪</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Blogger Leaderboard -->
        <div class="chart-card" style="padding-bottom:4px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
              <div class="chart-title">عملکرد بلاگرها</div>
              <div class="chart-sub">رتبه‌بندی رفرال ۳۰ روز</div>
            </div>
            <a href="/admin/bloggers" style="font-size:11px;color:var(--accent);text-decoration:none;opacity:.8;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.8">همه ←</a>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:32px;">#</th>
                <th>بلاگر</th>
                <th>رفرال</th>
                <th>درآمد</th>
                <th>وضعیت</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><div class="rank-num r-1">۱</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(160,122,245,.15);color:var(--accent);">ع</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@tech_ali</div><div style="font-size:10px;color:var(--text3);">علی محمدی</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۲۱۴</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:100%;background:var(--accent);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۱۰۷M</td>
                <td><span class="badge badge-green">فعال</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-2">۲</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(236,72,153,.12);color:#ec4899;">م</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@design_mina</div><div style="font-size:10px;color:var(--text3);">مینا کریمی</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۱۸۷</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:87%;background:#ec4899;"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۱۸۷M</td>
                <td><span class="badge badge-green">فعال</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-3">۳</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(11,191,83,.12);color:var(--green);">ر</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@art_roya</div><div style="font-size:10px;color:var(--text3);">رویا حسینی</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۱۲۳</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:57%;background:var(--green);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۳۷M</td>
                <td><span class="badge badge-green">فعال</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۴</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(59,130,246,.12);color:#3b82f6;">س</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@startup_sara</div><div style="font-size:10px;color:var(--text3);">سارا رضایی</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۸۶</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:40%;background:#3b82f6;"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۶۸M</td>
                <td><span class="badge badge-green">فعال</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۵</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(245,146,58,.12);color:var(--orange);">ن</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@photo_nima</div><div style="font-size:10px;color:var(--text3);">نیما احمدی</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۵۴</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:25%;background:var(--orange);"></div></div></td>
                <td style="font-size:12px;color:var(--green);font-weight:600;">۲۷M</td>
                <td><span class="badge badge-orange">کند</span></td>
              </tr>
              <tr>
                <td><div class="rank-num r-n">۶</div></td>
                <td><div style="display:flex;align-items:center;gap:7px;"><div class="blogger-avatar" style="background:rgba(240,92,92,.12);color:var(--red);">ف</div><div><div style="font-size:12px;font-weight:700;color:var(--text);">@fashion_farida</div><div style="font-size:10px;color:var(--text3);">فریده نوری</div></div></div></td>
                <td><div style="font-size:12.5px;font-weight:700;color:var(--text);">۲۸</div><div class="mini-bar" style="margin-top:3px;"><div class="mini-bar-fill" style="width:13%;background:var(--red);"></div></div></td>
                <td style="font-size:12px;color:var(--text3);font-weight:600;">۸M</td>
                <td><span class="badge badge-red">غیر فعال</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Category + Error Rate row -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
        <!-- Category distribution -->
        <div class="chart-card">
          <div class="chart-title" style="margin-bottom:14px;">سفارشات به تفکیک دسته</div>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:11.5px;color:var(--text2);width:90px;flex-shrink:0;">افراد</div>
              <div style="flex:1;height:7px;background:var(--b1);border-radius:99px;overflow:hidden;"><div style="height:100%;width:45%;background:var(--accent);border-radius:99px;"></div></div>
              <div style="font-size:11px;font-weight:700;width:32px;text-align:left;">۴۵٪</div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:11.5px;color:var(--text2);width:90px;flex-shrink:0;">کسب‌وکار</div>
              <div style="flex:1;height:7px;background:var(--b1);border-radius:99px;overflow:hidden;"><div style="height:100%;width:28%;background:#3b82f6;border-radius:99px;"></div></div>
              <div style="font-size:11px;font-weight:700;width:32px;text-align:left;">۲۸٪</div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:11.5px;color:var(--text2);width:90px;flex-shrink:0;">رویدادها</div>
              <div style="flex:1;height:7px;background:var(--b1);border-radius:99px;overflow:hidden;"><div style="height:100%;width:15%;background:var(--green);border-radius:99px;"></div></div>
              <div style="font-size:11px;font-weight:700;width:32px;text-align:left;">۱۵٪</div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="font-size:11.5px;color:var(--text2);width:90px;flex-shrink:0;">خانواده</div>
              <div style="flex:1;height:7px;background:var(--b1);border-radius:99px;overflow:hidden;"><div style="height:100%;width:12%;background:var(--orange);border-radius:99px;"></div></div>
              <div style="font-size:11px;font-weight:700;width:32px;text-align:left;">۱۲٪</div>
            </div>
          </div>
        </div>

        <!-- Processing time histogram -->
        <div class="chart-card">
          <div class="chart-title" style="margin-bottom:4px;">توزیع زمان پردازش</div>
          <div class="chart-sub" style="margin-bottom:12px;">بر اساس تعداد سفارشات</div>
          <div style="display:flex;align-items:flex-end;gap:6px;height:80px;">
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="font-size:9px;color:var(--text3);font-weight:600;">۱۲۸</div>
              <div style="width:100%;background:rgba(160,122,245,.25);border-radius:4px 4px 0 0;" style="height:30%;"></div>
              <div style="width:100%;border-radius:4px 4px 0 0;background:rgba(160,122,245,.3);height:30px;"></div>
              <div style="font-size:9px;color:var(--text3);">&lt;5s</div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="font-size:9px;color:var(--text3);font-weight:600;">۶۴۲</div>
              <div style="width:100%;border-radius:4px 4px 0 0;background:var(--accent);height:65px;"></div>
              <div style="font-size:9px;color:var(--text3);">5-20s</div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="font-size:9px;color:var(--text3);font-weight:600;">۴۸۶</div>
              <div style="width:100%;border-radius:4px 4px 0 0;background:rgba(160,122,245,.6);height:50px;"></div>
              <div style="font-size:9px;color:var(--text3);">20-30s</div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="font-size:9px;color:var(--text3);font-weight:600;">۲۸۴</div>
              <div style="width:100%;border-radius:4px 4px 0 0;background:rgba(245,146,58,.5);height:30px;"></div>
              <div style="font-size:9px;color:var(--text3);">30-45s</div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="font-size:9px;color:var(--text3);font-weight:600;">۷۵</div>
              <div style="width:100%;border-radius:4px 4px 0 0;background:rgba(240,92,92,.5);height:9px;"></div>
              <div style="font-size:9px;color:var(--text3);">&gt;45s</div>
            </div>
          </div>
        </div>

        <!-- Quick stats -->
        <div class="chart-card">
          <div class="chart-title" style="margin-bottom:14px;">شاخص‌های کلیدی</div>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--bg);border:1px solid var(--b1);border-radius:8px;">
              <div style="font-size:11.5px;color:var(--text2);">نرخ موفقیت</div>
              <div style="font-size:14px;font-weight:800;color:var(--green);">۹۶.۵٪</div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--bg);border:1px solid var(--b1);border-radius:8px;">
              <div style="font-size:11.5px;color:var(--text2);">نرخ fallback</div>
              <div style="font-size:14px;font-weight:800;color:var(--orange);">۱۰٪</div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--bg);border:1px solid var(--b1);border-radius:8px;">
              <div style="font-size:11.5px;color:var(--text2);">هزینه AI / سفارش</div>
              <div style="font-size:14px;font-weight:800;color:var(--accent);">$۰.۲۴</div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--bg);border:1px solid var(--b1);border-radius:8px;">
              <div style="font-size:11.5px;color:var(--text2);">درآمد / هزینه AI</div>
              <div style="font-size:14px;font-weight:800;color:var(--green);">× ۲۴۰</div>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<style>
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// Main chart — orders + revenue over 30 days
const days=[];const orders=[];const revenue=[];
for(let i=29;i>=0;i--){
  const d=new Date();d.setDate(d.getDate()-i);
  days.push((d.getMonth()+1)+'/'+d.getDate());
  orders.push(Math.floor(55+Math.random()*80+Math.sin(i/3)*20));
  revenue.push(Math.floor(40+Math.random()*70+Math.sin(i/4)*15));
}
const mainCtx=document.getElementById('mainChart').getContext('2d');
new Chart(mainCtx,{
  type:'line',
  data:{
    labels:days,
    datasets:[
      {label:'سفارشات',data:orders,borderColor:'#a07af5',backgroundColor:'rgba(160,122,245,.08)',fill:true,tension:.4,pointRadius:0,pointHoverRadius:4,borderWidth:2},
      {label:'درآمد (M)',data:revenue,borderColor:'#0BBF53',backgroundColor:'rgba(11,191,83,.06)',fill:true,tension:.4,pointRadius:0,pointHoverRadius:4,borderWidth:2}
    ]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false,backgroundColor:'#16161c',borderColor:'#222230',borderWidth:1,titleFont:{family:'Vazirmatn',size:11},bodyFont:{family:'Vazirmatn',size:11},titleColor:'#a8c4a8',bodyColor:'#fff',padding:10}},
    scales:{
      x:{grid:{color:'rgba(34,34,48,.5)'},ticks:{color:'#4d7a56',font:{size:9,family:'Vazirmatn'},maxTicksLimit:8}},
      y:{grid:{color:'rgba(34,34,48,.5)'},ticks:{color:'#4d7a56',font:{size:9,family:'Vazirmatn'}}}
    }
  }
});

// Model donut chart
const modelCtx=document.getElementById('modelChart').getContext('2d');
new Chart(modelCtx,{
  type:'doughnut',
  data:{
    labels:['flux-1.1-pro','flux-kontext','sd-3.5','ideogram'],
    datasets:[{data:[67,18,10,5],backgroundColor:['#a07af5','#3b82f6','#0BBF53','#f5923a'],borderWidth:0,hoverBorderWidth:2,hoverBorderColor:'#fff'}]
  },
  options:{
    responsive:true,maintainAspectRatio:false,cutout:'72%',
    plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>ctx.label+': '+ctx.raw+'٪'},backgroundColor:'#16161c',borderColor:'#222230',borderWidth:1,bodyFont:{family:'Vazirmatn',size:11},bodyColor:'#fff',padding:8}}
  }
});

function setRange(btn,days){
  document.querySelectorAll('.time-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelector('.admin-content > div:first-child div:last-child').textContent='گزارش عملکرد — '+days+' روز اخیر';
}
</script>
@endsection
