    <!-- ══ DASHBOARD PAGE ══ -->
    <div id="dashboard-page">

      <div class="flex items-center justify-between mb-6 max-[600px]:flex-wrap max-[600px]:gap-[10px]">
        <div>
          <h1 class="text-xl font-extrabold text-watan-text tracking-[-0.4px] max-[480px]:text-[17px]">مرکز فرماندهی</h1>
          <div class="text-xs text-watan-text3 mt-[2px]">آخرین به‌روزرسانی: چند ثانیه پیش</div>
        </div>
        <div class="flex items-center gap-[6px] bg-green/[0.08] border border-green/[0.2] text-green text-[11px] font-bold py-[6px] px-3 rounded-lg">
          <div class="w-[6px] h-[6px] rounded-full bg-green shadow-[0_0_8px_rgb(var(--green))] animate-pulse"></div>
          لایو
        </div>
      </div>

      <!-- کارت‌های آمار -->
      <div class="grid grid-cols-4 gap-[14px] mb-5 max-[1100px]:grid-cols-2 max-[768px]:grid-cols-2 max-[768px]:gap-[10px] max-[600px]:grid-cols-1">
        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-green">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">درآمد امروز</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-green/[0.08] text-green"><i class="fa-solid fa-money-bill-wave"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۴۸٫۲M</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-up"></i> ۱۲٪</div>
            <div class="text-[11px] text-watan-text3">نسبت به دیروز</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-accent">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">تصاویر پردازش‌شده</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-accent/[0.1] text-accent"><i class="fa-solid fa-image"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۱٬۲۴۷</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-up"></i> ۸٪</div>
            <div class="text-[11px] text-watan-text3">امروز</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-cyan">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">کاربران فعال</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-cyan/[0.1] text-cyan"><i class="fa-solid fa-users"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۳۸۴</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-up"></i> ۵٪</div>
            <div class="text-[11px] text-watan-text3">آنلاین الان</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-red">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">تیکت‌های باز</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-red/[0.1] text-red"><i class="fa-solid fa-ticket"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۷</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-red"><i class="fa-solid fa-arrow-trend-up"></i> نیاز به رسیدگی</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-orange">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">ثبت‌نام امروز</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-orange/[0.1] text-orange"><i class="fa-solid fa-user-plus"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۶۳</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-up"></i> ۲۱٪</div>
            <div class="text-[11px] text-watan-text3">نسبت به دیروز</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-green">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">هزینه AI امروز</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-green/[0.08] text-green"><i class="fa-solid fa-microchip"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">$۱۸.۴</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-down"></i> ۳٪</div>
            <div class="text-[11px] text-watan-text3">بهینه‌تر از دیروز</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-accent">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">بلاگرهای فعال</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-accent/[0.1] text-accent"><i class="fa-solid fa-bullhorn"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۲۴</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-green"><i class="fa-solid fa-arrow-trend-up"></i> ۴</div>
            <div class="text-[11px] text-watan-text3">این هفته اضافه شد</div>
          </div>
        </div>

        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[18px] px-5 cursor-default transition-colors duration-200 hover:border-b2 before:content-[''] before:absolute before:top-0 before:right-0 before:w-[3px] before:h-full before:rounded-tr-[10px] before:rounded-br-[10px] before:opacity-0 before:transition-opacity before:duration-200 hover:before:opacity-100 max-[480px]:py-[14px] max-[480px]:px-4 before:bg-cyan">
          <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] font-semibold text-watan-text3">صف پردازش</div>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm bg-cyan/[0.1] text-cyan"><i class="fa-solid fa-list-check"></i></div>
          </div>
          <div class="text-[26px] font-extrabold text-watan-text tracking-[-0.5px] leading-[1.1] mb-[6px] max-[480px]:text-[22px]">۱۲</div>
          <div class="flex items-center gap-[6px]">
            <div class="flex items-center gap-[3px] text-[11px] font-bold text-cyan"><i class="fa-solid fa-hourglass-half"></i> در انتظار</div>
          </div>
        </div>
      </div>

      <!-- ══ بخش محصولات ══ -->
      <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:11px;font-weight:700;color:var(--watan-text3,#4d7a56);letter-spacing:2px;text-transform:uppercase;">محصولات AI</div>
        <a href="/admin/analytics" style="font-size:11px;color:var(--accent,#a07af5);text-decoration:none;opacity:.8;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.8">آنالیتیکس کامل ←</a>
      </div>
      <div class="grid grid-cols-4 gap-[12px] mb-5 max-[1100px]:grid-cols-2 max-[600px]:grid-cols-2" style="background:rgba(160,122,245,.04);border:1px solid rgba(160,122,245,.1);border-radius:14px;padding:14px;">
        <a href="/admin/products" style="text-decoration:none;" class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[15px] px-4 cursor-pointer transition-colors duration-200 hover:border-b2 block">
          <div class="flex items-center justify-between mb-2">
            <div class="text-[10.5px] font-semibold" style="color:var(--watan-text3,#4d7a56);">محصولات فعال</div>
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs" style="background:rgba(160,122,245,.1);color:var(--accent,#a07af5);"><i class="fa-solid fa-box-open"></i></div>
          </div>
          <div class="text-[22px] font-extrabold" style="color:var(--accent,#a07af5);">۱۸</div>
          <div class="text-[10px] mt-1" style="color:var(--watan-text3,#4d7a56);">از ۲۰ محصول کل</div>
        </a>
        <a href="/admin/orders" style="text-decoration:none;" class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[15px] px-4 cursor-pointer transition-colors duration-200 hover:border-b2 block">
          <div class="flex items-center justify-between mb-2">
            <div class="text-[10.5px] font-semibold" style="color:var(--watan-text3,#4d7a56);">سفارشات امروز</div>
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs" style="background:rgba(11,191,83,.08);color:var(--green,#0BBF53);"><i class="fa-solid fa-cart-shopping"></i></div>
          </div>
          <div class="text-[22px] font-extrabold" style="color:var(--green,#0BBF53);">۸۷</div>
          <div class="flex items-center gap-1 text-[10px] mt-1" style="color:var(--green,#0BBF53);"><i class="fa-solid fa-arrow-up" style="font-size:8px;"></i> ۱۱٪ نسبت به دیروز</div>
        </a>
        <div class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[15px] px-4 cursor-default transition-colors duration-200 hover:border-b2">
          <div class="flex items-center justify-between mb-2">
            <div class="text-[10.5px] font-semibold" style="color:var(--watan-text3,#4d7a56);">محبوب‌ترین محصول</div>
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs" style="background:rgba(234,179,8,.1);color:#eab308;"><i class="fa-solid fa-trophy"></i></div>
          </div>
          <div class="text-[13px] font-extrabold leading-tight" style="color:var(--watan-text,#fff);">عکس لینکدین</div>
          <div class="text-[10px] mt-1" style="color:var(--watan-text3,#4d7a56);">۶۲۴ سفارش این ماه</div>
        </div>
        <a href="/admin/analytics" style="text-decoration:none;" class="relative overflow-hidden bg-s1 border border-b1 rounded-[10px] py-[15px] px-4 cursor-pointer transition-colors duration-200 hover:border-b2 block">
          <div class="flex items-center justify-between mb-2">
            <div class="text-[10.5px] font-semibold" style="color:var(--watan-text3,#4d7a56);">نرخ موفقیت AI</div>
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs" style="background:rgba(11,191,83,.08);color:var(--green,#0BBF53);"><i class="fa-solid fa-microchip"></i></div>
          </div>
          <div class="text-[22px] font-extrabold" style="color:var(--green,#0BBF53);">۹۶.۵٪</div>
          <div class="text-[10px] mt-1" style="color:var(--watan-text3,#4d7a56);">۳.۵٪ نرخ خطا</div>
        </a>
      </div>
      <!-- quick product links -->
      <div class="flex items-center gap-[8px] mb-5 flex-wrap">
        <a href="/admin/products" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;font-size:11.5px;font-weight:600;background:rgba(160,122,245,.08);color:var(--accent,#a07af5);border:1px solid rgba(160,122,245,.15);text-decoration:none;" onmouseover="this.style.background='rgba(160,122,245,.14)'" onmouseout="this.style.background='rgba(160,122,245,.08)'"><i class="fa-solid fa-box-open" style="font-size:10px;"></i> لیست محصولات</a>
        <a href="/admin/products/create" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;font-size:11.5px;font-weight:600;background:rgba(11,191,83,.06);color:var(--green,#0BBF53);border:1px solid rgba(11,191,83,.15);text-decoration:none;" onmouseover="this.style.background='rgba(11,191,83,.12)'" onmouseout="this.style.background='rgba(11,191,83,.06)'"><i class="fa-solid fa-plus" style="font-size:10px;"></i> ثبت محصول</a>
        <a href="/admin/orders" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;font-size:11.5px;font-weight:600;background:rgba(59,130,246,.06);color:#3b82f6;border:1px solid rgba(59,130,246,.15);text-decoration:none;" onmouseover="this.style.background='rgba(59,130,246,.12)'" onmouseout="this.style.background='rgba(59,130,246,.06)'"><i class="fa-solid fa-cart-shopping" style="font-size:10px;"></i> سفارشات</a>
        <a href="/admin/analytics" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;font-size:11.5px;font-weight:600;background:rgba(245,146,58,.06);color:var(--orange,#f5923a);border:1px solid rgba(245,146,58,.15);text-decoration:none;" onmouseover="this.style.background='rgba(245,146,58,.12)'" onmouseout="this.style.background='rgba(245,146,58,.06)'"><i class="fa-solid fa-chart-line" style="font-size:10px;"></i> آنالیتیکس</a>
      </div>

      <!-- نمودار + آمار سریع -->
      <div class="grid grid-cols-[1fr_340px] gap-[14px] mb-5 max-[1100px]:grid-cols-1">
        <div class="bg-s1 border border-b1 rounded-[10px] p-5 max-[768px]:p-[18px] max-[480px]:p-[14px]">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="text-[13px] font-bold text-watan-text">درآمد ۷ روز اخیر</div>
              <div class="text-[11px] text-watan-text3 mt-[2px]">تومان — بر اساس تراکنش‌های تایید شده</div>
            </div>
            <div class="text-[11px] font-semibold text-accent cursor-pointer opacity-80 hover:opacity-100">مشاهده کامل</div>
          </div>
          <div class="relative h-[200px]">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>

        <div class="bg-s1 border border-b1 rounded-[10px] p-5 max-[768px]:p-[18px] max-[480px]:p-[14px]">
          <div class="flex items-center justify-between mb-4">
            <div class="text-[13px] font-bold text-watan-text">آمار سریع</div>
          </div>
          <div class="flex flex-col gap-[10px]">
            <div class="flex items-center gap-3 py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-green/[0.08]"><i class="fa-solid fa-chart-pie text-[14px] text-green"></i></div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] text-watan-text3">نرخ تبدیل</div>
                <div class="text-sm font-bold text-watan-text">۷٫۴٪</div>
              </div>
              <span class="text-[10px] font-bold py-[2px] px-[7px] rounded-md flex-shrink-0 bg-green/[0.08] text-green border border-green/[0.2]">↑ خوب</span>
            </div>
            <div class="flex items-center gap-3 py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-accent/[0.1]"><i class="fa-solid fa-clock text-[14px] text-accent"></i></div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] text-watan-text3">میانگین پردازش</div>
                <div class="text-sm font-bold text-watan-text">۱۸ ثانیه</div>
              </div>
              <span class="text-[10px] font-bold py-[2px] px-[7px] rounded-md flex-shrink-0 bg-accent/[0.1] text-accent border border-accent/[0.25]">نرمال</span>
            </div>
            <div class="flex items-center gap-3 py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-orange/[0.1]"><i class="fa-solid fa-star text-[14px] text-orange"></i></div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] text-watan-text3">رضایت کاربران</div>
                <div class="text-sm font-bold text-watan-text">۴٫۸ / ۵</div>
              </div>
              <span class="text-[10px] font-bold py-[2px] px-[7px] rounded-md flex-shrink-0 bg-orange/[0.1] text-orange border border-orange/[0.25]">عالی</span>
            </div>
            <div class="flex items-center gap-3 py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-cyan/[0.1]"><i class="fa-solid fa-percent text-[14px] text-cyan"></i></div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] text-watan-text3">حاشیه سود</div>
                <div class="text-sm font-bold text-watan-text">۶۸٪</div>
              </div>
              <span class="text-[10px] font-bold py-[2px] px-[7px] rounded-md flex-shrink-0 bg-cyan/[0.1] text-cyan border border-cyan/[0.2]">↑ ۳٪</span>
            </div>
            <div class="flex items-center gap-3 py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-green/[0.08]"><i class="fa-solid fa-globe text-[14px] text-green"></i></div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] text-watan-text3">کل کاربران</div>
                <div class="text-sm font-bold text-watan-text">۱۲٬۴۸۳</div>
              </div>
              <span class="text-[10px] font-bold py-[2px] px-[7px] rounded-md flex-shrink-0 bg-green/[0.08] text-green border border-green/[0.2]">↑ ۶۳ امروز</span>
            </div>
          </div>
        </div>
      </div>

      <!-- جدول + سرور -->
      <div class="grid grid-cols-2 gap-[14px] mb-5 max-[1100px]:grid-cols-1">
        <div class="bg-s1 border border-b1 rounded-[10px] p-5 max-[768px]:p-[18px] max-[480px]:p-[14px]">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="text-[13px] font-bold text-watan-text">آخرین تراکنش‌ها</div>
              <div class="text-[11px] text-watan-text3 mt-[2px]">۵ تراکنش اخیر</div>
            </div>
            <div class="text-[11px] font-semibold text-accent cursor-pointer opacity-80 hover:opacity-100">همه تراکنش‌ها</div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead>
                <tr>
                  <th class="text-[10px] font-bold text-watan-text3 text-right pb-[10px] px-3 border-b border-b1 whitespace-nowrap">کاربر</th>
                  <th class="text-[10px] font-bold text-watan-text3 text-right pb-[10px] px-3 border-b border-b1 whitespace-nowrap">سبک</th>
                  <th class="text-[10px] font-bold text-watan-text3 text-right pb-[10px] px-3 border-b border-b1 whitespace-nowrap">مبلغ</th>
                  <th class="text-[10px] font-bold text-watan-text3 text-right pb-[10px] px-3 border-b border-b1 whitespace-nowrap">وضعیت</th>
                  <th class="text-[10px] font-bold text-watan-text3 text-right pb-[10px] px-3 border-b border-b1 whitespace-nowrap">زمان</th>
                </tr>
              </thead>
              <tbody>
                <tr class="group"><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">سارا محمدی</td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">ونگوگ</td><td class="group-hover:bg-s2 text-watan-text font-bold py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۴۹٬۰۰۰ ت</td><td class="group-hover:bg-s2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle"><span class="text-[10px] font-bold py-[2px] px-2 rounded-md inline-block bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۲ دقیقه پیش</td></tr>
                <tr class="group"><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">رضا کریمی</td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">آبرنگ</td><td class="group-hover:bg-s2 text-watan-text font-bold py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۴۹٬۰۰۰ ت</td><td class="group-hover:bg-s2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle"><span class="text-[10px] font-bold py-[2px] px-2 rounded-md inline-block bg-orange/[0.1] text-orange border border-orange/[0.25]">در انتظار</span></td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۸ دقیقه پیش</td></tr>
                <tr class="group"><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">نیلوفر احمدی</td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">انیمه</td><td class="group-hover:bg-s2 text-watan-text font-bold py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۷۹٬۰۰۰ ت</td><td class="group-hover:bg-s2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle"><span class="text-[10px] font-bold py-[2px] px-2 rounded-md inline-block bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۱۵ دقیقه پیش</td></tr>
                <tr class="group"><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">امیر حسین</td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">رئالیسم</td><td class="group-hover:bg-s2 text-watan-text font-bold py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۴۹٬۰۰۰ ت</td><td class="group-hover:bg-s2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle"><span class="text-[10px] font-bold py-[2px] px-2 rounded-md inline-block bg-red/[0.1] text-red border border-red/[0.2]">ناموفق</span></td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b border-b1 whitespace-nowrap align-middle">۲۳ دقیقه پیش</td></tr>
                <tr class="group"><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b-0 whitespace-nowrap align-middle">فاطمه زارع</td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b-0 whitespace-nowrap align-middle">مینیمال</td><td class="group-hover:bg-s2 text-watan-text font-bold py-[10px] px-3 border-b-0 whitespace-nowrap align-middle">۴۹٬۰۰۰ ت</td><td class="group-hover:bg-s2 py-[10px] px-3 border-b-0 whitespace-nowrap align-middle"><span class="text-[10px] font-bold py-[2px] px-2 rounded-md inline-block bg-green/[0.08] text-green border border-green/[0.2]">موفق</span></td><td class="group-hover:bg-s2 text-xs text-watan-text2 py-[10px] px-3 border-b-0 whitespace-nowrap align-middle">۳۱ دقیقه پیش</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-s1 border border-b1 rounded-[10px] p-5 max-[768px]:p-[18px] max-[480px]:p-[14px]">
          <div class="flex items-center justify-between mb-4">
            <div class="text-[13px] font-bold text-watan-text">وضعیت زیرساخت</div>
          </div>
          <div class="flex flex-col gap-2">
            <div class="flex items-center gap-[10px] py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-2 h-2 rounded-full flex-shrink-0 bg-green shadow-[0_0_6px_rgb(var(--green))]"></div>
              <div class="flex-1 text-xs font-semibold text-watan-text2">fal.ai API</div>
              <div class="w-[70px] h-1 bg-b1 rounded overflow-hidden"><div class="h-full rounded transition-[width] duration-[400ms] w-[42%] bg-green"></div></div>
              <div class="text-[11px] font-bold min-w-[36px] text-left text-green">۴۲٪</div>
            </div>
            <div class="flex items-center gap-[10px] py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-2 h-2 rounded-full flex-shrink-0 bg-green shadow-[0_0_6px_rgb(var(--green))]"></div>
              <div class="flex-1 text-xs font-semibold text-watan-text2">Supabase DB</div>
              <div class="w-[70px] h-1 bg-b1 rounded overflow-hidden"><div class="h-full rounded transition-[width] duration-[400ms] w-[28%] bg-green"></div></div>
              <div class="text-[11px] font-bold min-w-[36px] text-left text-green">۲۸٪</div>
            </div>
            <div class="flex items-center gap-[10px] py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-2 h-2 rounded-full flex-shrink-0 bg-green shadow-[0_0_6px_rgb(var(--green))]"></div>
              <div class="flex-1 text-xs font-semibold text-watan-text2">Laravel API</div>
              <div class="w-[70px] h-1 bg-b1 rounded overflow-hidden"><div class="h-full rounded transition-[width] duration-[400ms] w-[19%] bg-green"></div></div>
              <div class="text-[11px] font-bold min-w-[36px] text-left text-green">۱۹٪</div>
            </div>
            <div class="flex items-center gap-[10px] py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-2 h-2 rounded-full flex-shrink-0 bg-orange shadow-[0_0_6px_rgb(var(--orange))]"></div>
              <div class="flex-1 text-xs font-semibold text-watan-text2">Storage CDN</div>
              <div class="w-[70px] h-1 bg-b1 rounded overflow-hidden"><div class="h-full rounded transition-[width] duration-[400ms] w-[71%] bg-orange"></div></div>
              <div class="text-[11px] font-bold min-w-[36px] text-left text-orange">۷۱٪</div>
            </div>
            <div class="flex items-center gap-[10px] py-[10px] px-3 bg-s2 border border-b1 rounded-lg">
              <div class="w-2 h-2 rounded-full flex-shrink-0 bg-green shadow-[0_0_6px_rgb(var(--green))]"></div>
              <div class="flex-1 text-xs font-semibold text-watan-text2">Queue Worker</div>
              <div class="w-[70px] h-1 bg-b1 rounded overflow-hidden"><div class="h-full rounded transition-[width] duration-[400ms] w-[12%] bg-green"></div></div>
              <div class="text-[11px] font-bold min-w-[36px] text-left text-green">۱۲٪</div>
            </div>
          </div>

          <div class="mt-4">
            <div class="flex items-center justify-between mb-[10px]">
              <div class="text-[13px] font-bold text-watan-text">هشدارهای فعال</div>
              <span class="text-[10px] font-bold py-[3px] px-2 rounded-[10px] flex-shrink-0 bg-red/[0.1] text-red border border-red/[0.25]">۳</span>
            </div>
            <div class="flex flex-col gap-2">
              <div class="flex items-start gap-[10px] py-[10px] px-3 rounded-lg border-r-[3px] bg-red/[0.1] border-red">
                <div class="text-[13px] mt-px flex-shrink-0 text-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                  <div class="text-xs font-bold text-watan-text">Storage در ۷۱٪</div>
                  <div class="text-[11px] text-watan-text3 mt-px">ظرفیت CDN به زودی پر می‌شه</div>
                  <div class="text-[10px] text-watan-text3 mt-[3px]">۵ دقیقه پیش</div>
                </div>
              </div>
              <div class="flex items-start gap-[10px] py-[10px] px-3 rounded-lg border-r-[3px] bg-orange/[0.1] border-orange">
                <div class="text-[13px] mt-px flex-shrink-0 text-orange"><i class="fa-solid fa-circle-exclamation"></i></div>
                <div>
                  <div class="text-xs font-bold text-watan-text">۲ پرداخت در انتظار تایید</div>
                  <div class="text-[11px] text-watan-text3 mt-px">پرداخت دستی نیاز به بررسی دارد</div>
                  <div class="text-[10px] text-watan-text3 mt-[3px]">۲۰ دقیقه پیش</div>
                </div>
              </div>
              <div class="flex items-start gap-[10px] py-[10px] px-3 rounded-lg border-r-[3px] bg-accent/[0.1] border-accent">
                <div class="text-[13px] mt-px flex-shrink-0 text-accent"><i class="fa-solid fa-circle-info"></i></div>
                <div>
                  <div class="text-xs font-bold text-watan-text">نسخه جدید fal.ai</div>
                  <div class="text-[11px] text-watan-text3 mt-px">آپدیت مدل v3.1 در دسترس است</div>
                  <div class="text-[10px] text-watan-text3 mt-[3px]">۱ ساعت پیش</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- عملیات سریع -->
      <div class="bg-s1 border border-b1 rounded-[10px] p-5 max-[768px]:p-[18px] max-[480px]:p-[14px] mb-5">
        <div class="flex items-center justify-between mb-3">
          <div class="text-[13px] font-bold text-watan-text">عملیات سریع</div>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-5 max-[600px]:grid-cols-1">
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-green/[0.08]"><i class="fa-solid fa-user-plus text-[13px] text-green"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">افزودن کاربر</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-accent/[0.1]"><i class="fa-solid fa-palette text-[13px] text-accent"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">سبک جدید</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-orange/[0.1]"><i class="fa-solid fa-percent text-[13px] text-orange"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">کد تخفیف</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-cyan/[0.1]"><i class="fa-solid fa-comment-dots text-[13px] text-cyan"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">ارسال پیام گروهی</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-red/[0.1]"><i class="fa-solid fa-ticket text-[13px] text-red"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">بررسی تیکت‌ها</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-green/[0.08]"><i class="fa-solid fa-file-lines text-[13px] text-green"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">گزارش روزانه</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-accent/[0.1]"><i class="fa-solid fa-bullhorn text-[13px] text-accent"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">افزودن بلاگر</div>
          </div>
          <div class="group flex items-center gap-[10px] py-3 px-[14px] bg-s1 border border-b1 rounded-lg cursor-pointer transition-colors duration-150 hover:bg-s2 hover:border-b2">
            <div class="w-8 h-8 rounded-[7px] flex items-center justify-center text-[13px] flex-shrink-0 bg-orange/[0.1]"><i class="fa-solid fa-database text-[13px] text-orange"></i></div>
            <div class="text-xs font-semibold text-watan-text2 group-hover:text-watan-text">پشتیبان‌گیری</div>
          </div>
        </div>
      </div>

    </div>
    <!-- /dashboard-page -->

