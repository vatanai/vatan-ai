@extends('layouts.admin')
@section('title', 'داشبورد محصولات — AIPIX Admin')

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
.snav-sub-item{display:flex;align-items:center;gap:8px;padding:6px 10px;margin:1px 6px 1px 30px;border-radius:6px;cursor:pointer;transition:background .15s;text-decoration:none;}
.snav-sub-item:hover{background:var(--s2);}.snav-sub-item.active{background:rgba(160,122,245,.1);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item.active .snav-dot,.snav-sub-item:hover .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}

.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;border:none;}
.hdr-btn-primary:hover{opacity:.9;}

/* Range toggle */
.range-toggle{display:flex;background:var(--s2);border:1px solid var(--b1);border-radius:8px;padding:3px;gap:2px;}
.range-btn{padding:5px 12px;border-radius:6px;font-size:11.5px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--text2);font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.range-btn.active{background:var(--b2);color:var(--text);}

/* KPI grid */
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;position:relative;overflow:hidden;}
.kpi-card::before{content:'';position:absolute;top:0;right:0;width:3px;height:100%;border-radius:0 12px 12px 0;}
.kpi-card.green::before{background:var(--green);}
.kpi-card.accent::before{background:var(--accent);}
.kpi-card.orange::before{background:var(--orange);}
.kpi-card.red::before{background:var(--red);}
.kpi-card.blue::before{background:#3b82f6;}
.kpi-card.teal::before{background:#14b8a6;}
.kpi-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;margin-bottom:10px;}
.kpi-val{font-size:22px;font-weight:900;line-height:1;letter-spacing:-.5px;}
.kpi-label{font-size:10.5px;color:var(--text2);margin-top:3px;}
.kpi-delta{font-size:10px;font-weight:700;margin-top:4px;display:flex;align-items:center;gap:3px;}
.kpi-delta.up{color:var(--green);}
.kpi-delta.down{color:var(--red);}
.kpi-delta.warn{color:var(--orange);}

/* Section layouts */
.section-2col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.section-3col{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;}
.section-health{display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:16px;}

/* Card */
.card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.card-title{font-size:13px;font-weight:700;display:flex;align-items:center;gap:7px;}
.card-title i{color:var(--accent);}
.card-meta{font-size:11px;color:var(--text3);}

/* Chart container */
.chart-wrap{position:relative;}

/* Top products */
.top-prod-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--b1);}
.top-prod-row:last-child{border-bottom:none;}
.rank-badge{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0;}
.prod-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}
.prod-info{flex:1;}
.prod-name{font-size:12.5px;font-weight:700;color:var(--text);}
.prod-cat{font-size:10px;color:var(--text3);}
.prod-bar-wrap{width:90px;height:5px;background:var(--b1);border-radius:99px;overflow:hidden;}
.prod-bar-fill{height:100%;border-radius:99px;}
.prod-orders{font-size:12px;font-weight:700;color:var(--text);min-width:36px;text-align:left;}
.prod-rev{font-size:11px;color:var(--green);min-width:48px;text-align:left;}

/* Health table */
.health-table{width:100%;border-collapse:collapse;}
.health-table th{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:9px 12px;text-align:right;border-bottom:1px solid var(--b1);}
.health-table td{padding:10px 12px;border-bottom:1px solid var(--b1);font-size:12px;color:var(--text2);vertical-align:middle;}
.health-table tr:last-child td{border-bottom:none;}
.health-table tr:hover td{background:rgba(255,255,255,.012);}
.health-score{display:flex;align-items:center;gap:6px;}
.health-bar{height:5px;border-radius:99px;background:var(--b1);overflow:hidden;width:60px;}
.health-fill{height:100%;border-radius:99px;}
.spark{display:inline-flex;align-items:flex-end;gap:2px;height:24px;vertical-align:middle;}
.spark-b{width:5px;border-radius:2px 2px 0 0;opacity:.7;}

