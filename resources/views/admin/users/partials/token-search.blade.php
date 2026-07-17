{{-- پارشیال مدیریت توکن: جستجوی کاربر (نام، نام‌خانوادگی، ایمیل، موبایل) + کارت کاربر انتخاب‌شده --}}

<div class="content-card" style="margin-bottom:16px;">
  <div class="tk-card-header"><i class="fa-solid fa-magnifying-glass" style="color:var(--primary);"></i> جستجوی کاربر</div>
  <div class="tk-card-body">
    <div class="tk-search-wrap">
      <input type="text" id="tkSearchInput" class="input-pro" placeholder="نام، نام‌خانوادگی، ایمیل یا موبایل..." oninput="tkSearch(this.value)" autocomplete="off">
      <i class="fa-solid fa-magnifying-glass tk-search-icon"></i>
    </div>
    <div id="tkSearchResults" style="display:none;margin-top:12px;"></div>
  </div>
</div>

<div class="content-card" id="tkSelectedCard" style="margin-bottom:16px;display:none;">
  <div class="tk-card-header"><i class="fa-solid fa-user-check" style="color:var(--success);"></i> کاربر انتخاب‌شده</div>
  <div class="tk-card-body">
    <div class="tk-selected">
      <div class="tk-avatar" id="tkSelAvatar">—</div>
      <div style="flex:1;min-width:0;">
        <div class="tk-user-name" id="tkSelName">—</div>
        <div class="tk-user-meta" id="tkSelMeta">—</div>
      </div>
      <div style="text-align:left;">
        <div class="tk-sel-token-label">موجودی توکن</div>
        <div class="tk-sel-token" id="tkSelToken">—</div>
      </div>
    </div>
    <button type="button" class="tk-clear-btn" onclick="tkClear()">
      <i class="fa-solid fa-xmark" style="margin-left:4px;"></i> تغییر کاربر
    </button>
  </div>
</div>
