@extends('layouts.admin')
@section('title', 'مدیریت ادمین‌ها — وطن استودیو Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#fff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
.admin-content{padding:24px;flex:1;}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:16px;text-align:center;">
      <div style="width:80px;height:80px;border-radius:20px;background:rgba(160,122,245,.08);border:1px solid rgba(160,122,245,.2);display:flex;align-items:center;justify-content:center;font-size:32px;">👤</div>
      <div style="font-size:18px;font-weight:800;color:var(--text);">مدیریت ادمین‌ها</div>
      <div style="font-size:13px;color:var(--text2);max-width:400px;line-height:1.8;">افزودن، ویرایش و حذف کاربران ادمین پنل با سطوح دسترسی متفاوت در دست ساخت است.</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:8px;"><span style="font-size:11px;padding:4px 12px;border-radius:99px;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);">ادمین کل</span><span style="font-size:11px;padding:4px 12px;border-radius:99px;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);">مدیر میانی</span></div>
    </div>
  </div>
</main>
@endsection
