@extends('layouts.admin')
@section('title', 'CRM — وطن استودیو')

@section('content')

    <div id="crm-page" class="hidden">
  <div class="flex items-center justify-between mb-5">
    <div>
      <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px]">CRM — مدیریت پروژه</h1>
      <div class="text-xs text-watan-text3 mt-[2px]">پروژه‌ها، تسک‌ها و گزارش بهره‌وری</div>
    </div>
    <div id="crm-topbar-actions" style="display:flex;gap:8px;align-items:center">
      <span style="
        font-size:12px;
        font-weight:800;
        padding:4px 10px;
        border-radius:6px;
        background:rgba(160,122,245,0.12);
        color:var(--accent);
        border:1px solid rgba(160,122,245,0.25);
        letter-spacing:0.5px;
        user-select:none;
        margin-right:8px;
      ">v3</span>
      <button class="crm-btn crm-btn-primary" onclick="crmOpenModal('project')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        پروژه جدید
      </button>
    </div>
  </div>

  <!-- TAB BAR -->
  <div class="flex items-center gap-1 mb-6 bg-s2 border border-b1 rounded-[10px] p-1">
    <button onclick="crmNavigate('dashboard')" id="crm-tab-dashboard" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-colors duration-150 bg-s1 border border-b1 text-watan-text">داشبورد</button>
    <button onclick="crmNavigate('projects')" id="crm-tab-projects" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-colors duration-150 text-watan-text2 hover:text-watan-text">پروژه‌ها</button>
    <button onclick="crmNavigate('kanban')" id="crm-tab-kanban" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-colors duration-150 text-watan-text2 hover:text-watan-text"><i class="fa-solid fa-chart-kanban"></i> وضعیت پروژه‌ها</button>
    <button onclick="crmNavigate('report')" id="crm-tab-report" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-colors duration-150 text-watan-text2 hover:text-watan-text">مرکز عملکرد</button>
    <button onclick="crmNavigate('personnel')" id="crm-tab-personnel" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-colors duration-150 text-watan-text2 hover:text-watan-text"><i class="fa-solid fa-users"></i> پرسنل</button>
  </div>

  <!-- DASHBOARD TAB -->
  <div id="crm-page-dashboard">
    <div class="crm-stat-grid" id="crm-dashboard-stats"></div>
    <div id="crm-dashboard-alerts"></div>
    <div id="crm-dashboard-chart"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" id="crm-dashboard-body"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px" id="crm-dashboard-team-row"></div>
    <div id="crm-dashboard-personnel-perf"></div>
  </div>

  <!-- PROJECTS TAB -->
  <div id="crm-page-projects" style="display:none">
    <div style="display:flex;gap:8px;margin-bottom:16px;align-items:center">
      <button class="crm-btn" id="crm-btn-back" onclick="crmBackToProjects()" style="display:none">
        ← بازگشت به پروژه‌ها
      </button>
      <button class="crm-btn crm-btn-primary" id="crm-btn-add-task" onclick="crmOpenModal('task')" style="display:none">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        تسک جدید
      </button>
    </div>
    <div id="crm-projects-content"></div>
  </div>

  <!-- KANBAN TAB -->
  <div id="crm-page-kanban" style="display:none">
    <h2 style="font-size:15px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;margin-bottom:14px"><i class="fa-solid fa-chart-kanban"></i> وضعیت پروژه‌ها</h2>
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px">
      <input type="text" id="crm-kanban-search" oninput="crmRenderKanban()" placeholder="جستجوی پروژه..." style="font-family:'Vazirmatn',sans-serif;font-size:12px;padding:6px 12px;background:var(--s2);border:1px solid var(--b1);border-radius:8px;color:var(--text);outline:none;direction:rtl;flex:1;min-width:160px">
      <select id="crm-kanban-manager-filter" onchange="crmRenderKanban()" style="font-family:'Vazirmatn',sans-serif;font-size:12px;padding:6px 12px;background:var(--s2);border:1px solid var(--b1);border-radius:8px;color:var(--text);outline:none;direction:rtl">
        <option value="">مدیر پروژه (همه)</option>
      </select>
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)">
        <input type="checkbox" id="crm-kanban-no-manager" onchange="crmRenderKanban()"> بدون مدیر
      </label>
      <button class="crm-btn" onclick="crmResetKanbanFilter()">× پاک کردن فیلتر</button>
    </div>
    <div class="crm-kanban" id="crm-kanban-board"></div>
  </div>

  <!-- REPORT TAB -->
  <div id="crm-page-report" style="display:none">
    <div id="crm-report-content"></div>
  </div>

  <!-- PERSONNEL TAB -->
  <div id="crm-page-personnel" style="display:none">
    <div id="crm-personnel-list-view">
      <div style="display:flex;justify-content:flex-start;margin-bottom:16px">
        <button class="crm-btn crm-btn-primary" onclick="crmOpenModal('personnel')">+ افزودن نیرو</button>
      </div>
      <div class="crm-stat-grid" id="crm-personnel-stats"></div>
      <div style="margin-bottom:16px">
        <div style="position:relative">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:12px"></i>
          <input type="text" id="crm-personnel-search" class="bg-s1 border border-b1 rounded-[10px] text-[13px] text-watan-text" style="width:100%;height:38px;padding:0 38px 0 14px;outline:none;font-family:'Vazirmatn',sans-serif" placeholder="جستجوی پرسنل..." oninput="crmRenderPersonnel()">
        </div>
        <div id="crm-personnel-search-count" style="font-size:11px;color:var(--text3);margin-top:6px"></div>
      </div>
      <div class="crm-personnel-grid" id="crm-personnel-list"></div>
    </div>
    <div id="crm-personnel-profile" style="display:none"></div>
  </div>

  <!-- MODALS -->
  <div class="crm-overlay" id="crm-overlay" style="display:none" onclick="if(event.target.id==='crm-overlay')crmCloseModal()">
    <div class="crm-modal" id="crm-modal-project" style="display:none">
      <div class="crm-modal-header">
        <span class="crm-modal-title" id="crm-modal-project-title">پروژه جدید</span>
        <button class="crm-icon-btn" onclick="crmCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="crm-modal-body">
        <input type="hidden" id="crm-edit-project-id">
        <div class="crm-form-row"><label class="crm-form-label">نام پروژه</label><input type="text" id="crm-proj-name" class="crm-form-input" placeholder="مثال: اپلیکیشن موبایل"></div>
        <div class="crm-form-row-2">
          <div><label class="crm-form-label">ایموجی</label><input type="text" id="crm-proj-emoji" class="crm-form-input" placeholder="📁" maxlength="4" style="text-align:center;font-size:20px"></div>
          <div><label class="crm-form-label">وضعیت پروژه</label><select id="crm-proj-status" class="crm-form-input"><option value="planning">برنامه‌ریزی</option><option value="waiting">در انتظار</option><option value="inprogress">در حال انجام</option><option value="done">تکمیل شده</option><option value="stopped">متوقف شده</option></select></div>
        </div>
        <div class="crm-form-row"><label class="crm-form-label">مدیر پروژه</label><select id="crm-proj-manager" class="crm-form-input"><option value="">انتخاب مدیر پروژه</option></select></div>
        <div class="crm-form-row-2">
          <div><label class="crm-form-label">تاریخ شروع</label>
            <div style="position:relative">
              <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
              <input type="text" id="crm-proj-start" class="crm-form-input" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)" style="padding-right:30px">
            </div>
          </div>
          <div><label class="crm-form-label">تاریخ پایان</label>
            <div style="position:relative">
              <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
              <input type="text" id="crm-proj-end" class="crm-form-input" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)" style="padding-right:30px">
            </div>
          </div>
        </div>
        <div class="crm-form-row"><label class="crm-form-label">توضیحات</label><textarea id="crm-proj-desc" class="crm-form-input" rows="3" placeholder="توضیح مختصری..."></textarea></div>
        <div class="crm-form-row"><label class="crm-form-label">تاریخ مهلت</label>
          <div style="position:relative">
            <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
            <input type="text" id="crm-proj-deadline" class="crm-form-input" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)" style="padding-right:30px">
          </div>
        </div>
        <div class="crm-sep"></div>
        <div class="crm-form-row">
          <label class="crm-form-label">اعضای تیم</label>
          <div class="crm-checkbox-list" id="crm-proj-team-list"></div>
        </div>
        <div class="crm-sep"></div>
        <div class="crm-form-row">
          <label class="crm-form-label" style="cursor:pointer;display:flex;align-items:center;gap:6px" onclick="crmToggleInitialTasksSection()">
            <span id="crm-initial-tasks-arrow">▸</span> تسک‌های اولیه
          </label>
          <div id="crm-initial-tasks-body" style="display:none">
            <div style="display:flex;gap:6px;margin-bottom:8px">
              <input type="text" id="crm-initial-task-title" class="crm-form-input" placeholder="عنوان تسک...">
              <select id="crm-initial-task-assignee" class="crm-form-input" style="max-width:140px"><option value="">مسئول</option></select>
              <button class="crm-btn" style="white-space:nowrap" onclick="crmAddInitialTask()">+ افزودن</button>
            </div>
            <div id="crm-initial-tasks-list"></div>
          </div>
        </div>
      </div>
      <div class="crm-modal-footer">
        <button class="crm-btn" onclick="crmCloseModal()">انصراف</button>
        <button class="crm-btn crm-btn-primary" onclick="crmSaveProject()">ذخیره</button>
      </div>
    </div>
    <div class="crm-modal" id="crm-modal-task" style="display:none">
      <div class="crm-modal-header">
        <span class="crm-modal-title" id="crm-modal-task-title">تسک جدید</span>
        <button class="crm-icon-btn" onclick="crmCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="crm-modal-body">
        <input type="hidden" id="crm-edit-task-id">
        <div class="crm-form-row"><label class="crm-form-label">عنوان تسک</label><input type="text" id="crm-task-title" class="crm-form-input" placeholder="چه کاری باید انجام شود؟"></div>
        <div class="crm-form-row"><label class="crm-form-label">توضیحات</label><textarea id="crm-task-desc" class="crm-form-input" rows="2" placeholder="جزئیات بیشتر..."></textarea></div>
        <div class="crm-form-row-2">
          <div><label class="crm-form-label">اولویت</label><select id="crm-task-priority" class="crm-form-input"><option value="urgent">🔴 فوری</option><option value="high">🟠 بالا</option><option value="medium" selected>🔵 متوسط</option><option value="low">⚪ کم</option></select></div>
          <div><label class="crm-form-label">وضعیت</label><select id="crm-task-status" class="crm-form-input"><option value="backlog">انتظار</option><option value="todo">برنامه‌ریزی</option><option value="inprogress">در حال انجام</option><option value="done">انجام‌شده</option></select></div>
        </div>
        <div class="crm-form-row"><label class="crm-form-label">مسئول تسک</label><select id="crm-task-assignee" class="crm-form-input"><option value="">انتخاب مسئول</option></select></div>
        <div class="crm-form-row"><label class="crm-form-label">مهلت تسک</label>
          <div style="position:relative">
            <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px;z-index:1"></i>
            <input type="text" id="crm-task-deadline" class="crm-form-input" placeholder="1404/01/01" readonly onclick="openShamsiPicker(this)" style="padding-right:32px;cursor:pointer">
          </div>
        </div>
        <div class="crm-sep"></div>
        <div class="crm-form-row">
          <label class="crm-form-label">میکروتسک‌ها</label>
          <div id="crm-microtask-list" style="margin-bottom:8px"></div>
          <div style="display:flex;gap:8px">
            <input type="text" id="crm-micro-input" class="crm-form-input" placeholder="میکروتسک جدید..." onkeydown="if(event.key==='Enter')crmAddMicrotaskInModal()">
            <button class="crm-btn" onclick="crmAddMicrotaskInModal()" style="white-space:nowrap">+ افزودن</button>
          </div>
        </div>
      </div>
      <div class="crm-modal-footer">
        <button class="crm-btn" onclick="crmCloseModal()">انصراف</button>
        <button class="crm-btn crm-btn-primary" onclick="crmSaveTask()">ذخیره</button>
      </div>
    </div>
    <div class="crm-modal" id="crm-modal-personnel" style="display:none">
      <div class="crm-modal-header">
        <span class="crm-modal-title" id="crm-modal-personnel-title">افزودن نیرو</span>
        <button class="crm-icon-btn" onclick="crmCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="crm-modal-body">
        <input type="hidden" id="crm-edit-personnel-id">
        <div class="crm-form-row"><label class="crm-form-label">نام و نام خانوادگی</label><input type="text" id="crm-pers-name" class="crm-form-input" placeholder="مثال: علی محمدی"></div>
        <div class="crm-form-row"><label class="crm-form-label">شماره موبایل</label><input type="text" id="crm-pers-mobile" class="crm-form-input" placeholder="09xxxxxxxxx" style="direction:ltr;text-align:right"></div>
        <div class="crm-form-row">
          <label class="crm-form-label">نقش</label>
          <select id="crm-pers-role" class="crm-form-input">
            <option value="مدیر کل">مدیر کل</option>
            <option value="مدیر پیگیری">مدیر پیگیری</option>
            <option value="برنامه‌نویس">برنامه‌نویس</option>
            <option value="گرافیست">گرافیست</option>
            <option value="تولیدکننده محتوا">تولیدکننده محتوا</option>
            <option value="سایر">سایر</option>
          </select>
        </div>
        <div class="crm-form-row-2">
          <div><label class="crm-form-label">📧 ایمیل</label><input type="email" id="crm-pers-email" class="crm-form-input" placeholder="example@mail.com" style="direction:ltr;text-align:right"></div>
          <div><label class="crm-form-label">📅 تاریخ عضویت</label>
            <div style="position:relative">
              <i class="fa-solid fa-calendar-days" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none;font-size:12px"></i>
              <input type="text" id="crm-pers-join" class="crm-form-input" placeholder="1404/01/15" readonly onclick="openShamsiPicker(this)" style="padding-right:30px">
            </div>
          </div>
        </div>
        <div class="crm-form-row">
          <label class="crm-form-label">دسترسی به پروژه‌ها</label>
          <div class="crm-checkbox-list" id="crm-pers-projects-list"></div>
        </div>
      </div>
      <div class="crm-modal-footer">
        <button class="crm-btn" onclick="crmCloseModal()">انصراف</button>
        <button class="crm-btn crm-btn-primary" onclick="crmSavePersonnel()">ذخیره</button>
      </div>
    </div>
    <div class="crm-modal" id="crm-modal-assign-project" style="display:none">
      <div class="crm-modal-header">
        <span class="crm-modal-title" id="crm-modal-assign-project-title">واگذاری پروژه</span>
        <button class="crm-icon-btn" onclick="crmCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="crm-modal-body">
        <input type="hidden" id="crm-assign-project-personnel-id">
        <div class="crm-checkbox-list" id="crm-assign-project-list" style="max-height:240px"></div>
      </div>
      <div class="crm-modal-footer">
        <button class="crm-btn" onclick="crmCloseModal()">انصراف</button>
        <button class="crm-btn crm-btn-primary" onclick="crmSaveAssignProject()">ذخیره</button>
      </div>
    </div>
    <div class="crm-modal" id="crm-modal-assign-task" style="display:none">
      <div class="crm-modal-header">
        <span class="crm-modal-title" id="crm-modal-assign-task-title">واگذاری تسک</span>
        <button class="crm-icon-btn" onclick="crmCloseModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="crm-modal-body">
        <input type="hidden" id="crm-assign-task-personnel-id">
        <div class="crm-form-row">
          <label class="crm-form-label">انتخاب پروژه</label>
          <select id="crm-assign-task-project" class="crm-form-input" onchange="crmRenderAssignTaskList()"><option value="">انتخاب پروژه</option></select>
        </div>
        <div class="crm-checkbox-list" id="crm-assign-task-list" style="max-height:200px"></div>
      </div>
      <div class="crm-modal-footer">
        <button class="crm-btn" onclick="crmCloseModal()">انصراف</button>
        <button class="crm-btn crm-btn-primary" onclick="crmSaveAssignTask()">ذخیره</button>
      </div>
    </div>
  </div>
</div>

  {{-- Attendance Page --}}
  <div id="attendance-page" style="display:none" class="p-6">
    {{-- content rendered by JS --}}
  </div>

  {{-- Placeholder --}}
  <div id="placeholder-page" class="hidden">
    <div class="flex flex-col items-center justify-center min-h-[400px] text-center gap-3">
      <div class="text-[42px] opacity-20"><i class="fa-solid fa-layer-group"></i></div>
      <div class="text-[22px] font-extrabold text-accent tracking-[-0.4px]" id="placeholder-section-name"></div>
      <div class="text-[13px] text-watan-text3">این بخش در مراحل بعدی طراحی می‌شه</div>
    </div>
  </div>

@endsection

@section('scripts')
<script src="{{ asset('admin/js/crm.js') }}"></script>
@endsection
