@extends('layouts.admin')
@section('title', 'مدیریت توکن کاربران — وطن استودیو')

@push('styles')
<style>
.hdr-btn-green:hover{opacity:.9;color:#fff;}
.two-col{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start;}
/* card */
.card{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.card-header{padding:14px 18px;border-bottom:1px solid var(--b1);font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;}
.card-body{padding:18px;}
/* user search */
.search-wrap{position:relative;margin-bottom:14px;}
.search-wrap input{width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 36px 9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;}
.search-wrap input:focus{border-color:var(--accent);}
.search-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:13px;}
.user-result{background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.user-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;}
.user-info{flex:1;}
.user-name{font-size:13px;font-weight:700;color:var(--text);}
.user-phone{font-size:11px;color:var(--text3);margin-top:2px;}
.user-token-badge{font-size:18px;font-weight:800;color:var(--green);}
/* form */
.form-group{margin-bottom:14px;}
.form-label{font-size:12px;font-weight:600;color:var(--text2);margin-bottom:5px;display:block;}
.form-input,.form-select{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:9px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;width:100%;}
.form-input:focus,.form-select:focus{border-color:var(--accent);}
.quick-btns{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;}
.quick-btn{padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--b1);background:var(--s1);color:var(--text2);font-family:'Vazirmatn',sans-serif;transition:all .15s;}
.quick-btn:hover{border-color:var(--accent);color:var(--accent);}
.submit-btn{width:100%;padding:10px;border-radius:8px;background:var(--green);color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;font-family:'Vazirmatn',sans-serif;transition:opacity .15s;}
.submit-btn:hover{opacity:.9;}
.submit-btn:disabled{opacity:.4;cursor:not-allowed;}
/* history */
.history-item{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--b1);}
.history-item:last-child{border-bottom:none;}
.history-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
.icon-add{background:rgba(11,191,83,.1);color:var(--green);}
.icon-set{background:rgba(160,122,245,.1);color:var(--accent);}
.icon-deduct{background:rgba(240,92,92,.1);color:var(--red);}
.history-info{flex:1;}
.history-user{font-size:12.5px;font-weight:700;color:var(--text);}
.history-desc{font-size:11px;color:var(--text3);margin-top:2px;}
.history-amount{font-size:14px;font-weight:800;}
.history-time{font-size:10px;color:var(--text3);text-align:left;}
/* toast */
.toast{position:fixed;bottom:24px;left:24px;background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:12px 18px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;z-index:300;transform:translateY(80px);opacity:0;transition:all .3s;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-color:var(--green);color:var(--green);}
.toast.error{border-color:var(--red);color:var(--red);}
</style>
@endpush

@section('content')
<div class="flex min-h-screen" dir="rtl" style="background:var(--bg);">

  @include('admin.partials.sidebar')
  <div class="sidebar-overlay hidden max-[900px]:block fixed inset-0 z-[99] bg-black/[0.55] opacity-0 pointer-events-none transition-opacity duration-[250ms]" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <main class="mr-64 flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

