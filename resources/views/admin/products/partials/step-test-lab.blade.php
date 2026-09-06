{{-- گام چهارم: آزمایشگاه محصول --}}
@php($testProduct = $product ?? null)
<input type="hidden" name="test_draft_uuid" id="test-draft-uuid">

<div class="grid grid-cols-1 xl:grid-cols-[minmax(320px,38%)_1fr] gap-5 items-start">
  <section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl overflow-hidden xl:sticky xl:top-4">
    <header class="p-4 border-b border-[var(--b1)]">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-user text-[var(--accent)]"></i> تجربه کاربر</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">همان فیلدها و ویژگی‌هایی که کاربر در صفحه «بساز» می‌بیند</div>
    </header>
    <div class="p-4 space-y-3" id="test-user-experience"></div>
    <div class="p-4 border-t border-[var(--b1)] bg-[var(--s1)]">
      <button type="button" class="w-full h-11 rounded-xl bg-[var(--accent)] text-white text-xs font-bold opacity-90" disabled><i class="fa-solid fa-wand-magic-sparkles ml-2"></i>بساز</button>
    </div>
  </section>

  <div class="space-y-5 min-w-0">
    <section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
      <div class="flex items-start justify-between gap-3 flex-wrap mb-4 pb-3 border-b border-[var(--b1)]">
        <div><div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-flask text-[var(--accent)]"></i> تنظیم آزمایش</div><div class="text-[10.5px] text-[var(--text3)] mt-1">تست سریع یک مدل یا مقایسه هم‌زمان چند مدل</div></div>
        <div class="inline-flex p-1 rounded-lg bg-[var(--s1)] border border-[var(--b1)]">
          <button type="button" class="test-mode-btn px-3 h-8 rounded-md text-[11px] font-bold bg-[var(--accent)] text-white" data-mode="quick" onclick="setTestMode('quick')">تست سریع</button>
          <button type="button" class="test-mode-btn px-3 h-8 rounded-md text-[11px] font-bold text-[var(--text3)]" data-mode="compare" onclick="setTestMode('compare')">مقایسه مدل‌ها</button>
        </div>
      </div>

      <div class="mb-4">
        <label class="text-[11px] font-bold text-[var(--text2)] block mb-2">مدل‌های مورد آزمایش</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="test-model-grid">
          @foreach($aiModels as $index => $model)
            <label class="test-model-card flex items-center gap-2.5 p-3 rounded-xl bg-[var(--s1)] border border-[var(--b1)] cursor-pointer">
              <input type="checkbox" class="test-model-checkbox row-checkbox" value="{{ $model->openrouter_model_id }}" {{ $index === 0 ? 'checked' : '' }} onchange="onTestModelSelection(this)">
              <span class="min-w-0"><strong class="block text-[11.5px] text-[var(--text)] truncate">{{ $model->name }}</strong><small class="block text-[9.5px] text-[var(--text3)] font-mono truncate" dir="ltr">{{ $model->openrouter_model_id }}</small></span>
            </label>
          @endforeach
        </div>
        <div id="test-model-help" class="text-[10px] text-[var(--text3)] mt-2">در تست سریع فقط یک مدل انتخاب می‌شود.</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <label class="text-[11px] text-[var(--text2)]">رزولوشن<select id="test-resolution" class="input-pro w-full mt-1.5"><option>1K</option><option>2K</option><option>4K</option></select></label>
        <label class="text-[11px] text-[var(--text2)]">نسبت تصویر<select id="test-aspect-ratio" class="input-pro w-full mt-1.5"><option>1:1</option><option>4:5</option><option>3:4</option><option>9:16</option><option>16:9</option></select></label>
        <label class="text-[11px] text-[var(--text2)]">Seed<input id="test-seed" type="number" class="input-pro w-full mt-1.5" placeholder="تصادفی"></label>
      </div>

      <details class="rounded-xl border border-[var(--b1)] bg-[var(--s1)] mb-4">
        <summary class="p-3 text-[11px] font-bold text-[var(--text2)] cursor-pointer">مشاهده پرامپت نهایی</summary>
        <pre id="test-final-prompt" class="p-3 pt-0 text-[10.5px] leading-6 text-[var(--text2)] whitespace-pre-wrap break-words font-mono ltr text-left"></pre>
      </details>

      <button type="button" id="run-product-test" onclick="runProductTests()" class="w-full h-12 rounded-xl bg-[var(--accent)] text-white text-sm font-bold flex items-center justify-center gap-2"><i class="fa-solid fa-play"></i><span>اجرای تست سریع</span></button>
      <div class="text-[10px] text-[var(--text3)] text-center mt-2">انتشار محصول به اجرای تست وابسته نیست.</div>
    </section>

    <section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
      <div class="flex items-center justify-between gap-2 mb-4"><div><div class="text-xs font-bold text-[var(--text)]">خروجی‌های آزمایش</div><div class="text-[10.5px] text-[var(--text3)] mt-1">در حالت مقایسه، خروجی مدل‌ها کامل و کنار هم نمایش داده می‌شوند.</div></div><span id="test-running-badge" class="hidden badge-pro badge-info">در حال اجرا…</span></div>
      <div id="test-results-empty" class="empty-state py-8"><div class="empty-state-icon"><i class="fa-solid fa-vials"></i></div><div class="empty-state-title">هنوز آزمایشی اجرا نشده است</div></div>
      <div id="test-results-grid" class="grid grid-cols-1 lg:grid-cols-2 gap-4"></div>
    </section>

    <section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
      <div class="flex items-center justify-between gap-2 mb-4"><div class="text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-clock-rotate-left text-[var(--accent)] ml-2"></i>تاریخچه آزمایش‌ها</div><button type="button" onclick="loadTestHistory()" class="icon-action-btn" title="بروزرسانی"><i class="fa-solid fa-rotate"></i></button></div>
      <div id="test-history-list" class="space-y-2"><div class="text-[10.5px] text-[var(--text3)] text-center py-5">هنوز سابقه‌ای ثبت نشده است.</div></div>
    </section>
  </div>
