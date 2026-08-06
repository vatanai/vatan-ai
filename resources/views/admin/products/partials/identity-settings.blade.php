@php
  $source = $product ?? $duplicateFrom;
  $curSubjectType = old('subject_type', optional($source)->subject_type ?? 'generic');
  $hasOldChoice = session()->hasOldInput('identity_preservation');
  $curIdentityOn = $hasOldChoice ? old('identity_preservation') === '1' : (is_null($source) ? null : (bool) $source->identity_preservation);
  $curIdentityPrompt = old('identity_instructions', optional($source)->identity_instructions ?: \App\Services\ProductPromptBuilder::defaultIdentityInstructions());
  $curIdentityPromptFa = old('identity_instructions_fa', optional($source)->identity_instructions_fa ?: \App\Services\ProductPromptBuilder::defaultIdentityInstructionsFa());
  $curIdentityModel = old('identity_model', optional($source)->identity_model);
  $curIdentityProvider = old('identity_model_provider', optional($source)->identity_model_provider);
@endphp
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-4 md:p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-user-check text-[var(--accent)]"></i> چهره و حفظ هویت {!! $__help('identity_preservation', 'چهره و حفظ هویت') !!}</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">انتخاب روشن یا خاموش برای ثبت محصول اجباری است.</div>
  </div>

  <input type="hidden" name="subject_type" value="{{ $curSubjectType }}">

  <fieldset class="mb-4">
    <legend class="text-xs font-semibold text-[var(--text2)] mb-2">حفظ هویت در این محصول فعال باشد؟ <span class="text-[var(--red)]">*</span></legend>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
      @foreach([1=>['روشن','مدل Grade A، کیفیت High و پرامپت حفظ هویت در اختیار کاربر قرار می‌گیرد.'],0=>['خاموش','محصول فقط با مدل Grade B و کیفیت Medium اجرا می‌شود.']] as $value=>$copy)
        <label class="identity-choice flex gap-3 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer">
          <input type="radio" name="identity_preservation" value="{{ $value }}" class="accent-[var(--accent)] mt-0.5" {{ $curIdentityOn === (bool)$value ? 'checked' : '' }} required onchange="toggleIdentitySettings()">
          <span><b class="block text-xs text-[var(--text)]">{{ $copy[0] }}</b><small class="block text-[10px] text-[var(--text3)] mt-1 leading-relaxed">{{ $copy[1] }}</small></span>
        </label>
      @endforeach
    </div>
  </fieldset>

  <div id="identity-settings-wrap" class="{{ $curIdentityOn ? '' : 'hidden' }} space-y-3.5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">مدل Grade A حفظ هویت {!! $__help('identity_model', 'مدل حفظ هویت') !!}</label>
        <select id="identity-model-picker" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" onchange="syncIdentityModel(this)">
          <option value="">انتخاب مدل...</option>
          @foreach($aiModels as $model)
            <option value="{{ $model->provider }}|{{ $model->openrouter_model_id }}" {{ $curIdentityModel === $model->openrouter_model_id && $curIdentityProvider === $model->provider ? 'selected' : '' }}>{{ $model->name }} — {{ $model->provider }}</option>
          @endforeach
        </select>
        <input type="hidden" name="identity_model" id="identity-model" value="{{ $curIdentityModel }}">
        <input type="hidden" name="identity_model_provider" id="identity-model-provider" value="{{ $curIdentityProvider }}">
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">کردیت اضافه حفظ هویت {!! $__help('identity_credit_cost', 'کردیت حفظ هویت') !!}</label>
        <input type="number" name="identity_credit_cost" min="0" value="{{ old('identity_credit_cost', optional($source)->identity_credit_cost ?? 0) }}" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
      <div><label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">حداقل دریافتی از کاربر {!! $__help('min_reference_images', 'حداقل دریافتی از کاربر') !!}</label><input type="number" name="min_reference_images" min="0" max="3" value="{{ old('min_reference_images', optional($source)->min_reference_images ?? 1) }}" class="mt-1.5 w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]"></div>
      <div><label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">حداکثر دریافتی از کاربر {!! $__help('max_reference_images', 'حداکثر دریافتی از کاربر') !!}</label><input type="number" name="max_reference_images" min="2" max="3" value="{{ old('max_reference_images', optional($source)->max_reference_images ?? 3) }}" class="mt-1.5 w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]"><small class="text-[10px] text-[var(--text3)]">برای کنترل هزینه، سقف همیشه ۳ عکس است.</small></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5">
      <div><label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">پرامپت حفظ هویت (انگلیسی) {!! $__help('identity_instructions', 'پرامپت حفظ هویت') !!}</label><textarea name="identity_instructions" id="identity-instructions-en" rows="8" class="mt-1.5 w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left font-mono leading-relaxed">{{ $curIdentityPrompt }}</textarea></div>
      <div><label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">ترجمه فارسی خودکار {!! $__help('identity_instructions_fa', 'ترجمه فارسی پرامپت') !!}</label><textarea name="identity_instructions_fa" id="identity-instructions-fa" rows="8" class="mt-1.5 w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] leading-relaxed">{{ $curIdentityPromptFa }}</textarea><small id="identity-translation-state" class="text-[10px] text-[var(--text3)]">با تغییر متن انگلیسی، ترجمه فارسی خودکار به‌روزرسانی می‌شود.</small></div>
    </div>
    <small class="text-[10px] text-[var(--text3)]">متن انگلیسی یک‌بار با محصول ذخیره می‌شود و فقط وقتی کاربر حفظ هویت را روشن کند به پرامپت ارسالی اضافه می‌شود.</small>
  </div>
</div>
<script>
function toggleIdentitySettings(){var selected=document.querySelector('input[name="identity_preservation"]:checked');document.getElementById('identity-settings-wrap')?.classList.toggle('hidden',selected?.value!=='1');}
function onSubjectTypeChange(radio){document.querySelectorAll('.subject-type-card').forEach(function(card){card.classList.remove('border-[var(--accent)]','bg-[var(--accent)]/8')});radio.closest('.subject-type-card')?.classList.add('border-[var(--accent)]','bg-[var(--accent)]/8');if(['face','body'].includes(radio.value)){var on=document.querySelector('input[name="identity_preservation"][value="1"]');if(on){on.checked=true;toggleIdentitySettings();}}}
function syncIdentityModel(select){var parts=select.value.split('|');document.getElementById('identity-model-provider').value=parts.shift()||'';document.getElementById('identity-model').value=parts.join('|')||'';}
var identityTranslateTimer;
document.getElementById('identity-instructions-en')?.addEventListener('input', function(event){
  clearTimeout(identityTranslateTimer); var text=event.target.value.trim(); var state=document.getElementById('identity-translation-state');
  if(!text) return; if(state) state.textContent='در انتظار ترجمه…';
  identityTranslateTimer=setTimeout(function(){fetch('{{ route('admin.products.translate_identity_prompt') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('input[name="_token"]')?.value||'','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({text:text})}).then(function(r){if(!r.ok)throw new Error();return r.json()}).then(function(data){document.getElementById('identity-instructions-fa').value=data.translation||'';if(state)state.textContent='ترجمه فارسی به‌روز شد.'}).catch(function(){if(state)state.textContent='ترجمه خودکار انجام نشد؛ دوباره تلاش کنید یا ترجمه را دستی ویرایش کنید.'})},900);
});
</script>
