{{-- ══════════════════════════════════════════
     مینی‌سایدبار (mini-rail) — کپی دقیق از یوآی داشبورد محسن
     تنها تفاوت: بدون آیکون سبز لوگو در بالای آن
══════════════════════════════════════════ --}}
<div class="mini-rail" id="miniRail">

  <div class="mini-btn active" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-border-all"></i>
    <span class="mini-btn-tooltip">داشبورد</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-clock"></i>
    <span class="mini-btn-tooltip">تاریخچه</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-bell"></i>
    <span class="mini-btn-tooltip">اعلان‌ها</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-chart-line"></i>
    <span class="mini-btn-tooltip">گزارشات</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-users"></i>
    <span class="mini-btn-tooltip">کاربران</span>
  </div>

  <div class="mini-rail-divider"></div>

  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-file-arrow-down"></i>
    <span class="mini-btn-tooltip">دانلود</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-star"></i>
    <span class="mini-btn-tooltip">موارد ستاره‌دار</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-bookmark"></i>
    <span class="mini-btn-tooltip">ذخیره‌شده‌ها</span>
  </div>

  <div class="mini-rail-spacer"></div>

  <div class="mini-rail-divider"></div>

  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-circle-question"></i>
    <span class="mini-btn-tooltip">راهنما</span>
  </div>
  <div class="mini-btn" onclick="miniBtnGo(this)">
    <i class="fa-solid fa-trash-can"></i>
    <span class="mini-btn-tooltip">آرشیو</span>
  </div>

</div>

<script>
  function miniBtnGo(el) {
    document.querySelectorAll('.mini-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  }
</script>
