@php
  $cfTone = ['queued' => '', 'scripting' => 'is-info', 'rendering' => 'is-info', 'review' => 'is-warning', 'revision' => 'is-warning',
    'approved' => 'is-success', 'published' => 'is-success', 'rejected' => 'is-danger'][$s] ?? '';
@endphp
<span class="cf-pill {{ $cfTone }}">{{ \Vatan\Content\Support\Fa::label('production_status', $s) }}</span>