/* Badge */
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.badge-teal{background:rgba(20,184,166,.1);color:#14b8a6;border:1px solid rgba(20,184,166,.2);}

/* Category breakdown */
.cat-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--b1);}
.cat-row:last-child{border-bottom:none;}
.cat-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;}
.cat-name{flex:1;font-size:12px;font-weight:600;color:var(--text);}
.cat-bar-wrap{width:80px;height:5px;background:var(--b1);border-radius:99px;overflow:hidden;}
.cat-bar-fill{height:100%;border-radius:99px;}
.cat-count{font-size:11.5px;font-weight:700;color:var(--text);min-width:24px;text-align:center;}
.cat-rev{font-size:10.5px;color:var(--green);min-width:42px;text-align:left;}

/* Model pills */
.model-row{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--b1);}
.model-row:last-child{border-bottom:none;}
.model-name{font-size:11px;font-family:monospace;color:var(--text2);flex:1;}
.model-bar-wrap{width:80px;height:5px;background:var(--b1);border-radius:99px;overflow:hidden;}
.model-bar-fill{height:100%;border-radius:99px;background:var(--accent);}
.model-pct{font-size:11px;font-weight:700;color:var(--text);min-width:30px;text-align:left;}
.model-rate{font-size:10px;min-width:36px;text-align:right;}

/* Quick actions */
.actions-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.action-card{background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:14px;display:flex;flex-direction:column;align-items:center;gap:8px;cursor:pointer;transition:all .15s;text-decoration:none;}
.action-card:hover{border-color:var(--b2);background:var(--s2);}
.action-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;}
.action-label{font-size:11.5px;font-weight:600;color:var(--text2);text-align:center;}
</style>
@endpush

