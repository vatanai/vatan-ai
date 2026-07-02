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
    font-family:'Vazirmatn',sans-serif;direction:rtl;
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
          <select onchange="this.closest('.shamsi-picker')._monthChange(this.value)" style="background:var(--b1);border:none;color:var(--text);border-radius:6px;padding:2px 6px;font-family:'Vazirmatn',sans-serif;font-size:12px;cursor:pointer">
            ${['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'].map((name,i)=>`<option value="${i+1}" ${m===i+1?'selected':''}>${name}</option>`).join('')}
          </select>
          <select onchange="this.closest('.shamsi-picker')._yearChange(this.value)" style="background:var(--b1);border:none;color:var(--text);border-radius:6px;padding:2px 6px;font-family:'Vazirmatn',sans-serif;font-size:12px;cursor:pointer">
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
          padding:4px 10px;font-size:11px;cursor:pointer;font-family:'Vazirmatn',sans-serif">
          امروز
        </button>
        <button onclick="this.closest('.shamsi-picker').remove()"
          style="background:transparent;border:none;color:var(--text3);
          font-size:11px;cursor:pointer;font-family:'Vazirmatn',sans-serif">
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

// ==================== CRM STATE ====================
let crmState = {
  projects: [], tasks: [], personnel: [], currentTab: 'dashboard',
  selectedProjectId: null, selectedPersonnelId: null, modalMicrotasks: [], activityLog: [], attendance: []
};

function shamsiToDate(shamsiStr) {
  if (!shamsiStr) return null;
  const parts = shamsiStr.split('/').map(Number);
  if (parts.length !== 3 || parts.some(isNaN)) return null;
  try {
    return new persianDate(parts).toCalendar('gregorian').toDate();
  } catch (e) {
    return null;
  }
}

function formatShamsiDate(dateStr) {
  if (!dateStr) return '—';
  return dateStr.replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
}

function shamsiToTimestamp(shamsiStr) {
  if (!shamsiStr) return null;
  const [jy, jm, jd] = shamsiStr.split('/').map(Number);
  if (!jy || !jm || !jd) return null;
  const [gy, gm, gd] = ShamsiCal.toGregorian(jy, jm, jd);
  return new Date(gy, gm - 1, gd).getTime();
}

function getRelativeLabel(shamsiStr) {
  const ts = shamsiToTimestamp(shamsiStr);
  if (!ts) return '—';
  const today = new Date(); today.setHours(0,0,0,0);
  const diff = Math.round((ts - today.getTime()) / 86400000);
  if (diff < 0) return `<span style="color:var(--red)">${ShamsiCal.toFA(Math.abs(diff))} روز پیش</span>`;
  if (diff === 0) return `<span style="color:var(--orange)">امروز</span>`;
  if (diff === 1) return `<span style="color:var(--orange)">فردا</span>`;
  return `<span>${ShamsiCal.toFA(diff)} روز دیگر</span>`;
}

const crmPermissionsMap = {
  'مدیر کل':['مدیریت کامل سیستم','مدیریت پرسنل','مدیریت پروژه','مشاهده گزارش‌ها'],
  'مدیر پیگیری':['مدیریت پروژه','مدیریت تسک','مشاهده گزارش‌ها'],
  'برنامه‌نویس':['مدیریت تسک','مشاهده پروژه‌های خود'],
  'گرافیست':['مدیریت تسک','مشاهده پروژه‌های خود'],
  'تولیدکننده محتوا':['مدیریت تسک','مشاهده پروژه‌های خود'],
  'سایر':['مشاهده تسک‌های خود']
};

const projectStatusMap = {
  planning:   { label: 'برنامه‌ریزی', color: 'orange', icon: '🟡' },
  waiting:    { label: 'در انتظار',   color: 'gray',   icon: '⚪' },
  inprogress: { label: 'در حال انجام', color: 'blue',   icon: '🔵' },
  done:       { label: 'تکمیل شده',   color: 'green',  icon: '🟢' },
  stopped:    { label: 'متوقف شده',   color: 'red',    icon: '🔴' }
};

function crmLoad() {
  const s = localStorage.getItem('crmApp');
  if (s) { try { const d=JSON.parse(s); crmState.projects=d.projects||[]; crmState.tasks=d.tasks||[]; crmState.personnel=d.personnel||[]; crmState.activityLog=d.activityLog||[]; crmState.attendance=d.attendance||[]; } catch(e){} }
  if (!crmState.projects.length) crmSeedData();
}

function crmSave() {
  localStorage.setItem('crmApp', JSON.stringify({projects:crmState.projects,tasks:crmState.tasks,personnel:crmState.personnel,activityLog:crmState.activityLog,attendance:crmState.attendance}));
}

function crmUid() { return Math.random().toString(36).slice(2,10); }

function logActivity(type, action, options={}) {
  crmState.activityLog.push({
    id: crmUid(),
    type,
    userId: options.userId||null,
    userName: options.userName||'',
    action,
    projectId: options.projectId||null,
    taskId: options.taskId||null,
    timestamp: Date.now()
  });
  crmSave();
}

function crmShowToast(type, message, duration=3000) {
  const icons={success:'✓',warning:'⚠',error:'✕'};
  const colors={success:'var(--green)',warning:'var(--orange)',error:'var(--red)'};
  const container=document.getElementById('crm-toast-container');
  if(!container) return;
  const color=colors[type]||colors.success;
  const el=document.createElement('div');
  el.className='crm-toast bg-s2 border rounded-[10px] min-w-[240px] text-[13px] text-watan-text';
  el.style.borderColor=color;
  el.innerHTML=`<span class="crm-toast-icon" style="color:${color}">${icons[type]||icons.success}</span><span>${message}</span>`;
  container.appendChild(el);
  setTimeout(()=>{
    el.classList.add('crm-toast-out');
    setTimeout(()=>el.remove(),250);
  },duration);
}

function crmConfirmToast(message, onConfirm) {
  const container=document.getElementById('crm-toast-container');
  if(!container) return;
  const el=document.createElement('div');
  el.className='crm-toast bg-s2 border rounded-[10px] min-w-[240px] text-[13px] text-watan-text';
  el.style.borderColor='var(--red)';
  el.style.flexDirection='column';
  el.style.alignItems='stretch';
  el.innerHTML=`<div style="display:flex;align-items:center;gap:8px"><span class="crm-toast-icon" style="color:var(--red)">⚠</span><span>${message}</span></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
      <button class="crm-btn" style="font-size:11px;padding:4px 10px" data-act="cancel">انصراف</button>
      <button class="crm-btn crm-btn-danger" style="font-size:11px;padding:4px 10px" data-act="confirm">تأیید</button>
    </div>`;
  container.appendChild(el);
  el.querySelector('[data-act="confirm"]').onclick=()=>{el.remove();onConfirm();};
  el.querySelector('[data-act="cancel"]').onclick=()=>{el.remove();};
}

function crmSeedData() {
  const p1=crmUid(), p2=crmUid();
  const per1=crmUid(), per2=crmUid(), per3=crmUid();
  crmState.projects=[
    {id:p1,name:'اپلیکیشن موبایل',emoji:'📱',status:'inprogress',desc:'ساخت اپ iOS و اندروید',deadline:'2025-09-01',createdAt:Date.now(),managerId:per1,memberIds:[per1,per2],teamIds:[per1,per2],startDate:'2025-05-01',endDate:'2025-09-01'},
    {id:p2,name:'وب‌سایت شخصی',emoji:'🌐',status:'planning',desc:'پورتفولیو آنلاین',deadline:'2025-07-15',createdAt:Date.now()-86400000,managerId:per1,memberIds:[per3],teamIds:[per3],startDate:'2025-06-01',endDate:'2025-07-15'}
  ];
  const t1=crmUid(),t2=crmUid(),t3=crmUid(),t4=crmUid();
  crmState.tasks=[
    {id:t1,projectId:p1,title:'طراحی رابط کاربری',desc:'ساخت وایرفریم‌ها',priority:'high',status:'inprogress',deadline:'2025-06-30',createdAt:Date.now(),done:false,assigneeId:per2,startDate:'2025-05-05',microtasks:[{id:crmUid(),text:'صفحه لاگین',done:true},{id:crmUid(),text:'صفحه اصلی',done:true},{id:crmUid(),text:'پروفایل',done:false}]},
    {id:t2,projectId:p1,title:'راه‌اندازی API',desc:'اتصال به بکند',priority:'urgent',status:'todo',deadline:'2025-07-10',createdAt:Date.now()-3600000,done:false,assigneeId:per1,startDate:'2025-06-10',microtasks:[{id:crmUid(),text:'احراز هویت',done:false},{id:crmUid(),text:'مدل داده',done:false}]},
    {id:t3,projectId:p2,title:'نوشتن محتوا',desc:'بیوگرافی و پروژه‌ها',priority:'medium',status:'todo',deadline:'2025-07-05',createdAt:Date.now()-7200000,done:false,assigneeId:per3,startDate:'2025-06-12',microtasks:[{id:crmUid(),text:'بیوگرافی',done:true},{id:crmUid(),text:'لیست پروژه‌ها',done:false}]},
    {id:t4,projectId:p2,title:'تنظیم دامنه',desc:'',priority:'low',status:'done',deadline:'',createdAt:Date.now()-86400000,done:true,assigneeId:per3,startDate:'2025-06-01',microtasks:[]}
  ];
  crmState.personnel=[
    {id:per1,name:'علی محمدی',mobile:'09121234567',role:'مدیر کل',projectIds:[p1,p2],createdAt:Date.now()-172800000,email:'ali.mohammadi@example.com',joinDate:'2024-01-15'},
    {id:per2,name:'سارا احمدی',mobile:'09351234567',role:'برنامه‌نویس',projectIds:[p1],createdAt:Date.now()-129600000,email:'sara.ahmadi@example.com',joinDate:'2024-03-10'},
    {id:per3,name:'رضا کریمی',mobile:'09181234567',role:'گرافیست',projectIds:[p2],createdAt:Date.now()-86400000,email:'reza.karimi@example.com',joinDate:'2024-05-20'}
  ];
  crmState.activityLog=[
    {id:crmUid(),type:'project',userId:per1,userName:'علی محمدی',action:'ایجاد پروژه',projectId:p1,taskId:null,timestamp:Date.now()-172800000},
    {id:crmUid(),type:'project',userId:per1,userName:'علی محمدی',action:'ایجاد پروژه',projectId:p2,taskId:null,timestamp:Date.now()-129600000},
    {id:crmUid(),type:'task',userId:per2,userName:'سارا احمدی',action:'ایجاد تسک',projectId:p1,taskId:t1,timestamp:Date.now()-100000000},
    {id:crmUid(),type:'task',userId:per3,userName:'رضا کریمی',action:'تکمیل تسک',projectId:p2,taskId:t4,timestamp:Date.now()-86400000},
    {id:crmUid(),type:'task',userId:per1,userName:'علی محمدی',action:'ایجاد تسک',projectId:p1,taskId:t2,timestamp:Date.now()-3600000}
  ];
  crmState.attendance=[];
  crmSave();
}

function crmTaskProgress(task) {
  if (!task.microtasks||!task.microtasks.length) return task.done?100:0;
  return Math.round((task.microtasks.filter(m=>m.done).length/task.microtasks.length)*100);
}

function crmProjectProgress(pid) {
  const tasks=crmState.tasks.filter(t=>t.projectId===pid);
  if (!tasks.length) return 0;
  return Math.round(tasks.reduce((s,t)=>s+crmTaskProgress(t),0)/tasks.length);
}

function crmNavigate(tab) {
  crmState.currentTab=tab;
  ['dashboard','projects','kanban','report','personnel'].forEach(t=>{
    document.getElementById('crm-page-'+t).style.display=t===tab?'':'none';
    const btn=document.getElementById('crm-tab-'+t);
    if(t===tab){btn.classList.add('bg-s1','border','border-b1','text-watan-text');btn.classList.remove('text-watan-text2');}
    else{btn.classList.remove('bg-s1','border','border-b1','text-watan-text');btn.classList.add('text-watan-text2');}
  });
  const addBtn=document.getElementById('crm-topbar-actions');
  if(tab==='dashboard'||tab==='projects') addBtn.style.display='flex'; else addBtn.style.display='none';
  if(tab==='dashboard') crmRenderDashboard();
  else if(tab==='projects'){crmState.selectedProjectId=null;crmRenderProjects();}
  else if(tab==='kanban'){crmPopulateKanbanFilter();crmRenderKanban();}
  else if(tab==='report') crmRenderReport();
  else if(tab==='personnel'){crmState.selectedPersonnelId=null;crmRenderPersonnel();}
}

