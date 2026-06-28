@extends('layouts.admin')
@section('title', 'گزارش ترافیک بلاگرها — وطن استودیو')

@push('styles')
<style>
/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.kpi-label{font-size:11px;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:22px;font-weight:800;line-height:1;}
.kpi-sub{font-size:10px;color:var(--text3);margin-top:4px;}
/* charts row */
.charts-row{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px;}
.chart-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px;}
.chart-title{font-size:13px;font-weight:700;margin-bottom:14px;}
/* table */
.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.table-header{padding:14px 18px;border-bottom:1px solid var(--b1);display:flex;align-items:center;justify-content:space-between;}
.table-title{font-size:13px;font-weight:700;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.data-table td{padding:12px 16px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
.mini-bar-wrap{height:4px;border-radius:99px;background:var(--b1);overflow:hidden;width:80px;display:inline-block;vertical-align:middle;margin-right:8px;}
.mini-bar-fill{height:100%;border-radius:99px;}
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
          <div class="kpi-label">کل کلیک‌ها</div>
          <div class="kpi-val" style="color:var(--accent);" id="kpi-clicks">—</div>
          <div class="kpi-sub">از لینک‌های رفرال</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">ثبت‌نام موفق</div>
          <div class="kpi-val" style="color:var(--green);" id="kpi-signups">—</div>
          <div class="kpi-sub">کاربر جدید</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">نرخ تبدیل</div>
          <div class="kpi-val" id="kpi-conv">—</div>
          <div class="kpi-sub">کلیک → ثبت‌نام</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">درآمد از بلاگرها</div>
          <div class="kpi-val" style="color:var(--orange);" id="kpi-revenue">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-title">روند کلیک و ثبت‌نام</div>
          <canvas id="trendChart" height="200"></canvas>
        </div>
        <div class="chart-card">
          <div class="chart-title">سهم هر بلاگر</div>
          <canvas id="shareChart" height="200"></canvas>
        </div>
      </div>

      <!-- جدول بلاگرها -->
      <div class="table-wrap">
        <div class="table-header">
          <div class="table-title"><i class="fa-solid fa-ranking-star" style="color:var(--accent);margin-left:8px;"></i> عملکرد هر بلاگر</div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>رتبه</th>
              <th>بلاگر</th>
              <th style="text-align:center;">کلیک</th>
              <th style="text-align:center;">ثبت‌نام</th>
              <th style="text-align:center;">خرید</th>
              <th style="text-align:center;">تبدیل</th>
              <th style="text-align:center;">درآمد</th>
            </tr>
          </thead>
          <tbody id="trafficBody">
            <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--text3);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
          </tbody>
        </table>

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='گزارش ترافیک';});

let trendChart, shareChart;
let currentRange = 7;

const demoData = {
  7:  {clicks:1240, signups:186, conv:'15.0٪', revenue:'۴,۵۰۰,۰۰۰', labels:['ش','ی','د','س','چ','پ','ج'], clicks_data:[120,180,200,160,220,190,170], signup_data:[18,27,30,24,33,28,26]},
  30: {clicks:5800, signups:870, conv:'15.0٪', revenue:'۱۸,۰۰۰,۰۰۰', labels:Array.from({length:30},(_,i)=>`${i+1}`), clicks_data:Array.from({length:30},()=>Math.floor(150+Math.random()*100)), signup_data:Array.from({length:30},()=>Math.floor(20+Math.random()*15))},
  90: {clicks:14200, signups:2130, conv:'15.0٪', revenue:'۵۲,۰۰۰,۰۰۰', labels:Array.from({length:12},(_,i)=>`هفته ${i+1}`), clicks_data:Array.from({length:12},()=>Math.floor(1000+Math.random()*400)), signup_data:Array.from({length:12},()=>Math.floor(150+Math.random()*60))},
};

const demoBloggers = [
  {name:'رضا بلاگر', handle:'@reza_tech', clicks:680, signups:102, purchases:45, conv:'15.0٪', revenue:2200000},
  {name:'مریم وی‌لاگر', handle:'@maryam_life', clicks:380, signups:57, purchases:28, conv:'15.0٪', revenue:1400000},
  {name:'امیر ریویو', handle:'@amir_review', clicks:120, signups:18, purchases:10, conv:'15.0٪', revenue:600000},
  {name:'سارا آموزش', handle:'@sara_edu', clicks:60, signups:9, purchases:3, conv:'15.0٪', revenue:300000},
];