@section('content')
<div class="admin-wrap">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:rgba(11,191,83,.08);border:1px solid rgba(11,191,83,.2);display:flex;align-items:center;justify-content:center;box-shadow:0 0 16px rgba(11,191,83,.2);flex-shrink:0;">
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
      <a href="/admin/products" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-box-open"></i></div><div class="snav-label">محصولات</div></a>
      <a href="/admin/products/dashboard" class="snav-sub-item active"><div class="snav-dot"></div><div class="snav-sub-label">داشبورد محصولات</div></a>
      <a href="/admin/products/create" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">ثبت محصول جدید</div></a>
      <a href="/admin/products/categories" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div></a>
      <a href="/admin/products/pricing" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div></a>
      <a href="/admin/orders" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div></a>
      <div class="snav-section">کاربران</div>
      <a href="/admin/users" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-users"></i></div><div class="snav-label">کاربران</div></a>
      <a href="/admin/payments" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-credit-card"></i></div><div class="snav-label">پرداخت‌ها</div></a>
      <div class="snav-section">بازاریابی</div>
      <a href="/admin/bloggers" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div></a>
      <div class="snav-section">سیستم</div>
      <a href="/admin/jobs" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-microchip"></i></div><div class="snav-label">AI Job Queue</div></a>
      <a href="/admin/analytics" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-chart-line"></i></div><div class="snav-label">آنالیتیکس</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="font-size:14px;font-weight:700;color:var(--text);">داشبورد محصولات</div>
      <!-- Range selector -->
      <div class="range-toggle" style="margin-right:8px;">
        <button class="range-btn" onclick="setRange(7,this)">۷ روز</button>
        <button class="range-btn active" onclick="setRange(30,this)">۳۰ روز</button>
        <button class="range-btn" onclick="setRange(90,this)">۳ ماه</button>
        <button class="range-btn" onclick="setRange(365,this)">سال</button>
      </div>
      <div style="flex:1;"></div>
      <a href="/admin/products" class="hdr-btn hdr-btn-outline"><i class="fa-solid fa-list" style="font-size:11px;"></i> لیست محصولات</a>
      <a href="/admin/products/create" class="hdr-btn hdr-btn-primary"><i class="fa-solid fa-plus" style="font-size:11px;"></i> محصول جدید</a>
    </header>

    <main class="admin-content">

      <!-- ─── KPI Row ─── -->
      <div class="kpi-grid">
        <div class="kpi-card green">
          <div class="kpi-icon" style="background:rgba(11,191,83,.1);color:var(--green);"><i class="fa-solid fa-box-open"></i></div>
          <div class="kpi-val">۱۸</div>
          <div class="kpi-label">محصولات فعال</div>
          <div class="kpi-delta up"><i class="fa-solid fa-arrow-up" style="font-size:8px;"></i> ۲ محصول جدید</div>
        </div>
        <div class="kpi-card accent">
          <div class="kpi-icon" style="background:rgba(160,122,245,.1);color:var(--accent);"><i class="fa-solid fa-cart-shopping"></i></div>
          <div class="kpi-val">۲,۴۸۱</div>
          <div class="kpi-label">سفارشات ۳۰ روز</div>
          <div class="kpi-delta up"><i class="fa-solid fa-arrow-up" style="font-size:8px;"></i> ۲۳٪ رشد</div>
        </div>
        <div class="kpi-card green">
          <div class="kpi-icon" style="background:rgba(11,191,83,.08);color:var(--green);"><i class="fa-solid fa-coins"></i></div>
          <div class="kpi-val">۱.۴۴B</div>
          <div class="kpi-label">درآمد ۳۰ روز</div>
          <div class="kpi-delta up"><i class="fa-solid fa-arrow-up" style="font-size:8px;"></i> ۱۸٪</div>
        </div>
        <div class="kpi-card teal">
          <div class="kpi-icon" style="background:rgba(20,184,166,.1);color:#14b8a6;"><i class="fa-solid fa-check-circle"></i></div>
          <div class="kpi-val">۹۶.۵٪</div>
          <div class="kpi-label">نرخ موفقیت AI</div>
          <div class="kpi-delta up"><i class="fa-solid fa-arrow-up" style="font-size:8px;"></i> ۰.۸٪</div>
        </div>
        <div class="kpi-card orange">
          <div class="kpi-icon" style="background:rgba(245,146,58,.1);color:var(--orange);"><i class="fa-solid fa-clock"></i></div>
          <div class="kpi-val">۱۸.۶s</div>
          <div class="kpi-label">میانگین پردازش</div>
          <div class="kpi-delta down"><i class="fa-solid fa-arrow-down" style="font-size:8px;"></i> ۲s کندتر</div>
        </div>
        <div class="kpi-card red">
          <div class="kpi-icon" style="background:rgba(240,92,92,.08);color:var(--red);"><i class="fa-solid fa-rotate"></i></div>
          <div class="kpi-val">۸٪</div>
          <div class="kpi-label">نرخ Fallback</div>
          <div class="kpi-delta warn"><i class="fa-solid fa-minus" style="font-size:8px;"></i> ثابت</div>
        </div>
      </div>

      <!-- ─── Row 2: Trend chart + Top Products ─── -->
      <div class="section-3col">

        <!-- Orders & Revenue Trend -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-chart-line"></i> روند سفارشات و درآمد</div>
            <div style="display:flex;gap:12px;font-size:11px;color:var(--text2);">
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;background:var(--accent);border-radius:99px;display:inline-block;"></span>سفارشات</span>
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;background:var(--green);border-radius:99px;display:inline-block;"></span>درآمد</span>
            </div>
          </div>
          <div class="chart-wrap" style="height:210px;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>

        <!-- Top 5 Products -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-trophy"></i> پرفروش‌ترین‌ها</div>
            <a href="/admin/products" style="font-size:11px;color:var(--accent);text-decoration:none;">همه ←</a>
          </div>
          <div>
            <div class="top-prod-row">
              <div class="rank-badge" style="background:rgba(234,179,8,.15);color:#eab308;">۱</div>
              <div class="prod-icon" style="background:rgba(160,122,245,.12);color:var(--accent);">💼</div>
              <div class="prod-info">
                <div class="prod-name">عکس لینکدین</div>
                <div class="prod-cat">افراد</div>
              </div>
              <div class="prod-bar-wrap"><div class="prod-bar-fill" style="width:100%;background:var(--accent);"></div></div>
              <div class="prod-orders">۶۲۴</div>
              <div class="prod-rev">۳۱۲M</div>
            </div>
            <div class="top-prod-row">
              <div class="rank-badge" style="background:rgba(148,163,184,.12);color:#94a3b8;">۲</div>
              <div class="prod-icon" style="background:rgba(236,72,153,.1);color:#ec4899;">✨</div>
              <div class="prod-info">
                <div class="prod-name">آواتار انیمه</div>
                <div class="prod-cat">سرگرمی</div>
              </div>
              <div class="prod-bar-wrap"><div class="prod-bar-fill" style="width:76%;background:#ec4899;"></div></div>
              <div class="prod-orders">۴۷۵</div>
              <div class="prod-rev">۲۳۸M</div>
            </div>
            <div class="top-prod-row">
              <div class="rank-badge" style="background:rgba(180,83,9,.15);color:#b45309;">۳</div>
              <div class="prod-icon" style="background:rgba(11,191,83,.1);color:var(--green);">📸</div>
              <div class="prod-info">
                <div class="prod-name">عکس پرتره</div>
                <div class="prod-cat">افراد</div>
              </div>
              <div class="prod-bar-wrap"><div class="prod-bar-fill" style="width:58%;background:var(--green);"></div></div>
              <div class="prod-orders">۳۶۲</div>
              <div class="prod-rev">۱۸۱M</div>
            </div>
            <div class="top-prod-row">
              <div class="rank-badge" style="background:var(--b1);color:var(--text3);">۴</div>
              <div class="prod-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;">🎪</div>
              <div class="prod-info">
                <div class="prod-name">بنر رویداد</div>
                <div class="prod-cat">رویدادها</div>
              </div>
              <div class="prod-bar-wrap"><div class="prod-bar-fill" style="width:42%;background:#3b82f6;"></div></div>
              <div class="prod-orders">۲۶۱</div>
              <div class="prod-rev">۱۳۱M</div>
            </div>
            <div class="top-prod-row">
              <div class="rank-badge" style="background:var(--b1);color:var(--text3);">۵</div>
              <div class="prod-icon" style="background:rgba(245,146,58,.1);color:var(--orange);">🐾</div>
              <div class="prod-info">
                <div class="prod-name">عکس حیوانات</div>
                <div class="prod-cat">حیوانات</div>
              </div>
              <div class="prod-bar-wrap"><div class="prod-bar-fill" style="width:29%;background:var(--orange);"></div></div>
              <div class="prod-orders">۱۸۳</div>
              <div class="prod-rev">۹۲M</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Row 3: Health Table ─── -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-heart-pulse"></i> سلامت محصولات <span style="font-size:10px;font-weight:500;color:var(--text3);margin-right:6px;">— آپدیت آنی</span></div>
          <div style="display:flex;gap:8px;align-items:center;">
            <select style="background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:5px 10px;font-size:11.5px;color:var(--text2);font-family:'Vazirmatn',sans-serif;outline:none;cursor:pointer;">
              <option>همه دسته‌ها</option>
              <option>افراد</option>
              <option>کسب‌وکار</option>
              <option>سرگرمی</option>
            </select>
          </div>
        </div>
        <div style="overflow-x:auto;">
        <table class="health-table">
          <thead>
            <tr>
              <th>محصول</th>
              <th>وضعیت</th>
              <th>سفارشات</th>
              <th>درآمد</th>
              <th>نرخ موفقیت</th>
              <th>میانگین زمان</th>
              <th>Fallback</th>
              <th>مدل AI</th>
              <th>ترند ۷ روز</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(160,122,245,.12);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:12px;">💼</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">عکس لینکدین</div><div style="font-size:10px;color:var(--text3);">افراد</div></div>
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;فعال</span></td>
              <td style="font-weight:700;color:var(--text);">۶۲۴</td>
              <td style="color:var(--green);font-weight:600;">۳۱۲M</td>
              <td>
                <div class="health-score">
                  <div class="health-bar"><div class="health-fill" style="width:97.8%;background:var(--green);"></div></div>
                  <span style="font-size:11px;font-weight:700;color:var(--green);">۹۷.۸٪</span>
                </div>
              </td>
              <td style="font-size:11.5px;">۱۸.۶s</td>
              <td><span style="font-size:11.5px;color:var(--text3);">۸٪</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--accent);">flux-1.1-pro</td>
              <td>
                <div class="spark">
                  <div class="spark-b" style="height:40%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:55%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:50%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:75%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:65%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:85%;background:var(--accent);"></div>
                  <div class="spark-b" style="height:100%;background:var(--green);opacity:1;"></div>
                </div>
              </td>
              <td>
                <a href="/admin/products/1" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a>
                <a href="/admin/products/1/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a>
              </td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(236,72,153,.1);color:#ec4899;display:flex;align-items:center;justify-content:center;font-size:12px;">✨</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">آواتار انیمه</div><div style="font-size:10px;color:var(--text3);">سرگرمی</div></div>
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;فعال</span></td>
              <td style="font-weight:700;color:var(--text);">۴۷۵</td>
              <td style="color:var(--green);font-weight:600;">۲۳۸M</td>
              <td>
                <div class="health-score">
                  <div class="health-bar"><div class="health-fill" style="width:95%;background:var(--green);"></div></div>
                  <span style="font-size:11px;font-weight:700;color:var(--green);">۹۵.۰٪</span>
                </div>
              </td>
              <td style="font-size:11.5px;">۲۲.۴s</td>
              <td><span style="font-size:11.5px;color:var(--orange);">۱۵٪</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--accent);">flux-kontext</td>
              <td>
                <div class="spark">
                  <div class="spark-b" style="height:60%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:70%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:65%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:80%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:75%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:90%;background:#ec4899;"></div>
                  <div class="spark-b" style="height:100%;background:#ec4899;opacity:1;"></div>
                </div>
              </td>
              <td><a href="/admin/products/2" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a><a href="/admin/products/2/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a></td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(11,191,83,.1);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:12px;">📸</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">عکس پرتره</div><div style="font-size:10px;color:var(--text3);">افراد</div></div>
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;فعال</span></td>
              <td style="font-weight:700;color:var(--text);">۳۶۲</td>
              <td style="color:var(--green);font-weight:600;">۱۸۱M</td>
              <td>
                <div class="health-score">
                  <div class="health-bar"><div class="health-fill" style="width:98%;background:var(--green);"></div></div>
                  <span style="font-size:11px;font-weight:700;color:var(--green);">۹۸.۰٪</span>
                </div>
              </td>
              <td style="font-size:11.5px;">۱۵.۲s</td>
              <td><span style="font-size:11.5px;color:var(--text3);">۳٪</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--accent);">flux-1.1-pro</td>
              <td>
                <div class="spark">
                  <div class="spark-b" style="height:50%;background:var(--green);"></div>
                  <div class="spark-b" style="height:60%;background:var(--green);"></div>
                  <div class="spark-b" style="height:55%;background:var(--green);"></div>
                  <div class="spark-b" style="height:70%;background:var(--green);"></div>
                  <div class="spark-b" style="height:80%;background:var(--green);"></div>
                  <div class="spark-b" style="height:75%;background:var(--green);"></div>
                  <div class="spark-b" style="height:90%;background:var(--green);opacity:1;"></div>
                </div>
              </td>
              <td><a href="/admin/products/3" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a><a href="/admin/products/3/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a></td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(59,130,246,.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:12px;">🎪</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">بنر رویداد</div><div style="font-size:10px;color:var(--text3);">رویدادها</div></div>
                </div>
              </td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;فعال</span></td>
              <td style="font-weight:700;color:var(--text);">۲۶۱</td>
              <td style="color:var(--green);font-weight:600;">۱۳۱M</td>
              <td>
                <div class="health-score">
                  <div class="health-bar"><div class="health-fill" style="width:93%;background:var(--orange);"></div></div>
                  <span style="font-size:11px;font-weight:700;color:var(--orange);">۹۳.۰٪</span>
                </div>
              </td>
              <td style="font-size:11.5px;">۲۸.۱s</td>
              <td><span style="font-size:11.5px;color:var(--orange);">۲۲٪</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--orange);">ideogram/v3</td>
              <td>
                <div class="spark">
                  <div class="spark-b" style="height:80%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:70%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:65%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:60%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:50%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:55%;background:#3b82f6;"></div>
                  <div class="spark-b" style="height:45%;background:var(--red);opacity:1;"></div>
                </div>
              </td>
              <td><a href="/admin/products/4" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a><a href="/admin/products/4/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a></td>
            </tr>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:rgba(245,146,58,.1);color:var(--orange);display:flex;align-items:center;justify-content:center;font-size:12px;">🐾</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">عکس حیوانات</div><div style="font-size:10px;color:var(--text3);">حیوانات</div></div>
                </div>
              </td>
              <td><span class="badge badge-orange">پرمیوم</span></td>
              <td style="font-weight:700;color:var(--text);">۱۸۳</td>
              <td style="color:var(--green);font-weight:600;">۹۲M</td>
              <td>
                <div class="health-score">
                  <div class="health-bar"><div class="health-fill" style="width:96%;background:var(--green);"></div></div>
                  <span style="font-size:11px;font-weight:700;color:var(--green);">۹۶.۰٪</span>
                </div>
              </td>
              <td style="font-size:11.5px;">۱۹.۸s</td>
              <td><span style="font-size:11.5px;color:var(--text3);">۶٪</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--accent);">flux-1.1-pro</td>
              <td>
                <div class="spark">
                  <div class="spark-b" style="height:30%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:45%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:60%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:55%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:70%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:80%;background:var(--orange);"></div>
                  <div class="spark-b" style="height:100%;background:var(--orange);opacity:1;"></div>
                </div>
              </td>
              <td><a href="/admin/products/5" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a><a href="/admin/products/5/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a></td>
            </tr>
            <tr style="opacity:.55;">
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:28px;height:28px;border-radius:7px;background:var(--b1);color:var(--text3);display:flex;align-items:center;justify-content:center;font-size:12px;">🏢</div>
                  <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">لوگو کسب‌وکار</div><div style="font-size:10px;color:var(--text3);">کسب‌وکار</div></div>
                </div>
              </td>
              <td><span class="badge badge-gray">غیرفعال</span></td>
              <td style="font-weight:700;color:var(--text3);">—</td>
              <td style="color:var(--text3);">—</td>
              <td><span style="font-size:11px;color:var(--text3);">—</span></td>
              <td style="font-size:11.5px;color:var(--text3);">—</td>
              <td><span style="font-size:11.5px;color:var(--text3);">—</span></td>
              <td style="font-size:10.5px;font-family:monospace;color:var(--text3);">sd-3.5</td>
              <td>—</td>
              <td><a href="/admin/products/6/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a></td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <!-- ─── Row 4: Category + Model + Quick actions ─── -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">

        <!-- Category breakdown -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-layer-group"></i> دسته‌بندی‌ها</div>
            <div class="card-meta">۱۸ محصول</div>
          </div>
          <div>
            <div class="cat-row">
              <div class="cat-icon" style="background:rgba(160,122,245,.1);color:var(--accent);">👤</div>
              <div class="cat-name">افراد / پرتره</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:100%;background:var(--accent);"></div></div>
              <div class="cat-count">۷</div>
              <div class="cat-rev">۴۹۳M</div>
            </div>
            <div class="cat-row">
              <div class="cat-icon" style="background:rgba(236,72,153,.1);color:#ec4899;">✨</div>
              <div class="cat-name">سرگرمی</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:57%;background:#ec4899;"></div></div>
              <div class="cat-count">۴</div>
              <div class="cat-rev">۲۳۸M</div>
            </div>
            <div class="cat-row">
              <div class="cat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;">💼</div>
              <div class="cat-name">کسب‌وکار</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:43%;background:#3b82f6;"></div></div>
              <div class="cat-count">۳</div>
              <div class="cat-rev">۱۳۱M</div>
            </div>
            <div class="cat-row">
              <div class="cat-icon" style="background:rgba(11,191,83,.1);color:var(--green);">🎪</div>
              <div class="cat-name">رویدادها</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:28%;background:var(--green);"></div></div>
              <div class="cat-count">۲</div>
              <div class="cat-rev">۹۲M</div>
            </div>
            <div class="cat-row">
              <div class="cat-icon" style="background:rgba(245,146,58,.1);color:var(--orange);">🐾</div>
              <div class="cat-name">حیوانات</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:19%;background:var(--orange);"></div></div>
              <div class="cat-count">۱</div>
              <div class="cat-rev">۹۲M</div>
            </div>
            <div class="cat-row">
              <div class="cat-icon" style="background:var(--b1);color:var(--text3);">+</div>
              <div class="cat-name" style="color:var(--text3);">سایر</div>
              <div class="cat-bar-wrap"><div class="cat-bar-fill" style="width:5%;background:var(--b2);"></div></div>
              <div class="cat-count" style="color:var(--text3);">۱</div>
              <div class="cat-rev" style="color:var(--text3);">—</div>
            </div>
          </div>
        </div>

        <!-- AI Model performance -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-microchip"></i> عملکرد مدل‌های AI</div>
          </div>
          <div>
            <div class="model-row">
              <div>
                <div class="model-name">flux-1.1-pro</div>
                <div style="font-size:9px;color:var(--green);margin-top:1px;">primary</div>
              </div>
              <div class="model-bar-wrap"><div class="model-bar-fill" style="width:67%;"></div></div>
              <div class="model-pct">۶۷٪</div>
              <div class="model-rate" style="color:var(--green);">۹۸.۲٪</div>
            </div>
            <div class="model-row">
              <div>
                <div class="model-name">flux-kontext</div>
                <div style="font-size:9px;color:var(--text3);margin-top:1px;">primary</div>
              </div>
              <div class="model-bar-wrap"><div class="model-bar-fill" style="width:18%;"></div></div>
              <div class="model-pct">۱۸٪</div>
              <div class="model-rate" style="color:var(--green);">۹۵.۰٪</div>
            </div>
            <div class="model-row">
              <div>
                <div class="model-name">sd-3.5-large</div>
                <div style="font-size:9px;color:var(--orange);margin-top:1px;">fallback</div>
              </div>
              <div class="model-bar-wrap"><div class="model-bar-fill" style="width:10%;background:var(--orange);"></div></div>
              <div class="model-pct">۱۰٪</div>
              <div class="model-rate" style="color:var(--orange);">۸۸.۵٪</div>
            </div>
            <div class="model-row">
              <div>
                <div class="model-name">ideogram/v3</div>
                <div style="font-size:9px;color:var(--orange);margin-top:1px;">fallback</div>
              </div>
              <div class="model-bar-wrap"><div class="model-bar-fill" style="width:5%;background:var(--red);"></div></div>
              <div class="model-pct">۵٪</div>
              <div class="model-rate" style="color:var(--red);">۸۲.۰٪</div>
            </div>
          </div>
          <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--b1);">
            <div style="font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">هشدارها</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
              <div style="display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--orange);">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:10px;"></i>
                ideogram/v3 — timeout rate ۱۸٪
              </div>
              <div style="display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--orange);">
                <i class="fa-solid fa-clock" style="font-size:10px;"></i>
                بنر رویداد — زمان بالا (۲۸s)
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-bolt"></i> دسترسی سریع</div>
          </div>
          <div class="actions-grid">
            <a href="/admin/products/create" class="action-card">
              <div class="action-icon" style="background:rgba(11,191,83,.1);color:var(--green);"><i class="fa-solid fa-plus"></i></div>
              <div class="action-label">محصول جدید</div>
            </a>
            <a href="/admin/products" class="action-card">
              <div class="action-icon" style="background:rgba(160,122,245,.1);color:var(--accent);"><i class="fa-solid fa-list"></i></div>
              <div class="action-label">همه محصولات</div>
            </a>
            <a href="/admin/products/categories" class="action-card">
              <div class="action-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="fa-solid fa-layer-group"></i></div>
              <div class="action-label">دسته‌بندی</div>
            </a>
            <a href="/admin/products/pricing" class="action-card">
              <div class="action-icon" style="background:rgba(245,146,58,.1);color:var(--orange);"><i class="fa-solid fa-tag"></i></div>
              <div class="action-label">قیمت‌گذاری</div>
            </a>
            <a href="/admin/orders" class="action-card">
              <div class="action-icon" style="background:rgba(20,184,166,.1);color:#14b8a6;"><i class="fa-solid fa-cart-shopping"></i></div>
              <div class="action-label">سفارشات</div>
            </a>
            <a href="/admin/analytics" class="action-card">
              <div class="action-icon" style="background:rgba(240,92,92,.08);color:var(--red);"><i class="fa-solid fa-chart-line"></i></div>
              <div class="action-label">آنالیتیکس</div>
            </a>
            <a href="/admin/jobs" class="action-card">
              <div class="action-icon" style="background:rgba(160,122,245,.08);color:var(--accent);"><i class="fa-solid fa-microchip"></i></div>
              <div class="action-label">Job Queue</div>
            </a>
            <a href="/admin/products" class="action-card">
              <div class="action-icon" style="background:var(--b1);color:var(--text2);"><i class="fa-solid fa-box-archive"></i></div>
              <div class="action-label">آرشیو</div>
            </a>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ─── Trend Chart ───────────────────────────────────────────────────
