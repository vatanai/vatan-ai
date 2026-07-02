    <!-- ══ PRODUCTS CREATE PAGE ══ -->
    <div id="products-create-page" class="hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-extrabold tracking-[-0.4px]" style="color:var(--text);">ثبت محصول جدید</h1>
          <div class="text-xs mt-[2px]" style="color:var(--text3);">تعریف و پیکربندی محصول AI جدید</div>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="setActiveSub(null,'محصولات','لیست محصولات','products-list-page')" style="height:32px;padding:0 14px;border-radius:8px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;">
            <i class="fa-solid fa-arrow-right text-[10px]"></i> بازگشت
          </button>
          <button onclick="pcSaveDraft()" style="height:32px;padding:0 14px;border-radius:8px;border:1px solid var(--b1);background:var(--s1);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">ذخیره پیش‌نویس</button>
          <button onclick="pcPublish()" style="height:32px;padding:0 14px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;"><i class="fa-solid fa-check text-[10px]"></i> انتشار</button>
        </div>
      </div>

      <!-- Step indicator -->
      <div style="display:flex;align-items:center;gap:0;margin-bottom:20px;background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 20px;">
        @php $steps=[['n'=>1,'label'=>'اطلاعات پایه','icon'=>'fa-circle-info'],['n'=>2,'label'=>'تنظیمات AI','icon'=>'fa-microchip'],['n'=>3,'label'=>'ورودی‌ها','icon'=>'fa-sliders'],['n'=>4,'label'=>'قیمت‌گذاری','icon'=>'fa-tag'],['n'=>5,'label'=>'انتشار','icon'=>'fa-paper-plane']]; @endphp
        @foreach($steps as $s)
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;">
          <div id="pc-step-circle-{{ $s['n'] }}" style="width:32px;height:32px;border-radius:50%;border:2px solid {{ $s['n']===1 ? 'var(--accent)' : 'var(--b2)' }};background:{{ $s['n']===1 ? 'var(--accent)' : 'var(--s1)' }};display:flex;align-items:center;justify-content:center;font-size:11px;color:{{ $s['n']===1 ? '#fff' : 'var(--text3)' }};z-index:1;position:relative;transition:all .2s;cursor:pointer;" onclick="pcGoStep({{ $s['n'] }})">
            <i class="fa-solid {{ $s['icon'] }}"></i>
          </div>
          <div style="font-size:10px;font-weight:600;color:{{ $s['n']===1 ? 'var(--accent)' : 'var(--text3)' }};" id="pc-step-label-{{ $s['n'] }}">{{ $s['label'] }}</div>
        </div>
        @if(!$loop->last)<div style="flex:none;width:60px;height:1px;background:var(--b2);margin-top:-18px;"></div>@endif
        @endforeach
      </div>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:14px;align-items:start;">
        <!-- Main Form -->
        <div>

          <!-- Step 1: اطلاعات پایه -->
          <div id="pc-step-1" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 24px;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;margin-bottom:16px;"><i class="fa-solid fa-circle-info" style="color:var(--accent);"></i> اطلاعات پایه</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div style="grid-column:1/-1;">
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">نام محصول <span style="color:var(--red);">*</span></label>
                <input id="pc-name" oninput="pcUpdatePreview()" placeholder="مثلاً: عکس لینکدین حرفه‌ای" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:13px;color:var(--text);font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">Slug (URL) <span style="color:var(--red);">*</span></label>
                <input id="pc-slug" placeholder="linkedin-professional-photo" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;font-family:monospace;color:var(--text);outline:none;box-sizing:border-box;" dir="ltr" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">دسته‌بندی</label>
                <select style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;color:var(--text2);font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'">
                  <option>انتخاب دسته‌بندی</option>
                  <option>افراد</option><option>سرگرمی</option><option>کسب‌وکار</option><option>رویدادها</option><option>حیوانات</option>
                </select>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">آیکون / اموجی</label>
                <input id="pc-emoji" oninput="pcUpdatePreview()" placeholder="💼" maxlength="2" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:18px;text-align:center;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div style="grid-column:1/-1;">
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">توضیحات</label>
                <textarea placeholder="توضیح کوتاه درباره این محصول AI..." rows="3" style="width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:10px 12px;font-size:12px;color:var(--text);font-family:inherit;outline:none;resize:none;box-sizing:border-box;line-height:1.6;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"></textarea>
              </div>
            </div>
          </div>

          <!-- Step 2: تنظیمات AI -->
          <div id="pc-step-2" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 24px;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;margin-bottom:16px;"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> تنظیمات AI</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">مدل اصلی <span style="color:var(--red);">*</span></label>
                <select style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;font-family:monospace;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'">
                  <option>flux-1.1-pro</option><option>flux-kontext</option><option>ideogram/v3</option><option>sd-3.5-large</option>
                </select>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">مدل Fallback</label>
                <select style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;font-family:monospace;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'">
                  <option>sd-3.5-large</option><option>flux-1.1-pro</option><option>ideogram/v3</option><option>ندارد</option>
                </select>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">نسبت تصویر</label>
                <div style="display:flex;gap:6px;">
                  @foreach(['1:1','4:3','16:9','9:16'] as $r)
                  <button onclick="this.parentElement.querySelectorAll('button').forEach(b=>b.style.background='var(--s1)');this.parentElement.querySelectorAll('button').forEach(b=>b.style.color='var(--text2)');this.style.background='var(--accent)';this.style.color='#fff';" style="flex:1;height:34px;border-radius:7px;border:1px solid var(--b1);background:{{ $r==='1:1'?'var(--accent)':'var(--s1)' }};color:{{ $r==='1:1'?'#fff':'var(--text2)' }};font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;">{{ $r }}</button>
                  @endforeach
                </div>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">کیفیت خروجی</label>
                <select style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;color:var(--text2);font-family:inherit;outline:none;box-sizing:border-box;">
                  <option>Ultra (2048×2048)</option><option>High (1024×1024)</option><option>Standard (512×512)</option>
                </select>
              </div>
              <div style="grid-column:1/-1;">
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">قالب پرامپت <span style="font-size:9.5px;color:var(--text3);font-weight:400;">— از @{{متغیر}} برای ورودی‌ها استفاده کنید</span></label>
                <textarea id="pc-prompt" rows="4" placeholder="A professional @{{gender}} person with @{{hair_color}} hair, wearing @{{outfit}}, @{{background}} background, LinkedIn profile photo, ultra realistic..." style="width:100%;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:10px 12px;font-size:12px;font-family:monospace;color:var(--text);outline:none;resize:none;box-sizing:border-box;line-height:1.7;direction:ltr;" dir="ltr" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"></textarea>
              </div>
            </div>
          </div>

          <!-- Step 3: ورودی‌ها -->
          <div id="pc-step-3" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 24px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
              <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-sliders" style="color:var(--accent);"></i> تعریف ورودی‌ها</div>
              <button onclick="pcAddField()" style="height:30px;padding:0 12px;border-radius:7px;border:1px dashed var(--accent);background:rgba(160,122,245,.07);color:var(--accent);font-size:11.5px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;"><i class="fa-solid fa-plus text-[9px]"></i> فیلد جدید</button>
            </div>
            <div id="pc-fields-list">
              @php $fields=[['key'=>'gender','label'=>'جنسیت','type'=>'select','req'=>true],['key'=>'hair_color','label'=>'رنگ مو','type'=>'select','req'=>false],['key'=>'outfit','label'=>'لباس','type'=>'text','req'=>false]]; @endphp
              @foreach($fields as $fi=>$f)
              <div class="pc-field-row" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;margin-bottom:6px;">
                <div style="width:22px;height:22px;border-radius:5px;background:var(--accent);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $fi+1 }}</div>
                <input value="{{ $f['key'] }}" placeholder="key" style="width:110px;height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:11px;font-family:monospace;color:var(--text);outline:none;" dir="ltr"/>
                <input value="{{ $f['label'] }}" placeholder="برچسب فارسی" style="flex:1;height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:12px;color:var(--text);outline:none;font-family:inherit;"/>
                <select style="height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:11px;color:var(--text2);font-family:inherit;outline:none;">
                  <option {{ $f['type']==='text'?'selected':'' }}>text</option>
                  <option {{ $f['type']==='select'?'selected':'' }}>select</option>
                  <option>number</option><option>image</option>
                </select>
                <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--text2);cursor:pointer;white-space:nowrap;"><input type="checkbox" {{ $f['req']?'checked':'' }} style="accent-color:var(--accent);"/>اجباری</label>
                <button onclick="this.closest('.pc-field-row').remove()" style="width:26px;height:26px;border-radius:6px;border:1px solid var(--b1);background:none;color:var(--red);cursor:pointer;font-size:11px;"><i class="fa-solid fa-trash"></i></button>
              </div>
              @endforeach
            </div>
          </div>

          <!-- Step 4: قیمت‌گذاری -->
          <div id="pc-step-4" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 24px;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;margin-bottom:16px;"><i class="fa-solid fa-tag" style="color:var(--accent);"></i> قیمت‌گذاری</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">قیمت (تومان) <span style="color:var(--red);">*</span></label>
                <input type="number" placeholder="500000" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:13px;font-weight:700;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">هزینه کردیت AI</label>
                <input type="number" placeholder="10" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:13px;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">حداکثر سفارش / کاربر</label>
                <input type="number" placeholder="بدون محدودیت" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;color:var(--text);outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </div>
              <div style="grid-column:1/-1;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                  <input type="checkbox" style="width:16px;height:16px;accent-color:var(--accent);" checked/>
                  <span style="font-size:12px;color:var(--text2);">فعال‌سازی تخفیف برای اشتراک‌های ویژه</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Step 5: انتشار -->
          <div id="pc-step-5" style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:20px 24px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;margin-bottom:16px;"><i class="fa-solid fa-paper-plane" style="color:var(--accent);"></i> تنظیمات انتشار</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">وضعیت انتشار</label>
                <select style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;color:var(--text);font-family:inherit;outline:none;box-sizing:border-box;">
                  <option>فعال — نمایش عمومی</option><option>پیش‌نویس — فقط ادمین</option><option>غیرفعال</option>
                </select>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px;">ترتیب نمایش</label>
                <input type="number" placeholder="0" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:13px;color:var(--text);outline:none;box-sizing:border-box;"/>
              </div>
              <div style="grid-column:1/-1;display:flex;flex-direction:column;gap:8px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" style="width:16px;height:16px;accent-color:var(--accent);" checked/><span style="font-size:12px;color:var(--text2);">نمایش در صفحه اکتشاف (Explore)</span></label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" style="width:16px;height:16px;accent-color:var(--accent);"/><span style="font-size:12px;color:var(--text2);">محصول ویژه (Featured)</span></label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" style="width:16px;height:16px;accent-color:var(--accent);" checked/><span style="font-size:12px;color:var(--text2);">اجازه دسترسی بلاگرها</span></label>
              </div>
            </div>
          </div>

        </div>

        <!-- Live Preview Sidebar -->
        <div style="position:sticky;top:0;">
          <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
            <div style="font-size:12px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:14px;">پیش‌نمایش کارت</div>
            <div style="background:var(--s1);border:1px solid var(--b1);border-radius:12px;padding:16px;margin-bottom:14px;">
              <div style="width:100%;aspect-ratio:1;border-radius:8px;background:linear-gradient(135deg,var(--b1),var(--b2));display:flex;align-items:center;justify-content:center;font-size:40px;margin-bottom:12px;" id="pc-prev-emoji">💼</div>
              <div style="font-size:14px;font-weight:800;color:var(--text);margin-bottom:3px;" id="pc-prev-name">نام محصول</div>
              <div style="font-size:10.5px;color:var(--text3);margin-bottom:10px;">افراد · flux-1.1-pro</div>
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:13px;font-weight:800;color:var(--accent);" id="pc-prev-price">۵۰۰,۰۰۰ تومان</span>
                <span style="font-size:10px;padding:3px 8px;border-radius:99px;background:rgba(11,191,83,.12);color:var(--green);font-weight:700;border:1px solid rgba(11,191,83,.2);">فعال</span>
              </div>
            </div>
            <div style="border-top:1px solid var(--b1);padding-top:12px;">
              <div style="font-size:10.5px;color:var(--text3);line-height:1.8;">
                <div style="margin-bottom:4px;display:flex;align-items:flex-start;gap:5px;"><i class="fa-solid fa-lightbulb" style="color:var(--orange);margin-top:2px;font-size:9px;"></i><span>از متغیرهای <code style="background:var(--b1);padding:1px 5px;border-radius:4px;font-size:10px;">@{{key}}</code> در پرامپت استفاده کن</span></div>
                <div style="display:flex;align-items:flex-start;gap:5px;"><i class="fa-solid fa-lightbulb" style="color:var(--orange);margin-top:2px;font-size:9px;"></i><span>Fallback مدل رو برای پایداری تنظیم کن</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <!-- END PRODUCTS CREATE PAGE -->
