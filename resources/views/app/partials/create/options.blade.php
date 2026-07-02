{{-- پارشیال: فیلدهای داینامیک (text / select / toggle) --}}
@php
  $schema = is_array($product->input_schema) ? $product->input_schema : [];
  $textFields = collect($schema)->whereNotIn('type', ['image_upload'])->values();
@endphp

@if($textFields->isNotEmpty())
<div class="cp-options">
  <div class="cp-options__inner" style="padding:14px 16px;">

    <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:14px;">
      <i class="fa-solid fa-sliders" style="color:#a07af5;margin-left:6px;font-size:12px;"></i>
      تنظیمات
    </div>

    <div class="cp-options__fields" style="display:flex;flex-direction:column;gap:14px;">
      @foreach($textFields as $field)
      @php
        $fid = $field['field_id'] ?? 'field_'.$loop->index;
        $label = $field['label_fa'] ?? $fid;
        $type = $field['type'] ?? 'text';
        $required = ($field['required'] ?? '1') == '1';
      @endphp

      <div class="cp-field" data-key="{{ $fid }}" style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:12px;font-weight:600;color:rgba(255,255,255,0.7);">
          {{ $label }}
          @if($required)<span style="color:#f05c5c;margin-right:2px;">*</span>@endif
        </label>

        @if($type === 'text')
          <input type="text" name="fields[{{ $fid }}]"
            {{ $required ? 'required' : '' }}
            class="cp-input"
            style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px 14px;font-size:13px;color:#fff;outline:none;width:100%;font-family:inherit;transition:border-color 0.2s;"
            placeholder="{{ $label }}...">

        @elseif($type === 'select' && !empty($field['options']))
          <select name="fields[{{ $fid }}]" {{ $required ? 'required' : '' }}
            style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px 14px;font-size:13px;color:#fff;outline:none;width:100%;font-family:inherit;">
            <option value="">انتخاب کنید</option>
            @foreach(explode(',', $field['options']) as $opt)
              <option value="{{ trim($opt) }}">{{ trim($opt) }}</option>
            @endforeach
          </select>

        @elseif($type === 'toggle')
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;width:fit-content;">
            <input type="checkbox" name="fields[{{ $fid }}]" value="yes"
              style="width:18px;height:18px;accent-color:#a07af5;cursor:pointer;">
            <span style="font-size:12px;color:rgba(255,255,255,0.6);">{{ $label }}</span>
          </label>

        @else
          <input type="text" name="fields[{{ $fid }}]" {{ $required ? 'required' : '' }}
            style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px 14px;font-size:13px;color:#fff;outline:none;width:100%;font-family:inherit;"
            placeholder="{{ $label }}...">
        @endif

        <div id="error-{{ $fid }}" style="display:none;font-size:11px;color:#f05c5c;">این فیلد الزامی است.</div>
      </div>
      @endforeach
    </div>

  </div>
</div>
@endif