@extends('layouts.admin')

 <aside class="admin-sidebar">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 16px;border-bottom:1px solid var(--b1);flex-shrink:0;">
      <div style="width:36px;height:36px;border-radius:10px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900;color:#fff;box-shadow:0 0 16px rgba(11,191,83,.3);">و</div>
      <div>
        <div style="font-size:14px;font-weight:800;">وطن استودیو</div>
        <div style="font-size:9px;color:var(--text3);letter-spacing:2.5px;text-transform:uppercase;">Admin Panel</div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--b1);">
      <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">م</div>
      <div style="flex:1;">
        <div style="font-size:12px;font-weight:700;">محسن رضایی</div>
        <div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div>
      </div>
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
        <div class="snav-sub-item">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">داشبورد محصولات</div>
          <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:rgba(245,146,58,.08);color:var(--orange);border:1px solid rgba(245,146,58,.2);">در حال طراحی</span>
        </div>
        <a href="/admin/products" class="snav-sub-item" style="text-decoration:none;">
          <div class="snav-dot"></div>
          <div class="snav-sub-label">لیست محصولات</div>
        </a>
        <a href="/admin/products/create" class="snav-sub-item active" style="text-decoration:none;">
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
      <div style="height:1px;background:var(--b1);margin:8px 12px;"></div>
      <div class="snav-item">
        <div class="snav-icon"><i class="fa-solid fa-gear"></i></div>
        <div class="snav-label">تنظیمات</div>
      </div>
    </nav>
  </aside>