function crmRenderDashboard() {
  const allTasks=crmState.tasks;
  const todayDate=new Date(); todayDate.setHours(0,0,0,0);
  const todayStr=todayDate.toISOString().slice(0,10);
  const fiveDaysAgo=Date.now()-5*86400000;

  const activeProjects=crmState.projects.filter(p=>p.status==='inprogress'||p.status==='planning');
  const openTasks=allTasks.filter(t=>t.status!=='done');
  const overdueTasks=allTasks.filter(t=>t.deadline&&t.deadline<todayStr&&t.status!=='done');
  const avgProgress=crmState.projects.length?Math.round(crmState.projects.reduce((s,p)=>s+crmProjectProgress(p.id),0)/crmState.projects.length):0;

  document.getElementById('crm-dashboard-stats').innerHTML=`
    <div class="crm-stat-card"><div class="crm-stat-label">پروژه‌های فعال</div><div class="crm-stat-val" style="color:var(--accent)">${activeProjects.length}</div><div class="crm-stat-sub">از ${crmState.projects.length} پروژه</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">تسک‌های باز</div><div class="crm-stat-val" style="color:#3b82f6">${openTasks.length}</div><div class="crm-stat-sub">از ${allTasks.length} تسک</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">تسک‌های معوق</div><div class="crm-stat-val" style="color:var(--red)">${overdueTasks.length}</div><div class="crm-stat-sub">نیاز به پیگیری</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">میانگین پیشرفت</div><div class="crm-stat-val" style="color:var(--orange)">${avgProgress}%</div><div class="crm-stat-sub">در همه پروژه‌ها</div></div>`;

  // Alerts
  const nearDeadlineProjects=crmState.projects.filter(p=>{
    if(!p.endDate) return false;
    const diff=Math.round((new Date(p.endDate+'T00:00:00')-todayDate)/86400000);
    return diff>=0&&diff<=3;
  });
  const noActivityProjects=crmState.projects.filter(p=>!crmState.activityLog.some(a=>a.projectId===p.id&&a.timestamp>=fiveDaysAgo));
  const alertsEl=document.getElementById('crm-dashboard-alerts');
  if(overdueTasks.length||nearDeadlineProjects.length||noActivityProjects.length){
    alertsEl.innerHTML=`
      <div class="crm-alert-card">
        <div class="crm-alert-title">⚠️ هشدارها</div>
        ${overdueTasks.length?`<div class="crm-alert-item">🔴 ${overdueTasks.length} تسک معوق</div>`:''}
        ${nearDeadlineProjects.length?`<div class="crm-alert-item">🟠 ${nearDeadlineProjects.length} پروژه نزدیک موعد تحویل</div>`:''}
        ${noActivityProjects.length?`<div class="crm-alert-item">⚪ ${noActivityProjects.length} پروژه بدون فعالیت</div>`:''}
      </div>`;
  } else { alertsEl.innerHTML=''; }

  // Chart: وضعیت اهداف
  const statusGroups={backlog:'در انتظار',todo:'برنامه‌ریزی',inprogress:'در حال انجام',done:'تکمیل شده'};
  const statusColors={backlog:'var(--text3)',todo:'var(--orange)',inprogress:'#3b82f6',done:'var(--green)'};
  const statusKeys=Object.keys(statusGroups);
  const counts=statusKeys.map(k=>allTasks.filter(t=>t.status===k).length);
  const maxCount=Math.max(...counts,1);
  document.getElementById('crm-dashboard-chart').innerHTML=`
    <div class="crm-chart-card">
      <div class="crm-section-title">نمودار وضعیت اهداف</div>
      <div class="crm-chart-bars">
        ${statusKeys.map((key,i)=>`
          <div class="crm-chart-bar-col">
            <div class="crm-chart-bar-count">${counts[i]}</div>
            <div class="crm-chart-bar-track"><div class="crm-chart-bar-fill" data-h="${Math.round(counts[i]/maxCount*80)}" style="height:0px;background:${statusColors[key]}"></div></div>
            <div class="crm-chart-bar-label">${statusGroups[key]}</div>
          </div>`).join('')}
      </div>
    </div>`;
  setTimeout(()=>{
    document.querySelectorAll('#crm-dashboard-chart .crm-chart-bar-fill').forEach(el=>{
      el.style.height=el.getAttribute('data-h')+'px';
    });
  },50);

  // Main grid: projects (left) + موعدهای نزدیک (right)
  document.getElementById('crm-dashboard-body').innerHTML=`
    <div>
      <div class="crm-section-title">📁 پروژه‌ها</div>
      ${crmState.projects.map(p=>{
        const pct=crmProjectProgress(p.id);
        const sm=projectStatusMap[p.status]||projectStatusMap.planning;
        const badgeClass=({orange:'amber',blue:'blue',green:'green',red:'red'})[sm.color]||'gray';
        return`<div class="crm-project-item" onclick="crmOpenProject('${p.id}')"><div class="crm-project-header"><div class="crm-project-name"><span class="crm-project-emoji">${p.emoji||'📁'}</span>${p.name}</div><span class="crm-badge crm-badge-${badgeClass}">${sm.icon} ${sm.label}</span></div><div class="crm-project-prog"><div class="crm-prog-wrap"><div class="crm-prog-fill ${pct===100?'green':pct>50?'':'amber'}" style="width:${pct}%"></div></div><span>${pct}%</span></div></div>`;
      }).join('')||'<div class="crm-empty">هنوز پروژه‌ای ندارید</div>'}
    </div>
    <div>
      <div class="crm-section-title">📅 موعدهای نزدیک</div>
      ${(()=>{
        const upcoming=allTasks.filter(t=>t.deadline&&t.status!=='done').sort((a,b)=>(shamsiToTimestamp(a.deadline)||0)-(shamsiToTimestamp(b.deadline)||0)).slice(0,5);
        if(!upcoming.length) return '<div class="crm-empty">موعدی ثبت نشده</div>';
        return upcoming.map(t=>{
          const proj=crmState.projects.find(p=>p.id===t.projectId);
          return `<div class="crm-deadline-item" onclick="crmOpenProject('${t.projectId}')">
            <div style="min-width:0;flex:1">
              <div style="font-size:13px;color:var(--text)">${t.title}</div>
              ${proj?`<div style="font-size:11px;color:var(--text2);margin-top:2px">${proj.emoji||''} ${proj.name}</div>`:''}
            </div>
            <span style="font-size:11px;font-weight:600;white-space:nowrap;flex-shrink:0">${getRelativeLabel(t.deadline)}</span>
          </div>`;
        }).join('');
      })()}
    </div>`;

  // وضعیت تیم + پروژه‌های در خطر
  const activePersonnel=crmState.personnel.filter(p=>p.projectIds&&p.projectIds.length>0);
  const personnelWithoutOpenTasks=crmState.personnel.filter(p=>!allTasks.some(t=>t.assigneeId===p.id&&t.status!=='done'));
  const inprogCount=allTasks.filter(t=>t.status==='inprogress').length;

  const riskProjects=crmState.projects.filter(p=>{
    const hasOverdue=allTasks.some(t=>t.projectId===p.id&&t.deadline&&t.deadline<todayStr&&t.status!=='done');
    const noActivity=!crmState.activityLog.some(a=>a.projectId===p.id&&a.timestamp>=fiveDaysAgo);
    return hasOverdue||noActivity;
  }).slice(0,3);

  document.getElementById('crm-dashboard-team-row').innerHTML=`
    <div class="crm-team-card">
      <div class="crm-section-title">👥 وضعیت تیم</div>
      <div class="crm-team-stats">
        <div class="crm-team-stat"><span class="crm-team-stat-val" style="color:var(--accent)">${activePersonnel.length}</span><span class="crm-team-stat-label">پرسنل فعال</span></div>
        <div class="crm-team-stat"><span class="crm-team-stat-val" style="color:var(--orange)">${personnelWithoutOpenTasks.length}</span><span class="crm-team-stat-label">نفر بدون تسک</span></div>
        <div class="crm-team-stat"><span class="crm-team-stat-val" style="color:#3b82f6">${inprogCount}</span><span class="crm-team-stat-label">تسک در حال انجام</span></div>
      </div>
    </div>
    <div class="crm-team-card">
      <div class="crm-section-title">🚨 پروژه‌های در خطر</div>
      ${riskProjects.length?riskProjects.map(p=>{
        const hasOverdue=allTasks.some(t=>t.projectId===p.id&&t.deadline&&t.deadline<todayStr&&t.status!=='done');
        return `<div class="crm-risk-row"><span style="font-size:12px;color:var(--text)">${p.emoji||'📁'} ${p.name}</span><span class="crm-badge crm-badge-${hasOverdue?'red':'amber'}">${hasOverdue?'تسک معوق':'بدون فعالیت'}</span></div>`;
      }).join(''):'<div class="crm-empty" style="padding:20px">پروژه‌ای در خطر نیست</div>'}
    </div>`;

  crmRenderPersonnelPerf();
}

function crmRenderPersonnelPerf() {
  const period=crmState.perfPeriod||'weekly';
  const now=Date.now();
  const cutoff=period==='daily'?now-86400000:period==='monthly'?now-30*86400000:now-7*86400000;
  const rows=crmState.personnel.map(p=>{
    const assigned=crmState.tasks.filter(t=>t.assigneeId===p.id);
    const doneAll=assigned.filter(t=>t.status==='done');
    const donePeriod=doneAll.filter(t=>t.completedAt&&t.completedAt>=cutoff);
    const pct=assigned.length?Math.round(doneAll.length/assigned.length*100):0;
    return {p,doneCount:donePeriod.length,pct};
  }).sort((a,b)=>b.doneCount-a.doneCount).slice(0,5);

  document.getElementById('crm-dashboard-personnel-perf').innerHTML=`
    <div class="crm-section-title" style="margin-top:16px;justify-content:space-between">
      <span>عملکرد پرسنل</span>
      <div class="crm-toggle-group">
        <button class="crm-toggle-btn ${period==='daily'?'active':''}" onclick="crmSetPerfPeriod('daily')">روزانه</button>
        <button class="crm-toggle-btn ${period==='weekly'?'active':''}" onclick="crmSetPerfPeriod('weekly')">هفتگی</button>
        <button class="crm-toggle-btn ${period==='monthly'?'active':''}" onclick="crmSetPerfPeriod('monthly')">ماهانه</button>
      </div>
    </div>
    <div>
      ${rows.length?rows.map(r=>{
        const meta=crmRoleMeta(r.p.role);
        return `<div class="crm-personnel-perf-row">
          <div class="crm-avatar" style="background:${meta.color};width:32px;height:32px;font-size:12px">${crmInitials(r.p.name)}</div>
          <span class="crm-personnel-perf-name">${r.p.name}</span>
          <span class="crm-personnel-perf-count">${r.doneCount} تسک</span>
          <div class="crm-prog-wrap" style="flex:1;max-width:140px"><div class="crm-prog-fill ${r.pct===100?'green':''}" style="width:${r.pct}%"></div></div>
          <span style="font-size:11px;color:var(--text3);min-width:32px;text-align:left">${r.pct}%</span>
        </div>`;
      }).join(''):'<div class="crm-empty">پرسنلی موجود نیست</div>'}
    </div>`;
}

function crmSetPerfPeriod(period) {
  crmState.perfPeriod=period;
  crmRenderPersonnelPerf();
}

function crmRenderProjects() {
  if(crmState.selectedProjectId) {crmRenderProjectDetail(crmState.selectedProjectId);return;}
  document.getElementById('crm-btn-back').style.display='none';
  document.getElementById('crm-btn-add-task').style.display='none';
  const el=document.getElementById('crm-projects-content');
  const sorted=[...crmState.projects].sort((a,b)=>(a.archived?1:0)-(b.archived?1:0));
  el.innerHTML=sorted.length?sorted.map(p=>{
    const pct=crmProjectProgress(p.id);
    const tasks=crmState.tasks.filter(t=>t.projectId===p.id);
    const doneCount=tasks.filter(t=>t.status==='done').length;
    const manager=p.managerId?crmState.personnel.find(x=>x.id===p.managerId):null;
    const members=(p.memberIds||[]).map(mid=>crmState.personnel.find(x=>x.id===mid)).filter(Boolean);
    const visibleMembers=members.slice(0,4);
    const extraCount=members.length-visibleMembers.length;
    const sm=projectStatusMap[p.status]||projectStatusMap.planning;
    const badgeClass=crmStatusBadgeClass(sm.color);
    return`<div class="crm-project-item ${p.archived?'archived':''}" onclick="crmOpenProject('${p.id}')">
      <div class="crm-project-header">
        <div style="display:flex;align-items:center;gap:8px">
          <div class="crm-project-archive-check ${p.archived?'checked':''}" onclick="event.stopPropagation();crmToggleArchiveProject('${p.id}')">${p.archived?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>':''}</div>
          <div class="crm-project-name" style="font-size:15px"><span class="crm-project-emoji">${p.emoji||'📁'}</span>${p.name}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          ${p.archived?'<span class="crm-badge crm-badge-gray">بایگانی</span>':`<span class="crm-badge crm-badge-${badgeClass}">${sm.icon} ${sm.label}</span>`}
          <button class="crm-icon-btn" onclick="event.stopPropagation();crmOpenEditProject('${p.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="crm-icon-btn crm-btn-danger" onclick="event.stopPropagation();crmDeleteProject('${p.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
        ${manager?`<span class="crm-avatar" style="width:18px;height:18px;font-size:8px;background:${crmRoleMeta(manager.role).color}">${crmInitials(manager.name)}</span><span style="font-size:11px;color:var(--text2)">${manager.name}</span>`:'<span style="font-size:11px;color:var(--text3)">— مدیر تعیین نشده</span>'}
      </div>
      ${p.desc?`<p style="font-size:12px;color:var(--text2);margin-bottom:10px">${p.desc}</p>`:''}
      <div class="crm-project-prog" style="margin-bottom:8px"><div class="crm-prog-wrap"><div class="crm-prog-fill ${pct===100?'green':''}" style="width:${pct}%"></div></div><span>${pct}%</span></div>
      <div class="crm-project-meta" style="justify-content:space-between">
        <div style="display:flex;gap:12px">
          <span>📋 ${tasks.length} تسک، ${doneCount} انجام شده</span>
          ${p.deadline?`<span>📅 ${formatShamsiDate(p.deadline)}</span>`:''}
        </div>
        <div style="display:flex;justify-content:flex-end">
          ${visibleMembers.map(m=>`<span class="crm-member-avatar-mini" title="${m.name}" style="background:${crmRoleMeta(m.role).color}">${crmInitials(m.name)}</span>`).join('')}
          ${extraCount>0?`<span class="crm-member-avatar-mini" style="background:var(--b2)">+${extraCount}</span>`:''}
        </div>
      </div>
    </div>`;
  }).join(''):'<div class="crm-empty">هنوز هیچ پروژه‌ای ندارید</div>';
}

function crmOpenProject(pid) {
  crmNavigate('projects');
  crmState.selectedProjectId=pid;
  crmRenderProjects();
}
console.log('%c✓ Project Detail Fix Applied', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Version Badge + Task Deadline Fix', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Date + Picker + Badge Fixes Applied', 'color:#0BBF53;font-weight:bold;font-size:14px');

function crmBackToProjects() {
  crmState.selectedProjectId=null;crmRenderProjects();
}

function drawDonut(containerId, data, centerText, centerLabel) {
  const el=document.getElementById(containerId);
  if(!el) return;
  const total=data.reduce((s,d)=>s+d.value,0);
  const r=35, cx=50, cy=50;
  const circumference=2*Math.PI*r;
  let acc=0;
  const segments=total?data.filter(d=>d.value>0).map(d=>{
    const frac=d.value/total;
    const dash=frac*circumference;
    const seg=`<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${d.color}" stroke-width="14" stroke-dasharray="${dash} ${circumference-dash}" stroke-dashoffset="${-acc}" transform="rotate(-90 ${cx} ${cy})" style="transition:stroke-dasharray .6s ease"/>`;
    acc+=dash;
    return seg;
  }).join(''):'';
  const mainText=centerText!==undefined?centerText:total;
  const subText=centerLabel!==undefined?centerLabel:'کل تسک‌ها';
  el.innerHTML=`<svg viewBox="0 0 100 100" width="150" height="150">
    <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--b1)" stroke-width="14"/>
    ${segments}
    <text x="50" y="47" text-anchor="middle" font-size="18" font-weight="700" fill="var(--text)">${mainText}</text>
    <text x="50" y="61" text-anchor="middle" font-size="7" fill="var(--text3)">${subText}</text>
  </svg>`;
}

function crmSetProjectTaskFilter(f) {
  crmState.projectTaskFilter=f;
  crmRenderProjectDetail(crmState.selectedProjectId);
}

