<section class="ps-panel" data-ps-panel="import" @if($tab !== 'import') hidden @endif>
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
    <div class="content-card p-5">
      <div class="ps-card-title mb-2"><i class="fa-solid fa-link"></i> ورود محصولات از سایت</div>
      <p class="text-[12px] leading-6 mb-4" style="color:var(--text-soft);">نشانی یک سایت را وارد کنید. برای کتابخانه‌های شناخته‌شده، فهرست همه‌ی محصولات پیدا می‌شود؛ صفحه‌های محصول استاندارد هم جداگانه قابل ورود هستند.</p>
      <form id="product-import-form" action="{{ route('admin.product-shots.imports.store') }}" method="POST" class="space-y-3">
        @csrf
        <div class="ps-field"><label for="product-import-url">نشانی سایت یا صفحه‌ی محصول</label><input id="product-import-url" class="input-pro" name="source_url" type="url" required dir="ltr" placeholder="https://example.com" value="https://shootkits.com/"></div>
        <button class="btn-pro btn-pro-primary" type="submit" id="product-import-start"><i class="fa-solid fa-cloud-arrow-down"></i> شمارش و شروع انتقال</button>
      </form>
      <div id="product-import-progress" class="mt-4" aria-live="polite"></div>
    </div>

    <div class="content-card p-5">
      <div class="ps-card-title mb-2"><i class="fa-solid fa-wand-magic-sparkles"></i> اصلاحیه‌ی پرامپت محصول</div>
      <p class="text-[12px] leading-6 mb-3" style="color:var(--text-soft);">این دستور روی پرامپت تک‌تک اسلایدها اعمال می‌شود. متن اصلی تا جای ممکن حفظ می‌شود و فقط به‌اندازه‌ی لازم برای عکس خام محصول و کیفیت خروجی وطن اصلاح می‌شود.</p>
      <form id="product-import-settings" action="{{ route('admin.product-shots.imports.settings') }}" method="POST" class="space-y-3">
        @csrf
        @method('PUT')
        <div class="ps-field"><label for="product-import-amendment">دستور اصلاح پرامپت</label><textarea id="product-import-amendment" name="prompt_amendment" class="input-pro" rows="8" dir="auto" required>{{ $importSetting->prompt_amendment }}</textarea></div>
        <input type="hidden" name="text_model" value="{{ $importSetting->text_model }}">
        <div class="flex items-center gap-3"><button class="btn-pro btn-pro-ghost" type="submit">ذخیره‌ی اصلاحیه</button><span id="product-import-settings-status" class="text-[11px]" style="color:var(--text-soft);" aria-live="polite"></span></div>
      </form>
    </div>
  </div>

  <div class="content-card p-0 overflow-hidden">
    <div class="flex items-center justify-between flex-wrap gap-2 p-4" style="border-bottom:1px solid var(--divider);">
      <div class="ps-card-title"><i class="fa-solid fa-clock-rotate-left"></i> انتقال‌های اخیر</div>
      <span class="text-[11px]" style="color:var(--text-soft);">فهرست صفحات، تعداد، وضعیت و محصولات ایجادشده</span>
    </div>
    @if($importRuns->isEmpty())
      <div class="empty-state"><div class="empty-state-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div class="empty-state-title">هنوز انتقالی انجام نشده</div><div class="empty-state-desc">بعد از واردکردن نشانی سایت، آمار دقیق و پیشرفت اینجا دیده می‌شود.</div></div>
    @else
      <div class="overflow-x-auto"><table class="table-pro"><thead><tr><th>منبع</th><th>صفحات</th><th>پردازش</th><th>محصول</th><th>خطا</th><th>وضعیت</th><th>ادامه</th></tr></thead><tbody>
        @foreach($importRuns as $run)
          <tr>
            <td><span dir="ltr" class="inline-block max-w-[250px] truncate">{{ $run->source_host }}</span><div class="text-[10px]" style="color:var(--text-soft);">{{ $run->created_at?->format('Y/m/d H:i') }}</div></td>
            <td>{{ $fa($run->discovered_count) }}</td><td>{{ $fa($run->processed_count) }}</td><td>{{ $fa($run->product_count) }}</td><td>{{ $fa($run->failed_count) }}</td>
            <td><span class="badge-pro {{ $run->status === 'completed' ? 'badge-success' : ($run->status === 'partial' ? 'badge-warning' : 'badge-neutral') }}">{{ ['processing' => 'در حال انتقال', 'completed' => 'کامل', 'partial' => 'نیازمند تلاش دوباره', 'ready' => 'آماده'][$run->status] ?? $run->status }}</span></td>
            <td>@if($run->status === 'processing')<button type="button" class="btn-pro btn-pro-ghost" data-import-resume="{{ $run->id }}">ادامه</button>@elseif($run->status === 'partial')<button type="button" class="btn-pro btn-pro-ghost" data-import-retry="{{ $run->id }}">تلاش دوباره</button>@else—@endif</td>
          </tr>
        @endforeach
      </tbody></table></div>
    @endif
  </div>
