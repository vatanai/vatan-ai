// ============================================================================
//  crm-api.js — پل اتصال CRM به دیتابیس (REST)
//  این فایل بعد از کد فعلی CRM لود می‌شود و توابع مبتنی‌بر localStorage را با
//  نسخه‌ی متصل به دیتابیس (API واقعی) جایگزین (override) می‌کند.
//  الگوی امن: هر تغییر → فراخوانی API → بارگذاری مجدد از دیتابیس → رندر مجدد.
// ============================================================================
(function () {
  'use strict';

  const BASE = '/admin/crm-api';
  const csrf = () => (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

  // ── لایه‌ی درخواست ──────────────────────────────────────────────────────────
  async function apiRequest(method, path, body) {
    const opts = {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
      },
      credentials: 'same-origin',
    };
    if (body !== undefined) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(BASE + path, opts);
    let data = null;
    try { data = await res.json(); } catch (e) { /* ممکن است بدنه خالی باشد */ }
    if (!res.ok) {
      const msg = (data && (data.message || (data.errors && Object.values(data.errors)[0]?.[0]))) || ('خطای سرور (' + res.status + ')');
      throw new Error(msg);
    }
    return data;
  }

  const crmApi = {
    // Personnel
    getPersonnel:      ()            => apiRequest('GET',    '/personnel'),
    createPersonnel:   (p)           => apiRequest('POST',   '/personnel', p),
    updatePersonnel:   (id, p)       => apiRequest('PUT',    '/personnel/' + id, p),
    deletePersonnel:   (id)          => apiRequest('DELETE', '/personnel/' + id),
    assignProjects:    (id, ids)     => apiRequest('POST',   '/personnel/' + id + '/assign-projects', { project_ids: ids }),
    // Projects
    getProjects:       ()            => apiRequest('GET',    '/projects'),
    createProject:     (p)           => apiRequest('POST',   '/projects', p),
    updateProject:     (id, p)       => apiRequest('PUT',    '/projects/' + id, p),
    deleteProject:     (id)          => apiRequest('DELETE', '/projects/' + id),
    // Tasks
    getTasks:          ()            => apiRequest('GET',    '/tasks'),
    createTask:        (t)           => apiRequest('POST',   '/tasks', t),
    updateTask:        (id, t)       => apiRequest('PUT',    '/tasks/' + id, t),
    deleteTask:        (id)          => apiRequest('DELETE', '/tasks/' + id),
    toggleTask:        (id)          => apiRequest('POST',   '/tasks/' + id + '/toggle'),
    toggleMicro:       (tid, mid)    => apiRequest('POST',   '/tasks/' + tid + '/microtasks/' + mid + '/toggle'),
    // Activity / Attendance
    getActivity:       ()            => apiRequest('GET',    '/activity'),
    getAttendance:     ()            => apiRequest('GET',    '/attendance'),
    createAttendance:  (r)           => apiRequest('POST',   '/attendance', r),
  };
  window.crmApi = crmApi;

  // ── آداپترها: تبدیل خروجی API به شکل مورد انتظار فرانت ──────────────────────
  function adaptProject(p) {
    const memberIds = (p.member_ids || []).map(String);
    return {
      id: p.id, name: p.name, emoji: p.emoji, status: p.status,
      desc: p.description || '', deadline: p.deadline || '',
      createdAt: p.created_at || Date.now(),
      managerId: p.manager_id || null,
      memberIds: memberIds, teamIds: memberIds,
      startDate: p.start_date || '', endDate: p.end_date || '',
      archived: !!p.archived, progress: p.progress || 0,
    };
  }
  function adaptTask(t) {
    return {
      id: t.id, projectId: t.project_id, title: t.title,
      desc: t.description || '', priority: t.priority, status: t.status,
      done: !!t.done, deadline: t.deadline || '',
      createdAt: t.created_at || Date.now(),
      assigneeId: t.assignee_id || null,
      startDate: t.start_date || '',
      completedAt: t.completed_at || null,
      microtasks: (t.microtasks || []).map(m => ({ id: m.id, text: m.text, done: !!m.done })),
      comments: t.comments || [],
    };
  }
  function adaptPersonnel(p) {
    return {
      id: p.id, name: p.name, mobile: p.mobile || '', role: p.role,
      email: p.email || '', joinDate: p.join_date || '',
      active: p.active !== false, createdAt: p.created_at || Date.now(),
      projectIds: [], // پایین‌تر از روی اعضای پروژه‌ها پر می‌شود
    };
  }

  // ── بارگذاری کل دیتا از دیتابیس ──────────────────────────────────────────────
  async function crmReloadData() {
    const [personnel, projects, tasks, activity] = await Promise.all([
      crmApi.getPersonnel(), crmApi.getProjects(), crmApi.getTasks(), crmApi.getActivity(),
    ]);
    crmState.projects   = (projects || []).map(adaptProject);
    crmState.tasks      = (tasks || []).map(adaptTask);
    crmState.personnel  = (personnel || []).map(adaptPersonnel);
    crmState.activityLog = (activity || []);

    // پر کردن projectIds هر پرسنل از روی اعضای پروژه‌ها
    crmState.personnel.forEach(per => {
      per.projectIds = crmState.projects.filter(pr => (pr.memberIds || []).includes(per.id)).map(pr => pr.id);
    });
  }
  window.crmReloadData = crmReloadData;

  // اجرای امن یک عملیات نوشتنی: صبر برای API، سپس ری‌لود و رندر
  async function runMutation(fn, okMsg, afterRender) {
    try {
      await fn();
      await crmReloadData();
      if (typeof afterRender === 'function') afterRender();
      else if (typeof crmRenderPage === 'function') crmRenderPage();
      if (okMsg && typeof crmShowToast === 'function') crmShowToast('success', okMsg);
    } catch (e) {
      if (typeof crmShowToast === 'function') crmShowToast('error', e.message || 'خطا در ارتباط با سرور');
      console.error('[CRM]', e);
    }
  }

  // ── override: لود/سیو ───────────────────────────────────────────────────────
  window.crmSeedData = function () { /* داده از دیتابیس می‌آید */ };
  window.crmSave = function () { /* منبع حقیقت دیتابیس است */ };

  window.crmLoad = async function () {
    try {
      await crmReloadData();
      if (typeof crmRenderPage === 'function') crmRenderPage();
    } catch (e) {
      if (typeof crmShowToast === 'function') crmShowToast('error', 'خطا در بارگذاری اطلاعات CRM');
      console.error('[CRM] load failed', e);
    }
  };

  // ── override: پروژه ─────────────────────────────────────────────────────────
  window.crmSaveProject = function () {
    const name = document.getElementById('crm-proj-name').value.trim();
    if (!name) { alert('نام پروژه را وارد کنید'); return; }
    const id = document.getElementById('crm-edit-project-id').value;
    const memberIds = [...document.querySelectorAll('#crm-proj-team-list input:checked')].map(c => c.value);
    const payload = {
      name,
      emoji: document.getElementById('crm-proj-emoji').value || '📁',
      status: document.getElementById('crm-proj-status').value,
      description: document.getElementById('crm-proj-desc').value,
      deadline: document.getElementById('crm-proj-deadline').value || null,
      start_date: document.getElementById('crm-proj-start').value || null,
      end_date: document.getElementById('crm-proj-end').value || null,
      manager_id: document.getElementById('crm-proj-manager').value || null,
      member_ids: memberIds,
    };
    const initialTasks = (crmState.modalInitialTasks || []).slice();
    crmState.modalInitialTasks = [];
    runMutation(async () => {
      let project;
      if (id) project = await crmApi.updateProject(id, payload);
      else    project = await crmApi.createProject(payload);
      const pid = project.id;
      for (const t of initialTasks) {
        await crmApi.createTask({
          project_id: pid, title: t.title, description: '',
          priority: 'medium', status: 'backlog',
          assignee_id: t.assigneeId || null,
        });
      }
    }, id ? 'پروژه ویرایش شد' : 'پروژه ایجاد شد', () => {
      if (typeof crmCloseModal === 'function') crmCloseModal();
      if (typeof crmRenderDashboard === 'function') crmRenderDashboard();
      if (crmState.currentTab === 'projects' && typeof crmRenderProjects === 'function') crmRenderProjects();
    });
  };

  window.crmDeleteProject = function (id) {
    const doIt = () => runMutation(() => crmApi.deleteProject(id), 'پروژه حذف شد', () => {
      if (crmState.selectedProjectId === id) crmState.selectedProjectId = null;
      if (typeof crmRenderProjects === 'function') crmRenderProjects();
    });
    if (typeof crmConfirmToast === 'function') crmConfirmToast('پروژه و همه تسک‌هایش حذف شود؟', doIt);
    else if (confirm('پروژه و همه تسک‌هایش حذف شود؟')) doIt();
  };

  window.crmToggleArchiveProject = function (id) {
    const p = crmState.projects.find(x => x.id === id);
    if (!p) return;
    runMutation(() => crmApi.updateProject(id, { archived: !p.archived }), null,
      () => { if (typeof crmRenderProjects === 'function') crmRenderProjects(); });
  };

  window.crmKanbanDrop = function (ev, newStatus) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('crm-kanban-col-over');
    const projectId = ev.dataTransfer.getData('text/plain');
    const p = crmState.projects.find(x => x.id === projectId);
    if (!p || p.status === newStatus) return;
    runMutation(() => crmApi.updateProject(projectId, { status: newStatus }), null,
      () => { if (typeof crmRenderKanban === 'function') crmRenderKanban(); });
  };

  // ── override: تسک ───────────────────────────────────────────────────────────
  window.crmSaveTask = function () {
    const title = document.getElementById('crm-task-title').value.trim();
    if (!title) { alert('عنوان تسک را وارد کنید'); return; }
    const id = document.getElementById('crm-edit-task-id').value;
    const status = document.getElementById('crm-task-status').value;
    const micros = (crmState.modalMicrotasks || []).map(m => ({ text: m.text, done: !!m.done }));
    const base = {
      title,
      description: document.getElementById('crm-task-desc').value,
      priority: document.getElementById('crm-task-priority').value,
      status,
      deadline: document.getElementById('crm-task-deadline').value || null,
      assignee_id: document.getElementById('crm-task-assignee').value || null,
      microtasks: micros,
    };
    runMutation(async () => {
      if (id) {
        await crmApi.updateTask(id, base);
      } else {
        const pid = crmState.selectedProjectId || (crmState.projects[0] && crmState.projects[0].id);
        if (!pid) throw new Error('ابتدا یک پروژه بسازید');
        await crmApi.createTask({ ...base, project_id: pid });
      }
    }, id ? 'تسک ویرایش شد' : 'تسک ایجاد شد', () => {
      if (typeof crmCloseModal === 'function') crmCloseModal();
      if (typeof crmRenderPage === 'function') crmRenderPage();
    });
  };

  window.crmToggleTask = function (id) {
    runMutation(() => crmApi.toggleTask(id), null,
      () => { if (typeof crmRenderPage === 'function') crmRenderPage(); });
  };

  window.crmToggleMicro = function (taskId, microId) {
    runMutation(() => crmApi.toggleMicro(taskId, microId), null,
      () => { if (typeof crmRenderPage === 'function') crmRenderPage(); });
  };

  window.crmDeleteTask = function (id) {
    const doIt = () => runMutation(() => crmApi.deleteTask(id), 'تسک حذف شد',
      () => { if (typeof crmRenderPage === 'function') crmRenderPage(); });
    if (typeof crmConfirmToast === 'function') crmConfirmToast('حذف این تسک؟', doIt);
    else if (confirm('حذف این تسک؟')) doIt();
  };

  // ── override: پرسنل ─────────────────────────────────────────────────────────
  window.crmSavePersonnel = function () {
    const name = document.getElementById('crm-pers-name').value.trim();
    if (!name) { alert('نام را وارد کنید'); return; }
    const id = document.getElementById('crm-edit-personnel-id').value;
    const projectIds = [...document.querySelectorAll('#crm-pers-projects-list input:checked')].map(c => c.value);
    const payload = {
      name,
      mobile: document.getElementById('crm-pers-mobile').value.trim() || null,
      role: document.getElementById('crm-pers-role').value,
      email: document.getElementById('crm-pers-email').value.trim() || null,
      join_date: document.getElementById('crm-pers-join').value.trim() || null,
    };
    runMutation(async () => {
      let person;
      if (id) person = await crmApi.updatePersonnel(id, payload);
      else    person = await crmApi.createPersonnel(payload);
      await crmApi.assignProjects(person.id, projectIds);
    }, id ? 'نیرو ویرایش شد' : 'نیرو اضافه شد', () => {
      if (typeof crmCloseModal === 'function') crmCloseModal();
      if (typeof crmRenderPersonnel === 'function') crmRenderPersonnel();
    });
  };

  window.crmDeletePersonnel = function (id) {
    const doIt = () => runMutation(() => crmApi.deletePersonnel(id), 'نیرو حذف شد',
      () => { if (typeof crmRenderPersonnel === 'function') crmRenderPersonnel(); });
    if (typeof crmConfirmToast === 'function') crmConfirmToast('این نیرو حذف شود؟', doIt);
    else if (confirm('این نیرو حذف شود؟')) doIt();
  };

  window.crmDeactivatePersonnel = function (id) {
    const p = crmState.personnel.find(x => x.id === id);
    if (!p) return;
    const activate = (p.active === false);
    const apply = () => runMutation(() => crmApi.updatePersonnel(id, { active: activate }),
      activate ? 'نیرو فعال شد' : 'نیرو غیرفعال شد',
      () => { if (typeof crmRenderPersonnel === 'function') crmRenderPersonnel(); });
    if (activate) apply();
    else if (typeof crmConfirmToast === 'function') crmConfirmToast('آیا مطمئنید؟', apply);
    else if (confirm('آیا مطمئنید؟')) apply();
  };

  window.crmSaveAssignProject = function () {
    const personnelId = document.getElementById('crm-assign-project-personnel-id').value;
    const checkedIds = [...document.querySelectorAll('#crm-assign-project-list input:checked')].map(c => c.value);
    runMutation(() => crmApi.assignProjects(personnelId, checkedIds), 'پروژه‌ها بروزرسانی شد', () => {
      if (typeof crmCloseModal === 'function') crmCloseModal();
      if (typeof crmRenderPersonnel === 'function') crmRenderPersonnel();
    });
  };

  window.crmSaveAssignTask = function () {
    const personnelId = document.getElementById('crm-assign-task-personnel-id').value;
    const pid = document.getElementById('crm-assign-task-project').value;
    if (!pid) { if (typeof crmCloseModal === 'function') crmCloseModal(); return; }
    const checkedIds = [...document.querySelectorAll('#crm-assign-task-list input:checked')].map(c => c.value);
    const tasksInProject = crmState.tasks.filter(t => t.projectId === pid);
    runMutation(async () => {
      for (const t of tasksInProject) {
        const shouldAssign = checkedIds.includes(t.id);
        if (shouldAssign && t.assigneeId !== personnelId) {
          await crmApi.updateTask(t.id, { assignee_id: personnelId });
        } else if (!shouldAssign && t.assigneeId === personnelId) {
          await crmApi.updateTask(t.id, { assignee_id: null });
        }
      }
    }, 'تسک‌ها واگذار شد', () => {
      if (typeof crmCloseModal === 'function') crmCloseModal();
      if (typeof crmRenderPersonnel === 'function') crmRenderPersonnel();
    });
  };

  // ── override: حضور و غیاب (خواندن از دیتابیس) ────────────────────────────────
  window.seedAttendance = function () { attendanceState.records = []; };
  window.saveAttendance = function () { /* از طریق API ذخیره می‌شود */ };
  window.loadAttendance = async function () {
    try {
      const records = await crmApi.getAttendance();
      attendanceState.records = (records || []).map(r => ({
        id: r.id, personnelId: r.personnelId, date: r.date,
        checkIn: r.checkIn, checkOut: r.checkOut,
        totalHours: r.totalHours, note: r.note || '',
      }));
    } catch (e) {
      attendanceState.records = [];
      console.error('[CRM] attendance load failed', e);
    }
    attendanceState._loaded = true;
    if (typeof renderAttendancePage === 'function') renderAttendancePage();
  };

  console.log('%c✓ CRM متصل به دیتابیس شد (REST API)', 'color:#16594f;font-weight:bold');
})();
