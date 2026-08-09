@extends('layouts.admin')
@section('title', 'گام چهارم آزمایش — آزمایشگاه')

@section('content')
@php
  $settings = (array) $experiment->settings;
  $isComplete = $experiment->status === 'completed';
  $isFailed = $experiment->status === 'failed';
  $isRunning = in_array($experiment->status, ['queued', 'processing'], true);
  $exchangeToman = (float) (($exchange['rate'] ?? 0) / 10);
  $timelineState = $isComplete ? 4 : ($isFailed ? 2 : ($experiment->status === 'processing' ? 2 : 1));
@endphp
<main id="lab-result-page" class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl" style="background:var(--page-bg);">
    @if(session('success'))<div class="lab-result-alert success mb-4"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>@endif
    @if(session('error'))<div class="lab-result-alert danger mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span>{{ session('error') }}</span></div>@endif

    <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
      <div>
        <div class="flex items-center gap-2 mb-1.5">
          <a href="{{ route('admin.lab.index') }}" class="icon-action-btn" title="بازگشت"><i class="fa-solid fa-arrow-right"></i></a>
          <span class="lab-page-icon"><i class="fa-solid fa-flask"></i></span>
          <h1 class="text-xl font-extrabold" style="color:var(--text-h);">گام چهارم: اجرای آزمایش</h1>
        </div>
        <p class="text-[11px] mr-[88px]" style="color:var(--text-soft);">{{ $experiment->title }} · {{ $experiment->product->name_fa }}</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="badge-pro {{ $isComplete ? 'badge-success' : ($isFailed ? 'badge-danger' : 'badge-warning') }}">{{ $experiment->status_label }}</span>
        @if($experiment->applied_at)<span class="badge-pro badge-success"><i class="fa-solid fa-link"></i> اعمال‌شده روی محصول</span>@endif
        @if($isComplete)
          <form method="POST" action="{{ route('admin.lab.apply', $experiment) }}">@csrf<button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-check-double"></i> اعمال نتیجه روی محصول</button></form>
        @endif
        <a href="{{ route('admin.lab.create', ['duplicate_id' => $experiment->id]) }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-sliders"></i> تکثیر و تغییر مدل‌ها</a>
        <form method="POST" action="{{ route('admin.lab.duplicate', $experiment) }}">@csrf<button class="btn-pro btn-pro-ghost" type="submit"><i class="fa-solid fa-copy"></i> تکثیر آزمایش</button></form>
        @if($isRunning)
          <form method="POST" action="{{ route('admin.lab.cancel', $experiment) }}">@csrf<button class="btn-pro btn-pro-ghost" type="submit">لغو آزمایش</button></form>
        @endif
      </div>
    </div>

    <div class="lab-result-stepper mb-5">
      @foreach([
        ['ثبت محصول', 'محصول و تصاویر', true],
        ['انتخاب مدل‌ها', 'مدل‌های تصویری', true],
        ['تنظیمات', 'مرور و شروع', true],
        ['اجرای آزمایش', 'خروجی و ارزیابی', true],
      ] as $index => $step)
        @if($index > 0)<span class="lab-result-connector {{ $index < 3 || $isComplete ? 'done' : '' }}"></span>@endif
        <div class="lab-result-step {{ $index === 3 ? 'current' : ($index < 3 || $isComplete ? 'done' : '') }}">
          <span class="lab-result-circle">@if($index < 3 || $isComplete)<i class="fa-solid fa-check"></i>@else{{ $index + 1 }}@endif</span>
          <span><small>گام {{ $index + 1 }}</small><strong>{{ $step[0] }}</strong><em>{{ $step[1] }}</em></span>
        </div>
      @endforeach
    </div>

    <section class="content-card p-5 mb-4 lab-execution-card">
      <div class="lab-execution-heading">
        <span class="lab-execution-icon {{ $isComplete ? 'complete' : ($isFailed ? 'failed' : 'running') }}">
          <i class="fa-solid {{ $isComplete ? 'fa-check' : ($isFailed ? 'fa-xmark' : 'fa-spinner fa-spin') }}"></i>
        </span>
        <div>
          <h2>{{ $isComplete ? 'آزمایش کامل شد' : ($isFailed ? 'اجرای آزمایش با خطا متوقف شد' : 'آزمایش در حال اجراست') }}</h2>
          <p>{{ $isComplete ? 'خروجی‌ها آماده‌ی مقایسه و ارزیابی هستند.' : ($isFailed ? 'می‌توانید اجرای ناموفق را دوباره در صف قرار دهید.' : 'این صفحه به‌صورت خودکار وضعیت مدل‌ها را بررسی می‌کند.') }}</p>
        </div>
      </div>

      <div class="lab-timeline" aria-label="مراحل اجرای آزمایش">
        @foreach([
          ['تنظیمات ثبت شد', 'پرامپت و تصاویر مرجع ذخیره شدند.'],
          ['تولید خروجی', $experiment->status === 'queued' ? 'در انتظار شروع صف اجرا.' : 'مدل انتخاب‌شده در حال تولید خروجی است.'],
          ['ارزیابی هوش مصنوعی', $isComplete ? 'خروجی‌ها توسط ارزیاب بررسی شدند.' : 'پس از تولید خروجی انجام می‌شود.'],
          ['مقایسه نهایی', $isComplete ? 'خروجی‌ها برای مقایسه آماده‌اند.' : 'پس از پایان همه‌ی مدل‌ها فعال می‌شود.'],
        ] as $index => $timeline)
          @php
            $timelineIndex = $index + 1;
            $timelineDone = $isComplete || $timelineIndex === 1;
            $timelineActive = !$timelineDone && (($timelineIndex === 2 && !$isComplete) || ($timelineIndex === 3 && $isComplete));
            $timelineFailed = $isFailed && $timelineIndex === 2;
          @endphp
          <div class="lab-timeline-item {{ $timelineDone ? 'done' : '' }} {{ $timelineActive ? 'active' : '' }} {{ $timelineFailed ? 'failed' : '' }}">
            <span class="lab-timeline-dot">@if($timelineDone)<i class="fa-solid fa-check"></i>@elseif($timelineFailed)<i class="fa-solid fa-xmark"></i>@elseif($timelineActive)<i class="fa-solid fa-spinner fa-spin"></i>@else{{ $timelineIndex }}@endif</span>
            <span class="lab-timeline-copy"><strong>{{ $timeline[0] }}</strong><small>{{ $timeline[1] }}</small></span>
          </div>
        @endforeach
      </div>

      @if($isRunning)
        <div class="lab-loading-message"><i class="fa-solid fa-arrows-rotate fa-spin"></i><span>منتظر دریافت خروجی کامل مدل و ارزیابی هوش مصنوعی هستیم…</span></div>
      @elseif($isFailed)
        <div class="lab-loading-message failed"><i class="fa-solid fa-circle-exclamation"></i><span>حداقل یک اجرا ناموفق بوده است؛ جزئیات هر مدل پایین همین صفحه نمایش داده می‌شود.</span></div>
      @endif
    </section>

    <section class="content-card overflow-hidden mb-4">
      <div class="p-4 border-b" style="border-color:var(--border);"><h2 class="text-sm font-extrabold" style="color:var(--text-h);">جدول محاسبه دقیق</h2></div>
      <div class="overflow-x-auto"><table class="table-pro"><thead><tr><th>محصول</th><th>کد گزارش</th><th>تعداد مدل</th><th>زمان کل</th><th>قیمت دلار</th><th>قیمت تومان</th><th>هزینه آزمایش دلار</th><th>هزینه آزمایش تومان</th><th>نمره نهایی</th></tr></thead><tbody><tr><td>{{ $experiment->product_name_snapshot ?: $experiment->product?->name_fa }}</td><td dir="ltr">{{ $experiment->report_code ?: '—' }}</td><td>{{ $experiment->models_count ?: $experiment->runs->count() }}</td><td>{{ $experiment->runs->sum('build_seconds') ?: ($experiment->runs->sum('duration_ms') ? number_format($experiment->runs->sum('duration_ms') / 1000, 2) : '—') }} ثانیه</td><td dir="ltr">${{ number_format((float)($experiment->total_cost_usd ?: $experiment->actual_cost_usd ?: $experiment->estimated_cost_usd), 4) }}</td><td>{{ number_format((float)($experiment->total_cost_toman ?: $experiment->actual_cost_toman ?: $experiment->estimated_cost_toman)) }}</td><td dir="ltr">${{ number_format((float)$experiment->lab_cost_usd, 4) }}</td><td>{{ number_format((float)$experiment->lab_cost_toman) }}</td><td>{{ $experiment->overall_score ? number_format((float)$experiment->overall_score, 1).' از ۱۰' : '—' }}</td></tr></tbody></table></div>
    </section>

    <section class="content-card overflow-hidden mb-4">
      <div class="p-4 border-b" style="border-color:var(--border);"><h2 class="text-sm font-extrabold" style="color:var(--text-h);">نمره مدیر سایت</h2><p class="text-[10px] mt-1" style="color:var(--text-soft);">نمره، شباهت، کیفیت جزئیات و اولویت استفاده هر خروجی را ثبت کنید؛ اطلاعات بلافاصله در جدول محاسبه دقیق ذخیره می‌شود.</p></div>
      <div class="overflow-x-auto"><table class="table-pro"><thead><tr><th>مدل</th><th>نمره ۱ تا ۱۰</th><th>شباهت</th><th>کیفیت جزئیات</th><th>اولویت استفاده</th><th>ثبت</th></tr></thead><tbody>
      @foreach($experiment->runs as $run)
        @foreach($run->outputs as $output)
          @php
            $manager = $output->managerScore;
          @endphp
          <tr>
            <td>{{ $run->alias ?: $run->model_id }}</td>
            <td colspan="4">
              <form id="manager-score-{{ $output->id }}" method="POST" action="{{ route('admin.lab.outputs.manager-score', $output) }}" class="flex gap-2 flex-wrap">
                @csrf
                <input class="input-pro" style="width:82px" type="number" min="1" max="10" name="overall_score" value="{{ $manager?->overall_score }}" placeholder="۱–۱۰">
                <select class="input-pro" name="similarity_score"><option value="">شباهت</option>@foreach(['خیلی کم','کم','متوسط','زیاد','خیلی زیاد'] as $v)<option @selected($manager?->similarity_score === $v)>{{ $v }}</option>@endforeach</select>
                <select class="input-pro" name="detail_quality"><option value="">جزئیات</option>@foreach(['ضعیف','قابل قبول','خوب','عالی'] as $v)<option @selected($manager?->detail_quality === $v)>{{ $v }}</option>@endforeach</select>
                <input class="input-pro" style="width:90px" type="number" min="1" max="{{ max(1, $experiment->runs->count()) }}" name="usage_priority" value="{{ $manager?->usage_priority }}" placeholder="اولویت">
              </form>
            </td>
            <td><button class="btn-pro btn-pro-primary" type="submit" form="manager-score-{{ $output->id }}">ذخیره</button></td>
          </tr>
        @endforeach
      @endforeach
      </tbody></table></div>
    </section>

    <section class="content-card overflow-hidden mb-4">
      <div class="p-4 border-b" style="border-color:var(--border);">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <div><h2 class="text-sm font-extrabold" style="color:var(--text-h);">خروجی‌های آزمایش</h2><p class="text-[10px] mt-1" style="color:var(--text-soft);">خروجی همه‌ی مدل‌ها براساس گرید کنار هم نمایش داده شده تا مقایسه و انتخاب برنده‌ی هر گرید ساده باشد.</p></div>
          <div class="lab-output-settings"><span>کیفیت <b dir="ltr">{{ data_get($settings, 'resolution', '720') }}</b></span><span>نسبت <b dir="ltr">{{ data_get($settings, 'aspect_ratio', '4:5') }}</b></span><span>ارزیاب <b dir="ltr">{{ data_get($settings, 'scoring_model', 'openai/gpt-4o-mini') }}</b></span></div>
        </div>
      </div>

      <div class="lab-output-model-grid">
        @forelse($experiment->runs as $run)
          @php
            $runCost = (float) $run->actual_cost_usd ?: (float) $run->estimated_cost_usd;
            $runToman = $runCost * $exchangeToman;
          @endphp
          <article class="lab-result-model-card">
            <header class="lab-result-model-head">
              <div class="flex items-center gap-2 min-w-0"><span class="lab-model-header-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span><div class="min-w-0"><strong>{{ $run->grade_label ?: 'استاندارد' }} · {{ $run->alias ?: $run->model_id }}</strong><small dir="ltr">{{ $run->provider }} · {{ $run->model_id }} · {{ $run->role === 'fallback' ? 'جایگزین' : 'اصلی' }}</small></div></div>
              <span class="badge-pro {{ $run->status === 'completed' ? 'badge-success' : ($run->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">{{ $run->status_label }}</span>
            </header>

            <div class="lab-result-output-list">
              @forelse($run->outputs as $output)
                <figure class="lab-result-output">
                  @if($output->url)<a href="{{ $output->url }}" target="_blank" rel="noopener"><img src="{{ $output->url }}" alt="خروجی {{ $run->alias }}" loading="lazy"></a>@else<div class="lab-output-placeholder"><i class="fa-regular fa-image"></i></div>@endif
                </figure>
              @empty
                <div class="lab-output-placeholder"><i class="fa-solid {{ $run->status === 'failed' ? 'fa-triangle-exclamation' : 'fa-spinner fa-spin' }}"></i><span>{{ $run->status === 'failed' ? $run->error_message : 'در انتظار خروجی…' }}</span></div>
              @endforelse
            </div>

            <div class="lab-result-meta-grid">
              <div><span>زمان اجرا</span><strong>{{ $run->duration_ms ? number_format($run->duration_ms / 1000, 1) . ' ثانیه' : '—' }}</strong></div>
              <div><span>هزینه دلاری</span><strong dir="ltr">${{ number_format($runCost, 4) }}</strong></div>
              <div><span>هزینه تومانی</span><strong>{{ number_format($runToman) }} تومان</strong></div>
              <div><span>تعداد خروجی</span><strong>{{ $run->outputs->count() }} تصویر</strong></div>
            </div>
          </article>
        @empty
          <div class="lab-output-placeholder"><i class="fa-solid fa-hourglass-half"></i><span>اجراها هنوز ساخته نشده‌اند.</span></div>
        @endforelse
      </div>
    </section>

    <section class="content-card overflow-hidden mb-4">
      <div class="p-4 border-b" style="border-color:var(--border);"><h2 class="text-sm font-extrabold" style="color:var(--text-h);">رتبه‌بندی سه گرید</h2><p class="text-[10px] mt-1" style="color:var(--text-soft);">نمره‌ی نهایی ترکیبی از امتیاز هوش مصنوعی و امتیاز مدیر است؛ برای هر گرید فقط یک مدل به‌عنوان انتخاب‌شده ثبت می‌شود.</p></div>
      <div class="overflow-x-auto">
        <table class="lab-evaluation-table">
          <thead><tr><th>گرید</th><th>مدل</th><th>نقش</th><th>رتبه</th><th>نمره نهایی</th><th>وضعیت</th></tr></thead>
          <tbody>
            @forelse($experiment->runs->groupBy('grade_label') as $gradeLabel => $gradeRuns)
              @foreach($gradeRuns->sortBy('rank') as $run)
                <tr><td><strong>{{ $gradeLabel ?: 'استاندارد' }}</strong><small>{{ data_get($settings, 'grades.'.($run->grade_key ?: 'standard').'.resolution', data_get($settings, 'resolution', '720')) }} · {{ data_get($settings, 'grades.'.($run->grade_key ?: 'standard').'.aspect_ratio', data_get($settings, 'aspect_ratio', '4:5')) }}</small></td><td><strong>{{ $run->alias ?: $run->model_id }}</strong><small dir="ltr">{{ $run->provider }} · {{ $run->model_id }}</small></td><td>{{ $run->role === 'fallback' ? 'جایگزین' : 'اصلی' }}</td><td>{{ $run->rank ? '#' . $run->rank : '—' }}</td><td class="lab-ai-score">{{ $run->final_score ? number_format((float) $run->final_score, 1) . ' از ۵' : 'در انتظار نمره' }}</td><td>@if($run->is_selected)<span class="lab-winner"><i class="fa-solid fa-trophy"></i> انتخاب‌شده</span>@else<span style="color:var(--text-soft);">—</span>@endif</td></tr>
              @endforeach
            @empty
              <tr><td colspan="6" class="lab-table-empty">بعد از اجرای مدل‌ها رتبه‌بندی اینجا نمایش داده می‌شود.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="content-card overflow-hidden mb-4">
      <div class="p-4 border-b" style="border-color:var(--border);"><h2 class="text-sm font-extrabold" style="color:var(--text-h);">نمره‌دهی و توضیحات</h2><p class="text-[10px] mt-1" style="color:var(--text-soft);">امتیاز هوش مصنوعی به‌صورت خودکار ثبت شده و امتیاز دستی و یادداشت شما نیز در همین جدول ذخیره می‌شود.</p></div>
      <div class="overflow-x-auto">
        <table class="lab-evaluation-table">
          <thead><tr><th>مدل / خروجی</th><th>ارزیابی هوش مصنوعی</th><th>جزئیات ارزیابی</th><th>امتیاز دستی</th><th>برنده</th><th>یادداشت و ذخیره</th></tr></thead>
          <tbody>
            @forelse($experiment->runs as $run)
              @forelse($run->outputs as $output)
                @php
                  $aiScores = $output->scores->where('evaluator_type', 'ai');
                  $aiAverage = $aiScores->count() ? $aiScores->avg('score') : null;
                  $aiSummary = data_get($output->metadata, 'ai_evaluation.summary');
                @endphp
                <tr>
                  <td><strong>{{ $run->alias ?: $run->model_id }}</strong><small>#{{ $output->id }}</small></td>
                  <td><span class="lab-ai-score">{{ $aiAverage !== null ? number_format($aiAverage, 1) . ' از ۵' : 'در انتظار ارزیابی' }}</span><small>{{ data_get($output->metadata, 'ai_evaluation.model', data_get($settings, 'scoring_model', 'openai/gpt-4o-mini')) }}</small></td>
                  <td><div class="lab-score-breakdown">@forelse($aiScores as $score)<span>{{ $score->criterion }}: <b>{{ $score->score }}/۵</b></span>@empty<span>جزئیات هنوز ثبت نشده</span>@endforelse</div>@if($aiSummary)<p class="lab-score-summary">{{ $aiSummary }}</p>@endif</td>
                  <td><span class="lab-manual-score">{{ $output->manual_score ? $output->manual_score . ' از ۵' : '—' }}</span></td>
                  <td>@if($output->is_winner)<span class="lab-winner"><i class="fa-solid fa-trophy"></i> انتخاب‌شده</span>@else<span class="text-[var(--text-soft)]">—</span>@endif</td>
                  <td>
                    <form method="POST" action="{{ route('admin.lab.outputs.score', $output) }}" class="lab-score-form">
                      @csrf
                      <div class="flex items-center gap-2"><input type="number" name="manual_score" min="1" max="5" step=".1" value="{{ $output->manual_score }}" class="input-pro" placeholder="۱ تا ۵"><label><input type="checkbox" name="is_winner" value="1" @checked($output->is_winner)> برنده</label></div>
                      <div class="flex items-center gap-2 mt-2"><input type="text" name="note" value="{{ $output->note }}" class="input-pro" placeholder="یادداشت این خروجی"><button class="icon-action-btn" title="ذخیره ارزیابی"><i class="fa-solid fa-floppy-disk"></i></button></div>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="lab-table-empty">برای مدل «{{ $run->alias ?: $run->model_id }}» هنوز خروجی قابل ارزیابی وجود ندارد.</td></tr>
              @endforelse
            @empty
              <tr><td colspan="6" class="lab-table-empty">بعد از تولید خروجی، جدول ارزیابی تکمیل می‌شود.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="content-card p-4">
      <h2 class="text-xs font-extrabold mb-3" style="color:var(--text-h);">لاگ آزمایش</h2>
      <div class="lab-audit-list">
        @forelse($experiment->auditLogs as $log)<div><span>{{ $log->action }}</span><time>{{ \App\Support\Jalali::formatNumeric($log->created_at) }}</time></div>@empty<span style="color:var(--text-soft);">لاگی ثبت نشده است.</span>@endforelse
      </div>
    </section>
  </div>