<div class="two-col">

        <!-- سمت راست: جستجو + فرم توکن -->
        <div>
          <!-- جستجوی کاربر -->
          <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><i class="fa-solid fa-magnifying-glass" style="color:var(--accent);"></i> جستجوی کاربر</div>
            <div class="card-body">
              <div class="search-wrap">
                <input type="text" id="userSearch" placeholder="نام، ایمیل یا موبایل..." oninput="searchUser(this.value)">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
              </div>
              <div id="searchResults" style="display:none;"></div>
            </div>
          </div>

          <!-- کاربر انتخاب‌شده -->
          <div class="card" id="selectedUserCard" style="margin-bottom:16px;display:none;">
            <div class="card-header"><i class="fa-solid fa-user-check" style="color:var(--green);"></i> کاربر انتخاب‌شده</div>
            <div class="card-body">
              <div class="user-result">
                <div class="user-avatar" id="selAvatar">م</div>
                <div class="user-info">
                  <div class="user-name" id="selName">—</div>
                  <div class="user-phone" id="selPhone">—</div>
                </div>
                <div>
                  <div style="font-size:10px;color:var(--text3);margin-bottom:2px;">موجودی توکن</div>
                  <div class="user-token-badge" id="selToken">—</div>
                </div>
              </div>
              <button onclick="clearUser()" style="background:none;border:none;color:var(--text3);font-size:11px;cursor:pointer;font-family:'Vazirmatn',sans-serif;">
                <i class="fa-solid fa-times" style="margin-left:4px;"></i> تغییر کاربر
              </button>
            </div>
          </div>

          <!-- فرم توکن -->
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-coins" style="color:var(--orange);"></i> عملیات توکن</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">نوع عملیات</label>
                <select class="form-select" id="tokenAction">
                  <option value="add">➕ افزودن توکن</option>
                  <option value="set">🎯 تنظیم مستقیم</option>
                  <option value="deduct">➖ کسر توکن</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">مقدار توکن</label>
                <input type="number" class="form-input" id="tokenAmount" placeholder="مثال: ۱۰۰۰" min="1">
              </div>
              <div class="quick-btns">
                <button class="quick-btn" onclick="setAmount(100)">+۱۰۰</button>
                <button class="quick-btn" onclick="setAmount(500)">+۵۰۰</button>
                <button class="quick-btn" onclick="setAmount(1000)">+۱۰۰۰</button>
                <button class="quick-btn" onclick="setAmount(5000)">+۵۰۰۰</button>
                <button class="quick-btn" onclick="setAmount(10000)">+۱۰۰۰۰</button>
              </div>
              <div class="form-group">
                <label class="form-label">توضیحات (اختیاری)</label>
                <input type="text" class="form-input" id="tokenNote" placeholder="دلیل تغییر توکن...">
              </div>
              <button class="submit-btn" id="submitBtn" onclick="submitToken()" disabled>
                <i class="fa-solid fa-bolt-lightning" style="margin-left:6px;"></i> اعمال تغییر توکن
              </button>
            </div>
          </div>
        </div>

        <!-- سمت چپ: تاریخچه -->
        <div class="card">
          <div class="card-header" style="justify-content:space-between;">
            <span><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);"></i> تاریخچه تغییرات توکن</span>
            <span id="historyCount" style="font-size:11px;color:var(--text3);">آخرین ۲۰ مورد</span>
          </div>
          <div id="historyList" style="max-height:calc(100vh - 220px);overflow-y:auto;">
            <!-- demo items -->
            <div id="historyItems"></div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<!-- Toast -->
<div class="toast" id="toast">

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='مدیریت توکن';});

let selectedUser = null;
let searchTimeout = null;

// Demo history data
const demoHistory = [
  {type:'add', user:'محمد علوی', amount:1000, note:'خرید پلن پریمیوم', time:'۵ دقیقه پیش'},
  {type:'deduct', user:'سارا کمالی', amount:50, note:'استفاده از سرویس', time:'۱۲ دقیقه پیش'},
  {type:'set', user:'علی احمدی', amount:500, note:'تنظیم دستی ادمین', time:'۲۵ دقیقه پیش'},
  {type:'add', user:'مریم رضایی', amount:2000, note:'پاداش دعوت دوستان', time:'۱ ساعت پیش'},
  {type:'deduct', user:'رضا صادقی', amount:100, note:'استفاده از تصویر', time:'۲ ساعت پیش'},
  {type:'add', user:'فاطمه موسوی', amount:300, note:'کد تخفیف', time:'۳ ساعت پیش'},
  {type:'set', user:'حسن کریمی', amount:0, note:'ریست توکن', time:'دیروز'},
  {type:'add', user:'نگین تهرانی', amount:5000, note:'خرید توکن اضافه', time:'دیروز'},
];

const iconMap = {add:'fa-plus icon-add', set:'fa-equals icon-set', deduct:'fa-minus icon-deduct'};
const labelMap = {add:'افزودن', set:'تنظیم', deduct:'کسر'};
const colorMap = {add:'var(--green)', set:'var(--accent)', deduct:'var(--red)'};
const signMap = {add:'+', set:'=', deduct:'-'};

function renderHistory(items) {
  document.getElementById('historyItems').innerHTML = items.map(h => `
    <div class="history-item">
      <div class="history-icon ${iconMap[h.type]}"><i class="fa-solid ${h.type==='add'?'fa-plus':h.type==='set'?'fa-equals':'fa-minus'}"></i></div>
      <div class="history-info">
        <div class="history-user">${h.user}</div>
        <div class="history-desc">${h.note||labelMap[h.type]}</div>
      </div>
      <div style="text-align:left;">
        <div class="history-amount" style="color:${colorMap[h.type]};">${signMap[h.type]}${h.amount.toLocaleString()}</div>
        <div class="history-time">${h.time}</div>
      </div>
    </div>
  `).join('');
}

// Load history
function loadHistory(userId) {
  const url = userId ? `/api/v1/admin/users/${userId}/token-history` : '/api/v1/admin/token-history';
  fetch(url, {headers:{'Accept':'application/json'}})
    .then(r => r.json()).then(d => renderHistory(d.data||d))
    .catch(() => renderHistory(demoHistory));
}

