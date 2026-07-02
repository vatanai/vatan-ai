@extends('layouts.admin')
@section('title', 'آنالیتیکس سفارشات — وطن استودیو')

@push('styles')
<style>
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;}
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.kpi-label{font-size:11px;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:22px;font-weight:800;line-height:1;}
.kpi-sub{font-size:10px;color:var(--text3);margin-top:4px;}
/* chart cards */
.chart-card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px;margin-bottom:16px;}
.chart-title{font-size:13px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;}
/* status breakdown */
.status-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--b1);}
.status-row:last-child{border-bottom:none;}
.status-bar-wrap{flex:1;height:6px;border-radius:99px;background:var(--b1);overflow:hidden;}
.status-bar-fill{height:100%;border-radius:99px;}
/* leaderboard */
.leader-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--b1);}
.leader-item:last-child{border-bottom:none;}
.leader-rank{width:24px;text-align:center;font-size:12px;font-weight:800;color:var(--text3);flex-shrink:0;}
/* heatmap bars */
.heat-bar{display:inline-block;width:30px;border-radius:4px 4px 0 0;background:var(--accent);opacity:.7;transition:opacity .2s;cursor:default;}
.heat-bar:hover{opacity:1;}
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
          <div class="kpi-label">کل سفارشات</div>
          <div class="kpi-val" id="kpi-total">—</div>
          <div class="kpi-sub">در بازه انتخابی</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">درآمد کل</div>
          <div class="kpi-val" style="color:var(--green);" id="kpi-revenue">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">میانگین سفارش</div>
          <div class="kpi-val" style="color:var(--accent);" id="kpi-avg">—</div>
          <div class="kpi-sub">تومان</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">نرخ موفق</div>
          <div class="kpi-val" style="color:var(--orange);" id="kpi-success">—</div>
          <div class="kpi-sub">از کل</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">از بلاگرها</div>
          <div class="kpi-val" style="color:var(--red);" id="kpi-blogger">—</div>
          <div class="kpi-sub">سفارش</div>
        </div>
      </div>

      <!-- Main chart: revenue + orders combined -->
      <div class="chart-card">
        <div class="chart-title">
          <span>روند درآمد و تعداد سفارش</span>
          <div style="display:flex;gap:12px;font-size:11px;font-weight:600;">
            <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;border-radius:2px;background:#0BBF53;display:inline-block;"></span>درآمد</span>
            <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;border-radius:2px;background:#a07af5;display:inline-block;"></span>سفارشات</span>
          </div>
        </div>
        <canvas id="mainChart" height="200"></canvas>
      </div>

      <!-- Row 2: product pie + source breakdown -->
      <div class="two-col">
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>توزیع محصولات</span></div>
          <div style="display:flex;gap:20px;align-items:center;">
            <canvas id="productChart" style="max-width:150px;max-height:150px;"></canvas>
            <div id="productLegend" style="flex:1;"></div>
          </div>
        </div>
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>منبع سفارش</span></div>
          <div id="sourceBreakdown" style="padding-top:8px;"></div>
        </div>
      </div>
      <div style="margin-bottom:16px;"></div>

      <!-- Row 3: hourly heatmap + weekday chart -->
      <div class="two-col">
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>هیت‌مپ ساعتی</span></div>
          <div id="heatmap" style="display:flex;align-items:flex-end;gap:4px;height:80px;"></div>
          <div style="display:flex;gap:4px;margin-top:4px;" id="heatLabels"></div>
        </div>
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>فروش روزهای هفته</span></div>
          <canvas id="weekdayChart" height="120"></canvas>
        </div>
      </div>
      <div style="margin-bottom:16px;"></div>

      <!-- Row 4: status breakdown + top users -->
      <div class="two-col">
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>وضعیت سفارشات</span></div>
          <div id="statusBreakdown"></div>
        </div>
        <div class="chart-card" style="margin-bottom:0;">
          <div class="chart-title"><span>برترین خریداران</span></div>
          <div id="topUsers">

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='آنالیتیکس سفارشات';});

