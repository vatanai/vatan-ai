@extends('layouts.admin')
@section('title', 'جزئیات محصول — AIPIX Admin')

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
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;}.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn-outline{background:var(--s2);color:var(--text2);border:1px solid var(--b1);}
.hdr-btn-outline:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;}
.hdr-btn-primary:hover{opacity:.9;}
.hdr-btn-danger{background:rgba(240,92,92,.08);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.hdr-btn-danger:hover{background:rgba(240,92,92,.15);}

/* layout */
.show-layout{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;}
.card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 22px;margin-bottom:16px;}
.card-title{font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid var(--b1);margin-bottom:16px;}
.card-title i{color:var(--accent);}

/* hero */
.product-hero{background:linear-gradient(135deg,rgba(160,122,245,.12),rgba(11,191,83,.05));border:1px solid rgba(160,122,245,.2);border-radius:14px;padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;}
.product-thumb{width:90px;height:90px;border-radius:14px;background:linear-gradient(135deg,rgba(160,122,245,.3),rgba(160,122,245,.1));display:flex;align-items:center;justify-content:center;font-size:36px;color:var(--accent);flex-shrink:0;}
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}

/* detail rows */
.detail-row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--b1);}
.detail-row:last-child{border-bottom:none;}
.detail-key{font-size:11.5px;color:var(--text3);width:130px;flex-shrink:0;padding-top:2px;}
.detail-val{flex:1;font-size:13px;color:var(--text);font-weight:500;}

/* input schema */
.schema-row{display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg);border:1px solid var(--b1);border-radius:8px;margin-bottom:6px;}
.schema-type{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;background:rgba(160,122,245,.1);color:var(--accent);font-family:monospace;}
.schema-name{flex:1;font-size:12px;font-weight:600;color:var(--text);}
.schema-req{font-size:10px;font-weight:700;color:var(--red);}

/* prompt box */
.prompt-box{background:var(--bg);border:1px solid var(--b1);border-radius:8px;padding:12px 14px;font-size:12px;color:var(--text2);line-height:1.8;font-family:monospace;}
.prompt-var{display:inline-block;background:rgba(160,122,245,.15);color:var(--accent);border:1px solid rgba(160,122,245,.3);border-radius:5px;padding:1px 6px;font-size:10.5px;margin:1px;}

/* stats sidebar */
.stat-row{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--b1);}
.stat-row:last-child{border-bottom:none;}
.stat-label{font-size:12px;color:var(--text3);}
.stat-val{font-size:14px;font-weight:700;color:var(--text);}

/* mini orders table */
.mini-table{width:100%;border-collapse:collapse;}
.mini-table th{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:7px 10px;text-align:right;border-bottom:1px solid var(--b1);}
.mini-table td{padding:9px 10px;border-bottom:1px solid rgba(34,34,48,.5);font-size:11.5px;color:var(--text2);}
.mini-table tr:last-child td{border-bottom:none;}
.mini-table tr:hover td{background:rgba(255,255,255,.012);}

/* sparkline */
.sparkline-wrap{display:flex;align-items:flex-end;gap:3px;height:40px;margin-top:8px;}
.spark-bar{flex:1;border-radius:2px 2px 0 0;background:var(--accent);opacity:.6;transition:opacity .15s;}
.spark-bar:hover{opacity:1;}

/* fallback chain */
.fallback-chain{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:8px;}
.fallback-item{display:flex;align-items:center;gap:5px;}
.model-tag{background:var(--bg);border:1px solid var(--b1);border-radius:6px;padding:4px 9px;font-size:10.5px;font-family:monospace;color:var(--text2);}
.model-tag.primary{border-color:rgba(160,122,245,.3);color:var(--accent);}
.chain-arrow{color:var(--text3);font-size:10px;}

/* color swatches */
.color-swatch{width:20px;height:20px;border-radius:5px;border:2px solid var(--b2);display:inline-block;vertical-align:middle;margin-left:4px;}

