{{-- پارشیال: گام دوم — تنظیمات هوش مصنوعی --}}
{{-- این بخش نیاز به متغیر $aiModels دارد که از کنترلر پاس داده می‌شود --}}

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-microchip text-[#a07af5]"></i> پایپ‌لاین هوش مصنوعی</div>

  <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">مدل اصلی — اولویت یک</div>
  <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5 mb-4">
    <select name="primary_model" id="primary-model-select" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white w-full focus:border-[#a07af5] mb-2">
      <option value="">— انتخاب مدل اصلی —</option>
      @foreach ($aiModels as $model)
        <option value="{{ $model->model_id }}" {{ old('primary_model') == $model->model_id ? 'selected' : '' }}>
          {{ $model->name }} ({{ $model->provider }})
        </option>
      @endforeach
    </select>
    <div class="grid grid-cols-2 gap-2">
      <input type="number" name="timeout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" placeholder="زمان انتظار (ثانیه)" value="{{ old('timeout', 60) }}">
      <select name="pipeline_type" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="image_generation" {{ old('pipeline_type') == 'image_generation' ? 'selected' : '' }}>تولید عکس از صفر</option>
        <option value="image_editing" {{ old('pipeline_type', 'image_editing') == 'image_editing' ? 'selected' : '' }}>ویرایش عکس آپلودی کاربر</option>
        <option value="text_generation" {{ old('pipeline_type') == 'text_generation' ? 'selected' : '' }}>تولید متن</option>
      </select>
    </div>
  </div>

  <div class="text-[11px] font-bold text-[#4d7a56] mb-2 tracking-wider uppercase">مدل‌های جایگزین — اولویت دو، سه و...</div>
  <p class="text-[10.5px] text-[#4d7a56] mb-2.5 leading-relaxed">
    اگر مدل اصلی پاسخ نداد، سیستم به ترتیبی که اینجا چیده‌اید سراغ مدل بعدی می‌رود.
  </p>
  <div id="fallback-list" class="space-y-2"></div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold mt-2" onclick="addFallback()">
    <i class="fa-solid fa-plus"></i> افزودن مدل جایگزین
  </button>
</div>

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-terminal text-[#a07af5]"></i> تنظیمات پرامپت</div>

  <div class="bg-[#a07af5]/8 border border-[#a07af5]/25 rounded-lg p-3 mb-3.5 flex items-start gap-2.5">
    <i class="fa-solid fa-circle-info text-[#a07af5] mt-0.5 text-xs shrink-0"></i>
    <div class="text-[11px] text-[#c9b8f5] leading-relaxed">
      پرامپت باید حتماً به <strong>زبان انگلیسی</strong> نوشته شود چون مستقیم برای مدل هوش مصنوعی ارسال می‌شود.
      می‌توانید از متغیرهایی مثل <code class="bg-[#222230] px-1 rounded">{name}</code> استفاده کنید که در زمان اجرا با ورودی کاربر جایگزین می‌شوند.
    </div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[#a8c4a8]">متن پرامپت (انگلیسی) <span class="text-[#f05c5c] mr-0.5">*</span></label>
    <textarea name="prompt_template" id="prompt-template" rows="5"
      class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white min-h-[100px] ltr text-left font-mono focus:border-[#a07af5] outline-none transition-colors"
      placeholder="e.g. Edit the uploaded photo: change the background to a professional studio, cinematic lighting, keep the person unchanged. The person's name is {name}.">{{ old('prompt_template') }}</textarea>
    <div class="flex flex-wrap gap-1.5 mt-1.5" id="var-chips">
      <span class="text-[11px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 cursor-pointer text-[#a8c4a8] hover:border-[#a07af5]" onclick="insertVar('{name}')">{name}</span>
      <span class="text-[11px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 cursor-pointer text-[#a8c4a8] hover:border-[#a07af5]" onclick="insertVar('{gender}')">{gender}</span>
      <span class="text-[11px] bg-[#222230] border border-[#2e2e3e] rounded px-2 py-0.5 cursor-pointer text-[#a8c4a8] hover:border-[#a07af5]" onclick="insertVar('{style}')">{style}</span>
    </div>
  </div>

  {{-- دکمه تست پرامپت --}}
  <div class="border-t border-[#222230] pt-3.5 mt-1">
    <div class="flex items-center justify-between mb-3">
      <div>
        <div class="text-xs font-bold text-white">تست پرامپت</div>
        <div class="text-[10.5px] text-[#4d7a56] mt-0.5">مستقیم از همین صفحه عکس تولید کنید تا مطمئن شوید پرامپت درست است</div>
      </div>
      <button type="button" id="btn-test-prompt"
        onclick="testPromptNow()"
        class="inline-flex items-center gap-2 px-4 h-9 rounded-lg text-xs font-bold bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-all">
        <i class="fa-solid fa-play text-[11px]"></i>
        <span id="btn-test-text">اجرای تست</span>
      </button>
    </div>

    {{-- نمایش نتیجه تست --}}
    <div id="test-result-box" class="hidden">
      <div class="bg-[#111116] border border-[#222230] rounded-xl p-3 flex items-start gap-3">
        <img id="test-result-img" src="" alt="نتیجه تست" class="w-32 h-32 object-cover rounded-lg border border-[#222230] shrink-0">
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold text-[#0BBF53] mb-1 flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> تصویر با موفقیت تولید شد</div>
          <div class="text-[11px] text-[#4d7a56] mb-2">مدل استفاده‌شده: <span id="test-result-model" class="text-[#a8c4a8] font-mono"></span></div>
          <a id="test-result-download" href="#" target="_blank" class="text-[11px] text-[#a07af5] underline">مشاهده تصویر کامل</a>
        </div>
      </div>
    </div>

    <div id="test-error-box" class="hidden">
      <div class="bg-[#f05c5c]/10 border border-[#f05c5c]/30 rounded-xl p-3 text-xs text-[#ff9191]">
        <i class="fa-solid fa-triangle-exclamation ml-1"></i>
        <span id="test-error-text"></span>
      </div>
    </div>
  </div>
</div>

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-table-list text-[#a07af5]"></i> فیلدهای ورودی کاربر</div>
  <div id="input-fields-list" class="space-y-2"></div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[#2e2e3e] bg-transparent text-[#4d7a56] text-xs font-semibold mt-3" onclick="addInputField()">
    <i class="fa-solid fa-plus"></i> افزودن فیلد ورودی جدید
  </button>
</div>

<script>
function testPromptNow() {
  var prompt  = document.getElementById('prompt-template').value.trim();
  var modelId = document.getElementById('primary-model-select').value;

  if (!prompt)   { alert('ابتدا پرامپت را بنویسید.'); return; }
  if (!modelId)  { alert('ابتدا مدل اصلی را انتخاب کنید.'); return; }

  // reset
  document.getElementById('test-result-box').classList.add('hidden');
  document.getElementById('test-error-box').classList.add('hidden');

  var btn  = document.getElementById('btn-test-prompt');
  var text = document.getElementById('btn-test-text');
  btn.disabled = true;
  text.textContent = 'در حال تولید...';

  var csrfToken = document.querySelector('input[name="_token"]').value;

  fetch('{{ route('admin.ai-models.test-prompt') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      prompt:   prompt,
      model_id: modelId,
    }),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';

    if (data.success) {
      document.getElementById('test-result-img').src = data.image_url;
      document.getElementById('test-result-model').textContent = data.model;
      document.getElementById('test-result-download').href = data.image_url;
      document.getElementById('test-result-box').classList.remove('hidden');
    } else {
      document.getElementById('test-error-text').textContent = data.message || 'خطای ناشناخته';
      document.getElementById('test-error-box').classList.remove('hidden');
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';
    document.getElementById('test-error-text').textContent = 'خطا در ارتباط با سرور: ' + err.message;
    document.getElementById('test-error-box').classList.remove('hidden');
  });
}
</script>