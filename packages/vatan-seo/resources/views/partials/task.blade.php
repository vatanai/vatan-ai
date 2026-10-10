{{-- یک تسک در برنامه‌ی کار: $task --}}
@php
  $seoDone = $task->status === 'done';
  $seoMsgTone = $task->status === 'needs_action' ? 'is-danger' : ($seoDone ? 'is-success' : '');
  $seoAutoIcon = ['auto' => 'fa-robot', 'assisted' => 'fa-handshake-angle', 'manual' => 'fa-user'][$task->automation] ?? 'fa-user';
@endphp
<div class="seo-task {{ $seoDone ? 'is-done' : '' }}">
  <form method="POST" action="{{ route('seo.tasks.update', $task) }}">@csrf @method('PATCH')
    <input type="hidden" name="status" value="{{ $seoDone ? 'todo' : 'done' }}">
    <button class="seo-task-check" title="{{ $seoDone ? 'برگرداندن به صف' : 'علامت انجام شد' }}"><i class="fa-solid fa-check"></i></button>
  </form>
  <div style="min-width:0">
    <div class="seo-task-title">
      {{ $task->title }}
      @if($task->why) @include('seo::partials.help', ['text' => $task->why, 'title' => 'چرا این تسک؟']) @endif
    </div>
    <div class="seo-task-tags">
      <span class="seo-tag {{ $task->pillar === 'infrastructure' ? 'is-info' : 'is-primary' }}">{{ \Vatan\Seo\Models\Task::PILLARS[$task->pillar] ?? $task->pillar }}</span>
      @if($task->category)<span class="seo-tag">{{ \Vatan\Seo\Models\Task::CATEGORIES[$task->category] ?? $task->category }}</span>@endif
      <span class="seo-tag"><i class="fa-solid {{ $seoAutoIcon }}"></i>{{ \Vatan\Seo\Models\Task::AUTOMATION[$task->automation] ?? '' }}</span>
      @if($task->frequency !== 'once')<span class="seo-tag"><i class="fa-solid fa-rotate"></i>{{ \Vatan\Seo\Models\Task::FREQUENCIES[$task->frequency] ?? '' }}</span>@endif
      @if($task->status !== 'todo')<span class="seo-tag {{ ['done' => 'is-success', 'needs_action' => 'is-danger', 'waiting_approval' => 'is-warning', 'in_progress' => 'is-info', 'skipped' => ''][$task->status] ?? '' }}">{{ \Vatan\Seo\Models\Task::STATUSES[$task->status] ?? $task->status }}</span>@endif
      @if($task->due_on && !$seoDone)<span class="seo-tag {{ $task->isOverdue() ? 'is-danger' : '' }}"><i class="fa-regular fa-calendar"></i>{{ \Vatan\Seo\Support\Fa::date($task->due_on) }}</span>@endif
      @if($seoDone && $task->completed_at)<span class="seo-tag is-success"><i class="fa-solid fa-check"></i>{{ \Vatan\Seo\Support\Fa::ago($task->completed_at) }} · {{ $task->completed_by === 'agent' ? 'ایجنت' : 'شما' }}</span>@endif
      <span class="seo-tag" title="اولویت = تأثیر×۲ − زحمت"><i class="fa-solid fa-signal"></i>اولویت {{ \Vatan\Seo\Support\Fa::n($task->priority) }}</span>
    </div>
    @if($task->last_message)
      <div class="seo-task-msg {{ $seoMsgTone }}">{{ $task->last_message }}
        @if(!empty($task->result['urls']))<div class="seo-muted" style="margin-top:4px;font-size:11px">@foreach(array_slice($task->result['urls'], 0, 5) as $seoU)<div class="seo-ltr" style="display:block;text-align:right">{{ urldecode($seoU) }}</div>@endforeach</div>@endif
        @if(!empty($task->result['items']))<div style="margin-top:6px">@foreach(array_slice($task->result['items'], 0, 6) as $seoI)<div style="font-size:11px">• {{ $seoI['query'] ?? '' }} — رتبه {{ \Vatan\Seo\Support\Fa::n($seoI['position'] ?? 0, 1) }} · CTR {{ \Vatan\Seo\Support\Fa::percent($seoI['ctr'] ?? 0) }}</div>@endforeach</div>@endif
      </div>
    @endif
  </div>
  <div class="seo-task-actions">
    @if($task->check && !$seoDone)
      <form method="POST" action="{{ route('seo.tasks.check', $task) }}">@csrf<button class="seo-icon-btn" title="بررسی خودکار همین حالا" data-loading="بررسی"><i class="fa-solid fa-rotate-right"></i></button></form>
    @endif
    @if($task->keyword_id)<a class="seo-icon-btn" href="{{ route('seo.keywords.show', $task->keyword_id) }}" title="جزئیات کلمه"><i class="fa-solid fa-key"></i></a>@endif
    @if(!$seoDone && $task->status !== 'skipped')
      <form method="POST" action="{{ route('seo.tasks.update', $task) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="skipped"><button class="seo-icon-btn" title="رد کردن (لازم نیست)" data-confirm="رد شود؟"><i class="fa-solid fa-forward"></i></button></form>
    @endif
  </div>
</div>
