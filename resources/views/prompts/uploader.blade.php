{{-- resources/views/prompts/uploader.blade.php --}}
<div class="bg-white/[0.02] border border-white/[0.05] rounded-xl p-4 space-y-4">
    <div class="flex items-center justify-between">
        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
            <i class="fa-regular fa-folder text-[10px]"></i> تنظیمات پرامپت
        </span>
        <span class="text-[8px] font-bold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded uppercase">ویرایش اختصاصی</span>
    </div>

    {{-- باکس آپلود عکس --}}
    <label class="block border border-dashed border-white/10 hover:border-indigo-500/50 rounded-xl p-6 text-center cursor-pointer transition-all bg-[#161619] hover:bg-indigo-500/[0.02]">
        <input type="file" name="user_image" id="userImageInput" accept="image/*" class="hidden" onchange="previewImage(this)">
        
        {{-- بخش جایگاه اولیه آپلود --}}
        <div id="uploadPlaceholder" class="space-y-2.5 py-2">
            <div class="w-10 h-10 mx-auto rounded-full bg-white/[0.02] flex items-center justify-center border border-white/5 text-gray-400">
                <i class="fa-solid fa-cloud-arrow-up text-base"></i>
            </div>
            <div class="space-y-1">
                <p class="text-[11px] font-bold text-gray-300">تصویر پایه خود را انتخاب یا رها کنید</p>
                <p class="text-[9px] text-gray-500">مبنای استایل و تغییرات هوش مصنوعی</p>
            </div>
        </div>

        {{-- بخش پیش‌نمایش عکس انتخاب شده --}}
        <div id="imagePreviewContainer" class="hidden relative group animate-fade-in">
            <img id="imagePreview" src="#" alt="پیش‌نمایش" class="mx-auto max-h-24 rounded-lg border border-white/10 shadow-lg object-cover">
            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 rounded-lg flex items-center justify-center transition-opacity duration-200 text-[10px] font-bold text-white">
                <i class="fa-solid fa-rotate-right ml-1"></i> تعویض عکس
            </div>
        </div>
    </label>

    {{-- تکست‌اریا دستورات متنی --}}
    <textarea name="additional_prompt" placeholder="تغییرات یا دستورات دلخواه خود را بنویسید (مثلاً: جزئیات سینمایی، بارانی)..." class="w-full bg-[#161619] border border-white/[0.05] rounded-lg p-2.5 text-[11px] text-white placeholder-gray-600 focus:outline-none focus:border-indigo-500/30 h-14 resize-none leading-relaxed custom-scrollbar"></textarea>
</div>