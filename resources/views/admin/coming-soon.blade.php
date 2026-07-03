@extends('layouts.admin')
@section('title', 'در دست ساخت — وطن استودیو Admin')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px] flex items-center justify-center" id="content">
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:16px;text-align:center;padding:24px;">
      <div style="width:80px;height:80px;border-radius:20px;background:var(--primary-l);border:1px solid var(--primary-m);display:flex;align-items:center;justify-content:center;font-size:30px;color:var(--primary);">
        <i class="fa-regular fa-clock"></i>
      </div>
      <div style="font-size:18px;font-weight:800;color:var(--text-h);">این بخش هنوز آماده نمایش نیست</div>
      <div style="font-size:13px;color:var(--text-soft);max-width:420px;line-height:1.9;">
        این قسمت از پنل مدیریت در حال توسعه است و به‌زودی فعال می‌شود. فایل بک‌اند این بخش هنوز پیاده‌سازی نشده.
      </div>
      <a href="{{ route('admin.dashboard') }}" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;font-size:12.5px;font-weight:700;background:var(--primary);color:var(--accent);text-decoration:none;">
        <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i> بازگشت به مرکز فرماندهی
      </a>
    </div>
  </div>
</main>
@endsection
