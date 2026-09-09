<section class="referral-stat-grid" aria-label="خلاصه عملکرد همکاری در فروش">
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-primary"><i class="fa-solid fa-arrow-pointer"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['visits']) }}</div><div class="stat-card-label">ورود از لینک دعوت</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-success"><i class="fa-solid fa-user-check"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['conversions']) }}</div><div class="stat-card-label">ثبت‌نام رفرالی</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-info"><i class="fa-solid fa-coins"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['paid_tokens']) }}</div><div class="stat-card-label">توکن پرداخت‌شده</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-warning"><i class="fa-solid fa-shield-halved"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['pending']) }}</div><div class="stat-card-label">در انتظار بررسی</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-info"><i class="fa-solid fa-image"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['first_images']) }}</div><div class="stat-card-label">اولین تصویر دعوت‌شده</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-warning"><i class="fa-solid fa-wallet"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['pending_commission']) }}</div><div class="stat-card-label">کمیسیون در انتظار تسویه (تومان)</div></div>
  </article>
</section>

<section class="content-card referral-inviter-card">
  <div class="referral-card-head">
    <span class="referral-card-icon is-info"><i class="fa-solid fa-users-viewfinder"></i></span>
    <div>
      <h2>عملکرد کاربران رفرال</h2>
      <p>تعداد لینک‌های محصول، کلیک، ثبت‌نام و خرید هر دعوت‌کننده را یکجا ببینید.</p>
    </div>
  </div>

  <div class="referral-inviter-grid">
    @forelse($inviterCards as $inviter)
      @php
        $inviterName = trim($inviter->name.' '.$inviter->last_name) ?: 'کاربر بدون نام';
        $inviterUrl = $inviter->referral_code ? route('referral.visit', ['code' => $inviter->referral_code]) : null;
      @endphp
      <article class="referral-inviter-item">
        <div class="referral-inviter-head">
          @if($inviter->avatar)
            <img class="referral-inviter-avatar is-image" src="{{ asset('storage/'.$inviter->avatar) }}" alt="">
          @else
            <span class="referral-inviter-avatar">{{ mb_substr($inviterName, 0, 1) }}</span>
          @endif
          <div class="referral-inviter-identity">
            <strong>{{ $inviterName }}</strong>
            <small>{{ $inviter->phone ?: 'بدون شماره موبایل' }}</small>
          </div>
          <span class="referral-inviter-status">فعال</span>
        </div>

        @if($inviterUrl)
          <div class="referral-inviter-link" title="{{ $inviterUrl }}">
            <i class="fa-solid fa-link"></i>
            <code>{{ $inviterUrl }}</code>
            <span>لینک عمومی</span>
            <button type="button" class="referral-icon-button" onclick="window.copyReferralUrl(@js($inviterUrl))" title="کپی لینک کامل" aria-label="کپی لینک کامل"><i class="fa-regular fa-copy"></i></button>
          </div>
        @endif

        <div class="referral-inviter-metrics">
          <div><strong>{{ number_format((int) $inviter->referral_links_count) }}</strong><span>لینک محصول</span></div>
          <div><strong>{{ number_format((int) $inviter->referral_clicks_count) }}</strong><span>کلیک</span></div>
          <div><strong>{{ number_format((int) $inviter->referral_registrations_count) }}</strong><span>ثبت‌نام</span></div>
          <div><strong>{{ number_format((int) $inviter->referral_purchases_count) }}</strong><span>خرید موفق</span></div>
          <div><strong>{{ number_format((int) $inviter->referral_paid_tokens) }}</strong><span>توکن پرداختی</span></div>
          <div><strong>{{ number_format((int) $inviter->referral_generated_images_count) }}</strong><span>خروجی دعوت‌شده‌ها</span></div>
        </div>

        <div class="referral-card-section">
          <div class="referral-card-section-head">
            <strong><i class="fa-solid fa-link"></i> لینک‌های محصول</strong>
            <button type="button" class="referral-action is-approve" onclick="window.openReferralLinkDialog({{ $inviter->id }}, @js($inviterName))"><i class="fa-solid fa-plus"></i> ساخت لینک</button>
          </div>
          @forelse($inviter->referralLinks as $productLink)
            @php
              $productLinkUrl = route('referral.link', ['referralLink' => $productLink->slug]);
              $productName = $productLink->product?->name_fa ?: ($productLink->product?->name_en ?: 'محصول حذف‌شده');
              $productLinkActive = $productLink->status === 'active' && $productLink->deactivated_at === null;
            @endphp
            <div class="referral-product-link-row">
              <div class="referral-product-link-copy">
                <strong>{{ $productName }}</strong>
                <code title="{{ $productLinkUrl }}">{{ $productLinkUrl }}</code>
                <small>{{ number_format((int) $productLink->visits_count) }} کلیک <span>•</span> {{ number_format((int) $productLink->conversions_count) }} ثبت‌نام <span>•</span> {{ number_format((int) $productLink->purchases_count) }} خرید <span>•</span> {{ number_format((int) $productLink->first_images_count) }} خروجی</small>
              </div>
              <div class="referral-product-link-actions">
                <button type="button" class="referral-icon-button" onclick="window.copyReferralUrl(@js($productLinkUrl))" title="کپی لینک کامل" aria-label="کپی لینک کامل"><i class="fa-regular fa-copy"></i></button>
                <form method="POST" action="{{ route('admin.referrals.links.toggle', ['referralLink' => $productLink->id]) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="referral-link-status {{ $productLinkActive ? 'is-active' : 'is-inactive' }}" title="{{ $productLinkActive ? 'غیرفعال کردن لینک' : 'فعال کردن لینک' }}">{{ $productLinkActive ? 'فعال' : 'غیرفعال' }}</button>
                </form>
              </div>
            </div>
          @empty
            <div class="referral-subtree-empty">برای این کاربر هنوز لینک محصولی ساخته نشده است.</div>
          @endforelse
        </div>

        <div class="referral-card-section">
          <div class="referral-card-section-head"><strong><i class="fa-solid fa-sitemap"></i> زیرشاخه و نتیجه دعوت</strong><span>{{ number_format($inviter->referralConversions->count()) }} ثبت‌نام</span></div>
          @forelse($inviter->referralConversions->take(8) as $conversion)
            @php $invitee = $conversion->invitee; @endphp
            <div class="referral-subtree-row">
              <div>
                <strong>{{ trim(($invitee?->name ?? '').' '.($invitee?->last_name ?? '')) ?: 'کاربر بدون نام' }}</strong>
                <small>{{ $invitee?->phone ?: 'شماره ثبت نشده' }} @if($conversion->link?->product) <span>•</span> {{ $conversion->link->product->name_fa ?: $conversion->link->product->name_en }} @endif</small>
              </div>
              <div class="referral-subtree-metrics">
                <span><i class="fa-solid fa-images"></i> {{ number_format((int) ($invitee?->generated_images_count ?? 0)) }}</span>
                <span class="{{ ($invitee?->completed_purchase_exists ?? false) ? 'is-success' : '' }}"><i class="fa-solid fa-cart-shopping"></i> {{ ($invitee?->completed_purchase_exists ?? false) ? 'خرید' : 'بدون خرید' }}</span>
              </div>
            </div>
          @empty
            <div class="referral-subtree-empty">هنوز کاربری با این لینک ثبت‌نام نکرده است.</div>
          @endforelse
        </div>

        <div class="referral-inviter-actions">
          <a class="referral-action is-approve" href="{{ route('admin.referrals.visits', ['search' => $inviter->phone ?: $inviter->referral_code]) }}">
            <i class="fa-solid fa-arrow-pointer"></i> بازدیدها
          </a>
          <a class="referral-action is-neutral" href="{{ route('admin.referrals.conversions', ['search' => $inviter->phone ?: $inviter->referral_code]) }}">
            <i class="fa-solid fa-user-check"></i> ثبت‌نام‌ها
          </a>
        </div>
      </article>
    @empty
      <div class="referral-inviter-empty">
        <i class="fa-solid fa-chart-line"></i>
        <strong>هنوز عملکردی برای نمایش ثبت نشده است.</strong>
        <span>با اولین کلیک یا ثبت‌نام از لینک دعوت، کارت کاربر اینجا نمایش داده می‌شود.</span>
      </div>
    @endforelse
  </div>
