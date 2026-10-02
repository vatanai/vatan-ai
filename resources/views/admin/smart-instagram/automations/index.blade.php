@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'اتومیشن‌ها';
  $siSubtitle = 'وقتی این اتفاق افتاد، اگر این شرط برقرار بود، این کار را انجام بده — با سقف دفعات، توقف روی موارد حساس و حالت آزمایشی.';
@endphp

@section('si-actions')
  @if($canManage)<a href="{{ route('admin.smart-instagram.automations.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> قانون تازه</a>@endif
@endsection

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-play', 'tone' => 'success', 'value' => Ui::n($rules->where('status', 'active')->count()), 'label' => 'فعال'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-flask', 'tone' => 'warning', 'value' => Ui::n($rules->where('status', 'test')->count()), 'label' => 'آزمایشی (بدون ارسال)'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-bolt', 'tone' => 'info', 'value' => Ui::n($rules->sum('runs_count')), 'label' => 'کل اجراها'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-bug', 'tone' => $rules->sum('failure_count') ? 'danger' : 'primary', 'value' => Ui::n($rules->sum('failure_count')), 'label' => 'اجرای ناموفق'])
  </div>

  @if($canManage)
    <section style="margin-bottom:16px">
      <div class="si-panel-title" style="margin-bottom:10px"><i class="fa-solid fa-wand-magic-sparkles"></i> سناریوهای آماده</div>
      <div class="si-cards">
        @foreach($templates as $key => $template)
          <a href="{{ route('admin.smart-instagram.automations.create', ['template' => $key]) }}" class="si-tile">
            <div class="si-tile-title"><span class="si-quick-icon"><i class="fa-solid {{ $template['icon'] }}"></i></span>{{ $template['name'] }}</div>
            <div class="si-tile-desc">{{ $template['description'] }}</div>
            <span class="si-link">ساخت در حالت آزمایشی <i class="fa-solid fa-angle-left"></i></span>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  <section class="content-card si-panel is-flush" style="margin-bottom:14px">
    <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-diagram-project"></i> قوانین</div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>نام</th><th>شروع‌کننده</th><th>کلمات</th><th>وضعیت</th><th>نسخه</th><th>اجرا</th><th>نرخ موفقیت</th><th>آخرین اجرا</th><th></th></tr></thead>
        <tbody>
          @forelse($rules as $rule)
            <tr>
              <td><a class="si-td-strong" style="color:var(--text-h)" href="{{ route('admin.smart-instagram.automations.show', $rule) }}">{{ $rule->name }}</a>@if($rule->scope_ref)<div class="si-muted">فقط محتوای <span class="si-mono">{{ \Illuminate\Support\Str::limit($rule->scope_ref, 16) }}</span></div>@endif</td>
              <td>{{ $triggers[$rule->trigger] ?? $rule->trigger }}</td>
              <td class="si-muted">{{ \Illuminate\Support\Str::limit(implode('، ', (array) $rule->keywords), 40) ?: '—' }}</td>
              <td><span class="badge-pro badge-{{ Ui::statusTone($rule->status) }}"><i class="fa-solid fa-circle"></i> {{ $statuses[$rule->status] ?? $rule->status }}</span>@if($rule->last_error)<div class="si-error" title="{{ $rule->last_error }}">دارای خطا</div>@endif</td>
              <td class="si-num">{{ Ui::n($rule->version) }}</td>
              <td class="si-num">{{ Ui::n($rule->runs_count) }}</td>
              <td class="si-num">{{ $rule->successRate() === null ? '—' : Ui::pct($rule->successRate()) }}</td>
              <td class="si-muted">{{ Ui::ago($rule->last_run_at) }}</td>
              <td>
                <div style="display:flex;gap:5px;justify-content:flex-end">
                  <a href="{{ route('admin.smart-instagram.automations.show', $rule) }}" class="icon-action-btn" title="جزئیات و اجراها"><i class="fa-solid fa-eye"></i></a>
                  @if($canManage)
                    <a href="{{ route('admin.smart-instagram.automations.edit', $rule) }}" class="icon-action-btn" title="ویرایش"><i class="fa-solid fa-pen"></i></a>
                    <form method="POST" action="{{ route('admin.smart-instagram.automations.status', $rule) }}" @if($rule->status !== 'active') data-confirm="این قانون روی گفتگوهای واقعی اجرا شود؟ پیشنهاد: ابتدا در حالت آزمایشی بسنجید." @endif>@csrf
                      <input type="hidden" name="status" value="{{ $rule->status === 'active' ? 'paused' : 'active' }}">
                      <button class="icon-action-btn" title="{{ $rule->status === 'active' ? 'توقف' : 'فعال‌سازی' }}"><i class="fa-solid {{ $rule->status === 'active' ? 'fa-pause' : 'fa-play' }}"></i></button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="si-table-empty">هنوز قانونی نساخته‌اید؛ از سناریوهای آماده شروع کنید.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="content-card si-panel is-flush">
    <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-clock-rotate-left"></i> آخرین اجراها</div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>قانون</th><th>مخاطب</th><th>حالت</th><th>نتیجه</th><th>زمان</th></tr></thead>
        <tbody>
          @forelse($recentRuns as $run)
            <tr>
              <td>{{ $run->rule?->name ?? '—' }} <span class="si-muted">· نسخه {{ Ui::n($run->rule_version) }}</span></td>
              <td>{{ $run->contact?->label() ?? '—' }}</td>
              <td>{{ $run->mode === 'test' ? 'آزمایشی' : 'واقعی' }}</td>
              <td><span class="badge-pro badge-{{ Ui::statusTone($run->status) }}">{{ Ui::label('run', $run->status) }}</span></td>
              <td class="si-muted">{{ Ui::ago($run->created_at) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="si-table-empty">اجرایی ثبت نشده.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
