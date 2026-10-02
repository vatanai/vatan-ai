{{-- ردیف گفتگو در لیست‌های خلاصه: $conversation --}}
@php($siContact = $conversation->contact)
<a href="{{ route('admin.smart-instagram.inbox.show', $conversation) }}" class="si-row">
  <div class="si-avatar is-sm">{{ $siContact?->initials() ?? '؟' }}</div>
  <div class="si-row-main">
    <div class="si-row-title">{{ $siContact?->label() ?? 'مخاطب' }}</div>
    <div class="si-row-sub">{{ $conversation->last_message_preview ?: '—' }}</div>
  </div>
  <div class="si-row-meta">
    @if($conversation->needs_human)<span class="badge-pro badge-danger" title="نیازمند توجه انسانی"><i class="fa-solid fa-circle"></i> انسان</span>@endif
    @if($conversation->last_source)<span class="si-source">{{ \App\Services\SmartInstagram\Ui::label('source', $conversation->last_source) }}</span>@endif
    <span>{{ \App\Services\SmartInstagram\Ui::ago($conversation->last_message_at) }}</span>
  </div>
</a>
