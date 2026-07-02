@extends('layouts.admin')
@section('title', 'داشبورد بلاگر — وطن استودیو')

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
.snav-sub-item:hover{background:var(--s2);}.snav-sub-item.active{background:rgba(160,122,245,.1);}
.snav-dot{width:4px;height:4px;border-radius:50%;background:var(--b2);flex-shrink:0;}
.snav-sub-item.active .snav-dot{background:var(--accent);}
.snav-sub-label{flex:1;font-size:11.5px;font-weight:500;color:var(--text2);}
.snav-sub-item.active .snav-sub-label{color:var(--text);font-weight:600;}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);}
.breadcrumb a{color:var(--text2);text-decoration:none;}.breadcrumb a:hover{color:var(--text);}
.breadcrumb .current{color:var(--text);font-weight:600;}
.hdr-btn{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:34px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--b1);background:var(--s2);color:var(--text2);font-family:'Vazirmatn',sans-serif;transition:all .15s;text-decoration:none;}
.hdr-btn:hover{border-color:var(--b2);color:var(--text);}
.hdr-btn-primary{background:var(--accent);color:#fff;border-color:transparent;}
.hdr-btn-primary:hover{opacity:.9;color:#fff;}
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
/* profile header */
.profile-card{background:var(--s2);border:1px solid var(--b1);border-radius:16px;padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;}
.profile-avatar{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;flex-shrink:0;}
.profile-info{flex:1;}
.profile-name{font-size:18px;font-weight:800;margin-bottom:4px;}
.profile-handle{font-size:12px;color:var(--accent);margin-bottom:8px;}
.ref-link{display:flex;align-items:center;gap:8px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:8px 12px;font-family:monospace;font-size:12px;color:var(--text2);}
.copy-btn{padding:4px 10px;border-radius:6px;background:rgba(160,122,245,.1);border:1px solid rgba(160,122,245,.25);color:var(--accent);font-size:11px;font-weight:600;cursor:pointer;font-family:'Vazirmatn',sans-serif;transition:all .15s;flex-shrink:0;}
.copy-btn:hover{background:var(--accent);color:#fff;}
/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.kpi-label{font-size:11px;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:22px;font-weight:800;line-height:1;}
.kpi-sub{font-size:10px;color:var(--text3);margin-top:4px;}
/* charts */
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.chart-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px;}
.chart-title{font-size:13px;font-weight:700;margin-bottom:14px;}
/* table */
.table-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.table-card-header{padding:14px 18px;border-bottom:1px solid var(--b1);font-size:13px;font-weight:700;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.data-table td{padding:12px 16px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
/* modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:480px;max-width:calc(100vw - 32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.form-group{margin-bottom:14px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px;}
.form-input,.form-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;width:100%;}
.form-input:focus,.form-select:focus{border-color:var(--accent);}
.toast{position:fixed;bottom:24px;left:24px;background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 18px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;z-index:300;transform:translateY(80px);opacity:0;transition:all .3s;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-color:var(--green);color:var(--green);}
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
      <div style="flex:1;"><div style="font-size:12px;font-weight:700;">محسن رضایی</div><div style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.25);display:inline-block;margin-top:2px;">مدیر کل</div></div>
    </div>
    <nav style="flex:1;padding:8px 0;">
      <a href="/admin/dashboard" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-bolt-lightning"></i></div><div class="snav-label">مرکز فرماندهی</div></a>
      <div class="snav-section">مدیریت</div>
      <a href="/admin/users" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-users"></i></div><div class="snav-label">کاربران</div></a>
      <a href="/admin/bloggers" class="snav-item active"><div class="snav-icon"><i class="fa-solid fa-bullhorn"></i></div><div class="snav-label">بلاگرها</div></a>
      <a href="/admin/bloggers" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">لیست بلاگرها</div></a>
      <a href="/admin/bloggers/commission" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">مدیریت کمیسیون</div></a>
      <a href="/admin/bloggers/traffic" class="snav-sub-item"><div class="snav-dot"></div><div class="snav-sub-label">گزارش ترافیک</div></a>
      <a href="/admin/orders" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-cart-shopping"></i></div><div class="snav-label">سفارشات</div></a>
      <div class="snav-section">مالی</div>
      <a href="/admin/payments" class="snav-item"><div class="snav-icon"><i class="fa-solid fa-credit-card"></i></div><div class="snav-label">پرداخت‌ها</div></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <header class="admin-header">
      <div class="breadcrumb">
        <a href="/admin/dashboard"><i class="fa-solid fa-house" style="font-size:11px;"></i></a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="/admin/bloggers">بلاگرها</a>
        <span style="color:var(--text3);font-size:10px;"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="current" id="pageTitle">داشبورد بلاگر</span>
      </div>
      <div style="flex:1;"></div>
      <button onclick="openEditModal()" class="hdr-btn hdr-btn-primary"><i class="fa-solid fa-pen"></i> ویرایش</button>
      <a href="/admin/bloggers" class="hdr-btn"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
    </header>

    <div class="admin-content">

      <!-- Profile -->
      <div class="profile-card" id="profileCard">
        <div class="profile-avatar" id="pAvatar">ب</div>
        <div style="flex:1;">
          <div class="profile-name" id="pName">در حال بارگذاری...</div>
          <div class="profile-handle" id="pHandle"></div>
          <div style="display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap;">
            <div class="ref-link">
              <i class="fa-solid fa-link" style="color:var(--accent);font-size:11px;"></i>
              <span id="pRefLink">—</span>
              <button class="copy-btn" onclick="copyLink()">کپی</button>
            </div>
            <span class="badge" id="pStatus"></span>
          </div>
        </div>
        <div style="text-align:left;">
          <div style="font-size:10px;color:var(--text3);margin-bottom:4px;">کمیسیون</div>
          <div style="font-size:28px;font-weight:800;color:var(--accent);" id="pRate">—</div>
          <div style="font-size:10px;color:var(--text3);">درصد</div>
        </div>
      </div>

      <!-- KPI -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">کل کلیک</div>
          <div class="kpi-val" style="color:var(--accent);" id="kpi-clicks">—</div>
          <div class="kpi-sub">از لینک رفرال</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">کاربر معرفی‌شده</div>
          <div class="kpi-val" style="color:var(--green);" id="kpi-users">—</div>
          <div class="kpi-sub">ثبت‌نام موفق</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">کل درآمد</div>
          <div class="kpi-val" style="color:var(--orange);" id="kpi-revenue">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">کمیسیون معوق</div>
          <div class="kpi-val" style="color:var(--red);" id="kpi-pending">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-row" style="margin-bottom:20px;">
        <div class="chart-card">
          <div class="chart-title">درآمد ماهانه (۶ ماه)</div>
          <canvas id="revenueChart" height="180"></canvas>
        </div>
        <div class="chart-card">
          <div class="chart-title">ترافیک ۳۰ روز اخیر</div>
          <canvas id="dailyChart" height="180"></canvas>
        </div>
      </div>

      <!-- کاربران معرفی‌شده -->
      <div class="table-card" style="margin-bottom:20px;">
        <div class="table-card-header"><i class="fa-solid fa-users" style="color:var(--green);margin-left:8px;"></i> کاربران معرفی‌شده</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>کاربر</th>
              <th>تاریخ ثبت‌نام</th>
              <th style="text-align:center;">خرید انجام‌شده</th>
              <th style="text-align:center;">درآمد حاصله</th>
            </tr>
          </thead>
          <tbody id="referralBody">
            <tr><td colspan="4" style="padding:30px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
          </tbody>
        </table>
      </div>

      <!-- تاریخچه کمیسیون -->
      <div class="table-card">
        <div class="table-card-header"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);margin-left:8px;"></i> تاریخچه تسویه کمیسیون</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>ماه</th>
              <th style="text-align:center;">فروش</th>
              <th style="text-align:center;">کمیسیون</th>
              <th>وضعیت</th>
              <th>تاریخ پرداخت</th>
            </tr>
          </thead>
          <tbody id="commHistBody">
            <tr><td colspan="5" style="padding:30px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-pen" style="color:var(--accent);margin-left:8px;"></i> ویرایش بلاگر</span>
      <button class="modal-close" onclick="closeEditModal()"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="form-group">
      <label class="form-label">نرخ کمیسیون (%)</label>
      <input type="number" class="form-input" id="editRate" min="1" max="50">
    </div>
    <div class="form-group">
      <label class="form-label">وضعیت</label>
      <select class="form-select" id="editStatus">
        <option value="active">فعال</option>
        <option value="inactive">غیرفعال</option>
        <option value="suspended">معلق</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">یادداشت</label>
      <input type="text" class="form-input" id="editNote" placeholder="یادداشت داخلی...">
    </div>
    <button onclick="saveEdit()" style="width:100%;padding:10px;border-radius:8px;background:var(--accent);color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;font-family:'Vazirmatn',sans-serif;">ذخیره تغییرات</button>
  </div>
</div>

<div class="toast" id="toast"></div>
@endsection

@push('scripts')
<script>
const bloggerId = {{ $bloggerId ?? 1 }};

const demoProfile = {
  id: bloggerId, name:'رضا بلاگر', handle:'@reza_tech', code:'REZA20', rate:20,
  status:'active', clicks:1240, users:186, revenue:4500000, pending:900000,
  ref_link: window.location.origin + '/?ref=REZA20'
};

const demoReferrals = [
  {name:'علی احمدی', date:'۱۴۰۳/۰۵/۱۰', purchases:3, revenue:450000},
  {name:'مریم رضایی', date:'۱۴۰۳/۰۵/۰۸', purchases:1, revenue:150000},
  {name:'حسن کریمی', date:'۱۴۰۳/۰۵/۰۲', purchases:2, revenue:300000},
];

const demoCommHist = [
  {month:'شهریور ۱۴۰۳', sales:4500000, comm:900000, status:'pending', date:'—'},
  {month:'مرداد ۱۴۰۳', sales:3800000, comm:760000, status:'paid', date:'۱۴۰۳/۰۶/۰۱'},
  {month:'تیر ۱۴۰۳', sales:2900000, comm:580000, status:'paid', date:'۱۴۰۳/۰۵/۰۱'},
];

function loadProfile() {
  fetch(`/api/v1/admin/bloggers/${bloggerId}`, {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>renderProfile(d.data||d))
    .catch(()=>renderProfile(demoProfile));
}

function renderProfile(b) {
  document.getElementById('pAvatar').textContent = (b.name||'ب').charAt(0);
  document.getElementById('pName').textContent = b.name||'—';
  document.getElementById('pageTitle').textContent = b.name||'داشبورد بلاگر';
  document.getElementById('pHandle').textContent = b.handle||'';
  document.getElementById('pRefLink').textContent = b.ref_link || (window.location.origin+'/?ref='+b.code);
  document.getElementById('pRate').textContent = b.rate+'٪';
  document.getElementById('pStatus').textContent = b.status==='active'?'فعال':'غیرفعال';
  document.getElementById('pStatus').className = 'badge ' + (b.status==='active'?'badge-green':'badge-red');
  document.getElementById('kpi-clicks').textContent = (b.clicks||0).toLocaleString();
  document.getElementById('kpi-users').textContent = (b.users||0).toLocaleString();
  document.getElementById('kpi-revenue').textContent = (b.revenue||0).toLocaleString();
  document.getElementById('kpi-pending').textContent = (b.pending||0).toLocaleString();
  document.getElementById('editRate').value = b.rate;
  document.getElementById('editStatus').value = b.status;
}

function loadCharts() {
  // Revenue bar chart
  new Chart(document.getElementById('revenueChart'), {
    type:'bar',
    data:{
      labels:['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور'],
      datasets:[{label:'درآمد (تومان)', data:[1800000,2200000,1600000,2900000,3800000,4500000], backgroundColor:'rgba(160,122,245,.3)', borderColor:'#a07af5', borderWidth:2, borderRadius:6}]
    },
    options:{responsive:true,maintainAspectRatio:false, plugins:{legend:{display:false}},
      scales:{x:{ticks:{color:'#4d7a56',font:{size:10,family:'Vazirmatn'}},grid:{color:'rgba(255,255,255,.03)'}},y:{ticks:{color:'#4d7a56',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
  // Daily line chart
  const days = Array.from({length:30},(_,i)=>`${i+1}`);
  new Chart(document.getElementById('dailyChart'), {
    type:'line',
    data:{
      labels:days,
      datasets:[
        {label:'کلیک', data:days.map(()=>Math.floor(20+Math.random()*60)), borderColor:'#a07af5', backgroundColor:'rgba(160,122,245,.06)', borderWidth:2, tension:.4, pointRadius:0, fill:true},
        {label:'ثبت‌نام', data:days.map(()=>Math.floor(2+Math.random()*10)), borderColor:'#0BBF53', backgroundColor:'rgba(11,191,83,.06)', borderWidth:2, tension:.4, pointRadius:0, fill:true},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
      plugins:{legend:{labels:{color:'#4d7a56',font:{family:'Vazirmatn',size:10},boxWidth:8,usePointStyle:true}}},
      scales:{x:{ticks:{color:'#4d7a56',font:{size:9}},grid:{color:'rgba(255,255,255,.03)'}},y:{ticks:{color:'#4d7a56',font:{size:9}},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
}

function loadReferrals() {
  fetch(`/api/v1/admin/bloggers/${bloggerId}/referrals`, {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>renderReferrals(d.data||d))
    .catch(()=>renderReferrals(demoReferrals));
}

function renderReferrals(data) {
  document.getElementById('referralBody').innerHTML = data.map(u=>`
    <tr>
      <td style="font-weight:700;color:var(--text);">${u.name}</td>
      <td style="font-size:11.5px;color:var(--text3);">${u.date}</td>
      <td style="text-align:center;font-weight:700;color:var(--orange);">${u.purchases}</td>
      <td style="text-align:center;font-weight:700;color:var(--green);">${(u.revenue||0).toLocaleString()} ت</td>
    </tr>
  `).join('');
}

function loadCommHistory() {
  fetch(`/api/v1/admin/bloggers/${bloggerId}/commission-history`, {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>renderCommHist(d.data||d))
    .catch(()=>renderCommHist(demoCommHist));
}

function renderCommHist(data) {
  document.getElementById('commHistBody').innerHTML = data.map(h=>`
    <tr>
      <td style="font-weight:700;color:var(--text);">${h.month}</td>
      <td style="text-align:center;">${(h.sales||0).toLocaleString()} ت</td>
      <td style="text-align:center;font-weight:700;color:var(--accent);">${(h.comm||0).toLocaleString()} ت</td>
      <td><span class="badge ${h.status==='paid'?'badge-green':'badge-red'}">${h.status==='paid'?'پرداخت شده':'معوق'}</span></td>
      <td style="font-size:11.5px;color:var(--text3);">${h.date||'—'}</td>
    </tr>
  `).join('');
}

function copyLink() {
  const link = document.getElementById('pRefLink').textContent;
  navigator.clipboard.writeText(link).then(()=>showToast('لینک کپی شد'));
}

function openEditModal() { document.getElementById('editModal').classList.add('open'); }
function closeEditModal() { document.getElementById('editModal').classList.remove('open'); }
function saveEdit() {
  const rate = document.getElementById('editRate').value;
  const status = document.getElementById('editStatus').value;
  const note = document.getElementById('editNote').value;
  fetch(`/api/v1/admin/bloggers/${bloggerId}`, {
    method:'PUT', headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify({rate, status, note})
  }).then(()=>{closeEditModal();showToast('تغییرات ذخیره شد');loadProfile();})
    .catch(()=>{closeEditModal();showToast('تغییرات ذخیره شد (demo)');});
}

function showToast(msg) {
  const t=document.getElementById('toast');
  t.className='toast success show';
  t.innerHTML=`<i class="fa-solid fa-check-circle"></i> ${msg}`;
  setTimeout(()=>t.classList.remove('show'),3000);
}

loadProfile();
loadCharts();
loadReferrals();
loadCommHistory();
</script>
@endpush
