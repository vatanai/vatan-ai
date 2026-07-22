<section class="order-panel">
  <div class="order-panel-head"><div class="order-panel-title">فهرست تخفیف‌ها</div><span class="order-meta">{{ number_format($discounts->total()) }} مورد</span></div>
  <div class="discount-card-list">
    @forelse($discounts as $discount)
      @php
        $expired=$discount->ends_at && $discount->ends_at->isPast();
        $payload=[
          'id'=>$discount->id,'name'=>$discount->name,'code'=>$discount->code,'type'=>$discount->type,'value'=>$discount->value,
          'max_discount_credits'=>$discount->max_discount_credits,'min_order_credits'=>$discount->min_order_credits,
          'usage_limit'=>$discount->usage_limit,'usage_limit_per_user'=>$discount->usage_limit_per_user,'scope'=>$discount->scope,
          'product_ids'=>$discount->product_ids,'category_ids'=>$discount->category_ids,'first_order_only'=>$discount->first_order_only,
          'is_active'=>$discount->is_active,'starts_at'=>$discount->starts_at?->format('Y-m-d\TH:i'),'ends_at'=>$discount->ends_at?->format('Y-m-d\TH:i'),'description'=>$discount->description,
        ];
      @endphp
      <article class="discount-card">
        <div class="discount-card-top"><div><div class="discount-code">{{ $discount->code }}</div><div class="order-user-name" style="margin-top:4px">{{ $discount->name }}</div></div>@if($expired)<span class="order-badge danger">منقضی</span>@elseif($discount->is_active)<span class="order-badge success">فعال</span>@else<span class="order-badge">غیرفعال</span>@endif</div>
        <div class="order-info-grid" style="padding:8px 0 0">
          <div class="order-info-row"><span class="order-info-key">مقدار</span><span class="order-info-value">{{ $discount->type==='percent' ? $discount->value.'٪' : ($discount->type==='free' ? 'رایگان' : number_format($discount->value).' اعتبار') }}</span></div>
          <div class="order-info-row"><span class="order-info-key">دامنه</span><span class="order-info-value">{{ ['all'=>'همه محصولات','products'=>'محصولات منتخب','categories'=>'دسته‌بندی‌ها'][$discount->scope] }}</span></div>
          <div class="order-info-row"><span class="order-info-key">استفاده</span><span class="order-info-value">{{ number_format($discount->used_count) }}{{ $discount->usage_limit ? ' از '.number_format($discount->usage_limit) : '' }}</span></div>
          <div class="order-info-row"><span class="order-info-key">سفارش مرتبط</span><span class="order-info-value">{{ number_format($discount->orders_count) }}</span></div>
        </div>
        @if($discount->usage_limit)<div class="order-progress"><span style="width:{{ min(100,round($discount->used_count*100/max(1,$discount->usage_limit))) }}%"></span></div>@endif
        <div class="discount-card-actions">
          <button class="order-btn" type="button" onclick='discountEdit(@json($payload))'><i class="fa-solid fa-pen"></i> ویرایش</button>
          <form method="POST" action="{{ route('admin.discounts.toggle',$discount) }}">@csrf @method('PATCH')<button class="order-btn" title="تغییر وضعیت"><i class="fa-solid fa-power-off"></i></button></form>
          <form method="POST" action="{{ route('admin.discounts.destroy',$discount) }}" onsubmit="return confirm('این تخفیف حذف یا غیرفعال شود؟')">@csrf @method('DELETE')<button class="order-btn danger" title="حذف"><i class="fa-solid fa-trash"></i></button></form>
        </div>
      </article>
    @empty<div class="order-empty"><i class="fa-solid fa-tags"></i>هنوز تخفیفی ساخته نشده است.</div>@endforelse
  </div>
  @if($discounts->hasPages())<div class="order-pagination">{{ $discounts->links() }}</div>@endif
</section>
