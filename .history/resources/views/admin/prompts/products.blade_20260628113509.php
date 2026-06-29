@extends('layouts.admin')
@section('title', 'لیست محصولات — AIPIX Admin')

@push('styles')
<style>
/* ── PAGE VARS ── */
:root {
  --bg: #0c0c10; --s1: #111116; --s2: #16161c;
  --b1: #222230; --b2: #2e2e3e;
  --text: #ffffff; --text2: #a8c4a8; --text3: #4d7a56;
  --green: #0BBF53; --accent: #a07af5; --red: #f05c5c; --orange: #f5923a;
}
* { box-sizing: border-box; }
body { font-family: 'IRANSansXFaNum', sans-serif; background: var(--bg); color: var(--text); direction: rtl; }

/* ── LAYOUT ── */
.admin-wrap  { display: flex; min-height: 100vh; }
.admin-sidebar { position: fixed; top: 0; right: 0; bottom: 0; width: 256px; background: var(--s1); border-left: 1px solid var(--b1); display: flex; flex-direction: column; overflow-y: auto; z-index: 100; scrollbar-width: thin; scrollbar-color: var(--b2) transparent; }
.admin-main   { margin-right: 256px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
.admin-header { position: sticky; top: 0; z-index: 50; background: var(--s1); border-bottom: 1px solid var(--b1); padding: 0 24px; height: 56px; display: flex; align-items: center; gap: 12px; }
.admin-content { padding: 24px; flex: 1; }

/* ── SIDEBAR NAV ── */
.snav-section { font-size: 9px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--text3); padding: 12px 16px 4px; }
.snav-item { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin: 1px 6px; border-radius: 8px; cursor: pointer; transition: background .15s; height: 38px; }
.snav-item:hover { background: var(--s2); }
.snav-item.active { background: rgba(160,122,245,.12); }
.snav-icon { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: var(--text2); flex-shrink: 0; }
.snav-item.active .snav-icon { color: var(--accent); }
.snav-label { flex: 1; font-size: 12.5px; font-weight: 600; color: var(--text2); }
.snav-item.active .snav-label { color: var(--text); }
.snav-sub { padding: 2px 0 4px; }
.snav-sub-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px 6px 10px; margin: 1px 6px 1px 30px; border-radius: 6px; cursor: pointer; transition: background .15s; }
.snav-sub-item:hover { background: var(--s2); }
.snav-sub-item.active { background: rgba(160,122,245,.1); }
.snav-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--b2); flex-shrink: 0; transition: background .15s; }
.snav-sub-item.active .snav-dot, .snav-sub-item:hover .snav-dot { background: var(--accent); }
.snav-sub-label { flex: 1; font-size: 11.5px; font-weight: 500; color: var(--text2); }
.snav-sub-item.active .snav-sub-label { color: var(--text); font-weight: 600; }

