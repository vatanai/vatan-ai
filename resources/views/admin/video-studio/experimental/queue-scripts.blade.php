<script>
(() => {
  const form = document.getElementById('v2-form');
  const list = document.getElementById('v2-jobs-list');
  const pagination = document.getElementById('v2-jobs-pagination');
  const liveStatus = document.getElementById('v2-jobs-live-status');
  const searchInput = document.getElementById('v2-jobs-search');
  const searchClear = document.getElementById('v2-jobs-search-clear');
  const resultCount = document.getElementById('v2-jobs-result-count');
  const statNodes = Object.fromEntries([...document.querySelectorAll('[data-v2-job-stat]')].map(node => [node.dataset.v2JobStat, node]));
  if (!form || !list) return;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('[name="_token"]')?.value || '';
  const esc = value => String(value ?? '');
  const statusLabel = status => ({queued:'در صف',pending:'در انتظار',processing:'در حال ساخت',completed:'ساخته شد',failed:'خطا',cancelled:'لغو شد'})[status] || status || 'نامشخص';
  const applySettings = settings => {
    Object.entries(settings || {}).forEach(([name, value]) => {
      const selector = `[name="${CSS.escape(name)}"],[name="${CSS.escape(name)}[]"]`;
      [...form.querySelectorAll(selector)].forEach(element => {
        if (element.type === 'checkbox') element.checked = Array.isArray(value) ? value.map(String).includes(String(element.value)) : Boolean(value);
        else if (element.type === 'radio') element.checked = String(value) === String(element.value);
        else if (element.type !== 'file') element.value = Array.isArray(value) ? value[0] || '' : value ?? '';
      });
    });
    const product = document.getElementById('v2-product');
    const productOption = [...document.querySelectorAll('[data-v2-product-option]')].find(option => String(option.dataset.v2ProductId) === String(settings?.product_id));
    if (productOption && product) {
      product.value = productOption.dataset.v2ProductId;
      const label = document.getElementById('v2-product-picked-label');
      if (label) label.textContent = productOption.dataset.v2ProductName || productOption.textContent.trim();
      product.dispatchEvent(new Event('change', { bubbles: true }));
    }
    document.getElementById('v2-parent-job-id').value = settings?.parent_job_id || '';
    document.getElementById('v2-version').value = settings?.version || 1;
    document.querySelector('[data-v2-step="1"]')?.click();
    form.dispatchEvent(new CustomEvent('v2:settings-applied'));
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };
  const makeButton = (label, action, settings) => {
    const button = document.createElement('button'); button.type = 'button'; button.className = 'v2-mini-btn'; button.textContent = label; button.dataset[action] = JSON.stringify(settings || {}); return button;
  };
  const render = payload => {
    list.replaceChildren();
    const rows = Array.isArray(payload?.data) ? payload.data : [];
    if (!rows.length) { const empty = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 5; cell.className = 'v2-note'; cell.textContent = 'هنوز خروجی‌ای ثبت نشده است.'; empty.appendChild(cell); list.appendChild(empty); }
    rows.forEach(job => {
      const row = document.createElement('tr');
      const codeCell = document.createElement('td'); const code = document.createElement('span'); code.className = 'v2-job-code'; code.textContent = esc(job.code); codeCell.appendChild(code);
      const productCell = document.createElement('td'); const copy = document.createElement('span'); copy.className = 'v2-job-copy'; copy.textContent = esc(job.product); productCell.appendChild(copy);
      const versionCell = document.createElement('td'); versionCell.textContent = esc(job.version);
      const statusCell = document.createElement('td'); const cost = document.createElement('span'); cost.className = 'v2-job-cost'; cost.textContent = job.cost_toman ? `هزینهٔ تقریبی: ${Number(job.cost_toman).toLocaleString('fa-IR')} تومان` : 'نرخ مدل ثبت نشده'; const status = document.createElement('small'); status.className = 'v2-job-status'; status.textContent = statusLabel(job.status); statusCell.append(cost, status);
      const actions = document.createElement('td'); const actionWrap = document.createElement('div'); actionWrap.className = 'v2-inline-actions'; actionWrap.append(makeButton('ویرایش', 'v2Edit', job.settings), makeButton('تکثیر تنظیمات', 'v2Duplicate', job.settings)); actions.appendChild(actionWrap);
      row.append(codeCell, productCell, versionCell, statusCell, actions); list.appendChild(row);
    });
    Object.entries(statNodes).forEach(([key, node]) => { node.textContent = Number(payload?.stats?.[key] || 0).toLocaleString('fa-IR'); });
    if (resultCount) resultCount.textContent = `${Number(payload?.total || 0).toLocaleString('fa-IR')} نتیجه`;
    if (searchClear) searchClear.hidden = !String(searchInput?.value || '').trim();
    pagination?.replaceChildren();
    const current = Number(payload?.current_page || 1), last = Number(payload?.last_page || 1);
    for (let page = 1; page <= last; page += 1) {
      if (last > 8 && page > 3 && page < last - 2 && Math.abs(page - current) > 1) { if (!pagination.lastElementChild?.dataset.ellipsis) { const dots = document.createElement('span'); dots.textContent = '…'; dots.dataset.ellipsis = '1'; pagination.appendChild(dots); } continue; }
      const button = document.createElement('button'); button.type = 'button'; button.textContent = String(page); button.classList.toggle('is-active', page === current); button.dataset.v2JobsPage = String(page); pagination?.appendChild(button);
    }
    if (liveStatus) { liveStatus.textContent = `به‌روزرسانی شد · ${new Date().toLocaleTimeString('fa-IR', {hour:'2-digit', minute:'2-digit'})}`; liveStatus.classList.remove('is-error'); }
  };
  let page = 1;
  let refreshTimer = null;
  const refresh = async (requestedPage = page) => {
    try { const query = new URLSearchParams({ page: String(requestedPage), search: String(searchInput?.value || '').trim() }); const response = await fetch(`{{ route('admin.video-studio.experimental.jobs.snapshot') }}?${query.toString()}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }); if (!response.ok) throw new Error('snapshot'); page = requestedPage; render(await response.json()); }
    catch (_) { if (liveStatus) { liveStatus.textContent = 'همگام‌سازی ناموفق بود'; liveStatus.classList.add('is-error'); } }
  };
  list.addEventListener('click', event => {
    const edit = event.target.closest('[data-v2-edit]'); const duplicate = event.target.closest('[data-v2-duplicate]'); const target = edit || duplicate;
    if (!target) return;
    try { const settings = JSON.parse(target.dataset[edit ? 'v2Edit' : 'v2Duplicate'] || '{}'); applySettings(settings); window.alert(edit ? 'تنظیمات خروجی برای ویرایش بارگذاری شد.' : 'تنظیمات تکثیر شد؛ محصول مقصد و منبع را بررسی و ذخیره کنید.'); }
    catch (_) { window.alert('تنظیمات این خروجی قابل بارگذاری نیست.'); }
  });
  pagination?.addEventListener('click', event => { const button = event.target.closest('[data-v2-jobs-page]'); if (button) refresh(Number(button.dataset.v2JobsPage)); });
  searchInput?.addEventListener('input', () => { window.clearTimeout(refreshTimer); refreshTimer = window.setTimeout(() => refresh(1), 300); });
  searchClear?.addEventListener('click', () => { if (searchInput) searchInput.value = ''; refresh(1); });
  refresh();
  window.setInterval(() => refresh(page), 12000);
})();
</script>