function crmRenderProjectDetail(pid) {
  const proj=crmState.projects.find(p=>p.id===pid);
  if(!proj){crmState.selectedProjectId=null;crmRenderProjects();return;}
  document.getElementById('crm-btn-back').style.display='';
  document.getElementById('crm-btn-add-task').style.display='';
  if(!crmState.projectTaskFilter) crmState.projectTaskFilter='all';

  const tasks=crmState.tasks.filter(t=>t.projectId===pid);
  const pct=crmProjectProgress(pid);
  const todayStr=new Date().toISOString().slice(0,10);
  const todayDate0=new Date(); todayDate0.setHours(0,0,0,0);
  const sm=projectStatusMap[proj.status]||projectStatusMap.planning;
  const badgeClass=crmStatusBadgeClass(sm.color);
  const manager=proj.managerId?crmState.personnel.find(p=>p.id===proj.managerId):null;
  const members=(proj.memberIds||[]).map(mid=>crmState.personnel.find(x=>x.id===mid)).filter(Boolean);
  const endDateObj=proj.endDate?shamsiToDate(proj.endDate):null;
  const endPast=endDateObj?endDateObj<todayDate0:false;
  const recentLogs=crmState.activityLog.filter(a=>a.projectId===pid).sort((a,b)=>b.timestamp-a.timestamp).slice(0,10);

  const doneCount=tasks.filter(t=>t.status==='done').length;
  const inprogCount=tasks.filter(t=>t.status==='inprogress').length;
  const waitingCount=tasks.filter(t=>t.status==='backlog'||t.status==='todo').length;
  const notDoneCount=tasks.filter(t=>t.status!=='done').length;

  const taskFilters=[['all','همه'],['waiting','در انتظار'],['inprogress','در حال انجام'],['done','انجام شده']];
  const filteredTasks=tasks.filter(t=>{
    const f=crmState.projectTaskFilter;
    if(f==='all') return true;
    if(f==='waiting') return t.status==='backlog'||t.status==='todo';
    return t.status===f;
  });

  const taskStatusLabels={backlog:'انتظار',todo:'برنامه‌ریزی',inprogress:'در حال انجام',done:'انجام‌شده'};

  document.getElementById('crm-projects-content').innerHTML=`
    <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:24px;margin-bottom:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:10px">
          <button class="crm-btn" onclick="crmBackToProjects()">←</button>
          <h2 style="font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;margin:0"><span>${proj.emoji||'📁'}</span>${proj.name}</h2>
          <span class="crm-badge crm-badge-${badgeClass}">${sm.icon} ${sm.label}</span>
        </div>
        <button class="crm-icon-btn" onclick="crmOpenEditProject('${proj.id}')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
      </div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
        <div class="crm-prog-wrap" style="height:12px"><div class="crm-prog-fill ${pct===100?'green':''}" style="width:${pct}%"></div></div>
        <span style="font-size:13px;font-weight:600;color:${pct===100?'var(--green)':'var(--text2)'};flex-shrink:0">${pct}% پیشرفت کلی</span>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;font-size:12px;color:var(--text2)">
        <div>شروع: ${formatShamsiDate(proj.startDate)}</div>
        <div>موعد: <span style="color:${endPast?'var(--red)':'var(--text2)'}">${formatShamsiDate(proj.endDate)}</span></div>
        <div>مدیر: ${manager?manager.name:'—'}</div>
        <div>اعضا: ${members.length} نفر</div>
      </div>
    </div>

    <div class="crm-stat-grid">
      <div class="crm-stat-card"><div class="crm-stat-label">📋 کل تسک‌ها</div><div class="crm-stat-val" style="color:var(--accent)">${tasks.length}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">✅ انجام شده</div><div class="crm-stat-val" style="color:var(--green)">${doneCount}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">⏳ در حال انجام</div><div class="crm-stat-val" style="color:#3b82f6">${inprogCount}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">❌ انجام نشده</div><div class="crm-stat-val" style="color:var(--orange)">${notDoneCount}</div></div>
    </div>

    <div class="crm-section-title">اعضای تیم</div>
    <div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;margin-bottom:20px">
      ${members.length?members.map(m=>{
        const isManager=proj.managerId===m.id;
        const meta=crmRoleMeta(m.role);
        return`<div style="min-width:120px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center">
          <div style="position:relative">
            <div class="crm-avatar" style="width:48px;height:48px;font-size:16px;background:${meta.color}">${crmInitials(m.name)}</div>
            ${isManager?'<span style="position:absolute;top:-6px;left:-2px;font-size:14px;color:#FFD700">♛</span>':''}
          </div>
          <span style="font-size:12px;color:var(--text);font-weight:600">${m.name}</span>
          <span class="crm-badge ${meta.badge}" style="font-size:10px">${m.role}</span>
        </div>`;
      }).join(''):'<div class="crm-empty">عضوی تخصیص داده نشده</div>'}
    </div>

    <div class="crm-section-title">تایم‌لاین پروژه</div>
    <div style="margin-bottom:20px">${crmRenderProjectTimeline(proj,tasks,todayDate0)}</div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <div class="crm-section-title" style="margin-bottom:0">تسک‌ها</div>
      <button class="crm-btn crm-btn-primary" onclick="crmOpenModal('task')">+ افزودن تسک</button>
    </div>
    <div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap">
      ${taskFilters.map(([f,label])=>`<button class="crm-btn" style="${crmState.projectTaskFilter===f?'background:var(--accent);color:#fff;border-color:var(--accent)':''}" onclick="crmSetProjectTaskFilter('${f}')">${label}</button>`).join('')}
    </div>
    ${filteredTasks.length?filteredTasks.map(t=>{
      const assignee=t.assigneeId?crmState.personnel.find(p=>p.id===t.assigneeId):null;
      const overdue=t.deadline&&t.deadline<todayStr&&t.status!=='done';
      return`<div class="crm-task-item ${t.done?'done':''}">
        <div class="crm-task-check ${t.done?'checked':''}" onclick="crmToggleTask('${t.id}')">${t.done?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>':''}</div>
        <span class="crm-priority-dot crm-p-${t.priority}" style="margin-top:6px"></span>
        <div class="crm-task-body">
          <div class="crm-task-title">${t.title}</div>
          <div class="crm-task-meta">
            ${assignee?`<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--text2)"><span class="crm-avatar" style="width:16px;height:16px;font-size:7px;background:${crmRoleMeta(assignee.role).color}">${crmInitials(assignee.name)}</span>${assignee.name}</span>`:''}
            <span class="crm-badge crm-badge-gray">${taskStatusLabels[t.status]||t.status}</span>
            ${t.deadline?`<span style="font-size:11px;color:${overdue?'var(--red)':'var(--text3)'}">📅 ${formatShamsiDate(t.deadline)}</span>`:''}
          </div>
        </div>
        <div class="crm-task-actions">
          <button class="crm-icon-btn" onclick="crmOpenEditTask('${t.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="crm-icon-btn crm-btn-danger" onclick="crmDeleteTask('${t.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>
        </div>
      </div>`;
    }).join(''):'<div class="crm-empty">تسکی در این فیلتر یافت نشد</div>'}

    <div class="crm-section-title" style="margin-top:24px">نمودار وضعیت تسک‌ها</div>
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;margin-bottom:20px">
      <div id="crm-project-donut"></div>
      <div style="flex:1;min-width:160px;display:flex;flex-direction:column;gap:6px">
        ${[['انجام شده',doneCount,'var(--green)'],['در حال انجام',inprogCount,'#3b82f6'],['در انتظار',waitingCount,'var(--orange)'],['متوقف',0,'var(--red)']].map(([label,val,color])=>{
          const p2=tasks.length?Math.round(val/tasks.length*100):0;
          return`<div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text2);padding:3px 0">
            <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0"></span>${label}</span>
            <span style="color:var(--text3)">${p2}% (${val})</span>
          </div>`;
        }).join('')}
      </div>
    </div>

    <div class="crm-section-title">فعالیت‌های اخیر</div>
    <div style="margin-bottom:20px">
      ${recentLogs.length?(()=>{
        const groups={};
        recentLogs.forEach(a=>{const label=crmRelativeTime(a.timestamp); (groups[label]=groups[label]||[]).push(a);});
        return Object.entries(groups).map(([label,entries])=>`
          <div style="margin-bottom:10px">
            <div style="font-size:11px;font-weight:700;color:var(--text3);margin-bottom:4px">${label}</div>
            ${entries.map(a=>{
              const dotColor=a.type==='task'?'#3b82f6':'var(--accent)';
              const user=a.userId?crmState.personnel.find(p=>p.id===a.userId):null;
              return`<div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:12px;color:var(--text2)">
                <span style="width:7px;height:7px;border-radius:50%;background:${dotColor};flex-shrink:0"></span>
                ${user?`<span class="crm-avatar" style="width:18px;height:18px;font-size:8px;background:${crmRoleMeta(user.role).color};flex-shrink:0">${crmInitials(user.name)}</span>`:''}
                <span style="flex:1">${a.action}</span>
                <span style="color:var(--text3);font-size:11px">${crmRelativeTime(a.timestamp)}</span>
              </div>`;
            }).join('')}
          </div>`).join('');
      })():'<div class="crm-empty">هنوز فعالیتی ثبت نشده است</div>'}
    </div>`;

  drawDonut('crm-project-donut',[
    {label:'انجام شده',value:doneCount,color:'var(--green)'},
    {label:'در حال انجام',value:inprogCount,color:'#3b82f6'},
    {label:'در انتظار',value:waitingCount,color:'var(--orange)'},
    {label:'متوقف',value:0,color:'var(--red)'}
  ]);
}

function crmRenderProjectTimeline(proj, tasks, todayDate0) {
  if(!proj.startDate || !proj.endDate) return '<div class="crm-empty">تاریخ پروژه تعیین نشده</div>';
  const start=shamsiToDate(proj.startDate);
  const end=shamsiToDate(proj.endDate);
  if(!start||!end||end<=start) return '<div class="crm-empty">تاریخ پروژه تعیین نشده</div>';
  const totalSpan=end-start;
  let todayPct=((todayDate0-start)/totalSpan)*100;
  todayPct=Math.max(0,Math.min(100,todayPct));
  const isPastDeadline=todayDate0>end;

  const milestones=tasks.filter(t=>t.deadline).map(t=>{
    const d=shamsiToDate(t.deadline);
    if(!d) return null;
    let p=((d-start)/totalSpan)*100;
    p=Math.max(0,Math.min(100,p));
    const color=t.status==='done'?'var(--green)':(d<todayDate0?'var(--red)':'var(--orange)');
    return {pct:p,color,title:t.title};
  }).filter(Boolean);

  return `<div style="position:relative;height:64px;background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:0 14px;direction:ltr">
    <div style="position:absolute;top:30px;left:14px;right:14px;height:4px;background:var(--b1);border-radius:99px"></div>
    <div style="position:absolute;top:26px;width:11px;height:11px;border-radius:50%;background:${isPastDeadline?'var(--red)':'var(--accent)'};left:calc(${todayPct}% - 5.5px)" title="امروز"></div>
    ${milestones.map(m=>`<div style="position:absolute;top:27px;width:8px;height:8px;border-radius:50%;background:${m.color};left:calc(${m.pct}% - 4px);cursor:pointer" title="${m.title}"></div>`).join('')}
    <div style="position:absolute;top:42px;left:14px;font-size:10px;color:var(--text3)">${formatShamsiDate(proj.startDate)}</div>
    <div style="position:absolute;top:42px;right:14px;font-size:10px;color:var(--text3)">${formatShamsiDate(proj.endDate)}</div>
  </div>`;
}

function crmPopulateKanbanFilter() {
  const sel=document.getElementById('crm-kanban-manager-filter');
  const cur=sel.value;
  sel.innerHTML='<option value="">مدیر پروژه (همه)</option>'+crmState.personnel.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  sel.value=cur;
}

function crmResetKanbanFilter() {
  document.getElementById('crm-kanban-search').value='';
  document.getElementById('crm-kanban-manager-filter').value='';
  document.getElementById('crm-kanban-no-manager').checked=false;
  crmRenderKanban();
}

function crmKanbanAddProject(status) {
  crmOpenModal('project');
  document.getElementById('crm-proj-status').value=status;
}

function crmKanbanDragStart(ev, projectId) {
  ev.dataTransfer.setData('text/plain', projectId);
  ev.target.classList.add('crm-kanban-dragging');
}

function crmKanbanDragEnd(ev) {
  ev.target.classList.remove('crm-kanban-dragging');
}

function crmKanbanDragOver(ev) {
  ev.preventDefault();
  ev.currentTarget.classList.add('crm-kanban-col-over');
}

function crmKanbanDragLeave(ev) {
  ev.currentTarget.classList.remove('crm-kanban-col-over');
}

function crmKanbanDrop(ev, newStatus) {
  ev.preventDefault();
  ev.currentTarget.classList.remove('crm-kanban-col-over');
  const projectId=ev.dataTransfer.getData('text/plain');
  const p=crmState.projects.find(x=>x.id===projectId);
  if(!p||p.status===newStatus) return;
  p.status=newStatus;
  crmSave();
  const sm=projectStatusMap[newStatus]||projectStatusMap.planning;
  logActivity('project','وضعیت پروژه تغییر کرد به '+sm.label,{projectId});
  crmRenderKanban();
}

function crmRenderKanban() {
  const searchInput=document.getElementById('crm-kanban-search');
  const managerSel=document.getElementById('crm-kanban-manager-filter');
  const noManagerChk=document.getElementById('crm-kanban-no-manager');
  const q=(searchInput?searchInput.value:'').trim().toLowerCase();
  const managerId=managerSel?managerSel.value:'';
  const noManager=noManagerChk?noManagerChk.checked:false;

  const filteredProjects=crmState.projects.filter(p=>{
    if(q && !(p.name||'').toLowerCase().includes(q)) return false;
    if(noManager) return !p.managerId;
    if(managerId) return p.managerId===managerId;
    return true;
  });

  const todayStr=new Date().toISOString().slice(0,10);
  const cols=[
    {status:'planning',label:'برنامه‌ریزی',icon:'🟡',color:'var(--orange)'},
    {status:'waiting',label:'در انتظار',icon:'⚪',color:'var(--text3)'},
    {status:'inprogress',label:'در حال انجام',icon:'🔵',color:'#3b82f6'},
    {status:'done',label:'تکمیل شده',icon:'🟢',color:'var(--green)'},
    {status:'stopped',label:'متوقف شده',icon:'🔴',color:'var(--red)'}
  ];

  document.getElementById('crm-kanban-board').innerHTML=cols.map(col=>{
    const colProjects=filteredProjects.filter(p=>p.status===col.status);
    return`<div class="crm-kanban-col" style="border-top:3px solid ${col.color}" ondragover="crmKanbanDragOver(event)" ondragleave="crmKanbanDragLeave(event)" ondrop="crmKanbanDrop(event,'${col.status}')">
      <div class="crm-kanban-col-header">
        <div class="crm-kanban-col-title">${col.icon} ${col.label}</div>
        <span class="crm-kanban-col-count">${colProjects.length}</span>
      </div>
      <div class="crm-kanban-cards">
        ${colProjects.length?colProjects.map(p=>{
          const pct=crmProjectProgress(p.id);
          const tasks=crmState.tasks.filter(t=>t.projectId===p.id);
          const hasOverdue=tasks.some(t=>t.deadline&&t.deadline<todayStr&&t.status!=='done');
          const manager=p.managerId?crmState.personnel.find(x=>x.id===p.managerId):null;
          const members=(p.memberIds||[]).map(mid=>crmState.personnel.find(x=>x.id===mid)).filter(Boolean);
          const visibleMembers=members.slice(0,3);
          const extraCount=members.length-visibleMembers.length;
          const endOverdue=p.endDate&&p.endDate<todayStr;
          return`<div class="crm-kanban-card" draggable="true" ondragstart="crmKanbanDragStart(event,'${p.id}')" ondragend="crmKanbanDragEnd(event)" onclick="crmOpenProject('${p.id}')">
            <div class="crm-kanban-card-title">${p.emoji||'📁'} ${p.name}</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
              <div class="crm-prog-wrap" style="height:5px"><div class="crm-prog-fill ${pct===100?'green':''}" style="width:${pct}%"></div></div>
              <span style="font-size:11px;color:var(--text3);flex-shrink:0">${pct}%</span>
            </div>
            <div style="display:flex;align-items:center;margin-bottom:6px">
              ${visibleMembers.map(m=>`<span class="crm-member-avatar-mini" title="${m.name}" style="background:${crmRoleMeta(m.role).color};width:22px;height:22px;font-size:8px">${crmInitials(m.name)}</span>`).join('')}
              ${extraCount>0?`<span class="crm-member-avatar-mini" style="background:var(--b2);width:22px;height:22px;font-size:8px">+${extraCount}</span>`:''}
            </div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:8px">مدیر: ${manager?manager.name:'—'}</div>
            <div class="crm-kanban-card-meta">
              ${hasOverdue?'<span style="color:var(--red);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;border-radius:50%;background:var(--red)"></span>معوق</span>':'<span></span>'}
              <span>${tasks.length} تسک</span>
              ${p.endDate?`<span style="color:${endOverdue?'var(--red)':'var(--text3)'}">${formatShamsiDate(p.endDate)}</span>`:'<span></span>'}
            </div>
          </div>`;
        }).join(''):`<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:30px 12px;color:var(--text3);font-size:12px;text-align:center"><span style="font-size:22px;opacity:.5">📭</span>پروژه‌ای در این مرحله نیست</div>`}
      </div>
      <div style="padding:10px;border-top:1px solid var(--b1)">
        <button class="crm-btn" style="width:100%;justify-content:center;font-size:11px" onclick="crmKanbanAddProject('${col.status}')">+ افزودن پروژه</button>
      </div>
    </div>`;
  }).join('');
}

function crmDaysOverdue(deadlineStr) {
  const d=shamsiToDate(deadlineStr);
  if(!d) return 0;
  d.setHours(0,0,0,0);
  const today=new Date(); today.setHours(0,0,0,0);
  return Math.max(0,Math.round((today-d)/86400000));
}

function crmRenderReport() {
  const allTasks=crmState.tasks;
  const todayStr=new Date().toISOString().slice(0,10);
  const done=allTasks.filter(t=>t.status==='done').length;
  const total=allTasks.length;
  const doneRatio=total?done/total:0;

  const overdueTasks=allTasks.filter(t=>t.deadline&&t.deadline<todayStr&&t.status!=='done');
  const stoppedProjects=crmState.projects.filter(p=>p.status==='stopped');
  const fiveDaysAgo=Date.now()-5*86400000;
  const noActivityProjects=crmState.projects.filter(p=>!crmState.activityLog.some(a=>a.projectId===p.id&&a.timestamp>=fiveDaysAgo));

  let healthScore=100-(overdueTasks.length*5)-(stoppedProjects.length*10)-(noActivityProjects.length*8)+Math.min(doneRatio*20,20);
  healthScore=Math.max(0,Math.min(100,Math.round(healthScore)));
  let healthLabel,healthColor,healthIcon;
  if(healthScore>=80){healthLabel='عالی';healthColor='var(--green)';healthIcon='🟢';}
  else if(healthScore>=60){healthLabel='نیاز به توجه';healthColor='var(--orange)';healthIcon='🟡';}
  else{healthLabel='بحرانی';healthColor='var(--red)';healthIcon='🔴';}

  const activeProjects=crmState.projects.filter(p=>p.status==='inprogress'||p.status==='planning');
  const urgentTasks=allTasks.filter(t=>t.priority==='urgent'&&t.status!=='done');
  const avgProgress=crmState.projects.length?Math.round(crmState.projects.reduce((s,p)=>s+crmProjectProgress(p.id),0)/crmState.projects.length):0;

  let summaryMsg;
  if(healthScore>=80) summaryMsg=`امروز وضعیت عالی دارید 🚀 ${Math.round(doneRatio*100)}% تسک‌های برنامه‌ریزی شده انجام شده‌اند`;
  else if(healthScore>=60) summaryMsg='امروز وضعیت خوبی دارید 👌 چند تسک نیاز به توجه دارند';
  else summaryMsg=`وضعیت نیاز به رسیدگی دارد ⚠️ ${overdueTasks.length} تسک معوق وجود دارد`;

  const doneCount=done, inprogCount=allTasks.filter(t=>t.status==='inprogress').length, waitingCount=allTasks.filter(t=>t.status==='backlog'||t.status==='todo').length;
  const taskChartData=[
    {label:'انجام شده',value:doneCount,color:'var(--green)'},
    {label:'در حال انجام',value:inprogCount,color:'#3b82f6'},
    {label:'در انتظار',value:waitingCount,color:'var(--orange)'},
    {label:'متوقف',value:0,color:'var(--red)'}
  ];
  const chartTotal=taskChartData.reduce((s,d)=>s+d.value,0)||1;

  const overdueList=overdueTasks.map(t=>{
    const proj=crmState.projects.find(p=>p.id===t.projectId);
    const assignee=t.assigneeId?crmState.personnel.find(p=>p.id===t.assigneeId):null;
    return {...t,proj,assignee,daysOverdue:crmDaysOverdue(t.deadline)};
  }).sort((a,b)=>b.daysOverdue-a.daysOverdue);

  document.getElementById('crm-report-content').innerHTML=`
    <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
      <div>
        <div class="crm-section-title" style="margin-bottom:10px">سلامت پروژه‌ها</div>
        <div style="font-size:24px;font-weight:800;color:${healthColor}">${healthIcon} ${healthLabel}</div>
        <div style="font-size:12px;color:var(--text3);margin-top:6px">${overdueTasks.length} تسک معوق • ${stoppedProjects.length} پروژه متوقف • ${noActivityProjects.length} پروژه بدون فعالیت</div>
      </div>
      <div id="crm-health-ring"></div>
    </div>

    <div style="background:linear-gradient(135deg,rgba(160,122,245,0.1),rgba(11,191,83,0.05));border:1px solid var(--b1);border-radius:14px;padding:20px;margin-bottom:20px">
      <div class="crm-section-title">🚀 خلاصه امروز</div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:12px 0">
        <div class="crm-stat-card" style="padding:12px"><div class="crm-stat-label" style="font-size:11px">پروژه‌های فعال</div><div class="crm-stat-val" style="font-size:20px;color:var(--accent)">${activeProjects.length}</div></div>
        <div class="crm-stat-card" style="padding:12px"><div class="crm-stat-label" style="font-size:11px">تسک فوری</div><div class="crm-stat-val" style="font-size:20px;color:var(--red)">${urgentTasks.length}</div></div>
        <div class="crm-stat-card" style="padding:12px"><div class="crm-stat-label" style="font-size:11px">تسک معوق</div><div class="crm-stat-val" style="font-size:20px;color:var(--orange)">${overdueTasks.length}</div></div>
        <div class="crm-stat-card" style="padding:12px"><div class="crm-stat-label" style="font-size:11px">میانگین پیشرفت</div><div class="crm-stat-val" style="font-size:20px;color:var(--green)">${avgProgress}%</div></div>
      </div>
      <div style="font-size:13px;color:var(--text2)">${summaryMsg}</div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div class="crm-section-title" style="margin-bottom:0">بهره‌وری تیم این هفته</div>
      <div class="crm-toggle-group">
        <button class="crm-toggle-btn ${(crmState.reportPerfPeriod||'weekly')==='daily'?'active':''}" onclick="crmSetReportPerfPeriod('daily')">روزانه</button>
        <button class="crm-toggle-btn ${(crmState.reportPerfPeriod||'weekly')==='weekly'?'active':''}" onclick="crmSetReportPerfPeriod('weekly')">هفتگی</button>
        <button class="crm-toggle-btn ${(crmState.reportPerfPeriod||'weekly')==='monthly'?'active':''}" onclick="crmSetReportPerfPeriod('monthly')">ماهانه</button>
      </div>
    </div>
    <div id="crm-report-team-perf" style="margin-bottom:24px"></div>

    <div class="crm-section-title">نمودار وضعیت تسک‌ها</div>
    <div style="margin-bottom:8px">
      <div style="display:flex;width:100%;height:24px;border-radius:99px;overflow:hidden">
        ${taskChartData.filter(d=>d.value>0).map(d=>`<div title="${d.label}: ${Math.round(d.value/chartTotal*100)}%" style="width:${Math.round(d.value/chartTotal*100)}%;background:${d.color}"></div>`).join('')||`<div style="width:100%;background:var(--b1)"></div>`}
      </div>
    </div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px">
      ${taskChartData.map(d=>`<span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)"><span style="width:10px;height:10px;border-radius:50%;background:${d.color}"></span>${d.label} (${d.value})</span>`).join('')}
    </div>

    <div class="crm-section-title">تسک‌های معوق</div>
    ${overdueList.length?`<div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="background:var(--s2);border-bottom:1px solid var(--b1)">
          <th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">عنوان تسک</th>
          <th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">پروژه</th>
          <th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">مسئول</th>
          <th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">مهلت</th>
          <th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">روزهای تأخیر</th>
        </tr></thead>
        <tbody>
          ${overdueList.map(t=>`<tr style="background:rgba(240,92,92,0.06)">
            <td style="padding:8px">${t.title}</td>
            <td style="padding:8px">${t.proj?(t.proj.emoji+' '+t.proj.name):'—'}</td>
            <td style="padding:8px">${t.assignee?t.assignee.name:'—'}</td>
            <td style="padding:8px;color:var(--red)">${formatShamsiDate(t.deadline)}</td>
            <td style="padding:8px;color:var(--red);font-weight:700">${t.daysOverdue} روز</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>`:'<div class="crm-empty">✅ هیچ تسک معوقی وجود ندارد</div>'}`;

  drawDonut('crm-health-ring',[
    {value:healthScore,color:healthColor},
    {value:100-healthScore,color:'var(--b1)'}
  ], healthScore+'%','امتیاز سلامت');

  crmRenderReportTeamProductivity();
}

function crmSetReportPerfPeriod(period) {
  crmState.reportPerfPeriod=period;
  crmRenderReport();
}

function crmRenderReportTeamProductivity() {
  const period=crmState.reportPerfPeriod||'weekly';
  const now=Date.now();
  const cutoff=period==='daily'?now-86400000:period==='monthly'?now-30*86400000:now-7*86400000;
  const rows=crmState.personnel.map(p=>{
    const assigned=crmState.tasks.filter(t=>t.assigneeId===p.id);
    const doneAll=assigned.filter(t=>t.status==='done');
    const donePeriod=doneAll.filter(t=>t.completedAt&&t.completedAt>=cutoff);
    const pct=assigned.length?Math.round(doneAll.length/assigned.length*100):0;
    return {p,doneCount:donePeriod.length,pct};
  }).sort((a,b)=>b.doneCount-a.doneCount).slice(0,5);

  const container=document.getElementById('crm-report-team-perf');
  if(!container) return;
  container.innerHTML=rows.length?rows.map(r=>{
    const meta=crmRoleMeta(r.p.role);
    return`<div class="crm-personnel-perf-row">
      <div class="crm-avatar" style="background:${meta.color};width:32px;height:32px;font-size:12px">${crmInitials(r.p.name)}</div>
      <span class="crm-personnel-perf-name">${r.p.name}</span>
      <span class="crm-personnel-perf-count">${r.doneCount} تسک</span>
      <div class="crm-prog-wrap" style="flex:1;max-width:160px"><div class="crm-prog-fill ${r.pct===100?'green':''}" style="width:${r.pct}%"></div></div>
      <span style="font-size:11px;color:var(--text3);min-width:32px;text-align:left">${r.pct}%</span>
    </div>`;
  }).join(''):'<div class="crm-empty">پرسنلی موجود نیست</div>';
}

function crmRoleMeta(role) {
  const map={
    'مدیر کل':{badge:'crm-badge-purple',color:'var(--accent)'},
    'مدیر پیگیری':{badge:'crm-badge-green',color:'var(--green)'},
    'برنامه‌نویس':{badge:'crm-badge-blue',color:'#3b82f6'},
    'گرافیست':{badge:'crm-badge-amber',color:'var(--orange)'},
    'تولیدکننده محتوا':{badge:'crm-badge-cyan',color:'#22d3ee'},
    'سایر':{badge:'crm-badge-gray',color:'var(--text2)'}
  };
  return map[role]||map['سایر'];
}

function crmInitials(name) {
  const parts=(name||'').trim().split(/\s+/);
  if(!parts[0]) return '?';
  if(parts.length===1) return parts[0].slice(0,2);
  return parts[0].slice(0,1)+parts[1].slice(0,1);
}

function crmRenderPersonnel() {
  if(crmState.selectedPersonnelId){
    document.getElementById('crm-personnel-list-view').style.display='none';
    document.getElementById('crm-personnel-profile').style.display='';
    crmRenderPersonnelProfile(crmState.selectedPersonnelId);
    return;
  }
  document.getElementById('crm-personnel-list-view').style.display='';
  document.getElementById('crm-personnel-profile').style.display='none';

  const total=crmState.personnel.length;
  const active=crmState.personnel.filter(p=>p.projectIds&&p.projectIds.length>0).length;
  const rolesCount=new Set(crmState.personnel.map(p=>p.role)).size;
  document.getElementById('crm-personnel-stats').innerHTML=`
    <div class="crm-stat-card"><div class="crm-stat-label">کل پرسنل</div><div class="crm-stat-val" style="color:var(--accent)">${total}</div><div class="crm-stat-sub">نفر ثبت‌شده</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">فعال روی پروژه</div><div class="crm-stat-val" style="color:var(--green)">${active}</div><div class="crm-stat-sub">از ${total} نفر</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">تعداد پروژه‌ها</div><div class="crm-stat-val" style="color:#3b82f6">${crmState.projects.length}</div><div class="crm-stat-sub">پروژه فعال در سیستم</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">نقش‌های متنوع</div><div class="crm-stat-val" style="color:var(--orange)">${rolesCount}</div><div class="crm-stat-sub">نقش مختلف</div></div>`;

  const searchInput=document.getElementById('crm-personnel-search');
  const q=(searchInput?searchInput.value:'').trim().toLowerCase();
  const filtered=crmState.personnel.filter(p=>{
    if(!q) return true;
    return (p.name||'').toLowerCase().includes(q)||(p.role||'').toLowerCase().includes(q)||(p.mobile||'').toLowerCase().includes(q);
  });
  const countEl=document.getElementById('crm-personnel-search-count');
  if(countEl) countEl.textContent=`${filtered.length} نفر یافت شد`;

  document.getElementById('crm-personnel-list').innerHTML=filtered.length?filtered.map(p=>{
    const meta=crmRoleMeta(p.role);
    const projCount=crmState.projects.filter(pr=>(pr.memberIds||[]).includes(p.id)).length;
    const doneCount=crmState.tasks.filter(t=>t.assigneeId===p.id&&t.status==='done').length;
    const inprogCount=crmState.tasks.filter(t=>t.assigneeId===p.id&&t.status==='inprogress').length;
    return`<div class="crm-personnel-card" onclick="crmOpenPersonnelProfile('${p.id}')" style="cursor:pointer">
      <div class="crm-personnel-top">
        <div class="crm-avatar" style="background:${meta.color};width:48px;height:48px;font-size:16px;position:relative;flex-shrink:0">${crmInitials(p.name)}<span style="position:absolute;bottom:-1px;left:-1px;width:10px;height:10px;border-radius:50%;background:var(--green);border:2px solid var(--s2)"></span></div>
        <div style="flex:1;min-width:0">
          <div class="crm-personnel-name">${p.name}</div>
          <span class="crm-badge ${meta.badge}">${p.role}</span>
        </div>
        ${p.active===false?'<span class="crm-badge crm-badge-red">غیرفعال</span>':''}
      </div>
      <div style="display:flex;gap:8px">
        <div style="flex:1;text-align:center;background:var(--s1);border-radius:8px;padding:6px 4px"><div style="font-size:12px;font-weight:700;color:var(--text)">${projCount}</div><div style="font-size:10px;color:var(--text3)">📁 پروژه</div></div>
        <div style="flex:1;text-align:center;background:var(--s1);border-radius:8px;padding:6px 4px"><div style="font-size:12px;font-weight:700;color:var(--green)">${doneCount}</div><div style="font-size:10px;color:var(--text3)">✅ انجام شده</div></div>
        <div style="flex:1;text-align:center;background:var(--s1);border-radius:8px;padding:6px 4px"><div style="font-size:12px;font-weight:700;color:#3b82f6">${inprogCount}</div><div style="font-size:10px;color:var(--text3)">🕒 در حال انجام</div></div>
      </div>
      <div class="crm-personnel-mobile">📱 ${crmMaskMobile(p.mobile)}</div>
      <div style="display:flex;gap:6px">
        <button class="crm-btn" style="flex:1;justify-content:center" onclick="event.stopPropagation();crmOpenEditPersonnel('${p.id}')">ویرایش</button>
        <button class="crm-btn crm-btn-danger" style="flex:1;justify-content:center" onclick="event.stopPropagation();crmDeletePersonnel('${p.id}')">حذف</button>
      </div>
      <button class="crm-btn crm-btn-primary" style="width:100%;justify-content:center" onclick="event.stopPropagation();crmOpenPersonnelProfile('${p.id}')">مشاهده پروفایل</button>
    </div>`;
  }).join(''):'<div class="crm-empty">هنوز پرسنلی ثبت نشده</div>';
}

function crmMaskMobile(mobile) {
  if(!mobile) return '—';
  const m=mobile.trim();
  if(m.length<=7) return m;
  return m.slice(0,4)+'***'+m.slice(-3);
}

function crmOpenPersonnelProfile(id) {
  crmState.selectedPersonnelId=id;
  crmRenderPersonnel();
}

function crmBackToPersonnel() {
  crmState.selectedPersonnelId=null;
  crmRenderPersonnel();
}

function crmGoToProjectFromProfile(pid) {
  crmState.selectedPersonnelId=null;
  crmNavigate('projects');
  crmState.selectedProjectId=pid;
  crmRenderProjects();
}

function crmRenderPersonnelProfile(id) {
  const p=crmState.personnel.find(x=>x.id===id);
  if(!p){crmState.selectedPersonnelId=null;crmRenderPersonnel();return;}
  const meta=crmRoleMeta(p.role);
  const allTasks=crmState.tasks.filter(t=>t.assigneeId===id);
  const openTasks=allTasks.filter(t=>t.status!=='done');
  const doneTasks=allTasks.filter(t=>t.status==='done');
  const completionRate=allTasks.length?Math.round(doneTasks.length/allTasks.length*100):0;
  const memberProjects=crmState.projects.filter(pr=>(pr.memberIds||[]).includes(id));
  const activeProjects=memberProjects.filter(pr=>pr.status==='inprogress'||pr.status==='planning');
  const todayStr=new Date().toISOString().slice(0,10);
  const logs=crmState.activityLog.filter(a=>a.userId===id).sort((a,b)=>b.timestamp-a.timestamp).slice(0,8);
  const permissions=crmPermissionsMap[p.role]||crmPermissionsMap['سایر'];
  const taskStatusLabels={backlog:'انتظار',todo:'برنامه‌ریزی',inprogress:'در حال انجام',done:'انجام‌شده'};

  document.getElementById('crm-personnel-profile').innerHTML=`
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
      <button class="crm-btn" onclick="crmBackToPersonnel()">← بازگشت به پرسنل</button>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="crm-btn" onclick="crmOpenEditPersonnel('${id}')">✏️ ویرایش</button>
        <button class="crm-btn" onclick="crmOpenAssignProjectModal('${id}')">📋 واگذاری پروژه</button>
        <button class="crm-btn" onclick="crmOpenAssignTaskModal('${id}')">📌 واگذاری تسک</button>
        <button class="crm-btn crm-btn-danger" onclick="crmDeactivatePersonnel('${id}')">🚫 ${p.active===false?'فعال کردن':'غیرفعال کردن'}</button>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap">
      <div class="crm-avatar" style="width:64px;height:64px;font-size:22px;background:${meta.color};flex-shrink:0">${crmInitials(p.name)}</div>
      <div>
        <div style="font-size:18px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;flex-wrap:wrap">${p.name}<span class="crm-badge ${meta.badge}">${p.role}</span>${p.active===false?'<span class="crm-badge crm-badge-red">غیرفعال</span>':''}</div>
        <div style="font-size:12px;color:var(--text3);margin-top:4px;display:flex;gap:14px;flex-wrap:wrap">
          <span>📱 ${p.mobile||'—'}</span>
          <span>📧 ${p.email||'—'}</span>
          <span>تاریخ عضویت: ${formatShamsiDate(p.joinDate)}</span>
        </div>
      </div>
    </div>

    <div class="crm-stat-grid">
      <div class="crm-stat-card"><div class="crm-stat-label">پروژه‌های فعال</div><div class="crm-stat-val" style="color:var(--accent)">${activeProjects.length}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">تسک‌های باز</div><div class="crm-stat-val" style="color:#3b82f6">${openTasks.length}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">تسک‌های انجام شده</div><div class="crm-stat-val" style="color:var(--green)">${doneTasks.length}</div></div>
      <div class="crm-stat-card"><div class="crm-stat-label">نرخ تکمیل</div><div class="crm-stat-val" style="color:var(--orange)">${completionRate}%</div></div>
    </div>

    <div class="crm-section-title" style="margin-top:20px">پروژه‌های تخصیص داده شده</div>
    <div style="margin-bottom:20px">
      ${memberProjects.length?memberProjects.map(pr=>{
        const pct=crmProjectProgress(pr.id);
        const sm=projectStatusMap[pr.status]||projectStatusMap.planning;
        const badgeClass=crmStatusBadgeClass(sm.color);
        return`<div class="crm-project-item" onclick="crmGoToProjectFromProfile('${pr.id}')" style="cursor:pointer">
          <div class="crm-project-header">
            <div class="crm-project-name" style="font-size:13px"><span class="crm-project-emoji">${pr.emoji||'📁'}</span>${pr.name}</div>
            <span class="crm-badge crm-badge-${badgeClass}">${sm.icon} ${sm.label}</span>
          </div>
          <div class="crm-project-prog"><div class="crm-prog-wrap"><div class="crm-prog-fill ${pct===100?'green':''}" style="width:${pct}%"></div></div><span>${pct}%</span></div>
        </div>`;
      }).join(''):'<div class="crm-empty">پروژه‌ای تخصیص داده نشده</div>'}
    </div>

    <div class="crm-section-title">تسک‌های فعلی</div>
    <div style="margin-bottom:20px">
      ${openTasks.length?openTasks.map(t=>{
        const overdue=t.deadline&&t.deadline<todayStr;
        return`<div class="crm-task-item">
          <div class="crm-task-check ${t.done?'checked':''}" onclick="crmToggleTask('${t.id}')">${t.done?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>':''}</div>
          <div class="crm-task-body">
            <div class="crm-task-title">${t.title}</div>
            <div class="crm-task-meta">
              <span class="crm-badge crm-badge-${t.priority==='urgent'?'red':t.priority==='high'?'amber':t.priority==='medium'?'blue':'gray'}">${{urgent:'فوری',high:'بالا',medium:'متوسط',low:'کم'}[t.priority]}</span>
              ${t.deadline?`<span style="font-size:11px;color:${overdue?'var(--red)':'var(--text3)'}">📅 ${formatShamsiDate(t.deadline)}</span>`:''}
              <span class="crm-badge crm-badge-gray">${taskStatusLabels[t.status]||t.status}</span>
            </div>
          </div>
        </div>`;
      }).join(''):'<div class="crm-empty">تسک باز ندارد</div>'}
    </div>

    <div class="crm-section-title">فعالیت‌های اخیر</div>
    <div style="margin-bottom:20px">
      ${logs.length?logs.map(a=>{
        const dotColor=a.type==='task'?'#3b82f6':'var(--accent)';
        return`<div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:12px;color:var(--text2)"><span style="width:7px;height:7px;border-radius:50%;background:${dotColor};flex-shrink:0"></span><span style="flex:1">${a.action}</span><span style="color:var(--text3);font-size:11px">${crmRelativeTime(a.timestamp)}</span></div>`;
      }).join(''):'<div class="crm-empty">فعالیتی ثبت نشده</div>'}
    </div>

    <div class="crm-section-title">دسترسی‌ها و نقش</div>
    <div class="crm-stat-card" style="padding:16px;margin-bottom:20px">
      <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:8px">${p.role}</div>
      <div style="display:flex;flex-direction:column;gap:6px">
        ${permissions.map(perm=>`<div style="font-size:12px;color:var(--text2);display:flex;align-items:center;gap:6px"><span style="color:var(--green)">✓</span>${perm}</div>`).join('')}
      </div>
    </div>

    ${(()=>{
      attEnsureLoaded();
      const month=getCurrentShamsiMonth();
      const monthRecords=attendanceState.records.filter(r=>r.personnelId===id&&r.date&&r.date.startsWith(month)).sort((a,b)=>b.date.localeCompare(a.date));
      const totalHours=monthRecords.reduce((s,r)=>s+(r.totalHours||0),0);
      return`<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
          <div class="crm-section-title" style="margin-bottom:0">⏱️ حضور این ماه</div>
          <a href="javascript:void(0)" onclick="attGoToPersonAttendance('${id}')" style="font-size:11px;color:var(--accent);font-weight:600">مشاهده کامل ←</a>
        </div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:10px">مجموع ساعت این ماه: <strong style="color:var(--text)">${formatHours(totalHours)}</strong></div>
        <div style="overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="background:var(--s2);border-bottom:1px solid var(--b1)">
              <th style="padding:6px 8px;text-align:right;font-size:11px;color:var(--text3)">تاریخ</th>
              <th style="padding:6px 8px;text-align:right;font-size:11px;color:var(--text3)">ورود</th>
              <th style="padding:6px 8px;text-align:right;font-size:11px;color:var(--text3)">خروج</th>
              <th style="padding:6px 8px;text-align:right;font-size:11px;color:var(--text3)">کارکرد</th>
            </tr></thead>
            <tbody>
              ${monthRecords.length?monthRecords.slice(0,8).map((r,i)=>`<tr class="${i%2===0?'bg-s1':'bg-s2'}">
                <td style="padding:6px 8px">${formatShamsiDate(r.date)}</td>
                <td style="padding:6px 8px;direction:ltr;text-align:right">${r.checkIn||'—'}</td>
                <td style="padding:6px 8px;direction:ltr;text-align:right">${r.checkOut?r.checkOut:'<span style="color:var(--green)">— در محل</span>'}</td>
                <td style="padding:6px 8px">${r.totalHours!=null?formatHours(r.totalHours):'—'}</td>
              </tr>`).join(''):'<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--text3)">رکوردی برای این ماه ثبت نشده</td></tr>'}
            </tbody>
          </table>
        </div>`;
    })()}`;
}

function crmDeactivatePersonnel(id) {
  const p=crmState.personnel.find(x=>x.id===id);
  if(!p) return;
  if(p.active===false){
    p.active=true;
    crmSave();crmShowToast('success','نیرو فعال شد');crmRenderPersonnel();
    return;
  }
  crmConfirmToast('آیا مطمئنید؟', ()=>{
    p.active=false;
    crmSave();
    crmShowToast('warning','نیرو غیرفعال شد');
    crmRenderPersonnel();
  });
}

function crmRenderPersonnelProjectCheckboxes(selectedIds) {
  const sel=selectedIds||[];
  const el=document.getElementById('crm-pers-projects-list');
  el.innerHTML=crmState.projects.length?crmState.projects.map(p=>`<label class="crm-checkbox-row"><input type="checkbox" value="${p.id}" ${sel.includes(p.id)?'checked':''}> ${p.emoji||''} ${p.name}</label>`).join(''):'<span style="font-size:12px;color:var(--text3)">پروژه‌ای موجود نیست</span>';
}

function crmRenderProjectTeamCheckboxes(selectedIds) {
  const sel=selectedIds||[];
  const el=document.getElementById('crm-proj-team-list');
  const people=crmState.personnel.filter(p=>p.active!==false);
  el.innerHTML=people.length?people.map(p=>{
    const meta=crmRoleMeta(p.role);
    return `<label class="crm-checkbox-row"><input type="checkbox" value="${p.id}" ${sel.includes(p.id)?'checked':''}><span class="crm-avatar" style="width:20px;height:20px;font-size:9px;background:${meta.color}">${crmInitials(p.name)}</span> ${p.name} <span style="color:var(--text3)">(${p.role})</span></label>`;
  }).join(''):'<span style="font-size:12px;color:var(--text3)">پرسنلی ثبت نشده</span>';
}

function crmRenderProjectManagerSelect(selectedId) {
  const sel=document.getElementById('crm-proj-manager');
  const people=crmState.personnel.filter(p=>p.active!==false);
  sel.innerHTML='<option value="">انتخاب مدیر پروژه</option>'+people.map(p=>`<option value="${p.id}">${p.name} (${p.role})</option>`).join('');
  sel.value=selectedId||'';
}

function crmRenderTaskAssigneeSelect(selectedId, projectId) {
  const sel=document.getElementById('crm-task-assignee');
  const proj=crmState.projects.find(p=>p.id===projectId);
  const memberIds=proj?(proj.memberIds||[]):[];
  const people=crmState.personnel.filter(p=>memberIds.includes(p.id)&&p.active!==false);
  sel.innerHTML='<option value="">انتخاب مسئول</option>'+people.map(p=>`<option value="${p.id}">${p.name} (${p.role})</option>`).join('');
  sel.value=selectedId||'';
}

function crmStatusBadgeClass(color) {
  return ({orange:'amber',blue:'blue',green:'green',red:'red'})[color]||'gray';
}

function crmRelativeTime(timestamp) {
  const diffDays=Math.floor((Date.now()-timestamp)/86400000);
  if(diffDays<=0) return 'امروز';
  if(diffDays===1) return 'دیروز';
  return diffDays+' روز پیش';
}

function crmSavePersonnel() {
  const name=document.getElementById('crm-pers-name').value.trim();
  if(!name){alert('نام را وارد کنید');return;}
  const id=document.getElementById('crm-edit-personnel-id').value;
  const projectIds=[...document.querySelectorAll('#crm-pers-projects-list input:checked')].map(c=>c.value);
  const existing=id?crmState.personnel.find(p=>p.id===id):null;
  const data={name,mobile:document.getElementById('crm-pers-mobile').value.trim(),role:document.getElementById('crm-pers-role').value,projectIds,
    email:document.getElementById('crm-pers-email').value.trim(),
    joinDate:document.getElementById('crm-pers-join').value.trim(),
    active:existing?(existing.active!==false):true};
  if(id){const i=crmState.personnel.findIndex(p=>p.id===id);if(i>=0)crmState.personnel[i]={...crmState.personnel[i],...data};}
  else crmState.personnel.push({id:crmUid(),createdAt:Date.now(),...data});
  crmSave();crmShowToast('success',id?'نیرو ویرایش شد':'نیرو اضافه شد');crmCloseModal();crmRenderPersonnel();
}

function crmDeletePersonnel(id) {
  if(!confirm('این نیرو حذف شود؟')) return;
  crmState.personnel=crmState.personnel.filter(p=>p.id!==id);
  crmState.projects.forEach(p=>{
    if(p.teamIds) p.teamIds=p.teamIds.filter(tid=>tid!==id);
    if(p.memberIds) p.memberIds=p.memberIds.filter(mid=>mid!==id);
    if(p.managerId===id) p.managerId=null;
  });
  crmState.tasks.forEach(t=>{if(t.assigneeId===id) t.assigneeId=null;});
  crmSave();crmShowToast('warning','نیرو حذف شد');crmRenderPersonnel();
}

function crmOpenEditPersonnel(id) {
  const p=crmState.personnel.find(p=>p.id===id);
  if(!p) return;
  crmOpenModal('personnel');
  document.getElementById('crm-modal-personnel-title').textContent='ویرایش نیرو';
  document.getElementById('crm-edit-personnel-id').value=id;
  document.getElementById('crm-pers-name').value=p.name;
  document.getElementById('crm-pers-mobile').value=p.mobile||'';
  document.getElementById('crm-pers-role').value=p.role;
  document.getElementById('crm-pers-email').value=p.email||'';
  document.getElementById('crm-pers-join').value=p.joinDate||'';
  crmRenderPersonnelProjectCheckboxes(p.projectIds||[]);
}

// MODALS
function crmOpenModal(type) {
  document.getElementById('crm-overlay').style.display='flex';
  ['crm-modal-project','crm-modal-task','crm-modal-personnel','crm-modal-assign-project','crm-modal-assign-task'].forEach(mid=>document.getElementById(mid).style.display='none');
  if(type==='project'){
    document.getElementById('crm-modal-project').style.display='';
    document.getElementById('crm-modal-project-title').textContent='پروژه جدید';
    document.getElementById('crm-edit-project-id').value='';
    document.getElementById('crm-proj-name').value='';
    document.getElementById('crm-proj-emoji').value='📁';
    document.getElementById('crm-proj-status').value='planning';
    document.getElementById('crm-proj-desc').value='';
    document.getElementById('crm-proj-deadline').value='';
    document.getElementById('crm-proj-start').value='';
    document.getElementById('crm-proj-end').value='';
    crmRenderProjectManagerSelect('');
    crmRenderProjectTeamCheckboxes([]);
    crmState.modalInitialTasks=[];
    crmRenderInitialTasksList();
    document.getElementById('crm-initial-tasks-body').style.display='none';
    document.getElementById('crm-initial-tasks-arrow').textContent='▸';
    const initAssigneeSel=document.getElementById('crm-initial-task-assignee');
    initAssigneeSel.innerHTML='<option value="">مسئول</option>'+crmState.personnel.filter(p=>p.active!==false).map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
    setTimeout(()=>document.getElementById('crm-proj-name').focus(),100);
  } else if(type==='task') {
    document.getElementById('crm-modal-task').style.display='';
    document.getElementById('crm-modal-task-title').textContent='تسک جدید';
    document.getElementById('crm-edit-task-id').value='';
    document.getElementById('crm-task-title').value='';
    document.getElementById('crm-task-desc').value='';
    document.getElementById('crm-task-priority').value='medium';
    document.getElementById('crm-task-status').value='todo';
    document.getElementById('crm-task-deadline').value='';
    crmRenderTaskAssigneeSelect('', crmState.selectedProjectId);
    crmState.modalMicrotasks=[];
    crmRenderModalMicrotasks();
    setTimeout(()=>document.getElementById('crm-task-title').focus(),100);
  } else if(type==='personnel') {
    document.getElementById('crm-modal-personnel').style.display='';
    document.getElementById('crm-modal-personnel-title').textContent='افزودن نیرو';
    document.getElementById('crm-edit-personnel-id').value='';
    document.getElementById('crm-pers-name').value='';
    document.getElementById('crm-pers-mobile').value='';
    document.getElementById('crm-pers-role').value='مدیر کل';
    document.getElementById('crm-pers-email').value='';
    document.getElementById('crm-pers-join').value='';
    crmRenderPersonnelProjectCheckboxes([]);
    setTimeout(()=>document.getElementById('crm-pers-name').focus(),100);
  }
}

function crmOpenAssignProjectModal(personnelId) {
  document.getElementById('crm-overlay').style.display='flex';
  ['crm-modal-project','crm-modal-task','crm-modal-personnel','crm-modal-assign-task'].forEach(mid=>document.getElementById(mid).style.display='none');
  document.getElementById('crm-modal-assign-project').style.display='';
  const p=crmState.personnel.find(x=>x.id===personnelId);
  document.getElementById('crm-modal-assign-project-title').textContent='واگذاری پروژه به '+(p?p.name:'');
  document.getElementById('crm-assign-project-personnel-id').value=personnelId;
  const list=document.getElementById('crm-assign-project-list');
  list.innerHTML=crmState.projects.length?crmState.projects.map(pr=>`<label class="crm-checkbox-row"><input type="checkbox" value="${pr.id}" ${(pr.memberIds||[]).includes(personnelId)?'checked':''}> ${pr.emoji||'📁'} ${pr.name}</label>`).join(''):'<span style="font-size:12px;color:var(--text3)">پروژه‌ای موجود نیست</span>';
}

function crmSaveAssignProject() {
  const personnelId=document.getElementById('crm-assign-project-personnel-id').value;
  const checkedIds=[...document.querySelectorAll('#crm-assign-project-list input:checked')].map(c=>c.value);
  crmState.projects.forEach(pr=>{
    const ids=new Set(pr.memberIds||[]);
    if(checkedIds.includes(pr.id)) ids.add(personnelId); else ids.delete(personnelId);
    pr.memberIds=[...ids];
    pr.teamIds=pr.memberIds;
  });
  crmSave();
  crmShowToast('success','پروژه‌ها بروزرسانی شد');
  crmCloseModal();
  crmRenderPersonnel();
}

function crmOpenAssignTaskModal(personnelId) {
  document.getElementById('crm-overlay').style.display='flex';
  ['crm-modal-project','crm-modal-task','crm-modal-personnel','crm-modal-assign-project'].forEach(mid=>document.getElementById(mid).style.display='none');
  document.getElementById('crm-modal-assign-task').style.display='';
  const p=crmState.personnel.find(x=>x.id===personnelId);
  document.getElementById('crm-modal-assign-task-title').textContent='واگذاری تسک به '+(p?p.name:'');
  document.getElementById('crm-assign-task-personnel-id').value=personnelId;
  const sel=document.getElementById('crm-assign-task-project');
  sel.innerHTML='<option value="">انتخاب پروژه</option>'+crmState.projects.map(pr=>`<option value="${pr.id}">${pr.emoji||''} ${pr.name}</option>`).join('');
  sel.value='';
  document.getElementById('crm-assign-task-list').innerHTML='<span style="font-size:12px;color:var(--text3)">ابتدا پروژه را انتخاب کنید</span>';
}

function crmRenderAssignTaskList() {
  const pid=document.getElementById('crm-assign-task-project').value;
  const personnelId=document.getElementById('crm-assign-task-personnel-id').value;
  const list=document.getElementById('crm-assign-task-list');
  if(!pid){list.innerHTML='<span style="font-size:12px;color:var(--text3)">ابتدا پروژه را انتخاب کنید</span>';return;}
  const tasks=crmState.tasks.filter(t=>t.projectId===pid);
  list.innerHTML=tasks.length?tasks.map(t=>`<label class="crm-checkbox-row"><input type="checkbox" value="${t.id}" ${t.assigneeId===personnelId?'checked':''}> ${t.title}</label>`).join(''):'<span style="font-size:12px;color:var(--text3)">تسکی موجود نیست</span>';
}

function crmSaveAssignTask() {
  const personnelId=document.getElementById('crm-assign-task-personnel-id').value;
  const pid=document.getElementById('crm-assign-task-project').value;
  if(!pid){crmCloseModal();return;}
  const checkedIds=[...document.querySelectorAll('#crm-assign-task-list input:checked')].map(c=>c.value);
  crmState.tasks.forEach(t=>{
    if(t.projectId!==pid) return;
    if(checkedIds.includes(t.id)) t.assigneeId=personnelId;
    else if(t.assigneeId===personnelId) t.assigneeId=null;
  });
  crmSave();
  crmShowToast('success','تسک‌ها واگذار شد');
  crmCloseModal();
  crmRenderPersonnel();
}

function crmCloseModal() { document.getElementById('crm-overlay').style.display='none'; }

function crmToggleInitialTasksSection() {
  const body=document.getElementById('crm-initial-tasks-body');
  const arrow=document.getElementById('crm-initial-tasks-arrow');
  const open=body.style.display!=='none';
  body.style.display=open?'none':'';
  arrow.textContent=open?'▸':'▾';
}

function crmRenderInitialTasksList() {
  const list=document.getElementById('crm-initial-tasks-list');
  if(!list) return;
  const tasks=crmState.modalInitialTasks||[];
  list.innerHTML=tasks.length?tasks.map(t=>{
    const assignee=t.assigneeId?crmState.personnel.find(p=>p.id===t.assigneeId):null;
    return`<div class="crm-checkbox-row" style="justify-content:space-between">
      <span>${t.title}${assignee?` <span style="color:var(--text3)">(${assignee.name})</span>`:''}</span>
      <button class="crm-icon-btn" onclick="crmRemoveInitialTask('${t._tmpId}')"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>`;
  }).join(''):'<span style="font-size:12px;color:var(--text3)">تسکی اضافه نشده</span>';
}

function crmAddInitialTask() {
  const titleInp=document.getElementById('crm-initial-task-title');
  const title=titleInp.value.trim();
  if(!title) return;
  const assigneeId=document.getElementById('crm-initial-task-assignee').value||null;
  if(!crmState.modalInitialTasks) crmState.modalInitialTasks=[];
  crmState.modalInitialTasks.push({_tmpId:crmUid(),title,assigneeId});
  titleInp.value='';
  crmRenderInitialTasksList();
}

function crmRemoveInitialTask(tmpId) {
  crmState.modalInitialTasks=(crmState.modalInitialTasks||[]).filter(t=>t._tmpId!==tmpId);
  crmRenderInitialTasksList();
}

function crmRenderModalMicrotasks() {
  document.getElementById('crm-microtask-list').innerHTML=crmState.modalMicrotasks.map(m=>`<div class="crm-microtask-item ${m.done?'done':''}"><div class="crm-micro-check ${m.done?'checked':''}" onclick="crmToggleModalMicro('${m.id}')">${m.done?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>':''}</div><span class="crm-microtask-text">${m.text}</span><button class="crm-icon-btn" onclick="crmRemoveModalMicro('${m.id}')"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>`).join('');
}

function crmAddMicrotaskInModal() {
  const inp=document.getElementById('crm-micro-input');
  const text=inp.value.trim();
  if(!text) return;
  crmState.modalMicrotasks.push({id:crmUid(),text,done:false});
  crmRenderModalMicrotasks();
  inp.value='';inp.focus();
}

function crmToggleModalMicro(id) {
  const m=crmState.modalMicrotasks.find(m=>m.id===id);
  if(m){m.done=!m.done;crmRenderModalMicrotasks();}
}

function crmRemoveModalMicro(id) {
  crmState.modalMicrotasks=crmState.modalMicrotasks.filter(m=>m.id!==id);
  crmRenderModalMicrotasks();
}

function crmSaveProject() {
  const name=document.getElementById('crm-proj-name').value.trim();
  if(!name){alert('نام پروژه را وارد کنید');return;}
  const id=document.getElementById('crm-edit-project-id').value;
  const memberIds=[...document.querySelectorAll('#crm-proj-team-list input:checked')].map(c=>c.value);
  const data={name,emoji:document.getElementById('crm-proj-emoji').value||'📁',status:document.getElementById('crm-proj-status').value,desc:document.getElementById('crm-proj-desc').value,deadline:document.getElementById('crm-proj-deadline').value,
    teamIds:memberIds,
    memberIds,
    managerId:document.getElementById('crm-proj-manager').value||null,
    startDate:document.getElementById('crm-proj-start').value,
    endDate:document.getElementById('crm-proj-end').value};
  let pid;
  if(id){const i=crmState.projects.findIndex(p=>p.id===id);if(i>=0)crmState.projects[i]={...crmState.projects[i],...data};pid=id;}
  else{pid=crmUid();crmState.projects.push({id:pid,createdAt:Date.now(),...data});}
  (crmState.modalInitialTasks||[]).forEach(t=>{
    crmState.tasks.push({id:crmUid(),projectId:pid,createdAt:Date.now(),title:t.title,desc:'',priority:'medium',status:'backlog',done:false,assigneeId:t.assigneeId||null,deadline:'',startDate:'',microtasks:[]});
  });
  crmState.modalInitialTasks=[];
  crmSave();logActivity('project',id?'ویرایش پروژه':'ایجاد پروژه',{projectId:pid});
  crmShowToast('success',id?'پروژه ویرایش شد':'پروژه ایجاد شد');
  crmCloseModal();crmRenderDashboard();if(crmState.currentTab==='projects')crmRenderProjects();
}

function crmSaveTask() {
  const title=document.getElementById('crm-task-title').value.trim();
  if(!title){alert('عنوان تسک را وارد کنید');return;}
  const id=document.getElementById('crm-edit-task-id').value;
  const existing=id?crmState.tasks.find(t=>t.id===id):null;
  const isDone=document.getElementById('crm-task-status').value==='done';
  const data={title,desc:document.getElementById('crm-task-desc').value,priority:document.getElementById('crm-task-priority').value,status:document.getElementById('crm-task-status').value,deadline:document.getElementById('crm-task-deadline').value,microtasks:crmState.modalMicrotasks,done:isDone,
    assigneeId:document.getElementById('crm-task-assignee').value||null,
    startDate:existing?(existing.startDate||''):'',
    completedAt:isDone?(existing&&existing.completedAt?existing.completedAt:Date.now()):null};
  let tid,pid;
  if(id){const i=crmState.tasks.findIndex(t=>t.id===id);if(i>=0)crmState.tasks[i]={...crmState.tasks[i],...data};tid=id;pid=crmState.tasks[i].projectId;}
  else{pid=crmState.selectedProjectId||(crmState.projects[0]&&crmState.projects[0].id);if(!pid){alert('ابتدا یک پروژه بسازید');return;}tid=crmUid();crmState.tasks.push({id:tid,projectId:pid,createdAt:Date.now(),...data});}
  crmSave();logActivity('task',id?'ویرایش تسک':'ایجاد تسک',{projectId:pid,taskId:tid});
  crmShowToast('success',id?'تسک ویرایش شد':'تسک ایجاد شد');
  crmCloseModal();crmRenderPage();
}

function crmToggleTask(id) {
  const t=crmState.tasks.find(t=>t.id===id);
  if(!t) return;
  t.done=!t.done;t.status=t.done?'done':'inprogress';
  t.completedAt=t.done?Date.now():null;
  if(t.done) t.microtasks=(t.microtasks||[]).map(m=>({...m,done:true}));
  crmSave();logActivity('task',t.done?'تکمیل تسک':'بازگشایی تسک',{projectId:t.projectId,taskId:t.id});crmRenderPage();
}

function crmToggleMicro(taskId,microId) {
  const t=crmState.tasks.find(t=>t.id===taskId);
  if(!t) return;
  const m=t.microtasks.find(m=>m.id===microId);
  if(!m) return;
  m.done=!m.done;
  const allDone=t.microtasks.every(m=>m.done);
  if(allDone&&t.microtasks.length){t.done=true;t.status='done';t.completedAt=Date.now();}
  else if(t.done&&!allDone){t.done=false;t.status='inprogress';t.completedAt=null;}
  crmSave();crmRenderPage();
}

function crmDeleteTask(id) {
  if(!confirm('حذف این تسک؟')) return;
  const t=crmState.tasks.find(t=>t.id===id);
  crmState.tasks=crmState.tasks.filter(t=>t.id!==id);
  crmSave();logActivity('task','حذف تسک',{projectId:t?t.projectId:null,taskId:id});
  crmShowToast('warning','تسک حذف شد');
  crmRenderPage();
}

function crmDeleteProject(id) {
  if(!confirm('پروژه و همه تسک‌هایش حذف شود؟')) return;
  crmState.projects=crmState.projects.filter(p=>p.id!==id);
  crmState.tasks=crmState.tasks.filter(t=>t.projectId!==id);
  if(crmState.selectedProjectId===id) crmState.selectedProjectId=null;
  crmSave();logActivity('project','حذف پروژه',{projectId:id});
  crmShowToast('warning','پروژه حذف شد');
  crmRenderProjects();
}

function crmToggleArchiveProject(id) {
  const p=crmState.projects.find(p=>p.id===id);
  if(!p) return;
  p.archived=!p.archived;
  crmSave();crmRenderProjects();
}

function crmOpenEditProject(id) {
  const p=crmState.projects.find(p=>p.id===id);
  if(!p) return;
  crmOpenModal('project');
  document.getElementById('crm-modal-project-title').textContent='ویرایش پروژه';
  document.getElementById('crm-edit-project-id').value=id;
  document.getElementById('crm-proj-name').value=p.name;
  document.getElementById('crm-proj-emoji').value=p.emoji||'📁';
  document.getElementById('crm-proj-status').value=p.status||'planning';
  document.getElementById('crm-proj-desc').value=p.desc||'';
  document.getElementById('crm-proj-deadline').value=p.deadline||'';
  document.getElementById('crm-proj-start').value=p.startDate||'';
  document.getElementById('crm-proj-end').value=p.endDate||'';
  crmRenderProjectManagerSelect(p.managerId||'');
  crmRenderProjectTeamCheckboxes(p.memberIds||p.teamIds||[]);
}

function crmOpenEditTask(id) {
  const t=crmState.tasks.find(t=>t.id===id);
  if(!t) return;
  crmOpenModal('task');
  document.getElementById('crm-modal-task-title').textContent='ویرایش تسک';
  document.getElementById('crm-edit-task-id').value=id;
  document.getElementById('crm-task-title').value=t.title;
  document.getElementById('crm-task-desc').value=t.desc||'';
  document.getElementById('crm-task-priority').value=t.priority;
  document.getElementById('crm-task-status').value=t.status;
  document.getElementById('crm-task-deadline').value=t.deadline||'';
  crmRenderTaskAssigneeSelect(t.assigneeId||'', t.projectId);
  crmState.modalMicrotasks=JSON.parse(JSON.stringify(t.microtasks||[]));
  crmRenderModalMicrotasks();
}

function crmRenderPage() {
  const t=crmState.currentTab;
  if(t==='dashboard') crmRenderDashboard();
  else if(t==='projects') crmRenderProjects();
  else if(t==='kanban'){crmPopulateKanbanFilter();crmRenderKanban();}
  else if(t==='report') crmRenderReport();
  else if(t==='personnel') crmRenderPersonnel();
}

// ==================== ATTENDANCE SYSTEM ====================
const attendanceState = {
  records: [],
  filter: { month: '', personnelId: '' },
  page: 1,
  collapsed_weekly: false,
  collapsed_monthly: false
};

function saveAttendance() {
  localStorage.setItem('watanAttendance', JSON.stringify(attendanceState.records));
}

function loadAttendance() {
  const s = localStorage.getItem('watanAttendance');
  if (s) { try { attendanceState.records = JSON.parse(s); } catch(e){} }
  if (!attendanceState.records.length) seedAttendance();
  attendanceState._loaded = true;
}

function attEnsureLoaded() {
  if (!attendanceState._loaded) loadAttendance();
}

function attRandomTime(minH, minM, maxH, maxM) {
  const minTotal = minH*60+minM, maxTotal = maxH*60+maxM;
  const total = minTotal + Math.floor(Math.random()*(maxTotal-minTotal));
  const h = Math.floor(total/60), m = total%60;
  return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0');
}

function seedAttendance() {
  const people = crmState.personnel.slice(0,3);
  if (!people.length) { attendanceState.records=[]; return; }
  const noCheckoutIdx = new Set();
  while (noCheckoutIdx.size < 4) noCheckoutIdx.add(Math.floor(Math.random()*6));
  const records=[];
  for (let i=0;i<30;i++) {
    const greg=new Date(); greg.setDate(greg.getDate()-i);
    const dateStr=new persianDate(greg).format('YYYY/MM/DD');
    const person=people[i%people.length];
    const checkIn=attRandomTime(8,0,10,30);
    const checkOut=noCheckoutIdx.has(i)?null:attRandomTime(17,0,21,0);
    const totalHours=checkOut?calcHours(checkIn,checkOut):null;
    records.push({id:crmUid(),personnelId:person.id,date:dateStr,checkIn,checkOut,totalHours,note:''});
  }
  attendanceState.records=records;
  saveAttendance();
}

function calcHours(checkIn, checkOut) {
  if (!checkIn || !checkOut) return null;
  const [h1,m1] = checkIn.split(':').map(Number);
  const [h2,m2] = checkOut.split(':').map(Number);
  return ((h2*60+m2) - (h1*60+m1)) / 60;
}

function formatHours(decimal) {
  if (!decimal) return '—';
  const h = Math.floor(decimal);
  const m = Math.round((decimal - h) * 60);
  const toFa = n => String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  return m > 0 ? `${toFa(h)} ساعت و ${toFa(m)} دقیقه` : `${toFa(h)} ساعت`;
}

function getCurrentShamsiMonth() {
  return new persianDate().format('YYYY/MM');
}

function attToday() {
  return new persianDate().format('YYYY/MM/DD');
}

function attRecordsForMonth(month) {
  return attendanceState.records.filter(r=>r.date && r.date.startsWith(month));
}

function attFilteredRecords() {
  const month=attendanceState.filter.month||getCurrentShamsiMonth();
  let recs=attRecordsForMonth(month);
  if(attendanceState.filter.personnelId) recs=recs.filter(r=>r.personnelId===attendanceState.filter.personnelId);
  return recs.sort((a,b)=>b.date.localeCompare(a.date));
}

function attCurrentWeekDates() {
  const today=new Date(); today.setHours(0,0,0,0);
  const dow=today.getDay();
  const diffToSat=(dow+1)%7;
  const saturday=new Date(today); saturday.setDate(today.getDate()-diffToSat);
  const days=[];
  for(let i=0;i<7;i++){ const d=new Date(saturday); d.setDate(saturday.getDate()+i); days.push(d); }
  return days;
}

function renderAttendancePage() {
  attEnsureLoaded();
  if (!attendanceState.filter.month) attendanceState.filter.month = getCurrentShamsiMonth();

  const container = document.getElementById('attendance-page');
  container.innerHTML = `
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:20px;font-weight:800;color:var(--text)">حضور و غیاب</h1>
        <div style="font-size:12px;color:var(--text3);margin-top:2px">ثبت و مدیریت ورود و خروج پرسنل</div>
      </div>
      <button class="crm-btn crm-btn-primary" onclick="attOpenModal()">+ ثبت حضور</button>
    </div>

    <div class="crm-stat-grid" id="att-summary-cards"></div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:16px 0;background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:10px 14px">
      <select id="att-filter-month" class="crm-form-input" style="width:auto"></select>
      <select id="att-filter-personnel" class="crm-form-input" style="width:auto"><option value="">همه پرسنل</option></select>
      <button class="crm-btn crm-btn-primary" onclick="attApplyFilter()">اعمال فیلتر</button>
      <button class="crm-btn" onclick="attExportExcel()">خروجی Excel</button>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px;background:var(--s1);border:1px dashed var(--b2);border-radius:10px;padding:10px 14px">
      <span style="font-size:12px;color:var(--text2);font-weight:600">ثبت سریع:</span>
      <select id="att-quick-personnel" class="crm-form-input" style="width:auto"></select>
      <div style="position:relative">
        <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
        <input type="text" id="att-quick-date" class="crm-form-input" style="width:120px;padding-right:30px" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)">
      </div>
      <input type="time" id="att-quick-checkin" class="crm-form-input" style="width:auto">
      <input type="time" id="att-quick-checkout" class="crm-form-input" style="width:auto">
      <button class="crm-btn crm-btn-primary" onclick="attQuickSubmit()">ثبت</button>
    </div>

    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px" id="att-table"></table>
    </div>
    <div style="display:flex;justify-content:center;gap:8px;margin:14px 0" id="att-pagination"></div>

    <div style="margin-top:24px">
      <div class="crm-section-title" style="cursor:pointer" onclick="attToggleSection('weekly')">📊 خلاصه هفتگی <span id="att-weekly-arrow">▾</span></div>
      <div id="att-weekly-body"></div>
    </div>

    <div style="margin-top:24px">
      <div class="crm-section-title" style="cursor:pointer" onclick="attToggleSection('monthly')">🧑‍💼 کارکرد ماهانه پرسنل <span id="att-monthly-arrow">▾</span></div>
      <div id="att-monthly-body"></div>
    </div>

    <div class="crm-overlay" id="att-overlay" style="display:none" onclick="if(event.target.id==='att-overlay')attCloseModal()">
      <div class="crm-modal" id="att-modal-form">
        <div class="crm-modal-header">
          <span class="crm-modal-title" id="att-modal-title">ثبت حضور</span>
          <button class="crm-icon-btn" onclick="attCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="crm-modal-body">
          <input type="hidden" id="att-edit-id">
          <div class="crm-form-row"><label class="crm-form-label">پرسنل</label><select id="att-personnel" class="crm-form-input"></select></div>
          <div class="crm-form-row"><label class="crm-form-label">تاریخ</label>
            <div style="position:relative">
              <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
              <input type="text" id="att-date" class="crm-form-input" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)" style="padding-right:30px">
            </div>
          </div>
          <div class="crm-form-row-2">
            <div><label class="crm-form-label">ساعت ورود</label><input type="time" id="att-checkin" class="crm-form-input" oninput="attUpdateHoursPreview()"></div>
            <div><label class="crm-form-label">ساعت خروج</label><input type="time" id="att-checkout" class="crm-form-input" oninput="attUpdateHoursPreview()"></div>
          </div>
          <label class="crm-checkbox-row" style="margin-bottom:10px"><input type="checkbox" id="att-no-checkout" onchange="attToggleNoCheckout()"> هنوز خارج نشده</label>
          <div class="crm-form-row"><label class="crm-form-label">یادداشت</label><textarea id="att-note" class="crm-form-input" rows="2" placeholder="یادداشت (اختیاری)"></textarea></div>
          <div id="att-hours-preview" style="font-size:12px;color:var(--text2)"></div>
        </div>
        <div class="crm-modal-footer">
          <button class="crm-btn" onclick="attCloseModal()">انصراف</button>
          <button class="crm-btn crm-btn-primary" onclick="attSaveRecord()">ذخیره</button>
        </div>
      </div>
    </div>`;

  attPopulateFilterSelects();
  attRefreshData();
}

function attPopulateFilterSelects() {
  const monthSel=document.getElementById('att-filter-month');
  const months=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const curYear=new persianDate().format('YYYY');
  monthSel.innerHTML=months.map((m,i)=>{
    const val=curYear+'/'+String(i+1).padStart(2,'0');
    return `<option value="${val}">${m} ${curYear}</option>`;
  }).join('');
  monthSel.value=attendanceState.filter.month||getCurrentShamsiMonth();

  const persSel=document.getElementById('att-filter-personnel');
  persSel.innerHTML='<option value="">همه پرسنل</option>'+crmState.personnel.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  persSel.value=attendanceState.filter.personnelId||'';

  const quickSel=document.getElementById('att-quick-personnel');
  quickSel.innerHTML=crmState.personnel.filter(p=>p.active!==false).map(p=>`<option value="${p.id}">${p.name}</option>`).join('');

  const quickDate=document.getElementById('att-quick-date');
  if(quickDate && !quickDate.value) quickDate.value=attToday();
}

function attApplyFilter() {
  attendanceState.filter.month=document.getElementById('att-filter-month').value;
  attendanceState.filter.personnelId=document.getElementById('att-filter-personnel').value;
  attendanceState.page=1;
  attRefreshData();
}

function attExportExcel() {
  crmShowToast('warning','در حال آماده‌سازی...');
}

function attToggleSection(name) {
  attendanceState['collapsed_'+name] = !attendanceState['collapsed_'+name];
  if (name==='weekly') attRenderWeeklySummary(); else attRenderMonthlySummary();
}

function attRefreshData() {
  attRenderSummaryCards();
  attRenderTable();
  attRenderWeeklySummary();
  attRenderMonthlySummary();
}

function attRenderSummaryCards() {
  const month=attendanceState.filter.month||getCurrentShamsiMonth();
  const monthRecords=attRecordsForMonth(month);
  const totalHours=monthRecords.reduce((s,r)=>s+(r.totalHours||0),0);
  const daysWithRecords=new Set(monthRecords.map(r=>r.date)).size;
  const avgDaily=daysWithRecords?totalHours/daysWithRecords:0;
  const today=attToday();
  const presentToday=new Set(attendanceState.records.filter(r=>r.date===today).map(r=>r.personnelId)).size;

  const activePeople=crmState.personnel.filter(p=>p.active!==false);
  let absences=0;
  activePeople.forEach(p=>{
    const presentDays=new Set(monthRecords.filter(r=>r.personnelId===p.id).map(r=>r.date)).size;
    absences += Math.max(daysWithRecords - presentDays, 0);
  });

  document.getElementById('att-summary-cards').innerHTML=`
    <div class="crm-stat-card"><div class="crm-stat-label">مجموع ساعت کارکرد ماه</div><div class="crm-stat-val" style="color:var(--accent);font-size:18px">${formatHours(totalHours)}</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">میانگین ساعت روزانه</div><div class="crm-stat-val" style="color:#3b82f6;font-size:18px">${formatHours(avgDaily)}</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">پرسنل حاضر امروز</div><div class="crm-stat-val" style="color:var(--green)">${presentToday}</div></div>
    <div class="crm-stat-card"><div class="crm-stat-label">غیبت‌های این ماه</div><div class="crm-stat-val" style="color:var(--red)">${absences}</div></div>`;
}

function attRenderTable() {
  const recs=attFilteredRecords();
  const perPage=15;
  const totalPages=Math.max(1,Math.ceil(recs.length/perPage));
  if(!attendanceState.page) attendanceState.page=1;
  if(attendanceState.page>totalPages) attendanceState.page=totalPages;
  const start=(attendanceState.page-1)*perPage;
  const pageRecs=recs.slice(start,start+perPage);

  const table=document.getElementById('att-table');
  const th=l=>`<th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">${l}</th>`;
  table.innerHTML=`
    <thead><tr style="background:var(--s2);border-bottom:1px solid var(--b1)">
      ${th('ردیف')}${th('نام پرسنل')}${th('تاریخ')}${th('ساعت ورود')}${th('ساعت خروج')}${th('مجموع کارکرد')}${th('یادداشت')}${th('عملیات')}
    </tr></thead>
    <tbody>
      ${pageRecs.length?pageRecs.map((r,i)=>{
        const p=crmState.personnel.find(x=>x.id===r.personnelId);
        let hoursStyle='';
        if(r.totalHours!=null){
          if(r.totalHours>10) hoursStyle='color:var(--orange);font-weight:700';
          else if(r.totalHours<6) hoursStyle='color:var(--red);font-weight:700';
        }
        return`<tr class="${i%2===0?'bg-s1':'bg-s2'} hover:bg-b1">
          <td style="padding:8px">${start+i+1}</td>
          <td style="padding:8px">${p?p.name:'—'}</td>
          <td style="padding:8px">${formatShamsiDate(r.date)}</td>
          <td style="padding:8px;direction:ltr;text-align:right">${r.checkIn||'—'}</td>
          <td style="padding:8px;direction:ltr;text-align:right">${r.checkOut?r.checkOut:'<span style="color:var(--green)">— در محل</span>'}</td>
          <td style="padding:8px;${hoursStyle}">${r.totalHours!=null?formatHours(r.totalHours):'—'}</td>
          <td style="padding:8px;color:var(--text3)">${r.note||'—'}</td>
          <td style="padding:8px"><div style="display:flex;gap:4px">
            <button class="crm-icon-btn" onclick="attOpenEdit('${r.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
            <button class="crm-icon-btn crm-btn-danger" onclick="attDeleteRecord('${r.id}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>
          </div></td>
        </tr>`;
      }).join(''):'<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text3)">رکوردی یافت نشد</td></tr>'}
    </tbody>`;

  document.getElementById('att-pagination').innerHTML=`
    <button class="crm-btn" ${attendanceState.page<=1?'disabled':''} onclick="attChangePage(-1)">قبلی</button>
    <span style="font-size:12px;color:var(--text2);align-self:center">صفحه ${attendanceState.page} از ${totalPages}</span>
    <button class="crm-btn" ${attendanceState.page>=totalPages?'disabled':''} onclick="attChangePage(1)">بعدی</button>`;
}

function attChangePage(delta) {
  attendanceState.page=(attendanceState.page||1)+delta;
  attRenderTable();
}

function attRenderWeeklySummary() {
  const body=document.getElementById('att-weekly-body');
  const arrow=document.getElementById('att-weekly-arrow');
  if(!body) return;
  if(attendanceState.collapsed_weekly){ body.style.display='none'; if(arrow) arrow.textContent='▸'; return; }
  if(arrow) arrow.textContent='▾';
  body.style.display='';

  const weekDates=attCurrentWeekDates();
  const labels=['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
  const todayShamsi=attToday();
  const dayStats=weekDates.map((d,i)=>{
    const shamsi=new persianDate(d).format('YYYY/MM/DD');
    const dayRecords=attendanceState.records.filter(r=>r.date===shamsi);
    const present=new Set(dayRecords.map(r=>r.personnelId)).size;
    const hours=dayRecords.reduce((s,r)=>s+(r.totalHours||0),0);
    return {label:labels[i], shamsi, present, hours, isToday: shamsi===todayShamsi};
  });
  const maxHours=Math.max(...dayStats.map(d=>d.hours),1);

  body.innerHTML=`
    <div style="display:flex;align-items:flex-end;gap:14px;padding:16px 4px;background:var(--s2);border:1px solid var(--b1);border-radius:10px;margin-top:10px;overflow-x:auto">
      ${dayStats.map(d=>`
        <div style="flex:1;min-width:50px;display:flex;flex-direction:column;align-items:center;gap:6px">
          <div style="font-size:11px;font-weight:700;color:var(--text)">${formatHours(d.hours)}</div>
          <div style="width:100%;max-width:32px;height:80px;display:flex;align-items:flex-end;background:var(--b1);border-radius:4px 4px 0 0;overflow:hidden">
            <div style="width:100%;background:${d.isToday?'var(--accent)':'#3b82f6'};height:${Math.round(d.hours/maxHours*80)}px;transition:height .4s ease"></div>
          </div>
          <div style="font-size:10px;color:${d.isToday?'var(--accent)':'var(--text3)'};font-weight:${d.isToday?'700':'400'}">${d.label}</div>
          <div style="font-size:9px;color:var(--text3)">${formatShamsiDate(d.shamsi)}</div>
          <div style="font-size:10px;color:var(--text2)">${d.present} نفر</div>
        </div>`).join('')}
    </div>`;
}

function attRenderMonthlySummary() {
  const body=document.getElementById('att-monthly-body');
  const arrow=document.getElementById('att-monthly-arrow');
  if(!body) return;
  if(attendanceState.collapsed_monthly){ body.style.display='none'; if(arrow) arrow.textContent='▸'; return; }
  if(arrow) arrow.textContent='▾';
  body.style.display='';

  const month=attendanceState.filter.month||getCurrentShamsiMonth();
  const monthRecords=attRecordsForMonth(month);

  const rows=crmState.personnel.map(p=>{
    const recs=monthRecords.filter(r=>r.personnelId===p.id);
    const daysPresent=new Set(recs.map(r=>r.date)).size;
    const totalHours=recs.reduce((s,r)=>s+(r.totalHours||0),0);
    const avgHours=daysPresent?totalHours/daysPresent:0;
    const maxHours=recs.reduce((m,r)=>Math.max(m,r.totalHours||0),0);
    let status,statusColor;
    if(avgHours>=8){status='عالی';statusColor='green';}
    else if(avgHours>=6){status='خوب';statusColor='blue';}
    else if(avgHours>=4){status='متوسط';statusColor='amber';}
    else {status='ضعیف';statusColor='red';}
    return {p,daysPresent,totalHours,avgHours,maxHours,status,statusColor};
  });

  const th=l=>`<th style="padding:8px;text-align:right;font-size:11px;color:var(--text3)">${l}</th>`;
  body.innerHTML=`
    <div style="overflow-x:auto;margin-top:10px">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="background:var(--s2);border-bottom:1px solid var(--b1)">
          ${th('نام')}${th('روزهای حاضر')}${th('مجموع ساعت')}${th('میانگین روزانه')}${th('بیشترین ساعت')}${th('وضعیت')}
        </tr></thead>
        <tbody>
          ${rows.length?rows.map((r,i)=>`<tr class="${i%2===0?'bg-s1':'bg-s2'} hover:bg-b1">
            <td style="padding:8px">${r.p.name}</td>
            <td style="padding:8px">${r.daysPresent}</td>
            <td style="padding:8px">${formatHours(r.totalHours)}</td>
            <td style="padding:8px">${formatHours(r.avgHours)}</td>
            <td style="padding:8px">${formatHours(r.maxHours)}</td>
            <td style="padding:8px"><span class="crm-badge crm-badge-${r.statusColor}">${r.status}</span></td>
          </tr>`).join(''):'<tr><td colspan="6" style="padding:20px;text-align:center;color:var(--text3)">داده‌ای موجود نیست</td></tr>'}
        </tbody>
      </table>
    </div>`;
}

function attOpenModal() {
  document.getElementById('att-overlay').style.display='flex';
  document.getElementById('att-modal-title').textContent='ثبت حضور';
  document.getElementById('att-edit-id').value='';
  const persSel=document.getElementById('att-personnel');
  persSel.innerHTML=crmState.personnel.filter(p=>p.active!==false).map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  document.getElementById('att-date').value=attToday();
  document.getElementById('att-checkin').value='';
  document.getElementById('att-checkout').value='';
  document.getElementById('att-checkout').disabled=false;
  document.getElementById('att-no-checkout').checked=false;
  document.getElementById('att-note').value='';
  document.getElementById('att-hours-preview').textContent='';
}

function attCloseModal() {
  const el=document.getElementById('att-overlay');
  if(el) el.style.display='none';
}

function attToggleNoCheckout() {
  const noCheckout=document.getElementById('att-no-checkout').checked;
  const checkoutInput=document.getElementById('att-checkout');
  checkoutInput.disabled=noCheckout;
  if(noCheckout) checkoutInput.value='';
  attUpdateHoursPreview();
}

function attUpdateHoursPreview() {
  const checkIn=document.getElementById('att-checkin').value;
  const noCheckout=document.getElementById('att-no-checkout').checked;
  const checkOut=noCheckout?null:document.getElementById('att-checkout').value;
  const hours=calcHours(checkIn,checkOut);
  document.getElementById('att-hours-preview').textContent=hours!=null?`مجموع: ${formatHours(hours)}`:'';
}

function attOpenEdit(id) {
  const r=attendanceState.records.find(x=>x.id===id);
  if(!r) return;
  attOpenModal();
  document.getElementById('att-modal-title').textContent='ویرایش رکورد';
  document.getElementById('att-edit-id').value=id;
  document.getElementById('att-personnel').value=r.personnelId;
  document.getElementById('att-date').value=r.date;
  document.getElementById('att-checkin').value=r.checkIn||'';
  document.getElementById('att-checkout').value=r.checkOut||'';
  document.getElementById('att-no-checkout').checked=!r.checkOut;
  document.getElementById('att-checkout').disabled=!r.checkOut;
  attUpdateHoursPreview();
  document.getElementById('att-note').value=r.note||'';
}

function attSaveRecord() {
  const personnelId=document.getElementById('att-personnel').value;
  const date=document.getElementById('att-date').value;
  const checkIn=document.getElementById('att-checkin').value;
  if(!personnelId||!date||!checkIn){ alert('پرسنل، تاریخ و ساعت ورود الزامی است'); return; }
  const noCheckout=document.getElementById('att-no-checkout').checked;
  const checkOut=noCheckout?null:(document.getElementById('att-checkout').value||null);
  const note=document.getElementById('att-note').value.trim();
  const totalHours=calcHours(checkIn,checkOut);
  const id=document.getElementById('att-edit-id').value;

  const dup=attendanceState.records.find(r=>r.personnelId===personnelId&&r.date===date&&r.id!==id);
  if(dup && !id){
    crmConfirmToast('رکوردی برای این فرد در این تاریخ موجود است. ویرایش رکورد موجود؟', ()=>{
      dup.checkIn=checkIn; dup.checkOut=checkOut; dup.totalHours=totalHours; dup.note=note;
      saveAttendance();
      crmShowToast('success','رکورد بروزرسانی شد');
      attCloseModal();
      attRefreshData();
    });
    return;
  }

  if(id){
    const rec=attendanceState.records.find(r=>r.id===id);
    if(rec){ rec.personnelId=personnelId; rec.date=date; rec.checkIn=checkIn; rec.checkOut=checkOut; rec.totalHours=totalHours; rec.note=note; }
  } else {
    attendanceState.records.push({id:crmUid(),personnelId,date,checkIn,checkOut,totalHours,note});
  }
  saveAttendance();
  crmShowToast('success', id?'رکورد ویرایش شد':'حضور ثبت شد');
  attCloseModal();
  attRefreshData();
}

function attDeleteRecord(id) {
  crmConfirmToast('این رکورد حذف شود؟', ()=>{
    attendanceState.records=attendanceState.records.filter(r=>r.id!==id);
    saveAttendance();
    crmShowToast('warning','رکورد حذف شد');
    attRefreshData();
  });
}

function attQuickSubmit() {
  const personnelId=document.getElementById('att-quick-personnel').value;
  const date=document.getElementById('att-quick-date').value||attToday();
  const checkIn=document.getElementById('att-quick-checkin').value;
  const checkOut=document.getElementById('att-quick-checkout').value||null;
  if(!personnelId||!checkIn){ crmShowToast('error','پرسنل و ساعت ورود الزامی است'); return; }
  const totalHours=calcHours(checkIn,checkOut);
  const existing=attendanceState.records.find(r=>r.personnelId===personnelId&&r.date===date);
  if(existing){
    existing.checkIn=checkIn; existing.checkOut=checkOut; existing.totalHours=totalHours;
  } else {
    attendanceState.records.push({id:crmUid(),personnelId,date,checkIn,checkOut,totalHours,note:''});
  }
  saveAttendance();
  crmShowToast('success','حضور ثبت شد');
  document.getElementById('att-quick-checkin').value='';
  document.getElementById('att-quick-checkout').value='';
  attRefreshData();
}

function attGoToPersonAttendance(personnelId) {
  attEnsureLoaded();
  attendanceState.filter.personnelId = personnelId;
  attendanceState.filter.month = getCurrentShamsiMonth();
  attendanceState.page = 1;
  attendanceState._initialized = true;
  const navEl = document.querySelector('.nav-item[onclick*="attendance-page"]');
  if (navEl) setActive(navEl, 'حضور و غیاب', '', 'attendance-page');
  else showPage('attendance-page','حضور و غیاب');
  renderAttendancePage();
}

// Init attendance page when it becomes visible
const attendanceObserver = new MutationObserver(()=>{
  const page = document.getElementById('attendance-page');
  if(page && page.style.display !== 'none' && !attendanceState._initialized){
    attendanceState._initialized = true;
    if(!crmState.personnel.length) crmLoad();
    renderAttendancePage();
    crmShowToast('success','✓ سیستم حضور و غیاب فعال شد');
  }
});
const attendancePageEl = document.getElementById('attendance-page');
if(attendancePageEl) attendanceObserver.observe(attendancePageEl, {attributes:true, attributeFilter:['style']});

// Init CRM when crm-page becomes visible
const crmObserver = new MutationObserver(()=>{
  const page = document.getElementById('crm-page');
  if(page && page.style.display !== 'none' && !crmState._initialized){
    crmState._initialized = true;
    crmLoad();
    crmRenderDashboard();
    crmShowToast('success','✓ داشبورد CRM آپدیت شد',4000);
    crmShowToast('success','✓ آپدیت کامل CRM اعمال شد',4000);
  }
});
const crmPage = document.getElementById('crm-page');
if(crmPage) crmObserver.observe(crmPage, {attributes:true, attributeFilter:['style']});

console.log('%c✓ Phase 2 Complete — CRM Dashboard', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 3 Complete — CRM Projects', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 4 Complete — CRM Personnel', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 5 Complete — Kanban Board', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 6 Complete — Shamsi Datepicker', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 7 Complete — Attendance System', 'color:#0BBF53;font-weight:bold;font-size:14px');
console.log('%c✓ Phase 8 Complete — Full CRM Overhaul', 'color:#0BBF53;font-weight:bold;font-size:14px');
