@extends('layouts.admin')
@section('title', 'مدیریت کاربران — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/user-operational-snapshot.css') }}?v={{ filemtime(public_path('admin/css/user-operational-snapshot.css')) }}">
@endpush

@php
  $planOptionsForJs = $plans->map(fn ($plan) => [
    'id' => $plan->id,
    'name' => $plan->name,
    'tokens' => (int) $plan->tokens,
  ])->values();
@endphp

@section('content')
<div class="flex min-h-screen bg-[var(--page-bg)] text-[var(--text-h)]" dir="rtl">

  <main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')
    <div class="flex-1 p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]">

      <div class="grid grid-cols-4 gap-3 mb-5 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1">
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">کل کاربران</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--text-h)]">{{ $allUsersCount }}</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">در دیتابیس</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">کاربران فعال</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--success)]">{{ number_format($monthlyActiveUsersCount) }}</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">ماه جاری</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">تصاویر خلق شده</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--info)]">{{ number_format($totalGeneratedImagesCount) }}</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">کل</div>
        </div>
        <div class="p-4 rounded-xl border bg-[var(--card-bg)] border-[var(--border)]">
          <div class="text-[11px] text-[var(--text-soft)] mb-1.5">صفحه فعلی</div>
          <div class="text-[22px] font-extrabold leading-none text-[var(--text-h)]">1</div>
          <div class="text-[10px] text-[var(--text-soft)] mt-1">بدون صفحه‌بندی</div>
        </div>
      </div>

      <div class="flex gap-2.5 items-center flex-wrap p-3 rounded-xl mb-4 border bg-[var(--card-bg)] border-[var(--border)]">
        @if($errors->has('birth_month') || $errors->has('birth_day'))
          <div class="w-full px-3 py-2 rounded-lg border border-[var(--danger-m)] bg-[var(--danger-l)] text-[12px] text-[var(--danger)]">
            {{ $errors->first('birth_month') ?: $errors->first('birth_day') }}
          </div>
        @endif
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-1 min-w-[200px] items-center gap-1.5">
          @if($birthMonth)<input type="hidden" name="birth_month" value="{{ $birthMonth }}">@endif
          @if($birthDay)<input type="hidden" name="birth_day" value="{{ $birthDay }}">@endif
          <input type="search" name="q" value="{{ $search }}" class="min-w-0 flex-1 p-2 text-[13px] rounded-lg border outline-none transition bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-h)] focus:border-[var(--info)]" placeholder="جستجوی کاربر (نام، فامیلی، ایمیل، موبایل)..." id="searchInput">
          <button type="submit" class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-soft)] hover:border-[var(--info)] hover:text-[var(--info)]" title="جستجو" aria-label="جستجوی کاربر"><i class="fa-solid fa-magnifying-glass text-[11px]"></i></button>
        </form>
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2 flex-wrap">
          @if($search)<input type="hidden" name="q" value="{{ $search }}">@endif
          <span class="text-[11px] font-semibold text-[var(--text-soft)]">تولد شمسی:</span>
          <select name="birth_month" class="p-2 text-[12px] rounded-lg border outline-none bg-[var(--input-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
            <option value="">همه ماه‌ها</option>
            @foreach([1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'] as $monthNumber => $monthName)
              <option value="{{ $monthNumber }}" @selected($birthMonth === $monthNumber)>{{ $monthName }}</option>
            @endforeach
          </select>
          <select name="birth_day" class="p-2 text-[12px] rounded-lg border outline-none bg-[var(--input-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
            <option value="">همه روزها</option>
            @for($day = 1; $day <= 31; $day++)
              <option value="{{ $day }}" @selected($birthDay === $day)>روز {{ $day }}</option>
            @endfor
          </select>
          <div class="flex items-center gap-1 p-1 rounded-xl border bg-[var(--page-bg)] border-[var(--border)]">
            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-filter ml-1 text-[10px]"></i> اعمال</button>
            <a href="{{ route('admin.users.export', request()->except(['user_ids'])) }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-file-export ml-1 text-[10px]"></i> خروجی فیلتر</a>
            <a href="{{ route('admin.users.all_activities') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-timeline ml-1 text-[10px]"></i> فعالیت‌ها</a>
            <a href="{{ route('admin.users.all_logs') }}" class="px-2.5 py-1.5 rounded-lg text-[10.5px] font-semibold text-[var(--text-main)] hover:bg-[var(--primary-l)] hover:text-[var(--primary)]"><i class="fa-solid fa-images ml-1 text-[10px]"></i> لاگ‌ها</a>
            @if($birthMonth || $birthDay || $search)
              <a href="{{ route('admin.users.index') }}" class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-[var(--text-soft)] hover:bg-[var(--danger-l)] hover:text-[var(--danger)]" title="پاک کردن فیلترها" aria-label="پاک کردن فیلترها"><i class="fa-solid fa-xmark text-[10px]"></i></a>
            @endif
          </div>
        </form>
      </div>

      @if($canBulkManageUsers)
        <section id="bulk-user-actions-toolbar" class="hidden items-center gap-3 flex-wrap p-3.5 rounded-xl mb-4 border bg-[var(--primary-l)] border-[var(--primary-m)]">
          <div class="flex items-center gap-2 ml-auto">
            <span class="w-8 h-8 rounded-lg inline-flex items-center justify-center bg-[var(--card-bg)] text-[var(--primary)] border border-[var(--primary-m)]"><i class="fa-solid fa-users-gear"></i></span>
            <div><b class="block text-[12px] text-[var(--text-h)]"><span id="bulk-user-actions-count">۰</span> کاربر انتخاب شده</b><span class="block text-[10px] text-[var(--text-soft)]">تنظیمات انتخاب‌شده برای همه کاربران منتخب اعمال می‌شود.</span></div>
          </div>
          @if($canManageUserPlans)
            <div class="flex items-center gap-2 flex-wrap pl-3 border-l border-[var(--primary-m)]">
              <label class="flex items-center gap-2 text-[11px] font-bold text-[var(--text-main)]">
                پلن هدف
                <select id="bulk-user-plan-select" class="min-w-[180px] p-2 rounded-lg border outline-none bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
                  <option value="">پلن رایگان</option>
                  @foreach($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} · {{ number_format($plan->tokens) }} اعتبار</option>
                  @endforeach
                </select>
              </label>
              <button type="button" class="btn-pro btn-pro-primary" onclick="window.openBulkUserPlanDialog()"><i class="fa-solid fa-arrow-right-arrow-left text-[11px]"></i> اعمال پلن</button>
            </div>
          @endif
          @if($canManageUserStatuses)
            <div class="flex items-center gap-2 flex-wrap">
              <label class="flex items-center gap-2 text-[11px] font-bold text-[var(--text-main)]">
                وضعیت کاربر
                <select id="bulk-user-status-select" class="min-w-[130px] p-2 rounded-lg border outline-none bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer">
                  <option value="active">فعال</option>
                  <option value="suspended">معلق</option>
                  <option value="deleted">حذف شده</option>
                </select>
              </label>
              <button type="button" class="btn-pro btn-pro-ghost" onclick="window.openBulkUserStatusDialog()"><i class="fa-solid fa-user-shield text-[11px]"></i> اعمال وضعیت</button>
            </div>
          @endif
          <button type="button" class="btn-pro btn-pro-ghost" onclick="window.exportSelectedUsers()"><i class="fa-solid fa-download text-[11px]"></i> خروجی انتخاب‌شده</button>
          <button type="button" class="btn-pro btn-pro-primary" onclick="window.openBulkUserTokenDialog()"><i class="fa-solid fa-coins text-[11px]"></i> مدیریت اعتبار</button>
          <button type="button" class="btn-pro btn-pro-ghost" onclick="window.clearUserSelection()">لغو انتخاب</button>
        </section>
      @endif

      <div class="users-index-table overflow-x-auto rounded-2xl border bg-[var(--card-bg)] border-[var(--border)]">
        <table class="w-full table-fixed border-collapse text-right" id="usersTable">
          <thead>
            <tr class="h-[68px] bg-[var(--page-bg)]">
              @if($canBulkManageUsers)
                <th class="p-2 text-[10px] font-bold border-b border-[var(--border)] text-[var(--text-soft)] w-[3%] text-center"><input type="checkbox" id="select-all-users" class="cursor-pointer accent-[var(--primary)]" onchange="window.toggleAllUsers(this.checked)" aria-label="انتخاب همه کاربران"></th>
              @endif
              <th class="p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[13%] text-center"><span class="block">تعداد</span><span class="block">اطلاعات</span><span class="block">شناسه کاربر</span></th>
              <th class="p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[15%] text-center"><span class="block">نام و نام خانوادگی</span><span class="block">شماره</span><span class="block">ایمیل</span></th>
              <th class="p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[14%] text-center"><span class="block">ورود و</span><span class="block">خروجی‌ها</span></th>
              
              <th class="p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[13%] text-center"><span class="block">گزارش اعتبارها</span><span class="block">خرید و مصرف</span></th>
              
              <th class="p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[16%] text-center"><span class="block">دعوت از طرف</span><span class="block">عضویت</span></th>
              <th class="admin-status-column p-2 text-[10px] leading-4 font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[14%] text-center"><span class="block">وضعیت کاربر</span><span class="block">پلن</span><span class="block">گروه قیمت‌گذاری</span></th>
              <th class="p-2 text-[10px] font-bold tracking-wider border-b border-[var(--border)] text-[var(--text-soft)] w-[12%] text-center">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            @forelse($users as $i => $user)
            <tr class="transition-colors" data-user-row="{{ $user->id }}" style="--tw-bg-opacity:1;" onmouseenter="this.style.background='var(--primary-l)'" onmouseleave="this.style.background=''">
              @if($canBulkManageUsers)
                <td class="p-3 text-center"><input type="checkbox" value="{{ $user->id }}" class="user-bulk-check cursor-pointer accent-[var(--primary)]" onchange="window.updateUserBulkSelection()" aria-label="انتخاب {{ trim(($user->name ?? 'کاربر').' '.($user->last_name ?? '')) }} برای عملیات گروهی"></td>
              @endif
              @php
                $birthDateLabel = '—';
                if ($user->birth_date) {
                  [$birthJy, $birthJm, $birthJd] = \App\Support\Jalali::toJalaliYmd(
                    (int) $user->birth_date->format('Y'),
                    (int) $user->birth_date->format('n'),
                    (int) $user->birth_date->format('j'),
                  );
                  $birthDateLabel = sprintf('%04d/%02d/%02d', $birthJy, $birthJm, $birthJd);
                }
              @endphp
              <td class="p-2 text-center">
                <div class="flex flex-col items-center gap-1">
                  @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-[var(--border)]">
                  @else
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary)] flex items-center justify-center text-[12px] font-bold flex-shrink-0 text-[var(--text-h)]">
                      {{ mb_substr($user->name ?? 'ک', 0, 1) }}
                    </div>
                  @endif
                  <div class="inline-flex items-center justify-center gap-1 text-[9.5px] text-[var(--text-soft)]"><i class="fa-solid fa-list-ol text-[9px]" title="تعداد در فهرست"></i><span>تعداد: {{ $i + 1 }}</span></div>
                  <div class="inline-flex max-w-full items-center justify-center gap-1.5 text-[9.5px] text-[var(--text-soft)]">
                    <span class="inline-flex items-center gap-1 font-mono dir-ltr"><i class="fa-regular fa-id-badge text-[9px]" title="شناسه کاربر"></i><span>ID: {{ $user->id }}</span></span>
                    <span class="text-[var(--border)]">•</span>
                    <span class="inline-flex min-w-0 items-center gap-1"><i class="fa-solid fa-cake-candles text-[9px]" title="تاریخ تولد"></i><span class="truncate" title="{{ $birthDateLabel }}">{{ $birthDateLabel }}</span></span>
                  </div>
                </div>
              </td>

              <td class="p-2 text-center">
                <div class="admin-user-identity">
                  <strong title="{{ trim(($user->name ?? '').' '.($user->last_name ?? '')) ?: 'کاربر بدون نام' }}">{{ trim(($user->name ?? '').' '.($user->last_name ?? '')) ?: '—' }}</strong>
                  <span><i class="fa-solid fa-mobile-screen-button"></i><b dir="ltr">{{ $user->phone ?: '—' }}</b></span>
                  <span><i class="fa-solid fa-at"></i><b dir="ltr" title="{{ $user->email ?: 'ایمیل ثبت نشده' }}">{{ $user->email ?: '—' }}</b></span>
                </div>
              </td>
              <td class="p-2 text-center">
                @include('admin.users.partials.operational-snapshot', ['snapshotUser' => $user])
              </td>
              <td class="admin-credit-report p-2 text-[10px]">
                <div class="flex flex-col gap-1 text-right select-none">
                  <div class="flex justify-between items-center bg-[var(--page-bg)]/50 p-1 px-1.5 rounded border border-[var(--border)]/40">
                    <span class="text-[var(--text-soft)] text-[10px]">موجودی فعلی:</span>
                    <span data-user-token-balance="{{ $user->id }}" class="font-bold text-[var(--warning)] font-mono">{{ number_format($user->effective_token_balance) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[var(--text-soft)] text-[10px]">کل خریداری شده:</span>
                    <span data-user-token-purchased="{{ $user->id }}" class="text-[var(--success)] font-mono font-medium">{{ number_format($user->tokens_purchased ?? 0) }}</span>
                  </div>
                  <div class="flex justify-between items-center p-0.5 px-1.5">
                    <span class="text-[var(--text-soft)] text-[10px]">کل مصرف شده:</span>
                    <span class="text-[var(--danger)] font-mono font-medium">{{ number_format($user->tokens_used ?? 0) }}</span>
                  </div>
                </div>
              </td>

              @php
                $referralConversion = $user->referralConversion;
                $referrer = $user->referrer ?: $referralConversion?->inviter;
                $referralVisit = $referralConversion?->visit;
                $referralCode = $referralVisit?->referral_code ?: $referrer?->referral_code;
                $referralUrl = $referralCode ? route('referral.visit', ['code' => $referralCode]) : null;
                $joinedAt = $user->registered_at ?? $user->created_at;
                $selectedPlanId = $user->hasFreePlan() ? '' : $user->plan_id;
              @endphp
              <td class="admin-status-column p-2 text-center">
                <div class="flex flex-col items-center gap-1.5 text-[10.5px]">
                  <div class="inline-flex items-center justify-center gap-1 text-[var(--info)] font-mono font-medium text-[10px]" dir="rtl" title="عملکرد لینک‌های دعوت این کاربر">
                    <span class="text-[var(--text-soft)] font-sans">لینک دعوت:</span>
                    <span>{{ number_format((int) ($user->referral_links_count ?? 0)) }} لینک | {{ number_format((int) ($user->referral_visits_count ?? 0)) }} ورود</span>
                  </div>
                  <span class="w-10 h-px bg-[var(--border)]"></span>
                  @if($referrer)
                    <span class="max-w-full truncate font-bold text-[var(--text-h)]" title="{{ trim(($referrer->name ?? '').' '.($referrer->last_name ?? '')) ?: 'کاربر وطن' }}">{{ trim(($referrer->name ?? '').' '.($referrer->last_name ?? '')) ?: 'کاربر وطن' }}</span>
                    @if($referralUrl)
                      <span title="{{ $referralVisit?->landing_url ?: $referralUrl }}" class="inline-flex items-center gap-1 text-[var(--info)] font-mono dir-ltr">
                        <i class="fa-solid fa-link text-[9px]"></i><span>/r/{{ $referralCode }}</span>
                      </span>
                    @endif
                  @else
                    <span class="text-[var(--text-soft)]">ثبت‌نام مستقیم</span>
                  @endif
                  <span class="w-10 h-px bg-[var(--border)]"></span>
                  <span class="font-mono dir-ltr text-[10px] text-[var(--text-main)]">{{ \App\Support\Jalali::formatNumeric($joinedAt) }}</span>
                </div>
              </td>

              <td class="p-2 text-center">
                <div class="flex flex-col items-center gap-1.5">
                  @if($canManageUserStatuses)
                    <select onchange="window.changeUserStatus({{ $user->id }}, this)" data-user-status-id="{{ $user->id }}" data-current="{{ $user->status }}" class="user-status-control w-full max-w-[120px] px-2 py-1 rounded-md border outline-none bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--info)] cursor-pointer text-[10.5px] text-center" aria-label="تغییر وضعیت {{ trim(($user->name ?? 'کاربر').' '.($user->last_name ?? '')) }}">
                      <option value="active" @selected($user->status === 'active')>فعال</option>
                      <option value="suspended" @selected($user->status === 'suspended')>معلق</option>
                      <option value="deleted" @selected($user->status === 'deleted')>حذف شده</option>
                    </select>
                  @else
                    <span class="user-status-control w-full max-w-[120px] px-2 py-1 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center text-[10.5px]">
                      @if($user->status === 'active') فعال @elseif($user->status === 'suspended') معلق @else حذف شده @endif
                    </span>
                  @endif
                  @if($canManageUserPlans)
                    <select onchange="window.changeUserPlan({{ $user->id }}, this)" data-user-plan-id="{{ $user->id }}" data-current="{{ $selectedPlanId ?: '' }}" class="user-status-control w-full max-w-[120px] px-2 py-1 rounded-md border outline-none bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--info)] cursor-pointer text-[10.5px] text-center" aria-label="تغییر پلن {{ trim(($user->name ?? 'کاربر').' '.($user->last_name ?? '')) }}">
                      <option value="" @selected(!$selectedPlanId)>پلن رایگان</option>
                      @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((int) $selectedPlanId === $plan->id)>{{ $plan->name }} · {{ number_format($plan->tokens) }} اعتبار</option>
                      @endforeach
                    </select>
                  @else
                    <span class="user-status-control w-full max-w-[120px] px-2 py-1 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center text-[10.5px]">{{ $user->plan_display_name }}</span>
                  @endif
                  <select onchange="window.changeCustomerSegment({{ $user->id }}, this)" data-current="{{ $user->customer_segment ?: 'regular' }}" class="user-status-control w-full max-w-[120px] px-2 py-1 rounded-md border outline-none bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--info)] cursor-pointer text-[10.5px] text-center" aria-label="تغییر گروه قیمت‌گذاری">
                    <option value="regular" @selected(($user->customer_segment ?: 'regular') === 'regular')>کاربر عادی</option>
                    <option value="loyal" @selected($user->customer_segment === 'loyal')>مشتری ثابت</option>
                  </select>
                </div>
              </td>
              <td class="p-2 text-center">
                <div class="grid grid-cols-2 gap-1.5 justify-center w-fit mx-auto">
                  <button type="button" onclick="window.openUserModal(@js(trim(($user->name ?? 'کاربر').' '.($user->last_name ?? ''))), @js($user->generatedImages), {{ $user->id }}, @js($selectedPlanId ?: ''), @js(\App\Support\Jalali::formatNumeric($joinedAt)))" class="w-[66px] px-1.5 py-1 rounded-md border bg-[var(--info-l)] border-[var(--info-m)] hover:bg-[var(--info-l)] text-[var(--info)] text-[10px] font-medium transition-colors cursor-pointer"><i class="fa-solid fa-eye ml-1"></i> نمایش</button>
                  <a href="{{ route('admin.users.logs', $user->id) }}" class="w-[66px] px-1.5 py-1 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-main)] inline-flex items-center justify-center gap-1 cursor-pointer text-[10px] transition-all hover:border-[var(--info)] hover:text-[var(--info)]" title="مشاهده لاگ‌ها"><i class="fa-solid fa-history"></i> لاگ‌ها</a>
                  <a href="{{ route('admin.users.gallery.show', $user->id) }}" class="w-[66px] px-1.5 py-1 rounded-md border bg-[var(--primary-l)] border-[var(--primary-m)] text-[var(--primary)] inline-flex items-center justify-center gap-1 cursor-pointer text-[10px] transition-all hover:border-[var(--info)] hover:text-[var(--info)]" title="گالری و پروفایل کامل ورودی‌های کاربر"><i class="fa-solid fa-images"></i> گالری</a>
                  <div class="admin-user-credit-action-stack">
                    <button type="button" data-user-token-dialog data-user-id="{{ $user->id }}" data-user-name="{{ trim(($user->name ?? '').' '.($user->last_name ?? '')) }}" data-user-token="{{ (int) $user->tokens }}" class="admin-user-credit-button w-[66px] px-1.5 py-1 rounded-md border bg-[var(--page-bg)] border-[var(--text-main)] text-[var(--text-main)] inline-flex items-center justify-center gap-1 cursor-pointer text-[10px] transition-all hover:border-[var(--info)] hover:text-[var(--info)]" title="مدیریت اعتبار"><i class="fa-solid fa-coins"></i> اعتبار</button>
                    @if($canManageUserPlans)
                      <a class="admin-user-referral-button" href="{{ route('admin.referrals.users.links.create', $user) }}" title="مدیریت لینک‌ها و عملکرد همکاری در فروش {{ trim(($user->name ?? '').' '.($user->last_name ?? '')) }}">
                        <span><i class="fa-solid fa-people-arrows"></i> همکاری</span><i class="fa-solid fa-arrow-up-left-from-circle"></i>
                      </a>
                    @endif
                  </div>
                  @if((int) ($user->finance_cases_count ?? 0) > 0)
                    <a href="{{ route('admin.finance.cases.index', ['user_id' => $user->id]) }}" class="w-[66px] px-1.5 py-1 rounded-md border bg-[var(--success-l)] border-[var(--success-m)] text-[var(--success)] inline-flex items-center justify-center gap-1 cursor-pointer text-[10px] transition-all" title="پرونده‌های مالی کاربر"><i class="fa-solid fa-chart-pie"></i> مالی</a>
                  @else
                    <button type="button" disabled class="w-[66px] px-1.5 py-1 rounded-md border bg-[var(--page-bg)] border-[var(--border)] text-[var(--text-soft)] inline-flex items-center justify-center gap-1 text-[10px] opacity-50 cursor-not-allowed" title="این کاربر هنوز خرید، هدیه، ارتقا یا تعدیل مالی ثبت‌شده ندارد"><i class="fa-solid fa-chart-pie"></i> مالی</button>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="{{ $canBulkManageUsers ? 8 : 7 }}" class="text-center p-10 text-[13px] text-[var(--text-soft)]">هیچ کاربری یافت نشد.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<div id="productModal" class="fixed inset-0 z-[9999] items-center justify-center backdrop-blur-sm p-4 hidden transition-all duration-300 opacity-0" style="background:color-mix(in srgb, var(--text-h) 70%, transparent);">
  <div class="bg-[var(--card-bg)] border border-[var(--border)] rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
    <div class="flex items-center justify-between p-4 border-b border-[var(--border)] bg-[var(--page-bg)]">
      <h3 class="text-[14px] font-bold text-[var(--text-h)] flex items-center gap-2">
        <i class="fa-solid fa-user-gear text-[var(--info)]"></i>
        جزئیات کاربر: <span id="modalUserName" class="text-[var(--info)]"></span>
      </h3>
      <button onclick="window.closeUserModal()" class="text-[var(--text-soft)] hover:text-[var(--text-h)] transition-colors cursor-pointer text-[16px]">&times;</button>
    </div>
    
    <div class="p-4 max-h-[350px] overflow-y-auto" id="modalContent"></div>

    <div class="p-3 border-t border-[var(--border)] bg-[var(--page-bg)] text-left">
      <button onclick="window.closeUserModal()" class="px-4 py-1.5 bg-[var(--border)] hover:bg-[var(--primary-m)] text-[var(--text-main)] rounded-lg text-[12px] font-medium transition-colors cursor-pointer">بستن پنجره</button>
    </div>
  </div>
</div>

@if($canBulkManageUsers)
  <dialog id="bulk-user-token-dialog" class="w-[min(94vw,520px)] p-0 overflow-hidden rounded-2xl border shadow-2xl bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-h)] backdrop:bg-[color-mix(in_srgb,var(--text-h)_55%,transparent)]" dir="rtl" aria-labelledby="bulk-user-token-dialog-title">
    <form method="dialog" class="p-5" id="bulk-user-token-form" onsubmit="return false;">
      <div class="flex items-start justify-between gap-4 pb-4 border-b border-[var(--border)]">
        <div class="flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl inline-flex items-center justify-center bg-[var(--primary-l)] border border-[var(--primary-m)] text-[var(--primary)]"><i class="fa-solid fa-coins"></i></span>
          <div><h3 id="bulk-user-token-dialog-title" class="text-[14px] font-extrabold">مدیریت اعتبار کاربران</h3><p id="bulk-user-token-dialog-subtitle" class="mt-1 text-[10.5px] text-[var(--text-soft)]">برای کاربران منتخب اعمال می‌شود.</p></div>
        </div>
        <button type="button" onclick="window.closeBulkUserTokenDialog()" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-[var(--text-soft)] hover:text-[var(--danger)]" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="py-5 grid gap-3">
        <input type="hidden" id="bulk-user-token-ids">
        <label class="text-[11px] font-bold">نوع عملیات<select id="bulk-user-token-action" class="mt-1 w-full p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]" onchange="window.updateBulkTokenExpiryVisibility()"><option value="add">افزودن اعتبار</option><option value="deduct">کسر اعتبار</option><option value="set">تنظیم مستقیم موجودی</option></select></label>
        <label class="text-[11px] font-bold">مقدار اعتبار<input id="bulk-user-token-amount" type="number" min="0" step="1" inputmode="numeric" class="mt-1 w-full p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]" placeholder="مثال: ۱۰"></label>
        <label class="text-[11px] font-bold" id="bulk-user-token-kind-label">منبع اعتبار<select id="bulk-user-token-kind" class="mt-1 w-full p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]"><option value="gift">اعتبار هدیه</option><option value="plan_upgrade">هدیه ارتقای پلن</option><option value="paid_adjustment">اصلاح اعتبار خریداری‌شده</option></select></label>
        <div id="bulk-user-token-expiry" class="grid gap-2">
          <label class="text-[11px] font-bold">مهلت استفاده<select id="bulk-user-token-expiry-unit" class="mt-1 w-full p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]" onchange="window.updateBulkTokenExpiryVisibility()"><option value="none">بدون انقضا</option><option value="days">تعداد روز</option><option value="weeks">تعداد هفته</option><option value="months">تعداد ماه</option><option value="date">تاریخ مشخص</option></select></label>
          <input id="bulk-user-token-expiry-value" type="number" min="1" class="p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]" placeholder="مثال: ۷" style="display:none">
          <input id="bulk-user-token-expiry-date" type="date" class="p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)]" style="display:none">
        </div>
        <label class="flex items-center gap-2 text-[11px] cursor-pointer"><input id="bulk-user-token-send-sms" type="checkbox" class="accent-[var(--primary)]"> ارسال پیامک برای کاربر(ان)</label>
        <input id="bulk-user-token-note" type="text" maxlength="255" class="p-2 rounded-lg border bg-[var(--input-bg)] border-[var(--border)] text-[11px]" placeholder="توضیحات (اختیاری)">
        <p id="bulk-user-token-state" class="hidden text-[11px] text-[var(--danger)]"></p>
      </div>
      <div class="flex items-center justify-end gap-2 pt-4 border-t border-[var(--border)]"><button type="button" onclick="window.closeBulkUserTokenDialog()" class="btn-pro btn-pro-ghost">انصراف</button><button type="button" id="bulk-user-token-submit" onclick="window.submitBulkUserToken()" class="btn-pro btn-pro-primary"><i class="fa-solid fa-check"></i> اعمال اعتبار</button></div>
    </form>
  </dialog>
@endif

@if($canManageUserPlans)
  <dialog id="bulk-user-plan-dialog" class="w-[min(92vw,460px)] p-0 overflow-hidden rounded-2xl border shadow-2xl bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-h)] backdrop:bg-[color-mix(in_srgb,var(--text-h)_55%,transparent)]" dir="rtl" aria-labelledby="bulk-user-plan-dialog-title">
    <div class="p-5">
      <div class="flex items-start justify-between gap-4 pb-4 border-b border-[var(--border)]">
        <div class="flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl inline-flex items-center justify-center bg-[var(--primary-l)] border border-[var(--primary-m)] text-[var(--primary)]"><i class="fa-solid fa-users-gear"></i></span>
          <div>
            <h3 id="bulk-user-plan-dialog-title" class="text-[14px] font-extrabold text-[var(--text-h)]">تغییر پلن کاربران منتخب</h3>
            <p class="mt-1 text-[10.5px] text-[var(--text-soft)]">اعتبار کاربران و سابقه خریدشان تغییر نمی‌کند.</p>
          </div>
        </div>
        <button type="button" onclick="window.closeBulkUserPlanDialog()" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-[var(--text-soft)] hover:text-[var(--danger)] hover:bg-[var(--danger-l)]" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="py-5 space-y-3">
        <div class="p-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)]">
          <div class="text-[10.5px] text-[var(--text-soft)]">کاربران انتخاب‌شده</div>
          <div id="bulk-user-plan-dialog-count" class="mt-1 text-[18px] font-extrabold text-[var(--text-h)]">۰ کاربر</div>
        </div>
        <div class="p-3 rounded-xl border bg-[var(--primary-l)] border-[var(--primary-m)]">
          <div class="text-[10.5px] text-[var(--text-soft)]">پلن جدید</div>
          <div id="bulk-user-plan-dialog-target" class="mt-1 text-[13px] font-bold text-[var(--primary)]"></div>
        </div>
        <p id="bulk-user-plan-dialog-state" class="hidden text-[11px] text-[var(--danger)]"></p>
      </div>
      <div class="flex items-center justify-end gap-2 pt-4 border-t border-[var(--border)]">
        <button type="button" onclick="window.closeBulkUserPlanDialog()" class="btn-pro btn-pro-ghost">انصراف</button>
        <button id="bulk-user-plan-dialog-submit" type="button" onclick="window.applyBulkUserPlan()" class="btn-pro btn-pro-primary"><i class="fa-solid fa-check text-[11px]"></i> تایید تغییر پلن</button>
      </div>
    </div>
  </dialog>
@endif

@if($canManageUserStatuses)
  <dialog id="bulk-user-status-dialog" class="w-[min(92vw,460px)] p-0 overflow-hidden rounded-2xl border shadow-2xl bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-h)] backdrop:bg-[color-mix(in_srgb,var(--text-h)_55%,transparent)]" dir="rtl" aria-labelledby="bulk-user-status-dialog-title">
    <div class="p-5">
      <div class="flex items-start justify-between gap-4 pb-4 border-b border-[var(--border)]">
        <div class="flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl inline-flex items-center justify-center bg-[var(--primary-l)] border border-[var(--primary-m)] text-[var(--primary)]"><i class="fa-solid fa-user-shield"></i></span>
          <div>
            <h3 id="bulk-user-status-dialog-title" class="text-[14px] font-extrabold text-[var(--text-h)]">تغییر وضعیت کاربران منتخب</h3>
            <p class="mt-1 text-[10.5px] text-[var(--text-soft)]">حذف‌شده یک وضعیت نرم است و اطلاعات کاربر پاک نمی‌شود.</p>
          </div>
        </div>
        <button type="button" onclick="window.closeBulkUserStatusDialog()" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-[var(--text-soft)] hover:text-[var(--danger)] hover:bg-[var(--danger-l)]" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="py-5 space-y-3">
        <div class="p-3 rounded-xl border bg-[var(--page-bg)] border-[var(--border)]">
          <div class="text-[10.5px] text-[var(--text-soft)]">کاربران انتخاب‌شده</div>
          <div id="bulk-user-status-dialog-count" class="mt-1 text-[18px] font-extrabold text-[var(--text-h)]">۰ کاربر</div>
        </div>
        <div class="p-3 rounded-xl border bg-[var(--primary-l)] border-[var(--primary-m)]">
          <div class="text-[10.5px] text-[var(--text-soft)]">وضعیت جدید</div>
          <div id="bulk-user-status-dialog-target" class="mt-1 text-[13px] font-bold text-[var(--primary)]"></div>
        </div>
        <p id="bulk-user-status-dialog-state" class="hidden text-[11px] text-[var(--danger)]"></p>
      </div>
      <div class="flex items-center justify-end gap-2 pt-4 border-t border-[var(--border)]">
        <button type="button" onclick="window.closeBulkUserStatusDialog()" class="btn-pro btn-pro-ghost">انصراف</button>
        <button id="bulk-user-status-dialog-submit" type="button" onclick="window.applyBulkUserStatus()" class="btn-pro btn-pro-primary"><i class="fa-solid fa-check text-[11px]"></i> تایید تغییر وضعیت</button>
      </div>
    </div>
  </dialog>
@endif

@endsection

@section('scripts')
<script src="{{ asset('admin/js/user-credit-dialog.js') }}?v={{ filemtime(public_path('admin/js/user-credit-dialog.js')) }}"></script>
<script>
try {
  var bc = document.getElementById('breadcrumb');
  if(bc) bc.textContent = 'لیست کاربران سیستم';
} catch(e){}

window.userPlanOptions = @json($planOptionsForJs);
window.canManageUserPlans = @json((bool) $canManageUserPlans);
window.canManageUserStatuses = @json((bool) $canManageUserStatuses);
window.bulkUserPlanUrl = @json(route('admin.users.bulk-plan'));
window.bulkUserStatusUrl = @json(route('admin.users.bulk-status'));
window.bulkUserTokenUrl = @json(route('admin.api.users.bulk_token.update'));
window.userExportUrl = @json(route('admin.users.export'));
window.bulkUserPlanDialogState = { userIds: [], planId: null };
window.bulkUserStatusDialogState = { userIds: [], status: null };
window.bulkUserTokenDialogState = { userIds: [] };

window.updateBulkTokenExpiryVisibility = function() {
  const action = document.getElementById('bulk-user-token-action')?.value;
  const kind = document.getElementById('bulk-user-token-kind')?.value;
  const group = document.getElementById('bulk-user-token-expiry');
  const unit = document.getElementById('bulk-user-token-expiry-unit')?.value;
  const show = action === 'add' && kind !== 'paid_adjustment';
  if (group) group.style.display = show ? 'grid' : 'none';
  const value = document.getElementById('bulk-user-token-expiry-value');
  const date = document.getElementById('bulk-user-token-expiry-date');
  if (value) value.style.display = show && ['days','weeks','months'].includes(unit) ? 'block' : 'none';
  if (date) date.style.display = show && unit === 'date' ? 'block' : 'none';
};

window.openUserTokenDialog = function(userId, name, token) {
  const dialog = document.getElementById('bulk-user-token-dialog');
  if (!dialog) return;
  window.bulkUserTokenDialogState = { userIds: [Number(userId)] };
  document.getElementById('bulk-user-token-dialog-subtitle').textContent = (name || 'کاربر') + ' · موجودی فعلی: ' + Number(token || 0).toLocaleString('fa-IR') + ' اعتبار';
  document.getElementById('bulk-user-token-ids').value = String(userId);
  document.getElementById('bulk-user-token-action').value = 'add';
  document.getElementById('bulk-user-token-kind').value = 'gift';
  document.getElementById('bulk-user-token-amount').value = '';
  document.getElementById('bulk-user-token-expiry-unit').value = 'none';
  document.getElementById('bulk-user-token-send-sms').checked = false;
  document.getElementById('bulk-user-token-note').value = '';
  window.updateBulkTokenExpiryVisibility();
  document.getElementById('bulk-user-token-state').classList.add('hidden');
  if (typeof dialog.showModal === 'function') dialog.showModal();
};

window.openBulkUserTokenDialog = function() {
  const ids = window.getSelectedUserIds();
  if (!ids.length) return;
  const dialog = document.getElementById('bulk-user-token-dialog');
  if (!dialog) return;
  window.bulkUserTokenDialogState = { userIds: ids };
  document.getElementById('bulk-user-token-dialog-subtitle').textContent = ids.length.toLocaleString('fa-IR') + ' کاربر انتخاب شده‌اند.';
  document.getElementById('bulk-user-token-ids').value = ids.join(',');
  document.getElementById('bulk-user-token-action').value = 'add';
  document.getElementById('bulk-user-token-kind').value = 'gift';
  document.getElementById('bulk-user-token-amount').value = '';
  document.getElementById('bulk-user-token-expiry-unit').value = 'none';
  document.getElementById('bulk-user-token-send-sms').checked = false;
  document.getElementById('bulk-user-token-note').value = '';
  window.updateBulkTokenExpiryVisibility();
  document.getElementById('bulk-user-token-state').classList.add('hidden');
  if (typeof dialog.showModal === 'function') dialog.showModal();
};

window.closeBulkUserTokenDialog = function() { const dialog = document.getElementById('bulk-user-token-dialog'); if (dialog?.open) dialog.close(); };

window.submitBulkUserToken = async function() {
  const state = document.getElementById('bulk-user-token-state');
  const submit = document.getElementById('bulk-user-token-submit');
  const payload = window.bulkUserTokenDialogState || { userIds: [] };
  const action = document.getElementById('bulk-user-token-action').value;
  const amount = Number(document.getElementById('bulk-user-token-amount').value);
  const kind = document.getElementById('bulk-user-token-kind').value;
  const unit = document.getElementById('bulk-user-token-expiry-unit').value;
  if (!payload.userIds.length || !Number.isInteger(amount) || amount < 0 || (action !== 'set' && amount < 1)) { state.textContent = 'مقدار اعتبار را به‌درستی وارد کنید.'; state.classList.remove('hidden'); return; }
  let expiresAt = null;
  if (action === 'add' && kind !== 'paid_adjustment' && unit !== 'none') {
    if (unit === 'date') expiresAt = document.getElementById('bulk-user-token-expiry-date').value || null;
    else { const n = Number(document.getElementById('bulk-user-token-expiry-value').value); if (!Number.isInteger(n) || n < 1) { state.textContent = 'مهلت اعتبار را وارد کنید.'; state.classList.remove('hidden'); return; } const d = new Date(); if (unit === 'days') d.setDate(d.getDate()+n); if (unit === 'weeks') d.setDate(d.getDate()+n*7); if (unit === 'months') d.setMonth(d.getMonth()+n); expiresAt = d.toISOString(); }
  }
  submit.disabled = true;
  try {
    const response = await fetch(window.bulkUserTokenUrl, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''}, body:JSON.stringify({user_ids:payload.userIds, action, amount, credit_kind:kind, expires_at:expiresAt, send_sms:document.getElementById('bulk-user-token-send-sms').checked, note:document.getElementById('bulk-user-token-note').value || null}) });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'اعمال اعتبار انجام نشد.');
    window.closeBulkUserTokenDialog(); window.clearUserSelection();
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success'); else alert(data.message);
  } catch (error) { state.textContent = error.message || 'اعمال اعتبار انجام نشد.'; state.classList.remove('hidden'); }
  finally { submit.disabled = false; }
};