const RANGE_DATA = {
  7: {
    labels: ['۲۱ خرداد','۲۲','۲۳','۲۴','۲۵','۲۶','۲۷'],
    orders: [72, 85, 79, 91, 88, 103, 94],
    revenue: [36, 42, 40, 46, 44, 52, 47],
  },
  30: {
    labels: Array.from({length:30},(_,i)=>`${i+1} خرداد`),
    orders: [42,51,45,63,58,70,65,80,76,88,82,95,90,78,84,92,86,99,94,108,102,115,110,98,105,112,120,116,124,119],
    revenue: [21,26,23,32,29,35,33,40,38,44,41,48,45,39,42,46,43,50,47,54,51,58,55,49,53,56,60,58,62,60],
  },
  90: {
    labels: ['فروردین','اردیبهشت','خرداد (تا امروز)'],
    orders: [1842, 2110, 2481],
    revenue: [921, 1055, 1241],
  },
  365: {
    labels: ['تیر ۱۴۰۴','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند','فروردین','اردیبهشت','خرداد'],
    orders: [820,950,880,1100,1050,1280,1200,1380,1310,1640,1842,2481],
    revenue: [410,475,440,550,525,640,600,690,655,820,921,1241],
  }
};

let trendChart = null;
let currentRange = 30;

