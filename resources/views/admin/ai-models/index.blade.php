@extends('layouts.admin')
@section('title', 'مدیریت مدل‌های هوش مصنوعی — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px]" id="content">
      
      {{-- مودال اختصاصی و هوشمند نمایش پیام موفقیت عملیات --}}
      @if(session('success'))
        <div id="success-modal" class="fixed inset-0 flex items-center justify-center z-50 px-4 animate-fade-in">
          {{-- پس‌زمینه تاریک و شیشه‌ای بلورین --}}
          <div class="absolute inset-0 bg-[#000000]/60 backdrop-blur-sm"></div>
          
          {{-- باکس اصلی مودال پاپ‌آپ --}}
          <div class="bg-[#16161c] border border-emerald-500/30 rounded-2xl p-6 max-w-sm w-full relative z-10 shadow-2xl shadow-emerald-500/5 text-center transform scale-95 animate-scale-up">
            <div class="w-14 h-14 bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-emerald-400 text-2xl shadow-lg shadow-emerald-500/10">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3 class="text-sm font-extrabold text-white mb-2">عملیات موفقیت‌آمیز</h3>
            <p class="text-xs text-gray-400 leading-relaxed mb-5">{{ session('success') }}</p>
            
            <button onclick="closeSuccessModal()" class="w-full h-9 rounded-xl text-xs font-bold bg-emerald-500 text-[#0c0c10] shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition-all cursor-pointer">
              متوجه شدم
            </button>
          </div>
        </div>
      @endif

      <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
          <div class="text-xl font-extrabold tracking-tight mb-1">مدیریت گیت‌وی مدل‌های هوش مصنوعی</div>
          <div class="text-xs text-gray-500">لیست و تنظیمات زنده مدل‌های فعال پلتفرم متصل به OpenRouter</div>
        </div>
        <a href="{{ route('admin.ai-models.create') }}" class="inline-flex items-center gap-1.5 px-4 h-[38px] rounded-lg text-xs font-bold bg-[#a07af5] text-[#0c0c10] shadow-lg shadow-[#a07af5]/10 hover:bg-[#8f68e0] transition-all no-underline">
          <i class="fa-solid fa-plus text-[11px]"></i>
          ثبت مدل جدید
        </a>
      </div>

      <div class="bg-[#16161c] border border-[#222230] rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-right text-xs">
            <thead>
              <tr class="border-b border-[#222230] bg-[#111116] text-[#a8c4a8] font-bold">
                <th class="p-3.5">تصویر و نام مدل</th>
                <th class="p-3.5">کمپانی سازنده</th>
                <th class="p-3.5">نوع خروجی</th>
                <th class="p-3.5">ورودی تصویر</th>
                <th class="p-3.5">هزینه هر جنریت (توکن)</th>
                <th class="p-3.5">ابعاد پیش‌فرض</th>
                <th class="p-3.5">وضعیت</th>
                <th class="p-3.5 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#222230]/50">
              @forelse($models as $model)
                <tr class="hover:bg-[#1a1a24] transition-colors">
                  <td class="p-3.5 flex items-center gap-3">
                    <img src="{{ $model->image_url }}" class="w-9 h-9 rounded-lg object-cover border border-[#222230] bg-[#0c0c10]">
                    <div>
                      <div class="font-bold text-white mb-0.5">{{ $model->name }}</div>
                      <div class="text-[10px] text-gray-500 font-mono ltr text-right">{{ $model->openrouter_model_id }}</div>
                    </div>
                  </td>
                  <td class="p-3.5 text-gray-300 font-medium">{{ $model->provider_name }}</td>
                  <td class="p-3.5">
                    @if($model->output_modality == 'text')
                      <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 text-[10px] font-medium border border-blue-500/20">متن (text)</span>
                    @elseif($model->output_modality == 'image')
                      <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-400 text-[10px] font-medium border border-purple-500/20">عکس (image)</span>
                    @elseif($model->output_modality == 'video')
                      <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 text-[10px] font-medium border border-amber-500/20">ویدیو (video)</span>
                    @else
                      <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 text-[10px] font-medium border border-rose-500/20">صدا (audio)</span>
                    @endif
                  </td>
                  <td class="p-3.5">
                    {!! $model->supports_image_input 
                      ? '<span class="text-emerald-400 font-medium"><i class="fa-solid fa-circle-check ml-1"></i>دارد</span>' 
                      : '<span class="text-gray-500"><i class="fa-solid fa-circle-xmark ml-1"></i>خیر</span>' !!}
                  </td>
                  <td class="p-3.5 font-mono text-emerald-400 font-bold">{{ number_format($model->cost_per_generation) }}</td>
                  <td class="p-3.5 font-mono text-gray-400">{{ $model->default_width }} × {{ $model->default_height }}</td>
                  <td class="p-3.5">
                    @if($model->is_active)
                      <span class="inline-flex items-center gap-1 text-emerald-400 text-[11px] font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>فعال</span>
                    @else
                      <span class="inline-flex items-center gap-1 text-gray-500 text-[11px] font-medium"><span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>غیرفعال</span>
                    @endif
                  </td>
                  <td class="p-3.5 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <a href="{{ route('admin.ai-models.edit', $model->id) }}" class="w-7 h-7 rounded bg-[#222230] hover:bg-[#2e2e42] border border-[#2d2d3d] text-[#a8c4a8] hover:text-white transition-colors flex items-center justify-center no-underline">
                        <i class="fa-regular fa-pen-to-square text-[11px]"></i>
                      </a>
                      <form action="{{ route('admin.ai-models.destroy', $model->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این مدل هوش مصنوعی اطمینان دارید؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-7 h-7 rounded bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-400 transition-colors flex items-center justify-center cursor-pointer">
                          <i class="fa-regular fa-trash-can text-[11px]"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="p-10 text-center text-gray-500 font-medium">هیچ مدل هوش مصنوعی در پایگاه داده ثبت نشده است.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

{{-- استایل‌های انیمیشن پاپ‌آپ مودال --}}
<style>
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes scaleUp { from { transform: scale(0.92); opacity: 0; } to { transform: scale(1); opacity: 1; } }
  .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
  .animate-scale-up { animation: scaleUp 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
</style>

{{-- اسکریپت کنترلر مودال موفقیت --}}
<script>
  function closeSuccessModal() {
    const modal = document.getElementById('success-modal');
    if (modal) {
      modal.style.opacity = '0';
      modal.style.transition = 'opacity 0.2s ease-out';
      setTimeout(() => modal.remove(), 200);
    }
  }

  // بستن اتوماتیک مودال پس از ۴ ثانیه برای راحتی کار ادمین
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      closeSuccessModal();
    }, 4000);
  });
</script>
@endsection