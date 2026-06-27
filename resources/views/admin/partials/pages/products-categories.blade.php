    <!-- ══ PRODUCTS CATEGORIES PAGE ══ -->
    <div id="products-categories-page" class="hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-extrabold tracking-[-0.4px]" style="color:var(--text);">دسته‌بندی‌ها</h1>
          <div class="text-xs mt-[2px]" style="color:var(--text3);">مدیریت دسته‌بندی محصولات AI</div>
        </div>
        <button onclick="pcatOpenModal()" style="display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:32px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;"><i class="fa-solid fa-plus text-[10px]"></i> دسته جدید</button>
      </div>

      <!-- Stats -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;text-align:center;"><div style="font-size:22px;font-weight:900;color:var(--accent);">۷</div><div style="font-size:10px;color:var(--text3);margin-top:2px;">کل دسته‌ها</div></div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;text-align:center;"><div style="font-size:22px;font-weight:900;color:var(--green);">۶</div><div style="font-size:10px;color:var(--text3);margin-top:2px;">فعال</div></div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;text-align:center;"><div style="font-size:22px;font-weight:900;color:var(--text);">۲۴</div><div style="font-size:10px;color:var(--text3);margin-top:2px;">کل محصول</div></div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;text-align:center;"><div style="font-size:22px;font-weight:900;color:var(--orange);">۳.۴</div><div style="font-size:10px;color:var(--text3);margin-top:2px;">میانگین محصول/دسته</div></div>
      </div>

      <!-- Categories Grid -->
      @php
      $cats2=[
        ['emoji'=>'👤','name'=>'افراد / پرتره','slug'=>'people','count'=>7,'rev'=>'۴۹۳M','color'=>'var(--accent)','active'=>true,'top'=>'عکس لینکدین'],
        ['emoji'=>'✨','name'=>'سرگرمی','slug'=>'entertainment','count'=>4,'rev'=>'۲۳۸M','color'=>'#ec4899','active'=>true,'top'=>'آواتار انیمه'],
        ['emoji'=>'💼','name'=>'کسب‌وکار','slug'=>'business','count'=>3,'rev'=>'۱۳۱M','color'=>'#3b82f6','active'=>true,'top'=>'لوگو برند'],
        ['emoji'=>'🎪','name'=>'رویدادها','slug'=>'events','count'=>2,'rev'=>'۹۲M','color'=>'var(--green)','active'=>true,'top'=>'بنر رویداد'],
        ['emoji'=>'🐾','name'=>'حیوانات','slug'=>'animals','count'=>1,'rev'=>'۹۲M','color'=>'var(--orange)','active'=>true,'top'=>'عکس حیوانات'],
        ['emoji'=>'🌿','name'=>'طبیعت','slug'=>'nature','count'=>0,'rev'=>'—','color'=>'#14b8a6','active'=>false,'top'=>'—'],
        ['emoji'=>'🎨','name'=>'هنر و خلاقیت','slug'=>'art','count'=>0,'rev'=>'—','color'=>'var(--red)','active'=>false,'top'=>'—'],
      ];
      @endphp
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
        @foreach($cats2 as $c2)
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;{{ !$c2['active'] ? 'opacity:.6;' : '' }}">
          <!-- Color bar -->
          <div style="height:4px;background:{{ $c2['color'] }};"></div>
          <div style="padding:16px 18px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:42px;height:42px;border-radius:11px;background:{{ $c2['color'] }}18;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">{{ $c2['emoji'] }}</div>
                <div>
                  <div style="font-size:13px;font-weight:800;color:var(--text);">{{ $c2['name'] }}</div>
                  <div style="font-size:10px;font-family:monospace;color:var(--text3);margin-top:1px;">/{{ $c2['slug'] }}</div>
                </div>
              </div>
              <!-- Toggle -->
              <div onclick="this.classList.toggle('on');this.style.background=this.classList.contains('on')?'var(--green)':'var(--b2)';" style="width:34px;height:18px;border-radius:99px;background:{{ $c2['active'] ? 'var(--green)' : 'var(--b2)' }};cursor:pointer;position:relative;transition:background .2s;flex-shrink:0;" class="{{ $c2['active']?'on':'' }}">
                <div style="width:13px;height:13px;border-radius:50%;background:#fff;position:absolute;top:2.5px;{{ $c2['active'] ? 'left:18px' : 'left:3px' }};transition:left .2s;"></div>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:10px 0;border-top:1px solid var(--b1);">
              <div><div style="font-size:9.5px;color:var(--text3);">محصولات</div><div style="font-size:15px;font-weight:800;color:var(--text);">{{ $c2['count'] }}</div></div>
              <div><div style="font-size:9.5px;color:var(--text3);">درآمد</div><div style="font-size:13px;font-weight:700;color:var(--green);">{{ $c2['rev'] }}</div></div>
            </div>
            @if($c2['top'] !== '—')
            <div style="font-size:10px;color:var(--text3);padding-top:8px;border-top:1px solid var(--b1);">پرفروش: <span style="color:var(--text2);font-weight:600;">{{ $c2['top'] }}</span></div>
            @endif
            <div style="display:flex;gap:6px;margin-top:12px;">
              <button onclick="pcatEdit('{{ $c2['name'] }}')" style="flex:1;height:30px;border-radius:7px;border:1px solid var(--b1);background:none;color:var(--text2);font-size:11px;cursor:pointer;font-family:inherit;">ویرایش</button>
              <button style="width:30px;height:30px;border-radius:7px;border:1px solid rgba(240,92,92,.3);background:rgba(240,92,92,.06);color:var(--red);font-size:11px;cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      <!-- Add/Edit Modal -->
      <div id="pcat-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:16px;padding:28px 28px 24px;width:420px;max-width:92vw;position:relative;">
          <div style="font-size:15px;font-weight:800;color:var(--text);margin-bottom:20px;" id="pcat-modal-title">دسته‌بندی جدید</div>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:grid;grid-template-columns:64px 1fr;gap:10px;">
              <div><label style="font-size:10.5px;color:var(--text2);display:block;margin-bottom:5px;">اموجی</label><input id="pcat-emoji" placeholder="🎨" maxlength="2" style="width:100%;height:40px;text-align:center;font-size:20px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;outline:none;"/></div>
              <div><label style="font-size:10.5px;color:var(--text2);display:block;margin-bottom:5px;">نام دسته‌بندی <span style="color:var(--red);">*</span></label><input id="pcat-name" placeholder="مثلاً: هنر و خلاقیت" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:13px;color:var(--text);font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/></div>
            </div>
            <div><label style="font-size:10.5px;color:var(--text2);display:block;margin-bottom:5px;">Slug</label><input id="pcat-slug" placeholder="art-creativity" style="width:100%;height:40px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;padding:0 12px;font-size:12px;font-family:monospace;color:var(--text);outline:none;box-sizing:border-box;" dir="ltr" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/></div>
            <div><label style="font-size:10.5px;color:var(--text2);display:block;margin-bottom:5px;">رنگ نمایشی</label>
              <div style="display:flex;gap:6px;">
                @foreach(['#a07af5','#0BBF53','#ec4899','#3b82f6','#f5923a','#f05c5c','#14b8a6'] as $clr)
                <div onclick="document.querySelectorAll('.pcat-clr').forEach(d=>d.style.transform='scale(1)');this.style.transform='scale(1.3)';document.getElementById('pcat-color-val').value='{{ $clr }}';" class="pcat-clr" style="width:24px;height:24px;border-radius:50%;background:{{ $clr }};cursor:pointer;transition:transform .15s;border:2px solid transparent;"></div>
                @endforeach
              </div>
              <input id="pcat-color-val" type="hidden" value="#a07af5"/>
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:20px;">
            <button onclick="pcatCloseModal()" style="flex:1;height:38px;border-radius:8px;border:1px solid var(--b1);background:none;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">انصراف</button>
            <button onclick="pcatSave()" style="flex:2;height:38px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">ذخیره دسته‌بندی</button>
          </div>
          <button onclick="pcatCloseModal()" style="position:absolute;top:14px;left:16px;width:26px;height:26px;border-radius:6px;border:1px solid var(--b1);background:none;color:var(--text3);cursor:pointer;font-size:12px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>

    </div>
    <!-- END PRODUCTS CATEGORIES PAGE -->
