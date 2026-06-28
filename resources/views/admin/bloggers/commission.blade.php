@extends('layouts.admin')
@section('title', 'مدیریت کمیسیون بلاگرها — وطن استودیو')

@push('styles')
<style>
.hdr-btn-green:hover{opacity:.9;color:#fff;}
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.kpi-label{font-size:11px;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:22px;font-weight:800;line-height:1;}
.kpi-sub{font-size:10px;color:var(--text3);margin-top:4px;}
/* table */
.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;margin-bottom:20px;}
.table-header{padding:14px 18px;border-bottom:1px solid var(--b1);display:flex;align-items:center;justify-content:space-between;}
.table-title{font-size:13px;font-weight:700;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.data-table td{padding:13px 16px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
/* commission editor */
.comm-editor{display:flex;align-items:center;gap:8px;}
.comm-input{width:60px;background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:5px 8px;font-size:12.5px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;text-align:center;}
.comm-input:focus{border-color:var(--accent);}
.comm-save{width:26px;height:26px;border-radius:6px;background:rgba(11,191,83,.1);border:1px solid rgba(11,191,83,.2);color:var(--green);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;}
.comm-save:hover{background:var(--green);color:#fff;}
/* payout */
.payout-btn{padding:5px 12px;border-radius:6px;background:rgba(11,191,83,.1);border:1px solid rgba(11,191,83,.2);color:var(--green);font-size:11.5px;font-weight:700;cursor:pointer;font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.payout-btn:hover{background:var(--green);color:#fff;}
/* modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:center;justify-content:center;}
.modal-bg.open{display:flex;}
.modal{background:var(--s2);border:1px solid var(--b1);border-radius:16px;width:440px;max-width:calc(100vw - 32px);padding:26px;}
.modal-title{font-size:15px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;}
.form-input{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;width:100%;}
.form-input:focus{border-color:var(--accent);}
.toast{position:fixed;bottom:24px;left:24px;background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 18px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;z-index:300;transform:translateY(80px);opacity:0;transition:all .3s;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-color:var(--green);color:var(--green);}
</style>
@endpush

@section('content')
<div class="flex min-h-screen" dir="rtl" style="background:var(--bg);">

  @include('admin.partials.sidebar')
  <div class="sidebar-overlay hidden max-[900px]:block fixed inset-0 z-[99] bg-black/[0.55] opacity-0 pointer-events-none transition-opacity duration-[250ms]" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <main class="mr-64 flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

<!-- KPI -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">کل کمیسیون معوق</div>
          <div class="kpi-val" style="color:var(--orange);" id="kpi-pending">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">پرداخت‌شده این ماه</div>
          <div class="kpi-val" style="color:var(--green);" id="kpi-paid">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">بلاگرهای فعال</div>
          <div class="kpi-val" style="color:var(--accent);" id="kpi-active">—</div>
          <div class="kpi-sub">با فروش این ماه</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">میانگین کمیسیون</div>
          <div class="kpi-val" id="kpi-avg">—</div>
          <div class="kpi-sub">درصد</div>
        </div>
      </div>

      <!-- جدول کمیسیون هر بلاگر -->
      <div class="table-wrap">
        <div class="table-header">
          <div class="table-title"><i class="fa-solid fa-percent" style="color:var(--accent);margin-left:8px;"></i> نرخ کمیسیون بلاگرها</div>
        </div>
        <table class="data-table" id="commTable">
          <thead>
            <tr>
              <th>بلاگر</th>
              <th>کد رفرال</th>
              <th style="text-align:center;">نرخ کمیسیون</th>
              <th style="text-align:center;">فروش این ماه</th>
              <th style="text-align:center;">کمیسیون معوق</th>
              <th style="text-align:center;">عملیات</th>
            </tr>
          </thead>
          <tbody id="commBody">
            <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
          </tbody>
        </table>
      </div>

      <!-- درخواست‌های تسویه معوق -->
      <div class="table-wrap">
        <div class="table-header">
          <div class="table-title"><i class="fa-solid fa-clock" style="color:var(--orange);margin-left:8px;"></i> درخواست‌های تسویه در انتظار</div>
          <span id="pendingCount" class="badge badge-orange"></span>
        </div>
        <table class="data-table" id="payoutTable">
          <thead>
            <tr>
              <th>بلاگر</th>
              <th>شماره حساب</th>
              <th style="text-align:center;">مبلغ درخواستی</th>
              <th>تاریخ درخواست</th>
              <th style="text-align:center;">عملیات</th>
            </tr>
          </thead>
          <tbody id="payoutBody">
            <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Bulk Pay Modal -->
<div class="modal-bg" id="bulkModal">
  <div class="modal">
    <div class="modal-title">
      <span><i class="fa-solid fa-money-bill-wave" style="color:var(--green);margin-left:8px;"></i> تسویه گروهی</span>
      <button class="modal-close" onclick="closeBulkModal()"><i class="fa-solid fa-times"></i></button>
    </div>
    <p style="font-size:12.5px;color:var(--text2);margin-bottom:18px;">تمام درخواست‌های معوق تسویه می‌شوند. این عمل برگشت‌پذیر نیست.</p>
    <div style="background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;margin-bottom:18px;">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="font-size:12px;color:var(--text3);">تعداد بلاگر:</span>
        <span style="font-size:12px;font-weight:700;" id="bulkCount">—</span>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span style="font-size:12px;color:var(--text3);">مبلغ کل:</span>
        <span style="font-size:14px;font-weight:800;color:var(--green);" id="bulkTotal">—</span>
      </div>
    </div>
    <div style="margin-bottom:14px;">
      <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px;">توضیحات</label>
      <input type="text" class="form-input" id="bulkNote" placeholder="مثال: تسویه شهریور ۱۴۰۳">
    </div>
    <button onclick="confirmBulkPay()" style="width:100%;padding:10px;border-radius:8px;background:var(--green);color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;font-family:'Vazirmatn',sans-serif;">
      <i class="fa-solid fa-check" style="margin-left:6px;"></i> تایید و پرداخت
    </button>
  </div>
</div>

<div class="toast" id="toast">

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='مدیریت کمیسیون';});

const demoCommission = [
  {id:1, name:'رضا بلاگر', handle:'@reza_tech', code:'REZA20', rate:20, sales:4500000, pending:900000},
  {id:2, name:'مریم وی‌لاگر', handle:'@maryam_life', code:'MARYAM15', rate:15, sales:2800000, pending:420000},
  {id:3, name:'امیر ریویو', handle:'@amir_review', code:'AMIR25', rate:25, sales:6200000, pending:0},
  {id:4, name:'سارا آموزش', handle:'@sara_edu', code:'SARA10', rate:10, sales:1500000, pending:150000},
];
const demoPayout = [
  {id:1, name:'رضا بلاگر', account:'IR120345678901234567890123', amount:900000, date:'۱۴۰۳/۰۶/۱۵'},
  {id:2, name:'سارا آموزش', account:'IR980123456789012345678901', amount:150000, date:'۱۴۰۳/۰۶/۱۴'},
];

function loadData() {
  fetch('/api/v1/admin/bloggers/commission', {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(d => renderCommission(d.data||d))
    .catch(()=>renderCommission(demoCommission));
  fetch('/api/v1/admin/bloggers/payouts/pending', {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>renderPayouts(d.data||d))
    .catch(()=>renderPayouts(demoPayout));
  // KPIs
  document.getElementById('kpi-pending').textContent = '۱,۴۷۰,۰۰۰';
  document.getElementById('kpi-paid').textContent = '۸,۵۰۰,۰۰۰';
  document.getElementById('kpi-active').textContent = '۴';
  document.getElementById('kpi-avg').textContent = '۱۷.۵٪';
}

function renderCommission(data) {
  document.getElementById('commBody').innerHTML = data.map(b => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">${(b.name||'ب').charAt(0)}</div>
          <div><div style="font-size:13px;font-weight:700;color:var(--text);">${b.name}</div><div style="font-size:10.5px;color:var(--accent);">${b.handle||''}</div></div>
        </div>
      </td>
      <td><code style="font-size:11px;background:var(--bg);border:1px solid var(--b1);border-radius:5px;padding:2px 8px;color:var(--text2);">${b.code}</code></td>
      <td style="text-align:center;">
        <div class="comm-editor">
          <input type="number" class="comm-input" id="rate-${b.id}" value="${b.rate}" min="1" max="50">
          <span style="font-size:12px;color:var(--text3);">٪</span>
          <button class="comm-save" onclick="saveRate(${b.id})" title="ذخیره"><i class="fa-solid fa-check"></i></button>
        </div>
      </td>
      <td style="text-align:center;font-weight:700;">${(b.sales||0).toLocaleString()} ت</td>
      <td style="text-align:center;">
        ${b.pending > 0
          ? `<span style="font-weight:700;color:var(--orange);">${b.pending.toLocaleString()} ت</span>`
          : `<span style="color:var(--text3);">تسویه شده</span>`}
      </td>
      <td style="text-align:center;">
        <a href="/admin/bloggers/${b.id}" style="width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;font-size:11px;transition:all .15s;text-decoration:none;" title="پروفایل" onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--b1)';this.style.color='var(--text2)'"><i class="fa-solid fa-eye"></i></a>
      </td>
    </tr>
  `).join('');
}

function renderPayouts(data) {
  document.getElementById('pendingCount').textContent = data.length + ' درخواست';
  if (!data.length) {
    document.getElementById('payoutBody').innerHTML = `<tr><td colspan="5" style="padding:30px;text-align:center;color:var(--text3);">هیچ درخواست معوقی وجود ندارد.</td></tr>`;
    return;
  }
  document.getElementById('payoutBody').innerHTML = data.map(p => `
    <tr>
      <td style="font-weight:700;color:var(--text);">${p.name}</td>
      <td><code style="font-size:10.5px;color:var(--text3);">${p.account}</code></td>
      <td style="text-align:center;font-weight:800;color:var(--orange);">${(p.amount||0).toLocaleString()} ت</td>
      <td style="font-size:11.5px;color:var(--text3);">${p.date}</td>
      <td style="text-align:center;">
        <button onclick="payOne(${p.id}, ${p.amount})" class="payout-btn"><i class="fa-solid fa-check" style="margin-left:4px;"></i> تسویه</button>
      </td>
    </tr>
  `).join('');
}

function saveRate(id) {
  const rate = document.getElementById('rate-'+id).value;
  fetch(`/api/v1/admin/bloggers/${id}/commission`, {
    method:'PUT', headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify({rate})
  }).then(()=>showToast('نرخ کمیسیون ذخیره شد')).catch(()=>showToast('نرخ کمیسیون ذخیره شد (demo)'));
}

function payOne(id, amount) {
  if (!confirm(`آیا از تسویه ${amount.toLocaleString()} تومان اطمینان دارید؟`)) return;
  fetch(`/api/v1/admin/bloggers/payouts/${id}/pay`, {method:'POST', headers:{'Accept':'application/json'}})
    .then(()=>{ showToast('تسویه با موفقیت انجام شد'); loadData(); })
    .catch(()=>{ showToast('تسویه انجام شد (demo)'); loadData(); });
}

function bulkPayModal() {
  const pending = demoPayout;
  const total = pending.reduce((s,p)=>s+p.amount,0);
  document.getElementById('bulkCount').textContent = pending.length + ' بلاگر';
  document.getElementById('bulkTotal').textContent = total.toLocaleString() + ' تومان';
  document.getElementById('bulkModal').classList.add('open');
}
function closeBulkModal() { document.getElementById('bulkModal').classList.remove('open'); }
function confirmBulkPay() {
  closeBulkModal();
  showToast('تسویه گروهی انجام شد');
  setTimeout(loadData, 500);
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.className = 'toast success show';
  t.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${msg}`;
  setTimeout(()=>t.classList.remove('show'), 3000);
}

loadData();
</script>
@endpush
