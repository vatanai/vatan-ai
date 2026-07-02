@extends('layouts.admin')
@section('title', 'لیست مدل‌های هوش مصنوعی — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">مدل‌های هوش مصنوعی</span>
      </div>
      <div class="flex-1"></div>
      <a href="{{ route('admin.ai-models.create') }}"
         class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[#a07af5] text-[#0c0c10] hover:bg-[#8f68e0] transition-colors no-underline">
        <i class="fa-solid fa-plus text-[11px]"></i> ثبت مدل جدید
      </a>
    </header>

    <main class="p-6 flex-1">

      @if(session('success'))
        <div class="bg-[#4d7a56]/10 border border-[#4d7a56]/30 rounded-xl p-3.5 mb-6 text-xs text-[#a8c4a8] flex items-center gap-2">
          <i class="fa-regular fa-circle-check text-emerald-400"></i>
          {{ session('success') }}
        </div>
      @endif

      <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="text-xl font-extrabold tracking-tight mb-1">مدل‌های هوش مصنوعی (OpenRouter)</div>
          <div class="text-xs text-[#4d7a56]">مدیریت، فعال‌سازی و مانیتورینگ کور-مدل‌های متصل به سیستم</div>
        </div>
      </div>

      <div class="bg-[#111116] border border-[#222230] rounded-xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-right text-xs">
            <thead>
              <tr class="bg-[#16161c] border-b border-[#222230] text-[#a8c4a8] font-bold h-11 select-none">
                <th class="p-4 w-12 text-center">#</th>
                <th class="p-4">نام مدل</th>
                <th class="p-4">شناسه API (Model ID)</th>
                <th class="p-4">ارائه‌دهنده (Provider)</th>
                <th class="p-4 text-center">قابلیت Vision</th>
                <th class="p-4 text-center">وضعیت</th>
                <th class="p-4 w-36 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#222230]/50 text-[#a8c4a8]">
              @forelse($models as $index => $model)
                <tr class="hover:bg-[#16161c]/40 transition-colors h-14">
                  <td class="p-4 text-center text-gray-600 font-mono">{{ $index + 1 }}</td>
                  <td class="p-4 font-bold text-white text-sm">{{ $model->name }}</td>
                  <td class="p-4 font-mono text-[11px] text-gray-400 ltr text-left" dir="ltr">{{ $model->model_id }}</td>
                  <td class="p-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-[#222230] text-gray-300 font-medium text-[10px]">
                      {{ $model->provider }}
                    </span>
                  </td>
                  
                  <td class="p-4 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] {{ $model->supports_vision ? 'text-emerald-400 bg-emerald-500/10 border border-emerald-500/20' : 'text-gray-500 bg-gray-500/5' }}">
                      {{ $model->supports_vision ? 'دارد' : 'خیر' }}
                    </span>
                  </td>

                  <td class="p-4 text-center">
                    <div class="flex items-center justify-center">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold text-[10px] {{ $model->is_active ? 'bg-[#4d7a56]/10 text-emerald-400 border border-[#4d7a56]/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                        {{ $model->is_active ? 'فعال' : 'غیرفعال' }}
                      </span>
                    </div>
                  </td>

                  <td class="p-4">
                    <div class="flex items-center justify-center gap-1.5">
                      
                      <button type="button" 
                              onclick="openDetailsModal({{ json_encode([
                                'name' => $model->name,
                                'model_id' => $model->model_id,
                                'provider' => $model->provider,
                                'supports_vision' => $model->supports_vision ? 'دارد' : 'خیر',
                                'is_active' => $model->is_active ? 'فعال' : 'غیرفعال',
                                'fallbacks' => is_array($model->fallback_models) ? $model->fallback_models : (json_decode($model->fallback_models, true) ?? []),
                                'created_at' => $model->created_at ? $model->created_at->format('Y-m-d') : '—'
                              ]) }})"
                              class="w-7 h-7 bg-[#16161c] hover:bg-[#222230] border border-[#222230] hover:border-[#2e2e3e] rounded-md text-gray-400 hover:text-white transition-all flex items-center justify-center" title="مشاهده جزئیات">
                        <i class="fa-regular fa-eye text-[11px]"></i>
                      </button>

                      <a href="{{ route('admin.ai-models.edit', $model->id) }}" 
                         class="w-7 h-7 bg-[#16161c] hover:bg-amber-500/10 border border-[#222230] hover:border-amber-500/30 rounded-md text-gray-400 hover:text-amber-400 transition-all flex items-center justify-center" title="ویرایش مدل">
                        <i class="fa-regular fa-pen-to-square text-[11px]"></i>
                      </a>

                      <form action="{{ route('admin.ai-models.destroy', $model->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این مدل هوش مصنوعی اطمینان دارید؟');" class="inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-7 h-7 bg-[#16161c] hover:bg-rose-500/10 border border-[#222230] hover:border-rose-500/30 rounded-md text-gray-400 hover:text-rose-400 transition-all flex items-center justify-center" title="حذف مدل">
                          <i class="fa-regular fa-trash-can text-[11px]"></i>
                        </button>
                      </form>

                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="p-12 text-center text-gray-500 font-medium">
                    <div class="flex flex-col items-center gap-2">
                      <i class="fa-solid fa-cloud-bounce text-xl text-gray-600"></i>
                      <span>هیچ مدل هوش مصنوعی تاکنون در سیستم تعریف نشده است.</span>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<div id="details-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all animate-fade-in" dir="rtl">
  <div class="bg-[#111116] border border-[#222230] rounded-xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
    
    <div class="bg-[#16161c] border-b border-[#222230] px-5 h-12 flex items-center justify-between shrink-0">
      <div class="text-xs font-bold text-white flex items-center gap-2">
        <i class="fa-solid fa-circle-nodes text-[#a07af5]"></i>
        <span id="m-name">نام مدل</span>
      </div>
      <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-white transition-colors text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="p-5 flex-1 overflow-y-auto space-y-4 text-xs">
      <div class="grid grid-cols-2 gap-3 bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3">
        <div>
          <span class="text-gray-500 block mb-1">کمپانی ارائه‌دهنده:</span>
          <span id="m-provider" class="font-bold text-white"></span>
        </div>
        <div>
          <span class="text-gray-500 block mb-1">تاریخ ثبت:</span>
          <span id="m-date" class="font-mono text-gray-300"></span>
        </div>
      </div>

      <div>
        <span class="text-gray-500 block mb-1">شناسه مدل (Model ID):</span>
        <div id="m-model-id" class="bg-[#0c0c10] border border-[#222230] rounded-lg p-2.5 font-mono text-[11px] text-emerald-400 ltr text-left tracking-wide"></div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3 flex justify-between items-center">
          <span class="text-gray-500">پشتیبانی از Vision:</span>
          <span id="m-vision" class="font-bold text-white"></span>
        </div>
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3 flex justify-between items-center">
          <span class="text-gray-500">وضعیت سیستم:</span>
          <span id="m-status" class="font-bold text-white"></span>
        </div>
      </div>

      <div class="border-t border-[#222230] pt-3">
        <span class="text-gray-500 block mb-2 font-semibold"><i class="fa-solid fa-sliders text-[#a07af5] ml-1"></i> مدل‌های جایگزین (Fallback):</span>
        <div id="m-fallbacks-box" class="space-y-1.5 max-h-32 overflow-y-auto pr-1"></div>
      </div>
    </div>

    <div class="bg-[#16161c] border-t border-[#222230] px-4 h-12 flex items-center justify-end shrink-0">
      <button onclick="closeDetailsModal()" class="px-4 h-8 rounded-lg text-xs font-semibold bg-[#222230] text-gray-400 hover:text-white transition-colors">
        بستن پنجره
      </button>
    </div>
  </div>