</div>

{{-- گزینه‌های کم‌کاربرد از گام‌های دیگر، برای خلوت ماندن مسیر اصلی ثبت محصول --}}
<details class="mt-5 bg-[var(--s2)] border border-dashed border-[var(--b2)] rounded-xl overflow-hidden" id="future-updates-panel">
  <summary class="p-4 cursor-pointer flex items-center justify-between gap-3 text-xs font-bold text-[var(--text2)]">
    <span class="flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-[var(--accent)]"></i> آپدیت‌های آینده</span>
    <span class="text-[10px] font-normal text-[var(--text3)]">گزینه‌های کم‌استفاده فعلی</span>
  </summary>
  <div class="p-4 pt-0 border-t border-[var(--b1)]" id="future-updates-content">
    <div class="text-[10.5px] text-[var(--text3)] py-4 text-center" id="future-updates-empty">در حال آماده‌سازی گزینه‌ها…</div>
  </div>
</details>

<script>
var productTestMode = 'quick';
var productTestObjectUrls = [];
function testUuid() { return crypto.randomUUID ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16|0,v=c==='x'?r:(r&3|8);return v.toString(16);}); }
function ensureTestDraftUuid() { var el=document.getElementById('test-draft-uuid'); if(!el.value) el.value=localStorage.getItem((window.PRODUCT_CREATE_CONFIG?.autosaveKey||'product')+'-test-uuid')||testUuid(); localStorage.setItem((window.PRODUCT_CREATE_CONFIG?.autosaveKey||'product')+'-test-uuid',el.value); return el.value; }