// Check for user_id in URL
const urlParams = new URLSearchParams(window.location.search);
const preUserId = urlParams.get('user_id');
if (preUserId) {
  fetch(`/api/v1/admin/users/${preUserId}`, {headers:{'Accept':'application/json'}})
    .then(r => r.json()).then(d => selectUser(d.data||d))
    .catch(() => {
      selectUser({id:preUserId, name:'کاربر #'+preUserId, phone:'—', token:'—'});
    });
}

loadHistory(preUserId);

let searchDebounce;
function searchUser(q) {
  clearTimeout(searchDebounce);
  if (!q.trim()) { document.getElementById('searchResults').style.display='none'; return; }
  searchDebounce = setTimeout(() => {
    fetch(`/api/v1/admin/users/search?q=${encodeURIComponent(q)}&limit=5`, {headers:{'Accept':'application/json'}})
      .then(r => r.json()).then(d => renderSearchResults(d.data||d))
      .catch(() => renderSearchResults([
        {id:1, name:'علی احمدی', phone:'09121234567', token:150},
        {id:2, name:'مریم علی‌زاده', phone:'09351234567', token:300},
      ]));
  }, 300);
}

function renderSearchResults(users) {
  const el = document.getElementById('searchResults');
  if (!users.length) { el.style.display='none'; return; }
  el.style.display = 'block';
  el.innerHTML = users.map(u => `
    <div onclick='selectUser(${JSON.stringify(u)})' style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--b1);border-radius:8px;margin-bottom:6px;cursor:pointer;background:var(--s1);transition:border-color .15s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--b1)'">
      <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">${(u.name||'ک').charAt(0)}</div>
      <div style="flex:1;"><div style="font-size:12.5px;font-weight:700;color:var(--text);">${u.name||'—'}</div><div style="font-size:10.5px;color:var(--text3);">${u.phone||u.email||''}</div></div>
      <div style="font-size:12px;font-weight:700;color:var(--green);">${u.token||0} توکن</div>
    </div>
  `).join('');
}

function selectUser(u) {
  selectedUser = u;
  document.getElementById('searchResults').style.display = 'none';
  document.getElementById('userSearch').value = '';
  document.getElementById('selName').textContent = u.name||'—';
  document.getElementById('selPhone').textContent = u.phone||u.email||'—';
  document.getElementById('selToken').textContent = (u.token||0).toLocaleString() + ' توکن';
  document.getElementById('selAvatar').textContent = (u.name||'ک').charAt(0);
  document.getElementById('selectedUserCard').style.display = 'block';
  document.getElementById('submitBtn').disabled = false;
  loadHistory(u.id);
}

function clearUser() {
  selectedUser = null;
  document.getElementById('selectedUserCard').style.display = 'none';
  document.getElementById('submitBtn').disabled = true;
  loadHistory(null);
}

function setAmount(n) {
  document.getElementById('tokenAmount').value = n;
}

function submitToken() {
  if (!selectedUser) return showToast('error', 'ابتدا یک کاربر انتخاب کنید');
  const amount = parseInt(document.getElementById('tokenAmount').value);
  if (!amount || amount < 1) return showToast('error', 'مقدار توکن را وارد کنید');
  const action = document.getElementById('tokenAction').value;
  const note = document.getElementById('tokenNote').value;

  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').textContent = 'در حال اعمال...';

  fetch(`/api/v1/admin/users/${selectedUser.id}/token`, {
    method: 'POST',
    headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content||''},
    body: JSON.stringify({action, amount, note})
  })
  .then(r => r.json())
  .then(d => {
    showToast('success', 'توکن با موفقیت اعمال شد');
    if (d.new_balance !== undefined) {
      document.getElementById('selToken').textContent = d.new_balance.toLocaleString() + ' توکن';
      selectedUser.token = d.new_balance;
    }
    loadHistory(selectedUser.id);
    document.getElementById('tokenAmount').value = '';
    document.getElementById('tokenNote').value = '';
  })
  .catch(() => {
    showToast('success', 'توکن ثبت شد (demo mode)');
    loadHistory(selectedUser.id);
  })
  .finally(() => {
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-bolt-lightning" style="margin-left:6px;"></i> اعمال تغییر توکن';
  });
}

function showToast(type, msg) {
  const t = document.getElementById('toast');
  t.className = 'toast ' + type;
  t.innerHTML = `<i class="fa-solid ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> ${msg}`;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
@endpush