</div>

<script>
function openDetailsModal(data) {
  document.getElementById('m-name').textContent = data.name;
  document.getElementById('m-provider').textContent = data.provider;
  document.getElementById('m-date').textContent = data.created_at;
  document.getElementById('m-model-id').textContent = data.model_id;
  document.getElementById('m-vision').textContent = data.supports_vision;
  document.getElementById('m-status').textContent = data.is_active;

  const fallbackBox = document.getElementById('m-fallbacks-box');
  fallbackBox.innerHTML = '';
  
  if (data.fallbacks && data.fallbacks.length > 0) {
    data.fallbacks.forEach((fb, idx) => {
      if(fb) {
        const item = document.createElement('div');
        item.className = 'bg-[#0c0c10] border border-[#222230] rounded p-2 font-mono text-[10px] text-gray-400 flex items-center gap-2 ltr text-left';
        item.innerHTML = `<span class="text-gray-600">${idx + 1}.</span> <span class="flex-1">${fb}</span>`;
        fallbackBox.appendChild(item);
      }
    });
  } else {
    fallbackBox.innerHTML = `<div class="text-gray-600 italic text-[11px]">هیچ مدل جایگزینی ثبت نشده است.</div>`;
  }

  const modal = document.getElementById('details-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
  const modal = document.getElementById('details-modal');
  modal.classList.remove('flex');
  modal.classList.add('hidden');
  document.body.style.overflow = '';
}
</script>
@endsection