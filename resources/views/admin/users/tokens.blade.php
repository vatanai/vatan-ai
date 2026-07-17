@extends('layouts.admin')
@section('title', 'مدیریت توکن کاربران — وطن استودیو')

@push('styles')
<style>
/* ─── صفحه‌ی مدیریت توکن — همه‌ی رنگ‌ها فقط از توکن‌های design-tokens.css (روز/شب خودکار) ─── */
.tk-grid{display:grid;grid-template-columns:400px minmax(0,1fr);gap:20px;align-items:start;}
@media(max-width:1100px){.tk-grid{grid-template-columns:1fr;}}

/* کارت‌ها (بدنه از .content-card مشترک استفاده می‌کند) */
.tk-card-header{display:flex;align-items:center;gap:8px;padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--text-h);}
.tk-card-body{padding:18px;}

/* جستجوی کاربر */
.tk-search-wrap{position:relative;}
.tk-search-wrap .input-pro{width:100%;padding-right:36px;}
.tk-search-icon{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:var(--text-soft);font-size:13px;pointer-events:none;}
.tk-result-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;margin-bottom:6px;cursor:pointer;background:var(--input-bg);transition:border-color .15s,background .15s;}
.tk-result-item:hover{border-color:var(--primary);background:var(--primary-l);}
.tk-no-result{font-size:12px;color:var(--text-soft);padding:10px 4px;text-align:center;}
.tk-avatar{width:34px;height:34px;border-radius:50%;background:var(--primary-l);border:1px solid var(--primary-m);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;}
body:not(.light) .tk-avatar{color:var(--accent);border-color:var(--border);}
.tk-user-name{font-size:12.5px;font-weight:700;color:var(--text-h);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tk-user-meta{font-size:10.5px;color:var(--text-soft);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tk-token-badge{font-size:12px;font-weight:800;color:var(--success);white-space:nowrap;}

/* کاربر انتخاب‌شده */
.tk-selected{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg);}
.tk-sel-token{font-size:20px;font-weight:800;color:var(--success);line-height:1.2;}
.tk-sel-token-label{font-size:10px;color:var(--text-soft);margin-bottom:2px;}
.tk-clear-btn{background:none;border:none;color:var(--text-soft);font-size:11px;cursor:pointer;font-family:inherit;padding:0;margin-top:10px;transition:color .15s;}
.tk-clear-btn:hover{color:var(--danger);}

/* فرم عملیات توکن */
.tk-form-group{margin-bottom:14px;}
.tk-label{display:block;font-size:12px;font-weight:600;color:var(--text-main);margin-bottom:6px;}
.tk-form-group .input-pro{width:100%;}

/* باکس میانبرهای افزودن/کسر (۱ / ۵ / ۱۰ / ۲۰ / ۵۰) — زیر فیلد مقدار توکن */
.tk-quick-box{background:var(--input-bg);border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:14px;}
.tk-quick-title{display:flex;align-items:center;gap:6px;font-size:10.5px;font-weight:700;color:var(--text-soft);margin-bottom:8px;}
.tk-quick-row{display:flex;gap:6px;}
.tk-quick-row + .tk-quick-row{margin-top:6px;}
.tk-chip{flex:1;height:32px;border-radius:9px;border:1px solid var(--border);background:var(--card-bg);color:var(--text-main);font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;}
.tk-chip-add:hover{border-color:var(--success);color:var(--success);background:var(--success-l);}
.tk-chip-deduct:hover{border-color:var(--danger);color:var(--danger);background:var(--danger-l);}

/* پیش‌نمایش موجودی پس از اعمال */
.tk-preview{display:none;font-size:11px;color:var(--text-soft);margin:-6px 0 14px;}
.tk-preview b{color:var(--text-h);}
.tk-preview.is-danger, .tk-preview.is-danger b{color:var(--danger);}

/* دکمه‌ی اعمال (پایه از .btn-pro مشترک) */
.tk-submit{width:100%;height:42px;justify-content:center;font-size:13px;}

/* تاریخچه */
#tkHistoryList{max-height:calc(100vh - 210px);overflow-y:auto;}
.tk-h-item{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--divider);}
.tk-h-item:last-child{border-bottom:none;}
.tk-h-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;border:1px solid;}
.tk-h-add{background:var(--success-l);color:var(--success);border-color:var(--success-m);}
.tk-h-deduct{background:var(--danger-l);color:var(--danger);border-color:var(--danger-m);}
.tk-h-set{background:var(--primary-l);color:var(--primary);border-color:var(--primary-m);}
body:not(.light) .tk-h-set{color:var(--accent);}
.tk-h-user{font-size:12.5px;font-weight:700;color:var(--text-h);}
.tk-h-desc{font-size:11px;color:var(--text-soft);margin-top:2px;}
.tk-h-amount{font-size:13.5px;font-weight:800;white-space:nowrap;}
.tk-amt-add{color:var(--success);}
.tk-amt-deduct{color:var(--danger);}
.tk-amt-set{color:var(--primary);}
body:not(.light) .tk-amt-set{color:var(--accent);}
.tk-h-meta{font-size:10px;color:var(--text-soft);margin-top:2px;white-space:nowrap;}
.tk-h-loading{font-size:12px;color:var(--text-soft);text-align:center;padding:28px 16px;}

/* توست نتیجه‌ی عملیات */
.tk-toast{position:fixed;bottom:24px;left:24px;z-index:400;display:flex;align-items:center;gap:10px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:12px 18px;font-size:12.5px;font-weight:700;box-shadow:var(--shadow-card);transform:translateY(90px);opacity:0;transition:all .3s;pointer-events:none;}
.tk-toast.show{transform:translateY(0);opacity:1;}
.tk-toast.success{border-color:var(--success-m);color:var(--success);}
.tk-toast.error{border-color:var(--danger-m);color:var(--danger);}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">

    <div class="tk-grid">

      {{-- ستون راست: جستجوی کاربر + کاربر انتخاب‌شده + فرم عملیات توکن --}}
      <div>
        @include('admin.users.partials.token-search')
        @include('admin.users.partials.token-form')
      </div>

      {{-- ستون چپ: تاریخچه‌ی تغییرات توکن --}}
      @include('admin.users.partials.token-history')

    </div>

  </div>
</main>

{{-- توست نتیجه‌ی عملیات --}}
<div class="tk-toast" id="tkToast"></div>
@endsection

@section('scripts')
  @include('admin.users.partials.token-scripts')
@endsection
