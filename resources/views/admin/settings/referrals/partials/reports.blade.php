@php
  $queryWithoutPage = request()->except('page');
  $pageRoute = ['conversions' => 'admin.referrals.conversions', 'rewards' => 'admin.referrals.rewards', 'visits' => 'admin.referrals.visits'][$tab];
@endphp

<section class="content-card referral-reports" aria-labelledby="referral-reports-title">
  <header class="referral-reports-head">
    <div>
      <h2 id="referral-reports-title">{{ $pageMeta['title'] }}</h2>
      <p>{{ $pageMeta['description'] }}</p>
    </div>
    <a class="btn-pro btn-pro-secondary" href="{{ route('admin.referrals.export', array_merge($queryWithoutPage, ['tab' => $tab])) }}">
      <i class="fa-solid fa-file-arrow-down"></i> خروجی گزارش
    </a>
  </header>

  <form class="referral-filter-form" method="GET" action="{{ route($pageRoute) }}">
    <label class="referral-filter-search">
      <span>جست‌وجو</span>
      <div><i class="fa-solid fa-magnifying-glass"></i><input class="input-pro" name="search" value="{{ request('search') }}" placeholder="نام، موبایل یا کد دعوت"></div>
    </label>

    @if($tab === 'conversions')
      <label><span>وضعیت دعوت</span><select class="input-pro" name="status"><option value="">همه وضعیت‌ها</option><option value="qualified" @selected(request('status') === 'qualified')>معتبر</option><option value="under_review" @selected(request('status') === 'under_review')>نیازمند بررسی</option><option value="rejected" @selected(request('status') === 'rejected')>ردشده</option></select></label>
      <label><span>وضعیت خرید</span><select class="input-pro" name="purchase"><option value="">همه کاربران</option><option value="completed" @selected(request('purchase') === 'completed')>خرید موفق داشته</option><option value="waiting" @selected(request('purchase') === 'waiting')>هنوز خرید نکرده</option></select></label>
    @elseif($tab === 'rewards')
      <label><span>وضعیت پاداش</span><select class="input-pro" name="status"><option value="">همه وضعیت‌ها</option><option value="paid" @selected(request('status') === 'paid')>پرداخت‌شده</option><option value="pending" @selected(request('status') === 'pending')>در انتظار بررسی</option><option value="rejected" @selected(request('status') === 'rejected')>ردشده</option></select></label>
      <label><span>نوع پاداش</span><select class="input-pro" name="reward_type"><option value="">همه پاداش‌ها</option><option value="registration_gift" @selected(request('reward_type') === 'registration_gift')>هدیه ثبت‌نام</option><option value="invitee_reward" @selected(request('reward_type') === 'invitee_reward')>هدیه دعوت‌شده</option><option value="inviter_reward" @selected(request('reward_type') === 'inviter_reward')>پاداش دعوت‌کننده</option></select></label>
    @else
      <label><span>نتیجه بازدید</span><select class="input-pro" name="conversion"><option value="">همه بازدیدها</option><option value="converted" @selected(request('conversion') === 'converted')>منجر به ثبت‌نام</option><option value="not_converted" @selected(request('conversion') === 'not_converted')>بدون ثبت‌نام</option></select></label>
    @endif

    <label><span>از تاریخ</span><input class="input-pro" type="date" name="date_from" value="{{ request('date_from') }}"></label>
    <label><span>تا تاریخ</span><input class="input-pro" type="date" name="date_to" value="{{ request('date_to') }}"></label>
    <label><span>ترتیب</span><select class="input-pro" name="sort"><option value="newest">جدیدترین</option><option value="oldest" @selected(request('sort') === 'oldest')>قدیمی‌ترین</option></select></label>
    <div class="referral-filter-actions">
      <button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button>
      <a class="btn-pro btn-pro-secondary" href="{{ route($pageRoute) }}">پاک‌کردن</a>
    </div>
  </form>

  <div class="referral-table-wrap">
    @if($tab === 'conversions')
      <table class="table-pro referral-report-table">
        <thead><tr><th>دعوت‌کننده</th><th>کاربر دعوت‌شده</th><th>خرید</th><th>پاداش پرداختی</th><th>وضعیت</th><th>زمان</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($records as $item)
          <tr>
            <td data-label="دعوت‌کننده"><strong>{{ trim(($item->inviter?->name ?? '').' '.($item->inviter?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->inviter?->phone }} · {{ $item->inviter?->referral_code }}</small></td>
            <td data-label="دعوت‌شده"><strong>{{ trim(($item->invitee?->name ?? '').' '.($item->invitee?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->invitee?->phone }}</small></td>
            <td data-label="خرید"><span class="badge-pro {{ $item->purchase_completed ? 'badge-success' : 'badge-neutral' }}">{{ $item->purchase_completed ? 'خرید موفق' : 'در انتظار خرید' }}</span></td>
            <td data-label="پاداش پرداختی">{{ number_format((int) $item->paid_tokens) }} توکن</td>
            <td data-label="وضعیت">
              <span class="badge-pro {{ $item->status === 'qualified' ? 'badge-success' : ($item->status === 'under_review' ? 'badge-warning' : 'badge-danger') }}">{{ $item->status === 'qualified' ? 'معتبر' : ($item->status === 'under_review' ? 'نیازمند بررسی' : 'ردشده') }}</span>
              @if($item->risk_reason)<small class="referral-risk-reason">{{ $item->risk_reason }}</small>@endif
            </td>
            <td data-label="زمان">{{ $item->created_at?->format('Y/m/d H:i') }}</td>
            <td data-label="عملیات">
              @if($item->status === 'under_review' && auth('admin')->user()?->isLeader())
                <form class="referral-review-form" method="POST" action="{{ route('admin.referrals.conversions.review', $item) }}">
                  @csrf @method('PATCH')
                  <input class="input-pro" name="note" maxlength="255" placeholder="یادداشت اختیاری">
                  <div><button class="referral-action is-approve" name="action" value="approve" type="submit" onclick="return confirm('این دعوت و پاداش‌های معلق آن تأیید شود؟')"><i class="fa-solid fa-check"></i> تأیید</button><button class="referral-action is-reject" name="action" value="reject" type="submit" onclick="return confirm('این دعوت رد شود؟')"><i class="fa-solid fa-xmark"></i> رد</button></div>
                </form>
              @else
                <span class="referral-no-action">ثبت‌شده</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td class="td-empty" colspan="7">نتیجه‌ای مطابق فیلترهای انتخابی پیدا نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    @elseif($tab === 'rewards')
      <table class="table-pro referral-report-table">
        <thead><tr><th>کاربر</th><th>نوع پاداش</th><th>مقدار</th><th>وضعیت</th><th>دلیل / یادداشت</th><th>زمان</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($records as $item)
          @php
            $rewardLabel = ['registration_gift' => 'هدیه ثبت‌نام', 'invitee_reward' => 'هدیه دعوت‌شده', 'inviter_reward' => 'پاداش دعوت‌کننده'][$item->reward_type] ?? $item->reward_type;
            $rewardStatusLabel = ['paid' => 'پرداخت‌شده', 'pending' => 'در انتظار بررسی', 'processing' => 'در حال پردازش', 'rejected' => 'ردشده'][$item->status] ?? $item->status;
            $rewardStatusClass = ['paid' => 'badge-success', 'pending' => 'badge-warning', 'processing' => 'badge-info', 'rejected' => 'badge-danger'][$item->status] ?? 'badge-neutral';
          @endphp
          <tr>
            <td data-label="کاربر"><strong>{{ trim(($item->user?->name ?? '').' '.($item->user?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->user?->phone }}</small></td>
            <td data-label="نوع پاداش">{{ $rewardLabel }}</td>
            <td data-label="مقدار"><strong>{{ number_format($item->amount) }}</strong> توکن</td>
            <td data-label="وضعیت"><span class="badge-pro {{ $rewardStatusClass }}">{{ $rewardStatusLabel }}</span></td>
            <td data-label="دلیل / یادداشت"><span class="referral-cell-note">{{ $item->reason ?: '—' }}</span></td>
            <td data-label="زمان">{{ $item->created_at?->format('Y/m/d H:i') }}</td>
            <td data-label="عملیات">
              @if($item->status === 'pending' && auth('admin')->user()?->isLeader())
                <form class="referral-review-form" method="POST" action="{{ route('admin.referrals.rewards.review', $item) }}">
                  @csrf @method('PATCH')
                  <input class="input-pro" name="note" maxlength="255" placeholder="یادداشت اختیاری">
                  <div><button class="referral-action is-approve" name="action" value="approve" type="submit" onclick="return confirm('این پاداش به موجودی کاربر افزوده شود؟')"><i class="fa-solid fa-check"></i> تأیید</button><button class="referral-action is-reject" name="action" value="reject" type="submit" onclick="return confirm('این پاداش رد شود؟')"><i class="fa-solid fa-xmark"></i> رد</button></div>
                </form>
              @else
                <span class="referral-no-action">ثبت‌شده</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td class="td-empty" colspan="7">نتیجه‌ای مطابق فیلترهای انتخابی پیدا نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    @else
      <table class="table-pro referral-report-table">
        <thead><tr><th>دعوت‌کننده</th><th>کد دعوت</th><th>نتیجه</th><th>کاربر ثبت‌نام‌شده</th><th>صفحه ورود</th><th>زمان</th></tr></thead>
        <tbody>
        @forelse($records as $item)
          <tr>
            <td data-label="دعوت‌کننده"><strong>{{ trim(($item->inviter?->name ?? '').' '.($item->inviter?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->inviter?->phone }}</small></td>
            <td data-label="کد دعوت"><code>{{ $item->referral_code }}</code></td>
            <td data-label="نتیجه"><span class="badge-pro {{ $item->converted_user_id ? 'badge-success' : 'badge-neutral' }}">{{ $item->converted_user_id ? 'منجر به ثبت‌نام' : 'بدون ثبت‌نام' }}</span></td>
            <td data-label="کاربر ثبت‌نام‌شده">{{ $item->convertedUser ? (trim($item->convertedUser->name.' '.$item->convertedUser->last_name) ?: $item->convertedUser->phone) : '—' }}</td>
            <td data-label="صفحه ورود"><span class="referral-landing-url" title="{{ $item->landing_url }}">{{ $item->landing_url ?: '—' }}</span></td>
            <td data-label="زمان">{{ $item->visited_at?->format('Y/m/d H:i') }}</td>
          </tr>
        @empty
          <tr><td class="td-empty" colspan="6">نتیجه‌ای مطابق فیلترهای انتخابی پیدا نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    @endif
  </div>

  @if($records->hasPages())
    <footer class="referral-pagination">
      <span>نمایش {{ number_format($records->firstItem()) }} تا {{ number_format($records->lastItem()) }} از {{ number_format($records->total()) }} مورد</span>
      <div>
        @if($records->onFirstPage())<span class="is-disabled">قبلی</span>@else<a href="{{ $records->previousPageUrl() }}">قبلی</a>@endif
        <b>صفحه {{ number_format($records->currentPage()) }} از {{ number_format($records->lastPage()) }}</b>
        @if($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}">بعدی</a>@else<span class="is-disabled">بعدی</span>@endif
      </div>
    </footer>
  @endif
</section>
