@extends('layouts.admin')
@section('title', 'دسته‌بندی محصولات — AIPIX Admin')

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
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;transition:background .15s;}
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
/* layout grid */
.page-grid{display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;}
/* form card */
.card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:22px 24px;}
.card-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:18px;display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid var(--b1);}
.card-title i{color:var(--accent);}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);}
.req{color:var(--red);margin-right:2px;}
.hint{font-size:10px;font-weight:400;color:var(--text3);margin-right:4px;}
.form-input,.form-select,.form-textarea{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'IRANSansXFaNum',sans-serif;outline:none;direction:rtl;transition:border-color .15s;width:100%;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);}
.form-textarea{resize:vertical;min-height:72px;}
.form-input::placeholder,.form-textarea::placeholder{color:var(--text3);}
/* icon picker */
.icon-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:6px;}
.icon-opt{width:100%;aspect-ratio:1;border-radius:8px;border:1.5px solid var(--b1);background:var(--s1);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:var(--text3);transition:all .15s;}
.icon-opt:hover{border-color:var(--b2);color:var(--text2);}
.icon-opt.selected{border-color:var(--accent);background:rgba(160,122,245,.12);color:var(--accent);}
/* color row */
.color-row{display:flex;gap:6px;flex-wrap:wrap;}
.color-dot{width:26px;height:26px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .15s;}
.color-dot:hover,.color-dot.active{border-color:#fff;transform:scale(1.15);}
/* submit btn */
.btn-full{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:0 20px;height:40px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:'IRANSansXFaNum',sans-serif;transition:all .15s;margin-top:6px;}
.btn-primary{background:var(--accent);color:#fff;}
.btn-primary:hover{background:#8f68e0;}
/* table */
.cat-table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.cat-table{width:100%;border-collapse:collapse;}
.cat-table th{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:12px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);white-space:nowrap;}
.cat-table td{padding:14px 16px;border-bottom:1px solid var(--b1);font-size:13px;color:var(--text2);vertical-align:middle;}
.cat-table tr:last-child td{border-bottom:none;}
.cat-table tr:hover td{background:rgba(255,255,255,.02);}
.cat-icon-cell{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.cat-name{font-size:13px;font-weight:700;color:var(--text);}
.cat-slug{font-size:11px;color:var(--text3);direction:ltr;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
.action-btns{display:flex;gap:6px;}
.action-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;transition:all .15s;text-decoration:none;}
.action-btn:hover{border-color:var(--b2);color:var(--text);}
.action-btn.danger:hover{border-color:var(--red);color:var(--red);background:rgba(240,92,92,.05);}
/* subcategory expand */
.subcat-row{background:var(--bg);}
.subcat-row td{padding:8px 16px 8px 16px;border-bottom:none;}
.subcat-list{display:flex;flex-wrap:wrap;gap:6px;}
.subcat-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;background:var(--s1);border:1px solid var(--b1);font-size:11.5px;color:var(--text2);cursor:default;}
.subcat-chip button{background:none;border:none;cursor:pointer;color:var(--text3);font-size:10px;padding:0;}
.subcat-chip button:hover{color:var(--red);}
/* stats strip */
.stats-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.stat-label{font-size:12px;color:var(--text2);margin-bottom:6px;}
.stat-val{font-size:26px;font-weight:700;line-height:1;}
.stat-sub{font-size:11px;color:var(--text3);margin-top:4px;}
/* modal overlay */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:480px;max-width:calc(100vw - 40px);padding:24px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.modal-close:hover{color:var(--text);}
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
        <a href="/admin/products/categories" class="snav-sub-item active" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div></a>
        <a href="/admin/products/pricing" class="snav-sub-item" style="text-decoration:none;"><div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div></a>
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
        <span class="current">دسته‌بندی‌ها</span>
      </div>
      <div style="flex:1;"></div>
      <button class="hdr-btn hdr-btn-outline" onclick="openModal()">
        <i class="fa-solid fa-plus" style="font-size:11px;"></i> دسته جدید
      </button>
    </header>

    <main class="admin-content">
      <div style="margin-bottom:22px;">
        <div style="font-size:20px;font-weight:800;letter-spacing:-.4px;margin-bottom:4px;">مدیریت دسته‌بندی‌ها</div>
        <div style="font-size:13px;color:var(--text3);">ساختار دسته‌بندی محصولات AIPIX — دسته‌های اصلی و زیردسته‌ها</div>
      </div>

      <!-- Stats -->
      <div class="stats-strip">
        <div class="stat-card">
          <div class="stat-label">دسته‌های اصلی</div>
          <div class="stat-val" style="color:var(--accent);">۱۰</div>
          <div class="stat-sub">ROOT categories</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">کل زیردسته‌ها</div>
          <div class="stat-val" style="color:var(--green);">۳۶</div>
          <div class="stat-sub">در تمام دسته‌ها</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">دسته‌های فعال</div>
          <div class="stat-val" style="color:var(--text);">۹</div>
          <div class="stat-sub">۱ دسته غیرفعال</div>
        </div>
      </div>

      <!-- Two-col layout -->
      <div class="page-grid">

        <!-- ADD FORM -->
        <div>
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-folder-plus"></i> افزودن دسته جدید</div>

            <div class="form-group">
              <label class="form-label">نام فارسی <span class="req">*</span></label>
              <input type="text" class="form-input" placeholder="مثال: کودکان" id="new-name-fa">
            </div>
            <div class="form-group">
              <label class="form-label">نام انگلیسی / کلید <span class="req">*</span></label>
              <input type="text" class="form-input" style="direction:ltr;text-align:left;" placeholder="KIDS" id="new-name-en">
            </div>
            <div class="form-group">
              <label class="form-label">توضیح <span class="hint">اختیاری</span></label>
              <textarea class="form-textarea" placeholder="توضیح کوتاه از این دسته..." id="new-desc"></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">آیکون</label>
              <div class="icon-grid" id="icon-grid">
                <div class="icon-opt selected" data-icon="fa-user" onclick="selectIcon(this)"><i class="fa-solid fa-user"></i></div>
                <div class="icon-opt" data-icon="fa-briefcase" onclick="selectIcon(this)"><i class="fa-solid fa-briefcase"></i></div>
                <div class="icon-opt" data-icon="fa-calendar-star" onclick="selectIcon(this)"><i class="fa-solid fa-calendar-star"></i></div>
                <div class="icon-opt" data-icon="fa-house-user" onclick="selectIcon(this)"><i class="fa-solid fa-house-user"></i></div>
                <div class="icon-opt" data-icon="fa-child" onclick="selectIcon(this)"><i class="fa-solid fa-child"></i></div>
                <div class="icon-opt" data-icon="fa-paw" onclick="selectIcon(this)"><i class="fa-solid fa-paw"></i></div>
                <div class="icon-opt" data-icon="fa-star" onclick="selectIcon(this)"><i class="fa-solid fa-star"></i></div>
                <div class="icon-opt" data-icon="fa-box" onclick="selectIcon(this)"><i class="fa-solid fa-box"></i></div>
                <div class="icon-opt" data-icon="fa-robot" onclick="selectIcon(this)"><i class="fa-solid fa-robot"></i></div>
                <div class="icon-opt" data-icon="fa-video" onclick="selectIcon(this)"><i class="fa-solid fa-video"></i></div>
                <div class="icon-opt" data-icon="fa-camera" onclick="selectIcon(this)"><i class="fa-solid fa-camera"></i></div>
                <div class="icon-opt" data-icon="fa-palette" onclick="selectIcon(this)"><i class="fa-solid fa-palette"></i></div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">رنگ accent</label>
              <div class="color-row" id="color-row">
                <div class="color-dot active" style="background:#a07af5;" data-color="#a07af5" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#0BBF53;" data-color="#0BBF53" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#f05c5c;" data-color="#f05c5c" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#f5923a;" data-color="#f5923a" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#3b82f6;" data-color="#3b82f6" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#ec4899;" data-color="#ec4899" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#14b8a6;" data-color="#14b8a6" onclick="pickColor(this)"></div>
                <div class="color-dot" style="background:#eab308;" data-color="#eab308" onclick="pickColor(this)"></div>
              </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">وضعیت</label>
              <select class="form-select" id="new-status">
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
              </select>
            </div>

            <button class="btn-full btn-primary" onclick="addCategory()">
              <i class="fa-solid fa-plus"></i> ثبت دسته‌بندی
            </button>
          </div>
        </div>

        <!-- CATEGORIES TABLE -->
        <div>
          <div class="cat-table-wrap">
            <table class="cat-table">
              <thead>
                <tr>
                  <th style="width:48px;"></th>
                  <th>نام دسته</th>
                  <th>زیردسته‌ها</th>
                  <th>محصولات</th>
                  <th>وضعیت</th>
                  <th style="width:90px;">عملیات</th>
                </tr>
              </thead>
              <tbody id="cat-tbody">

                <!-- PEOPLE -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(160,122,245,.12);color:#a07af5;"><i class="fa-solid fa-user"></i></div></td>
                  <td><div class="cat-name">PEOPLE</div><div class="cat-slug">شخصی / پرتره</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">Professional</span>
                      <span class="subcat-chip">Fashion</span>
                      <span class="subcat-chip">Lifestyle</span>
                      <span class="subcat-chip">Fitness</span>
                      <span class="subcat-chip">Beauty</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('PEOPLE')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text);font-weight:700;">۳</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" title="ویرایش" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger" title="حذف"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- BUSINESS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(59,130,246,.12);color:#3b82f6;"><i class="fa-solid fa-briefcase"></i></div></td>
                  <td><div class="cat-name">BUSINESS</div><div class="cat-slug">کسب‌وکار</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">Real Estate</span>
                      <span class="subcat-chip">Medical</span>
                      <span class="subcat-chip">Lawyer</span>
                      <span class="subcat-chip">Coach</span>
                      <span class="subcat-chip">Education</span>
                      <span class="subcat-chip">Entrepreneur</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('BUSINESS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text);font-weight:700;">۱</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- EVENTS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(245,146,58,.12);color:#f5923a;"><i class="fa-solid fa-calendar-star"></i></div></td>
                  <td><div class="cat-name">EVENTS</div><div class="cat-slug">مناسبت‌ها</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">Birthday</span>
                      <span class="subcat-chip">Wedding</span>
                      <span class="subcat-chip">Graduation</span>
                      <span class="subcat-chip">Valentine</span>
                      <span class="subcat-chip">Nowruz</span>
                      <span class="subcat-chip">Yalda</span>
                      <span class="subcat-chip">Eid</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('EVENTS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text);font-weight:700;">۴</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- FAMILY -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(11,191,83,.12);color:#0BBF53;"><i class="fa-solid fa-house-user"></i></div></td>
                  <td><div class="cat-name">FAMILY</div><div class="cat-slug">خانواده</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">خانواده کامل</span>
                      <span class="subcat-chip">والدین</span>
                      <span class="subcat-chip">نوزاد</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('FAMILY')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text);font-weight:700;">۱</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- KIDS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(236,72,153,.12);color:#ec4899;"><i class="fa-solid fa-child"></i></div></td>
                  <td><div class="cat-name">KIDS</div><div class="cat-slug">کودکان</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">کودک</span>
                      <span class="subcat-chip">نوجوان</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('KIDS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text3);">—</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- PETS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(234,179,8,.12);color:#eab308;"><i class="fa-solid fa-paw"></i></div></td>
                  <td><div class="cat-name">PETS</div><div class="cat-slug">حیوانات خانگی</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">سگ</span>
                      <span class="subcat-chip">گربه</span>
                      <span class="subcat-chip">سایر</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('PETS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text3);">—</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- ENTERTAINMENT -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(245,146,58,.12);color:#f5923a;"><i class="fa-solid fa-star"></i></div></td>
                  <td><div class="cat-name">ENTERTAINMENT</div><div class="cat-slug">سرگرمی</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">Anime / Manga</span>
                      <span class="subcat-chip">Disney / Pixar</span>
                      <span class="subcat-chip">Superhero</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('ENTERTAINMENT')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text3);">—</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- PRODUCTS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(20,184,166,.12);color:#14b8a6;"><i class="fa-solid fa-box"></i></div></td>
                  <td><div class="cat-name">PRODUCTS</div><div class="cat-slug">محصولات تجاری</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">محصول تجاری</span>
                      <span class="subcat-chip">فود</span>
                      <span class="subcat-chip">فشن</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('PRODUCTS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text3);">—</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- AVATARS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(160,122,245,.12);color:#a07af5;"><i class="fa-solid fa-robot"></i></div></td>
                  <td><div class="cat-name">AVATARS</div><div class="cat-slug">آواتار</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">واقع‌گرایانه</span>
                      <span class="subcat-chip">کارتونی</span>
                      <span class="subcat-chip">سه‌بعدی</span>
                      <button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('AVATARS')">+ اضافه</button>
                    </div>
                  </td>
                  <td style="color:var(--text);font-weight:700;">۱</td>
                  <td><span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;فعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

                <!-- VIDEOS -->
                <tr>
                  <td><div class="cat-icon-cell" style="background:rgba(240,92,92,.12);color:#f05c5c;"><i class="fa-solid fa-video"></i></div></td>
                  <td><div class="cat-name">VIDEOS</div><div class="cat-slug">ویدیو — فاز بعد</div></td>
                  <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                      <span class="subcat-chip">Personal</span>
                      <span class="subcat-chip">Business</span>
                      <span class="subcat-chip">Social Media</span>
                      <span class="subcat-chip">Kids</span>
                      <span class="subcat-chip">AI Tools</span>
                    </div>
                  </td>
                  <td style="color:var(--text3);">—</td>
                  <td><span class="badge badge-gray"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;غیرفعال</span></td>
                  <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /page-grid -->
    </main>
  </div>

</div><!-- /admin-wrap -->

<!-- ══ ADD CATEGORY MODAL ══ -->
<div class="modal-bg" id="modal-bg" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-folder-plus" style="color:var(--accent);margin-left:8px;"></i>ویرایش / افزودن دسته</span>
      <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="form-group">
      <label class="form-label">نام فارسی <span class="req">*</span></label>
      <input type="text" class="form-input" placeholder="نام دسته به فارسی">
    </div>
    <div class="form-group">
      <label class="form-label">کلید انگلیسی <span class="req">*</span></label>
      <input type="text" class="form-input" style="direction:ltr;text-align:left;" placeholder="CATEGORY_KEY">
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">وضعیت</label>
      <select class="form-select"><option>فعال</option><option>غیرفعال</option></select>
    </div>
    <div style="display:flex;gap:8px;margin-top:18px;">
      <button style="flex:1;" class="btn-full btn-primary" onclick="closeModal()"><i class="fa-solid fa-check"></i> ذخیره</button>
      <button style="width:100px;" class="btn-full" style="background:var(--s1);border:1px solid var(--b1);color:var(--text2);" onclick="closeModal()">انصراف</button>
    </div>
  </div>
</div>

<!-- ══ ADD SUBCAT MODAL ══ -->
<div class="modal-bg" id="subcat-modal" onclick="if(event.target===this)closeSubcat()">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-sitemap" style="color:var(--accent);margin-left:8px;"></i>افزودن زیردسته به <strong id="subcat-parent-name"></strong></span>
      <button class="modal-close" onclick="closeSubcat()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="form-group">
      <label class="form-label">نام زیردسته <span class="req">*</span></label>
      <input type="text" class="form-input" placeholder="مثال: Wedding Planner" id="subcat-input">
    </div>
    <div style="display:flex;gap:8px;margin-top:6px;">
      <button style="flex:1;" class="btn-full btn-primary" onclick="closeSubcat()"><i class="fa-solid fa-plus"></i> افزودن</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function selectIcon(el){document.querySelectorAll('.icon-opt').forEach(e=>e.classList.remove('selected'));el.classList.add('selected');}
function pickColor(el){document.querySelectorAll('.color-dot').forEach(e=>e.classList.remove('active'));el.classList.add('active');}
function openModal(){document.getElementById('modal-bg').classList.add('open');}
function closeModal(){document.getElementById('modal-bg').classList.remove('open');}
function openSubcatModal(name){document.getElementById('subcat-parent-name').textContent=name;document.getElementById('subcat-modal').classList.add('open');}
function closeSubcat(){document.getElementById('subcat-modal').classList.remove('open');}
function addCategory(){
  const fa=document.getElementById('new-name-fa').value.trim();
  const en=document.getElementById('new-name-en').value.trim();
  if(!fa||!en){alert('نام فارسی و انگلیسی اجباری است');return;}
  const icon=document.querySelector('.icon-opt.selected')?.dataset.icon||'fa-folder';
  const color=document.querySelector('.color-dot.active')?.dataset.color||'#a07af5';
  const status=document.getElementById('new-status').value;
  const tbody=document.getElementById('cat-tbody');
  const tr=document.createElement('tr');
  tr.innerHTML=`
    <td><div class="cat-icon-cell" style="background:${color}22;color:${color};"><i class="fa-solid ${icon}"></i></div></td>
    <td><div class="cat-name">${en}</div><div class="cat-slug">${fa}</div></td>
    <td><div style="display:flex;flex-wrap:wrap;gap:4px;"><button style="background:none;border:1px dashed var(--b2);border-radius:6px;padding:2px 8px;font-size:10px;color:var(--text3);cursor:pointer;" onclick="openSubcatModal('${en}')">+ اضافه</button></div></td>
    <td style="color:var(--text3);">—</td>
    <td><span class="badge ${status==='active'?'badge-green':'badge-gray'}"><i class="fa-solid fa-circle" style="font-size:6px;"></i>&nbsp;${status==='active'?'فعال':'غیرفعال'}</span></td>
    <td><div class="action-btns"><button class="action-btn" onclick="openModal()"><i class="fa-solid fa-pen"></i></button><button class="action-btn danger"><i class="fa-solid fa-trash"></i></button></div></td>
  `;
  tbody.appendChild(tr);
  document.getElementById('new-name-fa').value='';
  document.getElementById('new-name-en').value='';
  document.getElementById('new-desc').value='';
}
</script>
@endsection
