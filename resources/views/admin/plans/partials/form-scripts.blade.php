<script>
let planFeatureIndex={{ count(old('features', $plan->features ?: [['title'=>'']])) }};
function addPlanFeature(){
  const list=document.getElementById('feature-list'),row=document.createElement('div');
  row.className='pb-feature';
  row.innerHTML=`<input class="pb-input" name="features[${planFeatureIndex}][title]" required placeholder="عنوان قابلیت"><input class="pb-input" name="features[${planFeatureIndex}][value]" placeholder="مقدار اختیاری"><select class="pb-select" name="features[${planFeatureIndex}][included]"><option value="yes">دارد</option><option value="limited">محدود</option><option value="no">ندارد</option></select><label class="pb-check"><input type="checkbox" name="features[${planFeatureIndex}][highlighted]" value="1"> مهم</label><button type="button" class="pb-btn pb-btn-sm pb-btn-danger" onclick="removePlanFeature(this)"><i class="fa-solid fa-trash"></i></button>`;
  list.appendChild(row); planFeatureIndex++;
}
function removePlanFeature(button){if(document.querySelectorAll('.pb-feature').length>1)button.closest('.pb-feature').remove();}
function normalizeDigits(value){return value.replace(/[۰-۹]/g,d=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/\D/g,'');}
document.querySelectorAll('.money-input').forEach(input=>{const format=()=>{const raw=normalizeDigits(input.value);input.value=raw?Number(raw).toLocaleString('en-US'):'';};input.addEventListener('input',format);format();});
function updatePlanPreview(){
  const price=normalizeDigits(document.getElementById('plan-price').value);
  document.getElementById('preview-name').textContent=document.getElementById('plan-name').value||'نام پلن';
  document.getElementById('preview-price').textContent=Number(price)===0?'رایگان':Number(price).toLocaleString('fa-IR')+' تومان';
  document.getElementById('preview-tokens').textContent=Number(document.getElementById('plan-tokens').value||0).toLocaleString('fa-IR');
  document.getElementById('preview-short').textContent=document.getElementById('plan-short').value||'مناسب برای گروه هدف شما';
  document.querySelectorAll('#desktop-plan-preview .vpc__name,#mobile-plan-preview .vpc__name').forEach(el=>el.textContent=document.getElementById('plan-name').value||'نام پلن');
  document.querySelectorAll('#desktop-plan-preview .vpc__fit,#mobile-plan-preview .vpc__fit').forEach(el=>el.textContent=document.getElementById('plan-short').value||'مناسب برای کاربران وطن استودیو');
  document.querySelectorAll('#desktop-plan-preview .vpc__price,#mobile-plan-preview .vpc__price').forEach(el=>el.innerHTML=Number(price)===0?'رایگان':Number(price).toLocaleString('fa-IR')+' <small>تومان / ماه</small>');
  document.querySelectorAll('#desktop-plan-preview .vpc__tokens,#mobile-plan-preview .vpc__tokens').forEach(el=>el.textContent=Number(document.getElementById('plan-tokens').value||0).toLocaleString('fa-IR')+' توکن');
}
['plan-name','plan-price','plan-tokens','plan-short'].forEach(id=>document.getElementById(id)?.addEventListener('input',updatePlanPreview));updatePlanPreview();
function selectPlanCardStyle(style){
  document.querySelectorAll('#desktop-plan-preview .vpc,#mobile-plan-preview .vpc').forEach(card=>{
    const previous=card.dataset.planStyle||'classic';
    card.classList.remove('vpc--'+previous);
    card.classList.add('vpc--'+style);
    card.dataset.planStyle=style;
  });
}
</script>
