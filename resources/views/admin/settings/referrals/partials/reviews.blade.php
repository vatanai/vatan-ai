<section class="content-card referral-reports">
  <header class="referral-reports-head">
    <div><h2>دعوت‌های نیازمند تصمیم</h2><p>این دعوت‌ها به‌دلیل الگوی تکراری یا رفتار مشکوک متوقف شده‌اند.</p></div>
    <span class="badge-pro badge-warning">{{ number_format($reviewConversions->total()) }} مورد</span>
  </header>
  <div class="referral-table-wrap">
    <table class="table-pro referral-report-table">
      <thead><tr><th>دعوت‌کننده</th><th>دعوت‌شده</th><th>دلیل بررسی</th><th>خرید</th><th>زمان</th><th>تصمیم</th></tr></thead>
      <tbody>
      @forelse($reviewConversions as $item)
        <tr>
          <td data-label="دعوت‌کننده"><strong>{{ trim(($item->inviter?->name ?? '').' '.($item->inviter?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->inviter?->phone }} · {{ $item->inviter?->referral_code }}</small></td>
          <td data-label="دعوت‌شده"><strong>{{ trim(($item->invitee?->name ?? '').' '.($item->invitee?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->invitee?->phone }}</small></td>
          <td data-label="دلیل بررسی"><span class="referral-risk-reason">{{ $item->risk_reason ?: 'نیازمند بررسی مدیر' }}</span></td>
          <td data-label="خرید"><span class="badge-pro {{ $item->purchase_completed ? 'badge-success' : 'badge-neutral' }}">{{ $item->purchase_completed ? 'خرید موفق' : 'بدون خرید' }}</span></td>
          <td data-label="زمان">{{ $item->created_at?->format('Y/m/d H:i') }}</td>
          <td data-label="تصمیم">
            @if(auth('admin')->user()?->isLeader())
              <form class="referral-review-form" method="POST" action="{{ route('admin.referrals.conversions.review', $item) }}">
                @csrf @method('PATCH')
                <input class="input-pro" name="note" maxlength="255" placeholder="یادداشت اختیاری">
                <div><button class="referral-action is-approve" name="action" value="approve" type="submit"><i class="fa-solid fa-check"></i> تأیید</button><button class="referral-action is-reject" name="action" value="reject" type="submit"><i class="fa-solid fa-xmark"></i> رد</button></div>
              </form>
            @else<span class="referral-no-action">فقط رهبر</span>@endif
          </td>
        </tr>
      @empty
        <tr><td class="td-empty" colspan="6">دعوتی در صف بررسی نیست.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($reviewConversions->hasPages())<div class="referral-simple-pages">{{ $reviewConversions->links() }}</div>@endif
</section>

<section class="content-card referral-reports">
  <header class="referral-reports-head">
    <div><h2>پاداش‌های معلق</h2><p>پاداش‌هایی که تا تصمیم مدیر به موجودی کاربران اضافه نشده‌اند.</p></div>
    <span class="badge-pro badge-warning">{{ number_format($reviewRewards->total()) }} مورد</span>
  </header>
  <div class="referral-table-wrap">
    <table class="table-pro referral-report-table">
      <thead><tr><th>کاربر</th><th>نوع پاداش</th><th>مقدار</th><th>دلیل</th><th>زمان</th><th>تصمیم</th></tr></thead>
      <tbody>
      @forelse($reviewRewards as $item)
        @php($rewardLabel = ['registration_gift' => 'هدیه ثبت‌نام', 'invitee_reward' => 'هدیه دعوت‌شده', 'inviter_reward' => 'پاداش دعوت‌کننده'][$item->reward_type] ?? $item->reward_type)
        <tr>
          <td data-label="کاربر"><strong>{{ trim(($item->user?->name ?? '').' '.($item->user?->last_name ?? '')) ?: 'بدون نام' }}</strong><small>{{ $item->user?->phone }}</small></td>
          <td data-label="نوع پاداش">{{ $rewardLabel }}</td>
          <td data-label="مقدار"><strong>{{ number_format($item->amount) }}</strong> توکن</td>
          <td data-label="دلیل"><span class="referral-cell-note">{{ $item->reason ?: 'نیازمند بررسی مدیر' }}</span></td>
          <td data-label="زمان">{{ $item->created_at?->format('Y/m/d H:i') }}</td>
          <td data-label="تصمیم">
            @if(auth('admin')->user()?->isLeader())
              <form class="referral-review-form" method="POST" action="{{ route('admin.referrals.rewards.review', $item) }}">
                @csrf @method('PATCH')
                <input class="input-pro" name="note" maxlength="255" placeholder="یادداشت اختیاری">
                <div><button class="referral-action is-approve" name="action" value="approve" type="submit"><i class="fa-solid fa-check"></i> تأیید</button><button class="referral-action is-reject" name="action" value="reject" type="submit"><i class="fa-solid fa-xmark"></i> رد</button></div>
              </form>
            @else<span class="referral-no-action">فقط رهبر</span>@endif
          </td>
        </tr>
      @empty
        <tr><td class="td-empty" colspan="6">پاداشی در صف بررسی نیست.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($reviewRewards->hasPages())<div class="referral-simple-pages">{{ $reviewRewards->links() }}</div>@endif
</section>
