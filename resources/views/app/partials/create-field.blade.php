@php
  $type = $field['type']; $id = $field['id']; $value = $field['value'] ?? null; $options = $field['options'] ?? [];
@endphp
@if($type === 'info')
  <div class="cw-info"><i class="fa-solid fa-lightbulb"></i><span>{{ $field['label'] }}</span></div>
@elseif($type === 'section')
  <div class="cw-schema-section"><strong>{{ $field['label'] }}</strong>@if($field['help'] ?? false)<span>{{ $field['help'] }}</span>@endif</div>
@elseif($type === 'divider')
  <div class="cw-schema-divider"></div>
@else
<div class="cw-field" data-field="{{ $id }}" data-field-type="{{ $type }}" data-field-credit="{{ (int)($field['credit_cost'] ?? 0) }}" @if(!empty($field['show_if']['field'])) data-show-field="{{ $field['show_if']['field'] }}" data-show-op="{{ $field['show_if']['op'] ?? 'eq' }}" data-show-value="{{ $field['show_if']['value'] ?? '' }}" @endif>
  <label class="cw-label" for="cw-{{ $id }}"><span>{{ $field['label'] }} @if($field['required'] ?? false)<b>*</b>@endif</span>@if($field['help'] ?? false)<small>{{ $field['help'] }}</small>@endif</label>

  @if(in_array($type, ['image_upload','multi_image','file_upload']))
    <label class="cw-upload {{ $type === 'multi_image' ? 'is-multi' : '' }}" for="cw-{{ $id }}">
      <input id="cw-{{ $id }}" name="uploads[{{ $id }}]{{ $type === 'multi_image' ? '[]' : '' }}" type="file" {{ $type === 'multi_image' ? 'multiple' : '' }} accept="{{ $field['accept'] ?: ($type !== 'file_upload' ? 'image/*' : '') }}" data-upload-input>
      <span class="cw-upload-icon"><i class="fa-solid {{ $type === 'multi_image' ? 'fa-images' : ($type === 'file_upload' ? 'fa-file-arrow-up' : 'fa-cloud-arrow-up') }}"></i></span>
      <span class="cw-upload-copy"><strong>{{ $type === 'multi_image' ? 'افزودن تصاویر بیشتر' : ($type === 'file_upload' ? 'انتخاب فایل' : 'عکس را اینجا رها کنید') }}</strong><small>{{ $type === 'image_upload' ? 'یا برای انتخاب از دستگاه کلیک کنید' : ($field['help'] ?? 'برای انتخاب کلیک کنید') }}</small></span>
      <span class="cw-upload-action">انتخاب</span>
    </label>
    <div class="cw-upload-preview" data-upload-preview></div>
  @elseif(in_array($type, ['textarea','prompt','negative_prompt']))
    <textarea id="cw-{{ $id }}" name="fields[{{ $id }}]" class="cw-input" placeholder="{{ $field['placeholder'] ?? '' }}">{{ $value }}</textarea>
  @elseif(in_array($type, ['text','number','seed']))
    <input id="cw-{{ $id }}" name="fields[{{ $id }}]" class="cw-input" type="{{ in_array($type, ['number','seed']) ? 'number' : 'text' }}" value="{{ $value }}" placeholder="{{ $field['placeholder'] ?? '' }}" @if($field['min'] !== '') min="{{ $field['min'] }}" @endif @if($field['max'] !== '') max="{{ $field['max'] }}" @endif>
  @elseif(in_array($type, ['radio','multi_select','gender']))
    <div class="cw-chips {{ $type === 'multi_select' ? 'multi' : '' }}">
      @foreach($options as $option)<label><input type="{{ $type === 'multi_select' ? 'checkbox' : 'radio' }}" name="fields[{{ $id }}]{{ $type === 'multi_select' ? '[]' : '' }}" value="{{ $option['value'] }}" data-option-credit="{{ (int)($option['credit'] ?? 0) }}" {{ $value === $option['value'] ? 'checked' : '' }}><span>{{ $option['label'] }}</span></label>@endforeach
    </div>
  @elseif($type === 'select')
    <div class="cw-select-wrap"><select id="cw-{{ $id }}" name="fields[{{ $id }}]" class="cw-input">@foreach($options as $option)<option value="{{ $option['value'] }}" data-option-credit="{{ (int)($option['credit'] ?? 0) }}" {{ $value === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>@endforeach</select><i class="fa-solid fa-chevron-down"></i></div>
  @elseif($type === 'button_group')
    <div class="cw-segment">@foreach($options as $option)<label><input type="radio" name="fields[{{ $id }}]" value="{{ $option['value'] }}" data-option-credit="{{ (int)($option['credit'] ?? 0) }}" {{ $value === $option['value'] ? 'checked' : '' }}><span>{{ $option['label'] }}</span></label>@endforeach</div>
  @elseif(in_array($type, ['strength','slider']))
    <div class="cw-range-wrap"><div><span>کمترین تغییر</span><output>{{ $value }}{{ $field['unit'] ?? '' }}</output><span>بیشترین شباهت</span></div><input id="cw-{{ $id }}" name="fields[{{ $id }}]" type="range" min="{{ $field['min'] ?: 0 }}" max="{{ $field['max'] ?: 100 }}" step="{{ $field['step'] ?: 1 }}" value="{{ $value !== '' ? $value : 50 }}" data-range></div>
  @elseif($type === 'color')
    <div class="cw-color-control"><input id="cw-{{ $id }}" name="fields[{{ $id }}]" type="color" value="{{ $value ?: '#16594f' }}"><span data-color-value>{{ $value ?: '#16594f' }}</span><i class="fa-solid fa-eyedropper"></i></div>
  @elseif($type === 'switch')
    <input type="hidden" name="fields[{{ $id }}]" value="0"><label class="cw-toggle"><span>{{ $field['label'] }}</span><input id="cw-{{ $id }}" name="fields[{{ $id }}]" value="1" type="checkbox" {{ in_array((string)$value, ['1','true'], true) ? 'checked' : '' }}><i></i></label>
  @elseif($type === 'checkbox')
    <input type="hidden" name="fields[{{ $id }}]" value="0"><label class="cw-check"><input id="cw-{{ $id }}" name="fields[{{ $id }}]" value="1" type="checkbox"><i class="fa-solid fa-check"></i><span>{{ $field['label'] }}</span></label>
  @elseif($type === 'style_preset')
    <div class="cw-style-grid">@foreach($options as $option)<label><input type="radio" name="fields[{{ $id }}]" value="{{ $option['value'] }}" data-option-credit="{{ (int)($option['credit'] ?? 0) }}" {{ $value === $option['value'] ? 'checked' : '' }}><span>@if($option['image'])<img src="{{ $option['image'] }}" alt="">@else<div class="cw-style-placeholder"><i class="fa-solid fa-wand-magic-sparkles"></i></div>@endif<b>{{ $option['label'] }}</b><i class="fa-solid fa-check"></i></span></label>@endforeach</div>
  @elseif($type === 'aspect_ratio')
    <div class="cw-ratios">@foreach($options as $option)<label><input type="radio" name="fields[{{ $id }}]" value="{{ $option['value'] }}" data-option-credit="{{ (int)($option['credit'] ?? 0) }}" {{ $value === $option['value'] ? 'checked' : '' }} data-ratio><span><i style="--ratio:{{ str_replace(':',' / ',$option['value']) }}"></i><small>{{ $option['label'] }}</small></span></label>@endforeach</div>
  @elseif($type === 'resolution')
    <div class="cw-resolution">@foreach($options as $option)<label><input type="radio" name="fields[{{ $id }}]" value="{{ $option['value'] }}" {{ $value === $option['value'] ? 'checked' : '' }} data-credit="{{ (int)($option['credit'] ?? 0) }}"><span><b>{{ $option['label'] }}</b><small>{{ $option['meta'] }}</small></span></label>@endforeach</div>
  @endif
</div>
@endif