window.escapeUserPlanHtml = function(value) {
  return String(value ?? '').replace(/[&<>'"]/g, function(character) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character];
  });
}

window.userPlanOptionsMarkup = function(currentPlanId) {
  const current = currentPlanId ? String(currentPlanId) : '';
  const freeSelected = current === '' ? ' selected' : '';
  const plans = (window.userPlanOptions || []).map(function(plan) {
    const selected = String(plan.id) === current ? ' selected' : '';
    return '<option value="' + plan.id + '"' + selected + '>'
      + window.escapeUserPlanHtml(plan.name) + ' · ' + Number(plan.tokens || 0).toLocaleString('fa-IR') + ' اعتبار</option>';
  }).join('');
  return '<option value=""' + freeSelected + '>پلن رایگان</option>' + plans;
}

window.getSelectedUserIds = function() {
  return Array.from(document.querySelectorAll('.user-bulk-check:checked')).map(function(input) {
    return Number(input.value);
  }).filter(Number.isFinite);
}

window.updateUserBulkSelection = function() {
  const toolbar = document.getElementById('bulk-user-actions-toolbar');
  const selectAll = document.getElementById('select-all-users');
  const checks = Array.from(document.querySelectorAll('.user-bulk-check'));
  const selected = window.getSelectedUserIds();

  if (toolbar) {
    toolbar.classList.toggle('hidden', selected.length === 0);
    toolbar.classList.toggle('flex', selected.length > 0);
  }

  const count = document.getElementById('bulk-user-actions-count');
  if (count) count.textContent = selected.length.toLocaleString('fa-IR');

  if (selectAll) {
    const visibleChecks = checks.filter(function(check) {
      return check.closest('tr') && check.closest('tr').style.display !== 'none';
    });
    selectAll.checked = visibleChecks.length > 0 && visibleChecks.every(function(check) { return check.checked; });
    selectAll.indeterminate = visibleChecks.some(function(check) { return check.checked; }) && !selectAll.checked;
  }
}

