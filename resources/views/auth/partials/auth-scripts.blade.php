<script>
  const authState={phone:'',sending:false,verifying:false,registering:false,resendTimer:null,expiresAt:0};
  const csrf=()=>document.querySelector('meta[name="csrf-token"]').content;
  const fa='۰۱۲۳۴۵۶۷۸۹';
  const decimalZeroPoints=[0x30,0x660,0x6f0,0x7c0,0x966,0x9e6,0xa66,0xae6,0xb66,0xbe6,0xc66,0xce6,0xd66,0xe50,0xed0,0xf20,0x1040,0x1090,0x17e0,0x1810,0x1946,0x19d0,0x1a80,0x1a90,0x1b50,0x1bb0,0x1c40,0x1c50,0xa620,0xa8d0,0xa900,0xa9d0,0xa9f0,0xaa50,0xabf0,0xff10,0x104a0,0x10d30,0x11066,0x110f0,0x11136,0x111d0,0x112f0,0x11450,0x114d0,0x11650,0x116c0,0x11730,0x118e0,0x11950,0x11c50,0x11d50,0x11da0,0x11f50,0x16a60,0x16ac0,0x16b50,0x16e80,0x1d7ce,0x1d7d8,0x1d7e2,0x1d7ec,0x1d7f6,0x1e140,0x1e2f0,0x1e4f0,0x1e950];
  function normalizeDigits(value){return Array.from(String(value??'')).map(char=>{const point=char.codePointAt(0),zero=decimalZeroPoints.find(base=>point>=base&&point<=base+9);return zero===undefined?char:String(point-zero)}).join('')}
  function normalizePhone(value){let phone=normalizeDigits(value).trim().replace(/[\s\-()]/g,'');if(phone.startsWith('+98'))phone='0'+phone.slice(3);else if(phone.startsWith('0098'))phone='0'+phone.slice(4);else if(phone.startsWith('98')&&phone.length===12)phone='0'+phone.slice(2);else if(/^9\d{9}$/.test(phone))phone='0'+phone;return phone}
  function toFa(value){return String(value).replace(/\d/g,d=>fa[d])}
  function activeStep(){return document.querySelector('.auth-step.active')?.id}
  function updateStageHeight(){requestAnimationFrame(()=>{const stage=document.getElementById('step-stage'),step=document.querySelector('.auth-step.active');if(stage&&step)stage.style.height=`${step.scrollHeight}px`})}
  function goToStep(id){document.querySelectorAll('.auth-step').forEach(step=>step.classList.toggle('active',step.id===id));updateStageHeight()}
  function setLoading(show,text='در حال انجام...'){const el=document.getElementById('auth-loading');document.getElementById('auth-loading-text').textContent=text;el.classList.toggle('is-visible',show);el.setAttribute('aria-hidden',show?'false':'true')}
  function showError(id,message,wrapId){const error=document.getElementById(id);error.textContent=message;error.classList.remove('hidden');if(wrapId)document.getElementById(wrapId)?.classList.add('has-error');updateStageHeight()}
  function clearError(id,wrapId){document.getElementById(id)?.classList.add('hidden');if(wrapId)document.getElementById(wrapId)?.classList.remove('has-error')}
  async function jsonRequest(url,body){const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(body)});let data={};try{data=await response.json()}catch{}if(!response.ok||data.status!=='success')throw new Error(data.message||'در ارتباط با سرور مشکلی پیش آمد.');return data}

  async function sendCode(){
    if(authState.sending)return;
    const input=document.getElementById('phone-input'),phone=normalizePhone(input.value);input.value=phone;
    if(!/^09\d{9}$/.test(phone)){showError('phone-error','شماره موبایل معتبر نیست.','phone-wrap');return}
    clearError('phone-error','phone-wrap');authState.phone=phone;authState.sending=true;
    const button=document.getElementById('send-code-button');button.disabled=true;
    document.getElementById('otp-phone-display').textContent=toFa(phone);resetOtp();goToStep('step-otp');setLoading(true,'در حال ارسال کد ورود...');
    try{const data=await jsonRequest('/auth/unified/send-otp',{phone});startResendTimer(data.resend_in||60,data.expires_in||180);setLoading(false);focusFirstOtp()}
    catch(error){setLoading(false);goToStep('step-phone');showError('phone-error',error.message,'phone-wrap')}
    finally{authState.sending=false;button.disabled=false}
  }
  function backToPhone(){if(authState.sending||authState.verifying)return;goToStep('step-phone');setTimeout(()=>document.getElementById('phone-input').focus(),40)}
  function resetOtp(){document.querySelectorAll('.otp-box').forEach(box=>{box.value='';box.classList.remove('has-error')});clearError('otp-error')}
  function focusFirstOtp(){const box=document.querySelector('.otp-box');setTimeout(()=>box?.focus({preventScroll:true}),40)}
  function startResendTimer(seconds,expiresIn){clearInterval(authState.resendTimer);authState.expiresAt=Date.now()+expiresIn*1000;const link=document.getElementById('resend-link'),timer=document.getElementById('resend-timer');link.disabled=true;let left=seconds;const draw=()=>{timer.textContent=`(${toFa(left)} ثانیه)`;if(left--<=0){clearInterval(authState.resendTimer);link.disabled=false;timer.textContent=''}};draw();authState.resendTimer=setInterval(draw,1000)}
  async function resendCode(){if(authState.sending||document.getElementById('resend-link').disabled)return;await sendCodeForCurrentPhone()}
  async function sendCodeForCurrentPhone(){authState.sending=true;document.getElementById('resend-link').disabled=true;resetOtp();setLoading(true,'در حال ارسال مجدد کد...');try{const data=await jsonRequest('/auth/unified/send-otp',{phone:authState.phone});startResendTimer(data.resend_in||60,data.expires_in||180);setLoading(false);focusFirstOtp()}catch(error){setLoading(false);showError('otp-error',error.message);document.getElementById('resend-link').disabled=false}finally{authState.sending=false}}
  function otpCode(){return normalizeDigits([...document.querySelectorAll('.otp-box')].map(box=>box.value).join('')).replace(/\D/g,'').slice(0,5)}
  function fillOtp(value,submit=true){const code=normalizeDigits(value).replace(/\D/g,'').slice(0,5),boxes=[...document.querySelectorAll('.otp-box')];boxes.forEach((box,i)=>box.value=code[i]||'');clearError('otp-error');if(code.length===5&&submit)verifyCode();else boxes[code.length]?.focus()}
  async function verifyCode(){
    if(authState.verifying||authState.sending)return;const code=otpCode(),boxes=[...document.querySelectorAll('.otp-box')];
    if(code.length!==5){boxes.forEach(box=>{if(!box.value)box.classList.add('has-error')});return}
    if(Date.now()>authState.expiresAt){showError('otp-error','کد منقضی شده است؛ کد جدید بگیر.');return}
    authState.verifying=true;setLoading(true,'در حال تأیید شماره...');
    try{const data=await jsonRequest('/auth/unified/verify-otp',{phone:authState.phone,code});if(data.next==='redirect'){window.location.href=data.redirect;return}setLoading(false);goToStep('step-profile');setTimeout(()=>document.getElementById('name-input').focus({preventScroll:true}),50)}
    catch(error){setLoading(false);showError('otp-error',error.message);boxes.forEach(box=>{box.value='';box.classList.add('has-error')});document.getElementById('otp-boxes').classList.remove('shake-effect');void document.getElementById('otp-boxes').offsetWidth;document.getElementById('otp-boxes').classList.add('shake-effect');focusFirstOtp()}
    finally{authState.verifying=false}
  }
  function validEmail(value){return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)}
  function syncTermsConsent(){const checkbox=document.getElementById('terms-consent'),button=document.getElementById('profile-submit-button'),icon=document.getElementById('profile-submit-icon');if(!checkbox||!button)return;const accepted=checkbox.checked;button.disabled=!accepted;button.setAttribute('aria-disabled',accepted?'false':'true');if(icon)icon.className=accepted?'fa-solid fa-check':'fa-solid fa-lock';if(accepted)clearError('terms-error')}
  function normalizedNumericInput(id,maxLength){const input=document.getElementById(id),value=normalizeDigits(input.value).replace(/\D/g,'').slice(0,maxLength);input.value=value;return value}
  async function completeProfile(){
    if(authState.registering)return;const name=document.getElementById('name-input').value.trim(),last=document.getElementById('lastname-input').value.trim(),email=document.getElementById('email-input').value.trim();let valid=true;
    if(!name){showError('name-error','نام را وارد کنید.','name-wrap');valid=false}else clearError('name-error','name-wrap');if(!last){showError('lastname-error','نام خانوادگی را وارد کنید.','lastname-wrap');valid=false}else clearError('lastname-error','lastname-wrap');
    const termsAccepted=document.getElementById('terms-consent')?.checked===true;if(!termsAccepted){showError('terms-error','برای ورود، پذیرش قوانین و شرایط استفاده لازم است.');valid=false}else clearError('terms-error');
    const dayValue=normalizedNumericInput('birth-day-input',2),yearValue=normalizedNumericInput('birth-year-input',4),day=Number(dayValue),month=Number(document.getElementById('birth-month-input').value),year=Number(yearValue),current=Number(document.getElementById('birth-year-input').dataset.currentYear);let birthError='';
    if(!dayValue||day<1||day>31)birthError='روز تولد باید عددی بین ۱ تا ۳۱ باشد.';else if(!month)birthError='ماه تولد را انتخاب کنید.';else if(!yearValue||year<1250||year>current)birthError=`سال تولد باید بین ۱۲۵۰ تا ${toFa(current)} باشد.`;else if(month>6&&day>30)birthError='روز واردشده با ماه انتخاب‌شده سازگار نیست.';
    if(birthError){showError('birthdate-error',birthError,'birthdate-wrap');valid=false}else clearError('birthdate-error','birthdate-wrap');if(email&&!validEmail(email)){showError('email-error','ایمیل معتبر نیست.','email-wrap');valid=false}else clearError('email-error','email-wrap');if(!valid)return;
    authState.registering=true;setLoading(true,'در حال ساخت حساب و ورود...');
    try{const data=await jsonRequest('/auth/unified/register',{phone:authState.phone,name,last_name:last,email,birth_day:day,birth_month:month,birth_year:year,terms:termsAccepted?'1':''});localStorage.setItem('show_welcome_modal','true');localStorage.setItem('user_first_name',data.user_name||name);window.location.href=data.redirect}
    catch(error){setLoading(false);alert(error.message)}finally{authState.registering=false}
  }
  ['birth-day-input','birth-year-input'].forEach(id=>document.getElementById(id)?.addEventListener('input',()=>{normalizedNumericInput(id,id==='birth-day-input'?2:4);clearError('birthdate-error','birthdate-wrap')}));
  document.querySelectorAll('.otp-box').forEach((box,index,boxes)=>{box.addEventListener('input',()=>{const value=normalizeDigits(box.value).replace(/\D/g,'');if(value.length>1){fillOtp(value);return}box.value=value;box.classList.remove('has-error');clearError('otp-error');if(value&&index<boxes.length-1)boxes[index+1].focus();if([...boxes].every(item=>item.value))verifyCode()});box.addEventListener('keydown',event=>{if(event.key==='Backspace'&&!box.value&&index>0)boxes[index-1].focus()});box.addEventListener('paste',event=>{const value=event.clipboardData?.getData('text')||'';if(normalizeDigits(value).replace(/\D/g,'')){event.preventDefault();fillOtp(value)}})});
  document.addEventListener('keydown',event=>{if(event.key!=='Enter'||event.shiftKey||event.ctrlKey||event.altKey||event.metaKey)return;const actions={'step-phone':sendCode,'step-otp':verifyCode,'step-profile':completeProfile};if(actions[activeStep()]){event.preventDefault();actions[activeStep()]()}});
  window.addEventListener('DOMContentLoaded',()=>{document.getElementById('terms-consent')?.addEventListener('change',syncTermsConsent);syncTermsConsent();goToStep('step-phone');setTimeout(()=>document.getElementById('phone-input').focus({preventScroll:true}),80)});window.addEventListener('resize',updateStageHeight);
</script>
