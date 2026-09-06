{{-- resources/views/partials/profile-dropdown.blade.php --}}
@auth
<div id="vatan-profile-dropdown" class="profile-dropdown-menu absolute top-12 left-0 w-[220px] bg-[#121218] [.light_&]:bg-white border border-white/10 [.light_&]:border-black/10 rounded-[14px] shadow-2xl p-2 z-[400] origin-top select-none" style="display: none;">
  
  {{-- بخش نام کاربر و اطلاعات حساب --}}
  <div class="user-info-section p-2.5 flex flex-col gap-0.5 border-b border-white/5 [.light_&]:border-black/5 pb-3 mb-1.5">
    <span class="user-name text-[14px] font-bold text-white [.light_&]:text-black truncate">
      {{ auth()->user()->name ?? 'کاربر وطن AI' }}
    </span>
    <span class="user-email text-[11px] text-white/40 [.light_&]:text-black/45 truncate">
      {{ auth()->user()->email }}
    </span>
    @php
      $dropdownTierKey = auth()->user()->plan?->model_tier_key ?? 'free';
      $dropdownTier = \App\Services\ModelTierService::DEFINITIONS[$dropdownTierKey] ?? \App\Services\ModelTierService::DEFINITIONS['free'];
    @endphp
    <span class="user-email text-[11px] text-white/40 [.light_&]:text-black/45 truncate">پلن {{ auth()->user()->plan_display_name }} · {{ $dropdownTier['name'] }}</span>
  </div>

  {{-- بخش اعتبار‌ها — متصل شده به موجودی واقعی دیتابیس کاربر --}}
  <div class="dropdown-token-section p-2.5 flex flex-col gap-1">
    <span class="token-title text-[11px] text-white/40 [.light_&]:text-black/45">اعتبار‌های باقی‌مانده:</span>
    <span id="top-nav-tokens" class="token-value text-[13.5px] font-black text-[#cffe00]">
      {{ number_format(auth()->user()->effective_token_balance) }} اعتبار
    </span>
  </div>
  
  <hr class="border-0 h-px bg-white/5 [.light_&]:bg-black/5 my-1.5 mx-1">
  
  {{-- آیتم‌های منو --}}
  <a href="{{ route('app.profile') }}" class="dropdown-item flex items-center gap-2.5 p-2.5 text-white/70 [.light_&]:text-black/70 text-[13px] font-medium no-underline rounded-md transition-all duration-150 hover:text-white [.light_&]:hover:text-black hover:bg-white/5 [.light_&]:hover:bg-black/5">
    <i class="fa-solid fa-user w-4 h-4 text-center opacity-80 text-[14px]"></i>
    پروفایل
  </a>

  {{-- گزینه گالری من (جدید) --}}
  <a href="{{ route('profile.gallery') }}" class="dropdown-item flex items-center gap-2.5 p-2.5 text-white/70 [.light_&]:text-black/70 text-[13px] font-medium no-underline rounded-md transition-all duration-150 hover:text-white [.light_&]:hover:text-black hover:bg-white/5 [.light_&]:hover:bg-black/5">
    <i class="fa-solid fa-images w-4 h-4 text-center opacity-80 text-[14px]"></i>
    گالری من
  </a>
<a href="{{ route('pricing.index') }}" class="dropdown-item flex items-center gap-2.5 p-2.5 text-white/70 [.light_&]:text-black/70 text-[13px] font-medium no-underline rounded-md transition-all duration-150 hover:text-white [.light_&]:hover:text-black hover:bg-white/5 [.light_&]:hover:bg-black/5">
    <i class="fa-solid fa-gem w-4 h-4 text-center opacity-80 text-[14px]"></i>
    خرید یا ارتقای پلن
  </a>
  
  <a href="#" class="dropdown-item flex items-center gap-2.5 p-2.5 text-white/70 [.light_&]:text-black/70 text-[13px] font-medium no-underline rounded-md transition-all duration-150 hover:text-white [.light_&]:hover:text-black hover:bg-white/5 [.light_&]:hover:bg-black/5">
    <i class="fa-solid fa-gear w-4 h-4 text-center opacity-80 text-[14px]"></i>
    تنظیمات
  </a>
  
  <hr class="border-0 h-px bg-white/5 [.light_&]:bg-black/5 my-1.5 mx-1">
  
  {{-- دکمه خروج متصل به سیستم لاگ‌اوت لاراول --}}
  <button type="button" onclick="window.logoutFromCurrentPage(this)" class="dropdown-item w-full flex items-center gap-2.5 p-2.5 text-[#ff4a4a] text-[13px] font-medium no-underline rounded-md transition-all duration-150 hover:bg-[#ff4a4a]/10">
    <i class="fa-solid fa-right-from-bracket w-4 h-4 text-center text-[14px]"></i>
    خروج از حساب
  </button>

</div>
@endauth