function buildChart(range) {
  const d = RANGE_DATA[range];
  const ctx = document.getElementById('trendChart');
  if (trendChart) trendChart.destroy();
  trendChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: d.labels,
      datasets: [
        {
          label: 'سفارشات',
          data: d.orders,
          borderColor: '#a07af5',
          backgroundColor: 'rgba(160,122,245,0.06)',
          borderWidth: 2,
          pointRadius: d.labels.length > 20 ? 0 : 3,
          pointHoverRadius: 5,
          pointBackgroundColor: '#a07af5',
          tension: 0.4,
          fill: true,
          yAxisID: 'y',
        },
        {
          label: 'درآمد (M)',
          data: d.revenue,
          borderColor: '#0BBF53',
          backgroundColor: 'rgba(11,191,83,0.05)',
          borderWidth: 2,
          pointRadius: d.labels.length > 20 ? 0 : 3,
          pointHoverRadius: 5,
          pointBackgroundColor: '#0BBF53',
          tension: 0.4,
          fill: true,
          yAxisID: 'y1',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#16161c',
          borderColor: '#222230',
          borderWidth: 1,
          titleColor: '#e8eaf2',
          bodyColor: '#7c839e',
          titleFont: { family: 'Vazirmatn', size: 11, weight: '700' },
          bodyFont: { family: 'Vazirmatn', size: 11 },
          padding: 10,
          rtl: true,
          callbacks: {
            label: ctx => ctx.datasetIndex === 0
              ? `سفارشات: ${ctx.parsed.y}`
              : `درآمد: ${ctx.parsed.y}M تومان`
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.03)' },
          ticks: {
            color: '#4d7a56',
            font: { family: 'Vazirmatn', size: 10 },
            maxTicksLimit: 8,
            maxRotation: 0,
          },
          border: { display: false }
        },
        y: {
          position: 'right',
          grid: { color: 'rgba(255,255,255,0.03)' },
          ticks: { color: '#4d7a56', font: { family: 'Vazirmatn', size: 10 } },
          border: { display: false }
        },
        y1: {
          position: 'left',
          grid: { display: false },
          ticks: { color: '#4d7a56', font: { family: 'Vazirmatn', size: 10 } },
          border: { display: false }
        }
      }
    }
  });
}

function setRange(range, btn) {
  currentRange = range;
  document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  buildChart(range);
}

buildChart(30);
</script>
@endsection
