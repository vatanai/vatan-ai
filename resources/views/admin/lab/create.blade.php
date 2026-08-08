@extends('layouts.admin')
@section('title', 'ثبت آزمایش جدید')
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl" style="background:var(--page-bg);">
    <div class="flex items-center justify-between gap-3 mb-5"><div><h1 class="text-xl font-extrabold" style="color:var(--text-h);">ثبت آزمایش جدید</h1><p class="text-[12px] mt-1" style="color:var(--text-soft);">محصول را انتخاب کنید و سپس همان آزمایشگاه مشترک فرم محصول را اجرا کنید.</p></div><a href="{{ route('admin.lab.index') }}" class="btn-pro btn-pro-ghost">بازگشت</a></div>
    @if($errors->any())<div class="content-card p-3 mb-4" style="color:var(--danger);">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.lab.store') }}" id="standalone-lab-form">
      @csrf
      <input type="hidden" name="product_id" id="standalone-product-id" value="{{ old('product_id', $selectedProductId) }}">
      <input type="hidden" name="ai_lab_payload" id="ai_lab_payload">
      <section class="content-card p-5" id="standalone-step-one">
        <div class="flex items-center gap-2 mb-4"><span class="lab-page-icon"><i class="fa-solid fa-box"></i></span><h2 class="text-base font-bold" style="color:var(--text-h);">گام اول: انتخاب محصول</h2></div>
        <input id="standalone-product-search" class="input-pro w-full mb-3" placeholder="جستجوی نام یا کد محصول">
        <div id="standalone-products" class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-[420px] overflow-y-auto">
          @foreach($products as $product)<button type="button" class="standalone-product text-right p-3 rounded-lg border" data-id="{{ $product->id }}" data-search="{{ $product->name_fa }} {{ $product->name_en }} {{ $product->product_code }}" style="border-color:var(--b1);background:var(--s1);color:var(--text-h);"><strong>{{ $product->name_fa }}</strong><small class="block mt-1" style="color:var(--text-soft);">{{ $product->product_code }} · {{ $product->name_en }}</small></button>@endforeach
        </div>
        <div class="flex justify-end mt-5"><button type="button" id="standalone-next" class="btn-pro btn-pro-primary">ادامه به آزمایشگاه <i class="fa-solid fa-arrow-left"></i></button></div>
      </section>
      <section id="standalone-step-two" class="hidden mt-4">
        @include('admin.products.partials.ai-model-lab', ['aiModels' => $models, 'exchange' => $exchange ?? [], 'labTested' => false, 'product' => null, 'labVersion' => 'V12'])
        <div class="flex justify-between mt-4"><button type="button" id="standalone-back" class="btn-pro btn-pro-ghost">گام قبل</button><button type="submit" class="btn-pro btn-pro-primary">ساخت و اجرای آزمایش <i class="fa-solid fa-flask"></i></button></div>
      </section>
    </form>
  </div>
</main>
@push('scripts')
<script>
(() => { const form=document.getElementById('standalone-lab-form'), one=document.getElementById('standalone-step-one'), two=document.getElementById('standalone-step-two'), id=document.getElementById('standalone-product-id'); document.querySelectorAll('.standalone-product').forEach(b=>b.addEventListener('click',()=>{id.value=b.dataset.id;document.querySelectorAll('.standalone-product').forEach(x=>x.style.borderColor='var(--b1)');b.style.borderColor='var(--accent)';})); document.getElementById('standalone-product-search')?.addEventListener('input',e=>{const q=e.target.value.toLowerCase();document.querySelectorAll('.standalone-product').forEach(b=>b.classList.toggle('hidden',!b.dataset.search.toLowerCase().includes(q)));}); document.getElementById('standalone-next')?.addEventListener('click',()=>{if(!id.value){alert('ابتدا یک محصول را انتخاب کنید.');return;} window.setAiModelLabProduct?.(id.value); one.classList.add('hidden');two.classList.remove('hidden');}); document.getElementById('standalone-back')?.addEventListener('click',()=>{two.classList.add('hidden');one.classList.remove('hidden');}); form?.addEventListener('submit',()=>{ if(!id.value) return; }); })();
</script>
@endpush
@endsection