window.toggleAllUsers = function(checked) {
  document.querySelectorAll('.user-bulk-check').forEach(function(check) {
    const row = check.closest('tr');
    if (row && row.style.display !== 'none') check.checked = checked;
  });
  window.updateUserBulkSelection();
}

window.clearUserSelection = function() {
  document.querySelectorAll('.user-bulk-check:checked').forEach(function(check) { check.checked = false; });
  window.updateUserBulkSelection();
}

window.exportSelectedUsers = function() {
  const userIds = window.getSelectedUserIds();
  if (!userIds.length) return;

  const current = new URL(window.location.href);
  const exportUrl = new URL(window.userExportUrl, window.location.origin);
  ['q', 'birth_month', 'birth_day'].forEach(function(key) {
    const value = current.searchParams.get(key);
    if (value) exportUrl.searchParams.set(key, value);
  });
  userIds.forEach(function(userId) { exportUrl.searchParams.append('user_ids[]', userId); });
  window.location.assign(exportUrl.toString());
}

window.syncUserPlanControls = function(userId, planId) {
  const value = planId ? String(planId) : '';
  document.querySelectorAll('[data-user-plan-id="' + String(userId) + '"]').forEach(function(select) {
    select.value = value;
    select.dataset.current = value;
  });
}

window.openBulkUserPlanDialog = function() {
  const userIds = window.getSelectedUserIds();
  const planSelect = document.getElementById('bulk-user-plan-select');
  const dialog = document.getElementById('bulk-user-plan-dialog');
  if (!userIds.length || !planSelect || !dialog) return;

  const target = planSelect.options[planSelect.selectedIndex];
  window.bulkUserPlanDialogState = { userIds: userIds, planId: planSelect.value || null };
  document.getElementById('bulk-user-plan-dialog-count').textContent = userIds.length.toLocaleString('fa-IR') + ' کاربر';
  document.getElementById('bulk-user-plan-dialog-target').textContent = target ? target.textContent.trim() : 'پلن رایگان';
  const state = document.getElementById('bulk-user-plan-dialog-state');
  state.textContent = '';
  state.classList.add('hidden');
  if (typeof dialog.showModal === 'function') dialog.showModal();
}