function collectTestSchema() {
  var fd=new FormData(document.getElementById('real-product-form')), fields={};
  fd.forEach(function(value,key){ var m=key.match(/^input_schema\[(\d+)\]\[([^\]]+)\](?:\[(\d+)\]\[([^\]]+)\])?/); if(!m || value instanceof File) return; var i=m[1], prop=m[2]; fields[i] ||= {options:[]}; if(m[3]!==undefined){ fields[i].options[m[3]] ||= {}; fields[i].options[m[3]][m[4]]=value; } else fields[i][prop]=value; });
  return Object.values(fields).filter(function(f){return f.field_id && f.label_fa;}).sort(function(a,b){return Number(a.order||0)-Number(b.order||0);});
}
function renderTestUserExperience() {
  var wrap=document.getElementById('test-user-experience'); if(!wrap) return; var schema=collectTestSchema(); wrap.innerHTML='';
  if(!schema.length){ wrap.innerHTML='<div class="text-[10.5px] text-[var(--text3)] text-center py-8">در گام سوم هنوز فیلدی برای کاربر تعریف نشده است.</div>'; refreshTestFinalPrompt(); return; }
  schema.forEach(function(field){
    var box=document.createElement('div'); box.className='test-user-field'; box.dataset.fieldId=field.field_id;
    var label='<label class="text-[11px] font-bold text-[var(--text2)] block mb-1.5">'+escapeTestHtml(field.label_fa)+(String(field.required)==='1'?' <span class="text-[var(--danger)]">*</span>':'')+'</label>';
    var type=field.type||'text', control='';
    if(['select','radio','chips','style_picker','aspect_ratio'].includes(type)) { var opts=(field.options||[]).filter(Boolean); if(type==='select'||type==='aspect_ratio') control='<select class="test-ux-input input-pro w-full" data-id="'+field.field_id+'"><option value="">انتخاب کنید</option>'+opts.map(function(o){return '<option value="'+escapeTestHtml(o.value||o.label||'')+'" data-prompt="'+escapeTestHtml(o.prompt||'')+'">'+escapeTestHtml(o.label||o.value||'')+'</option>';}).join('')+'</select>'; else control='<div class="grid grid-cols-2 gap-2">'+opts.map(function(o,idx){return '<label class="p-2.5 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[10.5px] text-[var(--text2)] cursor-pointer"><input class="test-ux-input ml-1" type="radio" name="test_ux_'+field.field_id+'" data-id="'+field.field_id+'" value="'+escapeTestHtml(o.value||o.label||'')+'" data-prompt="'+escapeTestHtml(o.prompt||'')+'" '+(idx===0?'checked':'')+'> '+escapeTestHtml(o.label||o.value||'')+'</label>';}).join('')+'</div>'; }
    else if(['image','file','multi_image','before_image'].includes(type)) control='<input type="file" '+(type==='multi_image'?'multiple':'')+' accept="image/*" class="test-ux-input input-pro w-full" data-id="'+field.field_id+'">';
    else if(type==='textarea') control='<textarea class="test-ux-input input-pro w-full" rows="3" data-id="'+field.field_id+'" placeholder="'+escapeTestHtml(field.placeholder||'')+'"></textarea>';
    else if(type==='toggle'||type==='checkbox') control='<label class="flex items-center gap-2 text-[11px] text-[var(--text2)]"><input type="checkbox" class="test-ux-input row-checkbox" data-id="'+field.field_id+'" value="1"> فعال</label>';
    else control='<input type="'+(type==='number'||type==='range'?'number':'text')+'" class="test-ux-input input-pro w-full" data-id="'+field.field_id+'" value="'+escapeTestHtml(field.default||'')+'" placeholder="'+escapeTestHtml(field.placeholder||'')+'">';
    box.innerHTML=label+control+(field.help_text?'<div class="text-[9.5px] text-[var(--text3)] mt-1">'+escapeTestHtml(field.help_text)+'</div>':''); wrap.appendChild(box);
  });
  wrap.querySelectorAll('.test-ux-input').forEach(function(el){el.addEventListener('input',refreshTestFinalPrompt);el.addEventListener('change',refreshTestFinalPrompt);}); refreshTestFinalPrompt();
}
function escapeTestHtml(v){var d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
function collectTestInputs(){var out={},files=[];document.querySelectorAll('.test-ux-input').forEach(function(el){var id=el.dataset.id;if(!id)return;if(el.type==='file'){Array.from(el.files||[]).forEach(function(f){files.push(f);});out[id]=Array.from(el.files||[]).map(function(f){return f.name;});}else if((el.type==='radio'||el.type==='checkbox')&&!el.checked){}else out[id]=el.value;});return {values:out,files:files};}
function buildTestPrompt(){var template=document.getElementById('prompt-template')?.value||'', data=collectTestInputs().values;Object.keys(data).forEach(function(k){var val=Array.isArray(data[k])?data[k].join(', '):data[k];template=template.split('{'+k+'}').join(val||'');});var additions=[];document.querySelectorAll('.test-ux-input:checked option:checked,.test-ux-input option:checked,.test-ux-input:checked').forEach(function(el){if(el.dataset?.prompt)additions.push(el.dataset.prompt);});return [template,additions.join(', '),document.querySelector('[name="negative_prompt"]')?.value?'Avoid: '+document.querySelector('[name="negative_prompt"]').value:''].filter(Boolean).join('\n');}
function refreshTestFinalPrompt(){var el=document.getElementById('test-final-prompt');if(el)el.textContent=buildTestPrompt()||'پرامپت هنوز تعریف نشده است.';}
function setTestMode(mode){productTestMode=mode;document.querySelectorAll('.test-mode-btn').forEach(function(b){var active=b.dataset.mode===mode;b.classList.toggle('bg-[var(--accent)]',active);b.classList.toggle('text-white',active);b.classList.toggle('text-[var(--text3)]',!active);});var checks=Array.from(document.querySelectorAll('.test-model-checkbox'));if(mode==='quick'){var first=checks.find(function(c){return c.checked;})||checks[0];checks.forEach(function(c){c.checked=c===first;});}document.getElementById('test-model-help').textContent=mode==='quick'?'در تست سریع فقط یک مدل انتخاب می‌شود.':'دو یا چند مدل را برای مقایسه کنار هم انتخاب کنید.';document.querySelector('#run-product-test span').textContent=mode==='quick'?'اجرای تست سریع':'اجرای مقایسه مدل‌ها';}
function onTestModelSelection(changed){if(productTestMode==='quick'&&changed.checked)document.querySelectorAll('.test-model-checkbox').forEach(function(c){if(c!==changed)c.checked=false;});}
async function runProductTests(){var models=Array.from(document.querySelectorAll('.test-model-checkbox:checked')).map(function(c){return c.value;});if(!models.length)return alert('حداقل یک مدل را انتخاب کنید.');if(productTestMode==='compare'&&models.length<2)return alert('برای مقایسه حداقل دو مدل انتخاب کنید.');var prompt=buildTestPrompt().trim();if(!prompt)return alert('پرامپت محصول خالی است.');var btn=document.getElementById('run-product-test'),badge=document.getElementById('test-running-badge'),grid=document.getElementById('test-results-grid');btn.disabled=true;badge.classList.remove('hidden');document.getElementById('test-results-empty').classList.add('hidden');grid.innerHTML='';var batch=testUuid(),inputs=collectTestInputs();await Promise.all(models.map(async function(model){var holder=createTestResultCard(model);grid.appendChild(holder);var fd=new FormData();fd.append('_token',document.querySelector('[name="_token"]').value);fd.append('model_id',model);fd.append('prompt',prompt);fd.append('prompt_template',document.getElementById('prompt-template')?.value||'');fd.append('negative_prompt',document.querySelector('[name="negative_prompt"]')?.value||'');fd.append('product_id',@json($testProduct?->id));fd.append('draft_uuid',ensureTestDraftUuid());fd.append('batch_uuid',batch);fd.append('mode',productTestMode);fd.append('resolution',document.getElementById('test-resolution').value);fd.append('aspect_ratio',document.getElementById('test-aspect-ratio').value);if(document.getElementById('test-seed').value)fd.append('seed',document.getElementById('test-seed').value);fd.append('input_values_json',JSON.stringify(inputs.values));inputs.files.forEach(function(file){fd.append('reference_images[]',file);});try{var res=await fetch(@json(route('admin.ai-models.test-prompt')),{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}),data=await res.json();renderTestResultCard(holder,data,model);}catch(e){renderTestResultCard(holder,{success:false,message:e.message},model);}}));btn.disabled=false;badge.classList.add('hidden');loadTestHistory();}
function createTestResultCard(model){var card=document.createElement('article');card.className='rounded-xl border border-[var(--b1)] bg-[var(--s1)] overflow-hidden';card.innerHTML='<div class="aspect-square flex items-center justify-center text-[var(--text3)]"><i class="fa-solid fa-spinner fa-spin text-xl"></i></div><div class="p-3 text-[10px] font-mono text-[var(--text3)] truncate" dir="ltr">'+escapeTestHtml(model)+'</div>';return card;}
function renderTestResultCard(card,data,model){if(!data.success){card.innerHTML='<div class="aspect-square flex items-center justify-center p-5 text-center text-[11px] text-[var(--danger)]">'+escapeTestHtml(data.message||'اجرای مدل ناموفق بود')+'</div><div class="p-3 text-[10px] font-mono text-[var(--text3)] truncate" dir="ltr">'+escapeTestHtml(model)+'</div>';return;}var tokens=data.usage?.total_tokens;card.dataset.runId=data.run_id;card.innerHTML='<a href="'+data.image_url+'" target="_blank" class="block aspect-square bg-[var(--bg)]"><img src="'+data.image_url+'" class="w-full h-full object-contain" alt="خروجی آزمایش"></a><div class="p-3"><div class="text-[10px] font-mono text-[var(--text2)] truncate mb-2" dir="ltr">'+escapeTestHtml(model)+'</div><div class="grid grid-cols-2 gap-2 mb-3"><div><small class="block text-[9px] text-[var(--text3)]">زمان اجرا</small><strong class="text-[11px] text-[var(--text)]">'+formatTestDuration(data.duration_ms)+'</strong></div><div><small class="block text-[9px] text-[var(--text3)]">مصرف توکن</small><strong class="text-[11px] text-[var(--text)]">'+(tokens==null?'اعلام نشده':Number(tokens).toLocaleString('fa-IR'))+'</strong></div></div>'+testFeedbackHtml(data.run_id,null,'',false)+'</div>';wireTestFeedback(card);}
function testFeedbackHtml(id,rating,note,fav){return '<div class="flex items-center justify-between gap-2 border-t border-[var(--b1)] pt-2"><div class="flex gap-1" data-rating>'+[1,2,3,4,5].map(function(n){return '<button type="button" data-score="'+n+'" class="text-[12px] '+(rating>=n?'text-[var(--warning)]':'text-[var(--text3)]')+'"><i class="fa-solid fa-star"></i></button>';}).join('')+'</div><button type="button" data-favorite class="icon-action-btn '+(fav?'is-active':'')+'" title="انتخاب نتیجه برتر"><i class="fa-solid fa-trophy"></i></button></div><textarea data-note rows="2" class="input-pro w-full mt-2" placeholder="یادداشت ارزیابی…">'+escapeTestHtml(note||'')+'</textarea><button type="button" data-save-feedback data-run="'+id+'" class="btn-pro btn-pro-ghost w-full mt-2">ذخیره ارزیابی</button>';}
function wireTestFeedback(root){root.querySelectorAll('[data-rating] button').forEach(function(btn){btn.onclick=function(){root.dataset.rating=btn.dataset.score;root.querySelectorAll('[data-rating] button').forEach(function(b){b.classList.toggle('text-[var(--warning)]',Number(b.dataset.score)<=Number(btn.dataset.score));b.classList.toggle('text-[var(--text3)]',Number(b.dataset.score)>Number(btn.dataset.score));});};});root.querySelector('[data-favorite]')?.addEventListener('click',function(){this.classList.toggle('is-active');});root.querySelector('[data-save-feedback]')?.addEventListener('click',async function(){var id=this.dataset.run;await fetch(@json(url('/admin/product-tests'))+'/'+id,{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('[name="_token"]').value,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({rating:root.dataset.rating||null,note:root.querySelector('[data-note]')?.value||'',is_favorite:root.querySelector('[data-favorite]')?.classList.contains('is-active')||false})});this.textContent='ذخیره شد';});}
function formatTestDuration(ms){if(ms==null)return '—';return ms<1000?ms.toLocaleString('fa-IR')+' میلی‌ثانیه':(ms/1000).toLocaleString('fa-IR',{maximumFractionDigits:1})+' ثانیه';}
async function loadTestHistory(){var url=@json(route('admin.product-tests.history'))+'?'+(@json($testProduct?->id)?'product_id='+@json($testProduct?->id):'draft_uuid='+ensureTestDraftUuid());try{var data=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json();}),list=document.getElementById('test-history-list');if(!data.runs?.length){list.innerHTML='<div class="text-[10.5px] text-[var(--text3)] text-center py-5">هنوز سابقه‌ای ثبت نشده است.</div>';return;}list.innerHTML=data.runs.map(function(r){return '<div class="flex items-center gap-3 p-2.5 rounded-xl bg-[var(--s1)] border border-[var(--b1)]">'+(r.image_url?'<img src="'+r.image_url+'" class="w-14 h-14 rounded-lg object-cover">':'<div class="w-14 h-14 rounded-lg flex items-center justify-center text-[var(--danger)]"><i class="fa-solid fa-triangle-exclamation"></i></div>')+'<div class="min-w-0 flex-1"><div class="text-[10px] font-mono text-[var(--text2)] truncate" dir="ltr">'+escapeTestHtml(r.model)+'</div><div class="text-[9.5px] text-[var(--text3)] mt-1">'+formatTestDuration(r.duration_ms)+' · '+(r.total_tokens==null?'توکن اعلام نشده':Number(r.total_tokens).toLocaleString('fa-IR')+' توکن')+'</div></div>'+(r.is_favorite?'<i class="fa-solid fa-trophy text-[var(--warning)]"></i>':'')+'</div>';}).join('');}catch(e){} }
function initProductTestLab(){ensureTestDraftUuid();renderTestUserExperience();loadTestHistory();}

function moveFutureProductUpdates() {
  var target = document.getElementById('future-updates-content');
  var empty = document.getElementById('future-updates-empty');
  if (!target) return;
  var items = Array.from(document.querySelectorAll('[data-future-update]'));
  items.forEach(function(item) {
    var section = document.createElement('section');
    section.className = 'pt-4 mt-4 border-t border-[var(--b1)]';
    var title = document.createElement('div');
    title.className = 'text-[11px] font-bold text-[var(--text2)] mb-3 flex items-center gap-2';
    title.innerHTML = '<i class="fa-solid fa-layer-group text-[var(--accent)]"></i>' + item.dataset.futureUpdate;
    section.appendChild(title);
    item.classList.remove('hidden');
    section.appendChild(item);
    target.appendChild(section);
  });
  if (empty) empty.classList.toggle('hidden', items.length > 0);
}
document.addEventListener('DOMContentLoaded', moveFutureProductUpdates);
</script>
