@extends('layouts.admin')
@section('title', 'مدیریت تخفیفات — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @include('admin.orders.partials.messages')
    <div class="orders-head"><div><div class="orders-title">تخفیفات</div><div class="orders-subtitle">کدهای تخفیف اعتبار برای همه محصولات، محصولات منتخب یا دسته‌بندی‌ها</div></div><button class="order-btn primary" type="button" onclick="discountNew()"><i class="fa-solid fa-plus"></i> تخفیف جدید</button></div>
    <div class="orders-stats">
      @foreach([['کل تخفیف‌ها',$stats['total'],'fa-tags'],['فعال',$stats['active'],'fa-circle-check'],['دفعات استفاده',$stats['uses'],'fa-users'],['اعتبار تخفیف‌داده‌شده',$stats['credits'],'fa-coins']] as [$label,$value,$icon])<div class="order-stat"><div class="order-stat-icon"><i class="fa-solid {{ $icon }}"></i></div><div class="order-stat-label">{{ $label }}</div><div class="order-stat-value">{{ number_format($value) }}</div></div>@endforeach
    </div>
    <form class="order-panel order-filters" method="GET" style="grid-template-columns:minmax(220px,2fr) minmax(150px,1fr) auto">
      <div class="order-field"><label>جستجو</label><input class="order-input" name="q" value="{{ request('q') }}" placeholder="نام یا کد تخفیف"></div>
      <div class="order-field"><label>وضعیت</label><select class="order-select" name="status"><option value="">همه</option><option value="active" @selected(request('status')==='active')>فعال</option><option value="inactive" @selected(request('status')==='inactive')>غیرفعال</option><option value="expired" @selected(request('status')==='expired')>منقضی</option></select></div>
      <button class="order-btn primary"><i class="fa-solid fa-filter"></i> اعمال</button>
    </form>
    <div class="discount-layout">
      @include('admin.discounts.partials.form')
      @include('admin.discounts.partials.list')
    </div>
  </div>
</main>
@endsection
@section('scripts')
<script>
const discountBase='{{ url('/admin/discounts') }}';
function discountNew(){const f=document.getElementById('discount-form');f.reset();f.action=discountBase;document.getElementById('discount-method').value='POST';document.getElementById('discount-form-title').textContent='ساخت تخفیف جدید';document.getElementById('discount-submit').innerHTML='<i class="fa-solid fa-plus"></i> ساخت تخفیف';discountScope();window.scrollTo({top:0,behavior:'smooth'});}
function discountEdit(data){const f=document.getElementById('discount-form');f.action=discountBase+'/'+data.id;document.getElementById('discount-method').value='PUT';document.getElementById('discount-form-title').textContent='ویرایش '+data.code;document.getElementById('discount-submit').innerHTML='<i class="fa-solid fa-floppy-disk"></i> ذخیره تغییرات';['name','code','type','value','max_discount_credits','min_order_credits','usage_limit','usage_limit_per_user','scope','starts_at','ends_at','description'].forEach(k=>{if(f.elements[k])f.elements[k].value=data[k]??''});f.elements.is_active.checked=!!data.is_active;f.elements.first_order_only.checked=!!data.first_order_only;document.querySelectorAll('[name="product_ids[]"],[name="category_ids[]"]').forEach(o=>o.selected=false);(data.product_ids||[]).forEach(id=>{const o=f.querySelector('[name="product_ids[]"] option[value="'+id+'"]');if(o)o.selected=true});(data.category_ids||[]).forEach(id=>{const o=f.querySelector('[name="category_ids[]"] option[value="'+id+'"]');if(o)o.selected=true});discountScope();window.scrollTo({top:0,behavior:'smooth'});}
function discountScope(){const value=document.getElementById('discount-scope').value;document.getElementById('discount-products').style.display=value==='products'?'block':'none';document.getElementById('discount-categories').style.display=value==='categories'?'block':'none';}
document.addEventListener('DOMContentLoaded',discountScope);
</script>
@endsection
