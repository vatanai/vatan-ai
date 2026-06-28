{{-- ══ AI HUB PAGE ══ --}}
<div id="ai-hub-page" style="display:none;">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-[10px]">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">مرکز هوش مصنوعی</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">نمای کلی عملکرد سیستم AI — آپدیت لحظه‌ای</div>
    </div>
    <div class="flex items-center gap-[6px] bg-green/[0.08] border border-green/[0.2] text-green text-[11px] font-bold py-[6px] px-3 rounded-lg">
      <div class="w-[6px] h-[6px] rounded-full bg-green animate-pulse" style="box-shadow:0 0 8px rgba(11,191,83,0.6)"></div>
      لایو
    </div>
  </div>

  {{-- Stat Cards --}}
  <div class="grid grid-cols-4 gap-[14px] mb-5 max-[1100px]:grid-cols-2 max-[480px]:grid-cols-1">

    <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:bg-green before:opacity-0 hover:before:opacity-100 before:transition-opacity before:duration-200">
      <div class="flex items-center justify-between mb-3">
        <div class="text-[11px] font-semibold text-watan-text3">تولیدات امروز</div>
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-green/[0.08] text-green"><i class="fa-solid fa-image"></i></div>
      </div>
      <div class="text-2xl font-extrabold text-watan-text tracking-tight">۱,۲۴۷</div>
      <div class="text-[11px] text-watan-text3 mt-[6px] flex items-center gap-1"><span class="text-green font-bold">+۱۸٪</span> نسبت به دیروز</div>
    </div>

    <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:bg-accent before:opacity-0 hover:before:opacity-100 before:transition-opacity before:duration-200">
      <div class="flex items-center justify-between mb-3">
        <div class="text-[11px] font-semibold text-watan-text3">نرخ موفقیت</div>
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-accent/[0.08] text-accent"><i class="fa-solid fa-circle-check"></i></div>
      </div>
      <div class="text-2xl font-extrabold text-watan-text tracking-tight">۹۷.۳<span class="text-base font-semibold text-watan-text3">٪</span></div>
      <div class="text-[11px] text-watan-text3 mt-[6px] flex items-center gap-1"><span class="text-green font-bold">+۰.۴٪</span> هفته گذشته</div>
    </div>

    <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:bg-orange before:opacity-0 hover:before:opacity-100 before:transition-opacity before:duration-200">
      <div class="flex items-center justify-between mb-3">
        <div class="text-[11px] font-semibold text-watan-text3">میانگین تأخیر (p50)</div>
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-orange/[0.08] text-orange"><i class="fa-solid fa-bolt"></i></div>
      </div>
      <div class="text-2xl font-extrabold text-watan-text tracking-tight">۸.۴<span class="text-sm font-semibold text-watan-text3"> ثانیه</span></div>
      <div class="text-[11px] text-watan-text3 mt-[6px]">SLA هدف: ۱۲۰s <span class="text-green font-bold">✓ سالم</span></div>
    </div>

    <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:bg-red before:opacity-0 hover:before:opacity-100 before:transition-opacity before:duration-200">
      <div class="flex items-center justify-between mb-3">
        <div class="text-[11px] font-semibold text-watan-text3">هزینه AI امروز</div>
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-red/[0.08] text-red"><i class="fa-solid fa-dollar-sign"></i></div>
      </div>
      <div class="text-2xl font-extrabold text-watan-text tracking-tight">$۱۸.۴۲</div>
      <div class="text-[11px] text-watan-text3 mt-[6px]">بودجه ماهانه: $۵۰۰ — <span class="text-orange font-bold">۴٪ مصرف</span></div>
    </div>

  </div>

  {{-- Chart + Provider Health --}}
  <div class="grid gap-4 mb-4" style="grid-template-columns:1fr 300px;" id="ai-hub-two-col">

    {{-- Latency Chart --}}
    <div class="bg-s1 border border-b1 rounded-[10px] p-5">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-watan-text">تأخیر ۷ روز گذشته</div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-[5px]"><div class="w-[18px] h-[3px] rounded-full bg-green"></div><span class="text-[10px] text-watan-text3">p50</span></div>
          <div class="flex items-center gap-[5px]"><div class="w-[18px] h-[3px] rounded-full bg-orange"></div><span class="text-[10px] text-watan-text3">p95</span></div>
          <div class="flex items-center gap-[5px]"><div class="w-[18px] h-[3px] rounded-full bg-red"></div><span class="text-[10px] text-watan-text3">p99</span></div>
        </div>
      </div>
      <div style="height:180px;"><canvas id="aiLatencyChart"></canvas></div>
    </div>

    {{-- Provider Health + Queue --}}
    <div class="bg-s1 border border-b1 rounded-[10px] p-5 flex flex-col gap-4">
      <div class="text-sm font-bold text-watan-text">وضعیت پروایدرها</div>

      {{-- fal.ai --}}
      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <div class="w-[7px] h-[7px] rounded-full bg-green" style="box-shadow:0 0 6px rgba(11,191,83,0.6)"></div>
            <span class="text-[12px] font-bold text-watan-text">fal.ai</span>
            <span class="text-[9px] font-bold py-px px-[5px] rounded bg-green/[0.08] text-green border border-green/[0.2]">Primary</span>
          </div>
          <span class="text-[11px] font-bold text-green">آنلاین</span>
        </div>
        <div class="grid grid-cols-3 gap-[6px]">
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-watan-text">۷.۲s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p50</div>
          </div>
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-orange">۱۲.۸s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p95</div>
          </div>
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-red">۱۸.۳s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p99</div>
          </div>
        </div>
      </div>

      {{-- Replicate --}}
      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <div class="w-[7px] h-[7px] rounded-full bg-accent" style="box-shadow:0 0 6px rgba(160,122,245,0.6)"></div>
            <span class="text-[12px] font-bold text-watan-text">Replicate</span>
            <span class="text-[9px] font-bold py-px px-[5px] rounded bg-accent/[0.08] text-accent border border-accent/[0.2]">Fallback</span>
          </div>
          <span class="text-[11px] font-bold text-green">آنلاین</span>
        </div>
        <div class="grid grid-cols-3 gap-[6px]">
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-watan-text">۱۱.۴s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p50</div>
          </div>
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-orange">۱۹.۷s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p95</div>
          </div>
          <div class="bg-s2 rounded-lg p-[8px] text-center border border-b1">
            <div class="text-[13px] font-extrabold text-red">۲۸.۱s</div>
            <div class="text-[9px] text-watan-text3 mt-px">p99</div>
          </div>
        </div>
      </div>

      {{-- Redis Queue --}}
      <div class="pt-3 border-t border-b1">
        <div class="text-[11px] font-bold text-watan-text3 mb-3">صف پردازش Redis</div>
        <div class="space-y-[6px]">
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-watan-text">در انتظار</span>
            <span class="text-[12px] font-bold text-orange">۱۲</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-watan-text">در حال پردازش</span>
            <span class="text-[12px] font-bold text-green">۳</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-watan-text">شکست‌خورده</span>
            <span class="text-[12px] font-bold text-red">۱</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- Recent Jobs --}}
  <div class="bg-s1 border border-b1 rounded-[10px] overflow-hidden">
    <div class="flex items-center justify-between p-4 border-b border-b1">
      <div class="text-sm font-bold text-watan-text">آخرین جاب‌های اجرا</div>
      <button
        class="text-[11px] font-semibold text-green hover:text-watan-text transition-colors duration-150"
        onclick="setActiveSub(null,'هوش مصنوعی','لاگ‌های اجرا','ai-logs-page');document.getElementById('breadcrumb').textContent='هوش مصنوعی — لاگ‌های اجرا'">
        مشاهده همه <i class="fa-solid fa-arrow-left text-[9px]"></i>
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b border-b1">
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">Job ID</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">کاربر</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">محصول</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">پروایدر</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">زمان</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">هزینه</th>
            <th class="text-right py-[10px] px-4 text-[10px] font-bold text-watan-text3 uppercase tracking-[1.5px]">وضعیت</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-b1 hover:bg-s2 transition-colors duration-150">
            <td class="py-[10px] px-4 font-mono text-[11px] text-accent font-bold">#J5021</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text">ali.rezaei</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text2">عکس رستوران</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></td>
            <td class="py-[10px] px-4 text-[12px] font-bold text-watan-text">۷.۲s</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text3">$0.014</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td>
          </tr>
          <tr class="border-b border-b1 hover:bg-s2 transition-colors duration-150">
            <td class="py-[10px] px-4 font-mono text-[11px] text-accent font-bold">#J5020</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text">maryam.k</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text2">پس‌زمینه غذا</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></td>
            <td class="py-[10px] px-4 text-[12px] font-bold text-watan-text">۸.۹s</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text3">$0.018</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td>
          </tr>
          <tr class="border-b border-b1 hover:bg-s2 transition-colors duration-150">
            <td class="py-[10px] px-4 font-mono text-[11px] text-accent font-bold">#J5019</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text">hassan.m</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text2">ویترین کافه</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-accent/[0.08] text-accent border border-accent/[0.2]">Replicate</span></td>
            <td class="py-[10px] px-4 text-[12px] font-bold text-watan-text">۱۲.۴s</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text3">$0.021</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td>
          </tr>
          <tr class="border-b border-b1 hover:bg-s2 transition-colors duration-150">
            <td class="py-[10px] px-4 font-mono text-[11px] text-accent font-bold">#J5018</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text">sara.t</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text2">عکس رستوران</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></td>
            <td class="py-[10px] px-4 text-[12px] font-bold text-red">—</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text3">—</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-red/[0.08] text-red border border-red/[0.2]">خطا</span></td>
          </tr>
          <tr class="hover:bg-s2 transition-colors duration-150">
            <td class="py-[10px] px-4 font-mono text-[11px] text-accent font-bold">#J5017</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text">reza.d</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text2">منو دیجیتال</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">fal.ai</span></td>
            <td class="py-[10px] px-4 text-[12px] font-bold text-watan-text">۶.۸s</td>
            <td class="py-[10px] px-4 text-[12px] text-watan-text3">$0.013</td>
            <td class="py-[10px] px-4"><span class="text-[10px] font-bold py-px px-2 rounded-md bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<style>
  @media (max-width: 900px) {
    #ai-hub-two-col { grid-template-columns: 1fr !important; }
  }