window.closeBulkUserPlanDialog = function() {
  const dialog = document.getElementById('bulk-user-plan-dialog');
  if (dialog && dialog.open) dialog.close();
}

window.applyBulkUserPlan = async function() {
  const dialog = document.getElementById('bulk-user-plan-dialog');
  const submit = document.getElementById('bulk-user-plan-dialog-submit');
  const state = document.getElementById('bulk-user-plan-dialog-state');
  const payload = window.bulkUserPlanDialogState || { userIds: [], planId: null };
  if (!payload.userIds.length || !submit) return;

  submit.disabled = true;
  submit.classList.add('opacity-60', 'cursor-wait');
  state.textContent = '';
  state.classList.add('hidden');
  try {
    const response = await fetch(window.bulkUserPlanUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ user_ids: payload.userIds, plan_id: payload.planId })
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'ذخیره پلن کاربران انجام نشد.');

    payload.userIds.forEach(function(userId) { window.syncUserPlanControls(userId, data.plan_id); });
    window.closeBulkUserPlanDialog();
    window.clearUserSelection();
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
    else alert(data.message);
  } catch (error) {
    state.textContent = error.message || 'ذخیره پلن کاربران انجام نشد.';
    state.classList.remove('hidden');
  } finally {
    submit.disabled = false;
    submit.classList.remove('opacity-60', 'cursor-wait');
  }
}

