<div class="content-card p-5">
  <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-images" style="color:var(--primary);"></i> بنرهای ثبت‌شده</div>
  <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">بنرها با توجه به دستگاه و بعد از ردیف تعیین‌شده در فهرست محصولات ترند قرار می‌گیرند.</div>

  <div class="overflow-x-auto">
    <table class="table-pro min-w-[900px]">
      <thead><tr><th>بنر</th><th>نمایش در</th><th>بعد از ردیف</th><th>اولویت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
      <tbody>
        @forelse($banners as $banner)
          <tr>
            <td>
              <div class="flex items-center gap-2.5 min-w-[230px]">
                <img src="{{ $banner->imageUrl($banner->image_desktop ? 'desktop' : 'mobile') }}" alt="" class="w-20 h-10 rounded-lg object-cover">
                <div class="text-[11px] font-bold" style="color:var(--text-h);">{{ $banner->title }}</div>
              </div>
            </td>
            <td class="text-[11px]" style="color:var(--text-soft);">{{ ['both' => 'موبایل و دسکتاپ', 'mobile' => 'فقط موبایل', 'desktop' => 'فقط دسکتاپ'][$banner->display_target] ?? $banner->display_target }}</td>
            <td class="text-[12px] font-bold">{{ $banner->row_number }}</td>
            <td class="text-[12px]">{{ $banner->sort_order }}</td>
            <td>
              <form method="POST" action="{{ route('admin.trends.banners.toggle', $banner) }}">
                @csrf @method('PATCH')
                <button type="submit" class="badge-pro {{ $banner->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $banner->is_active ? 'فعال' : 'غیرفعال' }}</button>
              </form>
            </td>
            <td>
              <div class="flex items-center gap-1.5">
                <a href="{{ route('admin.trends.index', ['edit_banner' => $banner->id]) }}" class="icon-action-btn" title="ویرایش"><i class="fa-solid fa-pen"></i></a>
                <form method="POST" action="{{ route('admin.trends.banners.destroy', $banner) }}" onsubmit="return confirm('این بنر حذف شود؟');">
                  @csrf @method('DELETE')
                  <button type="submit" class="icon-action-btn" title="حذف" style="color:var(--danger);"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">هنوز بنری برای صفحه ترند ثبت نشده است.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
