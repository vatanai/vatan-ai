@extends('layouts.admin')

@section('title', 'تولید خودکار ویدیو — وطن استودیو')

@push('styles')
<style>
  .video-studio-page{background:var(--page-bg);min-height:calc(100vh - 68px);padding:24px;direction:rtl}
  .video-studio-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap}
  .video-studio-title{font-size:22px;font-weight:900;color:var(--text-h);letter-spacing:-.3px}
  .video-studio-subtitle{font-size:12px;color:var(--text-soft);margin-top:5px}
  .video-studio-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .studio-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--card-bg);color:var(--text-main);font-size:12px;font-weight:800;text-decoration:none;transition:.2s}
  .studio-btn:hover{border-color:var(--primary);color:var(--primary)}
  .studio-btn.primary{background:var(--primary);border-color:var(--primary);color:var(--accent)}
  .studio-btn.primary:hover{filter:brightness(1.08)}
  .studio-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px}
  .studio-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-card)}
  .studio-kpi{padding:15px 16px;min-height:122px;position:relative;overflow:hidden}
  .studio-kpi:before{content:"";position:absolute;right:0;top:0;bottom:0;width:3px;background:var(--primary);border-radius:0 14px 14px 0}
  .studio-kpi.success:before{background:var(--success)}.studio-kpi.warning:before{background:var(--warning)}.studio-kpi.danger:before{background:var(--danger)}.studio-kpi.info:before{background:var(--info)}
  .studio-kpi-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--primary-l);color:var(--primary);margin-bottom:11px}
  .studio-kpi.success .studio-kpi-icon{background:var(--success-l);color:var(--success)}.studio-kpi.warning .studio-kpi-icon{background:var(--warning-l);color:var(--warning)}.studio-kpi.danger .studio-kpi-icon{background:var(--danger-l);color:var(--danger)}.studio-kpi.info .studio-kpi-icon{background:var(--info-l);color:var(--info)}
  .studio-kpi-value{font-size:23px;line-height:1;font-weight:900;color:var(--text-h);font-variant-numeric:tabular-nums}
  .studio-kpi-label{font-size:11px;color:var(--text-soft);margin-top:5px}
  .studio-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:16px;margin-bottom:16px}
  .studio-panel{padding:18px}
  .studio-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
  .studio-panel-title{font-size:13px;font-weight:900;color:var(--text-h);display:flex;align-items:center;gap:7px}.studio-panel-title i{color:var(--primary)}
  .studio-panel-meta{font-size:10px;color:var(--text-soft)}
  .studio-chart{display:flex;align-items:flex-end;gap:8px;height:180px;padding:10px 3px 22px;border-bottom:1px solid var(--divider)}
  .studio-bar{flex:1;min-width:8px;height:100%;display:flex;align-items:flex-end;position:relative}
  .studio-bar-fill{width:100%;min-height:3px;border-radius:6px 6px 2px 2px;background:var(--primary);opacity:.82;transition:height .25s}
  .studio-bar-label{position:absolute;bottom:-19px;right:50%;transform:translateX(50%);font-size:9px;color:var(--text-soft);white-space:nowrap}
  .studio-bar-value{position:absolute;top:-17px;right:50%;transform:translateX(50%);font-size:9px;color:var(--text-soft);opacity:0;transition:opacity .2s}.studio-bar:hover .studio-bar-value{opacity:1}
  .studio-health{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  .studio-health-item{padding:12px;border:1px solid var(--border);border-radius:11px;background:var(--input-bg)}
  .studio-health-label{font-size:10px;color:var(--text-soft);margin-bottom:7px}.studio-health-value{font-size:17px;font-weight:900;color:var(--text-h)}
  .studio-progress{height:6px;background:var(--border);border-radius:99px;overflow:hidden;margin-top:8px}.studio-progress>span{display:block;height:100%;background:var(--primary);border-radius:inherit}
  .studio-table-wrap{overflow:auto}.studio-table{width:100%;border-collapse:collapse;min-width:650px}.studio-table th{font-size:10px;color:var(--text-soft);font-weight:800;text-align:right;padding:9px 10px;border-bottom:1px solid var(--divider);white-space:nowrap}.studio-table td{font-size:11px;color:var(--text-main);padding:11px 10px;border-bottom:1px solid var(--divider);vertical-align:middle}.studio-table tr:last-child td{border-bottom:0}.studio-table tr:hover td{background:var(--input-bg)}
  .studio-product{font-weight:800;color:var(--text-h)}.studio-muted{font-size:10px;color:var(--text-soft);margin-top:3px}
  .studio-badge{display:inline-flex;align-items:center;gap:4px;border-radius:99px;padding:4px 8px;font-size:10px;font-weight:800;border:1px solid}.studio-badge.success{background:var(--success-l);color:var(--success);border-color:var(--success-m)}.studio-badge.warning{background:var(--warning-l);color:var(--warning);border-color:var(--warning-m)}.studio-badge.danger{background:var(--danger-l);color:var(--danger);border-color:var(--danger-m)}.studio-badge.neutral{background:var(--input-bg);color:var(--text-soft);border-color:var(--border)}
  .studio-empty{padding:28px;text-align:center;color:var(--text-soft);font-size:11px;border:1px dashed var(--border);border-radius:11px;background:var(--input-bg)}
  .studio-source{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--divider)}.studio-source:last-child{border-bottom:0}.studio-source-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--primary-l);color:var(--primary)}.studio-source-name{font-size:11px;font-weight:800;color:var(--text-h);flex:1}.studio-source-status{font-size:10px;color:var(--success);font-weight:800}
  .studio-modal{position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;padding:20px;background:color-mix(in srgb,var(--text-h) 45%,transparent)}.studio-modal.is-open{display:flex}.studio-modal-card{width:min(720px,100%);max-height:min(78vh,720px);overflow:hidden;background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-card);display:flex;flex-direction:column}.studio-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--divider)}.studio-modal-title{font-size:14px;font-weight:900;color:var(--text-h)}.studio-modal-close{border:0;background:var(--input-bg);color:var(--text-main);width:32px;height:32px;border-radius:8px;cursor:pointer}.studio-modal-tools{display:grid;grid-template-columns:minmax(0,1fr) 150px;gap:8px;padding:12px 18px;border-bottom:1px solid var(--divider)}.studio-product-list{overflow:auto;padding:10px 18px 16px;display:grid;gap:7px}.studio-product-choice{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);color:var(--text-main);cursor:pointer;text-align:right}.studio-product-choice:hover,.studio-product-choice:focus{border-color:var(--primary);outline:0}.studio-product-choice.is-covered{opacity:.7}.studio-product-choice-main{min-width:0}.studio-product-choice-name{font-size:11px;font-weight:900;color:var(--text-h)}.studio-product-choice-meta{font-size:10px;color:var(--text-soft);margin-top:3px}.studio-product-choice-status{font-size:10px;font-weight:800;color:var(--success);white-space:nowrap}.studio-selected-product{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--primary);border-radius:10px;background:var(--primary-l)}.studio-selected-product-name{font-size:12px;font-weight:900;color:var(--text-h)}.studio-manual-box{display:grid;gap:6px;padding:10px;border:1px dashed var(--border);border-radius:10px;background:var(--input-bg)}
  .studio-settings{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(300px,.85fr);gap:16px}.studio-form{display:grid;gap:13px}.studio-field{display:grid;gap:6px}.studio-field label{font-size:11px;font-weight:800;color:var(--text-h)}.studio-field small{font-size:10px;color:var(--text-soft)}.studio-input,.studio-select,.studio-textarea{width:100%;border:1px solid var(--border);border-radius:9px;background:var(--input-bg);color:var(--text-main);font:inherit;font-size:11px;padding:10px 11px;outline:0}.studio-input:focus,.studio-select:focus,.studio-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-l)}.studio-textarea{min-height:82px;resize:vertical}.studio-options{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px}.studio-option{position:relative}.studio-option input{position:absolute;opacity:0;pointer-events:none}.studio-option label{display:flex;flex-direction:column;gap:5px;align-items:center;justify-content:center;min-height:64px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);padding:8px;text-align:center;cursor:pointer;font-size:10px;color:var(--text-soft);transition:.2s}.studio-option label i{font-size:15px;color:var(--primary)}.studio-option input:checked+label{border-color:var(--primary);background:var(--primary-l);color:var(--text-h);box-shadow:inset 0 0 0 1px var(--primary)}.studio-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.studio-check{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid var(--border);border-radius:9px;background:var(--input-bg);font-size:10px;color:var(--text-main);cursor:pointer}.studio-check input{accent-color:var(--primary);width:15px;height:15px}.studio-alert{padding:10px 12px;border-radius:9px;background:var(--success-l);color:var(--success);font-size:11px;font-weight:800;margin-bottom:15px}.studio-error{padding:8px 10px;border-radius:8px;background:var(--danger-l);color:var(--danger);font-size:10px}.studio-conditional.is-hidden{display:none}.studio-hook-list{display:grid;gap:8px;max-height:390px;overflow:auto}.studio-hook{padding:11px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg)}.studio-hook-top{display:flex;align-items:center;justify-content:space-between;gap:8px}.studio-hook-title{font-size:11px;font-weight:900;color:var(--text-h)}.studio-hook-text{font-size:11px;color:var(--text-main);line-height:1.8;margin-top:6px}.studio-hook-tags{font-size:9px;color:var(--text-soft);margin-top:5px}.studio-link-btn{border:0;background:transparent;color:var(--danger);cursor:pointer;font-size:10px;padding:3px}
  .studio-conditional.is-hidden{display:none}.studio-images{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.studio-image-choice{position:relative}.studio-image-choice input{position:absolute;opacity:0}.studio-image-choice label{display:block;aspect-ratio:1;border:2px solid var(--border);border-radius:9px;overflow:hidden;cursor:pointer;background:var(--input-bg);transition:.2s}.studio-image-choice img{width:100%;height:100%;object-fit:cover}.studio-image-choice input:checked+label{border-color:var(--primary);box-shadow:0 0 0 2px var(--primary-l)}.studio-image-choice input:checked+label:after{content:'✓';position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--primary);color:var(--accent);font-size:12px;font-weight:900}.studio-queue{display:grid;gap:8px}.studio-job{display:grid;grid-template-columns:minmax(0,1fr) auto auto auto;align-items:center;gap:12px;padding:11px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg)}.studio-job-main{min-width:0}.studio-job-title{font-size:11px;font-weight:900;color:var(--text-h);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.studio-job-meta{font-size:10px;color:var(--text-soft);margin-top:4px}.studio-job-status{font-size:10px;font-weight:800}.studio-job-status.queued{color:var(--warning)}.studio-job-status.processing{color:var(--info)}.studio-job-status.completed{color:var(--success)}.studio-job-status.failed{color:var(--danger)}.studio-job-actions{display:flex;align-items:center;gap:4px}.studio-job-edit-toggle{border:1px solid var(--border);background:var(--card-bg);color:var(--text-soft);border-radius:8px;padding:5px 8px;font-size:10px;cursor:pointer}.studio-job-edit-toggle:hover{border-color:var(--primary);color:var(--primary)}.studio-job-editor{grid-column:1/-1;display:grid;gap:8px;padding:10px;border-top:1px solid var(--divider);margin-top:2px}.studio-job-editor.is-hidden{display:none}.studio-job-editor textarea{min-height:66px}.studio-job-editor small{font-size:10px;color:var(--text-soft)}
  @media(max-width:1100px){.studio-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.studio-layout,.studio-settings{grid-template-columns:1fr}}
  @media(max-width:650px){.video-studio-page{padding:15px}.studio-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.studio-kpi{min-height:108px;padding:12px}.studio-kpi-value{font-size:20px}.studio-health{grid-template-columns:1fr}.video-studio-title{font-size:19px}}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="video-studio-page" id="content">
    @if(session('success'))<div class="studio-alert"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="studio-error">اطلاعات کامل نیست؛ {{ $errors->first() }}</div>@endif
    <div class="video-studio-head">
      <div>
        <div class="video-studio-title">تولید خودکار ویدیو</div>
        <div class="video-studio-subtitle">مدیریت محصولات، خروجی‌ها، خطاها و وضعیت پایپ‌لاین در یک نمای واحد</div>
      </div>
      <div class="video-studio-actions">
        <a class="studio-btn" href="{{ route('admin.products') }}"><i class="fa-solid fa-box-open"></i> محصولات</a>
        <a class="studio-btn" href="https://docs.google.com/spreadsheets/d/1r44fnFeUL6ndq_XmVP0XNW6J16Kekz_6pBEnL_WHDa4/edit" target="_blank" rel="noopener"><i class="fa-solid fa-table-list"></i> گزارش شیت</a>
        <a class="studio-btn primary" href="#studio-settings"><i class="fa-solid fa-clapperboard"></i> ساخت ویدیو</a>
      </div>
    </div>

    <div class="studio-grid">
      <div class="studio-card studio-kpi"><div class="studio-kpi-icon"><i class="fa-solid fa-clapperboard"></i></div><div class="studio-kpi-value">{{ number_format($videoCount) }}</div><div class="studio-kpi-label">کل خروجی‌های ویدیو</div></div>
      <div class="studio-card studio-kpi success"><div class="studio-kpi-icon"><i class="fa-solid fa-circle-check"></i></div><div class="studio-kpi-value">{{ number_format($completedCount) }}</div><div class="studio-kpi-label">تولیدهای موفق</div></div>
      <div class="studio-card studio-kpi warning"><div class="studio-kpi-icon"><i class="fa-solid fa-spinner"></i></div><div class="studio-kpi-value">{{ number_format($processingCount) }}</div><div class="studio-kpi-label">در صف پردازش</div></div>
      <div class="studio-card studio-kpi danger"><div class="studio-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="studio-kpi-value">{{ number_format($failedCount) }}</div><div class="studio-kpi-label">خروجی ناموفق</div></div>
      <div class="studio-card studio-kpi info"><div class="studio-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div class="studio-kpi-value">{{ number_format($coveredProducts) }} / {{ number_format($activeProducts) }}</div><div class="studio-kpi-label">محصول دارای خروجی / فعال</div></div>
    </div>

    <div class="studio-settings" style="margin-bottom:16px">
      <section class="studio-card studio-panel" id="studio-settings">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-sliders"></i> تنظیمات ساخت</div><div class="studio-panel-meta">قابل تغییر برای هر محصول</div></div>
        <form id="studio-settings-form" class="studio-form" method="POST" action="{{ route('admin.video-studio.settings.update') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_method" id="studio-form-method" value="PATCH">
          <div class="studio-field"><label>محصول هدف</label><input type="hidden" id="studio-product" name="product_id" value="{{ $selectedProductId }}"><div class="studio-selected-product"><div class="studio-selected-product-name">{{ $selectedProduct?->name_fa ?? 'تنظیمات پیش‌فرض همه محصولات' }}</div><button class="studio-btn" type="button" id="open-product-picker"><i class="fa-solid fa-magnifying-glass"></i> انتخاب محصول</button></div><small>با انتخاب محصول از پنجره‌ی جست‌وجو، تصاویر همان محصول پایین همین بخش نمایش داده می‌شود.</small></div>
          @if($selectedProduct)
            <div class="studio-field"><label>تصاویر محصول برای ویدیو</label><div class="studio-images">@forelse($productImages as $image)<div class="studio-image-choice"><input id="studio-image-{{ $loop->index }}" type="checkbox" name="selected_images[]" value="{{ $image['url'] }}" checked><label for="studio-image-{{ $loop->index }}"><img src="{{ $image['url'] }}" alt="تصویر {{ $loop->iteration }}"></label></div>@empty<div class="studio-empty" style="grid-column:1/-1">برای این محصول تصویر قابل استفاده پیدا نشد.</div>@endforelse</div><small>تصویرهای انتخاب‌شده همراه سفارش ساخت به ورکفلو ارسال می‌شوند.</small></div>
          @endif
          <input type="hidden" name="font_family" value="B_Yekan">
          <div class="studio-field"><label>منبع صدا</label><div class="studio-options" id="source-options">
            @foreach(['auto'=>['fa-wand-magic-sparkles','خودکار'],'upload'=>['fa-file-audio','فایل مستقیم'],'music'=>['fa-music','فایل موزیک'],'video'=>['fa-film','ویدیوی منبع']] as $mode=>$option)
              <div class="studio-option"><input id="source-{{ $mode }}" type="radio" name="source_mode" value="{{ $mode }}" @checked(($settings->source_mode ?? 'auto') === $mode)><label for="source-{{ $mode }}"><i class="fa-solid {{ $option[0] }}"></i>{{ $option[1] }}</label></div>
            @endforeach
          </div><small id="source-help">منبع انتخابی بعد از اتصال به ورکفلو، هنگام ساخت استفاده می‌شود.</small></div>
          <div class="studio-field" id="source-url-field"><label for="source-url">منبع صدا</label><input id="source-url" class="studio-input" type="url" name="source_url" value="{{ old('source_url', $settings->source_url) }}" placeholder="لینک فایل موزیک یا ویدیوی منبع"><input class="studio-input" type="file" name="source_file" accept="audio/*,video/mp4,video/quicktime,video/webm"><small id="source-url-help">می‌توانی لینک بدهی یا فایل موزیک/ویدیوی منبع را مستقیم انتخاب کنی.</small></div>
          <div class="studio-field"><label>قاب خروجی</label><div class="studio-options">
            @foreach(['9:16'=>['fa-mobile-screen-button','استوری عمودی'],'1:1'=>['fa-square','مربع'],'4:5'=>['fa-image','پست عمودی'],'16:9'=>['fa-display','افقی']] as $ratio=>$option)
              <div class="studio-option"><input id="ratio-{{ str_replace(':','-',$ratio) }}" type="radio" name="aspect_ratio" value="{{ $ratio }}" @checked(($settings->aspect_ratio ?? '9:16') === $ratio)><label for="ratio-{{ str_replace(':','-',$ratio) }}"><i class="fa-solid {{ $option[0] }}"></i>{{ $option[1] }}<span dir="ltr">{{ $ratio }}</span></label></div>
            @endforeach
          </div><small>حالت پیش‌فرض استوری است و برای هر خروجی قابل تغییر است.</small></div>
          <div class="studio-field"><label>کنترل‌های هوشمند</label><div class="studio-checks">
            <label class="studio-check"><input type="hidden" name="auto_generate_hook" value="0"><input type="checkbox" name="auto_generate_hook" value="1" @checked($settings->auto_generate_hook)> ساخت هوک با هوش مصنوعی</label>
            <label class="studio-check"><input type="hidden" name="auto_generate_caption" value="0"><input type="checkbox" name="auto_generate_caption" value="1" @checked($settings->auto_generate_caption)> ساخت کپشن با هوش مصنوعی</label>
            <label class="studio-check"><input type="hidden" name="auto_generate_keyword" value="0"><input id="auto-keyword-toggle" type="checkbox" name="auto_generate_keyword" value="1" @checked($settings->auto_generate_keyword)> پیشنهاد کلمه کلیدی دایرکت</label>
            <label class="studio-check"><input type="hidden" name="approval_required" value="0"><input type="checkbox" name="approval_required" value="1" @checked($settings->approval_required)> تأیید انسانی قبل از نهایی شدن</label>
          </div></div>
          <div class="studio-field studio-conditional" id="hook-manual"><label for="hook-text-manual">هوک دستی</label><div class="studio-manual-box"><input id="hook-text-manual" class="studio-input" name="hook_text" value="{{ old('hook_text', $settings->hook_text ?? '') }}" placeholder="وقتی ساخت هوک خودکار خاموش است، هوک را اینجا بنویس."><small>با روشن بودن ساخت خودکار، این مقدار در سفارش ارسال نمی‌شود.</small></div></div>
          <div class="studio-field studio-conditional" id="caption-manual"><label for="caption-text-manual">کپشن دستی</label><div class="studio-manual-box"><textarea id="caption-text-manual" class="studio-textarea" name="caption_text" placeholder="وقتی ساخت کپشن خودکار خاموش است، کپشن را اینجا بنویس.">{{ old('caption_text', $settings->caption_text ?? '') }}</textarea><small>با روشن بودن ساخت خودکار، کپشن توسط مدل کم‌هزینه تولید می‌شود.</small></div></div>
          <div class="studio-field"><label for="hook-guidelines">نکات اختصاصی هوک</label><textarea id="hook-guidelines" class="studio-textarea" name="hook_guidelines" placeholder="مثلاً: کنجکاوی ایجاد کن، کوتاه و محاوره‌ای بنویس...">{{ old('hook_guidelines', $settings->hook_guidelines) }}</textarea></div>
          <div class="studio-field"><label for="caption-guidelines">نکات اختصاصی کپشن</label><textarea id="caption-guidelines" class="studio-textarea" name="caption_guidelines" placeholder="لحن، طول، هشتگ‌ها و نکاتی که باید رعایت شود...">{{ old('caption_guidelines', $settings->caption_guidelines) }}</textarea></div>
          <div class="studio-field studio-conditional" id="keyword-settings"><label for="keyword">کلمه کلیدی و متن پاسخ دایرکت دستی</label><div class="studio-manual-box"><input id="keyword" class="studio-input" name="keyword" value="{{ old('keyword', $settings->keyword) }}" placeholder="مثلاً: قیمت"><textarea class="studio-textarea" name="dm_template" placeholder="متن آماده پاسخ به کامنت یا دایرکت...">{{ old('dm_template', $settings->dm_template) }}</textarea><small>با خاموش کردن پیشنهاد خودکار، این دو مقدار برای همان محصول استفاده می‌شوند.</small></div></div>
          <div class="video-studio-actions"><button class="studio-btn primary" type="button" onclick="submitStudioForm('{{ route('admin.video-studio.settings.update') }}','PATCH')"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات</button><button id="queue-submit" class="studio-btn" type="button" onclick="submitStudioForm('{{ route('admin.video-studio.jobs.store') }}','POST')"><i class="fa-solid fa-list-check"></i> ذخیره و افزودن به صف ساخت</button></div>
          <small>«ذخیره تنظیمات» فقط تنظیمات را نگه می‌دارد؛ برای ساخت، «ذخیره و افزودن به صف ساخت» را بزنید.</small>
        </form>
      </section>

      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-lightbulb"></i> کتابخانه هوک</div><div class="studio-panel-meta">ایده‌های قابل استفاده برای هوش مصنوعی</div></div>
        <form class="studio-form" method="POST" action="{{ route('admin.video-studio.hooks.store') }}" style="margin-bottom:14px">
          @csrf
          <input type="hidden" name="product_id" value="{{ $selectedProductId }}">
          <div class="studio-field"><label for="hook-title">عنوان ایده</label><input id="hook-title" class="studio-input" name="title" required placeholder="مثلاً: سؤال چالشی قبل از خرید"></div>
          <div class="studio-field"><label for="hook-text">متن هوک</label><textarea id="hook-text" class="studio-textarea" name="hook_text" required placeholder="متن کوتاه و الهام‌بخش هوک..."></textarea></div>
          <div class="studio-field"><label for="hook-tags">برچسب‌ها</label><input id="hook-tags" class="studio-input" name="tags" placeholder="کودک، هدیه، مقایسه"></div>
          <button class="studio-btn" type="submit"><i class="fa-solid fa-plus"></i> افزودن هوک</button>
        </form>
        <div class="studio-hook-list">
          @forelse($hookInspirations as $hook)
            <div class="studio-hook"><div class="studio-hook-top"><div class="studio-hook-title">{{ $hook->title }}</div><form method="POST" action="{{ route('admin.video-studio.hooks.destroy', $hook) }}" onsubmit="return confirm('این هوک حذف شود؟')">@csrf @method('DELETE')<button class="studio-link-btn" type="submit" title="حذف"><i class="fa-solid fa-trash"></i></button></form></div><div class="studio-hook-text">{{ $hook->hook_text }}</div>@if($hook->tags)<div class="studio-hook-tags"># {{ $hook->tags }}</div>@endif</div>
          @empty
            <div class="studio-empty">هنوز ایده‌ای ثبت نشده است. چند هوک موفق خودت را اینجا اضافه کن تا هوش مصنوعی از ساختارشان الهام بگیرد.</div>
          @endforelse
        </div>
      </section>
    </div>

    <div class="studio-modal" id="product-picker" role="dialog" aria-modal="true" aria-labelledby="product-picker-title">
      <div class="studio-modal-card">
        <div class="studio-modal-head"><div class="studio-modal-title" id="product-picker-title">انتخاب محصول برای ساخت ویدیو</div><button class="studio-modal-close" type="button" id="close-product-picker" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="studio-modal-tools"><input class="studio-input" id="product-picker-search" type="search" placeholder="جست‌وجوی نام یا شناسه محصول"><select class="studio-select" id="product-picker-sort"><option value="newest">جدیدترین</option><option value="oldest">قدیمی‌ترین</option><option value="name_asc">نام: الف تا ی</option><option value="name_desc">نام: ی تا الف</option></select></div>
        <div class="studio-product-list" id="product-picker-list">
          @foreach($products as $product)
            @php($covered = $producedProducts->contains('product_id', $product->id))
            <button type="button" class="studio-product-choice {{ $covered ? 'is-covered' : '' }}" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name_fa }}" data-product-search="{{ mb_strtolower($product->name_fa . ' ' . $product->slug . ' ' . $product->id) }}" data-product-order="{{ optional($product->created_at)->timestamp ?? 0 }}">
              <span class="studio-product-choice-main"><span class="studio-product-choice-name">{{ $product->name_fa }}</span><span class="studio-product-choice-meta">شناسه {{ $product->id }} · {{ $product->slug }}</span></span>
              @if($covered)<span class="studio-product-choice-status">دارای سفارش</span>@else<span class="studio-product-choice-meta">آماده ساخت</span>@endif
            </button>
          @endforeach
          <div class="studio-empty" id="product-picker-empty" style="display:none">محصولی با این جست‌وجو پیدا نشد.</div>
        </div>
      </div>
    </div>

    <section class="studio-card studio-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-list-check"></i> صف ساخت ویدیو</div><div class="studio-panel-meta">آخرین ۲۰ سفارش</div></div>
      @if($jobs->isNotEmpty())
        <form id="studio-bulk-form" method="POST" action="{{ route('admin.video-studio.jobs.bulk') }}">
          @csrf
          <div class="studio-job-actions" style="margin-bottom:10px"><button class="studio-btn" type="submit" name="action" value="retry"><i class="fa-solid fa-rotate"></i> ساخت مجدد انتخاب‌شده‌ها</button><button class="studio-btn" type="submit" name="action" value="delete"><i class="fa-solid fa-trash"></i> حذف انتخاب‌شده‌ها</button><small class="studio-muted">برای مدیریت گروهی، کنار هر سفارش را تیک بزنید.</small></div>
        </form>
        <div class="studio-queue">
          @foreach($jobs as $job)
            @php($jobStatus = (string) $job->status)
            @php($jobLabel = ['queued' => 'در صف', 'processing' => 'در حال ساخت', 'completed' => 'تکمیل‌شده', 'failed' => 'ناموفق'][$jobStatus] ?? $jobStatus)
            <div class="studio-job">
              <div class="studio-job-main"><label class="studio-check"><input form="studio-bulk-form" type="checkbox" name="job_ids[]" value="{{ $job->id }}"><span></span></label><div><div class="studio-job-title">{{ $job->product?->name_fa ?? 'محصول حذف‌شده' }}</div><div class="studio-job-meta">{{ $job->source_mode === 'video' ? 'ویدیوی منبع' : ($job->source_mode === 'music' ? 'فایل موزیک' : 'منبع خودکار') }} · قاب {{ $job->aspect_ratio }} · {{ \App\Support\Jalali::formatNumeric($job->created_at) }}</div></div></div>
              <div class="studio-job-status {{ in_array($jobStatus, ['queued','processing','completed','failed'], true) ? $jobStatus : 'queued' }}">{{ $jobLabel }}</div>
              <div class="studio-muted">#{{ $job->id }}</div>
              <div class="studio-job-actions"><button type="button" class="studio-job-edit-toggle" data-job-editor-toggle="{{ $job->id }}"><i class="fa-solid fa-pen-to-square"></i> ویرایش</button>@if($jobStatus === 'failed')<form method="POST" action="{{ route('admin.video-studio.jobs.retry', $job) }}">@csrf<button class="studio-link-btn" type="submit" title="ساخت مجدد"><i class="fa-solid fa-rotate-left"></i></button></form>@endif</div>
              <div class="studio-job-editor is-hidden" id="studio-job-editor-{{ $job->id }}"><small>اصلاحیه را محاوره‌ای بنویس؛ هوش مصنوعی آن را روی همین سفارش اعمال می‌کند.</small><form method="POST" action="{{ route('admin.video-studio.jobs.revise', $job) }}" class="studio-form">@csrf<textarea class="studio-textarea" name="revision_request" required placeholder="مثلاً: هوک کوتاه‌تر و هیجان‌انگیزتر شود، هر دو تصویر استفاده شوند و کپشن دوستانه‌تر باشد."></textarea><div class="studio-job-actions"><button class="studio-btn primary" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> ارسال اصلاحیه و ساخت مجدد</button>@if($job->video_url)<a class="studio-btn" href="{{ $job->video_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-video"></i> مشاهده خروجی</a>@endif</div></form></div>
            </div>
          @endforeach
        </div>
      @else
        <div class="studio-empty">هنوز سفارشی در صف ساخت نیست. تنظیمات را انتخاب کنید و «ذخیره و افزودن به صف ساخت» را بزنید.</div>
      @endif
    </section>

    <section class="studio-card studio-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-shield-check"></i> محصولات دارای سفارش ساخت</div><div class="studio-panel-meta">برای جلوگیری از ساخت تکراری</div></div>
      @if($producedProducts->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>آخرین وضعیت</th><th>شماره سفارش</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($producedProducts as $produced)
            @php($pStatus=(string)$produced->status)
            <tr><td class="studio-product">{{ $produced->product?->name_fa ?? 'محصول حذف‌شده' }}</td><td><span class="studio-badge {{ $pStatus === 'completed' ? 'success' : ($pStatus === 'failed' ? 'danger' : 'warning') }}">{{ $pStatus === 'completed' ? 'تکمیل‌شده' : ($pStatus === 'failed' ? 'ناموفق' : 'در صف/در حال ساخت') }}</span></td><td>#{{ $produced->id }}</td><td>{{ \App\Support\Jalali::formatNumeric($produced->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز برای محصولی سفارش ساخت ثبت نشده است.</div>
      @endif
    </section>

    <div class="studio-layout">
      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-chart-column"></i> روند اجرای خط تولید</div><div class="studio-panel-meta">۱۴ روز اخیر</div></div>
        @php($maxDaily = max(1, (int) $daily->max('count')))
        @if($daily->sum('count') > 0)
          <div class="studio-chart" aria-label="نمودار اجرای روزانه">
            @foreach($daily as $day)
              <div class="studio-bar"><span class="studio-bar-value">{{ $day['count'] }}</span><span class="studio-bar-fill" style="height:{{ max(3, round(($day['count'] / $maxDaily) * 100)) }}%"></span><span class="studio-bar-label">{{ $day['label'] }}</span></div>
            @endforeach
          </div>
        @else
          <div class="studio-empty">هنوز داده‌ای برای نمودار تولید ویدیو ثبت نشده است.</div>
        @endif
      </section>

      <section class="studio-card studio-panel">
        <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-heart-pulse"></i> سلامت سیستم</div><div class="studio-panel-meta">منابع متصل</div></div>
        <div class="studio-health">
          <div class="studio-health-item"><div class="studio-health-label">نرخ موفقیت</div><div class="studio-health-value">{{ $videoCount ? number_format(($completedCount / $videoCount) * 100, 1) : '۰' }}٪</div><div class="studio-progress"><span style="width:{{ $videoCount ? min(100, ($completedCount / $videoCount) * 100) : 0 }}%"></span></div></div>
          <div class="studio-health-item"><div class="studio-health-label">پوشش محصولات فعال</div><div class="studio-health-value">{{ $activeProducts ? number_format(($coveredProducts / $activeProducts) * 100, 1) : '۰' }}٪</div><div class="studio-progress"><span style="width:{{ $activeProducts ? min(100, ($coveredProducts / $activeProducts) * 100) : 0 }}%"></span></div></div>
        </div>
        <div style="margin-top:16px">
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-database"></i></div><div class="studio-source-name">محصولات دیتابیس</div><div class="studio-source-status">متصل</div></div>
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-video"></i></div><div class="studio-source-name">ثبت تولیدهای ویدیو</div><div class="studio-source-status">{{ $dataSources['generated_videos'] ? 'متصل' : 'در انتظار جدول' }}</div></div>
          <div class="studio-source"><div class="studio-source-icon"><i class="fa-solid fa-flask"></i></div><div class="studio-source-name">آزمایش‌های محصول</div><div class="studio-source-status">{{ $dataSources['product_test_runs'] ? 'متصل' : 'در انتظار جدول' }}</div></div>
        </div>
      </section>
    </div>

    <section class="studio-card studio-panel" style="margin-bottom:16px">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-clock-rotate-left"></i> آخرین خروجی‌های ویدیو</div><div class="studio-panel-meta">دادهٔ زنده از دیتابیس</div></div>
      @if($latestVideos->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>وضعیت</th><th>مدت</th><th>کیفیت</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($latestVideos as $video)
            @php($status = (string) $video->status)
            @php($statusClass = in_array($status, ['completed','success'], true) ? 'success' : (in_array($status, ['failed','error'], true) ? 'danger' : 'warning'))
            <tr><td><div class="studio-product">{{ $video->product?->name_fa ?? 'بدون محصول' }}</div><div class="studio-muted">#{{ $video->id }}</div></td><td><span class="studio-badge {{ $statusClass }}"><i class="fa-solid {{ $statusClass === 'success' ? 'fa-check' : ($statusClass === 'danger' ? 'fa-xmark' : 'fa-ellipsis') }}"></i>{{ $status === 'completed' ? 'موفق' : ($status === 'failed' || $status === 'error' ? 'ناموفق' : 'در حال پردازش') }}</span></td><td>{{ $video->duration_seconds ? $video->duration_seconds . ' ثانیه' : '—' }}</td><td>{{ $video->width && $video->height ? $video->width . '×' . $video->height : '—' }}</td><td>{{ \App\Support\Jalali::formatNumeric($video->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز خروجی ویدیویی در دیتابیس ثبت نشده است. ثبت‌های پایپ‌لاین تلگرام فعلاً در شیت گزارش ذخیره می‌شوند.</div>
      @endif
    </section>

    <section class="studio-card studio-panel">
      <div class="studio-panel-head"><div class="studio-panel-title"><i class="fa-solid fa-vials"></i> آخرین آزمایش‌های محصول</div><a class="studio-panel-meta" href="{{ route('admin.product-tests.history') }}">مشاهده همه ←</a></div>
      @if($latestTests->isNotEmpty())
        <div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>محصول</th><th>مدل</th><th>وضعیت</th><th>زمان اجرا</th><th>تاریخ</th></tr></thead><tbody>
          @foreach($latestTests as $test)
            @php($testStatus = (string) $test->status)
            <tr><td class="studio-product">{{ $test->product?->name_fa ?? 'پیش‌نویس محصول' }}</td><td>{{ $test->model_id }}</td><td><span class="studio-badge {{ $testStatus === 'completed' ? 'success' : ($testStatus === 'failed' ? 'danger' : 'warning') }}">{{ $testStatus === 'completed' ? 'موفق' : ($testStatus === 'failed' ? 'ناموفق' : 'در حال اجرا') }}</span></td><td>{{ $test->duration_ms ? number_format($test->duration_ms / 1000, 1) . ' ثانیه' : '—' }}</td><td>{{ \App\Support\Jalali::formatNumeric($test->created_at) }}</td></tr>
          @endforeach
        </tbody></table></div>
      @else
        <div class="studio-empty">هنوز آزمایش محصولی ثبت نشده است.</div>
      @endif
    </section>
  </div>
</main>
@endsection

@section('scripts')
<script>
  document.getElementById('breadcrumb')?.replaceChildren(document.createTextNode('تولید خودکار ویدیو'));
  const sourceHelp = document.getElementById('source-help');
  const sourceUrlField = document.getElementById('source-url-field');
  const sourceUrl = document.getElementById('source-url');
  const keywordToggle = document.getElementById('auto-keyword-toggle');
  const keywordSettings = document.getElementById('keyword-settings');
  const hookToggle = document.querySelector('input[name="auto_generate_hook"][type="checkbox"]');
  const captionToggle = document.querySelector('input[name="auto_generate_caption"][type="checkbox"]');
  const hookManual = document.getElementById('hook-manual');
  const captionManual = document.getElementById('caption-manual');
  const productPicker = document.getElementById('product-picker');
  const productPickerSearch = document.getElementById('product-picker-search');
  const productPickerSort = document.getElementById('product-picker-sort');
  const productPickerList = document.getElementById('product-picker-list');
  const productPickerEmpty = document.getElementById('product-picker-empty');
  const studioForm = document.getElementById('studio-settings-form');
  const formMethod = document.getElementById('studio-form-method');
  const sourceDescriptions = {
    auto: 'ورکفلو بر اساس منبع موجود، بهترین گزینه را انتخاب می‌کند.',
    upload: 'یک فایل مستقیم صوتی یا ویدیویی را با نشانی آن مشخص کنید.',
    music: 'فایل موزیک انتخابی شما روی ویدیوی محصول قرار می‌گیرد.',
    video: 'صدای ویدیوی منبع استخراج می‌شود و مدت خروجی با آن هماهنگ می‌ماند.'
  };
  function updateStudioControls() {
    const selected = document.querySelector('input[name="source_mode"]:checked')?.value || 'auto';
    if (sourceHelp) sourceHelp.textContent = sourceDescriptions[selected] || sourceDescriptions.auto;
    if (sourceUrlField) sourceUrlField.style.display = selected === 'auto' ? 'none' : 'grid';
    if (sourceUrl) sourceUrl.placeholder = selected === 'video' ? 'لینک ویدیوی منبع' : 'لینک فایل صوتی یا موزیک';
    if (hookManual) hookManual.classList.toggle('is-hidden', !!hookToggle?.checked);
    if (captionManual) captionManual.classList.toggle('is-hidden', !!captionToggle?.checked);
    if (keywordSettings) keywordSettings.classList.toggle('is-hidden', !!keywordToggle?.checked);
  }
  document.querySelectorAll('input[name="source_mode"]').forEach((input) => input.addEventListener('change', updateStudioControls));
  keywordToggle?.addEventListener('change', updateStudioControls);
  hookToggle?.addEventListener('change', updateStudioControls);
  captionToggle?.addEventListener('change', updateStudioControls);
  updateStudioControls();

  function submitStudioForm(action, method) {
    if (!studioForm) return;
    const queueButton = document.getElementById('queue-submit');
    studioForm.action = action;
    studioForm.method = 'POST';
    if (formMethod) formMethod.value = method === 'PATCH' ? 'PATCH' : '';
    if (method === 'POST' && queueButton) { queueButton.disabled = true; queueButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> در حال ثبت سفارش...'; }
    studioForm.submit();
  }
  window.submitStudioForm = submitStudioForm;

  function sortProductChoices() {
    if (!productPickerList) return;
    const items = [...productPickerList.querySelectorAll('.studio-product-choice')];
    const sort = productPickerSort?.value || 'newest';
    items.sort((a, b) => {
      if (sort === 'name_asc') return a.dataset.productName.localeCompare(b.dataset.productName, 'fa');
      if (sort === 'name_desc') return b.dataset.productName.localeCompare(a.dataset.productName, 'fa');
      const delta = Number(a.dataset.productOrder || 0) - Number(b.dataset.productOrder || 0);
      return sort === 'oldest' ? -delta : delta;
    });
    items.forEach((item) => productPickerList.insertBefore(item, productPickerEmpty));
  }
  function filterProductChoices() {
    const term = (productPickerSearch?.value || '').trim().toLocaleLowerCase('fa');
    let visible = 0;
    productPickerList?.querySelectorAll('.studio-product-choice').forEach((item) => {
      const show = !term || (item.dataset.productSearch || '').toLocaleLowerCase('fa').includes(term);
      item.style.display = show ? 'flex' : 'none';
      if (show) visible++;
    });
    if (productPickerEmpty) productPickerEmpty.style.display = visible ? 'none' : 'block';
  }
  document.getElementById('open-product-picker')?.addEventListener('click', () => { productPicker?.classList.add('is-open'); productPickerSearch?.focus(); sortProductChoices(); filterProductChoices(); });
  document.getElementById('close-product-picker')?.addEventListener('click', () => productPicker?.classList.remove('is-open'));
  productPicker?.addEventListener('click', (event) => { if (event.target === productPicker) productPicker.classList.remove('is-open'); });
  productPickerSearch?.addEventListener('input', filterProductChoices);
  productPickerSort?.addEventListener('change', () => { sortProductChoices(); filterProductChoices(); });
  productPickerList?.querySelectorAll('.studio-product-choice').forEach((item) => item.addEventListener('click', () => {
    window.location = '{{ route('admin.products.dashboard') }}?product_id=' + encodeURIComponent(item.dataset.productId);
  }));
  document.querySelectorAll('[data-job-editor-toggle]').forEach((button) => button.addEventListener('click', () => {
    const editor = document.getElementById('studio-job-editor-' + button.dataset.jobEditorToggle);
    editor?.classList.toggle('is-hidden');
  }));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') productPicker?.classList.remove('is-open'); });
</script>
@endsection
