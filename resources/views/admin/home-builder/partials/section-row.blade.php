@php
  $typeInfo = $typeRegistry[$section->type] ?? ['label' => $section->type, 'icon' => 'fa-solid fa-cube', 'layouts' => []];
  $layoutInfo = $typeInfo['layouts'][$section->layout] ?? null;
  $statusMap = [
    'published' => ['label' => 'منتشرشده', 'cls' => 'badge-success'],
    'draft'     => ['label' => 'پیش‌نویس', 'cls' => 'badge-warning'],
    'hidden'    => ['label' => 'مخفی',      'cls' => 'badge-danger'],
  ];
  $statusInfo = $statusMap[$section->status] ?? $statusMap['draft'];
  $resp = $section->responsiveSettings();
@endphp
<div class="hb-row" id="hb-row-{{ $section->id }}" data-id="{{ $section->id }}" draggable="true"
     style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--card-bg);">

  <div class="hb-drag-handle" title="جابه‌جایی" style="cursor:grab;color:var(--text-soft);flex-shrink:0;padding:4px;">
    <i class="fa-solid fa-grip-vertical"></i>
  </div>

  <div style="width:40px;height:40px;border-radius:10px;background:var(--input-bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--primary);">
    <i class="{{ $typeInfo['icon'] }}"></i>
  </div>

  <div style="flex:1;min-width:0;">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <span class="text-[13.5px] font-bold" style="color:var(--text-h);">{{ $section->title_fa ?: $typeInfo['label'] }}</span>
      <span class="badge-pro {{ $statusInfo['cls'] }}"><i class="fa-solid fa-circle"></i> {{ $statusInfo['label'] }}</span>
    </div>
    <div class="text-[11px] mt-1" style="color:var(--text-soft);">
      {{ $typeInfo['label'] }}
      @if($layoutInfo) — {{ $layoutInfo['label'] }} @endif
      <span style="margin-inline-start:8px;">
        <i class="fa-solid fa-desktop" style="{{ $resp['desktop'] ? '' : 'opacity:.3;' }}" title="دسکتاپ"></i>
        <i class="fa-solid fa-tablet-screen-button" style="{{ $resp['tablet'] ? '' : 'opacity:.3;' }}" title="تبلت"></i>
        <i class="fa-solid fa-mobile-screen" style="{{ $resp['mobile'] ? '' : 'opacity:.3;' }}" title="موبایل"></i>
      </span>
    </div>
  </div>

  <div class="flex items-center gap-1.5" style="flex-shrink:0;">
    <button type="button" class="icon-action-btn" title="ویرایش" onclick='HomeBuilder.openEditDrawer(@json($section))'>
      <i class="fa-solid fa-pen"></i>
    </button>
    <button type="button" class="icon-action-btn" title="تکثیر" onclick="HomeBuilder.duplicate({{ $section->id }})">
      <i class="fa-solid fa-copy"></i>
    </button>
    @if($section->status === 'published')
      <button type="button" class="icon-action-btn" title="مخفی کردن" onclick="HomeBuilder.setStatus({{ $section->id }}, 'hidden')">
        <i class="fa-solid fa-eye-slash"></i>
      </button>
    @else
      <button type="button" class="icon-action-btn" title="انتشار" onclick="HomeBuilder.setStatus({{ $section->id }}, 'published')">
        <i class="fa-solid fa-eye"></i>
      </button>
    @endif
    <button type="button" class="icon-action-btn danger" title="حذف" onclick="HomeBuilder.destroy({{ $section->id }})">
      <i class="fa-solid fa-trash"></i>
    </button>
  </div>
</div>
