@extends('layouts.admin')
@section('title', 'مدیران سایت — وطن استودیو')

@push('styles')
<style>
.admins-page{padding:24px;display:grid;gap:18px}.admins-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.admins-title{font-size:20px;font-weight:900;color:var(--text-h)}.admins-subtitle{font-size:12px;color:var(--text-soft);margin-top:5px}.admins-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.8fr);gap:18px;align-items:start}.admins-card{background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-card);overflow:hidden}.admins-card-head{padding:16px 18px;border-bottom:1px solid var(--divider);display:flex;justify-content:space-between;align-items:center;gap:10px}.admins-card-title{font-size:14px;font-weight:800;color:var(--text-h)}.admins-count{font-size:11px;color:var(--text-soft);background:var(--input-bg);border:1px solid var(--border);border-radius:99px;padding:4px 9px}.admins-list{display:grid}.admin-row{display:grid;grid-template-columns:minmax(180px,1.4fr) minmax(170px,1fr) auto;gap:14px;align-items:center;padding:15px 18px;border-bottom:1px solid var(--divider)}.admin-row:last-child{border-bottom:0}.admin-person{display:flex;align-items:center;gap:11px;min-width:0}.admin-avatar{width:40px;height:40px;border-radius:12px;background:var(--primary);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:900;flex-shrink:0}.admin-name{font-size:13px;font-weight:800;color:var(--text-h);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.admin-email,.admin-phone{font-size:11px;color:var(--text-soft);direction:ltr;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.admin-meta{display:flex;gap:6px;flex-wrap:wrap}.admin-badge{font-size:10px;font-weight:700;border-radius:99px;padding:5px 9px;border:1px solid}.admin-badge.role{color:var(--primary);background:var(--primary-l);border-color:var(--primary-m)}.admin-badge.active{color:var(--success);background:var(--success-l);border-color:var(--success-m)}.admin-badge.inactive{color:var(--danger);background:var(--danger-l);border-color:var(--danger-m)}.admin-actions{display:flex;gap:6px}.admin-btn{border:1px solid var(--border);background:var(--card-bg);color:var(--text-soft);border-radius:9px;height:34px;padding:0 11px;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.admin-btn:hover{border-color:var(--primary);color:var(--primary)}.admin-btn.danger:hover{border-color:var(--danger);color:var(--danger)}.admin-form{padding:18px;display:grid;gap:13px}.admin-field{display:grid;gap:6px}.admin-field label{font-size:11px;font-weight:700;color:var(--text-main)}.admin-input{width:100%;height:42px;border-radius:10px;border:1px solid var(--border);background:var(--input-bg);color:var(--text-h);padding:0 12px;font-size:12px;outline:none}.admin-input:focus{border-color:var(--primary);background:var(--card-bg)}.admin-form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}.admin-check{display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-main)}.admin-check input{accent-color:var(--primary);width:16px;height:16px}.admin-submit{height:42px;border:0;border-radius:10px;background:var(--primary);color:var(--accent);font-size:12px;font-weight:900;cursor:pointer}.admin-cancel{height:38px;border:1px solid var(--border);border-radius:10px;background:var(--card-bg);color:var(--text-soft);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}.admin-alert{padding:11px 14px;border-radius:11px;border:1px solid;font-size:12px}.admin-alert.success{color:var(--success);border-color:var(--success-m);background:var(--success-l)}.admin-alert.error{color:var(--danger);border-color:var(--danger-m);background:var(--danger-l)}.admin-hint{font-size:10px;line-height:1.8;color:var(--text-soft)}.admin-empty{padding:34px;text-align:center;color:var(--text-soft);font-size:12px}@media(max-width:1100px){.admins-grid{grid-template-columns:1fr}.admin-row{grid-template-columns:minmax(0,1fr) auto}.admin-meta{grid-column:1}}@media(max-width:640px){.admins-page{padding:14px}.admin-row{grid-template-columns:1fr}.admin-actions{justify-content:flex-start}.admin-form-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content">
    <div class="admins-page">
      <div class="admins-head">
        <div>
          <h1 class="admins-title">مدیران سایت</h1>
          <p class="admins-subtitle">ساخت و مدیریت حساب ورود رهبر و مدیران سایت</p>
        </div>
      </div>

      @if(session('success'))<div class="admin-alert success">{{ session('success') }}</div>@endif
      @if($errors->any())<div class="admin-alert error">{{ $errors->first() }}</div>@endif

      <div class="admins-grid">
        <section class="admins-card">
          <div class="admins-card-head">
            <h2 class="admins-card-title">حساب‌های مدیران</h2>
            <span class="admins-count">{{ number_format($admins->count()) }} مدیر</span>
          </div>
          <div class="admins-list">
            @forelse($admins as $admin)
              <article class="admin-row">
                <div class="admin-person">
                  <div class="admin-avatar">{{ mb_substr($admin->name, 0, 1) }}</div>
                  <div style="min-width:0">
                    <div class="admin-name">{{ $admin->name }}</div>
                    <div class="admin-email">{{ $admin->email }}</div>
                    <div class="admin-phone">{{ $admin->phone ?: 'شماره ثبت نشده' }}</div>
                  </div>
                </div>
                <div class="admin-meta">
                  <span class="admin-badge role">{{ $admin->role === 'leader' ? 'رهبر' : 'مدیر' }}</span>
                  <span class="admin-badge {{ $admin->is_active ? 'active' : 'inactive' }}">{{ $admin->is_active ? 'فعال' : 'غیرفعال' }}</span>
                </div>
                <div class="admin-actions">
                  <button type="button" class="admin-btn" onclick="window.copyAdminPassword(this, {{ $admin->id }})" title="کپی رمز عبور" aria-label="کپی رمز عبور {{ $admin->name }}"><i class="fa-solid fa-copy"></i></button>
                  <a class="admin-btn" href="{{ route('admin.settings.admins', ['edit' => $admin->id]) }}"><i class="fa-solid fa-pen"></i> ویرایش</a>
                  @if(!auth('admin')->user()->is($admin))
                    <form method="POST" action="{{ route('admin.settings.admins.destroy', $admin) }}" onsubmit="return confirm('این حساب مدیر حذف شود؟')">
                      @csrf @method('DELETE')
                      <button class="admin-btn danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                    </form>
                  @endif
                </div>
              </article>
            @empty
              <div class="admin-empty">مدیری ثبت نشده است.</div>
            @endforelse
          </div>
        </section>

        <section class="admins-card">
          <div class="admins-card-head">
            <h2 class="admins-card-title">{{ $editingAdmin ? 'ویرایش مدیر' : 'افزودن مدیر جدید' }}</h2>
          </div>
          <form class="admin-form" method="POST" action="{{ $editingAdmin ? route('admin.settings.admins.update', $editingAdmin) : route('admin.settings.admins.store') }}">
            @csrf
            @if($editingAdmin) @method('PUT') @endif
            <div class="admin-field">
              <label for="name">نام و نام خانوادگی</label>
              <input class="admin-input" id="name" name="name" value="{{ old('name', $editingAdmin?->name) }}" required>
            </div>
            <div class="admin-field">
              <label for="email">جیمیل</label>
              <input class="admin-input" id="email" type="email" name="email" dir="ltr" value="{{ old('email', $editingAdmin?->email) }}" placeholder="name@gmail.com" required>
            </div>
            <div class="admin-field">
              <label for="phone">شماره موبایل</label>
              <input class="admin-input" id="phone" name="phone" dir="ltr" inputmode="numeric" maxlength="11" value="{{ old('phone', $editingAdmin?->phone) }}" placeholder="09xxxxxxxxx" required>
            </div>
            <div class="admin-form-row">
              <div class="admin-field">
                <label for="role">نقش</label>
                <select class="admin-input" id="role" name="role" required>
                  <option value="admin" @selected(old('role', $editingAdmin?->role) === 'admin')>مدیر</option>
                  <option value="leader" @selected(old('role', $editingAdmin?->role) === 'leader')>رهبر</option>
                </select>
              </div>
              <label class="admin-check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingAdmin?->is_active ?? true))>
                اجازه ورود فعال باشد
              </label>
            </div>
            <div class="admin-field">
              <label for="password">رمز عبور {{ $editingAdmin ? '(برای حفظ رمز فعلی خالی بگذارید)' : '' }}</label>
              <input class="admin-input" id="password" type="password" name="password" minlength="8" {{ $editingAdmin ? '' : 'required' }} autocomplete="new-password">
            </div>
            <div class="admin-field">
              <label for="password_confirmation">تکرار رمز عبور</label>
              <input class="admin-input" id="password_confirmation" type="password" name="password_confirmation" minlength="8" {{ $editingAdmin ? '' : 'required' }} autocomplete="new-password">
            </div>
            <p class="admin-hint">سطوح دسترسی جزئی در گام بعدی اضافه می‌شود. فعلاً رهبر و مدیر هر دو وارد پنل می‌شوند، اما فقط رهبر می‌تواند حساب مدیران را مدیریت کند.</p>
            <button class="admin-submit" type="submit">{{ $editingAdmin ? 'ذخیره تغییرات' : 'ساخت حساب مدیر' }}</button>
            @if($editingAdmin)<a class="admin-cancel" href="{{ route('admin.settings.admins') }}">انصراف از ویرایش</a>@endif
          </form>
        </section>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
window.copyAdminPassword = async function(button, adminId) {
  const originalIcon = button.innerHTML;
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  button.disabled = true;
  button.classList.add('opacity-60', 'cursor-wait');

  try {
    const response = await fetch(`/admin/settings/admins/${adminId}/copy-password`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
      },
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'کپی رمز عبور انجام نشد.');

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
    button.title = 'رمز کپی شد';
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
</script>
@endsection
