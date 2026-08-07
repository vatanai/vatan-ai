@extends('layouts.admin')
@section('title', 'لیست پروایدرهای هوش مصنوعی — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    @if(session('success'))
      <div class="content-card mb-4 flex items-center gap-2 border-[var(--success)]/30 text-[var(--success)]" role="status"><i class="fa-solid fa-circle-check"></i><span class="text-xs font-bold">{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="content-card mb-4 border-[var(--danger)]/30 text-[var(--danger)]"><div class="text-xs font-bold mb-2">خطا در ذخیره یا بررسی تنظیمات</div><ul class="m-0 pr-5 text-xs leading-7 list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="flex items-start justify-between gap-4 flex-wrap mb-5">
      <div>
        <h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">لیست پروایدرها</h1>
        <p class="text-xs text-[var(--text-soft)] m-0">اتصال سرویس‌ها، ثبت امن کلیدها و فعال‌سازی Providerها</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.ai-models.sync-catalog') }}">
          @csrf
          <input type="hidden" name="provider" value="all">
          <button class="btn-pro btn-pro-ghost" type="submit"><i class="fa-solid fa-arrows-rotate ml-1"></i> همگام‌سازی عکس و ویدیو</button>
        </form>
        <a href="{{ route('admin.ai-models.providers.create') }}" class="btn-pro btn-pro-primary no-underline inline-flex items-center gap-2"><i class="fa-solid fa-plus"></i> افزودن پروایدر</a>
      </div>
    </div>

    <div class="provider-board">
      @include('admin.ai-models.partials.provider-cards')
    </div>
  </div>
</main>
@endsection

@push('styles')
<style>
  .provider-board { background:transparent; }
  .provider-grid-clean { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
  .provider-card[data-provider-enabled="1"] { border-color:color-mix(in srgb, var(--success) 42%, var(--border)); }
  .provider-card[data-provider-enabled="0"] { border-color:color-mix(in srgb, var(--danger) 42%, var(--border)); }
  .provider-status-box { display:inline-flex; align-items:center; justify-content:center; min-height:25px; padding:4px 8px; border:1px solid; border-radius:7px; font-size:9px; font-weight:800; white-space:nowrap; }
  .provider-status-box.is-on { color:var(--success); border-color:color-mix(in srgb, var(--success) 32%, transparent); background:color-mix(in srgb, var(--success) 10%, transparent); }
  .provider-status-box.is-off { color:var(--danger); border-color:color-mix(in srgb, var(--danger) 32%, transparent); background:color-mix(in srgb, var(--danger) 10%, transparent); }
  .provider-toggle-btn { border:1px solid var(--border); border-radius:7px; padding:5px 8px; background:var(--card-bg); color:var(--text-soft); font-size:9px; cursor:pointer; }
  .provider-toggle-btn:hover { color:var(--primary); border-color:var(--primary); }
  .provider-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:6px; margin:0 0 8px; }
  .provider-metrics > div { min-width:0; padding:7px; border:1px solid var(--border); border-radius:8px; background:var(--card-bg); }
  .provider-metrics span,.provider-metrics strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .provider-metrics span { color:var(--text-soft); font-size:8px; }
  .provider-metrics strong { margin-top:3px; color:var(--text-h); font-size:10px; }
  .provider-key-line { margin-bottom:10px; color:var(--text-soft); font-size:9px; }
  .provider-key-line b { color:var(--text-main); }
  .provider-models-panel { margin:0 0 10px; padding:10px; border:1px solid var(--border); border-radius:9px; background:var(--card-bg); }
  .provider-models-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
  .provider-models-head strong { display:block; color:var(--text-main); font-size:10px; }
  .provider-models-head span { display:block; margin-top:2px; color:var(--text-soft); font-size:8px; }
  .provider-model-add { display:inline-flex; align-items:center; gap:4px; padding:5px 7px; border:1px solid var(--primary); border-radius:7px; color:var(--primary); font-size:8px; font-weight:800; text-decoration:none; white-space:nowrap; }
  .provider-model-add:hover { background:var(--primary-l); }
  .provider-model-list { display:grid; gap:5px; max-height:300px; overflow-y:auto; padding-left:2px; }
  .provider-model-row { display:flex; align-items:center; justify-content:space-between; gap:7px; padding:6px; border:1px solid var(--border); border-radius:7px; background:var(--input-bg); }
  .provider-model-info { display:flex; align-items:center; gap:6px; min-width:0; }
  .provider-model-media { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; flex-shrink:0; border-radius:6px; background:var(--primary-l); color:var(--primary); font-size:9px; }
  .provider-model-info strong,.provider-model-info small { display:block; max-width:210px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .provider-model-info strong { color:var(--text-main); font-size:9px; }
  .provider-model-info small { margin-top:2px; color:var(--text-soft); font-size:8px; }
  .provider-model-info .provider-model-grade { color:var(--warning); font-weight:800; }
  .provider-model-actions { display:flex; align-items:center; gap:4px; flex-shrink:0; }
  .provider-model-status { display:inline-flex; align-items:center; min-height:18px; padding:2px 5px; border:1px solid; border-radius:5px; font-size:7px; font-weight:800; }
  .provider-model-status.is-on { color:var(--success); border-color:color-mix(in srgb,var(--success) 28%,transparent); background:color-mix(in srgb,var(--success) 8%,transparent); }
  .provider-model-status.is-off { color:var(--danger); border-color:color-mix(in srgb,var(--danger) 28%,transparent); background:color-mix(in srgb,var(--danger) 8%,transparent); }
  .provider-model-action { display:inline-flex; align-items:center; justify-content:center; width:23px; height:23px; padding:0; border:1px solid var(--border); border-radius:6px; background:var(--card-bg); color:var(--text-soft); font-size:9px; text-decoration:none; cursor:pointer; }
  .provider-model-action:hover { color:var(--primary); border-color:var(--primary); }
  .provider-model-action.is-danger { color:var(--danger); }
  .provider-model-empty { padding:10px 4px; color:var(--text-soft); font-size:9px; text-align:center; }
  .provider-model-all { display:flex; align-items:center; justify-content:space-between; gap:5px; margin-top:8px; color:var(--primary); font-size:8px; font-weight:800; text-decoration:none; }
  .provider-model-all:hover { text-decoration:underline; }
  .provider-limit-panel { padding:10px; border:1px solid var(--border); border-radius:9px; background:var(--card-bg); }
  .provider-limit-head { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:9px; }
  .provider-limit-live { color:var(--text-soft); font-size:8.5px; direction:rtl; }
  .provider-limit-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; }
  .provider-limit-fields label { display:grid; gap:4px; color:var(--text-soft); font-size:9px; }
  .provider-limit-fields .input-pro { min-width:0; height:32px; padding:5px 7px; font-size:10px; }
  .provider-limit-help { margin-top:8px; color:var(--text-soft); font-size:8.5px; line-height:1.8; }
  .provider-add-card { min-height:190px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:7px; border:1px dashed var(--primary); border-radius:12px; background:color-mix(in srgb, var(--primary) 5%, transparent); color:var(--text-h); text-decoration:none; transition:all .2s ease; }
  .provider-add-card:hover { background:color-mix(in srgb, var(--primary) 10%, transparent); transform:translateY(-1px); }
  .provider-add-icon { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background:var(--primary-l); color:var(--primary); }
  .provider-add-card strong { font-size:12px; }
  .provider-add-card small { color:var(--text-soft); font-size:9px; }
  @media (max-width:900px) { .provider-grid-clean { grid-template-columns:1fr; } .provider-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:480px) { .provider-limit-fields { grid-template-columns:1fr; } .provider-model-row { align-items:flex-start; flex-direction:column; } .provider-model-actions { width:100%; justify-content:flex-end; } }
</style>
@endpush
