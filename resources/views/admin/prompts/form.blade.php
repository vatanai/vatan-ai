<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-w-4xl w-full max-[768px]:p-[18px]" id="content" dir="rtl">
    <div class="mb-6"><h1 class="text-xl font-extrabold" style="color:var(--text-h);">{{ $prompt ? 'ویرایش پرامپت' : 'افزودن پرامپت جدید' }}</h1><p class="text-xs mt-1" style="color:var(--text-secondary);">اطلاعات سبک و متن پرامپت را وارد کنید.</p></div>
    @if(isset($errors) && $errors->any())<div class="mb-5 rounded-lg p-4 text-xs" style="background:color-mix(in srgb, var(--danger) 12%, transparent);color:var(--danger);">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border p-5" style="border-color:var(--border);background:var(--card-bg);">
      @csrf @if($formMethod !== 'POST') @method($formMethod) @endif
      <div><label class="block text-xs font-bold mb-2" style="color:var(--text-h);">نام سبک</label><input required name="name" value="{{ old('name', $prompt?->name) }}" class="w-full rounded-lg border p-3 text-sm" style="border-color:var(--border);background:var(--page-bg);color:var(--text-main);"></div>
      <div><label class="block text-xs font-bold mb-2" style="color:var(--text-h);">متن پرامپت</label><textarea required name="prompt" rows="8" dir="ltr" class="w-full rounded-lg border p-3 text-sm" style="border-color:var(--border);background:var(--page-bg);color:var(--text-main);">{{ old('prompt', $prompt?->prompt) }}</textarea></div>
      <div><label class="block text-xs font-bold mb-2" style="color:var(--text-h);">توضیحات</label><textarea name="description" rows="3" class="w-full rounded-lg border p-3 text-sm" style="border-color:var(--border);background:var(--page-bg);color:var(--text-main);">{{ old('description', $prompt?->description) }}</textarea></div>
      <div><label class="block text-xs font-bold mb-2" style="color:var(--text-h);">تصویر {{ $prompt ? '(اختیاری)' : '' }}</label><input {{ $prompt ? '' : 'required' }} type="file" name="image" accept="image/*" class="text-xs" style="color:var(--text-secondary);"></div>
      <label class="flex items-center gap-2 text-xs" style="color:var(--text-main);"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $prompt?->is_active ?? true))> فعال باشد</label>
      <div class="flex gap-2"><button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold" style="background:var(--primary);color:var(--text-h);">{{ $submitLabel }}</button><a href="{{ route('admin.prompts.index') }}" class="px-5 py-2 rounded-lg text-xs border" style="border-color:var(--border);color:var(--text-main);">انصراف</a></div>
    </form>
  </div>
</main>