const COLORS = ['#a07af5','#0BBF53','#f5923a','#f05c5c','#3b82f6'];

function setRange(days, btn) {
  currentRange = days;
  document.querySelectorAll('[onclick*="setRange"]').forEach(b => b.classList.remove('active-range'));
  btn.classList.add('active-range');
  loadData();
}

function loadData() {
  const d = demoData[currentRange];
  document.getElementById('kpi-clicks').textContent = d.clicks.toLocaleString();
  document.getElementById('kpi-signups').textContent = d.signups.toLocaleString();
  document.getElementById('kpi-conv').textContent = d.conv;
  document.getElementById('kpi-revenue').textContent = d.revenue;

  renderTrendChart(d);
  renderShareChart(demoBloggers);
  renderTable(demoBloggers);

  fetch(`/api/v1/admin/bloggers/traffic?days=${currentRange}`, {headers:{'Accept':'application/json'}})
    .then(r=>r.json()).then(data => {
      if (data.kpi) {
        document.getElementById('kpi-clicks').textContent = (data.kpi.clicks||0).toLocaleString();
        document.getElementById('kpi-signups').textContent = (data.kpi.signups||0).toLocaleString();
      }
      if (data.bloggers) renderTable(data.bloggers);
    }).catch(()=>{});
}

function renderTrendChart(d) {
  if (trendChart) trendChart.destroy();
  trendChart = new Chart(document.getElementById('trendChart'), {
    type:'line',
    data:{
      labels: d.labels,
      datasets:[
        {label:'کلیک', data:d.clicks_data, borderColor:'#a07af5', backgroundColor:'rgba(160,122,245,.08)', borderWidth:2, tension:.4, pointRadius:3, fill:true},
        {label:'ثبت‌نام', data:d.signup_data, borderColor:'#0BBF53', backgroundColor:'rgba(11,191,83,.08)', borderWidth:2, tension:.4, pointRadius:3, fill:true},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
      plugins:{legend:{labels:{color:'#4d7a56',font:{family:'Vazirmatn',size:11},boxWidth:8,usePointStyle:true}}},
      scales:{x:{ticks:{color:'#4d7a56',font:{size:10}},grid:{color:'rgba(255,255,255,.03)'}},y:{ticks:{color:'#4d7a56',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
}

function renderShareChart(bloggers) {
  if (shareChart) shareChart.destroy();
  shareChart = new Chart(document.getElementById('shareChart'), {
    type:'doughnut',
    data:{
      labels: bloggers.map(b=>b.name),
      datasets:[{data: bloggers.map(b=>b.clicks), backgroundColor:COLORS, borderWidth:0}]
    },
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',
      plugins:{legend:{position:'bottom',labels:{color:'#4d7a56',font:{family:'Vazirmatn',size:10},boxWidth:8,padding:8}}}}
  });
}

function renderTable(bloggers) {
  const max = Math.max(...bloggers.map(b=>b.clicks));
  document.getElementById('trafficBody').innerHTML = bloggers.map((b,i) => `
    <tr>
      <td style="font-weight:800;color:${i===0?'#f5923a':i===1?'#a8c4a8':'var(--text3)'};">#${i+1}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:30px;height:30px;border-radius:50%;background:${COLORS[i%5]+'22'};border:1px solid ${COLORS[i%5]+'44'};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:${COLORS[i%5]};">${(b.name||'ب').charAt(0)}</div>
          <div><div style="font-size:12.5px;font-weight:700;color:var(--text);">${b.name}</div><div style="font-size:10px;color:var(--accent);">${b.handle||''}</div></div>
        </div>
      </td>
      <td style="text-align:center;">
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;">
          <div class="mini-bar-wrap"><div class="mini-bar-fill" style="width:${Math.round(b.clicks/max*100)}%;background:var(--accent);"></div></div>
          <span style="font-weight:700;">${b.clicks.toLocaleString()}</span>
        </div>
      </td>
      <td style="text-align:center;font-weight:700;color:var(--green);">${b.signups.toLocaleString()}</td>
      <td style="text-align:center;font-weight:700;color:var(--orange);">${b.purchases.toLocaleString()}</td>
      <td style="text-align:center;">${b.conv}</td>
      <td style="text-align:center;font-weight:700;color:var(--text);">${(b.revenue||0).toLocaleString()} ت</td>
    </tr>
  `).join('');
}

loadData();
</script>
@endpush
