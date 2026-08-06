<script>
// Lightweight Shamsi Calendar
const ShamsiCal = {
  // Convert Gregorian to Jalali
  toJalali(gy, gm, gd) {
    let g_d_no, j_d_no, j_np;
    const g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
    const j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    gy -= 1600; gm -= 1; gd -= 1;
    g_d_no = 365*gy + Math.floor((gy+3)/4) - Math.floor((gy+99)/100) + Math.floor((gy+399)/400);
    for(let i=0;i<gm;i++) g_d_no += g_days_in_month[i];
    if(gm>1 && ((gy%4==0&&gy%100!=0)||(gy%400==0))) g_d_no++;
    g_d_no += gd;
    j_d_no = g_d_no - 79;
    j_np = Math.floor(j_d_no/12053); j_d_no %= 12053;
    let jy = 979 + 33*j_np + 4*Math.floor(j_d_no/1461);
    j_d_no %= 1461;
    if(j_d_no >= 366){ jy += Math.floor((j_d_no-1)/365); j_d_no = (j_d_no-1)%365; }
    let ji;
    for(ji=0;ji<11&&j_d_no>=j_days_in_month[ji];ji++) j_d_no -= j_days_in_month[ji];
    return [jy, ji+1, j_d_no+1];
  },
  // Convert Jalali to Gregorian
  toGregorian(jy, jm, jd) {
    jy += 1595; jd--;
    let jdy = 365*jy + Math.floor(jy/33)*8 + Math.floor((jy%33+3)/4);
    for(let i=1;i<jm;i++) jdy += i<=6?31:30;
    jdy += jd;
    let gdy = jdy - 948;
    let gy = Math.floor(gdy/365.25);
    let gd = Math.floor(gdy - gy*365 - Math.floor(gy/4));
    const g_days=[31,28+((gy%4==0&&gy%100!=0)||(gy%400==0)?1:0),31,30,31,30,31,31,30,31,30,31];
    let gm=0;
    while(gm<12&&gd>g_days[gm]){gd-=g_days[gm];gm++;}
    return [gy+1, gm+1, gd+1];
  },
  daysInMonth(jy,jm){ return jm<=6?31:jm<=11?30:((jy%33)%4==1?30:29); },
  today(){ const n=new Date(); return ShamsiCal.toJalali(n.getFullYear(),n.getMonth()+1,n.getDate()); },
  toFA(n){ return String(n).replace(/\d/g,d=>'۰۱۲۳۴۵۶۷۸۹'[d]); },
  monthName(m){ return['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'][m-1]; }
};