</main>
@endsection

@push('styles')
<style>
  #lab-result-page :focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
  .lab-page-icon { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; color:var(--primary); background:var(--primary-l); font-size:14px; }
  .lab-result-alert { display:flex; align-items:center; gap:9px; padding:11px 13px; border:1px solid var(--border); border-radius:10px; font-size:11px; }
  .lab-result-alert.success { color:var(--success); background:color-mix(in srgb, var(--success) 8%, transparent); border-color:color-mix(in srgb, var(--success) 30%, transparent); }
  .lab-result-alert.danger { color:var(--danger); background:color-mix(in srgb, var(--danger) 8%, transparent); border-color:color-mix(in srgb, var(--danger) 30%, transparent); }
  .lab-result-stepper { display:flex; align-items:center; gap:0; padding:6px; border:1px solid var(--border); border-radius:12px; background:var(--card-bg); }
  .lab-result-step { flex:1; display:flex; align-items:center; gap:10px; min-width:0; padding:10px; border-radius:9px; color:var(--text-soft); }
  .lab-result-step.current { color:var(--text-h); background:var(--primary-l); border:1px solid var(--primary); }
  .lab-result-circle { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border:2px solid color-mix(in srgb, var(--text-soft) 42%, transparent); border-radius:999px; color:var(--text-soft); font-size:11px; font-weight:800; }
  .lab-result-step.done .lab-result-circle, .lab-result-step.current .lab-result-circle { border-color:var(--primary); color:var(--primary); background:var(--card-bg); }
  .lab-result-step small, .lab-result-step strong, .lab-result-step em { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-style:normal; }
  .lab-result-step small { font-size:9px; color:var(--text-soft); }
  .lab-result-step strong { margin-top:2px; color:var(--text-h); font-size:11px; }
  .lab-result-step em { margin-top:2px; color:var(--text-soft); font-size:9px; }
  .lab-result-connector { width:24px; height:1px; flex-shrink:0; background:var(--border); }
  .lab-result-connector.done { background:var(--primary); }
  .lab-execution-heading { display:flex; align-items:center; gap:12px; }
  .lab-execution-icon { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:12px; font-size:16px; }
  .lab-execution-icon.running { color:var(--primary); background:var(--primary-l); }
  .lab-execution-icon.complete { color:var(--success); background:color-mix(in srgb, var(--success) 10%, transparent); }
  .lab-execution-icon.failed { color:var(--danger); background:color-mix(in srgb, var(--danger) 10%, transparent); }
  .lab-execution-heading h2 { margin:0; color:var(--text-h); font-size:14px; font-weight:800; }
  .lab-execution-heading p { margin:4px 0 0; color:var(--text-soft); font-size:10px; }
  .lab-timeline { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:0; margin-top:24px; }
  .lab-timeline-item { position:relative; display:flex; gap:8px; min-width:0; padding-left:14px; }
  .lab-timeline-item:not(:last-child)::after { content:''; position:absolute; top:13px; left:0; right:28px; height:1px; background:var(--border); transform:translateX(-50%); }
  .lab-timeline-item.done:not(:last-child)::after { background:var(--primary); }
  .lab-timeline-dot { position:relative; z-index:1; width:27px; height:27px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border:2px solid var(--border); border-radius:999px; color:var(--text-soft); background:var(--card-bg); font-size:9px; }
  .lab-timeline-item.done .lab-timeline-dot { border-color:var(--success); color:var(--success); }
  .lab-timeline-item.active .lab-timeline-dot { border-color:var(--primary); color:var(--primary); box-shadow:0 0 0 4px var(--primary-l); }
  .lab-timeline-item.failed .lab-timeline-dot { border-color:var(--danger); color:var(--danger); }
  .lab-timeline-copy { min-width:0; }
  .lab-timeline-copy strong, .lab-timeline-copy small { display:block; }
  .lab-timeline-copy strong { color:var(--text-h); font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .lab-timeline-copy small { margin-top:3px; color:var(--text-soft); font-size:8px; line-height:1.7; }
  .lab-loading-message { display:flex; align-items:center; gap:8px; margin-top:20px; padding:10px 12px; border-radius:9px; background:var(--input-bg); color:var(--primary); font-size:10px; }
  .lab-loading-message.failed { color:var(--danger); }
  .lab-output-settings { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  .lab-output-settings span { padding:5px 8px; border:1px solid var(--border); border-radius:7px; color:var(--text-soft); background:var(--input-bg); font-size:9px; }
  .lab-output-settings b { color:var(--text-h); }
  .lab-output-model-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:2px; background:var(--border); }
  .lab-result-model-card { min-width:0; background:var(--card-bg); }
  .lab-result-model-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:12px; border-bottom:1px solid var(--border); }
  .lab-model-header-icon { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:9px; background:var(--primary-l); color:var(--primary); font-size:12px; }
  .lab-result-model-head strong, .lab-result-model-head small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .lab-result-model-head strong { color:var(--text-h); font-size:11px; }
  .lab-result-model-head small { margin-top:3px; color:var(--text-soft); font-size:8px; }
  .lab-result-output-list { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:2px; background:var(--border); }
  .lab-result-output { margin:0; min-width:0; background:var(--input-bg); }
  .lab-result-output img { display:block; width:100%; aspect-ratio:4/5; object-fit:contain; background:var(--input-bg); }
  .lab-output-placeholder { min-height:220px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:var(--text-soft); background:var(--input-bg); font-size:10px; text-align:center; padding:16px; }
  .lab-output-placeholder i { font-size:22px; }
  .lab-result-meta-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:6px; padding:10px; }
  .lab-result-meta-grid div { min-width:0; padding:7px; border:1px solid var(--border); border-radius:7px; background:var(--input-bg); }
  .lab-result-meta-grid span, .lab-result-meta-grid strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .lab-result-meta-grid span { color:var(--text-soft); font-size:8px; }
  .lab-result-meta-grid strong { margin-top:3px; color:var(--text-h); font-size:9px; }
  .lab-evaluation-table { width:100%; min-width:980px; border-collapse:collapse; }
  .lab-evaluation-table th, .lab-evaluation-table td { padding:11px 10px; border-bottom:1px solid var(--border); text-align:right; vertical-align:top; font-size:10px; }
  .lab-evaluation-table th { color:var(--text-soft); background:var(--input-bg); font-size:9px; font-weight:700; white-space:nowrap; }
  .lab-evaluation-table td { color:var(--text-main); }
  .lab-evaluation-table td > strong, .lab-evaluation-table td > small { display:block; }
  .lab-evaluation-table td > strong { color:var(--text-h); font-size:10px; }
  .lab-evaluation-table td > small { margin-top:4px; color:var(--text-soft); font-size:8px; direction:ltr; }
  .lab-ai-score { display:block; color:var(--primary); font-weight:800; }
  .lab-score-breakdown { display:flex; gap:4px; flex-wrap:wrap; max-width:230px; }
  .lab-score-breakdown span { padding:4px 6px; border-radius:6px; color:var(--text-soft); background:var(--input-bg); font-size:8px; }
  .lab-score-breakdown b { color:var(--text-h); }
  .lab-score-summary { max-width:230px; margin-top:5px; color:var(--text-soft); font-size:8px; line-height:1.7; }
  .lab-manual-score, .lab-winner { color:var(--text-h); font-weight:700; white-space:nowrap; }
  .lab-winner { color:var(--success); }
  .lab-score-form { min-width:230px; }
  .lab-score-form .input-pro { min-width:0; flex:1; height:30px; font-size:9px; }
  .lab-score-form label { display:flex; align-items:center; gap:4px; flex-shrink:0; color:var(--text-soft); font-size:9px; white-space:nowrap; }
  .lab-table-empty { padding:25px !important; color:var(--text-soft) !important; text-align:center !important; }
  .lab-audit-list { display:grid; gap:7px; }
  .lab-audit-list > div { display:flex; align-items:center; justify-content:space-between; gap:10px; padding-bottom:7px; border-bottom:1px solid var(--border); color:var(--text-soft); font-size:9px; }
  .lab-audit-list time { direction:ltr; }
  @media (max-width: 900px) { .lab-timeline { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px 4px; } .lab-timeline-item:not(:last-child)::after { display:none; } }
  @media (max-width: 767px) { .lab-result-stepper { display:grid; grid-template-columns:1fr; gap:3px; } .lab-result-connector { display:none; } .lab-result-step { padding:8px; } .lab-timeline { grid-template-columns:1fr; gap:10px; } .lab-result-meta-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } .lab-output-settings { width:100%; } }
</style>
@endpush

@section('scripts')
@if($isRunning)
<script>window.setTimeout(() => window.location.reload(), 4000);</script>
@endif
<script>document.addEventListener('click', function (event) { const button = event.target.closest('button[form^="manager-score-"]'); if (!button) return; event.preventDefault(); const form = button.closest('tr')?.querySelector('form'); if (form) form.submit(); });</script>
@endsection