let mainChart, productChart, weekdayChart;

const COLORS = ['#a07af5','#0BBF53','#f5923a','#f05c5c','#3b82f6','#10b981'];

function setRange(days, btn) {
  document.querySelectorAll('[onclick*="setRange"]').forEach(b=>b.classList.remove('active-range'));
  btn.classList.add('active-range');
  loadData(days);
}

function loadData(days) {
  const labels = days <= 7
    ? ['شنبه','یک‌شنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه']
    : days <= 30 ? Array.from({length:30},(_,i)=>`${i+1}`)
    : days <= 90 ? Array.from({length:12},(_,i)=>`هفته ${i+1}`)
    : ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

  const revenueData = labels.map(()=>Math.floor(800000+Math.random()*2000000));
  const ordersData = labels.map(()=>Math.floor(5+Math.random()*30));
  const totalOrders = ordersData.reduce((a,b)=>a+b,0);
  const totalRev = revenueData.reduce((a,b)=>a+b,0);

  document.getElementById('kpi-total').textContent = totalOrders.toLocaleString();
  document.getElementById('kpi-revenue').textContent = (totalRev/1000000).toFixed(1)+'M';
  document.getElementById('kpi-avg').textContent = Math.floor(totalRev/totalOrders/1000).toLocaleString()+'K';
  document.getElementById('kpi-success').textContent = '۸۴٪';
  document.getElementById('kpi-blogger').textContent = Math.floor(totalOrders*0.3).toLocaleString();

  renderMainChart(labels, revenueData, ordersData);
  renderProductChart();
  renderSourceBreakdown(totalOrders);
  renderHeatmap();
  renderWeekdayChart();
  renderStatusBreakdown(totalOrders);
  renderTopUsers();

  fetch(`/api/v1/admin/orders/analytics?days=${days}`, {headers:{'Accept':'application/json'}}).catch(()=>{});
}