function openShamsiPicker(inputEl) {
  document.querySelectorAll('.shamsi-picker').forEach(e=>e.remove());
  const [ty,tm,td] = ShamsiCal.today();
  let curVal = inputEl.value;
  let [cy,cm,cd] = curVal ? curVal.split('/').map(Number) : [ty,tm,td];
  if(!cy||!cm||!cd){cy=ty;cm=tm;cd=td;}

  const picker = document.createElement('div');
  picker.className = 'shamsi-picker';
  picker.style.cssText = `
    position:fixed;z-index:9999;background:var(--s2);border:1px solid var(--b1);
    border-radius:12px;padding:12px;width:280px;box-shadow:0 8px 32px rgba(0,0,0,0.5);
    font-family:'IRANSansXFaNum',sans-serif;direction:rtl;
  `;

  function render(y,m) {
    const days = ShamsiCal.daysInMonth(y,m);
    const firstDay = new Date(...ShamsiCal.toGregorian(y,m,1).map((v,i)=>i===1?v-1:v));
    const dayOfWeek = (firstDay.getDay()+1)%7;
    const weekDays = ['ش','ی','د','س','چ','پ','ج'];
    let html = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <button onclick="this.closest('.shamsi-picker')._prev()"
          style="background:var(--b1);border:none;color:var(--text);border-radius:6px;
          width:28px;height:28px;cursor:pointer;font-size:14px">‹</button>
        <div style="display:flex;gap:4px;align-items:center">
          <select onchange="this.closest('.shamsi-picker')._monthChange(this.value)" style="background:var(--b1);border:none;color:var(--text);border-radius:6px;padding:2px 6px;font-family:'IRANSansXFaNum',sans-serif;font-size:12px;cursor:pointer">
            ${['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'].map((name,i)=>`<option value="${i+1}" ${m===i+1?'selected':''}>${name}</option>`).join('')}
          </select>
          <select onchange="this.closest('.shamsi-picker')._yearChange(this.value)" style="background:var(--b1);border:none;color:var(--text);border-radius:6px;padding:2px 6px;font-family:'IRANSansXFaNum',sans-serif;font-size:12px;cursor:pointer">
            ${Array.from({length:11},(_,i)=>1400+i).map(yr=>`<option value="${yr}" ${y===yr?'selected':''}>${ShamsiCal.toFA(yr)}</option>`).join('')}
          </select>
        </div>
        <button onclick="this.closest('.shamsi-picker')._next()"
          style="background:var(--b1);border:none;color:var(--text);border-radius:6px;
          width:28px;height:28px;cursor:pointer;font-size:14px">›</button>
      </div>
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:6px">
        ${weekDays.map(d=>`<div style="text-align:center;font-size:11px;
        color:var(--text3);padding:4px 0">${d}</div>`).join('')}
      </div>
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
        ${Array(dayOfWeek).fill('<div></div>').join('')}
        ${Array.from({length:days},(_,i)=>{
          const day = i+1;
          const isToday = y===ty&&m===tm&&day===td;
          const isSel = y===cy&&m===cm&&day===cd;
          return `<div onclick="this.closest('.shamsi-picker')._pick(${y},${m},${day})"
            style="text-align:center;padding:5px 2px;border-radius:50%;cursor:pointer;
            font-size:12px;
            ${isSel?'background:var(--accent);color:#fff;font-weight:700;':''}
            ${isToday&&!isSel?'border:1.5px solid var(--green);color:var(--green);':''}
            ${!isSel&&!isToday?'color:var(--text2);':''}
            " onmouseover="if(!this.style.background.includes('accent'))
            this.style.background='var(--b1)'"
            onmouseout="if(!this.style.background.includes('accent'))
            this.style.background=''">
            ${ShamsiCal.toFA(day)}
          </div>`;
        }).join('')}
      </div>
      <div style="margin-top:8px;border-top:1px solid var(--b1);padding-top:8px;
        display:flex;justify-content:space-between">
        <button onclick="this.closest('.shamsi-picker')._pick(...ShamsiCal.today())"
          style="background:var(--b1);border:none;color:var(--text2);border-radius:6px;
          padding:4px 10px;font-size:11px;cursor:pointer;font-family:'IRANSansXFaNum',sans-serif">
          امروز
        </button>
        <button onclick="this.closest('.shamsi-picker').remove()"
          style="background:transparent;border:none;color:var(--text3);
          font-size:11px;cursor:pointer;font-family:'IRANSansXFaNum',sans-serif">
          بستن
        </button>
      </div>
    `;
    picker.innerHTML = html;
  }

  picker._pick = function(y,m,d) {
    const val = `${y}/${String(m).padStart(2,'0')}/${String(d).padStart(2,'0')}`;
    inputEl.value = val;
    inputEl.dispatchEvent(new Event('change'));
    picker.remove();
  };
  picker._prev = function(){
    if(cm===1){cy--;cm=12;}else cm--;
    render(cy,cm);
  };
  picker._next = function(){
    if(cm===12){cy++;cm=1;}else cm++;
    render(cy,cm);
  };
  picker._monthChange = function(v){ cm=parseInt(v); render(cy,cm); };
  picker._yearChange = function(v){ cy=parseInt(v); render(cy,cm); };

  render(cy,cm);
  document.body.appendChild(picker);

  // Position picker near input
  const rect = inputEl.getBoundingClientRect();
  const top = rect.bottom + window.scrollY + 4;
  const left = Math.max(4, rect.left + window.scrollX - 280 + rect.width);
  picker.style.top = top + 'px';
  picker.style.left = left + 'px';

  // Close on outside click
  setTimeout(()=>{
    document.addEventListener('click', function handler(e){
      if(!picker.contains(e.target) && e.target !== inputEl){
        picker.remove();
        document.removeEventListener('click', handler);
      }
    });
  }, 100);
}
</script>
