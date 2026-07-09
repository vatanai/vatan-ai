{{-- پارشیال: آپلود عکس --}}
{{-- برای هر فیلد image_upload در input_schema یک slot می‌سازد --}}
@php
  $schema = is_array($product->input_schema) ? $product->input_schema : [];
  $uploadFields = collect($schema)->where('type', 'image_upload')->values();

  // اگر هیچ فیلد آپلودی در schema نبود، یه slot پیش‌فرض می‌سازیم
  if ($uploadFields->isEmpty()) {
    $uploadFields = collect([[
      'field_id' => 'photo',
      'label_fa' => 'عکس شما',
      'required' => '1',
    ]]);
  }
@endphp

<div class="cp-upload">
  <div class="cp-upload__inner" style="padding:14px 16px;">

    <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:4px;">
      <i class="fa-solid fa-images" style="color:#a07af5;margin-left:6px;font-size:12px;"></i>
      آپلود عکس
    </div>
    <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-bottom:14px;">
      عکس باید واضح و روبرو باشد — کیفیت بالاتر نتیجه بهتر می‌دهد
    </div>

    <div class="cp-upload__grid cp-upload__grid--{{ $uploadFields->count() == 1 ? '1' : ($uploadFields->count() <= 2 ? '2' : ($uploadFields->count() <= 3 ? '3' : '4')) }}"
         style="display:grid;grid-template-columns:repeat({{ min($uploadFields->count(), 2) }},1fr);gap:12px;">

      @foreach($uploadFields as $field)
      @php $fieldId = $field['field_id']; @endphp
      <div class="cp-upload-card" id="slot-{{ $fieldId }}" data-slot="{{ $fieldId }}"
        style="aspect-ratio:3/4;border-radius:14px;border:2px dashed rgba(255,255,255,0.15);background:rgba(255,255,255,0.03);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;transition:border-color 0.2s,background 0.2s;position:relative;overflow:hidden;"
        onclick="document.getElementById('file-{{ $fieldId }}').click()">

        {{-- Preview overlay --}}
        <img id="preview-{{ $fieldId }}" src="" alt="پیش‌نمایش"
          style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:12px;">

        {{-- Placeholder --}}
        <div id="placeholder-{{ $fieldId }}" style="text-align:center;padding:16px;">
          <i class="fa-solid fa-camera" style="font-size:28px;color:rgba(160,122,245,0.5);display:block;margin-bottom:10px;"></i>
          <div style="font-size:12px;font-weight:700;color:rgba(255,255,255,0.6);">{{ $field['label_fa'] ?? 'عکس شما' }}</div>
          <div style="font-size:10px;color:rgba(255,255,255,0.3);margin-top:4px;">کلیک کنید</div>
        </div>

        {{-- Done checkmark --}}
        <div id="done-{{ $fieldId }}" style="display:none;position:absolute;top:8px;right:8px;width:24px;height:24px;background:#0BBF53;border-radius:50%;display:none;align-items:center;justify-content:center;">
          <i class="fa-solid fa-check" style="color:#fff;font-size:11px;"></i>
        </div>

        <input type="file" id="file-{{ $fieldId }}" accept="image/*" style="display:none;"
          onchange="handleUpload('{{ $fieldId }}', this)">
      </div>
      @endforeach

    </div>

    <div id="form-error" style="display:none;margin-top:12px;padding:10px 14px;background:rgba(240,92,92,0.1);border:1px solid rgba(240,92,92,0.3);border-radius:10px;font-size:12px;color:#ff9191;align-items:center;gap:8px;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span id="form-error-text"></span>
    </div>

  </div>
</div>

<script>
function handleUpload(fieldId, input) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];
  var reader = new FileReader();
  reader.onload = function(e) {
    var slot = document.getElementById('slot-' + fieldId);
    var preview = document.getElementById('preview-' + fieldId);
    var placeholder = document.getElementById('placeholder-' + fieldId);
    var done = document.getElementById('done-' + fieldId);

    preview.src = e.target.result;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
    done.style.display = 'flex';
    slot.classList.add('is-filled');
    slot.style.borderColor = 'rgba(11,191,83,0.4)';
    document.dispatchEvent(new CustomEvent('cp:upload-changed'));
  };
  reader.readAsDataURL(file);
}
</script>