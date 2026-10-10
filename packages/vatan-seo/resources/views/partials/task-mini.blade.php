{{-- تسک فشرده در تقویم ۹۰ روزه: $task --}}
@php
  $seoDone = $task->status === 'done';
  $seoTone = ['done' => 'success', 'needs_action' => 'danger', 'skipped' => '', 'waiting_approval' => 'warning'][$task->status] ?? ($task->isOverdue() ? 'warning' : 'info');
  $seoIcon = ['auto' => 'fa-robot', 'assisted' => 'fa-handshake-angle', 'manual' => 'fa-user'][$task->automation] ?? 'fa-user';
@endphp
<div class="seo-mini {{ $seoDone ? 'is-done' : '' }} {{ $task->status === 'skipped' ? 'is-skipped' : '' }}">
  <form method="POST" action="{{ route('seo.tasks.update', $task) }}">@csrf @method('PATCH')
    <input type="hidden" name="status" value="{{ $seoDone ? 'todo' : 'done' }}">
    <button class="seo-mini-check is-{{ $seoTone }}" title="{{ $seoDone ? 'برگرداندن' : 'انجام شد' }}"><i class="fa-solid fa-check"></i></button>
  </form>
  <div class="seo-mini-body">
    <div class="seo-mini-title">{{ $task->title }}</div>
    <div class="seo-mini-meta">
      <i class="fa-solid {{ $seoIcon }}" title="{{ \Vatan\Seo\Models\Task::AUTOMATION[$task->automation] ?? '' }}"></i>
      <span>{{ ['keyword' => 'کلمه', 'weekly' => 'هفتگی', 'milestone' => 'نقطه‌ی عطف', 'opportunity' => 'استراتژیست', 'audit' => \Vatan\Seo\Models\Task::PILLARS[$task->pillar] ?? ''][$task->kind] ?? '' }}</span>
      @if($task->status === 'needs_action')<span class="tone-danger">نیاز به اقدام</span>@endif
    </div>
    @if($task->last_message && $task->status === 'needs_action')<div class="seo-mini-msg">{{ \Illuminate\Support\Str::limit($task->last_message, 110) }}</div>@endif
  </div>
  @if($task->why)@include('seo::partials.help', ['text' => $task->why, 'title' => 'چرا این تسک؟'])@endif
</div>
