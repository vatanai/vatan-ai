@extends('layouts.admin')
@section('title', 'مدیریت کاربران — وطن استودیو')

@section('content')
<div class="flex min-h-screen bg-[var(--page-bg)] text-[var(--text-h)]" dir="rtl">

  <main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

      <div class="grid grid-cols-4 gap-3 mb-5 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1">
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">کل کاربران</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--text-h)]">{{ $allUsersCount }}</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">در دیتابیس</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">کاربران فعال</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--success)]">—</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">ماه جاری</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">تصاویر خلق شده</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--info)]">—</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">کل</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">صفحه فعلی</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--text-h)]">1</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">بدون صفحه‌بندی</div>
        </div>
      </div>

      <div class="flex gap-2.5 items-center flex-wrap p-3 rounded-xl mb-4 border bg-[var(--card-bg)] border-[var(--border)]">
        @if($errors->has('birth_month') || $errors->has('birth_day'))
          <div class="w-full px-3 py-2 rounded-lg border border-[var(--danger-m)] bg-[var(--danger-l)] text-[12px] text-[var(--danger)]">
            {{ $errors->first('birth_month') ?: $errors->first('birth_day') }}
          </div>
        @endif
        <input type="text" class="flex-1 min-w-[200px] p-2 text-[13px] rounded-lg border outline-none transition bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-h)] focus:border-[var(--info)]" placeholder="جستجوی کاربر (نام، فامیلی، ایمیل، موبایل)..." id="searchInput" oninput="window.filterTable(this.value)">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2 flex-wrap">
          <span class="text-[11px] font-semibold text-[var(--text-soft)]">تولد شمسی:</span>
          <select name="birth_month" class="p-2 text-[12px] rounded-lg border outline-none bg-[var(--input-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
            <option value="">همه ماه‌ها</option>
            @foreach([1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'] as $monthNumber => $monthName)
              <option value="{{ $monthNumber }}" @selected($birthMonth === $monthNumber)>{{ $monthName }}</option>
            @endforeach
          </select>
          <select name="birth_day" class="p-2 text-[12px] rounded-lg border outline-none bg-[var(--input-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
            <option value="">همه روزها</option>
            @for($day = 1; $day <= 31; $day++)
              <option value="{{ $day }}" @selected($birthDay === $day)>روز {{ $day }}</option>
            @endfor
          </select>
          <button type="submit" class="hdr-btn"><i class="fa-solid fa-filter"></i> اعمال</button>
          @if($birthMonth || $birthDay)
            <a href="{{ route('admin.users.index') }}" class="hdr-btn"><i class="fa-solid fa-xmark"></i> پاک کردن</a>
          @endif
        </form>
        <a href="{{ route('admin.users.all_activities') }}" class="hdr-btn"><i class="fa-solid fa-timeline"></i> فعالیت‌ها</a>
        <a href="{{ route('admin.users.all_logs') }}" class="hdr-btn"><i class="fa-solid fa-images"></i> لاگ تصاویر</a>
      </div>

      <div class="overflow-x-auto rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]">
        <table class="w-full border-collapse text-right min-w-[1250px]" id="usersTable">
          <thead>
            <tr class="bg-[var(--page-bg)]">
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-12">#</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">اطلاعات کاربر</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">نام</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">نام خانوادگی</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">ایمیل</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">موبایل</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">تاریخ تولد</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)]">رمز عبور</th>
              
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[180px]">گزارش توکن‌ها</th>
              
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">تصاویر خلق شده</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">تاریخ عضویت</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">وضعیت دسترسی</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">گروه قیمت‌گذاری</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">جزئیات</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">عملیات</th>
              <th class="p-3 text-[10px] font-bold uppercase tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] text-center">فعالیت</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            @forelse($users as $i => $user)
            <tr class="transition-colors" style="--tw-bg-opacity:1;" onmouseenter="this.style.background='var(--primary-l)'" onmouseleave="this.style.background=''">
              <td class="p-3 text-[11px] text-[var(--text-soft)]">{{ $i + 1 }}</td>
              
              <td class="p-3">
                <div class="flex items-center gap-2.5">
                  @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-[var(--border)]">
                  @else
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary)] flex items-center justify-center text-[12px] font-bold flex-shrink-0 text-[var(--text-h)]">
                      {{ mb_substr($user->name ?? 'ک', 0, 1) }}
                    </div>
                  @endif
                  <div>
                    <div class="text-[10.5px] text-[var(--text-soft)] font-mono">ID: {{ $user->id }}</div>
                  </div>
                </div>
              </td>

              <td class="p-3 text-[13px] font-medium text-[var(--text-h)]">{{ $user->name ?? '—' }}</td>
              <td class="p-3 text-[13px] font-medium text-[var(--text-h)]">{{ $user->last_name ?? '—' }}</td>
              <td class="p-3 text-[12px] font-mono text-[var(--text-main)]">{{ $user->email ?? '—' }}</td>
              <td class="p-3 text-[12px] font-mono text-[var(--text-main)]">{{ $user->phone ?? '—' }}</td>
              <td class="p-3 text-center text-[11px] font-mono text-[var(--text-main)]">
                @if($user->birth_date)
                  @php([$birthJy, $birthJm, $birthJd] = \App\Support\Jalali::toJalaliYmd((int)$user->birth_date->format('Y'), (int)$user->birth_date->format('n'), (int)$user->birth_date->format('j')))
                  {{ sprintf('%04d/%02d/%02d', $birthJy, $birthJm, $birthJd) }}
                @else
                  —
                @endif
              </td>
              <td class="p-3">
                <button type="button" onclick="window.copyUserPassword(this, {{ $user->id }})" class="w-7 h-7 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center cursor-pointer text-[11px] transition-all hover:border-[var(--info)] hover:text-[var(--info)]" title="کپی رمز عبور" aria-label="کپی رمز عبور برای {{ $user->name ?? 'کاربر' }}">
                  <i class="fa-solid fa-copy"></i>
                </button>
              </td>

              <td class="p-3 text-[11px]">
                <div class="flex flex-col gap-1 text-right select-none">
                  <div class="flex justify-between items-center bg-[var(--page-bg)]/50 p-1 px-1.5 rounded border border-[var(--border)]/40">
                    <span class="text-[var(--text-soft)] text-[10px]">موجودی فعلی:</span>
                    <span class="font-bold text-[var(--warning)] font-mono">{{ number_format($user->tokens ?? 0) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[var(--text-soft)] text-[10px]">کل خریداری شده:</span>
                    <span class="text-[var(--success)] font-mono font-medium">{{ number_format($user->tokens_purchased ?? 0) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[var(--text-soft)] text-[10px]">کل مصرف شده:</span>
                    <span class="text-[var(--danger)] font-mono font-medium">{{ number_format($user->tokens_used ?? 0) }}</span>
                  </div>
                </div>
              </td>

              <td class="p-3 text-center"><span class="font-bold text-[var(--info)]">{{ $user->generated_images_count ?? 0 }}</span></td>
              
              <td class="p-3 text-center text-[11px] text-[var(--text-soft)] dir-ltr font-mono">
                {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}
              </td>

              <td class="p-3 text-center">
                <span id="badge-{{ $user->id }}" class="inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200
                  @if($user->status === 'active') text-[var(--success)] bg-[var(--success-l)] border-[var(--success-m)]
                  @elseif($user->status === 'suspended') text-[var(--warning)] bg-[var(--warning-l)] border-[var(--warning-m)]
                  @else text-[var(--danger)] bg-[var(--danger-l)] border-[var(--danger-m)] @endif">
                  @if($user->status === 'active') فعال
                  @elseif($user->status === 'suspended') معلق
                  @else حذف شده @endif
                </span>
              </td>
              <td class="p-3 text-center">
                <select onchange="window.changeCustomerSegment({{ $user->id }}, this)" data-current="{{ $user->customer_segment ?: 'regular' }}" class="text-[11px] p-1.5 rounded border outline-none bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
                  <option value="regular" @selected(($user->customer_segment ?: 'regular')==='regular')>کاربر عادی</option>
                  <option value="loyal" @selected($user->customer_segment==='loyal')>مشتری ثابت</option>
                </select>
              </td>
              <td class="p-3 text-center">
                <button type="button" 
                  onclick="window.openUserModal('{{ $user->name ?? 'کاربر' }} {{ $user->last_name ?? '' }}', {{ json_encode($user->generatedImages) }})"
                  class="px-2 py-1 bg-[var(--info-l)] border border-[var(--info-m)] hover:bg-[var(--info-l)] text-[var(--info)] rounded-md text-[11px] font-medium transition-colors cursor-pointer">
                  <i class="fa-solid fa-eye ml-1"></i> محصولات
                </button>
              </td>

              <td class="p-3 text-center">
                <div class="flex items-center justify-center gap-1">
                  <select onchange="window.changeStatus({{ $user->id }}, this.value)" class="text-[11px] p-1 rounded border outline-none font-sans bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--info)] cursor-pointer">
                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>معلق</option>
                    <option value="deleted" {{ $user->status === 'deleted' ? 'selected' : '' }}>حذف شده</option>
                  </select>

                  <a href="{{ route('admin.users.logs', $user->id) }}" class="w-7 h-7 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center cursor-pointer text-[11px] transition-all hover:border-[var(--info)] hover:text-[var(--info)] ml-0.5" title="مشاهده لاگ‌ها"><i class="fa-solid fa-history"></i></a>
                  <a href="{{ route('admin.users.tokens') }}?user_id={{ $user->id }}" class="w-7 h-7 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center cursor-pointer text-[11px] transition-all hover:border-[var(--info)] hover:text-[var(--info)]" title="مدیریت توکن"><i class="fa-solid fa-coins"></i></a>
                </div>
              </td>
              <td class="p-3 text-center">
                <button type="button"
                  onclick="window.openActivityModal(@js(trim(($user->name ?? 'کاربر').' '.($user->last_name ?? ''))), @js($user->created_at?->format('Y-m-d H:i')), @js($user->generatedImages->map(fn($image) => ['action' => 'ساخت تصویر', 'date' => $image->created_at?->format('Y-m-d H:i')])->values()))"
                  class="w-8 h-8 rounded-lg border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center hover:border-[var(--info)] hover:text-[var(--info)]"
                  title="نمایش همه فعالیت‌های کاربر">
                  <i class="fa-solid fa-list-check"></i>
                </button>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="16" class="text-center p-10 text-[13px] text-[var(--text-soft)]">هیچ کاربری یافت نشد.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<div id="productModal" class="fixed inset-0 z-[9999] items-center justify-center backdrop-blur-sm p-4 hidden transition-all duration-300 opacity-0" style="background:color-mix(in srgb, var(--text-h) 70%, transparent);">
  <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
    <div class="flex items-center justify-between p-4 border-b border-[var(--border)] bg-[var(--page-bg)]">
      <h3 class="text-[14px] font-bold text-[var(--text-h)] flex items-center gap-2">
        <i class="fa-solid fa-cubes text-[var(--info)]"></i>
        محصولات استفاده شده توسط: <span id="modalUserName" class="text-[var(--info)]"></span>
      </h3>
      <button onclick="window.closeUserModal()" class="text-[var(--text-soft)] hover:text-[var(--text-h)] transition-colors cursor-pointer text-[16px]">&times;</button>
    </div>
    
    <div class="p-4 max-h-[350px] overflow-y-auto" id="modalContent"></div>

    <div class="p-3 border-t border-[var(--border)] bg-[var(--page-bg)] text-left">
      <button onclick="window.closeUserModal()" class="px-4 py-1.5 bg-[var(--border)] hover:bg-[var(--primary-m)] text-[var(--text-main)] rounded-lg text-[12px] font-medium transition-colors cursor-pointer">بستن پنجره</button>
    </div>
  </div>
</div>

<div id="activityModal" class="fixed inset-0 z-[9999] items-center justify-center p-4 hidden" style="background:color-mix(in srgb, var(--text-h) 55%, transparent);">
  <div class="rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl" style="background:var(--card-bg);border:1px solid var(--border);">
    <div class="flex items-center justify-between p-4" style="border-bottom:1px solid var(--border);">
      <h3 class="text-[14px] font-bold" style="color:var(--text-h);"><i class="fa-solid fa-timeline ml-2" style="color:var(--primary);"></i>فعالیت‌های <span id="activityUserName"></span></h3>
      <button type="button" onclick="window.closeActivityModal()" style="color:var(--text-soft);" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="activityModalContent" class="p-4 max-h-[420px] overflow-y-auto"></div>
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
    container.innerHTML = `<div class="text-center p-6 text-[12.5px] text-[var(--text-soft)]">این کاربر هنوز از هیچ محصولی برای تولید تصویر استفاده نکرده است.</div>`;
  } else {
    let listHtml = `<div class="divide-y divide-[var(--border)]">`;
    generatedImages.forEach((img, index) => {
      const productName = img.product ? (img.product.title || img.product.name) : 'محصول نامشخص / حذف شده';
      const category = img.product ? (img.product.category || '—') : '—';
      const dateStr = img.created_at ? img.created_at.substring(0, 16).replace('T', ' ') : '—';
      
      listHtml += `
        <div class="py-3 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded bg-[var(--info-l)] border border-[var(--info-m)] text-[var(--info)] flex items-center justify-center text-[11px] font-mono font-bold">${index + 1}</div>
            <div>
              <div class="text-[12.5px] font-bold text-[var(--text-h)]">${productName}</div>
              <div class="text-[10px] text-[var(--text-soft)] mt-0.5">دسته‌بندی: ${category}</div>
            </div>
          </div>
          <div class="text-left">
            <span class="inline-block text-[10.5px] font-mono text-[var(--text-soft)] bg-[var(--page-bg)] px-2 py-0.5 rounded border border-[var(--border)]">${dateStr}</span>
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

window.openActivityModal = function(fullName, joinedAt, activities) {
  document.getElementById('activityUserName').textContent = fullName || 'کاربر';
  const entries = [{ action: 'ورود / عضویت در سامانه', date: joinedAt || '—', icon: 'fa-right-to-bracket' }]
    .concat((activities || []).map(item => ({ action: item.action || 'فعالیت کاربر', date: item.date || '—', icon: 'fa-wand-magic-sparkles' })));
  document.getElementById('activityModalContent').innerHTML = entries.map(function(item) {
    return '<div class="flex items-center gap-3 p-3 mb-2 rounded-xl" style="background:var(--input-bg);border:1px solid var(--border);">'
      + '<span class="w-9 h-9 rounded-lg inline-flex items-center justify-center" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid '+item.icon+'"></i></span>'
      + '<div class="flex-1"><div class="text-[12px] font-bold" style="color:var(--text-main);">'+item.action+'</div><div class="text-[10px] mt-1" style="color:var(--text-soft);">'+item.date+'</div></div></div>';
  }).join('');
  const modal = document.getElementById('activityModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
window.closeActivityModal = function() {
  const modal = document.getElementById('activityModal');
  modal.classList.remove('flex');
  modal.classList.add('hidden');
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
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-[var(--success)] bg-[var(--success-l)] border-[var(--success-m)]';
        badge.innerText = 'فعال';
      } else if(status === 'suspended') {
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-[var(--warning)] bg-[var(--warning-l)] border-[var(--warning-m)]';
        badge.innerText = 'معلق';
      } else if(status === 'deleted') {
        badge.className = 'inline-block px-2.5 py-1 text-[11px] font-medium rounded-md border transition-all duration-200 text-[var(--danger)] bg-[var(--danger-l)] border-[var(--danger-m)]';
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

window.copyUserPassword = async function(button, userId) {
  const originalIcon = button.innerHTML;
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  button.disabled = true;
  button.classList.add('opacity-60', 'cursor-wait');

  try {
    const response = await fetch(`/admin/users/${userId}/copy-password`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
      },
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'ساخت رمز جدید انجام نشد.');

    let copied = false;
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(data.password);
      copied = true;
    } else {
      const input = document.createElement('textarea');
      input.value = data.password;
      input.setAttribute('readonly', '');
      input.style.position = 'fixed';
      input.style.opacity = '0';
      document.body.appendChild(input);
      input.select();
      copied = document.execCommand('copy');
      input.remove();
    }
    if (!copied) throw new Error('کپی خودکار رمز در این مرورگر ممکن نشد.');

    button.innerHTML = '<i class="fa-solid fa-check"></i>';
    button.title = 'رمز جدید کپی شد';
    if (typeof window.showAdminToast === 'function') window.showAdminToast('رمز عبور کپی شد.', 'success');
    else alert('رمز عبور کپی شد.');
    setTimeout(() => {
      button.innerHTML = originalIcon;
      button.title = 'کپی رمز عبور';
    }, 1800);
  } catch (error) {
    if (typeof window.showAdminToast === 'function') window.showAdminToast(error.message, 'error');
    else alert(error.message);
  } finally {
    button.disabled = false;
    button.classList.remove('opacity-60', 'cursor-wait');
  }
}

window.changeCustomerSegment = function(userId, select) {
  const previous = select.dataset.current;
  fetch(`/admin/users/${userId}/customer-segment`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ customer_segment: select.value })
  }).then(async response => {
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'ذخیره گروه انجام نشد.');
    select.dataset.current = select.value;
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
  }).catch(error => {
    select.value = previous;
    if (typeof window.showAdminToast === 'function') window.showAdminToast(error.message, 'error');
    else alert(error.message);
  });
}
</script>
@endsection
