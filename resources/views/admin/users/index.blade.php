@extends('layouts.admin')
@section('title', 'مدیریت کاربران — وطن استودیو')

@push('styles')
<style>
.badge-green{background:rgba(11,191,83,.1);color:var(--green);border:1px solid rgba(11,191,83,.2);}
.badge-orange{background:rgba(245,146,58,.1);color:var(--orange);border:1px solid rgba(245,146,58,.2);}
.badge-red{background:rgba(240,92,92,.1);color:var(--red);border:1px solid rgba(240,92,92,.2);}
.badge-purple{background:rgba(160,122,245,.1);color:var(--accent);border:1px solid rgba(160,122,245,.2);}
.badge-gray{background:var(--b1);color:var(--text2);border:1px solid var(--b2);}
/* filter */
.filter-bar{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-input{background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:7px 12px;font-size:13px;color:var(--text);font-family:'Vazirmatn',sans-serif;outline:none;direction:rtl;}
.filter-input:focus{border-color:var(--accent);}
/* table */
.table-wrap{background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:11px 16px;text-align:right;border-bottom:1px solid var(--b1);background:var(--s1);}
.data-table td{padding:13px 16px;border-bottom:1px solid var(--b1);font-size:12.5px;color:var(--text2);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.012);}
.action-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .15s;text-decoration:none;margin-left:3px;}
.action-btn:hover{border-color:var(--accent);color:var(--accent);}
/* kpi */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:16px 18px;}
.kpi-label{font-size:11px;color:var(--text3);margin-bottom:6px;}
.kpi-val{font-size:22px;font-weight:800;line-height:1;}
.kpi-sub{font-size:10px;color:var(--text3);margin-top:4px;}
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
          <div class="kpi-label">کل کاربران</div>
          <div class="kpi-val">{{ $users->total() }}</div>
          <div class="kpi-sub">در دیتابیس</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">کاربران فعال</div>
          <div class="kpi-val" style="color:var(--green);">—</div>
          <div class="kpi-sub">ماه جاری</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">تصاویر خلق شده</div>
          <div class="kpi-val" style="color:var(--accent);">—</div>
          <div class="kpi-sub">کل</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">صفحه فعلی</div>
          <div class="kpi-val">{{ $users->currentPage() }}</div>
          <div class="kpi-sub">از {{ $users->lastPage() }} صفحه</div>
        </div>
      </div>

      <!-- فیلتر -->
      <div class="filter-bar">
        <input type="text" class="filter-input" placeholder="جستجوی کاربر (نام، ایمیل، موبایل)..." id="searchInput" style="flex:1;min-width:200px;" oninput="filterTable(this.value)">
        <a href="{{ route('admin.users.all_activities') }}" class="hdr-btn"><i class="fa-solid fa-timeline"></i> فعالیت‌ها</a>
        <a href="{{ route('admin.users.all_logs') }}" class="hdr-btn"><i class="fa-solid fa-images"></i> لاگ تصاویر</a>
      </div>

      <!-- جدول -->
      <div class="table-wrap">
        <table class="data-table" id="usersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>نام کاربر</th>
              <th>ایمیل</th>
              <th>موبایل</th>
              <th style="text-align:center;">تصاویر</th>
              <th style="text-align:center;">عملیات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $i => $user)
            <tr>
              <td style="color:var(--text3);font-size:11px;">{{ $users->firstItem() + $i }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#6a4dcc);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                    {{ mb_substr($user->name ?? 'ک', 0, 1) }}
                  </div>
                  <div>
                    <div style="font-size:13px;font-weight:700;color:var(--text);">{{ $user->name ?? 'کاربر' }} {{ $user->last_name ?? '' }}</div>
                    <div style="font-size:10.5px;color:var(--text3);">ID: {{ $user->id }}</div>
                  </div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:12px;">{{ $user->email ?? '—' }}</td>
              <td style="font-family:monospace;font-size:12px;">{{ $user->phone ?? '—' }}</td>
              <td style="text-align:center;"><span style="font-weight:700;color:var(--accent);">{{ $user->generations_count ?? 0 }}</span></td>
              <td style="text-align:center;">
                <a href="{{ route('admin.users.logs', $user->id) }}" class="action-btn" title="مشاهده لاگ‌ها"><i class="fa-solid fa-history"></i></a>
                <a href="{{ route('admin.users.tokens') }}?user_id={{ $user->id }}" class="action-btn" title="مدیریت توکن"><i class="fa-solid fa-coins"></i></a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:var(--text3);">هیچ کاربری یافت نشد.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- پجینیشن -->
      <div style="margin-top:16px;">{{ $users->links() }}

    </div>
  </main>
</div>
@endsection
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){var bc=document.getElementById('breadcrumb');if(bc)bc.textContent='لیست کاربران';});

function filterTable(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(row => {
    row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
@endpush