</section>

<dialog id="admin-referral-link-dialog" class="referral-link-dialog">
  <form method="POST" id="admin-referral-link-form" action="">
    @csrf
    <button type="button" class="referral-dialog-close" onclick="window.closeReferralLinkDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    <span class="referral-card-icon is-primary"><i class="fa-solid fa-link"></i></span>
    <h2>ساخت لینک دعوت محصولی</h2>
    <p>برای <strong id="admin-referral-link-user"></strong> یک لینک فعال بسازید.</p>
    <label class="referral-field"><span>محصول مقصد</span><select name="product_id" class="input-pro" required><option value="">انتخاب محصول</option>@foreach($referralProducts as $referralProduct)<option value="{{ $referralProduct->id }}">{{ $referralProduct->name_fa ?: $referralProduct->name_en }}</option>@endforeach</select></label>
    <div class="referral-dialog-actions"><button type="button" class="referral-action is-neutral" onclick="window.closeReferralLinkDialog()">انصراف</button><button type="submit" class="referral-action is-approve"><i class="fa-solid fa-plus"></i> ساخت لینک</button></div>
  </form>
</dialog>

<script>
  window.copyReferralUrl = async function (url) {
    try {
      if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(url);
      else {
        const input = document.createElement('textarea'); input.value = url; input.setAttribute('readonly', ''); input.style.position = 'fixed'; input.style.opacity = '0'; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove();
      }
      if (typeof window.showAdminToast === 'function') window.showAdminToast('لینک کامل کپی شد.', 'success');
    } catch (error) { if (typeof window.showAdminToast === 'function') window.showAdminToast('کپی لینک انجام نشد.', 'error'); }
  };
  window.openReferralLinkDialog = function (userId, userName) {
    const dialog = document.getElementById('admin-referral-link-dialog');
    const form = document.getElementById('admin-referral-link-form');
    const name = document.getElementById('admin-referral-link-user');
    if (!dialog || !form || !name) return;
    form.action = '{{ url('/admin/referrals/users') }}/' + encodeURIComponent(userId) + '/links';
    name.textContent = userName;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', 'open');
  };
  window.closeReferralLinkDialog = function () { const dialog = document.getElementById('admin-referral-link-dialog'); if (dialog?.open) dialog.close(); else dialog?.removeAttribute('open'); };
