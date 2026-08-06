<div class="content-card p-5">
  <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> مناسبت‌های ثبت‌شده</div>
  <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">در صفحه عمومی فقط سه مناسبت فعال با بالاترین اولویت و جدیدترین تاریخ نمایش داده می‌شوند.</div>

  <div class="overflow-x-auto">
    <table class="table-pro min-w-[760px]">
      <thead><tr><th>عنوان</th><th>جستجو</th><th>دسته‌بندی</th><th>اولویت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
      <tbody>
        @forelse($occasions as $occasion)
          <tr>
            <td>
              <form method="POST" action="{{ route('admin.trends.occasions.update', $occasion) }}" class="grid grid-cols-2 gap-2 min-w-[230px]">
                @csrf @method('PUT')
                <input type="text" name="title_fa" value="{{ $occasion->title_fa }}" required class="input-pro">
                <input type="text" name="query" value="{{ $occasion->query }}" class="input-pro">
                <select name="category_id" class="input-pro col-span-2"><option value="">بدون اتصال مستقیم</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ (int) $occasion->category_id === (int) $category->id ? 'selected' : '' }}>{{ $category->name_fa ?: $category->name }}</option>@endforeach</select>
                <input type="number" name="sort_order" min="0" max="255" value="{{ $occasion->sort_order }}" class="input-pro">
                <label class="flex items-center gap-2 text-[10px]" style="color:var(--text-soft);"><input type="checkbox" name="is_active" value="1" {{ $occasion->is_active ? 'checked' : '' }}> فعال</label>
                <button type="submit" class="btn-pro btn-pro-ghost col-span-2 justify-center"><i class="fa-solid fa-floppy-disk text-[10px]"></i> ذخیره تغییرات</button>
              </form>
            </td>
            <td class="align-top text-[11px]" style="color:var(--text-soft);">{{ $occasion->query ?: '—' }}</td>
            <td class="align-top text-[11px]" style="color:var(--text-soft);">{{ $occasion->category?->name_fa ?: $occasion->category?->name ?: '—' }}</td>
            <td class="align-top text-[11px]" style="color:var(--text-soft);">{{ $occasion->sort_order }}</td>
            <td class="align-top"><form method="POST" action="{{ route('admin.trends.occasions.toggle', $occasion) }}">@csrf @method('PATCH')<button type="submit" class="badge-pro {{ $occasion->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $occasion->is_active ? 'فعال' : 'غیرفعال' }}</button></form></td>
            <td class="align-top"><form method="POST" action="{{ route('admin.trends.occasions.destroy', $occasion) }}" onsubmit="return confirm('این مناسبت حذف شود؟');">@csrf @method('DELETE')<button type="submit" class="icon-action-btn" title="حذف"><i class="fa-solid fa-trash"></i></button></form></td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">هنوز مناسبتی ثبت نشده است.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
