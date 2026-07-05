@extends('layouts.admin')
@section('title', 'مدیریت کاربران — وطن استودیو')

@section('content')
<div class="flex min-h-screen bg-[#0f111a] text-[#f1f5f9]" dir="rtl">

  @include('admin.partials.sidebar')
  <div class="sidebar-overlay hidden max-[900px]:block fixed inset-0 z-[99] bg-black/55 opacity-0 pointer-events-none transition-opacity duration-250" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

      <div class="grid grid-cols-4 gap-3 mb-5 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1">
        <div class="p-4 rounded-xl border bg-[#151824] border-[#222638]">
          <div class="text-[11px] text-[#94a3b8] mb-1.5">کل کاربران</div>
          <div class="text-[22px] font-extrabold leading-none text-white">{{ $users->count() }}</div>
          <div class="text-[10px] text-[#94a3b8] mt-1">در دیتابیس</div>
        </div>
        <div class="p-4 rounded-xl border bg-[#151824] border-[#222638]">
          <div class="text-[11px] text-[#94a3b8] mb-1.5">کاربران فعال</div>
          <div class="text-[22px] font-extrabold leading-none text-emerald-500">—</div>
          <div class="text-[10px] text-[#94a3b8] mt-1">ماه جاری</div>
        </div>
        <div class="p-4 rounded-xl border bg-[#151824] border-[#222638]">
          <div class="text-[11px] text-[#94a3b8] mb-1.5">تصاویر خلق شده</div>
          <div class="text-[22px] font-extrabold leading-none text-violet-500">—</div>
          <div class="text-[10px] text-[#94a3b8] mt-1">کل</div>
        </div>
        <div class="p-4 rounded-xl border bg-[#151824] border-[#222638]">
          <div class="text-[11px] text-[#94a3b8] mb-1.5">صفحه فعلی</div>
          <div class="text-[22px] font-extrabold leading-none text-white">1</div>
          <div class="text-[10px] text-[#94a3b8] mt-1">بدون صفحه‌بندی</div>
        </div>
      </div>

      <div class="flex gap-2.5 items-center flex-wrap p-3 rounded-xl mb-4 border bg-[#151824] border-[#222638]">
        <input type="text" class="flex-1 min-w-[200px] p-2 text-[13px] rounded-lg border outline-none transition bg-[#0f111a] border-[#222638] text-white focus:border-violet-500" placeholder="جستجوی کاربر (نام، فامیلی، ایمیل، موبایل)..." id="searchInput" oninput="window.filterTable(this.value)">
        <a href="{{ route('admin.users.all_activities') }}" class="hdr-btn"><i class="fa-solid fa-timeline"></i> فعالیت‌ها</a>
        <a href="{{ route('admin.users.all_logs') }}" class="hdr-btn"><i class="fa-solid fa-images"></i> لاگ تصاویر</a>
      </div>

      <div class="overflow-x-auto rounded-2xl border bg-[#151824] border-[#222638]">
        <table class="w-full border-collapse text-right min-w-[1150px]" id="usersTable">
          <thead>
            <tr class="bg-[#0f111a]">
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] w-12">#</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8]">اطلاعات کاربر</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8]">نام</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8]">نام خانوادگی</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8]">ایمیل</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8]">موبایل</th>
              
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] w-[180px]">گزارش توکن‌ها</th>
              
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] text-center">تصاویر خلق شده</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] text-center">تاریخ عضویت</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] text-center">وضعیت دسترسی</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] text-center">جزئیات</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[#222638] text-[#94a3b8] text-center">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#222638]">
            @forelse($users as $i => $user)
            <tr class="hover:bg-white/[0.012] transition-colors">
              <td class="p-3 text-[11px] text-[#94a3b8]">{{ $i + 1 }}</td>
              
              <td class="p-3">
                <div class="flex items-center gap-2.5">
                  @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-[#222638]">
                  @else
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-[#6a4dcc] flex items-center justify-center text-[12px] font-bold flex-shrink-0 text-white">
                      {{ mb_substr($user->name ?? 'ک', 0, 1) }}
                    </div>
                  @endif
                  <div>
                    <div class="text-[10.5px] text-[#94a3b8] font-mono">ID: {{ $user->id }}</div>
                  </div>
                </div>
              </td>

              <td class="p-3 text-[13px] font-medium text-white">{{ $user->name ?? '—' }}</td>
              <td class="p-3 text-[13px] font-medium text-white">{{ $user->last_name ?? '—' }}</td>
              <td class="p-3 text-[12px] font-mono text-[#cbd5e1]">{{ $user->email ?? '—' }}</td>
              <td class="p-3 text-[12px] font-mono text-[#cbd5e1]">{{ $user->phone ?? '—' }}</td>

              <td class="p-3 text-[11px]">
                <div class="flex flex-col gap-1 text-right select-none">
                  <div class="flex justify-between items-center bg-[#0f111a]/50 p-1 px-1.5 rounded border border-[#222638]/40">
                    <span class="text-[#94a3b8] text-[10px]">موجودی فعلی:</span>
                    <span class="font-bold text-amber-500 font-mono">{{ number_format($user->tokens ?? 0) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[#64748b] text-[10px]">کل خریداری شده:</span>
                    <span class="text-emerald-500 font-mono font-medium">{{ number_format($user->tokens_purchased ?? 0) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[#64748b] text-[10px]">کل مصرف شده:</span>
                    <span class="text-rose-500 font-mono font-medium">{{ number_format($user->tokens_used ?? 0) }}</span>
                  </div>
                </div>
              </td>

              <td class="p-3 text-center"><span class="font-bold text-violet-500">{{ $user->generated_images_count ?? 0 }}</span></td>
              
              <td class="p-3 text-center text-[11px] text-[#94a3b8] dir-ltr font-mono">
                {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}
              </td>

              <td class="p-3 text-center">
                <span id="badge-{{ $user->id }}" class="inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200
                  @if($user->status === 'active') text-emerald-500 bg-emerald-500/10 border-emerald-500/20
                  @elseif($user->status === 'suspended') text-amber-500 bg-amber-500/10 border-amber-500/20
                  @else text-rose-500 bg-rose-500/10 border-rose-500/20 @endif">
                  @if($user->status === 'active') فعال
                  @elseif($user->status === 'suspended') معلق
                  @else حذف شده @endif
                </span>
              </td>

              <td class="p-3 text-center">
                <button type="button" 
                  onclick="window.openUserModal('{{ $user->name ?? 'کاربر' }} {{ $user->last_name ?? '' }}', {{ json_encode($user->generatedImages) }})"
                  class="px-2 py-1 bg-violet-600/10 border border-violet-500/20 hover:bg-violet-600/20 text-violet-400 rounded-md text-[11px] font-medium transition-colors cursor-pointer">
                  <i class="fa-solid fa-eye ml-1"></i> محصولات
                </button>
              </td>

              <td class="p-3 text-center">
                <div class="flex items-center justify-center gap-1">
                  <select onchange="window.changeStatus({{ $user->id }}, this.value)" class="text-[11px] p-1 rounded border outline-none font-sans bg-[#0f111a] border-[#222638] text-[#cbd5e1] focus:border-violet-500 cursor-pointer">
                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="deleted" {{ $user->status === 'deleted' ? 'selected' : '' }}>حذف شده</option>
                  </select>

                  <a href="{{ route('admin.users.logs', $user->id) }}" class="w-7 h-7 rounded-md border bg-[#0f111a] border-[#222638] text-[#cbd5e1] inline-flex items-center justify-center cursor-pointer text-[11px] transition-all hover:border-violet-500 hover:text-violet-500 ml-0.5" title="مشاهده لاگ‌ها"><i class="fa-solid fa-history"></i></a>
                  <a href="{{ route('admin.users.tokens') }}?user_id={{ $user->id }}" class="w-7 h-7 rounded-md border bg-[#0f111a] border-[#222638] text-[#cbd5e1] inline-flex items-center justify-center cursor-pointer text-[11px] transition-all hover:border-violet-500 hover:text-violet-500" title="مدیریت توکن"><i class="fa-solid fa-coins"></i></a>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="12" class="text-center p-10 text-[13px] text-[#94a3b8]">هیچ کاربری یافت نشد.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<div id="productModal" class="fixed inset-0 z-[9999] items-center justify-center bg-black/70 backdrop-blur-sm p-4 hidden transition-all duration-300 opacity-0">
  <div class="bg-[#151824] border border-[#222638] rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
    <div class="flex items-center justify-between p-4 border-b border-[#222638] bg-[#0f111a]">
      <h3 class="text-[14px] font-bold text-white flex items-center gap-2">
        <i class="fa-solid fa-cubes text-violet-500"></i>
        محصولات استفاده شده توسط: <span id="modalUserName" class="text-violet-400"></span>
      </h3>
      <button onclick="window.closeUserModal()" class="text-[#94a3b8] hover:text-white transition-colors cursor-pointer text-[16px]">&times;</button>
    </div>
    
    <div class="p-4 max-h-[350px] overflow-y-auto" id="modalContent"></div>

    <div class="p-3 border-t border-[#222638] bg-[#0f111a] text-left">
      <button onclick="window.closeUserModal()" class="px-4 py-1.5 bg-[#222638] hover:bg-[#2d324a] text-[#cbd5e1] rounded-lg text-[12px] font-medium transition-colors cursor-pointer">بستن پنجره</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
try {
  var bc = document.getElementById('breadcrumb');
  if(bc) bc.textContent = 'لیست کاربران سیستم';
} catch(e){}

window.filterTable = function(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(row => {
    row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

window.openUserModal = function(fullName, generatedImages) {
  document.getElementById('modalUserName').innerText = fullName;
  const container = document.getElementById('modalContent');
  container.innerHTML = '';

  if (!generatedImages || generatedImages.length === 0) {
    container.innerHTML = `<div class="text-center p-6 text-[12.5px] text-[#94a3b8]">این کاربر هنوز از هیچ محصولی برای تولید تصویر استفاده نکرده است.</div>`;
  } else {
    let listHtml = `<div class="divide-y divide-[#222638]">`;
    generatedImages.forEach((img, index) => {
      const productName = img.product ? (img.product.title || img.product.name) : 'محصول نامشخص / حذف شده';
      const category = img.product ? (img.product.category || '—') : '—';
      const dateStr = img.created_at ? img.created_at.substring(0, 16).replace('T', ' ') : '—';
      
      listHtml += `
        <div class="py-3 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded bg-violet-500/10 border border-violet-500/20 text-violet-400 flex items-center justify-center text-[11px] font-mono font-bold">${index + 1}</div>
            <div>
              <div class="text-[12.5px] font-bold text-white">${productName}</div>
              <div class="text-[10px] text-[#94a3b8] mt-0.5">دسته‌بندی: ${category}</div>
            </div>
          </div>
          <div class="text-left">
            <span class="inline-block text-[10.5px] font-mono text-[#94a3b8] bg-[#0f111a] px-2 py-0.5 rounded border border-[#222638]">${dateStr}</span>
          </div>
        </div>
      `;
    });
    listHtml += `</div>`;
    container.innerHTML = listHtml;
  }

  const modal = document.getElementById('productModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => {
    modal.classList.remove('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-95');
    modal.querySelector('.transform').classList.add('scale-100');
  }, 20);
}

window.closeUserModal = function() {
  const modal = document.getElementById('productModal');
  modal.classList.add('opacity-0');
  modal.querySelector('.transform').classList.remove('scale-100');
  modal.querySelector('.transform').classList.add('scale-95');
  setTimeout(() => {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 200);
}

window.changeStatus = function(userId, status) {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
  fetch(`/admin/users/${userId}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ status: status })
  })
  .then(res => res.json())
  .then(data => {
    if(data.status === 'success') {
      const badge = document.getElementById(`badge-${userId}`);
      if(status === 'active') {
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
        badge.innerText = 'فعال';
      } else if(status === 'suspended') {
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-amber-500 bg-amber-500/10 border-amber-500/20';
        badge.innerText = 'معلق';
      } else if(status === 'deleted') {
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-rose-500 bg-rose-500/10 border-rose-500/20';
        badge.innerText = 'حذف شده';
      }
    } else {
      alert('عملیات با خطا مواجه شد.');
    }
  })
  .catch(err => {
    console.error(err);
    alert('خطا در برقراری ارتباط با سرور.');
  });
}
</script>
@endsection