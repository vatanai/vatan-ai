@extends('layouts.admin')
@section('title', 'تنظیمات سیستم — وطن استودیو Admin')

@push('styles')
<style>
:root{--bg:#0c0c10;--s1:#111116;--s2:#16161c;--b1:#222230;--b2:#2e2e3e;--text:#fff;--text2:#a8c4a8;--text3:#4d7a56;--green:#0BBF53;--accent:#a07af5;--red:#f05c5c;--orange:#f5923a;}
*{box-sizing:border-box;}
body{font-family:'Vazirmatn',sans-serif;background:var(--bg);color:var(--text);direction:rtl;}
.admin-wrap{display:flex;min-height:100vh;}
.admin-main{margin-right:256px;flex:1;display:flex;flex-direction:column;}
.admin-header{position:sticky;top:0;z-index:50;background:var(--s1);border-bottom:1px solid var(--b1);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;}
.admin-content{padding:24px;flex:1;}
</style>
@endpush

@section('content')
<header class="admin-header">
  <div style="font-size:14px;font-weight:700;">تنظیمات سیستم</div>
  <div style="flex:1;"></div>
</header>
<main class="admin-content">
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:16px;text-align:center;">
    <div style="width:80px;height:80px;border-radius:20px;background:rgba(160,122,245,.08);border:1px solid rgba(160,122,245,.2);display:flex;align-items:center;justify-content:center;font-size:32px;">⚙️</div>
    <div style="font-size:18px;font-weight:800;color:var(--text);">تنظیمات سیستم</div>
    <div style="font-size:13px;color:var(--text2);max-width:400px;line-height:1.8;">پیکربندی عمومی سیستم شامل کوتا پیش‌فرض، نرخ‌ها و رفتار سیستم در دست ساخت است.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:8px;"><span style="font-size:11px;padding:4px 12px;border-radius:99px;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);">کوتا پیش‌فرض</span><span style="font-size:11px;padding:4px 12px;border-radius:99px;background:rgba(160,122,245,.08);color:var(--accent);border:1px solid rgba(160,122,245,.2);">تنظیمات عمومی</span></div>
  </div>
</main>
@endsection
