@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = $source->title;
  $siSubtitle = ($categories[$source->category] ?? $source->category).' · نسخه‌ی '.Ui::n($source->version).' · '.Ui::n($source->char_count).' نویسه در '.Ui::n($source->chunk_count).' بخش';
  $digest = (array) $source->digest;
@endphp

@section('si-actions')
  <span class="badge-pro badge-{{ Ui::statusTone($source->status) }}"><i class="fa-solid fa-circle"></i> {{ Ui::label('knowledge', $source->status) }}</span>
  <span class="badge-pro {{ $source->isUsableByAi() ? 'badge-success' : 'badge-neutral' }}">{{ $source->isUsableByAi() ? 'دستیار از آن استفاده می‌کند' : 'دستیار از آن استفاده نمی‌کند' }}</span>
  @if($canManage)
    @if($source->status !== 'approved')
      <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.action', $source) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-check text-[11px]"></i> تأیید</button></form>
    @else
      <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.action', $source) }}">@csrf<input type="hidden" name="action" value="draft"><button class="btn-pro btn-pro-ghost">لغو تأیید</button></form>
    @endif
    <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.action', $source) }}">@csrf<input type="hidden" name="action" value="digest"><button class="btn-pro btn-pro-ghost"><i class="fa-solid fa-brain text-[11px]"></i> تحلیل هوشمند</button></form>
    @if($source->status !== 'archived')
      <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.action', $source) }}">@csrf<input type="hidden" name="action" value="archive"><button class="btn-pro btn-pro-ghost">بایگانی</button></form>
    @endif
    <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.destroy', $source) }}" data-confirm="این منبع دانش برای همیشه حذف شود؟">@csrf @method('DELETE')<button class="btn-pro btn-pro-danger" aria-label="حذف"><i class="fa-solid fa-trash text-[11px]"></i></button></form>
  @endif
@endsection

@section('si-page')
  <div class="si-grid-2">
    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-brain"></i> برداشت دستیار از این منبع</div><div class="si-panel-sub">دستیار متن را خوانده و آن‌چه فهمیده را خلاصه کرده؛ پیش از تأیید بررسی کنید.</div></div>
          @if($source->digest_status)<span class="badge-pro badge-{{ Ui::statusTone(['done' => 'success', 'failed' => 'failed', 'queued' => 'queued', 'running' => 'running'][$source->digest_status] ?? 'info') }}">{{ ['done' => 'تحلیل شد', 'failed' => 'ناموفق', 'queued' => 'در صف', 'running' => 'در حال تحلیل'][$source->digest_status] ?? $source->digest_status }}</span>@endif
        </div>
        @if($digest)
          @if(!empty($digest['summary']))<p style="font-size:12.5px;line-height:2;color:var(--text-h);margin:0 0 10px">{{ $digest['summary'] }}</p>@endif
          @if(!empty($digest['key_facts']))<div class="si-label">حقایق کلیدی</div><ul class="si-ul">@foreach((array) $digest['key_facts'] as $fact)<li>{{ is_array($fact) ? implode(' — ', $fact) : $fact }}</li>@endforeach</ul>@endif
          @if(!empty($digest['prices']))
            <div class="si-label" style="margin-top:10px">قیمت‌های صریح</div>
            <div class="si-table-wrap"><table class="table-pro"><thead><tr><th>مورد</th><th>قیمت</th><th>شرط</th></tr></thead><tbody>@foreach((array) $digest['prices'] as $price)<tr><td>{{ data_get($price, 'item', '—') }}</td><td>{{ data_get($price, 'price', '—') }}</td><td class="si-muted">{{ data_get($price, 'condition', '—') }}</td></tr>@endforeach</tbody></table></div>
          @endif
          @if(!empty($digest['faqs']))<div class="si-label" style="margin-top:10px">پرسش‌هایی که این منبع پاسخ می‌دهد</div>@foreach((array) $digest['faqs'] as $faq)<div class="si-source-chip"><b>{{ data_get($faq, 'q') }}</b><br>{{ data_get($faq, 'a') }}</div>@endforeach @endif
          @if(!empty($digest['gaps']))<div class="si-flash is-warning" style="margin-top:12px"><i class="fa-solid fa-circle-question"></i><div><b>کمبودها:</b> {{ implode('، ', array_map(fn ($g) => is_array($g) ? implode(' ', $g) : $g, (array) $digest['gaps'])) }}</div></div>@endif
          @if(!empty($digest['warnings']))<div class="si-flash is-danger"><i class="fa-solid fa-triangle-exclamation"></i><div><b>ابهام/تناقض:</b> {{ implode('، ', array_map(fn ($g) => is_array($g) ? implode(' ', $g) : $g, (array) $digest['warnings'])) }}</div></div>@endif
          @if(!empty($digest['truncated']))<p class="si-help">این متن طولانی بود؛ تحلیل روی بخش اول انجام شد، اما همه‌ی بخش‌ها برای جست‌وجو ایندکس شده‌اند.</p>@endif
        @else
          <div class="si-table-empty">هنوز تحلیل نشده؛ «تحلیل هوشمند» را بزنید.</div>
        @endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-cubes"></i> بخش‌های ایندکس‌شده</div><span class="si-muted">{{ Ui::n($chunks->count()) }} از {{ Ui::n($source->chunk_count) }}</span></div>
        @foreach($chunks as $chunk)
          <div class="si-source-chip"><span class="si-muted">بخش {{ Ui::n($chunk->position + 1) }}</span><br>{{ \Illuminate\Support\Str::limit($chunk->content, 400) }}</div>
        @endforeach
      </section>
    </div>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-pen"></i> ویرایش</div></div>
      @if($canManage)
        <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.update', $source) }}" class="si-form is-1">
          @csrf @method('PUT')
          <div class="si-field"><label for="s-title">عنوان</label><input id="s-title" name="title" class="input-pro" required maxlength="190" value="{{ old('title', $source->title) }}"></div>
          <div class="si-field"><label for="s-cat">دسته</label><select id="s-cat" name="category" class="input-pro">@foreach($categories as $k => $l)<option value="{{ $k }}" @selected($source->category === $k)>{{ $l }}</option>@endforeach</select></div>
          <div class="si-field"><label for="s-content">متن</label><textarea id="s-content" name="content" class="input-pro is-tall" style="min-height:360px" maxlength="200000" required>{{ old('content', $source->content) }}</textarea><span class="si-help">تغییر متن، نسخه را بالا می‌برد و تا تأیید دوباره از دسترس دستیار خارج می‌شود.</span></div>
          <div class="si-field"><label for="s-valid">معتبر تا</label><input id="s-valid" type="date" name="valid_until" class="input-pro" value="{{ old('valid_until', $source->valid_until?->format('Y-m-d')) }}"></div>
          <label class="si-check"><input type="hidden" name="ai_allowed" value="0"><input type="checkbox" name="ai_allowed" value="1" @checked($source->ai_allowed)> دستیار اجازه‌ی استفاده دارد</label>
          <div class="si-form-actions"><button class="btn-pro btn-pro-primary">ذخیره</button></div>
          <div class="si-help">@if($source->approver)تأیید: {{ $source->approver->name }} · {{ Ui::date($source->approved_at, true) }} · @endif استفاده در پاسخ‌ها: {{ Ui::n($source->usage_count) }} بار @if($source->original_filename) · فایل: {{ $source->original_filename }}@endif</div>
        </form>
      @else
        <div class="si-pre">{{ $source->content }}</div>
      @endif
    </section>
  </div>
@endsection
