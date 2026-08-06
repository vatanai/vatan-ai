<div class="content-card p-5 mb-5">
  <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
    <div class="text-[14px] font-extrabold" style="color:var(--text-h);"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> محصولات صفحه ترند</div>
    <span class="text-[11px]" style="color:var(--text-soft);">با غیرفعال‌کردن هر ردیف، محصول از صفحه عمومی ترند حذف می‌شود.</span>
  </div>

  <div class="overflow-x-auto mt-4">
    <table class="table-pro min-w-[980px]">
      <thead><tr><th>محصول</th><th>وضعیت</th><th>بازدید</th><th>بازکردن از ترند</th><th>ساخت</th><th>دانلود</th><th>عملیات</th></tr></thead>
      <tbody>
        @forelse($products as $product)
          <tr>
            <td>
              <div class="flex items-center gap-2.5 min-w-[230px]">
                <img src="{{ $product->displayImageUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
                <div class="min-w-0">
                  <a href="{{ route('app.product', $product->route_slug) }}" target="_blank" class="block truncate text-[12px] font-bold no-underline" style="color:var(--text-h);">{{ $product->name_fa }}</a>
                  <div class="text-[9px] mt-1" style="color:var(--text-soft);">{{ $product->product_code ?: $product->slug }}</div>
                </div>
              </div>
            </td>
            <td><span class="badge-pro badge-success"><i class="fa-solid fa-circle text-[6px]"></i> فعال</span></td>
            <td class="text-[12px] font-bold">{{ number_format($product->views_count) }}</td>
            <td class="text-[12px] font-bold">{{ number_format($product->trend_opens_count) }}</td>
            <td class="text-[12px] font-bold">{{ number_format($product->generations_count) }}</td>
            <td class="text-[12px] font-bold">{{ number_format($product->downloads_count) }}</td>
            <td>
              <form method="POST" action="{{ route('admin.trends.products.toggle', $product) }}" onsubmit="return confirm('این محصول از صفحه ترند حذف شود؟');">
                @csrf @method('PATCH')
                <button type="submit" class="btn-pro btn-pro-danger text-[10px]"><i class="fa-solid fa-eye-slash"></i> حذف از ترند</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7"><div class="empty-state">هنوز محصولی در صفحه ترند فعال نشده است.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($products->hasPages())
    <div class="mt-4">{{ $products->links() }}</div>
  @endif
</div>