/* ── HEADER ── */
.breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text2); }
.breadcrumb a { color: var(--text2); text-decoration: none; transition: color .15s; }
.breadcrumb a:hover { color: var(--text); }
.breadcrumb .sep { color: var(--text3); font-size: 10px; }
.breadcrumb .current { color: var(--text); font-weight: 600; }
.hdr-btn { display: flex; align-items: center; gap: 6px; padding: 0 14px; height: 34px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; font-family: 'IRANSansXFaNum', sans-serif; transition: all .15s; text-decoration: none; }
.hdr-btn-primary { background: var(--accent); color: #fff; }
.hdr-btn-primary:hover { background: #8f68e0; }
.hdr-btn-outline { background: var(--s2); color: var(--text2); border: 1px solid var(--b1); }
.hdr-btn-outline:hover { border-color: var(--b2); color: var(--text); }

/* ── STATS ── */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
.stat-card { background: var(--s2); border: 1px solid var(--b1); border-radius: 12px; padding: 16px 18px; }
.stat-label { font-size: 12px; color: var(--text2); margin-bottom: 6px; }
.stat-val { font-size: 26px; font-weight: 700; line-height: 1; }
.stat-sub { font-size: 11px; color: var(--text3); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.stat-sub.up { color: var(--green); }

/* ── FILTER BAR ── */
.filter-bar { background: var(--s2); border: 1px solid var(--b1); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-search { flex: 1; min-width: 200px; position: relative; }
.filter-search input { width: 100%; background: var(--s1); border: 1px solid var(--b1); border-radius: 8px; padding: 8px 36px 8px 12px; font-size: 13px; color: var(--text); font-family: 'IRANSansXFaNum', sans-serif; outline: none; direction: rtl; }
.filter-search input:focus { border-color: var(--accent); }
.filter-search .icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text3); font-size: 13px; }
.filter-select { background: var(--s1); border: 1px solid var(--b1); border-radius: 8px; padding: 8px 12px; font-size: 12.5px; color: var(--text2); font-family: 'IRANSansXFaNum', sans-serif; outline: none; cursor: pointer; direction: rtl; min-width: 130px; }
.filter-select:focus { border-color: var(--accent); }
.filter-count { font-size: 12px; color: var(--text3); margin-right: auto; white-space: nowrap; }

/* ── TABLE ── */
.products-table-wrap { background: var(--s2); border: 1px solid var(--b1); border-radius: 12px; overflow: hidden; }
.products-table { width: 100%; border-collapse: collapse; }
.products-table th { font-size: 11px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: 1px; padding: 12px 16px; text-align: right; border-bottom: 1px solid var(--b1); background: var(--s1); white-space: nowrap; }
.products-table td { padding: 14px 16px; border-bottom: 1px solid var(--b1); font-size: 13px; color: var(--text2); vertical-align: middle; }
.products-table tr:last-child td { border-bottom: none; }
.products-table tr:hover td { background: rgba(255,255,255,.02); }
.product-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: var(--b1); flex-shrink: 0; }
.product-thumb-placeholder { width: 44px; height: 44px; border-radius: 8px; background: var(--b1); display: flex; align-items: center; justify-content: center; color: var(--text3); font-size: 18px; flex-shrink: 0; }
.product-name-fa { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.product-name-en { font-size: 11px; color: var(--text3); direction: ltr; text-align: right; }
.badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-green  { background: rgba(11,191,83,.1);    color: var(--green);  border: 1px solid rgba(11,191,83,.2);  }
.badge-gray   { background: var(--b1);              color: var(--text2);  border: 1px solid var(--b2);           }
.badge-red    { background: rgba(240,92,92,.1);     color: var(--red);    border: 1px solid rgba(240,92,92,.2);  }
.badge-orange { background: rgba(245,146,58,.1);    color: var(--orange); border: 1px solid rgba(245,146,58,.2); }
.badge-purple { background: rgba(160,122,245,.1);   color: var(--accent); border: 1px solid rgba(160,122,245,.2);}
.badge-blue   { background: rgba(59,130,246,.1);    color: #3b82f6;       border: 1px solid rgba(59,130,246,.2);  }
.credit-pill { display: inline-flex; align-items: center; gap: 4px; background: rgba(160,122,245,.08); color: var(--accent); border: 1px solid rgba(160,122,245,.15); border-radius: 99px; padding: 3px 9px; font-size: 12px; font-weight: 600; }
.action-btns { display: flex; align-items: center; gap: 6px; }
.action-btn { width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--b1); background: var(--s1); color: var(--text2); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; transition: all .15s; text-decoration: none; }
.action-btn:hover { border-color: var(--b2); color: var(--text); }
.action-btn.danger:hover { border-color: var(--red); color: var(--red); background: rgba(240,92,92,.05); }

/* ── PAGINATION ── */
.pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--b1); }
.page-info { font-size: 12px; color: var(--text3); }
.page-btns { display: flex; gap: 4px; }
.page-btn { width: 32px; height: 32px; border-radius: 7px; border: 1px solid var(--b1); background: var(--s1); color: var(--text2); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; font-weight: 600; transition: all .15s; }
.page-btn:hover { border-color: var(--b2); color: var(--text); }
.page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }

