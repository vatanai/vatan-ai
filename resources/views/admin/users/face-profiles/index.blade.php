@extends('layouts.admin')

@section('title', 'کارکتر شیت کاربران — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/user-gallery.css') }}?v={{ filemtime(public_path('admin/css/user-gallery.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @if(session('success'))
      <div class="mb-4 p-3 rounded-xl border bg-[var(--success-l)] border-[var(--success-m)] text-[var(--success)] text-[11px]">{{ session('success') }}</div>
    @endif
    @if($errors->has('face_profile') || $errors->has('images') || $errors->has('images.0') || $errors->has('name'))
      <div class="mb-4 p-3 rounded-xl border bg-[var(--danger-l)] border-[var(--danger-m)] text-[var(--danger)] text-[11px]">{{ $errors->first('face_profile') ?: $errors->first('images') ?: $errors->first('images.0') ?: $errors->first('name') }}</div>
    @endif

    <div class="flex items-start justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-[17px] font-extrabold text-[var(--text-h)]">کارکتر شیت کاربران</h1>
        <p class="mt-1 text-[11px] text-[var(--text-soft)]">همه‌ی مرجع‌های چهره‌ی ذخیره‌شده را ببین، نامشان را تغییر بده یا از حساب کاربر حذف کن.</p>
      </div>
      <a href="{{ route('admin.users.gallery.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-[var(--card-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)] hover:border-[var(--primary)] hover:text-[var(--primary)]"><i class="fa-solid fa-photo-film"></i> گالری کاربران</a>
    </div>

    <div class="admin-face-tabs mb-5">
      <a href="{{ route('admin.users.index') }}" class="admin-face-tab"><i class="fa-solid fa-users"></i> فهرست کاربران</a>
      <a href="{{ route('admin.users.gallery.index') }}" class="admin-face-tab"><i class="fa-solid fa-images"></i> گالری ورودی‌ها</a>
      <a href="{{ route('admin.users.face-profiles.index') }}" class="admin-face-tab active"><i class="fa-solid fa-user"></i> کارکتر شیت‌ها</a>
    </div>

    <div class="grid grid-cols-4 gap-3 mb-5 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1">
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کل کارکتر شیت‌ها</span><strong class="block mt-1 text-[23px] text-[var(--text-h)]">{{ number_format($stats['all']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">پروفایل‌های فعال</span><strong class="block mt-1 text-[23px] text-[var(--success)]">{{ number_format($stats['active']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">کاربران دارای پروفایل</span><strong class="block mt-1 text-[23px] text-[var(--primary)]">{{ number_format($stats['users']) }}</strong></div>
      <div class="p-4 rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]"><span class="text-[10px] text-[var(--text-soft)]">سقف هر کاربر</span><strong class="block mt-1 text-[23px] text-[var(--info)]">۵ <small class="text-[10px] font-normal">پروفایل</small></strong></div>
    </div>

    <section class="rounded-2xl border bg-[var(--card-bg)] border-[var(--border)] overflow-hidden">
      <div class="p-4 border-b border-[var(--border)] flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h2 class="text-[12px] font-bold text-[var(--text-h)]">مدیریت مرجع‌های چهره</h2>
          <p class="mt-1 text-[10px] text-[var(--text-soft)]">برای افزودن پروفایل جدید، وارد گالری همان کاربر شو.</p>
        </div>
        <form method="GET" class="flex items-center gap-2 max-[560px]:w-full">
          <input name="q" value="{{ $search }}" placeholder="جستجوی نام کاربر یا پروفایل" class="w-56 max-[560px]:flex-1 px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]">
          <select name="status" class="px-3 py-2 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-[11px] text-[var(--text-main)]">
            <option value="active" @selected($status === 'active')>فعال</option>
            <option value="all" @selected($status === 'all')>همه</option>
            <option value="deleted" @selected($status === 'deleted')>حذف‌شده</option>
          </select>
          <button class="w-9 h-9 rounded-xl border border-[var(--border)] text-[var(--text-soft)] hover:text-[var(--primary)]" title="جستجو" aria-label="جستجو"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>

      <div class="admin-face-profile-grid p-4">
        @forelse($profiles as $profile)
          @php
            $profileUserName = trim(($profile->user?->name ?? '').' '.($profile->user?->last_name ?? '')) ?: 'کاربر حذف‌شده';
            $profileImages = $profile->referenceImageEntries();
          @endphp
          <article class="admin-face-profile-card">
            <div class="admin-face-profile-card__head">
              <div class="min-w-0"><a href="{{ route('admin.users.gallery.show', $profile->user_id) }}" class="admin-face-profile-user">{{ $profileUserName }}</a><span>{{ $profile->user?->phone ?: $profile->user?->email ?: 'شناسه کاربر: '.$profile->user_id }}</span></div>
              <span class="admin-face-profile-status {{ $profile->status === 'active' ? 'is-active' : 'is-deleted' }}">{{ $profile->status === 'active' ? 'فعال' : 'حذف‌شده' }}</span>
            </div>
            <div class="admin-face-profile-images">
              @forelse($profileImages as $image)
                @php($imagePath = (string) ($image['path'] ?? ''))
                @if(filter_var($imagePath, FILTER_VALIDATE_URL))
                  <img src="{{ $imagePath }}" alt="{{ $profile->name }}" loading="lazy">
                @else
                  <img src="{{ asset('storage/'.ltrim($imagePath, '/')) }}" alt="{{ $profile->name }}" loading="lazy">
                @endif
              @empty
                <span class="admin-face-profile-no-image"><i class="fa-solid fa-user"></i></span>
              @endforelse
            </div>
            <div class="admin-face-profile-card__meta"><span>{{ number_format(count($profileImages)) }} تصویر مرجع</span><span>{{ \App\Support\Jalali::formatNumeric($profile->created_at) }}</span></div>
            @if($profile->status === 'active')
              <form action="{{ route('admin.users.face-profiles.update', [$profile->user_id, $profile]) }}" method="POST" class="admin-face-profile-rename">
                @csrf
                @method('PATCH')
                <input type="text" name="name" value="{{ $profile->name }}" maxlength="80" aria-label="نام {{ $profile->name }}" required>
                <button type="submit" title="ذخیره نام" aria-label="ذخیره نام"><i class="fa-solid fa-check"></i></button>
              </form>
              <div class="admin-face-profile-actions">
                <a href="{{ route('admin.users.gallery.show', $profile->user_id) }}#face-profiles"><i class="fa-solid fa-images"></i> گالری کاربر</a>
                <form action="{{ route('admin.users.face-profiles.destroy', [$profile->user_id, $profile]) }}" method="POST" onsubmit="return confirm('این کارکتر شیت از حساب کاربر حذف شود؟');">@csrf @method('DELETE')<button type="submit"><i class="fa-solid fa-trash-can"></i> حذف</button></form>
              </div>
            @endif
          </article>
        @empty
          <div class="admin-face-profile-empty"><i class="fa-solid fa-user"></i><strong>کارکتر شیتی پیدا نشد.</strong><span>با جستجوی دیگری امتحان کن یا از گالری یک کاربر پروفایل جدید اضافه کن.</span></div>
        @endforelse
      </div>
      <div class="p-4 border-t border-[var(--border)]">{{ $profiles->links() }}</div>
    </section>
  </div>
</main>
@endsection
