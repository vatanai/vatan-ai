@php
  $channelLabels = ['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'both' => 'چندکاناله', 'other' => 'ارتباط دستی'];
  $channelIcons = ['instagram' => 'fa-brands fa-instagram', 'telegram' => 'fa-brands fa-telegram', 'both' => 'fa-solid fa-layer-group', 'other' => 'fa-solid fa-hand'];
  $priorityLabels = ['high' => 'مهم', 'low' => 'کم', 'normal' => 'عادی'];
  $contactType = $lead->stage <= 2 ? 'voice' : 'message';
@endphp
<article class="sp-lead-card" draggable="true" data-lead-id="{{ $lead->id }}" data-stage="{{ $lead->stage }}">
  <div class="sp-lead-top">
    <span class="sp-channel sp-channel-{{ $lead->channel }}"><i class="{{ $channelIcons[$lead->channel] ?? $channelIcons['other'] }}"></i> {{ $channelLabels[$lead->channel] ?? $channelLabels['other'] }}</span>
    <span class="sp-lead-top-tools">
      <span class="sp-priority-wrap"><span class="sp-priority sp-priority-{{ $lead->priority }}">{{ $priorityLabels[$lead->priority] ?? $priorityLabels['normal'] }}</span><span class="sp-priority-check" title="وضعیت کارت"><i class="fa-solid fa-check"></i></span></span>
      <span class="sp-drag-handle" title="کشیدن کارت" aria-label="کشیدن کارت"><i class="fa-solid fa-grip-vertical"></i></span>
    </span>
  </div>
  <div class="sp-lead-identity">
    <div><strong>{{ $lead->name ?: 'بدون نام' }}</strong>@if($lead->contact_url)<a href="{{ $lead->contact_url }}" target="_blank" rel="noopener">{{ $lead->handle }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@else<span class="sp-lead-no-link">{{ $lead->handle }} · لینک ندارد</span>@endif</div>
    <span class="sp-lead-follow-up-wrap"><span class="sp-lead-follow-up" title="زمان پیگیری بعدی" data-follow-up-at="{{ $lead->next_follow_up_at?->toIso8601String() }}"><i class="fa-regular fa-clock"></i><strong>{{ $lead->next_follow_up_at ? 'در حال نمایش زمان' : 'بدون پیگیری' }}</strong></span><button class="sp-card-action sp-open-contact" type="button" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name ?: $lead->handle }}" data-contact-type="{{ $contactType }}"><span>ثبت نتیجه</span></button></span>
  </div>
  <div class="sp-lead-meta">
    <span title="منبع جذب و کانال پیدا کردن همکار"><i class="fa-solid fa-compass"></i> {{ $acquisitionSourceLabels[$lead->acquisition_source] ?? 'سایر' }}</span>
    <span title="مسئول پیگیری کارت"><i class="fa-solid fa-user"></i> {{ $lead->assignee?->name ?: 'بدون مسئول' }}</span>
    <span title="تعداد ارتباط‌های ثبت‌شده"><i class="fa-solid fa-paper-plane"></i> <b data-contact-count>{{ number_format($lead->contact_count) }}</b></span>
  </div>
</article>