</script>

<section class="content-card referral-hub-card">
  <div class="referral-card-head">
    <span class="referral-card-icon is-primary"><i class="fa-solid fa-table-cells-large"></i></span>
    <div><h2>مدیریت برنامه</h2><p>هر بخش صفحه و ابزارهای مخصوص خودش را دارد.</p></div>
  </div>
  <div class="referral-hub-grid">
    @foreach([
      ['route' => 'admin.referrals.settings', 'icon' => 'fa-sliders', 'title' => 'تنظیمات برنامه', 'text' => 'مقدار هدیه، تخفیف خرید، کمیسیون و محتوای پروفایل'],
      ['route' => 'admin.referrals.conversions', 'icon' => 'fa-user-group', 'title' => 'فهرست دعوت‌ها', 'text' => 'وضعیت ثبت‌نام، خرید و دعوت‌کننده'],
      ['route' => 'admin.referrals.rewards', 'icon' => 'fa-coins', 'title' => 'گزارش پاداش‌ها', 'text' => 'ریز توکن‌های پرداخت‌شده و معلق'],
      ['route' => 'admin.referrals.visits', 'icon' => 'fa-arrow-pointer', 'title' => 'بازدید لینک‌ها', 'text' => 'ورودی لینک‌ها و نتیجه تبدیل'],
      ['route' => 'admin.referrals.reviews', 'icon' => 'fa-shield-halved', 'title' => 'صف بررسی', 'text' => 'تصمیم‌گیری روی موارد مشکوک و تکراری'],
    ] as $item)
      <a href="{{ route($item['route']) }}" class="referral-hub-link">
        <span><i class="fa-solid {{ $item['icon'] }}"></i></span>
        <div><strong>{{ $item['title'] }}</strong><small>{{ $item['text'] }}</small></div>
        <i class="fa-solid fa-chevron-left"></i>
      </a>
    @endforeach
  </div>
</section>
