@extends('layouts.admin')
@section('title', 'لیست‌های هوشمند کاربران — وطن استودیو')

@push('styles')
<style>
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
/* smart list cards */
.smart-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;}
.smart-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;cursor:pointer;transition:all .2s;position:relative;overflow:hidden;}
.smart-card:hover{border-color:var(--b2);transform:translateY(-1px);}
.smart-card.active{border-color:var(--accent);background:rgba(160,122,245,.06);}
.smart-card::before{content:'';position:absolute;top:0;right:0;width:3px;height:100%;border-radius:0 14px 14px 0;}
.smart-card.color-green::before{background:var(--green);}
.smart-card.color-accent::before{background:var(--accent);}
.smart-card.color-orange::before{background:var(--orange);}
.smart-card.color-red::before{background:var(--red);}
.smart-card.color-blue::before{background:#3b82f6;}
.smart-icon{font-size:20px;margin-bottom:10px;}
.smart-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;}
.smart-desc{font-size:11px;color:var(--text3);margin-bottom:10px;}
.smart-count{font-size:22px;font-weight:800;}
/* table */
.table-section{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.table-section-header{padding:14px 18px;border-bottom:1px solid var(--b1);display:flex;align-items:center;justify-content:space-between;}
.table-section-title{font-size:13px;font-weight:700;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.data-table td{padding:12px 16px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;text-decoration:none;margin-left:3px;}
.action-btn:hover{border-color:var(--accent);color:var(--accent);}
.skeleton{background:linear-gradient(90deg,var(--b1) 25%,var(--b2) 50%,var(--b1) 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:6px;height:14px;}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>
@endpush

@section('content')
<div class="flex min-h-screen" dir="rtl" style="background:var(--bg);">

  @include('admin.partials.sidebar')
  <div class="sidebar-overlay hidden max-[900px]:block fixed inset-0 z-[99] bg-black/[0.55] opacity-0 pointer-events-none transition-opacity duration-[250ms]" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <main class="mr-64 flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

<!-- کارت‌های لیست هوشمند -->
      <div class="smart-grid" id="smartGrid">

        <div class="smart-card color-green" onclick="loadList('new_today', this)">
          <div class="smart-icon" style="color:var(--green);">🆕</div>
          <div class="smart-title">کاربران جدید امروز</div>
          <div class="smart-desc">ثبت‌نام کرده‌اند در ۲۴ ساعت اخیر</div>
          <div class="smart-count" style="color:var(--green);" id="count-new_today">—</div>
        </div>

        <div class="smart-card color-red" onclick="loadList('no_token', this)">
          <div class="smart-icon" style="color:var(--red);">🔴</div>
          <div class="smart-title">بدون توکن</div>
          <div class="smart-desc">موجودی توکن صفر یا کمتر از ۵</div>
          <div class="smart-count" style="color:var(--red);" id="count-no_token">—</div>
        </div>

        <div class="smart-card color-orange" onclick="loadList('low_token', this)">
          <div class="smart-icon" style="color:var(--orange);">⚠️</div>
          <div class="smart-title">توکن کم</div>
          <div class="smart-desc">بین ۵ تا ۵۰ توکن باقی‌مانده</div>
          <div class="smart-count" style="color:var(--orange);" id="count-low_token">—</div>
        </div>

        <div class="smart-card color-accent" onclick="loadList('inactive_30d', this)">
          <div class="smart-icon" style="color:var(--accent);">😴</div>
          <div class="smart-title">غیرفعال ۳۰ روز</div>
          <div class="smart-desc">ورود به سیستم نداشته‌اند</div>
          <div class="smart-count" style="color:var(--accent);" id="count-inactive_30d">—</div>
        </div>

        <div class="smart-card color-green" onclick="loadList('vip', this)">
          <div class="smart-icon" style="color:var(--green);">⭐</div>
          <div class="smart-title">VIP / پر مصرف</div>
          <div class="smart-desc">بیش از ۵۰۰ تصویر تولید کرده</div>
          <div class="smart-count" style="color:var(--green);" id="count-vip">—</div>
        </div>

        <div class="smart-card color-orange" onclick="loadList('no_order', this)">
          <div class="smart-icon" style="color:var(--orange);">🛒</div>
          <div class="smart-title">بدون خرید</div>
          <div class="smart-desc">هیچ سفارشی ثبت نکرده‌اند</div>
          <div class="smart-count" style="color:var(--orange);" id="count-no_order">—</div>
        </div>

        <div class="smart-card color-blue" onclick="loadList('referred', this)">
          <div class="smart-icon" style="color:#3b82f6;">🔗</div>
          <div class="smart-title">از طریق بلاگر</div>
          <div class="smart-desc">معرفی‌شده از لینک رفرال</div>
          <div class="smart-count" style="color:#3b82f6;" id="count-referred">—</div>
        </div>

        <div class="smart-card color-red" onclick="loadList('churned', this)">
          <div class="smart-icon" style="color:var(--red);">📉</div>
          <div class="smart-title">ریزش کرده</div>
          <div class="smart-desc">خرید کرده ولی ۶۰ روز غیرفعال</div>
          <div class="smart-count" style="color:var(--red);" id="count-churned">—</div>
        </div>

        <div class="smart-card color-accent" onclick="loadList('high_usage', this)">
          <div class="smart-icon" style="color:var(--accent);">🚀</div>
          <div class="smart-title">پر تقاضا این هفته</div>
          <div class="smart-desc">بیش از ۲۰ درخواست در ۷ روز</div>
          <div class="smart-count" style="color:var(--accent);" id="count-high_usage">—</div>
        </div>

      </div>

      <!-- جدول نتایج -->
      <div class="table-section" id="tableSection" style="display:none;">
        <div class="table-section-header">
          <div class="table-section-title" id="tableTitle">نتایج</div>
          <div style="display:flex;gap:8px;align-items:center;">
            <span id="tableCount" class="badge badge-purple"></span>
            <button onclick="exportList()" class="hdr-btn" style="height:30px;font-size:11px;"><i class="fa-solid fa-download"></i> خروجی CSV</button>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>کاربر</th>
                <th>موبایل / ایمیل</th>
                <th style="text-align:center;">توکن</th>
                <th style="text-align:center;">تصاویر</th>
                <th>آخرین ورود</th>
                <th style="text-align:center;">عملیات</th>
              </tr>
            </thead>
            <tbody id="smartTableBody">
              <tr id="loadingRow">
                <td colspan="6" style="padding:40px;text-align:center;color:var(--text3);">
                  <i class="fa-solid fa-spinner fa-spin" style="margin-left:8px;"></i> در حال بارگذاری...
                </td>
              </tr>
            </tbody>
          </table>

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='لیست‌های هوشمند';});

const listMeta = {
  new_today:   { title: 'کاربران جدید امروز', emoji: '🆕' },
  no_token:    { title: 'بدون توکن', emoji: '🔴' },
  low_token:   { title: 'توکن کم', emoji: '⚠️' },
  inactive_30d:{ title: 'غیرفعال ۳۰ روز', emoji: '😴' },
  vip:         { title: 'کاربران VIP', emoji: '⭐' },
  no_order:    { title: 'بدون خرید', emoji: '🛒' },
  referred:    { title: 'معرفی‌شده از بلاگر', emoji: '🔗' },
  churned:     { title: 'ریزش کرده', emoji: '📉' },
  high_usage:  { title: 'پر تقاضا این هفته', emoji: '🚀' },
};

// Demo data
const demoData = {
  new_today: [{id:1,name:'علی احمدی',phone:'09121234567',email:'ali@ex.com',token:100,imgs:0,last:'الان'},{id:2,name:'مریم رضایی',phone:'09351234567',email:'maryam@ex.com',token:50,imgs:0,last:'۲ ساعت پیش'}],
  no_token: [{id:3,name:'حسن کریمی',phone:'09211234567',email:'hasan@ex.com',token:0,imgs:12,last:'دیروز'},{id:4,name:'فاطمه موسوی',phone:'09011234567',email:'f@ex.com',token:2,imgs:5,last:'۳ روز پیش'}],
  low_token: [{id:5,name:'رضا صادقی',phone:'09181234567',email:'reza@ex.com',token:35,imgs:80,last:'امروز'}],
  inactive_30d: [{id:6,name:'نگین تهرانی',phone:'09141234567',email:'nagin@ex.com',token:200,imgs:30,last:'۳۵ روز پیش'}],
  vip: [{id:7,name:'محمد علوی',phone:'09111234567',email:'m@ex.com',token:5000,imgs:820,last:'دیروز'}],
  no_order: [{id:8,name:'سارا کمالی',phone:'09171234567',email:'sara@ex.com',token:10,imgs:2,last:'هفته پیش'}],
  referred: [{id:9,name:'امیر حسینی',phone:'09381234567',email:'amir@ex.com',token:150,imgs:45,last:'امروز'}],
  churned: [{id:10,name:'لیلا نوری',phone:'09031234567',email:'leila@ex.com',token:0,imgs:60,last:'۶۵ روز پیش'}],
  high_usage: [{id:11,name:'بهراد شریفی',phone:'09221234567',email:'b@ex.com',token:300,imgs:95,last:'همین الان'}],
};

// Load counts on page load
document.addEventListener('DOMContentLoaded', () => {
  Object.keys(demoData).forEach(key => {
    const el = document.getElementById('count-' + key);
    if (el) el.textContent = demoData[key].length;
    // Try real API
    fetch('/api/v1/admin/users/smart-list/' + key + '/count', {headers:{'Accept':'application/json'}})
      .then(r => r.json()).then(d => { if (el && d.count !== undefined) el.textContent = d.count; }).catch(()=>{});
  });
});

function loadList(type, card) {
  // Active card
  document.querySelectorAll('.smart-card').forEach(c => c.classList.remove('active'));
  card.classList.add('active');

  const meta = listMeta[type];
  document.getElementById('tableTitle').textContent = meta.emoji + ' ' + meta.title;
  document.getElementById('tableSection').style.display = 'block';
  document.getElementById('tableSection').scrollIntoView({behavior:'smooth', block:'nearest'});

  renderRows(null); // show loading

  fetch('/api/v1/admin/users/smart-list/' + type, {headers:{'Accept':'application/json'}})
    .then(r => r.json())
    .then(d => renderRows(d.data || d))
    .catch(() => renderRows(demoData[type] || []));
}

function renderRows(users) {
  const tbody = document.getElementById('smartTableBody');
  if (!users) {
    tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin" style="margin-left:8px;"></i> در حال بارگذاری...</td></tr>`;
    return;
  }
  document.getElementById('tableCount').textContent = users.length + ' کاربر';
  if (!users.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="padding:40px;text-align:center;color:var(--text3);">کاربری در این دسته یافت نشد.</td></tr>`;
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">${(u.name||'ک').charAt(0)}</div>
          <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">${u.name||'—'}</div><div style="font-size:10px;color:var(--text3);">ID: ${u.id}</div></div>
        </div>
      </td>
      <td style="font-family:monospace;font-size:11.5px;">${u.phone||u.email||'—'}</td>
      <td style="text-align:center;font-weight:700;color:${(u.token||0)<10?'var(--red)':(u.token||0)<50?'var(--orange)':'var(--green)'};">${(u.token||0).toLocaleString()}</td>
      <td style="text-align:center;font-weight:700;color:var(--accent);">${(u.imgs||u.generations_count||0).toLocaleString()}</td>
      <td style="font-size:11px;color:var(--text3);">${u.last||u.last_login||'—'}</td>
      <td style="text-align:center;">
        <a href="/admin/users/${u.id}/logs" class="action-btn" title="لاگ"><i class="fa-solid fa-history"></i></a>
        <a href="/admin/users/tokens?user_id=${u.id}" class="action-btn" title="توکن"><i class="fa-solid fa-coins"></i></a>
      </td>
    </tr>
  `).join('');
}

function exportList() {
  // Simple CSV export from current table
  const rows = [...document.querySelectorAll('#smartTableBody tr')];
  const csv = rows.map(r => [...r.querySelectorAll('td')].map(td => '"' + td.innerText.trim().replace(/"/g,'""') + '"').join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], {type:'text/csv;charset=utf-8;'});
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'smart-list.csv';
  link.click();
}
</script>
@endpush