</style>

<script>
let aiHubChart = null;

function initAiHubChart() {
  if (typeof Chart === 'undefined') return;
  const ctx = document.getElementById('aiLatencyChart');
  if (!ctx) return;
  if (aiHubChart) aiHubChart.destroy();
  const isDark = !document.body.classList.contains('light');
  const gridColor  = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
  const textColor  = isDark ? '#4d7a56' : '#9ba3bf';
  const tooltipBg  = isDark ? '#16161c' : '#fff';
  const tooltipBdr = isDark ? '#222230' : '#e2e5ef';

  aiHubChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['شنبه','یک‌شنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه'],
      datasets: [
        {
          label: 'p50',
          data: [7.2, 8.1, 6.9, 7.4, 8.4, 7.8, 7.2],
          borderColor: '#0BBF53', backgroundColor: 'rgba(11,191,83,0.06)',
          borderWidth: 2, tension: 0.4, fill: true,
          pointBackgroundColor: '#0BBF53', pointBorderColor: isDark ? '#111116' : '#fff',
          pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
        },
        {
          label: 'p95',
          data: [12.8, 14.2, 11.9, 13.1, 15.2, 12.4, 12.8],
          borderColor: '#f5923a', backgroundColor: 'rgba(245,146,58,0.04)',
          borderWidth: 2, tension: 0.4, fill: false,
          pointBackgroundColor: '#f5923a', pointBorderColor: isDark ? '#111116' : '#fff',
          pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
        },
        {
          label: 'p99',
          data: [18.3, 21.4, 17.2, 19.8, 23.1, 18.9, 18.3],
          borderColor: '#f05c5c', backgroundColor: 'rgba(240,92,92,0.04)',
          borderWidth: 2, tension: 0.4, fill: false,
          pointBackgroundColor: '#f05c5c', pointBorderColor: isDark ? '#111116' : '#fff',
          pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: tooltipBg, borderColor: tooltipBdr, borderWidth: 1,
          titleColor: isDark ? '#e8eaf2' : '#1a1c2a',
          bodyColor: isDark ? '#7c839e' : '#555c7a',
          titleFont: { family: 'IRANSansXFaNum', size: 12, weight: '700' },
          bodyFont:  { family: 'IRANSansXFaNum', size: 11 },
          padding: 12, rtl: true,
          callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + 's' }
        }
      },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'IRANSansXFaNum', size: 11 } }, border: { display: false } },
        y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'IRANSansXFaNum', size: 11 }, callback: v => v + 's' }, border: { display: false } }
      }
    }
  });
}
</script>
