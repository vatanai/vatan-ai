    <!-- ══ PRODUCTS DASHBOARD PAGE ══ -->
    <div id="products-dashboard-page" class="hidden">

      <!-- Header row -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-extrabold tracking-[-0.4px]" style="color:var(--text);">داشبورد محصولات</h1>
          <div class="text-xs mt-[2px]" style="color:var(--text3);">نمای جامع عملکرد و سلامت محصولات AI</div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Range toggle -->
          <div class="flex p-[3px] gap-[2px] rounded-lg" style="background:var(--s1);border:1px solid var(--b1);">
            <button onclick="setProdRange(7,this)" class="pd-range px-3 py-[5px] rounded-md text-[11.5px] font-semibold border-none cursor-pointer" style="background:none;color:var(--text2);font-family:inherit;">۷ روز</button>
            <button onclick="setProdRange(30,this)" class="pd-range px-3 py-[5px] rounded-md text-[11.5px] font-semibold border-none cursor-pointer pd-range-active" style="background:var(--b2);color:var(--text);font-family:inherit;">۳۰ روز</button>
            <button onclick="setProdRange(90,this)" class="pd-range px-3 py-[5px] rounded-md text-[11.5px] font-semibold border-none cursor-pointer" style="background:none;color:var(--text2);font-family:inherit;">۳ ماه</button>
          </div>
          <a href="/admin/products" style="display:inline-flex;align-items:center;gap:6px;padding:0 12px;height:32px;border-radius:8px;font-size:12px;font-weight:600;background:var(--s1);border:1px solid var(--b1);color:var(--text2);text-decoration:none;">
            <i class="fa-solid fa-list text-[10px]"></i> لیست محصولات
          </a>
          <a href="/admin/products/create" style="display:inline-flex;align-items:center;gap:6px;padding:0 12px;height:32px;border-radius:8px;font-size:12px;font-weight:600;background:var(--accent);color:#fff;text-decoration:none;border:none;">
            <i class="fa-solid fa-plus text-[10px]"></i> محصول جدید
          </a>
        </div>
      </div>

      <!-- KPI Cards -->
      <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:18px;">
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid var(--green);">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">محصولات فعال</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۱۸</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;display:flex;align-items:center;gap:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۲ محصول جدید</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid var(--accent);">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">سفارشات ماه</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۲,۴۸۱</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;display:flex;align-items:center;gap:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۲۳٪ رشد</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid var(--green);">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">درآمد ماه</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۱.۴۴B</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;display:flex;align-items:center;gap:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۱۸٪</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid #14b8a6;">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">نرخ موفقیت AI</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۹۶.۵٪</div>
          <div style="font-size:10px;color:var(--green);margin-top:3px;display:flex;align-items:center;gap:3px;"><i class="fa-solid fa-arrow-up text-[8px]"></i> ۰.۸٪</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid var(--orange);">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">میانگین پردازش</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۱۸.۶s</div>
          <div style="font-size:10px;color:var(--orange);margin-top:3px;display:flex;align-items:center;gap:3px;"><i class="fa-solid fa-arrow-down text-[8px]"></i> ۲s کندتر</div>
        </div>
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;border-right:3px solid var(--red);">
          <div style="font-size:10.5px;color:var(--text2);margin-bottom:6px;">نرخ Fallback</div>
          <div style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-.5px;">۸٪</div>
          <div style="font-size:10px;color:var(--text3);margin-top:3px;">ثابت</div>
        </div>
      </div>

      <!-- Trend chart + Top Products -->
      <div style="display:grid;grid-template-columns:1fr 300px;gap:14px;margin-bottom:14px;">

        <!-- Trend Chart -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-chart-line" style="color:var(--accent);"></i> روند سفارشات و درآمد</div>
            <div style="display:flex;gap:14px;font-size:11px;color:var(--text2);">
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;background:var(--accent);border-radius:99px;display:inline-block;"></span>سفارشات</span>
              <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:3px;background:var(--green);border-radius:99px;display:inline-block;"></span>درآمد</span>
            </div>
          </div>
          <div style="height:200px;position:relative;">
            <canvas id="prodTrendChart"></canvas>
          </div>
        </div>

        <!-- Top 5 Products -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-trophy" style="color:var(--accent);"></i> پرفروش‌ترین‌ها</div>
            <a href="/admin/products" style="font-size:11px;color:var(--accent);text-decoration:none;">همه ←</a>
          </div>
          @php
          $topProds = [
            ['rank'=>1,'rankColor'=>'#eab308','emoji'=>'💼','name'=>'عکس لینکدین','cat'=>'افراد','pct'=>100,'barColor'=>'var(--accent)','orders'=>'۶۲۴','rev'=>'۳۱۲M'],
            ['rank'=>2,'rankColor'=>'#94a3b8','emoji'=>'✨','name'=>'آواتار انیمه','cat'=>'سرگرمی','pct'=>76,'barColor'=>'#ec4899','orders'=>'۴۷۵','rev'=>'۲۳۸M'],
            ['rank'=>3,'rankColor'=>'#b45309','emoji'=>'📸','name'=>'عکس پرتره','cat'=>'افراد','pct'=>58,'barColor'=>'var(--green)','orders'=>'۳۶۲','rev'=>'۱۸۱M'],
            ['rank'=>4,'rankColor'=>'#475569','emoji'=>'🎪','name'=>'بنر رویداد','cat'=>'رویدادها','pct'=>42,'barColor'=>'#3b82f6','orders'=>'۲۶۱','rev'=>'۱۳۱M'],
            ['rank'=>5,'rankColor'=>'#475569','emoji'=>'🐾','name'=>'عکس حیوانات','cat'=>'حیوانات','pct'=>29,'barColor'=>'var(--orange)','orders'=>'۱۸۳','rev'=>'۹۲M'],
          ];
          @endphp
          @foreach($topProds as $p)
          <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--b1);{{ $loop->last ? 'border-bottom:none;' : '' }}">
            <div style="width:20px;height:20px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;background:{{ $p['rankColor'] }}22;color:{{ $p['rankColor'] }};flex-shrink:0;">{{ $p['rank'] }}</div>
            <div style="width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:rgba(160,122,245,.08);">{{ $p['emoji'] }}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:12px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $p['name'] }}</div>
              <div style="font-size:9.5px;color:var(--text3);">{{ $p['cat'] }}</div>
            </div>
            <div style="width:60px;height:4px;background:var(--b1);border-radius:99px;overflow:hidden;flex-shrink:0;">
              <div style="height:100%;width:{{ $p['pct'] }}%;background:{{ $p['barColor'] }};border-radius:99px;"></div>
            </div>
            <div style="font-size:12px;font-weight:700;color:var(--text);min-width:30px;text-align:left;">{{ $p['orders'] }}</div>
          </div>
          @endforeach
        </div>
      </div>

      <!-- Product Health Table -->
      <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;"><i class="fa-solid fa-heart-pulse" style="color:var(--accent);"></i> سلامت محصولات</div>
          <select style="background:var(--s1);border:1px solid var(--b1);border-radius:6px;padding:5px 10px;font-size:11.5px;color:var(--text2);font-family:inherit;outline:none;cursor:pointer;">
            <option>همه دسته‌ها</option><option>افراد</option><option>سرگرمی</option><option>کسب‌وکار</option>
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--b1);">
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">محصول</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">وضعیت</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">سفارشات</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">درآمد</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">موفقیت AI</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">زمان</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">مدل</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;text-align:right;">ترند</th>
                <th style="font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:8px 12px;width:80px;"></th>
              </tr>
            </thead>
            <tbody>
              @php
              $products = [
                ['emoji'=>'💼','name'=>'عکس لینکدین','cat'=>'افراد','status'=>'فعال','statusColor'=>'var(--green)','orders'=>'۶۲۴','rev'=>'۳۱۲M','rate'=>97.8,'rateColor'=>'var(--green)','time'=>'۱۸.۶s','model'=>'flux-1.1-pro','modelColor'=>'var(--accent)','spark'=>[40,55,50,75,65,85,100],'sparkColor'=>'var(--accent)','id'=>1],
                ['emoji'=>'✨','name'=>'آواتار انیمه','cat'=>'سرگرمی','status'=>'فعال','statusColor'=>'var(--green)','orders'=>'۴۷۵','rev'=>'۲۳۸M','rate'=>95.0,'rateColor'=>'var(--green)','time'=>'۲۲.۴s','model'=>'flux-kontext','modelColor'=>'var(--accent)','spark'=>[60,70,65,80,75,90,100],'sparkColor'=>'#ec4899','id'=>2],
                ['emoji'=>'📸','name'=>'عکس پرتره','cat'=>'افراد','status'=>'فعال','statusColor'=>'var(--green)','orders'=>'۳۶۲','rev'=>'۱۸۱M','rate'=>98.0,'rateColor'=>'var(--green)','time'=>'۱۵.۲s','model'=>'flux-1.1-pro','modelColor'=>'var(--accent)','spark'=>[50,60,55,70,80,75,90],'sparkColor'=>'var(--green)','id'=>3],
                ['emoji'=>'🎪','name'=>'بنر رویداد','cat'=>'رویدادها','status'=>'فعال','statusColor'=>'var(--green)','orders'=>'۲۶۱','rev'=>'۱۳۱M','rate'=>93.0,'rateColor'=>'var(--orange)','time'=>'۲۸.۱s','model'=>'ideogram/v3','modelColor'=>'var(--orange)','spark'=>[80,70,65,60,50,55,45],'sparkColor'=>'var(--red)','id'=>4],
                ['emoji'=>'🐾','name'=>'عکس حیوانات','cat'=>'حیوانات','status'=>'فعال','statusColor'=>'var(--green)','orders'=>'۱۸۳','rev'=>'۹۲M','rate'=>96.0,'rateColor'=>'var(--green)','time'=>'۱۹.۸s','model'=>'flux-1.1-pro','modelColor'=>'var(--accent)','spark'=>[30,45,60,55,70,80,100],'sparkColor'=>'var(--orange)','id'=>5],
                ['emoji'=>'🏢','name'=>'لوگو کسب‌وکار','cat'=>'کسب‌وکار','status'=>'غیرفعال','statusColor'=>'var(--text3)','orders'=>'—','rev'=>'—','rate'=>0,'rateColor'=>'var(--text3)','time'=>'—','model'=>'sd-3.5','modelColor'=>'var(--text3)','spark'=>[],'sparkColor'=>'var(--b2)','id'=>6],
              ];
              @endphp
              @foreach($products as $pr)
              <tr style="border-bottom:1px solid var(--b1);{{ $loop->last ? 'border-bottom:none;' : '' }} {{ ($pr['status']??'')==='غیرفعال' ? 'opacity:.5;' : '' }}" onmouseover="this.style.background='rgba(255,255,255,.012)'" onmouseout="this.style.background=''">
                <td style="padding:10px 12px;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:26px;height:26px;border-radius:7px;background:rgba(160,122,245,.1);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;">{{ $pr['emoji'] }}</div>
                    <div>
                      <div style="font-size:12.5px;font-weight:700;color:var(--text);">{{ $pr['name'] }}</div>
                      <div style="font-size:9.5px;color:var(--text3);">{{ $pr['cat'] }}</div>
                    </div>
                  </div>
                </td>
                <td style="padding:10px 12px;">
                  <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;font-size:10.5px;font-weight:700;background:{{ $pr['statusColor'] }}18;color:{{ $pr['statusColor'] }};border:1px solid {{ $pr['statusColor'] }}30;">
                    {{ ($pr['status']??'')==='فعال' ? '<i class="fa-solid fa-circle" style="font-size:5px;"></i>&nbsp;' : '' }}{{ $pr['status'] }}
                  </span>
                </td>
                <td style="padding:10px 12px;font-size:12.5px;font-weight:700;color:var(--text);">{{ $pr['orders'] }}</td>
                <td style="padding:10px 12px;font-size:12px;font-weight:600;color:var(--green);">{{ $pr['rev'] }}</td>
                <td style="padding:10px 12px;">
                  @if($pr['rate'] > 0)
                  <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:52px;height:4px;background:var(--b1);border-radius:99px;overflow:hidden;">
                      <div style="height:100%;width:{{ $pr['rate'] }}%;background:{{ $pr['rateColor'] }};border-radius:99px;"></div>
                    </div>
                    <span style="font-size:11px;font-weight:700;color:{{ $pr['rateColor'] }};">{{ $pr['rate'] }}٪</span>
                  </div>
                  @else
                  <span style="color:var(--text3);font-size:11px;">—</span>
                  @endif
                </td>
                <td style="padding:10px 12px;font-size:11.5px;color:var(--text2);">{{ $pr['time'] }}</td>
                <td style="padding:10px 12px;font-size:10px;font-family:monospace;color:{{ $pr['modelColor'] }};">{{ $pr['model'] }}</td>
                <td style="padding:10px 12px;">
                  @if(count($pr['spark']))
                  <div style="display:inline-flex;align-items:flex-end;gap:2px;height:22px;">
                    @foreach($pr['spark'] as $sv)
                    <div style="width:5px;height:{{ $sv }}%;border-radius:2px 2px 0 0;background:{{ $pr['sparkColor'] }};opacity:{{ $loop->last ? 1 : 0.6 }};"></div>
                    @endforeach
                  </div>
                  @else
                  <span style="color:var(--text3);">—</span>
                  @endif
                </td>
                <td style="padding:10px 12px;">
                  <a href="/admin/products/{{ $pr['id'] }}" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a>
                  <a href="/admin/products/{{ $pr['id'] }}/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;">ویرایش</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Bottom row: Category + Model + Quick Actions -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">

        <!-- Category breakdown -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;margin-bottom:14px;"><i class="fa-solid fa-layer-group" style="color:var(--accent);"></i> دسته‌بندی‌ها</div>
          @php
          $cats = [
            ['emoji'=>'👤','name'=>'افراد / پرتره','pct'=>100,'color'=>'var(--accent)','count'=>'۷','rev'=>'۴۹۳M'],
            ['emoji'=>'✨','name'=>'سرگرمی','pct'=>57,'color'=>'#ec4899','count'=>'۴','rev'=>'۲۳۸M'],
            ['emoji'=>'💼','name'=>'کسب‌وکار','pct'=>43,'color'=>'#3b82f6','count'=>'۳','rev'=>'۱۳۱M'],
            ['emoji'=>'🎪','name'=>'رویدادها','pct'=>28,'color'=>'var(--green)','count'=>'۲','rev'=>'۹۲M'],
            ['emoji'=>'🐾','name'=>'حیوانات','pct'=>19,'color'=>'var(--orange)','count'=>'۱','rev'=>'۹۲M'],
          ];
          @endphp
          @foreach($cats as $c)
          <div style="display:flex;align-items:center;gap:9px;padding:8px 0;border-bottom:1px solid var(--b1);{{ $loop->last ? 'border-bottom:none;' : '' }}">
            <div style="width:26px;height:26px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;background:{{ $c['color'] }}18;">{{ $c['emoji'] }}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:11.5px;font-weight:600;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $c['name'] }}</div>
              <div style="width:100%;height:4px;background:var(--b1);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $c['pct'] }}%;background:{{ $c['color'] }};border-radius:99px;"></div>
              </div>
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--text);flex-shrink:0;">{{ $c['count'] }}</div>
            <div style="font-size:10.5px;color:var(--green);flex-shrink:0;min-width:38px;text-align:left;">{{ $c['rev'] }}</div>
          </div>
          @endforeach
        </div>

        <!-- AI Model performance -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;margin-bottom:14px;"><i class="fa-solid fa-microchip" style="color:var(--accent);"></i> عملکرد مدل‌های AI</div>
          @php
          $models = [
            ['name'=>'flux-1.1-pro','type'=>'primary','pct'=>67,'rate'=>98.2,'rateColor'=>'var(--green)'],
            ['name'=>'flux-kontext','type'=>'primary','pct'=>18,'rate'=>95.0,'rateColor'=>'var(--green)'],
            ['name'=>'sd-3.5-large','type'=>'fallback','pct'=>10,'rate'=>88.5,'rateColor'=>'var(--orange)'],
            ['name'=>'ideogram/v3','type'=>'fallback','pct'=>5,'rate'=>82.0,'rateColor'=>'var(--red)'],
          ];
          @endphp
          @foreach($models as $m)
          <div style="display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--b1);{{ $loop->last ? 'border-bottom:none;' : '' }}">
            <div style="flex:1;min-width:0;">
              <div style="font-size:10.5px;font-family:monospace;color:var(--text2);">{{ $m['name'] }}</div>
              <div style="font-size:9px;color:{{ $m['type']==='primary' ? 'var(--green)' : 'var(--orange)' }};margin-top:1px;">{{ $m['type'] }}</div>
            </div>
            <div style="width:70px;height:4px;background:var(--b1);border-radius:99px;overflow:hidden;flex-shrink:0;">
              <div style="height:100%;width:{{ $m['pct'] }}%;background:var(--accent);border-radius:99px;"></div>
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--text);min-width:28px;flex-shrink:0;">{{ $m['pct'] }}٪</div>
            <div style="font-size:10px;font-weight:700;color:{{ $m['rateColor'] }};min-width:38px;text-align:left;flex-shrink:0;">{{ $m['rate'] }}٪</div>
          </div>
          @endforeach
          <!-- Alerts -->
          <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--b1);">
            <div style="font-size:9.5px;font-weight:700;color:var(--text3);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">هشدارها</div>
            <div style="font-size:11px;color:var(--orange);display:flex;align-items:center;gap:6px;margin-bottom:5px;"><i class="fa-solid fa-triangle-exclamation text-[9px]"></i> ideogram/v3 — timeout rate ۱۸٪</div>
            <div style="font-size:11px;color:var(--orange);display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-clock text-[9px]"></i> بنر رویداد — زمان بالا (۲۸s)</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;padding:18px 20px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px;margin-bottom:14px;"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> دسترسی سریع</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            @php
            $actions = [
              ['icon'=>'fa-plus','color'=>'var(--green)','label'=>'محصول جدید','href'=>'/admin/products/create'],
              ['icon'=>'fa-list','color'=>'var(--accent)','label'=>'همه محصولات','href'=>'/admin/products'],
              ['icon'=>'fa-layer-group','color'=>'#3b82f6','label'=>'دسته‌بندی','href'=>'/admin/products/categories'],
              ['icon'=>'fa-tag','color'=>'var(--orange)','label'=>'قیمت‌گذاری','href'=>'/admin/products/pricing'],
              ['icon'=>'fa-cart-shopping','color'=>'#14b8a6','label'=>'سفارشات','href'=>'/admin/orders'],
              ['icon'=>'fa-chart-line','color'=>'var(--red)','label'=>'آنالیتیکس','href'=>'/admin/analytics'],
              ['icon'=>'fa-microchip','color'=>'var(--accent)','label'=>'Job Queue','href'=>'/admin/jobs'],
              ['icon'=>'fa-box-archive','color'=>'var(--text2)','label'=>'آرشیو','href'=>'/admin/products'],
            ];
            @endphp
            @foreach($actions as $a)
            <a href="{{ $a['href'] }}" style="display:flex;flex-direction:column;align-items:center;gap:7px;padding:12px 8px;border-radius:10px;text-decoration:none;background:var(--s1);border:1px solid var(--b1);cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='var(--b2)';this.style.background='var(--s2)'" onmouseout="this.style.borderColor='var(--b1)';this.style.background='var(--s1)'">
              <div style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;background:{{ $a['color'] }}18;color:{{ $a['color'] }};"><i class="fa-solid {{ $a['icon'] }}"></i></div>
              <div style="font-size:11px;font-weight:600;color:var(--text2);text-align:center;">{{ $a['label'] }}</div>
            </a>
            @endforeach
          </div>
        </div>

      </div>

    </div>
    <!-- END PRODUCTS DASHBOARD PAGE -->

