{{-- Admin dashboard scripts — split into partials during standardization (behavior-identical) --}}
@if(in_array($dashboardSection ?? null, ['crm', 'attendance'], true))
  @include('admin.partials.scripts.shamsi-calendar')
@endif
<script src="{{ asset('admin/js/main.js') }}"></script>
@if(in_array($dashboardSection ?? null, ['crm', 'attendance'], true))
  @include('admin.partials.scripts.dashboard-main-js')
  <script src="{{ asset('admin/js/crm-api.js') }}"></script>
@endif
@if(in_array($dashboardSection ?? null, ['ai'], true))
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
@endif
