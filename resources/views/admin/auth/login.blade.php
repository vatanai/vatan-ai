<!DOCTYPE html>
<html dir="rtl" lang="fa" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود ادمین — وطن استودیو</title>

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0c0c10] text-white min-h-screen flex items-center justify-center relative overflow-hidden font-sans antialiased selection:bg-[#a07af5]/30">

    <div class="absolute top-[-10%] left-[-10%] w-[450px] h-[450px] bg-[#a07af5]/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[450px] h-[450px] bg-[#0BBF53]/5 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-[400px] p-5 relative z-10">
        
        <div class="text-center mb-6 select-none">
            <div class="w-14 h-14 rounded-2xl bg-[#0BBF53] flex items-center justify-center text-2xl font-black text-white mx-auto shadow-lg shadow-[#0BBF53]/20 transition-transform duration-300 hover:scale-105">
                و
            </div>
            <h2 class="mt-4 text-center text-lg font-extrabold tracking-tight text-white">
                پنل مدیریت
            </h2>
            <p class="mt-1 text-center text-[10px] font-mono text-[#4d7a56] uppercase tracking-wider">
                AIPIX Admin Panel
            </p>
        </div>

        <div class="bg-[#111116] border border-[#222230]/80 p-6 sm:p-8 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.6)] backdrop-blur-md">
            
            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-3 mb-4 flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-[11px] text-rose-400 font-medium leading-relaxed">
                        {{ $errors->first() }}
                    </div>
                </div>
            @endif

            <form class="space-y-4" action="{{ route('admin.login.submit') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-[#a8c4a8] mb-1.5 pr-0.5">
                        ایمیل
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required autofocus
                        value="{{ old('email') }}"
                        class="block w-full px-4 py-2.5 bg-[#0c0c10] border border-[#222230] rounded-xl text-xs text-white placeholder-gray-600 outline-none transition-all duration-200 focus:border-[#a07af5] focus:ring-4 focus:ring-[#a07af5]/5 ltr text-left font-mono"
                        placeholder="admin@example.com">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-[#a8c4a8] mb-1.5 pr-0.5">
                        رمز عبور
                    </label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="block w-full px-4 py-2.5 bg-[#0c0c10] border border-[#222230] rounded-xl text-xs text-white placeholder-gray-600 outline-none transition-all duration-200 focus:border-[#a07af5] focus:ring-4 focus:ring-[#a07af5]/5 ltr text-left tracking-widest placeholder:tracking-normal"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between pt-0.5">
                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox" 
                            class="h-4 w-4 bg-[#0c0c10] border-[#222230] rounded text-[#a07af5] focus:ring-offset-0 focus:ring-[#a07af5]/30 cursor-pointer">
                        <label for="remember" class="text-[11px] text-[#a8c4a8]/70 select-none cursor-pointer transition-colors hover:text-white">
                            مرا به خاطر بسپار
                        </label>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                        class="w-full h-10 flex justify-center items-center gap-2 px-4 rounded-xl text-xs font-bold bg-[#a07af5] text-black shadow-lg shadow-[#a07af5]/10 hover:bg-[#8f68e0] active:scale-[0.98] transition-all duration-150 cursor-pointer">
                        ورود به پنل
                    </button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>