</section>

<script>
(function () {
  var csrf = document.querySelector('#product-import-form input[name="_token"]')?.value || '';
  var progress = document.getElementById('product-import-progress');
  var start = document.getElementById('product-import-start');
  var active = false;

  function show(message, tone) {
    if (!progress) return;
    var box = document.createElement('div');
    box.className = 'px-4 py-3 rounded-xl text-[12px]';
    box.style.cssText = 'white-space:pre-line;background:var(--' + (tone || 'info') + '-l);color:var(--' + (tone || 'info') + ');border:1px solid var(--' + (tone || 'info') + '-m);';
    box.textContent = message;
    progress.replaceChildren(box);
  }

  async function post(url, body) {
    var response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body || {}) });
    var data = await response.json();
    if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'درخواست انجام نشد.');
    return data;
  }

  async function runQueue(id, initial) {
    if (active) return;
    active = true;
    if (start) start.disabled = true;
    var run = initial || {};
    try {
      while (true) {
        var result = await post('/admin/product-shots/imports/' + id + '/process-next');
        run = result.run || run;
        var pct = run.discovered ? Math.round((run.processed / run.discovered) * 100) : 100;
        var note = result.item ? ('\nآخرین مورد: ' + (result.item.title || 'محصول') + (result.item.error ? ' · خطا: ' + result.item.error : '')) : '';
        show('یافت‌شده: ' + run.discovered + ' صفحه · پردازش‌شده: ' + run.processed + ' · محصولات فعال: ' + run.products + ' · خطا: ' + run.failed + ' · ' + pct + '٪' + (result.busy ? '\nیک مورد دیگر در حال پردازش است؛ کمی صبر می‌کنم…' : '') + note, run.failed ? 'warning' : 'info');
        if (result.busy) await new Promise(function (resolve) { window.setTimeout(resolve, 5000); });
        if (result.done) break;
      }
      show('انتقال پایان یافت. ' + run.products + ' محصول فعال ثبت شد؛ ' + run.failed + ' مورد خطا داشت. برای تلاش دوباره از فهرست انتقال‌ها استفاده کنید.', run.failed ? 'warning' : 'success');
    } catch (e) {
      show(e.message || 'انتقال متوقف شد؛ از فهرست می‌توانید دوباره ادامه دهید.', 'danger');
    } finally {
      active = false;
      if (start) start.disabled = false;
    }
  }

  document.getElementById('product-import-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (active) return;
    show('در حال فهرست‌برداری و تطبیق تعداد صفحات…', 'info');
    if (start) start.disabled = true;
    try {
      var data = await post(this.action, { source_url: this.elements.source_url.value });
      show('پیدا شد: ' + data.run.discovered + ' صفحه و برآورد ' + data.estimated_products + ' محصول. انتقال شروع شد.', 'info');
      await runQueue(data.run.id, data.run);
    } catch (error) {
      show(error.message || 'فهرست منبع خوانده نشد.', 'danger');
      if (start) start.disabled = false;
    }
  });

  document.getElementById('product-import-settings')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    var status = document.getElementById('product-import-settings-status');
    var form = this;
    var body = Object.fromEntries(new FormData(form).entries());
    try {
      var response = await fetch(form.action, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
      var result = await response.json();
      if (!response.ok) throw new Error(result.message || 'ذخیره انجام نشد.');
      status.textContent = result.message;
    } catch (error) { status.textContent = error.message; }
  });

  document.querySelectorAll('[data-import-resume]').forEach(function (button) {
    button.addEventListener('click', function () { runQueue(button.dataset.importResume); });
  });
  document.querySelectorAll('[data-import-retry]').forEach(function (button) {
    button.addEventListener('click', async function () {
      try {
        var data = await post('/admin/product-shots/imports/' + button.dataset.importRetry + '/retry-failed');
        await runQueue(button.dataset.importRetry, data.run);
      } catch (error) { show(error.message || 'تلاش دوباره انجام نشد.', 'danger'); }
    });
  });
})();
</script>
