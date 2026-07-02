@extends('layouts.admin')
@section('title', 'لیست پلن‌های اشتراک — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    {{-- هدر استیکی منطبق با ساختار ادمین --}}
    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">پلن‌های اشتراک</span>
      </div>
      <div class="flex-1"></div>
      <a href="{{ route('admin.plans.create') }}"
         class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[#a07af5] text-[#0c0c10] hover:bg-[#8f68e0] transition-colors no-underline">
        <i class="fa-solid fa-plus text-[11px]"></i> ثبت پلن جدید
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
          <div class="text-xl font-extrabold tracking-tight mb-1">مدیریت پلن‌های اشتراک و فروش</div>
          <div class="text-xs text-[#4d7a56]">پیکربندی قیمت‌ها، دوره‌های زمانی، تخصیص توکن و ویژگی‌های سطوح دسترسی کاربران</div>
        </div>
      </div>

      {{-- جدول نمایش داده‌ها --}}
      <div class="bg-[#111116] border border-[#222230] rounded-xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-right text-xs">
            <thead>
              <tr class="bg-[#16161c] border-b border-[#222230] text-[#a8c4a8] font-bold h-11 select-none">
                <th class="p-4 w-12 text-center">#</th>
                <th class="p-4">نام پلن</th>
                <th class="p-4">قیمت ماهانه</th>
                <th class="p-4">قیمت سالانه</th>
                <th class="p-4 text-center">توکن تخصیص یافته</th>
                <th class="p-4 text-center">وضعیت نمایش</th>
                <th class="p-4 w-36 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#222230]/50 text-[#a8c4a8]">
              @forelse($plans as $index => $plan)
                <tr class="hover:bg-[#16161c]/40 transition-colors h-14">
                  <td class="p-4 text-center text-gray-600 font-mono">{{ $index + 1 }}</td>
                  <td class="p-4">
                    <div class="flex flex-col gap-0.5">
                      <span class="font-bold text-white text-sm">{{ $plan->name }}</span>
                      @if($plan->is_popular)
                        <span class="text-[9px] text-[#a07af5] font-medium">★ محبوب‌ترین پلن</span>
                      @endif
                    </div>
                  </td>
                  <td class="p-4 font-mono text-gray-300">
                    {{ $plan->price_monthly > 0 ? number_format($plan->price_monthly) . ' تومان' : 'رایگان' }}
                  </td>
                  <td class="p-4 font-mono text-gray-300">
                    {{ $plan->price_yearly > 0 ? number_format($plan->price_yearly) . ' تومان' : '—' }}
                  </td>
                  
                  <td class="p-4 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-bold text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">
                      {{ number_format($plan->tokens_allocated) }}
                    </span>
                  </td>

                  <td class="p-4 text-center">
                    <div class="flex items-center justify-center">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold text-[10px] {{ $plan->is_active ? 'bg-[#4d7a56]/10 text-emerald-400 border border-[#4d7a56]/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                        {{ $plan->is_active ? 'فعال' : 'پیش‌نویس' }}
                      </span>
                    </div>
                  </td>

                  <td class="p-4">
                    <div class="flex items-center justify-center gap-1.5">
                      
                      {{-- دکمه مودال جزئیات پلن --}}
                      <button type="button" 
                              class="btn-plan-details w-7 h-7 bg-[#16161c] hover:bg-[#222230] border border-[#222230] hover:border-[#2e2e3e] rounded-md text-gray-400 hover:text-white transition-all flex items-center justify-center" 
                              title="مشاهده جزئیات"
                              data-name="{{ $plan->name }}"
                              data-monthly="{{ $plan->price_monthly > 0 ? number_format($plan->price_monthly) . ' تومان' : 'رایگان' }}"
                              data-yearly="{{ $plan->price_yearly > 0 ? number_format($plan->price_yearly) . ' تومان' : '—' }}"
                              data-tokens="{{ number_format($plan->tokens_allocated) }}"
                              data-status="{{ $plan->is_active ? 'فعال' : 'پیش‌نویس' }}"
                              data-popular="{{ $plan->is_popular ? 'بله' : 'خیر' }}"
                              data-features="@json(is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []))"
                              data-date="{{ $plan->created_at ? $plan->created_at->format('Y-m-d') : '—' }}">
                        <i class="fa-regular fa-eye text-[11px]"></i>
                      </button>

                      <a href="{{ route('admin.plans.edit', $plan->id) }}" 
                         class="w-7 h-7 bg-[#16161c] hover:bg-amber-500/10 border border-[#222230] hover:border-amber-500/30 rounded-md text-gray-400 hover:text-amber-400 transition-all flex items-center justify-center" title="ویرایش پلن">
                        <i class="fa-regular fa-pen-to-square text-[11px]"></i>
                      </a>

                      <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این پلن اشتراک اطمینان دارید؟ کاربران متصل متضرر خواهند شد.');" class="inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-7 h-7 bg-[#16161c] hover:bg-rose-500/10 border border-[#222230] hover:border-rose-500/30 rounded-md text-gray-400 hover:text-rose-400 transition-all flex items-center justify-center" title="حذف پلن">
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
                      <i class="fa-solid fa-receipt text-xl text-gray-600"></i>
                      <span>هیچ پلن اشتراکی تاکنون در سیستم تعریف نشده است.</span>
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

{{-- مدال نمایشی هوشمند جزئیات کامل یک پلن اشتراک --}}
<div id="details-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" dir="rtl">
  <div id="modal-content" class="bg-[#111116] border border-[#222230] rounded-xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh] scale-95 transition-transform duration-300">
    
    <div class="bg-[#16161c] border-b border-[#222230] px-5 h-12 flex items-center justify-between shrink-0">
      <div class="text-xs font-bold text-white flex items-center gap-2">
        <i class="fa-solid fa-gem text-[#a07af5]"></i>
        <span id="m-name">نام پلن</span>
      </div>
      <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-white transition-colors text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="p-5 flex-1 overflow-y-auto space-y-4 text-xs">
      <div class="grid grid-cols-2 gap-3 bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3">
        <div>
          <span class="text-gray-500 block mb-1">توکن‌های تخصیص یافته:</span>
          <span id="m-tokens" class="font-bold text-emerald-400 font-mono"></span>
        </div>
        <div>
          <span class="text-gray-500 block mb-1">تاریخ ایجاد پلن:</span>
          <span id="m-date" class="font-mono text-gray-300"></span>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3">
          <span class="text-gray-500 block mb-1">هزینه اشتراک ماهانه:</span>
          <span id="m-monthly" class="font-bold text-white font-mono"></span>
        </div>
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3">
          <span class="text-gray-500 block mb-1">هزینه اشتراک سالانه:</span>
          <span id="m-yearly" class="font-bold text-white font-mono"></span>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3 flex justify-between items-center">
          <span class="text-gray-500">وضعیت در فرانت:</span>
          <span id="m-status" class="font-bold text-white"></span>
        </div>
        <div class="bg-[#16161c]/40 border border-[#222230]/50 rounded-lg p-3 flex justify-between items-center">
          <span class="text-gray-500">برچسب محبوب‌ترین:</span>
          <span id="m-popular" class="font-bold text-white"></span>
        </div>
      </div>

      <div class="border-t border-[#222230] pt-3">
        <span class="text-gray-500 block mb-2 font-semibold"><i class="fa-solid fa-list-check text-[#a07af5] ml-1"></i> لیست ویژگی‌ها و دسترسی‌ها (Features):</span>
        <div id="m-features-box" class="space-y-1.5 max-h-36 overflow-y-auto pr-1"></div>
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
document.addEventListener('DOMContentLoaded', function () {
  // مدیریت اکشن کلیک روی دکمه چشمی (نمایش جزئیات)
  document.querySelectorAll('.btn-plan-details').forEach(button => {
    button.addEventListener('click', function() {
      const data = {
        name: this.getAttribute('data-name'),
        monthly: this.getAttribute('data-monthly'),
        yearly: this.getAttribute('data-yearly'),
        tokens: this.getAttribute('data-tokens'),
        is_active: this.getAttribute('data-status'),
        is_popular: this.getAttribute('data-popular'),
        created_at: this.getAttribute('data-date'),
        features: JSON.parse(this.getAttribute('data-features') || '[]')
      };
      openDetailsModal(data);
    });
  });

  // کلیک روی محدوده تاریک بیرون پنجره جهت بستن سریع مدال
  const modal = document.getElementById('details-modal');
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeDetailsModal();
    }
  });
});

function openDetailsModal(data) {
  document.getElementById('m-name').textContent = data.name;
  document.getElementById('m-tokens').textContent = data.tokens + ' توکن';
  document.getElementById('m-date').textContent = data.created_at;
  document.getElementById('m-monthly').textContent = data.monthly;
  document.getElementById('m-yearly').textContent = data.yearly;
  document.getElementById('m-status').textContent = data.is_active;
  document.getElementById('m-popular').textContent = data.is_popular;

  const featuresBox = document.getElementById('m-features-box');
  featuresBox.innerHTML = '';
  
  if (data.features && data.features.length > 0) {
    data.features.forEach((feature, idx) => {
      if(feature) {
        const item = document.createElement('div');
        item.className = 'bg-[#0c0c10] border border-[#222230] rounded p-2 text-[11px] text-gray-300 flex items-center gap-2';
        item.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-400 text-[10px]"></i> <span class="flex-1">${feature}</span>`;
        featuresBox.appendChild(item);
      }
    });
  } else {
    featuresBox.innerHTML = `<div class="text-gray-600 italic text-[11px]">هیچ ویژگی خاصی برای این پلن ثبت نشده است.</div>`;
  }

  const modal = document.getElementById('details-modal');
  const content = document.getElementById('modal-content');
  
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  
  setTimeout(() => {
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');
  }, 10);
  
  document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
  const modal = document.getElementById('details-modal');
  const content = document.getElementById('modal-content');
  
  modal.classList.add('opacity-0');
  content.classList.add('scale-95');
  
  setTimeout(() => {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 300);
  
  document.body.style.overflow = '';
}
</script>
@endsection