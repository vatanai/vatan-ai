@extends('layouts.admin')
@section('title', 'لیست محصولات — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">


  <div class="mr-64 flex-1 flex flex-col min-h-screen">

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">محصولات</span>
      </div>
      <div class="flex-1"></div>
      <a href="{{ route('admin.products.create') }}"
         class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-colors no-underline">
        <i class="fa-solid fa-plus text-[11px]"></i> ثبت محصول جدید
      </a>
    </header>

    <!-- Content -->
    <main class="p-6 flex-1">

      <div class="mb-5">
        <div class="text-xl font-extrabold tracking-tight mb-1">لیست محصولات</div>
        <div class="text-[13px] text-[#4d7a56]">مدیریت، ویرایش و پیکربندی تمام محصولات هوش مصنوعی پلتفرم</div>
      </div>

      <!-- ══ STAT CARDS ══ -->
      <div class="grid grid-cols-4 gap-3.5 mb-5">
        <div class="bg-[#16161c] border border-[#222230] rounded-2xl p-4 flex items-center gap-3.5">
          <div class="w-[42px] h-[42px] rounded-[10px] flex items-center justify-center text-[17px] flex-shrink-0 bg-[#a07af5]/10 text-[#a07af5]">
            <i class="fa-solid fa-box-open"></i>
          </div>
          <div>
            <div class="text-xl font-extrabold leading-tight">{{ $products->total() ?? 0 }}</div>
            <div class="text-[11.5px] text-[#4d7a56] mt-0.5">کل محصولات</div>
          </div>
        </div>
        <div class="bg-[#16161c] border border-[#222230] rounded-2xl p-4 flex items-center gap-3.5">
          <div class="w-[42px] h-[42px] rounded-[10px] flex items-center justify-center text-[17px] flex-shrink-0 bg-[#0BBF53]/10 text-[#0BBF53]">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div>
            <div class="text-xl font-extrabold leading-tight">{{ $activeCount ?? 0 }}</div>
            <div class="text-[11.5px] text-[#4d7a56] mt-0.5">فعال</div>
          </div>
        </div>
        <div class="bg-[#16161c] border border-[#222230] rounded-2xl p-4 flex items-center gap-3.5">
          <div class="w-[42px] h-[42px] rounded-[10px] flex items-center justify-center text-[17px] flex-shrink-0 bg-[#f5923a]/10 text-[#f5923a]">
            <i class="fa-solid fa-pen"></i>
          </div>
          <div>
            <div class="text-xl font-extrabold leading-tight">{{ $draftCount ?? 0 }}</div>
            <div class="text-[11.5px] text-[#4d7a56] mt-0.5">پیش‌نویس</div>
          </div>
        </div>
        <div class="bg-[#16161c] border border-[#222230] rounded-2xl p-4 flex items-center gap-3.5">
          <div class="w-[42px] h-[42px] rounded-[10px] flex items-center justify-center text-[17px] flex-shrink-0 bg-[#f05c5c]/10 text-[#f05c5c]">
            <i class="fa-solid fa-circle-xmark"></i>
          </div>
          <div>
            <div class="text-xl font-extrabold leading-tight">{{ $inactiveCount ?? 0 }}</div>
            <div class="text-[11.5px] text-[#4d7a56] mt-0.5">غیرفعال</div>
          </div>
        </div>
      </div>

      <!-- ══ TOOLBAR ══ -->
      <form method="GET" class="flex items-center gap-2.5 bg-[#16161c] border border-[#222230] rounded-2xl p-3.5 mb-4 flex-wrap">
        <div class="flex-1 min-w-[220px] relative">
          <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-[#4d7a56] text-xs"></i>
          <input type="text" name="search" placeholder="جستجو در نام، اسلاگ یا تگ..." value="{{ request('search') }}"
                 class="w-full bg-[#111116] border border-[#222230] rounded-lg py-2.5 pr-9 pl-3 text-[13px] text-white outline-none focus:border-[#a07af5] transition-colors" dir="rtl">
        </div>

        <select name="category" onchange="this.form.submit()"
                class="bg-[#111116] border border-[#222230] rounded-lg py-2.5 px-3 text-[12.5px] text-[#a8c4a8] outline-none cursor-pointer min-w-[130px] focus:border-[#a07af5]">
          <option value="">همه دسته‌بندی‌ها</option>
          @foreach(['PEOPLE','BUSINESS','EVENTS','FAMILY','KIDS','PETS','ENTERTAINMENT','PRODUCTS','AVATARS','VIDEOS'] as $cat)
            <option value="{{ $cat }}" {{ request('category')==$cat?'selected':'' }}>{{ $cat }}</option>
          @endforeach
        </select>

        <select name="status" onchange="this.form.submit()"
                class="bg-[#111116] border border-[#222230] rounded-lg py-2.5 px-3 text-[12.5px] text-[#a8c4a8] outline-none cursor-pointer min-w-[130px] focus:border-[#a07af5]">
          <option value="">همه وضعیت‌ها</option>
          <option value="active" {{ request('status')=='active'?'selected':'' }}>فعال</option>
          <option value="draft" {{ request('status')=='draft'?'selected':'' }}>پیش‌نویس</option>
          <option value="inactive" {{ request('status')=='inactive'?'selected':'' }}>غیرفعال</option>
        </select>

        <button type="submit"
                class="inline-flex items-center gap-1.5 px-3.5 h-[38px] rounded-lg text-xs font-semibold bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-colors">
          <i class="fa-solid fa-filter text-[11px]"></i> اعمال فیلتر
        </button>
      </form>

      <!-- ══ TABLE ══ -->
      <div class="bg-[#16161c] border border-[#222230] rounded-2xl overflow-hidden">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-[#111116] border-b border-[#222230]">
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">محصول</th>
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">دسته‌بندی</th>
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">قیمت</th>
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">ویژگی‌ها</th>
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">وضعیت</th>
              <th class="text-right text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">تاریخ ایجاد</th>
              <th class="text-left text-[11px] font-bold text-[#4d7a56] tracking-wide px-3.5 py-3 whitespace-nowrap">عملیات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($products ?? [] as $product)
            <tr class="border-b border-[#222230] last:border-b-0 hover:bg-[#111116] transition-colors">
              <td class="px-3.5 py-3 text-[12.5px] text-[#a8c4a8] align-middle">
                <div class="flex items-center gap-2.5 min-w-[230px]">
                  <div class="w-[42px] h-[42px] rounded-lg bg-[#111116] border border-[#222230] flex items-center justify-center text-[#4d7a56] text-sm flex-shrink-0 overflow-hidden">
                    @if($product->thumbnail)
                      <img src="{{ asset('storage/'.$product->thumbnail) }}" alt="" class="w-full h-full object-cover">
                    @else
                      <i class="fa-solid fa-image"></i>
                    @endif
                  </div>
                  <div>
                    <div class="text-[13px] font-bold text-white">{{ $product->name_fa }}</div>
                    <div class="text-[10.5px] text-[#4d7a56] font-mono" dir="ltr">{{ $product->slug }}</div>
                  </div>
                </div>
              </td>
              <td class="px-3.5 py-3 text-[12.5px] align-middle">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/20">{{ $product->category }}</span>
                @if($product->subcategory)
                  <div class="text-[10.5px] text-[#4d7a56] mt-1">{{ $product->subcategory }}</div>
                @endif
              </td>
              <td class="px-3.5 py-3 text-[12.5px] align-middle">
                @if($product->pricing_model === 'free')
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20">رایگان</span>
                @else
                  <div class="font-bold text-white">{{ $product->credit_cost }} کردیت</div>
                @endif
              </td>
              <td class="px-3.5 py-3 text-[12.5px] align-middle">
                <div class="flex gap-1.5">
                  <i class="fa-solid fa-star text-[11px] {{ $product->is_featured ? 'text-[#a07af5]' : 'text-[#4d7a56]' }}" title="ویژه"></i>
                  <i class="fa-solid fa-bolt text-[11px] {{ $product->is_new ? 'text-[#a07af5]' : 'text-[#4d7a56]' }}" title="جدید"></i>
                  <i class="fa-solid fa-fire text-[11px] {{ $product->is_trending ? 'text-[#a07af5]' : 'text-[#4d7a56]' }}" title="ترند"></i>
                  <i class="fa-solid fa-user-tag text-[11px] {{ $product->face_swap_enabled ? 'text-[#a07af5]' : 'text-[#4d7a56]' }}" title="Face Swap"></i>
                </div>
              </td>
              <td class="px-3.5 py-3 text-[12.5px] align-middle">
                @if($product->status === 'active')
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20"><i class="fa-solid fa-circle text-[6px]"></i> فعال</span>
                @elseif($product->status === 'draft')
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25"><i class="fa-solid fa-circle text-[6px]"></i> پیش‌نویس</span>
                @else
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#f05c5c]/10 text-[#f05c5c] border border-[#f05c5c]/20"><i class="fa-solid fa-circle text-[6px]"></i> غیرفعال</span>
                @endif
              </td>
              <td class="px-3.5 py-3 text-[12.5px] text-[#4d7a56] align-middle">{{ $product->created_at?->format('Y/m/d') }}</td>
              <td class="px-3.5 py-3 text-[12.5px] align-middle">
                <div class="flex items-center gap-1 justify-end">
                  <button type="button" title="مشاهده" onclick="openDetailsModal({{ $product->id }})"
                     class="w-[30px] h-[30px] rounded-md border border-[#222230] bg-[#111116] flex items-center justify-center text-[#a8c4a8] text-[11.5px] hover:border-[#a07af5] hover:text-[#a07af5] transition-colors">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <a href="{{ route('admin.products.edit', $product->id) }}" title="ویرایش"
                     class="w-[30px] h-[30px] rounded-md border border-[#222230] bg-[#111116] flex items-center justify-center text-[#a8c4a8] text-[11.5px] hover:border-[#a07af5] hover:text-[#a07af5] transition-colors no-underline">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('این محصول حذف شود؟')" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" title="حذف"
                            class="w-[30px] h-[30px] rounded-md border border-[#222230] bg-[#111116] flex items-center justify-center text-[#a8c4a8] text-[11.5px] hover:border-[#f05c5c] hover:text-[#f05c5c] hover:bg-[#f05c5c]/5 transition-colors">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="px-3.5 py-3">
                <div class="py-16 px-5 text-center">
                  <i class="fa-solid fa-box-open text-4xl text-[#4d7a56] mb-3 block"></i>
                  <div class="text-sm font-bold text-[#a8c4a8] mb-1">هنوز محصولی ثبت نشده</div>
                  <div class="text-xs text-[#4d7a56]">با کلیک روی «ثبت محصول جدید» اولین محصول خود را اضافه کنید</div>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>

        @if(isset($products) && $products->hasPages())
        <div class="flex items-center justify-between px-4.5 py-3.5 border-t border-[#222230]">
          <div class="text-[11.5px] text-[#4d7a56]">نمایش {{ $products->firstItem() }} تا {{ $products->lastItem() }} از {{ $products->total() }} محصول</div>
          <div class="flex gap-1">
            {{ $products->links() }}
          </div>
        </div>
        @endif
      </div>

    </main>
  </div>

  <!-- ══ DETAILS MODAL ══ -->
  <div id="details-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onclick="if(event.target===this) closeDetailsModal()">
    <div class="bg-[#16161c] border border-[#222230] rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">

      <div class="sticky top-0 bg-[#16161c] border-b border-[#222230] flex items-center justify-between px-5 py-4 z-10">
        <div class="flex items-center gap-3">
          <div id="m-thumb" class="w-11 h-11 rounded-lg bg-[#111116] border border-[#222230] flex items-center justify-center text-[#4d7a56] overflow-hidden flex-shrink-0">
            <i class="fa-solid fa-image"></i>
          </div>
          <div>
            <div id="m-name" class="text-sm font-bold text-white">—</div>
            <div id="m-slug" class="text-[10.5px] text-[#4d7a56] font-mono" dir="ltr">—</div>
          </div>
        </div>
        <button onclick="closeDetailsModal()" class="w-8 h-8 rounded-md border border-[#222230] bg-[#111116] flex items-center justify-center text-[#a8c4a8] hover:border-[#f05c5c] hover:text-[#f05c5c] transition-colors">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="p-5 space-y-5">

        <!-- وضعیت و بج‌ها -->
        <div class="flex items-center gap-2 flex-wrap">
          <span id="m-status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold"></span>
          <span id="m-category" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#a07af5]/10 text-[#a07af5] border border-[#a07af5]/20"></span>
          <span id="m-subcategory" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-[#111116] text-[#a8c4a8] border border-[#222230]"></span>
        </div>

        <!-- توضیحات -->
        <div>
          <div class="text-[11px] font-bold text-[#4d7a56] tracking-wide mb-1.5">توضیح فارسی</div>
          <div id="m-desc-fa" class="text-[12.5px] text-[#a8c4a8] leading-relaxed">—</div>
        </div>
        <div>
          <div class="text-[11px] font-bold text-[#4d7a56] tracking-wide mb-1.5">توضیح انگلیسی</div>
          <div id="m-desc-en" class="text-[12.5px] text-[#a8c4a8] leading-relaxed" dir="ltr">—</div>
        </div>

        <!-- اطلاعات شبکه‌ای -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5">
            <div class="text-[10.5px] text-[#4d7a56] mb-1">قیمت</div>
            <div id="m-price" class="text-sm font-bold text-white">—</div>
          </div>
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5">
            <div class="text-[10.5px] text-[#4d7a56] mb-1">مدل اصلی AI</div>
            <div id="m-model" class="text-sm font-bold text-white font-mono" dir="ltr">—</div>
          </div>
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5">
            <div class="text-[10.5px] text-[#4d7a56] mb-1">نوع خروجی</div>
            <div id="m-output" class="text-sm font-bold text-white">—</div>
          </div>
          <div class="bg-[#111116] border border-[#222230] rounded-xl p-3.5">
            <div class="text-[10.5px] text-[#4d7a56] mb-1">تاریخ ایجاد</div>
            <div id="m-date" class="text-sm font-bold text-white">—</div>
          </div>
        </div>

        <!-- ویژگی‌ها -->
        <div>
          <div class="text-[11px] font-bold text-[#4d7a56] tracking-wide mb-2">ویژگی‌ها</div>
          <div class="flex items-center gap-3" id="m-features"></div>
        </div>

        <!-- پرامپت -->
        <div>
          <div class="text-[11px] font-bold text-[#4d7a56] tracking-wide mb-1.5">Prompt Template</div>
          <div id="m-prompt" class="bg-[#111116] border border-[#222230] rounded-xl p-3.5 text-[11.5px] text-[#a8c4a8] font-mono leading-relaxed" dir="ltr">—</div>
        </div>

      </div>

      <div class="sticky bottom-0 bg-[#16161c] border-t border-[#222230] flex items-center justify-end gap-2 px-5 py-3.5">
        <button onclick="closeDetailsModal()" class="inline-flex items-center gap-1.5 px-3.5 h-9 rounded-lg text-xs font-semibold bg-[#111116] text-[#a8c4a8] border border-[#222230] hover:border-[#2e2e3e] hover:text-white transition-colors">
          بستن
        </button>
        <a id="m-edit-link" href="#" class="inline-flex items-center gap-1.5 px-3.5 h-9 rounded-lg text-xs font-semibold bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-colors no-underline">
          <i class="fa-solid fa-pen text-[11px]"></i> ویرایش محصول
        </a>
      </div>

    </div>
  </div>

</div>

<script>
const productsData = {
  @foreach(($products ?? []) as $product)
  {{ $product->id }}: {
    name: @json($product->name_fa),
    slug: @json($product->slug),
    thumbnail: @json($product->thumbnail ? asset('storage/'.$product->thumbnail) : null),
    status: @json($product->status),
    category: @json($product->category),
    subcategory: @json($product->subcategory),
    descFa: @json($product->description_fa ?? 'بدون توضیح'),
    descEn: @json($product->description_en ?? 'No description'),
    pricingModel: @json($product->pricing_model),
    creditCost: @json($product->credit_cost),
    primaryModel: @json($product->primary_model ?? '—'),
    outputType: @json($product->output_type ?? '—'),
    createdAt: @json($product->created_at?->format('Y/m/d H:i')),
    isFeatured: @json((bool) $product->is_featured),
    isNew: @json((bool) $product->is_new),
    isTrending: @json((bool) $product->is_trending),
    faceSwap: @json((bool) ($product->face_swap_enabled ?? false)),
    promptTemplate: @json($product->prompt_template ?? '—'),
    editUrl: @json(route('admin.products.edit', $product->id)),
  },
  @endforeach
};

const statusMap = {
  active:   { label: 'فعال',      classes: 'bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20' },
  draft:    { label: 'پیش‌نویس',  classes: 'bg-[#f5923a]/10 text-[#f5923a] border border-[#f5923a]/25' },
  inactive: { label: 'غیرفعال',   classes: 'bg-[#f05c5c]/10 text-[#f05c5c] border border-[#f05c5c]/20' },
};

function featureIcon(active, icon, label) {
  const color = active ? 'text-[#a07af5]' : 'text-[#4d7a56]';
  return `<div class="flex items-center gap-1.5"><i class="fa-solid ${icon} ${color} text-xs"></i><span class="text-[11px] ${active ? 'text-[#a8c4a8]' : 'text-[#4d7a56]'}">${label}</span></div>`;
}

function openDetailsModal(id) {
  const p = productsData[id];
  if (!p) return;

  document.getElementById('m-name').textContent = p.name;
  document.getElementById('m-slug').textContent = p.slug;

  const thumbEl = document.getElementById('m-thumb');
  thumbEl.innerHTML = p.thumbnail
    ? `<img src="${p.thumbnail}" class="w-full h-full object-cover">`
    : `<i class="fa-solid fa-image"></i>`;

  const st = statusMap[p.status] || statusMap.draft;
  const statusEl = document.getElementById('m-status');
  statusEl.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-bold ' + st.classes;
  statusEl.innerHTML = `<i class="fa-solid fa-circle text-[6px]"></i> ${st.label}`;

  document.getElementById('m-category').textContent = p.category || '—';

  const subEl = document.getElementById('m-subcategory');
  if (p.subcategory) { subEl.textContent = p.subcategory; subEl.style.display = ''; }
  else { subEl.style.display = 'none'; }

  document.getElementById('m-desc-fa').textContent = p.descFa;
  document.getElementById('m-desc-en').textContent = p.descEn;

  document.getElementById('m-price').textContent = p.pricingModel === 'free' ? 'رایگان' : `${p.creditCost ?? 0} کردیت`;
  document.getElementById('m-model').textContent = p.primaryModel;
  document.getElementById('m-output').textContent = p.outputType;
  document.getElementById('m-date').textContent = p.createdAt || '—';

  document.getElementById('m-features').innerHTML =
    featureIcon(p.isFeatured, 'fa-star', 'ویژه') +
    featureIcon(p.isNew, 'fa-bolt', 'جدید') +
    featureIcon(p.isTrending, 'fa-fire', 'ترند') +
    featureIcon(p.faceSwap, 'fa-user-tag', 'Face Swap');

  document.getElementById('m-prompt').textContent = p.promptTemplate;
  document.getElementById('m-edit-link').href = p.editUrl;

  const modal = document.getElementById('details-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
  const modal = document.getElementById('details-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeDetailsModal();
});
</script>
@endsection