window.syncUserStatusControls = function(userId, status) {
  document.querySelectorAll('[data-user-status-id="' + String(userId) + '"]').forEach(function(select) {
    select.value = status;
    select.dataset.current = status;
  });
}

window.openBulkUserStatusDialog = function() {
  const userIds = window.getSelectedUserIds();
  const statusSelect = document.getElementById('bulk-user-status-select');
  const dialog = document.getElementById('bulk-user-status-dialog');
  if (!userIds.length || !statusSelect || !dialog) return;

  const target = statusSelect.options[statusSelect.selectedIndex];
  window.bulkUserStatusDialogState = { userIds: userIds, status: statusSelect.value };
  document.getElementById('bulk-user-status-dialog-count').textContent = userIds.length.toLocaleString('fa-IR') + ' کاربر';
  document.getElementById('bulk-user-status-dialog-target').textContent = target ? target.textContent.trim() : 'فعال';
  const state = document.getElementById('bulk-user-status-dialog-state');
  state.textContent = '';
  state.classList.add('hidden');
  if (typeof dialog.showModal === 'function') dialog.showModal();
}

window.closeBulkUserStatusDialog = function() {
  const dialog = document.getElementById('bulk-user-status-dialog');
  if (dialog && dialog.open) dialog.close();
}

window.applyBulkUserStatus = async function() {
  const submit = document.getElementById('bulk-user-status-dialog-submit');
  const state = document.getElementById('bulk-user-status-dialog-state');
  const payload = window.bulkUserStatusDialogState || { userIds: [], status: null };
  if (!payload.userIds.length || !payload.status || !submit) return;

  submit.disabled = true;
  submit.classList.add('opacity-60', 'cursor-wait');
  state.textContent = '';
  state.classList.add('hidden');
  try {
    const response = await fetch(window.bulkUserStatusUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ user_ids: payload.userIds, status: payload.status })
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'ذخیره وضعیت کاربران انجام نشد.');

    payload.userIds.forEach(function(userId) { window.syncUserStatusControls(userId, data.user_status); });
    window.closeBulkUserStatusDialog();
    window.clearUserSelection();
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
    else alert(data.message);
  } catch (error) {
    state.textContent = error.message || 'ذخیره وضعیت کاربران انجام نشد.';
    state.classList.remove('hidden');
  } finally {
    submit.disabled = false;
    submit.classList.remove('opacity-60', 'cursor-wait');
  }
}

