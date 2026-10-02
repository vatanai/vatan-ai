{{-- استودیو تولید › اینستاگرام هوشمند — زیرمنوی سطح سوم (پارشیال مستقل؛ فقط از sidebar-menu صدا زده می‌شود) --}}
@php
  $siActive = request()->is('admin/smart-instagram*');
  $siUnanswered = 0;
  try {
    $siUnanswered = \Illuminate\Support\Facades\Cache::remember('smart-instagram:sidebar-unanswered', 60, function () {
      return \Illuminate\Support\Facades\Schema::hasTable('instagram_conversations')
        ? \App\Models\SmartInstagram\Conversation::query()->whereIn('status', ['new', 'unanswered'])->count()
        : 0;
    });
  } catch (\Throwable $e) {
    $siUnanswered = 0;
  }
  $siLinks = [
    ['route' => 'admin.smart-instagram.dashboard', 'label' => 'داشبورد اینستاگرام', 'active' => request()->is('admin/smart-instagram')],
    ['route' => 'admin.smart-instagram.inbox', 'label' => 'صندوق گفتگو', 'active' => request()->is('admin/smart-instagram/inbox*'), 'badge' => $siUnanswered],
    ['route' => 'admin.smart-instagram.contacts.index', 'label' => 'مشتریان و لیدها', 'active' => request()->is('admin/smart-instagram/contacts*')],
    ['route' => 'admin.smart-instagram.pipeline', 'label' => 'قیف فروش و وظایف', 'active' => request()->is('admin/smart-instagram/pipeline*')],
    ['route' => 'admin.smart-instagram.automations.index', 'label' => 'اتومیشن‌ها', 'active' => request()->is('admin/smart-instagram/automations*')],
    ['route' => 'admin.smart-instagram.knowledge.index', 'label' => 'دانش هوش مصنوعی', 'active' => request()->is('admin/smart-instagram/knowledge*')],
    ['route' => 'admin.smart-instagram.content', 'label' => 'محتوا و فراخوان‌ها', 'active' => request()->is('admin/smart-instagram/content*')],
    ['route' => 'admin.smart-instagram.reports', 'label' => 'گزارش‌ها', 'active' => request()->is('admin/smart-instagram/reports*')],
    ['route' => 'admin.smart-instagram.connections', 'label' => 'اتصال‌ها و تیم', 'active' => request()->is('admin/smart-instagram/connections*')],
    ['route' => 'admin.smart-instagram.health', 'label' => 'سلامت و لاگ‌ها', 'active' => request()->is('admin/smart-instagram/health*')],
  ];
@endphp
<div class="sub-item sub-item-parent {{ $siActive ? 'active' : '' }}" onclick="toggleSubSub('studio-smart-instagram-submenu', this)"><div class="sub-dot"></div><div class="sub-label">اینستاگرام هوشمند</div>@if($siUnanswered > 0)<span class="nav-status-badge warn">{{ $siUnanswered > 99 ? '۹۹+' : strtr((string) $siUnanswered, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }}</span>@endif<i class="fa-solid fa-chevron-down sub-chev"></i></div>
<div class="sub-sub-wrap {{ $siActive ? 'open' : '' }}" id="studio-smart-instagram-submenu"><div class="sub-sub-track">
  @foreach($siLinks as $siLink)
    <a href="{{ route($siLink['route']) }}" class="sub-sub-item {{ $siLink['active'] ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">{{ $siLink['label'] }}</div></a>
  @endforeach
</div></div>