function renderMainChart(labels, revenueData, ordersData) {
  if (mainChart) mainChart.destroy();
  mainChart = new Chart(document.getElementById('mainChart'), {
    data:{
      labels,
      datasets:[
        {type:'bar', label:'درآمد', data:revenueData, backgroundColor:'rgba(11,191,83,.2)', borderColor:'#0BBF53', borderWidth:2, borderRadius:4, yAxisID:'y'},
        {type:'line', label:'سفارشات', data:ordersData, borderColor:'#a07af5', backgroundColor:'rgba(160,122,245,.08)', borderWidth:2, tension:.4, pointRadius:3, yAxisID:'y1', fill:true},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
      plugins:{legend:{display:false}},
      scales:{
        x:{ticks:{color:'#4d7a56',font:{size:10,family:'Vazirmatn'}},grid:{color:'rgba(255,255,255,.03)'}},
        y:{position:'right',ticks:{color:'#4d7a56',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}},
        y1:{position:'left',ticks:{color:'#a07af5',font:{size:10}},grid:{display:false}},
      }}
  });
}

function renderProductChart() {
  const products = ['اشتراک پریمیوم','توکن ۱۰۰۰','توکن ۵۰۰۰','اشتراک پایه','سایر'];
  const data = [42, 28, 15, 10, 5];
  if (productChart) productChart.destroy();
  productChart = new Chart(document.getElementById('productChart'), {
    type:'doughnut',
    data:{labels:products, datasets:[{data, backgroundColor:COLORS, borderWidth:0}]},
    options:{responsive:false, cutout:'65%', plugins:{legend:{display:false}}}
  });
  document.getElementById('productLegend').innerHTML = products.map((p,i)=>`
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
      <div style="width:8px;height:8px;border-radius:50%;background:${COLORS[i]};flex-shrink:0;"></div>
      <div style="flex:1;font-size:11.5px;color:var(--text2);">${p}</div>
      <div style="font-size:12px;font-weight:700;color:var(--text);">${data[i]}٪</div>
    </div>
  `).join('');
}

function renderSourceBreakdown(total) {
  const sources = [
    {label:'مستقیم', pct:70, color:'var(--accent)'},
    {label:'از بلاگر', pct:22, color:'var(--green)'},
    {label:'کد تخفیف', pct:8, color:'var(--orange)'},
  ];
  document.getElementById('sourceBreakdown').innerHTML = sources.map(s=>`
    <div class="status-row">
      <div style="font-size:12.5px;font-weight:600;color:var(--text2);width:90px;">${s.label}</div>
      <div class="status-bar-wrap"><div class="status-bar-fill" style="width:${s.pct}%;background:${s.color};"></div></div>
      <div style="font-size:12px;font-weight:700;color:var(--text);width:36px;text-align:left;">${s.pct}٪</div>
    </div>
  `).join('');
}

function renderHeatmap() {
  const hours = Array.from({length:24},()=>Math.floor(Math.random()*100));
  const max = Math.max(...hours);
  document.getElementById('heatmap').innerHTML = hours.map((v,i)=>`
    <div class="heat-bar" style="height:${Math.max(4,Math.round(v/max*70))}px;" title="${i}:00 — ${v} سفارش"></div>
  `).join('');
  document.getElementById('heatLabels').innerHTML = [0,6,12,18,23].map(h=>`
    <span style="font-size:9px;color:var(--text3);flex:1;">${h}:00</span>
  `).join('');
}

function renderWeekdayChart() {
  if (weekdayChart) weekdayChart.destroy();
  const days = ['شنبه','یک‌شنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه'];
  weekdayChart = new Chart(document.getElementById('weekdayChart'), {
    type:'bar',
    data:{
      labels:days,
      datasets:[{data:days.map(()=>Math.floor(10+Math.random()*60)), backgroundColor:'rgba(160,122,245,.3)', borderColor:'#a07af5', borderWidth:2, borderRadius:4}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
      scales:{x:{ticks:{color:'#4d7a56',font:{size:9,family:'Vazirmatn'}},grid:{display:false}},y:{ticks:{color:'#4d7a56',font:{size:9}},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
}

function renderStatusBreakdown(total) {
  const statuses = [
    {label:'پرداخت شده', pct:84, color:'var(--green)'},
    {label:'در انتظار', pct:9, color:'var(--orange)'},
    {label:'شکست خورده', pct:5, color:'var(--red)'},
    {label:'برگشتی', pct:2, color:'var(--text3)'},
  ];
  document.getElementById('statusBreakdown').innerHTML = statuses.map(s=>`
    <div class="status-row">
      <div style="font-size:12.5px;font-weight:600;color:var(--text2);width:100px;">${s.label}</div>
      <div class="status-bar-wrap"><div class="status-bar-fill" style="width:${s.pct}%;background:${s.color};"></div></div>
      <div style="font-size:12px;font-weight:700;color:var(--text);width:36px;text-align:left;">${s.pct}٪</div>
    </div>
  `).join('');
}

function renderTopUsers() {
  const users = [
    {name:'محمد علوی', orders:14, revenue:2800000},
    {name:'رضا صادقی', orders:11, revenue:2200000},
    {name:'سارا کمالی', orders:9, revenue:1800000},
    {name:'علی احمدی', orders:7, revenue:1400000},
    {name:'مریم رضایی', orders:5, revenue:1000000},
  ];
  document.getElementById('topUsers').innerHTML = users.map((u,i)=>`
    <div class="leader-item">
      <div class="leader-rank" style="color:${i<3?'var(--orange)':'var(--text3)'}">#${i+1}</div>
      <div style="flex:1;">
        <div style="font-size:12.5px;font-weight:700;color:var(--text);">${u.name}</div>
        <div style="font-size:10.5px;color:var(--text3);">${u.orders} سفارش</div>
      </div>
      <div style="font-size:12px;font-weight:700;color:var(--green);">${(u.revenue/1000000).toFixed(1)}M ت</div>
    </div>
  `).join('');
}

loadData(7);
</script>
@endpush
