<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ورود ادمین — وطن استودیو</title>
@include('partials.site-icons')
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  /* ═══ لایوت دو ستونه صفحه ورود ادمین — CSS خام، مستقل از کامپایل Tailwind ═══ */
  .auth-shell {
    width: 100%;
    max-width: 420px;
  }
  .auth-form-col {
    width: 100%;
  }
  .auth-brand-col {
    display: none;
  }
  @media (min-width: 768px) {
    .auth-shell {
      width: 840px;
      max-width: 840px;
      display: grid;
      grid-template-columns: 420px 420px;
      align-items: stretch;
    }
    .auth-form-col {
      width: 420px;
      height: 684px;
      min-width: 0;
    }
    .auth-brand-col {
      display: flex;
      width: 420px;
      height: 684px;
    }
  }
</style>
</head>
<body class="m-0 min-h-screen min-h-[100dvh] flex items-center justify-center p-6 max-[480px]:p-4 bg-[#0c0c10] text-white font-[IRANSansXFaNum,_sans-serif] overflow-x-hidden">

{{-- هاله درخشان سبز پس‌زمینه --}}
<div class="fixed inset-0 -z-10 bg-[#0c0c10] overflow-hidden before:content-[''] before:absolute before:rounded-full before:blur-[90px] before:opacity-[0.14] before:w-[420px] before:h-[420px] before:bg-[#0BBF53] before:-top-[120px] before:-right-[100px] after:content-[''] after:absolute after:rounded-full after:blur-[90px] after:opacity-[0.14] after:w-[380px] after:h-[380px] after:bg-[#0BBF53] after:-bottom-[140px] after:-left-[100px]"></div>

{{-- باکس اصلی فرم --}}
<div class="auth-shell bg-[#111116] border border-[#222230] rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.4)]">

  {{-- ستون فرم (سمت راست) --}}
  <div class="auth-form-col overflow-y-auto py-8 px-7 max-[480px]:py-[18px] max-[480px]:px-4">

    <div class="flex flex-col items-center gap-2 mb-6 max-[480px]:mb-[14px] max-[480px]:gap-[6px]">
      <img src="{{ asset('assets/img/icon vatan.svg') }}" alt="وطن استودیو" class="h-14 w-auto max-[480px]:h-11">
      <div class="text-base font-extrabold text-white tracking-[-0.3px]">وطن استودیو</div>
    </div>

    <div class="text-[17px] font-extrabold text-white mb-1 text-center">ورود به پنل مدیریت</div>
    <div class="text-xs text-[#4d7a56] text-center mb-[22px]">اطلاعات ادمین خود را وارد کنید</div>

    @if($errors->any())
      <div class="flex items-start gap-2 rounded-[10px] p-3 mb-4" style="background-color: rgba(240,92,92,0.1); border: 1px solid rgba(240,92,92,0.25);">
        <i class="fa-solid fa-triangle-exclamation" style="color:#f05c5c; font-size:13px; margin-top:2px;"></i>
        <div class="text-[11px] text-[#f05c5c] leading-relaxed">{{ $errors->first() }}</div>
      </div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST">
      @csrf

      <div class="flex flex-col gap-[6px] mb-4 max-[480px]:mb-[10px]">
        <label for="email" class="text-[11px] font-semibold text-[#a8c4a8]">ایمیل</label>
        <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#0BBF53]">
          <i class="fa-solid fa-envelope" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px; pointer-events:none;"></i>
          <input id="email" name="email" type="email" autocomplete="email" required autofocus
            value="{{ old('email') }}"
            class="w-full min-w-0 bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]"
            style="padding-right:40px; padding-left:14px; text-align:left; direction:ltr;"
            placeholder="admin@example.com">
        </div>
      </div>

      <div class="flex flex-col gap-[6px] mb-3 max-[480px]:mb-[10px]">
        <label for="password" class="text-[11px] font-semibold text-[#a8c4a8]">رمز عبور</label>
        <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#0BBF53]">
          <i class="fa-solid fa-lock" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px; pointer-events:none;"></i>
          <input id="password" name="password" type="password" autocomplete="current-password" required
            class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]"
            style="padding-right:40px; padding-left:40px;"
            placeholder="••••••••">
          <i class="fa-solid fa-eye-slash cursor-pointer transition-colors hover:text-white" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px;" id="toggle-admin-password" onclick="toggleAdminPasswordVisibility()"></i>
        </div>
      </div>

      <div class="flex items-center gap-2 mb-[22px] max-[480px]:mb-4">
        <input id="remember" name="remember" type="checkbox" class="cursor-pointer" style="width:16px; height:16px; accent-color:#0BBF53;">
        <label for="remember" class="text-[11px] text-[#a8c4a8] select-none cursor-pointer">مرا به خاطر بسپار</label>
      </div>

      <button type="submit" class="flex w-full py-3 border-0 rounded-[10px] bg-[#0BBF53] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#09a447] active:scale-[0.99]">
        <span>ورود به پنل</span><i class="fa-solid fa-arrow-left"></i>
      </button>
    </form>

  </div>

  {{-- ستون برندینگ / لوگو (سمت چپ، فقط دسکتاپ) --}}
  <div class="auth-brand-col relative flex-col items-center justify-center bg-[#0a0a0c] p-10 overflow-hidden border-r border-[#222230]">
    <div class="absolute w-[280px] h-[280px] rounded-full bg-[#0BBF53] opacity-10 blur-[80px]"></div>
    <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن استودیو" class="relative z-[1] w-20 h-20 object-contain mb-5">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="relative z-[1] w-[140px] object-contain mb-4">
    <div class="relative z-[1] text-xs text-[#4d7a56] text-center max-w-[220px] leading-[1.8]">پنل مدیریت وطن استودیو</div>
  </div>

</div>

<script>
function toggleAdminPasswordVisibility() {
  const input = document.getElementById('password');
  const icon = document.getElementById('toggle-admin-password');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  }
}
</script>

</body>
</html>