window.filterTable = function(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll('#usersTable tbody tr').forEach(row => {
    row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
  if (typeof window.updateUserBulkSelection === 'function') window.updateUserBulkSelection();
}

window.openUserModal = function(fullName, generatedImages, userId, currentPlanId, joinedAt) {
  document.getElementById('modalUserName').innerText = fullName;
  const container = document.getElementById('modalContent');
  const safeUserId = Number(userId);
  const selectedPlan = currentPlanId ? String(currentPlanId) : '';
  let contentHtml = '';

  contentHtml += `
    <section class="mb-4 grid grid-cols-2 gap-2">
      <div class="p-2.5 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-center">
        <div class="text-[10px] text-[var(--text-soft)]">تاریخ عضویت</div>
        <div class="mt-1 text-[10.5px] font-mono text-[var(--text-main)]">${window.escapeUserPlanHtml(joinedAt || '—')}</div>
      </div>
      <div class="p-2.5 rounded-xl border bg-[var(--page-bg)] border-[var(--border)] text-center">
        <div class="text-[10px] text-[var(--text-soft)]">تصاویر خلق‌شده</div>
        <div class="mt-1 text-[14px] font-extrabold text-[var(--info)]">${Array.isArray(generatedImages) ? generatedImages.length.toLocaleString('fa-IR') : '۰'}</div>
      </div>
    </section>`;

  if (window.canManageUserPlans && Number.isFinite(safeUserId)) {
    contentHtml += `
      <section class="mb-4 p-3 rounded-xl border bg-[var(--primary-l)] border-[var(--primary-m)]">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <div>
            <div class="text-[12px] font-extrabold text-[var(--text-h)]"><i class="fa-solid fa-arrow-right-arrow-left ml-1 text-[var(--primary)]"></i>تغییر پلن کاربر</div>
            <div class="mt-1 text-[10px] text-[var(--text-soft)]">این تغییر موجودی اعتبار کاربر را دست نمی‌زند.</div>
          </div>
          <select data-user-plan-id="${safeUserId}" data-current="${selectedPlan}" onchange="window.changeUserPlan(${safeUserId}, this)" class="min-w-[190px] p-2 rounded-lg border outline-none bg-[var(--card-bg)] border-[var(--border)] text-[var(--text-main)] focus:border-[var(--primary)] cursor-pointer text-[11px]" aria-label="تغییر پلن کاربر">
            ${window.userPlanOptionsMarkup(selectedPlan)}
          </select>
        </div>
      </section>`;
  }

  if (!generatedImages || generatedImages.length === 0) {
    contentHtml += `<div class="text-center p-6 text-[12.5px] text-[var(--text-soft)]">این کاربر هنوز از هیچ محصولی برای تولید تصویر استفاده نکرده است.</div>`;
  } else {
    let listHtml = `<div class="mb-2 text-[11px] font-bold text-[var(--text-soft)]">محصولات استفاده‌شده</div><div class="divide-y divide-[var(--border)]">`;
    generatedImages.forEach((img, index) => {
      const productName = window.escapeUserPlanHtml(img.product ? (img.product.name_fa || img.product.title || img.product.name) : 'محصول نامشخص / حذف شده');
      const category = window.escapeUserPlanHtml(img.product ? (img.product.category || '—') : '—');
      const dateStr = window.escapeUserPlanHtml(img.jalali_created_at || '—');
      const imageUrl = window.escapeUserPlanHtml(img.admin_image_url || '');
      const orderNumber = window.escapeUserPlanHtml(img.order ? (img.order.order_number || '—') : (img.order_id || '—'));
      
      listHtml += `
        <div class="py-3 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            ${imageUrl ? `<a href="${imageUrl}" target="_blank" rel="noopener" class="w-14 h-14 rounded-lg overflow-hidden border border-[var(--border)] bg-[var(--page-bg)] flex-shrink-0" title="بازکردن تصویر اصلی"><img src="${imageUrl}" alt="خروجی ساخته‌شده" class="w-full h-full object-cover" loading="lazy"></a>` : `<div class="w-14 h-14 rounded-lg bg-[var(--info-l)] border border-[var(--info-m)] text-[var(--info)] flex items-center justify-center flex-shrink-0"><i class="fa-regular fa-image"></i></div>`}
            <div>
              <div class="text-[12.5px] font-bold text-[var(--text-h)]"><span class="text-[var(--info)] ml-1">${index + 1}.</span>${productName}</div>
              <div class="text-[10px] text-[var(--text-soft)] mt-0.5">دسته‌بندی: ${category} · سفارش: <span dir="ltr">${orderNumber}</span></div>
            </div>
          </div>
          <div class="text-left">
            <span class="inline-block text-[10.5px] font-mono text-[var(--text-soft)] bg-[var(--page-bg)] px-2 py-0.5 rounded border border-[var(--border)]">${dateStr}</span>
          </div>
        </div>
      `;
    });
    listHtml += `</div>`;
    contentHtml += listHtml;
  }
  container.innerHTML = contentHtml;

  const modal = document.getElementById('productModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => {
    modal.classList.remove('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-95');
    modal.querySelector('.transform').classList.add('scale-100');
  }, 20);
}

window.closeUserModal = function() {
  const modal = document.getElementById('productModal');
  modal.classList.add('opacity-0');
  modal.querySelector('.transform').classList.remove('scale-100');
  modal.querySelector('.transform').classList.add('scale-95');
  setTimeout(() => {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 200);
}

window.changeUserStatus = async function(userId, select) {
  const previous = select.dataset.current;
  select.disabled = true;
  try {
    const response = await fetch('/admin/users/' + userId + '/status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ status: select.value })
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'ذخیره وضعیت کاربر انجام نشد.');

    window.syncUserStatusControls(userId, data.user_status);
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
    else alert(data.message);
  } catch (error) {
    window.syncUserStatusControls(userId, previous);
    if (typeof window.showAdminToast === 'function') window.showAdminToast(error.message, 'error');
    else alert(error.message);
  } finally {
    select.disabled = false;
  }
}

window.changeCustomerSegment = function(userId, select) {
  const previous = select.dataset.current;
  fetch(`/admin/users/${userId}/customer-segment`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ customer_segment: select.value })
  }).then(async response => {
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'ذخیره گروه انجام نشد.');
    select.dataset.current = select.value;
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
  }).catch(error => {
    select.value = previous;
    if (typeof window.showAdminToast === 'function') window.showAdminToast(error.message, 'error');
    else alert(error.message);
  });
}

