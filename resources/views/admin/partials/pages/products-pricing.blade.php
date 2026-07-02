    <!-- ══ PRODUCTS PRICING PAGE ══ -->
    <div id="products-pricing-page" class="hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-extrabold tracking-[-0.4px]" style="color:var(--text);">قیمت‌گذاری</h1>
          <div class="text-xs mt-[2px]" style="color:var(--text3);">مدیریت قیمت، کردیت و تخفیف محصولات</div>
        </div>
        <button style="display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:32px;border-radius:8px;border:none;background:var(--green);color:#fff;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;"><i class="fa-solid fa-floppy-disk text-[10px]"></i> ذخیره همه تغییرات</button>
      </div>

      <!-- Revenue Overview -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;border-right:3px solid var(--green);">
          <div style="font-size:10px;color:var(--text3);margin-bottom:5px;">درآمد ماه جاری</div>
          <div style="font-size:20px;font-weight:900;color:var(--text);">۱.۴۴B</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۱۸٪ رشد</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;border-right:3px solid var(--accent);">
          <div style="font-size:10px;color:var(--text3);margin-bottom:5px;">میانگین سفارش</div>
          <div style="font-size:20px;font-weight:900;color:var(--text);">۵۸۰K</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۵٪</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;border-right:3px solid var(--orange);">
          <div style="font-size:10px;color:var(--text3);margin-bottom:5px;">تخفیف اعمال‌شده</div>
          <div style="font-size:20px;font-weight:900;color:var(--text);">۱۴۸M</div>
          <div style="font-size:10px;color:var(--orange);margin-top:3px;">۱۰.۳٪ از کل</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:10px;padding:14px 16px;border-right:3px solid #3b82f6;">
          <div style="font-size:10px;color:var(--text3);margin-bottom:5px;">کردیت مصرف‌شده</div>
          <div style="font-size:20px;font-weight:900;color:var(--text);">۲۴,۸۱۰</div>
          <div style="font-size:10px;color:var(--text3);margin-top:3px;">این ماه</div>
        </div>
      </div>

      <!-- Pricing Table -->
      <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;margin-bottom:14px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--b1);display:flex;align-items:center;justify-content:space-between;">
          <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-tag" style="color:var(--accent);"></i> قیمت‌گذاری به‌ازای محصول</div>
          <div style="font-size:11px;color:var(--text3);">تغییرات ذخیره‌نشده <span id="pp-unsaved" style="display:none;color:var(--orange);font-weight:700;">• ۱ آیتم</span></div>
        </div>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:var(--s1);">
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;text-align:right;">محصول</th>
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;text-align:right;">قیمت پایه (تومان)</th>
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;text-align:right;">کردیت AI</th>
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;text-align:right;">تخفیف (%)</th>
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;text-align:right;">قیمت نهایی</th>
              <th style="padding:10px 16px;font-size:10px;font-weight:700;color:var(--text3);letter-spacing:1.2px;text-transform:uppercase;">وضعیت</th>
            </tr>
          </thead>
          <tbody>
            @php
            $pricingData=[
              ['emoji'=>'💼','name'=>'عکس لینکدین','price'=>500000,'credit'=>10,'discount'=>0,'status'=>'فعال'],
              ['emoji'=>'✨','name'=>'آواتار انیمه','price'=>500000,'credit'=>12,'discount'=>10,'status'=>'فعال'],
              ['emoji'=>'📸','name'=>'عکس پرتره','price'=>450000,'credit'=>10,'discount'=>0,'status'=>'فعال'],
              ['emoji'=>'🎪','name'=>'بنر رویداد','price'=>600000,'credit'=>15,'discount'=>15,'status'=>'فعال'],
              ['emoji'=>'🐾','name'=>'عکس حیوانات','price'=>400000,'credit'=>8,'discount'=>0,'status'=>'فعال'],
              ['emoji'=>'🏢','name'=>'لوگو کسب‌وکار','price'=>700000,'credit'=>18,'discount'=>0,'status'=>'غیرفعال'],
            ];
            @endphp
            @foreach($pricingData as $pp)
            @php $final = intval($pp['price'] * (1 - $pp['discount']/100)); @endphp
            <tr style="border-top:1px solid var(--b1);{{ $pp['status']==='غیرفعال' ? 'opacity:.5;' : '' }}" onmouseover="this.style.background='rgba(255,255,255,.012)'" onmouseout="this.style.background=''">
              <td style="padding:10px 16px;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:16px;">{{ $pp['emoji'] }}</span>
                  <span style="font-size:13px;font-weight:700;color:var(--text);">{{ $pp['name'] }}</span>
                </div>
              </td>
              <td style="padding:10px 16px;">
                <input type="number" value="{{ $pp['price'] }}" oninput="document.getElementById('pp-unsaved').style.display='inline'" style="width:110px;height:32px;background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:12px;font-weight:700;color:var(--text);outline:none;font-family:inherit;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </td>
              <td style="padding:10px 16px;">
                <input type="number" value="{{ $pp['credit'] }}" style="width:70px;height:32px;background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:12px;color:var(--text);outline:none;font-family:inherit;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
              </td>
              <td style="padding:10px 16px;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="number" value="{{ $pp['discount'] }}" min="0" max="100" style="width:55px;height:32px;background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:12px;color:var(--text);outline:none;font-family:inherit;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--b1)'"/>
                  @if($pp['discount']>0)<span style="font-size:10px;padding:2px 6px;border-radius:99px;background:rgba(245,146,58,.12);color:var(--orange);font-weight:700;border:1px solid rgba(245,146,58,.2);">{{ $pp['discount'] }}٪</span>@endif
                </div>
              </td>
              <td style="padding:10px 16px;font-size:13px;font-weight:800;color:var(--green);">{{ number_format($final) }}</td>
              <td style="padding:10px 16px;">
                <span style="font-size:10.5px;padding:3px 8px;border-radius:99px;font-weight:700;background:{{ $pp['status']==='فعال' ? 'rgba(11,191,83,.12)' : 'rgba(168,196,168,.08)' }};color:{{ $pp['status']==='فعال' ? 'var(--green)' : 'var(--text3)' }};border:1px solid {{ $pp['status']==='فعال' ? 'rgba(11,191,83,.2)' : 'var(--b1)' }};">{{ $pp['status'] }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Discount Codes + Credit Packages -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

        <!-- Discount Codes -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-percent" style="color:var(--accent);"></i> کدهای تخفیف</div>
            <button style="height:28px;padding:0 10px;border-radius:7px;border:1px dashed var(--accent);background:rgba(160,122,245,.07);color:var(--accent);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:4px;"><i class="fa-solid fa-plus text-[9px]"></i> کد جدید</button>
          </div>
          @php $discounts=[['code'=>'VATAN20','pct'=>20,'used'=>142,'max'=>500,'exp'=>'۱۴۰۴/۰۵/۰۱','active'=>true],['code'=>'SUMMER30','pct'=>30,'used'=>89,'max'=>200,'exp'=>'۱۴۰۴/۰۴/۳۱','active'=>true],['code'=>'NEWUSER10','pct'=>10,'used'=>500,'max'=>500,'exp'=>'—','active'=>false]]; @endphp
          @foreach($discounts as $d)
          <div style="padding:10px 12px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;margin-bottom:6px;display:flex;align-items:center;gap:10px;{{ !$d['active'] ? 'opacity:.5;' : '' }}">
            <div style="flex:none;font-size:11px;font-family:monospace;font-weight:800;color:var(--accent);background:rgba(160,122,245,.1);padding:4px 8px;border-radius:6px;letter-spacing:1px;">{{ $d['code'] }}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:11px;color:var(--text2);">{{ $d['pct'] }}٪ تخفیف · {{ $d['used'] }}/{{ $d['max'] }} استفاده</div>
              <div style="font-size:10px;color:var(--text3);">انقضا: {{ $d['exp'] }}</div>
            </div>
            <div style="font-size:10px;height:20px;padding:0 7px;border-radius:99px;display:flex;align-items:center;background:{{ $d['active'] ? 'rgba(11,191,83,.12)' : 'rgba(168,196,168,.08)' }};color:{{ $d['active'] ? 'var(--green)' : 'var(--text3)' }};font-weight:700;border:1px solid {{ $d['active'] ? 'rgba(11,191,83,.2)' : 'var(--b1)' }};">{{ $d['active'] ? 'فعال' : 'تمام‌شده' }}</div>
          </div>
          @endforeach
        </div>

        <!-- Credit Packages -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-coins" style="color:var(--accent);"></i> پکیج‌های کردیت</div>
            <button style="height:28px;padding:0 10px;border-radius:7px;border:1px dashed var(--accent);background:rgba(160,122,245,.07);color:var(--accent);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:4px;"><i class="fa-solid fa-plus text-[9px]"></i> پکیج جدید</button>
          </div>
          @php $pkgs=[['credits'=>50,'price'=>'۱۵۰,۰۰۰','per'=>'۳,۰۰۰','badge'=>null,'popular'=>false],['credits'=>120,'price'=>'۳۰۰,۰۰۰','per'=>'۲,۵۰۰','badge'=>'پرفروش','popular'=>true],['credits'=>300,'price'=>'۶۵۰,۰۰۰','per'=>'۲,۱۶۶','badge'=>'صرفه‌جو','popular'=>false]]; @endphp
          <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($pkgs as $pk)
            <div style="padding:12px 14px;background:var(--s1);border:{{ $pk['popular'] ? '1px solid var(--accent)' : '1px solid var(--b1)' }};border-radius:9px;display:flex;align-items:center;gap:12px;">
              <div style="text-align:center;flex:none;width:52px;">
                <div style="font-size:18px;font-weight:900;color:{{ $pk['popular'] ? 'var(--accent)' : 'var(--text)' }};">{{ $pk['credits'] }}</div>
                <div style="font-size:9px;color:var(--text3);">کردیت</div>
              </div>
              <div style="flex:1;">
                <div style="font-size:13px;font-weight:800;color:var(--text);">{{ $pk['price'] }} تومان</div>
                <div style="font-size:10px;color:var(--text3);">هر کردیت: {{ $pk['per'] }} تومان</div>
              </div>
              @if($pk['badge'])<span style="font-size:10px;padding:2px 8px;border-radius:99px;background:{{ $pk['popular'] ? 'var(--accent)' : 'rgba(11,191,83,.12)' }};color:{{ $pk['popular'] ? '#fff' : 'var(--green)' }};font-weight:700;flex-shrink:0;">{{ $pk['badge'] }}</span>@endif
              <button style="width:26px;height:26px;border-radius:6px;border:1px solid var(--b1);background:none;color:var(--text3);cursor:pointer;font-size:10px;"><i class="fa-solid fa-pen"></i></button>
            </div>
            @endforeach
          </div>
        </div>

      </div>

    </div>
    <!-- END PRODUCTS PRICING PAGE -->