/* ── SECTION TITLE ── */
.section-title { font-size: 13px; font-weight: 600; color: var(--text2); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.section-title::before { content: ''; width: 3px; height: 14px; background: var(--accent); border-radius: 2px; }
</style>
@endpush

@section('content')
<div class="admin-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside class="admin-sidebar">

    <!-- Logo -->
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900;color:#fff;box-shadow:0 0 16px rgba(11,191,83,.3);">و</div>
      <div>
        <div style="font-size:14px;font-weight:800;letter-spacing:-.3px;">وطن استودیو</div>
        <div style="font-size:9px;color:var(--text3);letter-spacing:2.5px;text-transform:uppercase;margin-top:1px;">Admin Panel</div>
      </div>
    </div>

    <!-- Admin User -->
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">م</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:12px;font-weight:700;">محسن رضایی</div>
        <div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div>
      </div>
      <div style="width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);flex-shrink:0;"></div>
    </div>

    <!-- Nav -->
    <nav style="flex:1;padding:8px 0;">

      <!-- Back to Dashboard -->
      <a href="/admin/dashboard" class="snav-item" style="text-decoration:none;margin-bottom:4px;">
        <div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="snav-label">مرکز فرماندهی</div>
      </a>

      <div class="snav-section">مدیریت محصولات</div>

      <!-- محصولات (expanded + active) -->
      <div class="snav-item active">
        <div class="snav-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="snav-label">محصولات</div>
        <i class="fa-solid fa-chevron-down" style="font-size:9px;color:var(--text3);"></i>
      </div>
      <div class="snav-sub">
        <div class="snav-sub-item" onclick="window.location='/admin/products/overview'">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">داشبورد محصولات</div>
          <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span>
        </div>
        <a href="/admin/products" class="snav-sub-item active" style="text-decoration:none;">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">لیست محصولات</div>
        </a>
        <a href="/admin/products/create" class="snav-sub-item" style="text-decoration:none;">
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

      <!-- سفارشات -->
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
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-terminal"></i></div>
        <div class="snav-label">پرامپت‌ها</div>
        <span style="font-size:9px;padding:1px 6px;border-radius:6px;background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.25);">در حال طراحی</span>
      </div>

      <div style="height:1px;background:var(--b1);margin:8px 12px;"></div>

      <!-- تنظیمات -->
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
        <span class="sep"><i class="fa-solid fa-chevron-left" style="font-size:8px;"></i></span>
        <a href="/admin/products">محصولات</a>
        <span class="sep"><i class="fa-solid fa-chevron-left" style="font-size:8px;"></i></span>
        <span class="current">لیست محصولات</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline" style="gap:6px;">
        <i class="fa-solid fa-arrow-down-to-line" style="font-size:11px;"></i>
        خروجی Excel
      </button>
      <a href="/admin/products/create" class="hdr-btn hdr-btn-primary">
        <i class="fa-solid fa-plus" style="font-size:11px;"></i>
        ثبت محصول جدید
      </a>
    </header>

    <!-- Content -->
    <main class="admin-content">

      <!-- Page Title -->
      <div style="margin-bottom:20px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">لیست محصولات</div>
        <div style="font-size:13px;color:var(--text3);">مدیریت و نمایش تمام محصولات پلتفرم AIPIX</div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">کل محصولات</div>
          <div class="stat-val" style="color:var(--text);">۱۰</div>
          <div class="stat-sub">
            <i class="fa-solid fa-box"></i>
            در سیستم ثبت شده
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">محصولات فعال</div>
          <div class="stat-val" style="color:var(--green);">۷</div>
          <div class="stat-sub up">
            <i class="fa-solid fa-arrow-up" style="font-size:10px;"></i>
            ۲ محصول اضافه شد
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">پیش‌نویس</div>
          <div class="stat-val" style="color:var(--orange);">۲</div>
          <div class="stat-sub">
            <i class="fa-solid fa-clock"></i>
            در انتظار انتشار
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">کل سفارشات</div>
          <div class="stat-val" style="color:var(--accent);">۲,۴۸۱</div>
          <div class="stat-sub up">
            <i class="fa-solid fa-arrow-up" style="font-size:10px;"></i>
            این ماه
          </div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <div class="filter-search">
          <input type="text" placeholder="جستجو در محصولات..." id="search-input" oninput="filterProducts()">
          <i class="fa-solid fa-magnifying-glass icon"></i>
        </div>
        <select class="filter-select" id="cat-filter" onchange="filterProducts()">
          <option value="">همه دسته‌بندی‌ها</option>
          <option>PEOPLE</option>
          <option>BUSINESS</option>
          <option>EVENTS</option>
          <option>FAMILY</option>
          <option>KIDS</option>
          <option>PETS</option>
          <option>ENTERTAINMENT</option>
          <option>PRODUCTS</option>
          <option>AVATARS</option>
          <option>VIDEOS</option>
        </select>
        <select class="filter-select" id="status-filter" onchange="filterProducts()">
          <option value="">همه وضعیت‌ها</option>
          <option>active</option>
          <option>draft</option>
          <option>inactive</option>
        </select>
        <div class="filter-count" id="filter-count">نمایش ۱۰ از ۱۰ محصول</div>
      </div>

      <!-- Products Table -->
      <div class="products-table-wrap">
        <table class="products-table" id="products-table">
          <thead>
            <tr>
              <th style="width:48px;">#</th>
              <th>محصول</th>
              <th>دسته‌بندی</th>
              <th>وضعیت</th>
              <th>قیمت</th>
              <th>سفارشات</th>
              <th>تاریخ ایجاد</th>
              <th style="width:100px;">عملیات</th>
            </tr>
          </thead>
          <tbody id="products-tbody">

            <!-- Row 1 -->
            <tr data-name="عکس حرفه‌ای لینکدین linkedin professional headshot" data-cat="PEOPLE" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۱</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(160,122,245,.2),rgba(160,122,245,.05));color:var(--accent);">
                    <i class="fa-solid fa-user-tie"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">عکس حرفه‌ای لینکدین</div>
                    <div class="product-name-en">LinkedIn Professional Headshot</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-purple">PEOPLE</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۵ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۴۸۲</td>
              <td style="font-size:12px;">۱۴۰۵/۰۱/۱۵</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn" title="ویرایش"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn" title="پیش‌نمایش"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger" title="حذف" onclick="return confirm('حذف شود؟')"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 2 -->
            <tr data-name="طراحی بنر کسب‌وکار business banner design" data-cat="BUSINESS" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۲</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(59,130,246,.05));color:#3b82f6;">
                    <i class="fa-solid fa-briefcase"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">طراحی بنر کسب‌وکار</div>
                    <div class="product-name-en">Business Banner Design</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-blue">BUSINESS</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۸ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۳۲۱</td>
              <td style="font-size:12px;">۱۴۰۵/۰۱/۲۰</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn" title="ویرایش"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn" title="پیش‌نمایش"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger" title="حذف"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 3 -->
            <tr data-name="کارت تبریک تولد birthday greeting card" data-cat="EVENTS" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۳</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(240,92,92,.15),rgba(240,92,92,.03));color:var(--red);">
                    <i class="fa-solid fa-cake-candles"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">کارت تبریک تولد</div>
                    <div class="product-name-en">Birthday Greeting Card</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-orange">EVENTS</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۳ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۶۹۴</td>
              <td style="font-size:12px;">۱۴۰۵/۰۲/۰۵</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 4 -->
            <tr data-name="آواتار انیمه anime avatar" data-cat="ENTERTAINMENT" data-status="draft">
              <td style="color:var(--text3);font-size:12px;">۴</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(245,146,58,.15),rgba(245,146,58,.03));color:var(--orange);">
                    <i class="fa-solid fa-star"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">آواتار انیمه</div>
                    <div class="product-name-en">Anime Avatar</div>
                  </div>
                </div>
              </td>
              <td><span class="badge" style="background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">ENTERTAINMENT</span></td>
              <td><span class="badge badge-orange"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;پیش‌نویس</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۵ کردیت</span></td>
              <td style="color:var(--text3);">—</td>
              <td style="font-size:12px;">۱۴۰۵/۰۳/۱۰</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 5 -->
            <tr data-name="عکس خانوادگی family photo" data-cat="FAMILY" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۵</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(11,191,83,.15),rgba(11,191,83,.03));color:var(--green);">
                    <i class="fa-solid fa-house-user"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">عکس خانوادگی</div>
                    <div class="product-name-en">Family Photo Enhancement</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-green">FAMILY</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۶ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۲۰۸</td>
              <td style="font-size:12px;">۱۴۰۵/۰۲/۱۸</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 6 -->
            <tr data-name="کارت نوروز nowruz eid card" data-cat="EVENTS" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۶</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(160,122,245,.15),rgba(160,122,245,.03));color:var(--accent);">
                    <i class="fa-solid fa-seedling"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">کارت مبارکباد نوروز</div>
                    <div class="product-name-en">Nowruz Celebration Card</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-orange">EVENTS</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۴ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۳۱۵</td>
              <td style="font-size:12px;">۱۴۰۵/۰۱/۰۱</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 7 -->
            <tr data-name="آواتار دیجیتال digital avatar" data-cat="AVATARS" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۷</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(59,130,246,.15),rgba(59,130,246,.03));color:#3b82f6;">
                    <i class="fa-solid fa-robot"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">آواتار دیجیتال</div>
                    <div class="product-name-en">Digital Avatar Creator</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-purple">AVATARS</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۱۰ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۱۷۳</td>
              <td style="font-size:12px;">۱۴۰۵/۰۲/۲۵</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 8 -->
            <tr data-name="پرتره هنری artistic portrait" data-cat="PEOPLE" data-status="inactive">
              <td style="color:var(--text3);font-size:12px;">۸</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:var(--b1);color:var(--text3);">
                    <i class="fa-solid fa-palette"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">پرتره هنری</div>
                    <div class="product-name-en">Artistic Portrait</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-purple">PEOPLE</span></td>
              <td><span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;غیرفعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۷ کردیت</span></td>
              <td style="color:var(--text3);">—</td>
              <td style="font-size:12px;">۱۴۰۴/۱۲/۱۰</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 9 -->
            <tr data-name="عکس محصول product photo" data-cat="PRODUCTS" data-status="draft">
              <td style="color:var(--text3);font-size:12px;">۹</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(11,191,83,.1),rgba(11,191,83,.02));color:var(--green);">
                    <i class="fa-solid fa-camera"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">عکاسی محصول</div>
                    <div class="product-name-en">Product Photography AI</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-gray">PRODUCTS</span></td>
              <td><span class="badge badge-orange"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;پیش‌نویس</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۶ کردیت</span></td>
              <td style="color:var(--text3);">—</td>
              <td style="font-size:12px;">۱۴۰۵/۰۳/۲۰</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

            <!-- Row 10 -->
            <tr data-name="کارت فارغ التحصیلی graduation card" data-cat="EVENTS" data-status="active">
              <td style="color:var(--text3);font-size:12px;">۱۰</td>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="product-thumb-placeholder" style="background:linear-gradient(135deg,rgba(160,122,245,.15),rgba(160,122,245,.03));color:var(--accent);">
                    <i class="fa-solid fa-graduation-cap"></i>
                  </div>
                  <div>
                    <div class="product-name-fa">کارت فارغ‌التحصیلی</div>
                    <div class="product-name-en">Graduation Card Design</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-orange">EVENTS</span></td>
              <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
              <td><span class="credit-pill"><i class="fa-solid fa-coins" style="font-size:10px;"></i> ۴ کردیت</span></td>
              <td style="color:var(--text);font-weight:600;">۲۸۸</td>
              <td style="font-size:12px;">۱۴۰۵/۰۳/۰۱</td>
              <td>
                <div class="action-btns">
                  <a href="#" class="action-btn"><i class="fa-solid fa-pen"></i></a>
                  <a href="#" class="action-btn"><i class="fa-solid fa-eye"></i></a>
                  <a href="#" class="action-btn danger"><i class="fa-solid fa-trash"></i></a>
                </div>
              </td>
            </tr>

          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
          <div class="page-info">نمایش ۱–۱۰ از ۱۰ محصول</div>
          <div class="page-btns">
            <div class="page-btn" title="صفحه قبل"><i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></div>
            <div class="page-btn active">۱</div>
            <div class="page-btn" title="صفحه بعد"><i class="fa-solid fa-chevron-left" style="font-size:11px;"></i></div>
          </div>
        </div>
      </div>

    </main>
  </div>

</div>
@endsection

@section('scripts')
<script>
function filterProducts() {
  const search = document.getElementById('search-input').value.toLowerCase();
  const cat    = document.getElementById('cat-filter').value;
  const status = document.getElementById('status-filter').value;
  const rows   = document.querySelectorAll('#products-tbody tr');
  let visible  = 0;

  rows.forEach(row => {
    const name    = (row.dataset.name   || '').toLowerCase();
    const rowCat  = row.dataset.cat    || '';
    const rowStat = row.dataset.status || '';

    const matchSearch = !search || name.includes(search);
    const matchCat    = !cat    || rowCat === cat;
    const matchStatus = !status || rowStat === status;

    if (matchSearch && matchCat && matchStatus) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('filter-count').textContent =
    'نمایش ' + visible + ' از ' + rows.length + ' محصول';
}
</script>
@endsection