window.changeUserPlan = function(userId, select) {
  const previous = select.dataset.current;
  select.disabled = true;
  fetch('/admin/users/' + userId + '/plan', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ plan_id: select.value || null })
  }).then(async response => {
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'ذخیره پلن انجام نشد.');
    window.syncUserPlanControls(userId, data.plan_id);
    if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
    else alert(data.message);
  }).catch(error => {
    window.syncUserPlanControls(userId, previous);
    if (typeof window.showAdminToast === 'function') window.showAdminToast(error.message, 'error');
    else alert(error.message);
  }).finally(() => {
    select.disabled = false;
  });
}

@php($autoOpenUser = $autoOpenUserId ? $users->firstWhere('id', $autoOpenUserId) : null)
@if($autoOpenUser)
const openRequestedUser = function() {
  window.openUserModal(
    @js(trim(($autoOpenUser->name ?? 'کاربر').' '.($autoOpenUser->last_name ?? ''))),
    @js($autoOpenUser->generatedImages),
    {{ $autoOpenUser->id }},
    @js($autoOpenUser->hasFreePlan() ? '' : $autoOpenUser->plan_id),
    @js(\App\Support\Jalali::formatNumeric($autoOpenUser->registered_at ?? $autoOpenUser->created_at))
  );
};
if (document.readyState === 'complete') setTimeout(openRequestedUser, 0);
else window.addEventListener('load', openRequestedUser, { once: true });
@endif
</script>
@endsection