/* sample grid */
.sample-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.sample-cell{aspect-ratio:1;background:linear-gradient(135deg,rgba(160,122,245,.15),rgba(160,122,245,.05));border:1px solid var(--b1);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--accent);}
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
      <a href="/admin/products/dashboard" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">داشبورد محصولات</div></a>
      <a href="/admin/products/create" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">ثبت محصول جدید</div></a>
      <a href="/admin/products/categories" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">دسته‌بندی‌ها</div></a>
      <a href="/admin/products/pricing" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">قیمت‌گذاری</div></a>
      <a href="/admin/orders" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div></a>
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
        <a href="/admin/products">محصولات</a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="current">عکس حرفه‌ای لینکدین</span>
      </div>
      <div style="flex:1;"></div>
      <a href="/admin/products" class="hdr-btn hdr-btn-outline"><i class="fa-solid fa-arrow-right" style="font-size:11px;"></i> بازگشت</a>
      <a href="/admin/products/1/edit" class="hdr-btn hdr-btn-primary"><i class="fa-solid fa-pen" style="font-size:11px;"></i> ویرایش</a>
      <button class="hdr-btn hdr-btn-danger"><i class="fa-solid fa-box-archive" style="font-size:11px;"></i> آرشیو</button>
    </header>

    <main class="admin-content">

      <!-- Product Hero -->
      <div class="product-hero">
        <div class="product-thumb"><i class="fa-solid fa-user-tie"></i></div>
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <div style="font-size:22px;font-weight:800;">عکس حرفه‌ای لینکدین</div>
            <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;فعال</span>
            <span class="badge badge-purple"><i class="fa-solid fa-star" style="font-size:8px;"></i>&nbsp;ویژه</span>
            <span class="badge badge-orange">پرفروش</span>
          </div>
          <div style="font-size:12px;color:var(--text3);font-family:monospace;margin-bottom:10px;">linkedin-professional-photo</div>
          <div style="font-size:13px;color:var(--text2);line-height:1.6;max-width:600px;">تبدیل عکس معمولی به عکس حرفه‌ای برای لینکدین با هوش مصنوعی — مناسب برای پروفایل‌های شغلی و کسب‌وکاری</div>
          <div style="display:flex;gap:16px;margin-top:12px;">
            <div style="font-size:11px;color:var(--text3);">دسته: <span style="color:var(--text2);font-weight:600;">افراد / پرتره</span></div>
            <div style="font-size:11px;color:var(--text3);">هزینه: <span style="color:var(--accent);font-weight:700;">۵ کردیت</span></div>
            <div style="font-size:11px;color:var(--text3);">خروجی: <span style="color:var(--text2);font-weight:600;">image</span></div>
            <div style="font-size:11px;color:var(--text3);">ساخته شده: <span style="color:var(--text2);font-weight:600;">۱۴۰۵/۰۱/۱۵</span></div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
          <div style="font-size:30px;font-weight:900;color:var(--text);">۶۲۴</div>
          <div style="font-size:11px;color:var(--text3);">سفارش این ماه</div>
          <div style="font-size:12px;color:var(--green);font-weight:700;">↑ ۱۸٪</div>
        </div>
      </div>

      <div class="show-layout">
        <!-- LEFT COLUMN -->
        <div>
          <!-- AI Config -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-microchip"></i> پیکربندی AI</div>
            <div class="detail-row">
              <div class="detail-key">مدل اصلی</div>
              <div class="detail-val"><span style="font-family:monospace;font-size:12px;color:var(--accent);">black-forest-labs/flux-1.1-pro</span></div>
            </div>
            <div class="detail-row">
              <div class="detail-key">زنجیره Fallback</div>
              <div class="detail-val">
                <div class="fallback-chain">
                  <div class="fallback-item"><span class="model-tag primary">flux-1.1-pro</span></div>
                  <span class="chain-arrow">→</span>
                  <div class="fallback-item"><span class="model-tag">stability-ai/sd-3.5</span></div>
                  <span class="chain-arrow">→</span>
                  <div class="fallback-item"><span class="model-tag">ideogram/v3</span></div>
                </div>
              </div>
            </div>
            <div class="detail-row">
              <div class="detail-key">Prompt Template</div>
              <div class="detail-val" style="padding-top:4px;">
                <div class="prompt-box">Professional LinkedIn profile photo, formal attire, <span class="prompt-var">{{clothing_style}}</span>, clean background, high resolution, 4K, <span class="prompt-var">{{background_color}}</span>, confident expression</div>
              </div>
            </div>
            <div class="detail-row">
              <div class="detail-key">Negative Prompt</div>
              <div class="detail-val"><div class="prompt-box" style="color:var(--red);opacity:.8;">blurry, low quality, cartoon, anime, watermark, text overlay, distorted face</div></div>
            </div>
            <div class="detail-row">
              <div class="detail-key">تنظیمات</div>
              <div class="detail-val" style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="badge badge-green">NSFW filter</span>
                <span class="badge badge-purple">Face enhance</span>
                <span class="badge badge-gray">Background remove</span>
              </div>
            </div>
          </div>

          <!-- Input Schema -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-sliders"></i> Input Schema <span style="margin-right:auto;font-size:11px;font-weight:500;color:var(--text3);">۴ فیلد ورودی</span></div>
            <div class="schema-row">
              <span class="schema-type">image_upload</span>
              <span class="schema-name">user_photo</span>
              <span style="font-size:11px;color:var(--text2);">عکس شخصی کاربر</span>
              <span class="schema-req">*الزامی</span>
            </div>
            <div class="schema-row">
              <span class="schema-type">select</span>
              <span class="schema-name">clothing_style</span>
              <span style="font-size:11px;color:var(--text2);">استایل لباس</span>
              <span class="badge badge-gray" style="font-size:9.5px;">اختیاری</span>
            </div>
            <div class="schema-row">
              <span class="schema-type">color_picker</span>
              <span class="schema-name">background_color</span>
              <span style="font-size:11px;color:var(--text2);">رنگ پس‌زمینه</span>
              <span class="badge badge-gray" style="font-size:9.5px;">اختیاری</span>
            </div>
            <div class="schema-row">
              <span class="schema-type">text</span>
              <span class="schema-name">name</span>
              <span style="font-size:11px;color:var(--text2);">نام (برای watermark)</span>
              <span class="badge badge-gray" style="font-size:9.5px;">اختیاری</span>
            </div>
          </div>

          <!-- Output Config -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-image"></i> خروجی و نمایش</div>
            <div class="detail-row"><div class="detail-key">نوع خروجی</div><div class="detail-val">image</div></div>
            <div class="detail-row"><div class="detail-key">فرمت</div><div class="detail-val">JPEG, PNG</div></div>
            <div class="detail-row"><div class="detail-key">رزولوشن</div><div class="detail-val">1024×1024 px</div></div>
            <div class="detail-row"><div class="detail-key">Aspect Ratio</div><div class="detail-val">1:1</div></div>
            <div class="detail-row"><div class="detail-key">Watermark</div><div class="detail-val"><span class="badge badge-red">غیرفعال</span></div></div>
            <div class="detail-row"><div class="detail-key">تحویل</div><div class="detail-val">دانلود مستقیم + کتابخانه</div></div>
            <div class="detail-row"><div class="detail-key">استایل کارت</div><div class="detail-val"><span class="badge badge-purple">glass</span></div></div>
            <div class="detail-row">
              <div class="detail-key">رنگ‌های تم</div>
              <div class="detail-val">
                <span class="color-swatch" style="background:#a07af5;"></span>
                <span class="color-swatch" style="background:#6a4dcc;"></span>
                <span class="color-swatch" style="background:#111116;"></span>
                <span style="font-size:11px;color:var(--text3);margin-right:6px;">۳ رنگ</span>
              </div>
            </div>
          </div>

          <!-- Sample Outputs -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-images"></i> نمونه خروجی‌ها</div>
            <div class="sample-grid">
              <div class="sample-cell"><i class="fa-solid fa-user-tie"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-user-tie"></i></div>
              <div class="sample-cell"><i class="fa-solid fa-user-tie"></i></div>
              <div class="sample-cell" style="background:var(--b1);color:var(--text3);font-size:13px;"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell" style="background:var(--b1);color:var(--text3);font-size:13px;"><i class="fa-solid fa-plus"></i></div>
              <div class="sample-cell" style="background:var(--b1);color:var(--text3);font-size:13px;"><i class="fa-solid fa-plus"></i></div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
          <!-- Stats Card -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-chart-line"></i> آمار ۳۰ روز</div>
            <div class="sparkline-wrap" style="margin-bottom:8px;">
              <div class="spark-bar" style="height:35%;"></div>
              <div class="spark-bar" style="height:50%;"></div>
              <div class="spark-bar" style="height:45%;"></div>
              <div class="spark-bar" style="height:70%;"></div>
              <div class="spark-bar" style="height:60%;"></div>
              <div class="spark-bar" style="height:80%;"></div>
              <div class="spark-bar" style="height:75%;"></div>
              <div class="spark-bar" style="height:90%;"></div>
              <div class="spark-bar" style="height:85%;"></div>
              <div class="spark-bar" style="height:100%;background:var(--green);opacity:1;"></div>
            </div>
            <div class="stat-row"><div class="stat-label">سفارشات کل</div><div class="stat-val">۶۲۴</div></div>
            <div class="stat-row"><div class="stat-label">سفارشات موفق</div><div class="stat-val" style="color:var(--green);">۶۱۰</div></div>
            <div class="stat-row"><div class="stat-label">نرخ موفقیت</div><div class="stat-val" style="color:var(--green);">۹۷.۸٪</div></div>
            <div class="stat-row"><div class="stat-label">درآمد ماه</div><div class="stat-val" style="color:var(--text);">۳۱۲M</div></div>
            <div class="stat-row"><div class="stat-label">میانگین زمان</div><div class="stat-val">۱۸.۶s</div></div>
            <div class="stat-row"><div class="stat-label">از طریق بلاگر</div><div class="stat-val" style="color:var(--accent);">۴۱٪</div></div>
            <div class="stat-row"><div class="stat-label">Fallback rate</div><div class="stat-val" style="color:var(--orange);">۸٪</div></div>
          </div>

          <!-- Pricing -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-coins"></i> قیمت‌گذاری</div>
            <div class="stat-row"><div class="stat-label">نوع قیمت</div><div class="stat-val">per_credit</div></div>
            <div class="stat-row"><div class="stat-label">هزینه</div><div class="stat-val" style="color:var(--accent);">۵ کردیت</div></div>
            <div class="stat-row"><div class="stat-label">سطح</div><div class="stat-val"><span class="badge badge-purple">Standard</span></div></div>
            <div class="stat-row"><div class="stat-label">تخفیف فعال</div><div class="stat-val"><span class="badge badge-orange">BDAY20 — ۲۰٪</span></div></div>
          </div>

          <!-- Recent Orders -->
          <div class="card" style="padding-bottom:4px;">
            <div class="card-title">
              <i class="fa-solid fa-receipt"></i> آخرین سفارشات
              <a href="/admin/orders" style="margin-right:auto;font-size:11px;color:var(--accent);text-decoration:none;opacity:.7;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7">همه ←</a>
            </div>
            <table class="mini-table">
              <thead>
                <tr>
                  <th>شناسه</th>
                  <th>کاربر</th>
                  <th>وضعیت</th>
                  <th>زمان</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">#۲۴۸۱</td>
                  <td>سارا احمدی</td>
                  <td><span class="badge badge-green" style="font-size:9px;">موفق</span></td>
                  <td style="font-size:10.5px;">۱۸s</td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">#۲۴۷۷</td>
                  <td>زهرا حسینی</td>
                  <td><span class="badge badge-green" style="font-size:9px;">موفق</span></td>
                  <td style="font-size:10.5px;">۲۲s</td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">#۲۴۶۲</td>
                  <td>رضا موسوی</td>
                  <td><span class="badge badge-red" style="font-size:9px;">ناموفق</span></td>
                  <td style="font-size:10.5px;">60s</td>
                </tr>
                <tr>
                  <td style="font-family:monospace;font-size:10.5px;color:var(--text3);">#۲۴۵۸</td>
                  <td>نیلوفر کریمی</td>
                  <td><span class="badge badge-green" style="font-size:9px;">موفق</span></td>
                  <td style="font-size:10.5px;">۱۵s</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Tags -->
          <div class="card">
            <div class="card-title"><i class="fa-solid fa-tags"></i> تگ‌ها و دسته</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
              <span style="background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);border-radius:6px;padding:3px 10px;font-size:11px;">لینکدین</span>
              <span style="background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);border-radius:6px;padding:3px 10px;font-size:11px;">پروفایل</span>
              <span style="background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);border-radius:6px;padding:3px 10px;font-size:11px;">حرفه‌ای</span>
              <span style="background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);border-radius:6px;padding:3px 10px;font-size:11px;">کسب‌وکار</span>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--b1);">
              <div style="font-size:11.5px;color:var(--text3);margin-bottom:6px;">پلتفرم نمایش</div>
              <div style="display:flex;gap:6px;">
                <span class="badge badge-green"><i class="fa-solid fa-mobile-screen" style="font-size:9px;"></i>&nbsp;موبایل</span>
                <span class="badge badge-blue"><i class="fa-solid fa-desktop" style="font-size:9px;"></i>&nbsp;وب</span>
              </div>
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
// Link product action buttons
document.addEventListener('DOMContentLoaded', function() {
  // Prefill with productId if passed
  const pid = {{ $productId ?? 1 }};
  console.log('Viewing product ID:', pid);
});
</script>
@endsection
